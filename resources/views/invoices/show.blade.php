@extends('layouts.admin')

@section('main-content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">{{ __('Invoice Details') }} - {{ $invoice->invoice_number }}</h1>
        <div>
            <a href="{{ route('invoices.preview', $invoice) }}" class="btn btn-sm btn-info shadow-sm" target="_blank">
                <i class="fas fa-eye fa-sm text-white-50"></i> {{ __('Preview') }}
            </a>
            <a href="{{ route('invoices.download', $invoice) }}" class="btn btn-sm btn-success shadow-sm" target="_blank">
                <i class="fas fa-download fa-sm text-white-50"></i> {{ __('Download PDF') }}
            </a>
            <a href="{{ route('invoices.edit', $invoice) }}" class="btn btn-sm btn-warning shadow-sm">
                <i class="fas fa-edit fa-sm text-white-50"></i> {{ __('Edit') }}
            </a>
            <a href="{{ route('invoices.index') }}" class="btn btn-sm btn-primary shadow-sm">
                <i class="fas fa-arrow-left fa-sm text-white-50"></i> {{ __('Back') }}
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">{{ __('Invoice Information') }}</h6>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr><th width="30%">{{ __('Invoice Number') }}</th><td>{{ $invoice->invoice_number }}</td></tr>
                        <tr><th>{{ __('Customer') }}</th><td>{{ $invoice->customer->name ?? __('N/A') }}</td></tr>
                        <tr><th>{{ __('PPP Profile') }}</th><td>{{ $invoice->pppSecret->pppProfile->name ?? __('N/A') }}</td></tr>
                        <tr><th>{{ __('Username') }}</th><td>{{ $invoice->pppSecret->username ?? __('N/A') }}</td></tr>
                        <tr><th>{{ __('Amount') }}</th><td>Rp {{ number_format($invoice->amount, 0, ',', '.') }}</td></tr>
                        @if($invoice->tax > 0)
                        <tr><th>{{ __('Tax') }}</th><td>Rp {{ number_format($invoice->tax, 0, ',', '.') }}</td></tr>
                        @endif
                        <tr><th>{{ __('Total Amount') }}</th><td><strong>Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</strong></td></tr>
                        <tr><th>{{ __('Status') }}</th><td><span class="badge badge-{{ $invoice->status === 'paid' ? 'success' : ($invoice->due_date < now() ? 'danger' : 'warning') }}">{{ ucfirst($invoice->status) }}</span></td></tr>
                        <tr><th>{{ __('Invoice Date') }}</th><td>{{ $invoice->invoice_date->format('d M Y') }}</td></tr>
                        <tr><th>{{ __('Due Date') }}</th><td>{{ $invoice->due_date->format('d M Y') }}</td></tr>
                        @if($invoice->paid_date)
                        <tr><th>{{ __('Paid Date') }}</th><td>{{ $invoice->paid_date->format('d M Y') }}</td></tr>
                        @endif
                        <tr><th>{{ __('Created') }}</th><td>{{ $invoice->created_at->format('d M Y H:i') }}</td></tr>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">{{ __('Quick Actions') }}</h6>
                </div>
                <div class="card-body">
                    <a href="{{ route('invoices.preview', $invoice) }}" class="btn btn-info btn-block" target="_blank">
                        <i class="fas fa-eye"></i> {{ __('Preview Invoice') }}
                    </a>
                    <a href="{{ route('invoices.download', $invoice) }}" class="btn btn-success btn-block" target="_blank">
                        <i class="fas fa-download"></i> {{ __('Download PDF') }}
                    </a>
                    <a href="{{ route('invoices.edit', $invoice) }}" class="btn btn-warning btn-block">
                        <i class="fas fa-edit"></i> {{ __('Edit Invoice') }}
                    </a>
                    @if($invoice->status !== 'paid')
                    <a href="{{ route('payments.create', ['invoice_id' => $invoice->id]) }}" class="btn btn-primary btn-block">
                        <i class="fas fa-credit-card"></i> {{ __('Add Payment') }}
                    </a>
                    @endif
                </div>
            </div>
            
            @if($invoice->customer)
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">{{ __('Customer Information') }}</h6>
                </div>
                <div class="card-body">
                    <table class="table table-borderless table-sm">
                        <tr><th width="40%">{{ __('Name') }}</th><td>{{ $invoice->customer->name }}</td></tr>
                        @if($invoice->customer->phone)
                        <tr><th>{{ __('Phone') }}</th><td>{{ $invoice->customer->phone }}</td></tr>
                        @endif
                        @if($invoice->customer->email)
                        <tr><th>{{ __('Email') }}</th><td>{{ $invoice->customer->email }}</td></tr>
                        @endif
                        @if($invoice->customer->address)
                        <tr><th>{{ __('Address') }}</th><td>{{ $invoice->customer->address }}</td></tr>
                        @endif
                    </table>
                </div>
            </div>
            @endif
        </div>
    </div>
@endsection
