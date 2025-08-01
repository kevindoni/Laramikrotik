<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Receipts - Bulk Print</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: #fff;
        }

        .receipt {
            border: 2px solid #000;
            margin-bottom: 40px;
            padding: 20px;
            page-break-after: always;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .receipt:last-child {
            page-break-after: auto;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .company-name {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .company-info {
            font-size: 12px;
            color: #666;
        }

        .receipt-title {
            font-size: 20px;
            font-weight: bold;
            text-align: center;
            margin: 20px 0;
            text-transform: uppercase;
        }

        .receipt-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .receipt-info div {
            flex: 1;
        }

        .customer-info {
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 20px;
        }

        .payment-details {
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 20px;
        }

        .amount-section {
            background: #f5f5f5;
            border: 2px solid #000;
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
        }

        .amount-label {
            font-size: 14px;
            margin-bottom: 10px;
        }

        .amount-value {
            font-size: 28px;
            font-weight: bold;
            color: #000;
        }

        .footer {
            border-top: 1px solid #ddd;
            padding-top: 15px;
            text-align: center;
            font-size: 12px;
            color: #666;
        }

        .signature-section {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
        }

        .signature-box {
            text-align: center;
            width: 200px;
        }

        .signature-line {
            border-top: 1px solid #000;
            margin-top: 60px;
            padding-top: 5px;
        }

        @media print {
            body {
                margin: 0;
                padding: 0;
            }
            
            .receipt {
                margin: 0;
                border: 2px solid #000;
                page-break-after: always;
            }
            
            .receipt:last-child {
                page-break-after: auto;
            }
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

        .badge-primary {
            color: #fff;
            background-color: #007bff;
        }

        .badge-info {
            color: #fff;
            background-color: #17a2b8;
        }

        .badge-warning {
            color: #212529;
            background-color: #ffc107;
        }

        .badge-secondary {
            color: #fff;
            background-color: #6c757d;
        }
    </style>
</head>
<body>
    @foreach($payments as $payment)
    <div class="receipt">
        <!-- Header -->
        <div class="header">
            <div class="company-name">{{ config('app.name', 'ISP Company') }}</div>
            <div class="company-info">
                Internet Service Provider<br>
                Email: info@company.com | Phone: +62 xxx-xxxx-xxxx
            </div>
        </div>

        <!-- Receipt Title -->
        <div class="receipt-title">Payment Receipt</div>

        <!-- Receipt Info -->
        <div class="receipt-info">
            <div>
                <strong>Receipt #:</strong> {{ $payment->payment_number ?? 'N/A' }}<br>
                <strong>Date:</strong> {{ $payment->payment_date->format('d/m/Y H:i') }}
            </div>
            <div style="text-align: right;">
                <strong>Status:</strong> 
                @if($payment->status === 'verified')
                    <span class="badge badge-success">VERIFIED</span>
                @elseif($payment->status === 'pending')
                    <span class="badge badge-warning">PENDING</span>
                @else
                    <span class="badge badge-secondary">{{ strtoupper($payment->status) }}</span>
                @endif
            </div>
        </div>

        <!-- Customer Info -->
        <div class="customer-info">
            <strong>Customer Information:</strong><br>
            @if($payment->customer)
                <strong>Name:</strong> {{ $payment->customer->name }}<br>
                @if($payment->customer->phone)
                    <strong>Phone:</strong> {{ $payment->customer->phone }}<br>
                @endif
                @if($payment->customer->email)
                    <strong>Email:</strong> {{ $payment->customer->email }}<br>
                @endif
                @if($payment->customer->address)
                    <strong>Address:</strong> {{ $payment->customer->address }}
                @endif
            @else
                <em>No customer information available</em>
            @endif
        </div>

        <!-- Payment Details -->
        <div class="payment-details">
            <strong>Payment Details:</strong><br>
            @if($payment->invoice)
                <strong>Invoice:</strong> {{ $payment->invoice->invoice_number }}<br>
                @if($payment->invoice->description)
                    <strong>Description:</strong> {{ $payment->invoice->description }}<br>
                @endif
                <strong>Period:</strong> {{ $payment->invoice->period ?? 'N/A' }}<br>
            @endif
            <strong>Payment Method:</strong> 
            @php
                $methodConfig = [
                    'cash' => ['class' => 'success', 'label' => 'Cash'],
                    'bank_transfer' => ['class' => 'primary', 'label' => 'Bank Transfer'],
                    'e_wallet' => ['class' => 'info', 'label' => 'E-Wallet'],
                    'credit_card' => ['class' => 'warning', 'label' => 'Credit Card'],
                    'other' => ['class' => 'secondary', 'label' => 'Other']
                ];
                $config = $methodConfig[$payment->payment_method] ?? $methodConfig['other'];
            @endphp
            <span class="badge badge-{{ $config['class'] }}">{{ $config['label'] }}</span><br>
            
            @if($payment->reference_number)
                <strong>Reference:</strong> {{ $payment->reference_number }}<br>
            @endif
            
            @if($payment->notes)
                <strong>Notes:</strong> {{ $payment->notes }}
            @endif
        </div>

        <!-- Amount Section -->
        <div class="amount-section">
            <div class="amount-label">AMOUNT PAID</div>
            <div class="amount-value">Rp {{ number_format($payment->amount, 0, ',', '.') }}</div>
        </div>

        <!-- Signature Section -->
        <div class="signature-section">
            <div class="signature-box">
                <div class="signature-line">Customer Signature</div>
            </div>
            <div class="signature-box">
                <div class="signature-line">Officer Signature</div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <strong>Thank you for your payment!</strong><br>
            This is a computer-generated receipt and valid without signature.<br>
            For inquiries, please contact our customer service.
            @if($payment->created_at)
                <br><small>Printed on: {{ now()->format('d/m/Y H:i:s') }}</small>
            @endif
        </div>
    </div>
    @endforeach

    <script>
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html>
