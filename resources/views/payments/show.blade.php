@extends('layouts.admin')

@section('main-content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">{{ __('Payment Details') }}</h1>
        <div>
            @if($payment->status === 'verified')
                <button class="btn btn-sm btn-secondary shadow-sm" disabled title="{{ __('Cannot edit verified payment') }}">
                    <i class="fas fa-edit fa-sm text-white-50"></i> {{ __('Edit') }} ({{ __('Verified') }})
                </button>
            @else
                <a href="{{ route('payments.edit', $payment) }}" class="btn btn-sm btn-warning shadow-sm">
                    <i class="fas fa-edit fa-sm text-white-50"></i> {{ __('Edit') }}
                </a>
            @endif
            <a href="{{ route('payments.index') }}" class="btn btn-sm btn-primary shadow-sm">
                <i class="fas fa-arrow-left fa-sm text-white-50"></i> {{ __('Back') }}
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">{{ __('Payment Information') }}</h6>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <td class="font-weight-bold" style="width: 200px;">{{ __('Payment ID') }}</td>
                            <td>: {{ $payment->id }}</td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold">{{ __('Invoice') }}</td>
                            <td>: 
                                <a href="{{ route('invoices.show', $payment->invoice) }}" class="text-decoration-none">
                                    {{ $payment->invoice->invoice_number }}
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold">{{ __('Customer') }}</td>
                            <td>: 
                                @if($payment->invoice && $payment->invoice->customer)
                                    <a href="{{ route('customers.show', $payment->invoice->customer) }}" class="text-decoration-none">
                                        {{ $payment->invoice->customer->name }}
                                    </a>
                                @else
                                    N/A
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold">{{ __('Amount') }}</td>
                            <td>: <span class="h5 text-success">Rp {{ number_format($payment->amount, 0, ',', '.') }}</span></td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold">{{ __('Payment Method') }}</td>
                            <td>: 
                                @switch($payment->payment_method)
                                    @case('cash')
                                        <span class="badge badge-success">{{ __('Cash') }}</span>
                                        @break
                                    @case('bank_transfer')
                                        <span class="badge badge-info">{{ __('Bank Transfer') }}</span>
                                        @break
                                    @case('e_wallet')
                                        <span class="badge badge-warning">{{ __('E-Wallet') }}</span>
                                        @break
                                    @default
                                        <span class="badge badge-secondary">{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</span>
                                @endswitch
                            </td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold">{{ __('Payment Date') }}</td>
                            <td>: {{ $payment->payment_date->format('d M Y') }}</td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold">{{ __('Status') }}</td>
                            <td>: 
                                @switch($payment->status)
                                    @case('pending')
                                        <span class="badge badge-warning">{{ __('Pending') }}</span>
                                        @break
                                    @case('verified')
                                        <span class="badge badge-success">{{ __('Verified') }}</span>
                                        @break
                                    @case('rejected')
                                        <span class="badge badge-danger">{{ __('Rejected') }}</span>
                                        @break
                                    @default
                                        <span class="badge badge-secondary">{{ ucfirst($payment->status) }}</span>
                                @endswitch
                            </td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold">{{ __('Notes') }}</td>
                            <td>: {{ $payment->notes ?: '-' }}</td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold">{{ __('Created At') }}</td>
                            <td>: {{ $payment->created_at->format('d M Y H:i') }}</td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold">{{ __('Updated At') }}</td>
                            <td>: {{ $payment->updated_at->format('d M Y H:i') }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">{{ __('Invoice Summary') }}</h6>
                </div>
                <div class="card-body">
                    @if($payment->invoice)
                        <table class="table table-borderless table-sm">
                            <tr>
                                <td class="font-weight-bold">{{ __('Invoice Number') }}</td>
                                <td>: {{ $payment->invoice->invoice_number }}</td>
                            </tr>
                            <tr>
                                <td class="font-weight-bold">{{ __('Invoice Amount') }}</td>
                                <td>: Rp {{ number_format($payment->invoice->total_amount, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td class="font-weight-bold">{{ __('Paid Amount') }}</td>
                                <td>: Rp {{ number_format($payment->invoice->payments->sum('amount'), 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td class="font-weight-bold">{{ __('Remaining') }}</td>
                                <td>: Rp {{ number_format($payment->invoice->total_amount - $payment->invoice->payments->sum('amount'), 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td class="font-weight-bold">{{ __('Status') }}</td>
                                <td>: 
                                    @php
                                        $paidAmount = $payment->invoice->payments->sum('amount');
                                        $totalAmount = $payment->invoice->total_amount;
                                    @endphp
                                    @if($paidAmount >= $totalAmount)
                                        <span class="badge badge-success">{{ __('Paid') }}</span>
                                    @elseif($paidAmount > 0)
                                        <span class="badge badge-warning">{{ __('Partially Paid') }}</span>
                                    @else
                                        <span class="badge badge-danger">{{ __('Unpaid') }}</span>
                                    @endif
                                </td>
                            </tr>
                        </table>
                    @else
                        <p class="text-muted">{{ __('No invoice information available.') }}</p>
                    @endif
                </div>
            </div>

            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">{{ __('Actions') }}</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        @if($payment->status === 'verified')
                            <button class="btn btn-secondary btn-sm w-100" disabled title="{{ __('Cannot edit verified payment') }}">
                                <i class="fas fa-edit"></i> {{ __('Edit Payment') }} ({{ __('Verified') }})
                            </button>
                            <div class="alert alert-info alert-sm mt-2">
                                <small><i class="fas fa-info-circle"></i> {{ __('This payment is verified and cannot be edited for security reasons.') }}</small>
                            </div>
                        @else
                            <a href="{{ route('payments.edit', $payment) }}" class="btn btn-warning btn-sm w-100">
                                <i class="fas fa-edit"></i> {{ __('Edit Payment') }}
                            </a>
                        @endif
                        
                        @if($payment->status === 'verified')
                            <button class="btn btn-secondary btn-sm w-100" disabled title="{{ __('Cannot delete verified payment') }}">
                                <i class="fas fa-trash"></i> {{ __('Delete Payment') }} ({{ __('Verified') }})
                            </button>
                        @else
                            <form action="{{ route('payments.destroy', $payment) }}" method="POST" 
                                  onsubmit="return confirm('{{ __('Are you sure you want to delete this payment?') }}')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm w-100">
                                    <i class="fas fa-trash"></i> {{ __('Delete Payment') }}
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
