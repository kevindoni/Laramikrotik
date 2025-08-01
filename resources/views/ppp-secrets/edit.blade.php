@extends('layouts.admin')

@section('main-content')
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">{{ __('Edit PPP Secret') }}</h1>
        <div>
            <a href="{{ route('ppp-secrets.show', $pppSecret) }}" class="d-none d-sm-inline-block btn btn-sm btn-info shadow-sm">
                <i class="fas fa-eye fa-sm text-white-50"></i> {{ __('View Secret') }}
            </a>
            <a href="{{ route('ppp-secrets.index') }}" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
                <i class="fas fa-arrow-left fa-sm text-white-50"></i> {{ __('Back to PPP Secrets') }}
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
            <!-- PPP Secret Form -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">{{ __('PPP Secret Information') }}</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('ppp-secrets.update', $pppSecret) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="form-group row">
                            <label for="customer_id" class="col-sm-3 col-form-label">{{ __('Customer') }} <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <select class="form-control @error('customer_id') is-invalid @enderror" 
                                        id="customer_id" name="customer_id" required>
                                    <option value="">{{ __('Select Customer') }}</option>
                                    @foreach($customers as $customer)
                                        <option value="{{ $customer->id }}" {{ old('customer_id', $pppSecret->customer_id) == $customer->id ? 'selected' : '' }}>
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
                            <label for="ppp_profile_id" class="col-sm-3 col-form-label">{{ __('PPP Profile') }} <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <select class="form-control @error('ppp_profile_id') is-invalid @enderror"
                                        id="ppp_profile_id" name="ppp_profile_id" required>
                                    <option value="">{{ __('Select PPP Profile') }}</option>
                                    @foreach($profiles as $profile)
                                        <option value="{{ $profile->id }}"
                                                {{ old('ppp_profile_id', $pppSecret->ppp_profile_id) == $profile->id ? 'selected' : '' }}
                                                data-price="{{ $profile->price }}"
                                                data-speed="{{ $profile->formatted_rate_limit }}">
                                            {{ $profile->name }} - {{ $profile->formatted_rate_limit }}
                                            @if($profile->price) (Rp {{ number_format($profile->price, 0, ',', '.') }}/month) @endif
                                        </option>
                                    @endforeach
                                </select>
                                @error('ppp_profile_id')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="username" class="col-sm-3 col-form-label">{{ __('Username') }} <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control @error('username') is-invalid @enderror" 
                                       id="username" name="username" value="{{ old('username', $pppSecret->username) }}" required>
                                @error('username')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="password" class="col-sm-3 col-form-label">{{ __('Password') }} <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <div class="input-group">
                                    <input type="password" class="form-control @error('password') is-invalid @enderror" 
                                           id="password" name="password" value="{{ old('password', $pppSecret->password) }}" required>
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-outline-secondary" id="togglePassword">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    @error('password')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="service" class="col-sm-3 col-form-label">{{ __('Service Type') }}</label>
                            <div class="col-sm-9">
                                <select class="form-control @error('service') is-invalid @enderror" 
                                        id="service" name="service">
                                    <option value="pppoe" {{ old('service', $pppSecret->service) == 'pppoe' ? 'selected' : '' }}>PPPoE</option>
                                    <option value="pptp" {{ old('service', $pppSecret->service) == 'pptp' ? 'selected' : '' }}>PPTP</option>
                                    <option value="l2tp" {{ old('service', $pppSecret->service) == 'l2tp' ? 'selected' : '' }}>L2TP</option>
                                    <option value="sstp" {{ old('service', $pppSecret->service) == 'sstp' ? 'selected' : '' }}>SSTP</option>
                                </select>
                                @error('service')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="local_address" class="col-sm-3 col-form-label">{{ __('Local Address') }}</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control @error('local_address') is-invalid @enderror" 
                                       id="local_address" name="local_address" value="{{ old('local_address', $pppSecret->local_address) }}">
                                @error('local_address')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="remote_address" class="col-sm-3 col-form-label">{{ __('Remote Address') }}</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control @error('remote_address') is-invalid @enderror" 
                                       id="remote_address" name="remote_address" value="{{ old('remote_address', $pppSecret->remote_address) }}">
                                @error('remote_address')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="installation_date" class="col-sm-3 col-form-label">{{ __('Installation Date') }}</label>
                            <div class="col-sm-9">
                                <input type="date" class="form-control @error('installation_date') is-invalid @enderror" 
                                       id="installation_date" name="installation_date" 
                                       value="{{ old('installation_date', $pppSecret->installation_date ? $pppSecret->installation_date->format('Y-m-d') : '') }}">
                                @error('installation_date')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="due_date" class="col-sm-3 col-form-label">{{ __('Due Date') }}</label>
                            <div class="col-sm-9">
                                <input type="date" class="form-control @error('due_date') is-invalid @enderror" 
                                       id="due_date" name="due_date" 
                                       value="{{ old('due_date', $pppSecret->due_date ? $pppSecret->due_date->format('Y-m-d') : '') }}">
                                @error('due_date')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="comment" class="col-sm-3 col-form-label">{{ __('Comment') }}</label>
                            <div class="col-sm-9">
                                <textarea class="form-control @error('comment') is-invalid @enderror" 
                                          id="comment" name="comment" rows="3">{{ old('comment', $pppSecret->comment) }}</textarea>
                                @error('comment')
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
                                           {{ old('is_active', $pppSecret->is_active) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_active">
                                        {{ __('Active PPP Secret') }}
                                    </label>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <div class="form-group row">
                            <div class="col-sm-9 offset-sm-3">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save fa-sm text-white-50"></i> {{ __('Update PPP Secret') }}
                                </button>
                                <a href="{{ route('ppp-secrets.show', $pppSecret) }}" class="btn btn-info">
                                    {{ __('View Secret') }}
                                </a>
                                <a href="{{ route('ppp-secrets.index') }}" class="btn btn-secondary">
                                    {{ __('Cancel') }}
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Secret Summary Card -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">{{ __('Secret Summary') }}</h6>
                </div>
                <div class="card-body">
                    <div class="text-center">
                        <i class="fas fa-key fa-3x text-gray-300 mb-3"></i>
                    </div>
                    
                    <div class="mt-4">
                        <h6 class="text-primary">{{ __('Current Status:') }}</h6>
                        <p>
                            <span class="badge badge-{{ $pppSecret->is_active ? 'success' : 'danger' }}">
                                {{ $pppSecret->is_active ? __('Active') : __('Inactive') }}
                            </span>
                        </p>
                        
                        <h6 class="text-primary">{{ __('Customer:') }}</h6>
                        <p class="text-sm">{{ $pppSecret->customer->name ?? __('No Customer') }}</p>
                        
                        <h6 class="text-primary">{{ __('Profile:') }}</h6>
                        <p class="text-sm">{{ $pppSecret->pppProfile->name ?? __('No Profile') }}</p>
                        
                        <h6 class="text-primary">{{ __('Statistics:') }}</h6>
                        <ul class="text-sm text-gray-600">
                            <li>{{ __('Created:') }} {{ $pppSecret->created_at->format('d M Y') }}</li>
                            <li>{{ __('Total Invoices:') }} {{ $pppSecret->invoices->count() }}</li>
                            <li>{{ __('Unpaid Invoices:') }} {{ $pppSecret->unpaidInvoices->count() }}</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    // Toggle password visibility
    document.getElementById('togglePassword').addEventListener('click', function() {
        const passwordInput = document.getElementById('password');
        const icon = this.querySelector('i');
        
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    });
</script>
@endpush
