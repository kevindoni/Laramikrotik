@extends('layouts.admin')

@section('main-content')
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">{{ __('Create New Invoice') }}</h1>
        <a href="{{ route('invoices.index') }}" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
            <i class="fas fa-arrow-left fa-sm text-white-50"></i> {{ __('Back to Invoices') }}
        </a>
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
                    <form action="{{ route('invoices.store') }}" method="POST" id="invoiceForm">
                        @csrf
                        
                        <div class="form-group row">
                            <label for="invoice_number" class="col-sm-3 col-form-label">{{ __('Invoice Number') }}</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control @error('invoice_number') is-invalid @enderror" 
                                       id="invoice_number" name="invoice_number" value="{{ old('invoice_number', $invoiceNumber ?? '') }}" 
                                       readonly style="background-color: #f8f9fc;">
                                @error('invoice_number')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                                <small class="form-text text-muted">{{ __('Auto-generated invoice number') }}</small>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="customer_id" class="col-sm-3 col-form-label">{{ __('Customer') }} <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <select class="form-control @error('customer_id') is-invalid @enderror" 
                                        id="customer_id" name="customer_id" required>
                                    <option value="">{{ __('Select Customer') }}</option>
                                    @foreach($customers as $customer)
                                        <option value="{{ $customer->id }}" {{ old('customer_id', request('customer_id')) == $customer->id ? 'selected' : '' }}>
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
                            <label for="ppp_secret_id" class="col-sm-3 col-form-label">{{ __('PPP Secret') }}</label>
                            <div class="col-sm-9">
                                <select class="form-control @error('ppp_secret_id') is-invalid @enderror" 
                                        id="ppp_secret_id" name="ppp_secret_id">
                                    <option value="">{{ __('Select PPP Secret (Optional)') }}</option>
                                    @foreach($pppSecrets as $secret)
                                        <option value="{{ $secret->id }}" 
                                                {{ old('ppp_secret_id', request('ppp_secret_id')) == $secret->id ? 'selected' : '' }}
                                                data-customer="{{ $secret->customer_id }}"
                                                data-profile="{{ $secret->pppProfile->name ?? '' }}"
                                                data-price="{{ $secret->pppProfile->price ?? 0 }}">
                                            {{ $secret->username }} 
                                            @if($secret->pppProfile) 
                                                ({{ $secret->pppProfile->name }} - Rp {{ number_format($secret->pppProfile->price ?? 0, 0, ',', '.') }})
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                @error('ppp_secret_id')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                                <small class="form-text text-muted">{{ __('Select a PPP secret to auto-fill the amount from profile price') }}</small>
                            </div>
                        </div>

                        <hr>
                        <h6 class="text-primary">{{ __('Billing Details') }}</h6>

                        <div class="form-group row">
                            <label for="invoice_date" class="col-sm-3 col-form-label">{{ __('Invoice Date') }} <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <input type="date" class="form-control @error('invoice_date') is-invalid @enderror" 
                                       id="invoice_date" name="invoice_date" value="{{ old('invoice_date', date('Y-m-d')) }}" required>
                                @error('invoice_date')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="due_date" class="col-sm-3 col-form-label">{{ __('Due Date') }} <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <input type="date" class="form-control @error('due_date') is-invalid @enderror" 
                                       id="due_date" name="due_date" value="{{ old('due_date', date('Y-m-d', strtotime('+30 days'))) }}" required>
                                @error('due_date')
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
                                           id="amount" name="amount" value="{{ old('amount') }}" min="0" step="1000" required>
                                    @error('amount')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="tax" class="col-sm-3 col-form-label">{{ __('Tax Amount') }}</label>
                            <div class="col-sm-9">
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">Rp</span>
                                    </div>
                                    <input type="number" class="form-control @error('tax') is-invalid @enderror" 
                                           id="tax" name="tax" value="{{ old('tax', 0) }}" min="0" step="100">
                                    @error('tax')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                                <small class="form-text text-muted">{{ __('Leave 0 if no tax applicable') }}</small>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="total_amount" class="col-sm-3 col-form-label">{{ __('Total Amount') }}</label>
                            <div class="col-sm-9">
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">Rp</span>
                                    </div>
                                    <input type="number" class="form-control" id="total_amount" name="total_amount" 
                                           value="{{ old('total_amount') }}" readonly style="background-color: #f8f9fc;">
                                </div>
                                <small class="form-text text-muted">{{ __('Automatically calculated: Amount + Tax') }}</small>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="notes" class="col-sm-3 col-form-label">{{ __('Notes') }}</label>
                            <div class="col-sm-9">
                                <textarea class="form-control @error('notes') is-invalid @enderror" 
                                          id="notes" name="notes" rows="3">{{ old('notes') }}</textarea>
                                @error('notes')
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
                                    <option value="unpaid" {{ old('status', 'unpaid') == 'unpaid' ? 'selected' : '' }}>{{ __('Unpaid') }}</option>
                                    <option value="paid" {{ old('status') == 'paid' ? 'selected' : '' }}>{{ __('Paid') }}</option>
                                    <option value="overdue" {{ old('status') == 'overdue' ? 'selected' : '' }}>{{ __('Overdue') }}</option>
                                    <option value="cancelled" {{ old('status') == 'cancelled' ? 'selected' : '' }}>{{ __('Cancelled') }}</option>
                                </select>
                                @error('status')
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
                                    <i class="fas fa-save fa-sm text-white-50"></i> {{ __('Create Invoice') }}
                                </button>
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
            <!-- Information Card -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">{{ __('Information') }}</h6>
                </div>
                <div class="card-body">
                    <div class="text-center">
                        <i class="fas fa-file-invoice fa-3x text-gray-300 mb-3"></i>
                    </div>
                    <p class="text-gray-600 text-center">{{ __('Create a new invoice for customer billing. Select customer and PPP secret to auto-fill pricing information.') }}</p>
                    
                    <div class="mt-4">
                        <h6 class="text-primary">{{ __('Tips:') }}</h6>
                        <ul class="text-sm text-gray-600">
                            <li>{{ __('Invoice number is auto-generated') }}</li>
                            <li>{{ __('Selecting PPP secret will auto-fill amount') }}</li>
                            <li>{{ __('Default due date is 30 days from invoice date') }}</li>
                            <li>{{ __('Total amount includes tax if applicable') }}</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Selected Customer Info -->
            <div class="card shadow mb-4" id="customerCard" style="display: none;">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">{{ __('Selected Customer') }}</h6>
                </div>
                <div class="card-body">
                    <div id="customerDetails"></div>
                </div>
            </div>

            <!-- Calculation Summary -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">{{ __('Invoice Summary') }}</h6>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <th>{{ __('Amount') }}</th>
                            <td class="text-right" id="summaryAmount">Rp 0</td>
                        </tr>
                        <tr>
                            <th>{{ __('Tax') }}</th>
                            <td class="text-right" id="summaryTax">Rp 0</td>
                        </tr>
                        <tr class="table-primary">
                            <th>{{ __('Total') }}</th>
                            <td class="text-right font-weight-bold" id="summaryTotal">Rp 0</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    function formatCurrency(amount) {
        return 'Rp ' + parseInt(amount || 0).toLocaleString('id-ID');
    }

    function calculateTotal() {
        const amount = parseFloat(document.getElementById('amount').value) || 0;
        const tax = parseFloat(document.getElementById('tax').value) || 0;
        const total = amount + tax;
        
        document.getElementById('total_amount').value = total;
        
        // Update summary
        document.getElementById('summaryAmount').textContent = formatCurrency(amount);
        document.getElementById('summaryTax').textContent = formatCurrency(tax);
        document.getElementById('summaryTotal').textContent = formatCurrency(total);
    }

    // Calculate total when amount or tax changes
    document.getElementById('amount').addEventListener('input', calculateTotal);
    document.getElementById('tax').addEventListener('input', calculateTotal);

    // Auto-calculate due date when invoice date changes
    document.getElementById('invoice_date').addEventListener('change', function() {
        const invoiceDate = new Date(this.value);
        const dueDate = new Date(invoiceDate);
        dueDate.setDate(dueDate.getDate() + 30);
        
        document.getElementById('due_date').value = dueDate.toISOString().split('T')[0];
    });

    // Handle PPP secret selection
    document.getElementById('ppp_secret_id').addEventListener('change', function() {
        const option = this.options[this.selectedIndex];
        const customerId = option.getAttribute('data-customer');
        const price = option.getAttribute('data-price');
        
        if (this.value && customerId) {
            // Set customer
            document.getElementById('customer_id').value = customerId;
            
            // Set amount from profile price
            if (price && price > 0) {
                document.getElementById('amount').value = price;
                calculateTotal();
            }
        }
    });

    // Handle customer selection
    document.getElementById('customer_id').addEventListener('change', function() {
        const customerCard = document.getElementById('customerCard');
        const customerDetails = document.getElementById('customerDetails');
        
        if (this.value) {
            const customerName = this.options[this.selectedIndex].text;
            customerDetails.innerHTML = `<h6 class="text-primary">${customerName}</h6>`;
            customerCard.style.display = 'block';
            
            // Filter PPP secrets for selected customer
            const pppSecretSelect = document.getElementById('ppp_secret_id');
            Array.from(pppSecretSelect.options).forEach(option => {
                if (option.value) {
                    const secretCustomerId = option.getAttribute('data-customer');
                    option.style.display = secretCustomerId === this.value ? 'block' : 'none';
                }
            });
        } else {
            customerCard.style.display = 'none';
            
            // Show all PPP secrets
            const pppSecretSelect = document.getElementById('ppp_secret_id');
            Array.from(pppSecretSelect.options).forEach(option => {
                option.style.display = 'block';
            });
        }
    });

    // Initialize calculation on page load
    window.addEventListener('load', function() {
        calculateTotal();
        
        // Trigger customer change if customer is pre-selected
        const customerSelect = document.getElementById('customer_id');
        if (customerSelect.value) {
            customerSelect.dispatchEvent(new Event('change'));
        }
    });
</script>
@endpush
