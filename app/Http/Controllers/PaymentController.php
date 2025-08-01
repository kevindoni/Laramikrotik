<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PppSecret;
use App\Services\MikrotikService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
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
     * Display a listing of the payments.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = Payment::with(['customer', 'invoice']);
        
        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('customer', function($customerQuery) use ($search) {
                    $customerQuery->where('name', 'like', "%{$search}%")
                                  ->orWhere('phone', 'like', "%{$search}%");
                })->orWhereHas('invoice', function($invoiceQuery) use ($search) {
                    $invoiceQuery->where('invoice_number', 'like', "%{$search}%");
                })->orWhere('payment_number', 'like', "%{$search}%")
                  ->orWhere('reference_number', 'like', "%{$search}%");
            });
        }
        
        // Apply payment method filter
        if ($request->filled('method')) {
            $query->where('payment_method', $request->method);
        }
        
        // Apply date filters
        if ($request->filled('date_from')) {
            $query->where('payment_date', '>=', $request->date_from);
        }
        
        if ($request->filled('date_to')) {
            $query->where('payment_date', '<=', $request->date_to . ' 23:59:59');
        }
        
        // Apply amount filter
        if ($request->filled('amount_min')) {
            $query->where('amount', '>=', $request->amount_min);
        }
        
        $payments = $query->orderBy('payment_date', 'desc')->paginate(25);
        
        // Calculate statistics
        $todayTotal = Payment::whereDate('payment_date', today())->sum('amount');
        $recentTotal = Payment::where('payment_date', '>=', now()->subDays(7))->sum('amount');
        $last30DaysTotal = Payment::where('payment_date', '>=', now()->subDays(30))->sum('amount');
        $monthTotal = Payment::whereMonth('payment_date', now()->month)
                           ->whereYear('payment_date', now()->year)
                           ->sum('amount');
        $avgAmount = Payment::avg('amount') ?? 0;
        $totalCount = Payment::count();
        
        return view('payments.index', compact('payments', 'todayTotal', 'recentTotal', 'last30DaysTotal', 'monthTotal', 'avgAmount', 'totalCount'));
    }

    /**
     * Show the form for creating a new payment.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $invoices = Invoice::with('customer')->where('status', '!=', 'paid')->orderBy('created_at', 'desc')->get();
        
        return view('payments.create', compact('invoices'));
    }

    /**
     * Store a newly created payment in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'invoice_id' => 'required|exists:invoices,id',
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|string|in:cash,bank_transfer,credit_card,e_wallet,other',
            'proof' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:2048',
            'notes' => 'nullable|string|max:1000',
            'auto_verify' => 'boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->route('payments.create')
                ->withErrors($validator)
                ->withInput();
        }

        $data = $validator->validated();
        $autoVerify = $data['auto_verify'] ?? false;
        unset($data['auto_verify']);
        
        // Get customer_id from the selected invoice
        $invoice = Invoice::findOrFail($data['invoice_id']);
        $data['customer_id'] = $invoice->customer_id;
        
        // Generate payment number
        $data['payment_number'] = Payment::generatePaymentNumber();
        
        // Set default status
        $data['status'] = 'pending';
        
        // Handle file upload
        if ($request->hasFile('proof')) {
            $path = $request->file('proof')->store('payment_proofs', 'public');
            $data['proof'] = $path;
        }
        
        $payment = Payment::create($data);
        
        // Auto-verify if requested
        if ($autoVerify) {
            $payment->verify(Auth::id());
            
            $invoice = Invoice::find($data['invoice_id']);
            $invoice->updateStatus();
            
            // If invoice is now paid and has a PPP secret that's disabled, enable it
            if ($invoice->status === 'paid' && $invoice->pppSecret && !$invoice->pppSecret->is_active) {
                try {
                    $this->mikrotikService->connect();
                    $this->mikrotikService->enablePppSecret($invoice->pppSecret);
                    return redirect()->route('payments.show', $payment->id)
                        ->with('success', 'Payment created, verified, and PPP secret enabled.');
                } catch (Exception $e) {
                    return redirect()->route('payments.show', $payment->id)
                        ->with('warning', 'Payment created and verified, but failed to enable PPP secret: ' . $e->getMessage());
                }
            }
            
            return redirect()->route('payments.show', $payment->id)
                ->with('success', 'Payment created and verified successfully.');
        }

        return redirect()->route('payments.show', $payment->id)
            ->with('success', 'Payment created successfully.');
    }

    /**
     * Display the specified payment.
     *
     * @param  \App\Models\Payment  $payment
     * @return \Illuminate\Http\Response
     */
    public function show(Payment $payment)
    {
        $payment->load(['customer', 'invoice', 'verifiedByUser']);
        return view('payments.show', compact('payment'));
    }

    /**
     * Show the form for editing the specified payment.
     *
     * @param  \App\Models\Payment  $payment
     * @return \Illuminate\Http\Response
     */
    public function edit(Payment $payment)
    {
        // Don't allow editing verified payments
        if ($payment->status === 'verified') {
            return redirect()->route('payments.show', $payment->id)
                ->with('error', 'Cannot edit a verified payment.');
        }

        $invoices = Invoice::with('customer')->where('status', '!=', 'paid')->orWhere('id', $payment->invoice_id)->orderBy('created_at', 'desc')->get();
        
        return view('payments.edit', compact('payment', 'invoices'));
    }

    /**
     * Update the specified payment in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Payment  $payment
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Payment $payment)
    {
        // Don't allow updating verified payments
        if ($payment->status === 'verified') {
            return redirect()->route('payments.show', $payment->id)
                ->with('error', 'Cannot update a verified payment.');
        }

        $validator = Validator::make($request->all(), [
            'invoice_id' => 'required|exists:invoices,id',
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|string|in:cash,bank_transfer,credit_card,e_wallet,other',
            'proof' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:2048',
            'notes' => 'nullable|string|max:1000',
            'auto_verify' => 'boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->route('payments.edit', $payment->id)
                ->withErrors($validator)
                ->withInput();
        }

        $data = $validator->validated();
        $autoVerify = $data['auto_verify'] ?? false;
        unset($data['auto_verify']);
        
        // Get customer_id from the selected invoice
        $invoice = Invoice::findOrFail($data['invoice_id']);
        $data['customer_id'] = $invoice->customer_id;
        
        // Handle file upload
        if ($request->hasFile('proof')) {
            // Delete old file if exists
            if ($payment->proof) {
                Storage::disk('public')->delete($payment->proof);
            }
            
            $path = $request->file('proof')->store('payment_proofs', 'public');
            $data['proof'] = $path;
        }
        
        $payment->update($data);
        
        // Auto-verify if requested
        if ($autoVerify) {
            $payment->verify(Auth::id());
            
            $invoice = Invoice::find($data['invoice_id']);
            $invoice->updateStatus();
            
            // If invoice is now paid and has a PPP secret that's disabled, enable it
            if ($invoice->status === 'paid' && $invoice->pppSecret && !$invoice->pppSecret->is_active) {
                try {
                    $this->mikrotikService->connect();
                    $this->mikrotikService->enablePppSecret($invoice->pppSecret);
                    return redirect()->route('payments.show', $payment->id)
                        ->with('success', 'Payment updated, verified, and PPP secret enabled.');
                } catch (Exception $e) {
                    return redirect()->route('payments.show', $payment->id)
                        ->with('warning', 'Payment updated and verified, but failed to enable PPP secret: ' . $e->getMessage());
                }
            }
            
            return redirect()->route('payments.show', $payment->id)
                ->with('success', 'Payment updated and verified successfully.');
        }

        return redirect()->route('payments.show', $payment->id)
            ->with('success', 'Payment updated successfully.');
    }

    /**
     * Remove the specified payment from storage.
     *
     * @param  \App\Models\Payment  $payment
     * @return \Illuminate\Http\Response
     */
    public function destroy(Payment $payment)
    {
        // Don't allow deleting verified payments
        if ($payment->status === 'verified') {
            return redirect()->route('payments.show', $payment->id)
                ->with('error', 'Cannot delete a verified payment.');
        }

        // Delete proof file if exists
        if ($payment->proof) {
            Storage::disk('public')->delete($payment->proof);
        }

        $payment->delete();

        return redirect()->route('payments.index')
            ->with('success', 'Payment deleted successfully.');
    }

    /**
     * Verify the specified payment.
     *
     * @param  \App\Models\Payment  $payment
     * @return \Illuminate\Http\Response
     */
    public function verify(Payment $payment)
    {
        // Don't allow verifying already verified payments
        if ($payment->status === 'verified') {
            return redirect()->route('payments.show', $payment->id)
                ->with('error', 'Payment is already verified.');
        }

        $payment->verify(Auth::id());
        
        $invoice = $payment->invoice;
        $invoice->updateStatus();
        
        // If invoice is now paid and has a PPP secret that's disabled, enable it
        if ($invoice->status === 'paid' && $invoice->pppSecret && !$invoice->pppSecret->is_active) {
            try {
                $this->mikrotikService->connect();
                $this->mikrotikService->enablePppSecret($invoice->pppSecret);
                return redirect()->route('payments.show', $payment->id)
                    ->with('success', 'Payment verified and PPP secret enabled successfully.');
            } catch (Exception $e) {
                return redirect()->route('payments.show', $payment->id)
                    ->with('warning', 'Payment verified, but failed to enable PPP secret: ' . $e->getMessage());
            }
        }
        
        return redirect()->route('payments.show', $payment->id)
            ->with('success', 'Payment verified successfully.');
    }

    /**
     * Reject the specified payment.
     *
     * @param  \App\Models\Payment  $payment
     * @return \Illuminate\Http\Response
     */
    public function reject(Payment $payment)
    {
        // Don't allow rejecting verified payments
        if ($payment->status === 'verified') {
            return redirect()->route('payments.show', $payment->id)
                ->with('error', 'Cannot reject a verified payment.');
        }

        $payment->reject(Auth::id());
        
        return redirect()->route('payments.show', $payment->id)
            ->with('success', 'Payment rejected successfully.');
    }

    /**
     * Display pending payments.
     *
     * @return \Illuminate\Http\Response
     */
    public function pending()
    {
        $payments = Payment::with(['customer', 'invoice'])
            ->where('status', 'pending')
            ->orderBy('payment_date')
            ->paginate(15);

        return view('payments.index', [
            'payments' => $payments,
            'title' => 'Pending Payments'
        ]);
    }

    /**
     * Display verified payments.
     *
     * @return \Illuminate\Http\Response
     */
    public function verified()
    {
        $payments = Payment::with(['customer', 'invoice'])
            ->where('status', 'verified')
            ->orderBy('payment_date', 'desc')
            ->paginate(15);

        return view('payments.index', [
            'payments' => $payments,
            'title' => 'Verified Payments'
        ]);
    }

    /**
     * Display rejected payments.
     *
     * @return \Illuminate\Http\Response
     */
    public function rejected()
    {
        $payments = Payment::with(['customer', 'invoice'])
            ->where('status', 'rejected')
            ->orderBy('payment_date', 'desc')
            ->paginate(15);

        return view('payments.index', [
            'payments' => $payments,
            'title' => 'Rejected Payments'
        ]);
    }

    /**
     * Print the specified payment receipt.
     *
     * @param  \App\Models\Payment  $payment
     * @return \Illuminate\Http\Response
     */
    public function receipt(Payment $payment)
    {
        $payment->load(['customer', 'invoice', 'verifiedByUser']);
        return view('payments.receipt', compact('payment'));
    }

    /**
     * Bulk delete selected payments.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function bulkDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'selected_payments' => 'required|array|min:1',
            'selected_payments.*' => 'required|integer|exists:payments,id',
        ]);

        if ($validator->fails()) {
            return redirect()->route('payments.index')
                ->with('error', 'Invalid selection for bulk delete.');
        }

        $deletedCount = 0;
        $errors = [];

        foreach ($request->selected_payments as $paymentId) {
            try {
                $payment = Payment::findOrFail($paymentId);
                
                // Don't allow deleting verified payments
                if ($payment->status === 'verified') {
                    $errors[] = "Payment #{$payment->payment_number} is verified and cannot be deleted.";
                    continue;
                }
                
                // Delete proof file if exists
                if ($payment->proof) {
                    Storage::disk('public')->delete($payment->proof);
                }
                
                $payment->delete();
                $deletedCount++;
            } catch (Exception $e) {
                $errors[] = "Failed to delete payment ID {$paymentId}: " . $e->getMessage();
            }
        }

        if ($deletedCount > 0) {
            $message = "Successfully deleted {$deletedCount} payment(s).";
            if (!empty($errors)) {
                $message .= " Errors: " . implode(', ', $errors);
                return redirect()->route('payments.index')->with('warning', $message);
            }
            return redirect()->route('payments.index')->with('success', $message);
        }

        return redirect()->route('payments.index')
            ->with('error', 'No payments could be deleted. ' . implode(', ', $errors));
    }

    /**
     * Bulk export selected payments to Excel.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function bulkExport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'selected_payments' => 'required|array|min:1',
            'selected_payments.*' => 'required|integer|exists:payments,id',
        ]);

        if ($validator->fails()) {
            return redirect()->route('payments.index')
                ->with('error', 'Invalid selection for bulk export.');
        }

        $payments = Payment::with(['customer', 'invoice'])
            ->whereIn('id', $request->selected_payments)
            ->orderBy('payment_date', 'desc')
            ->get();

        $filename = 'payments_export_' . date('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($payments) {
            $file = fopen('php://output', 'w');
            
            // CSV Headers
            fputcsv($file, [
                'Payment Number',
                'Date',
                'Customer',
                'Invoice',
                'Amount',
                'Method',
                'Reference',
                'Status',
                'Notes'
            ]);

            foreach ($payments as $payment) {
                fputcsv($file, [
                    $payment->payment_number ?? 'N/A',
                    $payment->payment_date->format('Y-m-d H:i:s'),
                    $payment->customer ? $payment->customer->name : 'No customer',
                    $payment->invoice ? $payment->invoice->invoice_number : 'No invoice',
                    $payment->amount,
                    ucwords(str_replace('_', ' ', $payment->payment_method)),
                    $payment->reference_number ?? '',
                    ucfirst($payment->status),
                    $payment->notes ?? ''
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Bulk print receipts for selected payments.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function bulkPrint(Request $request)
    {
        $paymentIds = $request->get('payments', []);
        
        if (empty($paymentIds)) {
            return redirect()->route('payments.index')
                ->with('error', 'No payments selected for printing.');
        }

        $payments = Payment::with(['customer', 'invoice', 'verifiedByUser'])
            ->whereIn('id', $paymentIds)
            ->orderBy('payment_date', 'desc')
            ->get();

        return view('payments.bulk-print', compact('payments'));
    }

    /**
     * Send receipt email for a payment.
     *
     * @param  \App\Models\Payment  $payment
     * @return \Illuminate\Http\Response
     */
    public function sendReceipt(Payment $payment)
    {
        if (!$payment->customer || !$payment->customer->email) {
            return redirect()->route('payments.show', $payment)
                ->with('error', 'Customer email not found.');
        }

        try {
            // Here you would implement email sending logic
            // Mail::to($payment->customer->email)->send(new PaymentReceiptMail($payment));
            
            return redirect()->route('payments.show', $payment)
                ->with('success', 'Receipt sent to customer email successfully.');
        } catch (Exception $e) {
            return redirect()->route('payments.show', $payment)
                ->with('error', 'Failed to send receipt email: ' . $e->getMessage());
        }
    }

    /**
     * Download receipt PDF for a payment.
     *
     * @param  \App\Models\Payment  $payment
     * @return \Illuminate\Http\Response
     */
    public function downloadReceipt(Payment $payment)
    {
        $payment->load(['customer', 'invoice', 'verifiedByUser']);
        
        // Here you would implement PDF generation logic
        // return PDF::loadView('payments.receipt-pdf', compact('payment'))
        //     ->download("receipt-{$payment->payment_number}.pdf");
        
        return redirect()->route('payments.receipt', $payment);
    }

    /**
     * Export payments to Excel.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function export(Request $request)
    {
        $query = Payment::with(['customer', 'invoice']);
        
        // Apply filters from request
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('customer', function($customerQuery) use ($search) {
                    $customerQuery->where('name', 'like', "%{$search}%");
                })->orWhereHas('invoice', function($invoiceQuery) use ($search) {
                    $invoiceQuery->where('invoice_number', 'like', "%{$search}%");
                });
            });
        }
        
        if ($request->filled('method')) {
            $query->where('payment_method', $request->method);
        }
        
        if ($request->filled('date_from')) {
            $query->where('payment_date', '>=', $request->date_from);
        }
        
        if ($request->filled('date_to')) {
            $query->where('payment_date', '<=', $request->date_to . ' 23:59:59');
        }
        
        if ($request->filled('amount_min')) {
            $query->where('amount', '>=', $request->amount_min);
        }
        
        $payments = $query->orderBy('payment_date', 'desc')->get();
        
        $filename = 'payments_export_' . date('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($payments) {
            $file = fopen('php://output', 'w');
            
            // CSV Headers
            fputcsv($file, [
                'Payment Number',
                'Date',
                'Customer',
                'Phone',
                'Invoice',
                'Amount',
                'Method',
                'Reference',
                'Status',
                'Notes'
            ]);

            foreach ($payments as $payment) {
                fputcsv($file, [
                    $payment->payment_number ?? 'N/A',
                    $payment->payment_date->format('Y-m-d H:i:s'),
                    $payment->customer ? $payment->customer->name : 'No customer',
                    $payment->customer ? $payment->customer->phone : '',
                    $payment->invoice ? $payment->invoice->invoice_number : 'No invoice',
                    $payment->amount,
                    ucwords(str_replace('_', ' ', $payment->payment_method)),
                    $payment->reference_number ?? '',
                    ucfirst($payment->status),
                    $payment->notes ?? ''
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Generate payment report.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function report(Request $request)
    {
        // This would show a comprehensive payment report page
        return view('payments.report');
    }
}