@extends('layouts.admin')

@section('title', 'Invoice Preview - ' . $invoice->invoice_number)

@section('main-content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">{{ __('Invoice Preview') }} - {{ $invoice->invoice_number }}</h1>
        <div>
            <button onclick="printInvoice()" class="btn btn-sm btn-primary shadow-sm">
                <i class="fas fa-print fa-sm text-white-50"></i> {{ __('Print') }}
            </button>
            <a href="{{ route('invoices.download', $invoice) }}" target="_blank" class="btn btn-sm btn-success shadow-sm">
                <i class="fas fa-download fa-sm text-white-50"></i> {{ __('Download PDF') }}
            </a>
            <a href="{{ route('invoices.show', $invoice) }}" class="btn btn-sm btn-secondary shadow-sm">
                <i class="fas fa-arrow-left fa-sm text-white-50"></i> {{ __('Back') }}
            </a>
        </div>
    </div>

    <!-- Invoice Preview -->
    <div class="card shadow mb-4" id="invoice-preview">
        <div class="card-body p-5">
            <!-- Header -->
            <div class="row mb-4 header">
                <div class="col-md-6">
                    <h2 class="text-primary mb-0">INVOICE</h2>
                    <h4 class="text-muted">{{ $invoice->invoice_number }}</h4>
                </div>
                <div class="col-md-6 text-right">
                    <h3 class="text-primary">{{ $companySettings['company_name'] }}</h3>
                    <p class="mb-0">{{ $companySettings['address'] }}<br>
                    {{ $companySettings['city'] }}<br>
                    Phone: {{ $companySettings['phone'] }}<br>
                    Email: {{ $companySettings['email'] }}</p>
                </div>
            </div>

            <hr class="my-4">

            <!-- Bill To -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <h5 class="text-dark mb-2">{{ __('Bill To:') }}</h5>
                    <p class="mb-1"><strong>{{ $invoice->customer->name ?? 'N/A' }}</strong></p>
                    @if($invoice->customer)
                        @if($invoice->customer->address)
                            <p class="mb-1">{{ $invoice->customer->address }}</p>
                        @endif
                        <p class="mb-1">Phone: {{ $invoice->customer->phone ?? 'N/A' }}</p>
                        <p class="mb-0">Email: {{ $invoice->customer->email ?? 'N/A' }}</p>
                    @endif
                </div>
                <div class="col-md-6 text-right">
                    <table class="table table-sm table-borderless ml-auto" style="width: auto;">
                        <tr>
                            <td class="text-right"><strong>{{ __('Invoice Date:') }}</strong></td>
                            <td>{{ $invoice->invoice_date->format('d M Y') }}</td>
                        </tr>
                        <tr>
                            <td class="text-right"><strong>{{ __('Due Date:') }}</strong></td>
                            <td>{{ $invoice->due_date->format('d M Y') }}</td>
                        </tr>
                        <tr>
                            <td class="text-right"><strong>{{ __('Status:') }}</strong></td>
                            <td>
                                <span class="badge badge-{{ $invoice->status === 'paid' ? 'success' : ($invoice->due_date < now() ? 'danger' : 'warning') }}">
                                    {{ ucfirst($invoice->status) }}
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Invoice Items -->
            <div class="table-responsive mb-4">
                <table class="table table-bordered items-table">
                    <thead class="bg-primary text-white">
                        <tr>
                            <th>{{ __('Description') }}</th>
                            <th width="150" class="text-center">{{ __('Period') }}</th>
                            <th width="100" class="text-right">{{ __('Amount') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <strong>Internet Service - {{ $invoice->pppSecret->pppProfile->name ?? 'N/A' }}</strong><br>
                                <small class="text-muted">Username: {{ $invoice->pppSecret->username ?? 'N/A' }}</small>
                                @if($invoice->pppSecret && $invoice->pppSecret->pppProfile)
                                    <br><small class="text-muted">Speed: {{ $invoice->pppSecret->pppProfile->rate_limit ?? 'N/A' }}</small>
                                @endif
                            </td>
                            <td class="text-center">
                                {{ $invoice->invoice_date->format('M Y') }}
                            </td>
                            <td class="text-right">
                                Rp {{ number_format($invoice->amount, 0, ',', '.') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Totals -->
            <div class="row">
                <div class="col-md-6">
                    <!-- Payment Instructions -->
                    <div class="border p-3 bg-light">
                        <h6 class="mb-3">{{ __('Payment Instructions:') }}</h6>
                        
                        @php
                            $hasBankTransfer = (isset($companySettings['show_bank_bca']) && $companySettings['show_bank_bca'] && isset($companySettings['bank_bca']) && $companySettings['bank_bca']) || 
                                              (isset($companySettings['show_bank_mandiri']) && $companySettings['show_bank_mandiri'] && isset($companySettings['bank_mandiri']) && $companySettings['bank_mandiri']) || 
                                              (isset($companySettings['show_bank_bni']) && $companySettings['show_bank_bni'] && isset($companySettings['bank_bni']) && $companySettings['bank_bni']) || 
                                              (isset($companySettings['show_bank_bri']) && $companySettings['show_bank_bri'] && isset($companySettings['bank_bri']) && $companySettings['bank_bri']);
                            
                            $hasEWallet = (isset($companySettings['show_ewallet_dana']) && $companySettings['show_ewallet_dana'] && isset($companySettings['ewallet_dana']) && $companySettings['ewallet_dana']) || 
                                         (isset($companySettings['show_ewallet_ovo']) && $companySettings['show_ewallet_ovo'] && isset($companySettings['ewallet_ovo']) && $companySettings['ewallet_ovo']) || 
                                         (isset($companySettings['show_ewallet_gopay']) && $companySettings['show_ewallet_gopay'] && isset($companySettings['ewallet_gopay']) && $companySettings['ewallet_gopay']) || 
                                         (isset($companySettings['show_ewallet_shopeepay']) && $companySettings['show_ewallet_shopeepay'] && isset($companySettings['ewallet_shopeepay']) && $companySettings['ewallet_shopeepay']) || 
                                         (isset($companySettings['show_ewallet_linkaja']) && $companySettings['show_ewallet_linkaja'] && isset($companySettings['ewallet_linkaja']) && $companySettings['ewallet_linkaja']);
                        @endphp
                        
                        @if($hasBankTransfer)
                        <p class="mb-2"><strong>Bank Transfer:</strong></p>
                        @if(isset($companySettings['show_bank_bca']) && $companySettings['show_bank_bca'] && isset($companySettings['bank_bca']) && $companySettings['bank_bca'])
                        <p class="mb-1">Bank BCA: {{ $companySettings['bank_bca'] }}</p>
                        @endif
                        @if(isset($companySettings['show_bank_mandiri']) && $companySettings['show_bank_mandiri'] && isset($companySettings['bank_mandiri']) && $companySettings['bank_mandiri'])
                        <p class="mb-1">Bank Mandiri: {{ $companySettings['bank_mandiri'] }}</p>
                        @endif
                        @if(isset($companySettings['show_bank_bni']) && $companySettings['show_bank_bni'] && isset($companySettings['bank_bni']) && $companySettings['bank_bni'])
                        <p class="mb-1">Bank BNI: {{ $companySettings['bank_bni'] }}</p>
                        @endif
                        @if(isset($companySettings['show_bank_bri']) && $companySettings['show_bank_bri'] && isset($companySettings['bank_bri']) && $companySettings['bank_bri'])
                        <p class="mb-1">Bank BRI: {{ $companySettings['bank_bri'] }}</p>
                        @endif
                        @if(isset($companySettings['bank_account_name']) && $companySettings['bank_account_name'])
                        <p class="mb-3">A/N: {{ $companySettings['bank_account_name'] }}</p>
                        @endif
                        @endif
                        
                        @if($hasEWallet)
                        <p class="mb-1"><strong>E-Wallet:</strong></p>
                        @if(isset($companySettings['show_ewallet_dana']) && $companySettings['show_ewallet_dana'] && isset($companySettings['ewallet_dana']) && $companySettings['ewallet_dana'])
                        <p class="mb-1">DANA: {{ $companySettings['ewallet_dana'] }}</p>
                        @endif
                        @if(isset($companySettings['show_ewallet_ovo']) && $companySettings['show_ewallet_ovo'] && isset($companySettings['ewallet_ovo']) && $companySettings['ewallet_ovo'])
                        <p class="mb-1">OVO: {{ $companySettings['ewallet_ovo'] }}</p>
                        @endif
                        @if(isset($companySettings['show_ewallet_gopay']) && $companySettings['show_ewallet_gopay'] && isset($companySettings['ewallet_gopay']) && $companySettings['ewallet_gopay'])
                        <p class="mb-1">GoPay: {{ $companySettings['ewallet_gopay'] }}</p>
                        @endif
                        @if(isset($companySettings['show_ewallet_shopeepay']) && $companySettings['show_ewallet_shopeepay'] && isset($companySettings['ewallet_shopeepay']) && $companySettings['ewallet_shopeepay'])
                        <p class="mb-1">ShopeePay: {{ $companySettings['ewallet_shopeepay'] }}</p>
                        @endif
                        @if(isset($companySettings['show_ewallet_linkaja']) && $companySettings['show_ewallet_linkaja'] && isset($companySettings['ewallet_linkaja']) && $companySettings['ewallet_linkaja'])
                        <p class="mb-1">LinkAja: {{ $companySettings['ewallet_linkaja'] }}</p>
                        @endif
                        @endif
                        
                        @if(isset($companySettings['payment_note']) && $companySettings['payment_note'])
                        <p class="mb-1 mt-3"><strong>Note:</strong></p>
                        <p class="mb-0"><small>{{ $companySettings['payment_note'] }}</small></p>
                        @endif
                    </div>
                </div>
                <div class="col-md-6">
                    <table class="table table-sm table-borderless ml-auto" style="width: auto;">
                        <tr>
                            <td class="text-right"><strong>{{ __('Subtotal:') }}</strong></td>
                            <td class="text-right">Rp {{ number_format($invoice->amount, 0, ',', '.') }}</td>
                        </tr>
                        @if($invoice->tax > 0)
                        <tr>
                            <td class="text-right"><strong>{{ __('Tax:') }}</strong></td>
                            <td class="text-right">Rp {{ number_format($invoice->tax, 0, ',', '.') }}</td>
                        </tr>
                        @endif
                        <tr class="border-top">
                            <td class="text-right"><h5><strong>{{ __('Total:') }}</strong></h5></td>
                            <td class="text-right"><h5><strong>Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</strong></h5></td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Footer -->
            <div class="text-center mt-5 pt-4 border-top">
                @if(isset($companySettings['footer_note']) && $companySettings['footer_note'])
                <p class="text-muted mb-1">{{ $companySettings['footer_note'] }}</p>
                @endif
                <p class="text-muted mt-2 mb-0"><small>This is a computer-generated invoice and does not require a signature.</small></p>
                @if(isset($companySettings['developer_by']) && $companySettings['developer_by'])
                @endif
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<style>
@media print {
    /* Hide everything first */
    * {
        visibility: hidden !important;
    }
    
    /* Show only invoice content */
    #invoice-preview,
    #invoice-preview *,
    #invoice-preview .card-body,
    #invoice-preview .card-body * {
        visibility: visible !important;
    }
    
    /* Position invoice content for print */
    body {
        margin: 0 !important;
        padding: 0 !important;
        background: white !important;
    }
    
    #invoice-preview {
        position: absolute !important;
        top: 0 !important;
        left: 0 !important;
        width: 100% !important;
        height: auto !important;
        margin: 0 !important;
        padding: 0 !important;
        box-shadow: none !important;
        border: none !important;
        background: white !important;
    }
    
    #invoice-preview .card-body {
        padding: 20mm !important;
        margin: 0 !important;
        background: white !important;
        box-shadow: none !important;
        border: none !important;
    }
    
    /* Remove all admin layout elements */
    body > *:not(#wrapper) {
        display: none !important;
    }
    
    #wrapper > *:not(#content-wrapper) {
        display: none !important;
    }
    
    #content-wrapper > *:not(.container-fluid) {
        display: none !important;
    }
    
    .container-fluid > *:not(#invoice-preview) {
        display: none !important;
    }
    
    /* Ensure proper print styling */
    #invoice-preview .text-primary {
        color: #4e73df !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    
    #invoice-preview .bg-primary {
        background-color: #4e73df !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    
    #invoice-preview .bg-light {
        background-color: #f8f9fc !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    
    #invoice-preview .border-top {
        border-top: 1px solid #333 !important;
    }
    
    #invoice-preview .table-bordered {
        border: 1px solid #333 !important;
    }
    
    #invoice-preview .table-bordered th,
    #invoice-preview .table-bordered td {
        border: 1px solid #333 !important;
    }
}

