@extends('layouts.admin')

@section('main-content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">{{ __('Invoice Details') }} - {{ $invoice->invoice_number }}</h1>
        <div>
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
                        <tr><th>{{ __('Amount') }}</th><td>Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</td></tr>
                        <tr><th>{{ __('Status') }}</th><td><span class="badge badge-{{ $invoice->status === 'paid' ? 'success' : 'warning' }}">{{ ucfirst($invoice->status) }}</span></td></tr>
                        <tr><th>{{ __('Due Date') }}</th><td>{{ $invoice->due_date->format('d M Y') }}</td></tr>
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
                    <a href="{{ route('invoices.edit', $invoice) }}" class="btn btn-warning btn-block">{{ __('Edit Invoice') }}</a>
                    <a href="{{ route('payments.create', ['invoice_id' => $invoice->id]) }}" class="btn btn-success btn-block">{{ __('Add Payment') }}</a>
                </div>
            </div>
        </div>
    </div>
@endsection
