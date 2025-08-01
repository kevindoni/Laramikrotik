@extends('layouts.admin')

@section('main-content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">{{ __('Edit Payment') }}</h1>
        <a href="{{ route('payments.index') }}" class="btn btn-sm btn-primary shadow-sm">
            <i class="fas fa-arrow-left fa-sm text-white-50"></i> {{ __('Back') }}
        </a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger border-left-danger" role="alert">
            <ul class="pl-4 my-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">{{ __('Payment Information') }}</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('payments.update', $payment) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="form-group row">
                            <label for="invoice_id" class="col-sm-3 col-form-label">{{ __('Invoice') }} <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <select class="form-control @error('invoice_id') is-invalid @enderror" id="invoice_id" name="invoice_id" required>
                                    <option value="">{{ __('Select Invoice') }}</option>
                                    @foreach($invoices as $invoice)
                                        <option value="{{ $invoice->id }}" {{ old('invoice_id', $payment->invoice_id) == $invoice->id ? 'selected' : '' }}>
                                            {{ $invoice->invoice_number }} - {{ $invoice->customer->name ?? 'N/A' }} (Rp {{ number_format($invoice->total_amount, 0, ',', '.') }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="amount" class="col-sm-3 col-form-label">{{ __('Amount') }} <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">Rp</span>
                                    </div>
                                    <input type="number" class="form-control @error('amount') is-invalid @enderror" 
                                           id="amount" name="amount" value="{{ old('amount', $payment->amount) }}" min="0" step="1000" required>
                                </div>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="payment_method" class="col-sm-3 col-form-label">{{ __('Payment Method') }}</label>
                            <div class="col-sm-9">
                                <select class="form-control" id="payment_method" name="payment_method">
                                    <option value="cash" {{ old('payment_method', $payment->payment_method) == 'cash' ? 'selected' : '' }}>{{ __('Cash') }}</option>
                                    <option value="bank_transfer" {{ old('payment_method', $payment->payment_method) == 'bank_transfer' ? 'selected' : '' }}>{{ __('Bank Transfer') }}</option>
                                    <option value="e_wallet" {{ old('payment_method', $payment->payment_method) == 'e_wallet' ? 'selected' : '' }}>{{ __('E-Wallet') }}</option>
                                    <option value="other" {{ old('payment_method', $payment->payment_method) == 'other' ? 'selected' : '' }}>{{ __('Other') }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="payment_date" class="col-sm-3 col-form-label">{{ __('Payment Date') }}</label>
                            <div class="col-sm-9">
                                <input type="date" class="form-control" id="payment_date" name="payment_date" value="{{ old('payment_date', $payment->payment_date->format('Y-m-d')) }}">
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="notes" class="col-sm-3 col-form-label">{{ __('Notes') }}</label>
                            <div class="col-sm-9">
                                <textarea class="form-control" id="notes" name="notes" rows="3">{{ old('notes', $payment->notes) }}</textarea>
                            </div>
                        </div>

                        <hr>

                        <div class="form-group row">
                            <div class="col-sm-9 offset-sm-3">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save fa-sm text-white-50"></i> {{ __('Update Payment') }}
                                </button>
                                <a href="{{ route('payments.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
