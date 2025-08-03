<?php

namespace App\Http\Controllers;

use App\Http\Controllers\CompanySettingsController;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PppSecret;
use App\Services\MikrotikService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class InvoiceController extends Controller
{
    protected $mikrotikService;

    /**
     * Create a new controller instance.
     *
     * @param  \App\Services\MikrotikService  $mikrotikService
     * @return void
     */
    public function __construct(MikrotikService $mikrotikService)
    {
        $this->mikrotikService = $mikrotikService;
    }

    /**
     * Display a listing of the invoices.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = Invoice::with(['customer', 'pppSecret']);
        
        // Apply search filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function($customerQuery) use ($search) {
                      $customerQuery->where('name', 'like', "%{$search}%")
                                   ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }
        
        // Apply status filter
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }
        
        // Apply date filters
        if ($request->filled('date_from')) {
            $query->whereDate('invoice_date', '>=', $request->date_from);
        }
        
        if ($request->filled('date_to')) {
            $query->whereDate('invoice_date', '<=', $request->date_to);
        }
        
        // Apply period filter
        if ($request->filled('period')) {
            $period = explode('-', $request->period);
            if (count($period) === 2) {
                $year = $period[0];
                $month = $period[1];
                $query->whereYear('invoice_date', $year)
                      ->whereMonth('invoice_date', $month);
            }
        }
        
        $invoices = $query->orderBy('invoice_date', 'desc')->paginate(15);
        
        // Calculate statistics for all invoices (not just paginated)
        $allInvoices = Invoice::all();
        $totalInvoices = $allInvoices->count();
        $paidAmount = $allInvoices->where('status', 'paid')->sum('total_amount');
        $unpaidAmount = $allInvoices->where('status', 'unpaid')->sum('total_amount');
        $overdueCount = $allInvoices->where('status', 'unpaid')
                                   ->where('due_date', '<', now())
                                   ->count();
        
        $statistics = [
            'total_invoices' => $totalInvoices,
            'paid_amount' => $paidAmount,
            'outstanding' => $unpaidAmount,
            'overdue_count' => $overdueCount,
        ];

        return view('invoices.index', compact('invoices', 'statistics'));
    }

    /**
     * Show the form for creating a new invoice.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $customers = Customer::orderBy('name')->get();
        $customerId = $request->input('customer_id');
        $pppSecretId = $request->input('ppp_secret_id');
        $customer = null;
        $pppSecret = null;
        $pppSecrets = collect();

        if ($customerId) {
            $customer = Customer::find($customerId);
            $pppSecrets = $customer ? $customer->pppSecrets()->with('pppProfile')->get() : collect();
        }

        if ($pppSecretId) {
            $pppSecret = PppSecret::with('pppProfile')->find($pppSecretId);
        }

        return view('invoices.create', compact('customers', 'customer', 'pppSecrets', 'pppSecret'));
    }

    /**
     * Store a newly created invoice in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,id',
            'ppp_secret_id' => 'nullable|exists:ppp_secrets,id',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:invoice_date',
            'amount' => 'required|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return redirect()->route('invoices.create')
                ->withErrors($validator)
                ->withInput();
        }

        $data = $validator->validated();
        
        // Calculate total amount
        $data['tax'] = $data['tax'] ?? 0;
        $data['total_amount'] = $data['amount'] + $data['tax'];
        $data['status'] = 'unpaid';
        
        // Generate invoice number
        $data['invoice_number'] = Invoice::generateInvoiceNumber();

        $invoice = Invoice::create($data);

        return redirect()->route('invoices.show', $invoice->id)
            ->with('success', 'Invoice created successfully.');
    }

    /**
     * Display the specified invoice.
     *
     * @param  \App\Models\Invoice  $invoice
     * @return \Illuminate\Http\Response
     */
    public function show(Invoice $invoice)
    {
        $invoice->load(['customer', 'pppSecret', 'pppSecret.pppProfile', 'payments']);
        return view('invoices.show', compact('invoice'));
    }

    /**
     * Show the form for editing the specified invoice.
     *
     * @param  \App\Models\Invoice  $invoice
     * @return \Illuminate\Http\Response
     */
    public function edit(Invoice $invoice)
    {
        $customers = Customer::orderBy('name')->get();
        $pppSecrets = $invoice->customer ? $invoice->customer->pppSecrets()->with('pppProfile')->get() : collect();
        
        return view('invoices.edit', compact('invoice', 'customers', 'pppSecrets'));
    }

    /**
     * Update the specified invoice in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Invoice  $invoice
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Invoice $invoice)
    {
        // Don't allow editing if invoice is paid
        if ($invoice->status === 'paid') {
            return redirect()->route('invoices.show', $invoice->id)
                ->with('error', 'Cannot edit a paid invoice.');
        }

        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,id',
            'ppp_secret_id' => 'nullable|exists:ppp_secrets,id',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:invoice_date',
            'amount' => 'required|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return redirect()->route('invoices.edit', $invoice->id)
                ->withErrors($validator)
                ->withInput();
        }

        $data = $validator->validated();
        
        // Calculate total amount
        $data['tax'] = $data['tax'] ?? 0;
        $data['total_amount'] = $data['amount'] + $data['tax'];
        
        $invoice->update($data);
        $invoice->updateStatus(); // Recalculate status based on payments

        return redirect()->route('invoices.show', $invoice->id)
            ->with('success', 'Invoice updated successfully.');
    }

    /**
     * Remove the specified invoice from storage.
     *
     * @param  \App\Models\Invoice  $invoice
     * @return \Illuminate\Http\Response
     */
    public function destroy(Invoice $invoice)
    {
        // Check if invoice has payments
        if ($invoice->payments()->count() > 0) {
            return redirect()->route('invoices.show', $invoice->id)
                ->with('error', 'Cannot delete invoice with payments. Please delete all payments first.');
        }

        $invoice->delete();

        return redirect()->route('invoices.index')
            ->with('success', 'Invoice deleted successfully.');
    }

    /**
     * Mark the specified invoice as paid.
     *
     * @param  \App\Models\Invoice  $invoice
     * @return \Illuminate\Http\Response
     */
    public function markAsPaid(Invoice $invoice)
    {
        // Don't allow marking as paid if already paid
        if ($invoice->status === 'paid') {
            return redirect()->route('invoices.show', $invoice->id)
                ->with('error', 'Invoice is already marked as paid.');
        }

        // Create a payment for the remaining amount
        $remainingAmount = $invoice->total_amount - $invoice->totalPaid();
        
        if ($remainingAmount > 0) {
            $payment = new Payment([
                'payment_number' => Payment::generatePaymentNumber(),
                'invoice_id' => $invoice->id,
                'customer_id' => $invoice->customer_id,
                'payment_date' => now(),
                'amount' => $remainingAmount,
                'payment_method' => 'cash',
                'status' => 'verified',
                'verified_by' => Auth::id(),
                'verified_at' => now(),
                'notes' => 'Marked as paid by admin',
            ]);
            
            $payment->save();
        }

        $invoice->status = 'paid';
        $invoice->save();

        // If this invoice has a PPP secret and it's disabled, enable it
        if ($invoice->pppSecret && !$invoice->pppSecret->is_active) {
            try {
                $this->mikrotikService->connect();
                $this->mikrotikService->enablePppSecret($invoice->pppSecret);
                return redirect()->route('invoices.show', $invoice->id)
                    ->with('success', 'Invoice marked as paid and PPP secret enabled.');
            } catch (Exception $e) {
                return redirect()->route('invoices.show', $invoice->id)
                    ->with('warning', 'Invoice marked as paid but failed to enable PPP secret: ' . $e->getMessage());
            }
        }

        return redirect()->route('invoices.show', $invoice->id)
            ->with('success', 'Invoice marked as paid.');
    }

    /**
     * Generate invoices for all active PPP secrets with due dates in the current month.
     *
     * @return \Illuminate\Http\Response
     */
    public function generateMonthlyInvoices()
    {
        $now = now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();
        
        // Get all active PPP secrets with due dates in the current month
        $secrets = PppSecret::with(['customer', 'pppProfile'])
            ->where('is_active', true)
            ->whereBetween('due_date', [$startOfMonth, $endOfMonth])
            ->get();
        
        $created = 0;
        $skipped = 0;
        $errors = [];
        
        foreach ($secrets as $secret) {
            // Skip if customer is inactive
            if (!$secret->customer->is_active) {
                $skipped++;
                continue;
            }
            
            // Skip if already has an invoice for this month
            $existingInvoice = Invoice::where('ppp_secret_id', $secret->id)
                ->whereBetween('invoice_date', [$startOfMonth, $endOfMonth])
                ->first();
                
            if ($existingInvoice) {
                $skipped++;
                continue;
            }
            
            try {
                $invoice = new Invoice([
                    'customer_id' => $secret->customer_id,
                    'ppp_secret_id' => $secret->id,
                    'invoice_date' => $now,
                    'due_date' => $now->copy()->addDays(1)->endOfDay(), // Due next day at 23:59
                    'amount' => $secret->pppProfile->price,
                    'tax' => 0, // Default tax
                    'total_amount' => $secret->pppProfile->price,
                    'status' => 'unpaid',
                    'notes' => 'Monthly service invoice for ' . $secret->username,
                ]);

                $invoice->invoice_number = Invoice::generateInvoiceNumber();
                $invoice->save();
                $created++;
            } catch (Exception $e) {
                $errors[] = "Failed to create invoice for {$secret->username}: {$e->getMessage()}";
            }
        }
        
        $message = "Invoice generation completed: {$created} invoices created, {$skipped} skipped.";
        
        if (count($errors) > 0) {
            $message .= " Errors: " . implode("; ", $errors);
            return redirect()->route('invoices.index')
                ->with('warning', $message);
        }
        
        return redirect()->route('invoices.index')
            ->with('success', $message);
    }

    /**
     * Display overdue invoices.
     *
     * @return \Illuminate\Http\Response
     */
    public function overdue()
    {
        $invoices = Invoice::with(['customer', 'pppSecret'])
            ->where('status', '!=', 'paid')
            ->where('due_date', '<', now())
            ->orderBy('due_date')
            ->paginate(15);

        // Calculate statistics for all invoices (not just paginated)
        $allInvoices = Invoice::all();
        $totalInvoices = $allInvoices->count();
        $paidAmount = $allInvoices->where('status', 'paid')->sum('total_amount');
        $unpaidAmount = $allInvoices->where('status', 'unpaid')->sum('total_amount');
        $overdueCount = $allInvoices->where('status', 'unpaid')
                                   ->where('due_date', '<', now())
                                   ->count();
        
        $statistics = [
            'total_invoices' => $totalInvoices,
            'paid_amount' => $paidAmount,
            'outstanding' => $unpaidAmount,
            'overdue_count' => $overdueCount,
        ];

        return view('invoices.index', [
            'invoices' => $invoices,
            'statistics' => $statistics,
            'title' => 'Overdue Invoices'
        ]);
    }

    /**
     * Display unpaid invoices.
     *
     * @return \Illuminate\Http\Response
     */
    public function unpaid()
    {
        $invoices = Invoice::with(['customer', 'pppSecret'])
            ->where('status', '!=', 'paid')
            ->orderBy('due_date')
            ->paginate(15);

        // Calculate statistics for all invoices (not just paginated)
        $allInvoices = Invoice::all();
        $totalInvoices = $allInvoices->count();
        $paidAmount = $allInvoices->where('status', 'paid')->sum('total_amount');
        $unpaidAmount = $allInvoices->where('status', 'unpaid')->sum('total_amount');
        $overdueCount = $allInvoices->where('status', 'unpaid')
                                   ->where('due_date', '<', now())
                                   ->count();
        
        $statistics = [
            'total_invoices' => $totalInvoices,
            'paid_amount' => $paidAmount,
            'outstanding' => $unpaidAmount,
            'overdue_count' => $overdueCount,
        ];

        return view('invoices.index', [
            'invoices' => $invoices,
            'statistics' => $statistics,
            'title' => 'Unpaid Invoices'
        ]);
    }

    /**
     * Display paid invoices.
     *
     * @return \Illuminate\Http\Response
     */
    public function paid()
    {
        $invoices = Invoice::with(['customer', 'pppSecret'])
            ->where('status', 'paid')
            ->orderBy('invoice_date', 'desc')
            ->paginate(15);

        // Calculate statistics for all invoices (not just paginated)
        $allInvoices = Invoice::all();
        $totalInvoices = $allInvoices->count();
        $paidAmount = $allInvoices->where('status', 'paid')->sum('total_amount');
        $unpaidAmount = $allInvoices->where('status', 'unpaid')->sum('total_amount');
        $overdueCount = $allInvoices->where('status', 'unpaid')
                                   ->where('due_date', '<', now())
                                   ->count();
        
        $statistics = [
            'total_invoices' => $totalInvoices,
            'paid_amount' => $paidAmount,
            'outstanding' => $unpaidAmount,
            'overdue_count' => $overdueCount,
        ];

        return view('invoices.index', [
            'invoices' => $invoices,
            'statistics' => $statistics,
            'title' => 'Paid Invoices'
        ]);
    }

    /**
     * Print the specified invoice.
     *
     * @param  \App\Models\Invoice  $invoice
     * @return \Illuminate\Http\Response
     */
    public function print(Invoice $invoice)
    {
        $invoice->load(['customer', 'pppSecret', 'pppSecret.pppProfile', 'payments']);
        return view('invoices.print', compact('invoice'));
    }

    /**
     * Disable PPP secrets for overdue invoices.
     *
     * @return \Illuminate\Http\Response
     */
    public function disableOverdue()
    {
        // Get all overdue invoices with active PPP secrets
        $overdueInvoices = Invoice::with('pppSecret')
            ->where('status', '!=', 'paid')
            ->where('due_date', '<', now()) // Overdue (past due date)
            ->whereHas('pppSecret', function ($query) {
                $query->where('is_active', true);
            })
            ->get();
        
        $disabled = 0;
        $errors = [];
        
        foreach ($overdueInvoices as $invoice) {
            try {
                $this->mikrotikService->connect();
                $this->mikrotikService->disablePppSecret($invoice->pppSecret);
                $disabled++;
            } catch (Exception $e) {
                $errors[] = "Failed to disable PPP secret for invoice #{$invoice->invoice_number}: {$e->getMessage()}";
            }
        }
        
        $message = "Disabled {$disabled} PPP secrets for overdue invoices.";
        
        if (count($errors) > 0) {
            $message .= " Errors: " . implode("; ", $errors);
            return redirect()->route('invoices.overdue')
                ->with('warning', $message);
        }
        
        return redirect()->route('invoices.overdue')
            ->with('success', $message);
    }

    /**
     * Process overdue invoices by moving PPP secrets to Blokir profile.
     *
     * @return \Illuminate\Http\Response
     */
    public function processOverdue()
    {
        try {
            // Run the artisan command
            \Artisan::call('invoices:process-overdue');
            $output = \Artisan::output();
            
            return redirect()->route('invoices.overdue')
                ->with('success', 'Overdue invoices processed successfully. ' . $output);
        } catch (Exception $e) {
            return redirect()->route('invoices.overdue')
                ->with('error', 'Failed to process overdue invoices: ' . $e->getMessage());
        }
    }

    /**
     * Show invoice preview for printing.
     *
     * @param  \App\Models\Invoice  $invoice
     * @return \Illuminate\Http\Response
     */
    public function preview(Invoice $invoice)
    {
        $invoice->load(['customer', 'pppSecret.pppProfile']);
        $companySettings = CompanySettingsController::getSettings();
        
        return view('invoices.preview', compact('invoice', 'companySettings'));
    }

    /**
     * Generate PDF for invoice.
     *
     * @param  \App\Models\Invoice  $invoice
     * @return \Illuminate\Http\Response
     */
    public function pdf(Invoice $invoice)
    {
        $invoice->load(['customer', 'pppSecret.pppProfile']);
        $companySettings = CompanySettingsController::getSettings();
        
        // Return the preview view for PDF generation
        // Browser's print function will handle PDF creation
        return view('invoices.pdf', compact('invoice', 'companySettings'));
    }

    /**
     * Download invoice as PDF using browser's print function.
     *
     * @param  \App\Models\Invoice  $invoice
     * @return \Illuminate\Http\Response
     */
    public function download(Invoice $invoice)
    {
        $invoice->load(['customer', 'pppSecret.pppProfile']);
        $companySettings = CompanySettingsController::getSettings();
        
        return view('invoices.download', compact('invoice', 'companySettings'));
    }
}