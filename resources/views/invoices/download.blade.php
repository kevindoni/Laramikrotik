<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
        }
        
        .invoice-container {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            padding: 20mm;
            background: white;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 30px;
            border-bottom: 3px solid #4e73df;
            padding-bottom: 20px;
        }
        
        .company-info h1 {
            color: #4e73df;
            margin: 0;
            font-size: 32px;
            font-weight: bold;
        }
        
        .company-info p {
            margin: 5px 0;
            color: #666;
            font-size: 14px;
        }
        
        .invoice-title {
            text-align: right;
        }
        
        .invoice-title h2 {
            color: #4e73df;
            margin: 0;
            font-size: 36px;
            font-weight: bold;
        }
        
        .invoice-title h3 {
            color: #666;
            margin: 5px 0;
            font-weight: normal;
            font-size: 18px;
        }
        
        .bill-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
        }
        
        .bill-to {
            flex: 1;
        }
        
        .bill-to h4 {
            color: #333;
            margin-bottom: 15px;
            font-size: 16px;
            border-bottom: 2px solid #ddd;
            padding-bottom: 8px;
        }
        
        .bill-to p {
            margin: 8px 0;
            font-size: 14px;
        }
        
        .invoice-meta {
            text-align: right;
            min-width: 250px;
        }
        
        .meta-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .meta-table td {
            padding: 8px 12px;
            border-bottom: 1px solid #eee;
        }
        
        .meta-table .label {
            font-weight: bold;
            color: #333;
            text-align: left;
        }
        
        .meta-table .value {
            text-align: right;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            border: 1px solid #ddd;
        }
        
        .items-table th {
            background: #4e73df;
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #4e73df;
        }
        
        .items-table td {
            padding: 15px;
            border: 1px solid #ddd;
        }
        
        .items-table .amount {
            text-align: right;
            font-weight: bold;
        }
        
        .items-table .center {
            text-align: center;
        }
        
        .totals {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 40px;
        }
        
        .payment-info {
            flex: 1;
            background: #f8f9fc;
            padding: 20px;
            border-radius: 8px;
            margin-right: 30px;
            border: 1px solid #e3e6f0;
        }
        
        .payment-info h5 {
            color: #4e73df;
            margin-top: 0;
            margin-bottom: 15px;
            font-size: 16px;
        }
        
        .payment-info p {
            margin: 5px 0;
            font-size: 13px;
        }
        
        .payment-info strong {
            color: #333;
        }
        
        .total-section {
            min-width: 300px;
        }
        
        .total-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .total-table td {
            padding: 10px 15px;
            border-bottom: 1px solid #eee;
        }
        
        .total-table .label {
            text-align: right;
            font-weight: bold;
        }
        
        .total-table .amount {
            text-align: right;
            min-width: 120px;
        }
        
        .total-row {
            border-top: 3px solid #4e73df !important;
            background: #f8f9fc;
        }
        
        .total-row td {
            font-size: 18px;
            font-weight: bold;
            color: #4e73df;
            padding: 15px;
        }
        
        .footer {
            text-align: center;
            margin-top: 50px;
            padding-top: 30px;
            border-top: 2px solid #ddd;
            color: #666;
        }
        
        .footer p {
            margin: 10px 0;
        }
        
        .status {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status.paid {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .status.unpaid {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .status.overdue {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        @media print {
            body {
                margin: 0;
                padding: 0;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            .invoice-container {
                width: 100%;
                min-height: auto;
                margin: 0;
                padding: 15mm;
                box-shadow: none;
            }
            
            .header {
                break-inside: avoid;
            }
            
            .items-table {
                break-inside: avoid;
            }
            
            .totals {
                break-inside: avoid;
            }
        }
        
        @page {
            size: A4;
            margin: 0;
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <!-- Header -->
        <div class="header">
            <div class="company-info">
                <h1>{{ $companySettings['company_name'] ?? 'LaraNetworks' }}</h1>
                <p>{{ $companySettings['address'] ?? 'Your Address' }}<br>
                {{ $companySettings['city'] ?? 'Your City' }}<br>
                Phone: {{ $companySettings['phone'] ?? 'Your Phone' }}<br>
                Email: {{ $companySettings['email'] ?? 'your@email.com' }}</p>
            </div>
            <div class="invoice-title">
                <h2>INVOICE</h2>
                <h3>{{ $invoice->invoice_number }}</h3>
            </div>
        </div>

        <!-- Bill Info -->
        <div class="bill-info">
            <div class="bill-to">
                <h4>Bill To:</h4>
                <p><strong>{{ $invoice->customer->name ?? 'N/A' }}</strong></p>
                @if($invoice->customer)
                    @if($invoice->customer->address)
                        <p>{{ $invoice->customer->address }}</p>
                    @endif
                    <p>Phone: {{ $invoice->customer->phone ?? 'N/A' }}</p>
                    <p>Email: {{ $invoice->customer->email ?? 'N/A' }}</p>
                @endif
            </div>
            <div class="invoice-meta">
                <table class="meta-table">
                    <tr>
                        <td class="label">Invoice Date:</td>
                        <td class="value">{{ $invoice->invoice_date->format('d M Y') }}</td>
                    </tr>
                    <tr>
                        <td class="label">Due Date:</td>
                        <td class="value">{{ $invoice->due_date->format('d M Y') }}</td>
                    </tr>
                    <tr>
                        <td class="label">Status:</td>
                        <td class="value">
                            <span class="status {{ $invoice->status === 'paid' ? 'paid' : ($invoice->due_date < now() ? 'overdue' : 'unpaid') }}">
                                {{ ucfirst($invoice->status) }}
                            </span>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Invoice Items -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 50%;">Description</th>
                    <th class="center" style="width: 25%;">Period</th>
                    <th class="amount" style="width: 25%;">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <strong>Internet Service - {{ $invoice->pppSecret->pppProfile->name ?? 'N/A' }}</strong><br>
                        <small style="color: #666;">Username: {{ $invoice->pppSecret->username ?? 'N/A' }}</small>
                        @if($invoice->pppSecret && $invoice->pppSecret->pppProfile)
                            <br><small style="color: #666;">Speed: {{ $invoice->pppSecret->pppProfile->rate_limit ?? 'N/A' }}</small>
                        @endif
                    </td>
                    <td class="center">
                        {{ $invoice->invoice_date->format('M Y') }}
                    </td>
                    <td class="amount">
                        Rp {{ number_format($invoice->amount, 0, ',', '.') }}
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Totals -->
        <div class="totals">
            <div class="payment-info">
                <h5>Payment Instructions:</h5>
                
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
                <p><strong>Bank Transfer:</strong></p>
                @if(isset($companySettings['show_bank_bca']) && $companySettings['show_bank_bca'] && isset($companySettings['bank_bca']) && $companySettings['bank_bca'])
                <p>Bank BCA: {{ $companySettings['bank_bca'] }}</p>
                @endif
                
                @if(isset($companySettings['show_bank_mandiri']) && $companySettings['show_bank_mandiri'] && isset($companySettings['bank_mandiri']) && $companySettings['bank_mandiri'])
                <p>Bank Mandiri: {{ $companySettings['bank_mandiri'] }}</p>
                @endif
                
                @if(isset($companySettings['show_bank_bni']) && $companySettings['show_bank_bni'] && isset($companySettings['bank_bni']) && $companySettings['bank_bni'])
                <p>Bank BNI: {{ $companySettings['bank_bni'] }}</p>
                @endif
                
                @if(isset($companySettings['show_bank_bri']) && $companySettings['show_bank_bri'] && isset($companySettings['bank_bri']) && $companySettings['bank_bri'])
                <p>Bank BRI: {{ $companySettings['bank_bri'] }}</p>
                @endif
                
                @if(isset($companySettings['bank_account_name']) && $companySettings['bank_account_name'])
                <p>A/N: {{ $companySettings['bank_account_name'] }}</p>
                @endif
                @endif
                
                @if($hasEWallet)
                <p><strong>E-Wallet:</strong></p>
                @if(isset($companySettings['show_ewallet_dana']) && $companySettings['show_ewallet_dana'] && isset($companySettings['ewallet_dana']) && $companySettings['ewallet_dana'])
                <p>DANA: {{ $companySettings['ewallet_dana'] }}</p>
                @endif
                
                @if(isset($companySettings['show_ewallet_ovo']) && $companySettings['show_ewallet_ovo'] && isset($companySettings['ewallet_ovo']) && $companySettings['ewallet_ovo'])
                <p>OVO: {{ $companySettings['ewallet_ovo'] }}</p>
                @endif
                
                @if(isset($companySettings['show_ewallet_gopay']) && $companySettings['show_ewallet_gopay'] && isset($companySettings['ewallet_gopay']) && $companySettings['ewallet_gopay'])
                <p>GoPay: {{ $companySettings['ewallet_gopay'] }}</p>
                @endif
                
                @if(isset($companySettings['show_ewallet_shopeepay']) && $companySettings['show_ewallet_shopeepay'] && isset($companySettings['ewallet_shopeepay']) && $companySettings['ewallet_shopeepay'])
                <p>ShopeePay: {{ $companySettings['ewallet_shopeepay'] }}</p>
                @endif
                
                @if(isset($companySettings['show_ewallet_linkaja']) && $companySettings['show_ewallet_linkaja'] && isset($companySettings['ewallet_linkaja']) && $companySettings['ewallet_linkaja'])
                <p>LinkAja: {{ $companySettings['ewallet_linkaja'] }}</p>
                @endif
                @endif
                
                @if(isset($companySettings['payment_note']) && $companySettings['payment_note'])
                <p><strong>Note:</strong></p>
                <p><small>{{ $companySettings['payment_note'] }}</small></p>
                @endif
            </div>
            <div class="total-section">
                <table class="total-table">
                    <tr>
                        <td class="label">Subtotal:</td>
                        <td class="amount">Rp {{ number_format($invoice->amount, 0, ',', '.') }}</td>
                    </tr>
                    @if($invoice->tax > 0)
                    <tr>
                        <td class="label">Tax:</td>
                        <td class="amount">Rp {{ number_format($invoice->tax, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                    <tr class="total-row">
                        <td class="label">Total:</td>
                        <td class="amount">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Auto print when page loads
        window.onload = function() {
            // Small delay to ensure page is fully loaded
            setTimeout(function() {
                window.print();
            }, 500);
        }
        
        // Close window after printing (optional)
        window.onafterprint = function() {
            // Uncomment if you want to auto-close after printing
            // window.close();
        }
    </script>
</body>
</html>
