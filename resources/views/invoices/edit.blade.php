@extends('layouts.admin')

@section('main-content')
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">{{ __('Edit Invoice') }}</h1>
        <div>
            <a href="{{ route('invoices.show', $invoice) }}" class="d-none d-sm-inline-block btn btn-sm btn-info shadow-sm">
                <i class="fas fa-eye fa-sm text-white-50"></i> {{ __('View Invoice') }}
            </a>
            <a href="{{ route('invoices.index') }}" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
                <i class="fas fa-arrow-left fa-sm text-white-50"></i> {{ __('Back to Invoices') }}
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success border-left-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger border-left-danger" role="alert">
            <ul class="pl-4 my-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Content Row -->
    <div class="row">
        <div class="col-lg-8">
            <!-- Invoice Form -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">{{ __('Invoice Information') }}</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('invoices.update', $invoice) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="form-group row">
                            <label for="invoice_number" class="col-sm-3 col-form-label">{{ __('Invoice Number') }}</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control" id="invoice_number" name="invoice_number" 
                                       value="{{ $invoice->invoice_number }}" readonly style="background-color: #f8f9fc;">
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="customer_id" class="col-sm-3 col-form-label">{{ __('Customer') }} <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <select class="form-control @error('customer_id') is-invalid @enderror" 
                                        id="customer_id" name="customer_id" required>
                                    <option value="">{{ __('Select Customer') }}</option>
                                    @foreach($customers as $customer)
                                        <option value="{{ $customer->id }}" {{ old('customer_id', $invoice->customer_id) == $customer->id ? 'selected' : '' }}>
                                            {{ $customer->name }} ({{ $customer->phone }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('customer_id')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="invoice_date" class="col-sm-3 col-form-label">{{ __('Invoice Date') }} <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <input type="date" class="form-control @error('invoice_date') is-invalid @enderror" 
                                       id="invoice_date" name="invoice_date" value="{{ old('invoice_date', $invoice->invoice_date ? $invoice->invoice_date->format('Y-m-d') : '') }}" required>
                                @error('invoice_date')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
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
                                           id="amount" name="amount" value="{{ old('amount', $invoice->amount) }}" min="0" step="1000" required>
                                    @error('amount')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="tax" class="col-sm-3 col-form-label">{{ __('Tax') }}</label>
                            <div class="col-sm-9">
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">Rp</span>
                                    </div>
                                    <input type="number" class="form-control @error('tax') is-invalid @enderror" 
                                           id="tax" name="tax" value="{{ old('tax', $invoice->tax) }}" min="0" step="1000">
                                    @error('tax')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                                <small class="form-text text-muted">{{ __('Leave empty or 0 for no tax') }}</small>
                            </div>
                        </div>                        <div class="form-group row">
                            <label for="due_date" class="col-sm-3 col-form-label">{{ __('Due Date') }} <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <input type="date" class="form-control @error('due_date') is-invalid @enderror" 
                                       id="due_date" name="due_date" value="{{ old('due_date', $invoice->due_date->format('Y-m-d')) }}" required>
                                @error('due_date')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="status" class="col-sm-3 col-form-label">{{ __('Status') }}</label>
                            <div class="col-sm-9">
                                <select class="form-control @error('status') is-invalid @enderror" 
                                        id="status" name="status">
                                    <option value="unpaid" {{ old('status', $invoice->status) == 'unpaid' ? 'selected' : '' }}>{{ __('Unpaid') }}</option>
                                    <option value="paid" {{ old('status', $invoice->status) == 'paid' ? 'selected' : '' }}>{{ __('Paid') }}</option>
                                    <option value="overdue" {{ old('status', $invoice->status) == 'overdue' ? 'selected' : '' }}>{{ __('Overdue') }}</option>
                                    <option value="cancelled" {{ old('status', $invoice->status) == 'cancelled' ? 'selected' : '' }}>{{ __('Cancelled') }}</option>
                                </select>
                                @error('status')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="notes" class="col-sm-3 col-form-label">{{ __('Notes') }}</label>
                            <div class="col-sm-9">
                                <textarea class="form-control @error('notes') is-invalid @enderror" 
                                          id="notes" name="notes" rows="3">{{ old('notes', $invoice->notes) }}</textarea>
                                @error('notes')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <hr>

                        <div class="form-group row">
                            <div class="col-sm-9 offset-sm-3">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save fa-sm text-white-50"></i> {{ __('Update Invoice') }}
                                </button>
                                <a href="{{ route('invoices.show', $invoice) }}" class="btn btn-info">
                                    {{ __('View Invoice') }}
                                </a>
                                <a href="{{ route('invoices.index') }}" class="btn btn-secondary">
                                    {{ __('Cancel') }}
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Invoice Summary Card -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">{{ __('Invoice Summary') }}</h6>
                </div>
                <div class="card-body">
                    <div class="text-center">
                        <i class="fas fa-file-invoice fa-3x text-gray-300 mb-3"></i>
                    </div>
                    
                    <div class="mt-4">
                        <h6 class="text-primary">{{ __('Current Status:') }}</h6>
                        <p>
                            <span class="badge badge-{{ $invoice->status === 'paid' ? 'success' : ($invoice->status === 'overdue' ? 'danger' : 'warning') }}">
                                {{ ucfirst($invoice->status) }}
                            </span>
                        </p>
                        
                        <h6 class="text-primary">{{ __('Amount:') }}</h6>
                        <p class="h5 text-success">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</p>
                        
                        <h6 class="text-primary">{{ __('Payment Information:') }}</h6>
                        <ul class="text-sm text-gray-600">
                            <li>{{ __('Total Paid:') }} Rp {{ number_format($invoice->total_paid, 0, ',', '.') }}</li>
                            <li>{{ __('Remaining:') }} Rp {{ number_format($invoice->remaining_amount, 0, ',', '.') }}</li>
                            <li>{{ __('Payments Count:') }} {{ $invoice->payments->count() }}</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