@page {
    size: A4;
    margin: 0;
}
</style>

<script>
function printInvoice() {
    // Get the invoice content
    var invoiceContent = document.getElementById('invoice-preview').innerHTML;
    
    // Create a new window for printing
    var printWindow = window.open('', '_blank', 'width=800,height=600');
    
    // Write the invoice content to the new window
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Invoice {{ $invoice->invoice_number }}</title>
            <style>
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }
                
                body {
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                    color: #333;
                    line-height: 1.4;
                    background: white;
                    padding: 20mm;
                }
                
                .text-primary {
                    color: #4e73df !important;
                }
                
                .bg-primary {
                    background-color: #4e73df !important;
                    color: white !important;
                }
                
                .bg-light {
                    background-color: #f8f9fc !important;
                }
                
                .border {
                    border: 1px solid #ddd !important;
                }
                
                .border-top {
                    border-top: 1px solid #ddd !important;
                }
                
                .table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 1rem;
                }
                
                .table th,
                .table td {
                    padding: 12px;
                    vertical-align: top;
                    border-top: 1px solid #dee2e6;
                }
                
                .table-bordered {
                    border: 1px solid #dee2e6;
                }
                
                .table-bordered th,
                .table-bordered td {
                    border: 1px solid #dee2e6;
                }
                
                .text-right {
                    text-align: right !important;
                }
                
                .text-center {
                    text-align: center !important;
                }
                
                .mb-0 { margin-bottom: 0 !important; }
                .mb-1 { margin-bottom: 0.25rem !important; }
                .mb-2 { margin-bottom: 0.5rem !important; }
                .mb-3 { margin-bottom: 1rem !important; }
                .mb-4 { margin-bottom: 1.5rem !important; }
                .mt-3 { margin-top: 1rem !important; }
                .mt-5 { margin-top: 3rem !important; }
                .pt-4 { padding-top: 1.5rem !important; }
                .p-3 { padding: 1rem !important; }
                
                .row {
                    display: flex;
                    flex-wrap: wrap;
                    margin-right: -15px;
                    margin-left: -15px;
                }
                
                .col-md-6 {
                    flex: 0 0 50%;
                    max-width: 50%;
                    padding-right: 15px;
                    padding-left: 15px;
                }
                
                .badge {
                    display: inline-block;
                    padding: 0.25em 0.4em;
                    font-size: 75%;
                    font-weight: 700;
                    line-height: 1;
                    text-align: center;
                    white-space: nowrap;
                    vertical-align: baseline;
                    border-radius: 0.25rem;
                }
                
                .badge-success {
                    color: #fff;
                    background-color: #28a745;
                }
                
                .badge-warning {
                    color: #212529;
                    background-color: #ffc107;
                }
                
                .badge-danger {
                    color: #fff;
                    background-color: #dc3545;
                }
                
                .text-muted {
                    color: #6c757d !important;
                }
                
                h2, h3, h4, h5, h6 {
                    margin-bottom: 0.5rem;
                    font-weight: 500;
                    line-height: 1.2;
                }
                
                @media print {
                    body {
                        margin: 0;
                        padding: 15mm;
                    }
                    
                    @page {
                        size: A4;
                        margin: 0;
                    }
                }
            </style>
        </head>
        <body>
            ${invoiceContent}
        </body>
        </html>
    `);
    
    // Close the document and print
    printWindow.document.close();
    
    // Wait for content to load then print
    printWindow.onload = function() {
        printWindow.print();
        printWindow.close();
    };
}
</script>
@endpush
