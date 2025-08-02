@extends('layouts.admin')

@section('main-content')
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">{{ __('Edit Customer') }}</h1>
        <div>
            <a href="{{ route('customers.show', $customer) }}" class="d-none d-sm-inline-block btn btn-sm btn-info shadow-sm">
                <i class="fas fa-eye fa-sm text-white-50"></i> {{ __('View Customer') }}
            </a>
            <a href="{{ route('customers.index') }}" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
                <i class="fas fa-arrow-left fa-sm text-white-50"></i> {{ __('Back to Customers') }}
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
            <!-- Customer Form -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">{{ __('Customer Information') }}</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('customers.update', $customer) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="form-group row">
                            <label for="name" class="col-sm-3 col-form-label">{{ __('Full Name') }} <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                       id="name" name="name" value="{{ old('name', $customer->name) }}" required>
                                @error('name')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="phone" class="col-sm-3 col-form-label">{{ __('Phone Number') }} <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control @error('phone') is-invalid @enderror" 
                                       id="phone" name="phone" value="{{ old('phone', $customer->phone) }}" required>
                                @error('phone')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="email" class="col-sm-3 col-form-label">{{ __('Email Address') }}</label>
                            <div class="col-sm-9">
                                <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                       id="email" name="email" value="{{ old('email', $customer->email) }}">
                                @error('email')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="address" class="col-sm-3 col-form-label">{{ __('Address') }} <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <textarea class="form-control @error('address') is-invalid @enderror" 
                                          id="address" name="address" rows="3" required>{{ old('address', $customer->address) }}</textarea>
                                @error('address')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="identity_card_type" class="col-sm-3 col-form-label">{{ __('Identity Card Type') }}</label>
                            <div class="col-sm-9">
                                <select class="form-control @error('identity_card_type') is-invalid @enderror" 
                                        id="identity_card_type" name="identity_card_type">
                                    <option value="">{{ __('Select Identity Card Type') }}</option>
                                    <option value="KTP" {{ old('identity_card_type', $customer->identity_card_type) == 'KTP' ? 'selected' : '' }}>KTP</option>
                                    <option value="SIM" {{ old('identity_card_type', $customer->identity_card_type) == 'SIM' ? 'selected' : '' }}>SIM</option>
                                    <option value="Passport" {{ old('identity_card_type', $customer->identity_card_type) == 'Passport' ? 'selected' : '' }}>Passport</option>
                                </select>
                                @error('identity_card_type')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="identity_card_number" class="col-sm-3 col-form-label">{{ __('Identity Card Number') }}</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control @error('identity_card_number') is-invalid @enderror" 
                                       id="identity_card_number" name="identity_card_number" value="{{ old('identity_card_number', $customer->identity_card_number) }}">
                                @error('identity_card_number')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="location" class="col-sm-3 col-form-label">{{ __('Location') }}</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control @error('location') is-invalid @enderror" 
                                       id="location" name="location" value="{{ old('location', $customer->location) }}" 
                                       placeholder="{{ __('e.g., Building name, area') }}">
                                @error('location')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="coordinates" class="col-sm-3 col-form-label">{{ __('Coordinates') }}</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control @error('coordinates') is-invalid @enderror" 
                                       id="coordinates" name="coordinates" value="{{ old('coordinates', $customer->coordinates) }}" 
                                       placeholder="{{ __('e.g., -6.200000, 106.816666') }}">
                                @error('coordinates')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="registered_date" class="col-sm-3 col-form-label">{{ __('Registered Date') }}</label>
                            <div class="col-sm-9">
                                <input type="date" class="form-control @error('registered_date') is-invalid @enderror" 
                                       id="registered_date" name="registered_date" value="{{ old('registered_date', $customer->registered_date ? $customer->registered_date->format('Y-m-d') : '') }}">
                                @error('registered_date')
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
                                          id="notes" name="notes" rows="3">{{ old('notes', $customer->notes) }}</textarea>
                                @error('notes')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="is_active" class="col-sm-3 col-form-label">{{ __('Status') }}</label>
                            <div class="col-sm-9">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" 
                                           {{ old('is_active', $customer->is_active) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_active">
                                        {{ __('Active Customer') }}
                                    </label>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <div class="form-group row">
                            <div class="col-sm-9 offset-sm-3">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save fa-sm text-white-50"></i> {{ __('Update Customer') }}
                                </button>
                                <a href="{{ route('customers.show', $customer) }}" class="btn btn-info">
                                    {{ __('View Customer') }}
                                </a>
                                <a href="{{ route('customers.index') }}" class="btn btn-secondary">
                                    {{ __('Cancel') }}
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Customer Summary Card -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">{{ __('Customer Summary') }}</h6>
                </div>
                <div class="card-body">
                    <div class="text-center">
                        <i class="fas fa-user-edit fa-3x text-gray-300 mb-3"></i>
                    </div>
                    
                    <div class="mt-4">
                        <h6 class="text-primary">{{ __('Current Status:') }}</h6>
                        <p>
                            <span class="badge badge-{{ $customer->is_active ? 'success' : 'danger' }}">
                                {{ $customer->is_active ? __('Active') : __('Inactive') }}
                            </span>
                        </p>
                        
                        <h6 class="text-primary">{{ __('Statistics:') }}</h6>
                        <ul class="text-sm text-gray-600">
                            <li>{{ __('PPP Secrets:') }} {{ $customer->pppSecrets->count() }}</li>
                            <li>{{ __('Active Secrets:') }} {{ $customer->activePppSecrets->count() }}</li>
                            <li>{{ __('Total Invoices:') }} {{ $customer->invoices->count() }}</li>
                            <li>{{ __('Unpaid Invoices:') }} {{ $customer->unpaidInvoices->count() }}</li>
                        </ul>
                        
                        <h6 class="text-primary mt-3">{{ __('Member Since:') }}</h6>
                        <p class="text-sm text-gray-600">{{ $customer->created_at->format('d M Y') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    // Auto format phone number
    document.getElementById('phone').addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.startsWith('0')) {
            value = '62' + value.substring(1);
        }
        if (!value.startsWith('62')) {
            value = '62' + value;
        }
        e.target.value = value;
    });
</script>
@endpush
