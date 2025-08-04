<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
            line-height: 1.4;
        }
        
        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
        }
        
        .header {
            margin-bottom: 30px;
            border-bottom: 2px solid #4e73df;
            padding-bottom: 15px;
        }
        
        .header table {
            width: 100%;
        }
        
        .company h1 {
            color: #4e73df;
            margin: 0;
            font-size: 24px;
        }
        
        .invoice-title h2 {
            color: #4e73df;
            margin: 0;
            font-size: 28px;
            text-align: right;
        }
        
        .invoice-title h3 {
            color: #666;
            margin: 5px 0;
            font-weight: normal;
            text-align: right;
        }
        
        .bill-info {
            margin-bottom: 30px;
        }
        
        .bill-info table {
            width: 100%;
        }
        
        .bill-to h4 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .invoice-meta {
            text-align: right;
            vertical-align: top;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .items-table th {
            background: #4e73df;
            color: white;
            padding: 12px;
            text-align: left;
        }
        
        .items-table td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
        }
        
        .items-table .amount {
            text-align: right;
        }
        
        .items-table .center {
            text-align: center;
        }
        
        .totals table {
            width: 100%;
        }
        
        .payment-info {
            background: #f8f9fc;
            padding: 15px;
            border-radius: 5px;
            vertical-align: top;
        }
        
        .payment-info h5 {
            color: #4e73df;
            margin-top: 0;
        }
        
        .total-section {
            text-align: right;
            vertical-align: top;
        }
        
        .total-table {
            margin-left: auto;
            min-width: 200px;
        }
        
        .total-table td {
            padding: 5px 10px;
        }
        
        .total-row {
            border-top: 2px solid #4e73df;
            font-weight: bold;
            color: #4e73df;
        }
        
        .footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            color: #666;
        }
        
        .status {
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: bold;
        }
        
        .status.paid {
            background: #d4edda;
            color: #155724;
        }
        
        .status.unpaid {
            background: #fff3cd;
            color: #856404;
        }
        
        .status.overdue {
            background: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <!-- Header -->
        <div class="header">
            <table>
                <tr>
                    <td class="company">
                        <h1>{{ $companySettings['company_name'] ?? 'LaraNetworks' }}</h1>
                        <p>{{ $companySettings['address'] ?? 'Your Address' }}<br>
                        {{ $companySettings['city'] ?? 'Your City' }}<br>
                        Phone: {{ $companySettings['phone'] ?? 'Your Phone' }}<br>
                        Email: {{ $companySettings['email'] ?? 'your@email.com' }}</p>
                    </td>
                    <td class="invoice-title">
                        <h2>INVOICE</h2>
                        <h3>{{ $invoice->invoice_number }}</h3>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Bill Info -->
        <div class="bill-info">
            <table>
                <tr>
                    <td style="width: 50%;">
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
                    </td>
                    <td class="invoice-meta">
                        <table>
                            <tr>
                                <td><strong>Invoice Date:</strong></td>
                                <td>{{ $invoice->invoice_date->format('d M Y') }}</td>
                            </tr>
                            <tr>
                                <td><strong>Due Date:</strong></td>
                                <td>{{ $invoice->due_date->format('d M Y') }}</td>
                            </tr>
                            <tr>
                                <td><strong>Status:</strong></td>
                                <td>
                                    <span class="status {{ $invoice->status === 'paid' ? 'paid' : ($invoice->due_date < now() ? 'overdue' : 'unpaid') }}">
                                        {{ ucfirst($invoice->status) }}
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Invoice Items -->
        <table class="items-table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="center">Period</th>
                    <th class="amount">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <strong>Internet Service - {{ $invoice->pppSecret->pppProfile->name ?? 'N/A' }}</strong><br>
                        <small>Username: {{ $invoice->pppSecret->username ?? 'N/A' }}</small>
                        @if($invoice->pppSecret && $invoice->pppSecret->pppProfile)
                            <br><small>Speed: {{ $invoice->pppSecret->pppProfile->rate_limit ?? 'N/A' }}</small>
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
            <table>
                <tr>
                    <td style="width: 50%;">
                        <!-- Payment Instructions -->
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
                            
                            @if(isset($companySettings['show_manual_payment']) && $companySettings['show_manual_payment'] && isset($companySettings['manual_payment_info']) && $companySettings['manual_payment_info'])
                            <p><strong>Informasi Pembayaran Manual:</strong></p>
                            <p><small>{{ $companySettings['manual_payment_info'] }}</small></p>
                            @endif
                            
                            @if(isset($companySettings['payment_note']) && $companySettings['payment_note'])
                            <p><strong>Note:</strong></p>
                            <p><small>{{ $companySettings['payment_note'] }}</small></p>
                            @endif
                        </div>
                    </td>
                    <td class="total-section">
                        <table class="total-table">
                            <tr>
                                <td><strong>Subtotal:</strong></td>
                                <td class="amount">Rp {{ number_format($invoice->amount, 0, ',', '.') }}</td>
                            </tr>
                            @if($invoice->tax > 0)
                            <tr>
                                <td><strong>Tax:</strong></td>
                                <td class="amount">Rp {{ number_format($invoice->tax, 0, ',', '.') }}</td>
                            </tr>
                            @endif
                            <tr class="total-row">
                                <td><strong>Total:</strong></td>
                                <td class="amount"><strong>Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</strong></td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Footer -->
        <div class="footer">
            @if(isset($companySettings['footer_note']) && $companySettings['footer_note'])
            <p>{{ $companySettings['footer_note'] }}</p>
            @endif
            <p><small>This is a computer-generated invoice and does not require a signature.</small></p>
            @if(isset($companySettings['developer_by']) && $companySettings['developer_by'])
            @endif
        </div>
    </div>
</body>
</html>
