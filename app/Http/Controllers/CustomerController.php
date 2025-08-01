<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\PppSecret;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    /**
     * Display a listing of the customers.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $customers = Customer::orderBy('name')->paginate(15);
        return view('customers.index', compact('customers'));
    }

    /**
     * Show the form for creating a new customer.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('customers.create');
    }

    /**
     * Store a newly created customer in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'identity_card_number' => 'nullable|string|max:50',
            'identity_card_type' => 'nullable|string|max:50',
            'location' => 'nullable|string|max:255',
            'coordinates' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
            'registered_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return redirect()->route('customers.create')
                ->withErrors($validator)
                ->withInput();
        }

        $data = $validator->validated();
        
        // Set default registered date if not provided
        if (empty($data['registered_date'])) {
            $data['registered_date'] = now();
        }

        $customer = Customer::create($data);

        return redirect()->route('customers.show', $customer->id)
            ->with('success', 'Customer created successfully.');
    }

    /**
     * Display the specified customer.
     *
     * @param  \App\Models\Customer  $customer
     * @return \Illuminate\Http\Response
     */
    public function show(Customer $customer)
    {
        $pppSecrets = $customer->pppSecrets()->with('pppProfile')->get();
        $invoices = $customer->invoices()->orderBy('invoice_date', 'desc')->get();
        $payments = $customer->payments()->orderBy('payment_date', 'desc')->get();
        
        return view('customers.show', compact('customer', 'pppSecrets', 'invoices', 'payments'));
    }

    /**
     * Show the form for editing the specified customer.
     *
     * @param  \App\Models\Customer  $customer
     * @return \Illuminate\Http\Response
     */
    public function edit(Customer $customer)
    {
        return view('customers.edit', compact('customer'));
    }

    /**
     * Update the specified customer in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Customer  $customer
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Customer $customer)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'identity_card_number' => 'nullable|string|max:50',
            'identity_card_type' => 'nullable|string|max:50',
            'location' => 'nullable|string|max:255',
            'coordinates' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
            'registered_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return redirect()->route('customers.edit', $customer->id)
                ->withErrors($validator)
                ->withInput();
        }

        $customer->update($validator->validated());

        return redirect()->route('customers.show', $customer->id)
            ->with('success', 'Customer updated successfully.');
    }

    /**
     * Remove the specified customer from storage.
     *
     * @param  \App\Models\Customer  $customer
     * @return \Illuminate\Http\Response
     */
    public function destroy(Customer $customer)
    {
        // Check if customer has PPP secrets
        if ($customer->pppSecrets()->count() > 0) {
            return redirect()->route('customers.show', $customer->id)
                ->with('error', 'Cannot delete customer with active PPP secrets. Please delete all PPP secrets first.');
        }

        // Check if customer has invoices
        if ($customer->invoices()->count() > 0) {
            return redirect()->route('customers.show', $customer->id)
                ->with('error', 'Cannot delete customer with invoices. Please delete all invoices first.');
        }

        // Check if customer has payments
        if ($customer->payments()->count() > 0) {
            return redirect()->route('customers.show', $customer->id)
                ->with('error', 'Cannot delete customer with payments. Please delete all payments first.');
        }

        $customer->delete();

        return redirect()->route('customers.index')
            ->with('success', 'Customer deleted successfully.');
    }

    /**
     * Search for customers.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function search(Request $request)
    {
        $query = $request->input('query');
        
        if (empty($query)) {
            return redirect()->route('customers.index');
        }

        $customers = Customer::where('name', 'like', "%{$query}%")
            ->orWhere('phone', 'like', "%{$query}%")
            ->orWhere('email', 'like', "%{$query}%")
            ->orWhere('identity_card_number', 'like', "%{$query}%")
            ->orWhere('address', 'like', "%{$query}%")
            ->orderBy('name')
            ->paginate(15);

        return view('customers.index', compact('customers', 'query'));
    }

    /**
     * Display customers with overdue invoices.
     *
     * @return \Illuminate\Http\Response
     */
    public function overdue()
    {
        $customers = Customer::whereHas('invoices', function ($query) {
                $query->where('status', '!=', 'paid')
                    ->where('due_date', '<', now());
            })
            ->orderBy('name')
            ->paginate(15);

        return view('customers.index', [
            'customers' => $customers,
            'title' => 'Customers with Overdue Invoices'
        ]);
    }

    /**
     * Display inactive customers.
     *
     * @return \Illuminate\Http\Response
     */
    public function inactive()
    {
        $customers = Customer::where('is_active', false)
            ->orderBy('name')
            ->paginate(15);

        return view('customers.index', [
            'customers' => $customers,
            'title' => 'Inactive Customers'
        ]);
    }

    /**
     * Display customers with no active PPP secrets.
     *
     * @return \Illuminate\Http\Response
     */
    public function noService()
    {
        $customersWithService = PppSecret::where('is_active', true)
            ->select('customer_id')
            ->distinct()
            ->pluck('customer_id')
            ->toArray();

        $customers = Customer::whereNotIn('id', $customersWithService)
            ->where('is_active', true)
            ->orderBy('name')
            ->paginate(15);

        return view('customers.index', [
            'customers' => $customers,
            'title' => 'Customers with No Active Service'
        ]);
    }
}