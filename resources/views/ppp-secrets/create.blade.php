@extends('layouts.admin')

@section('main-content')
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">{{ __('Add New PPP Secret') }}</h1>
        <a href="{{ route('ppp-secrets.index') }}" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
            <i class="fas fa-arrow-left fa-sm text-white-50"></i> {{ __('Back to PPP Secrets') }}
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
            <!-- PPP Secret Form -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">{{ __('PPP Secret Information') }}</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('ppp-secrets.store') }}" method="POST" id="pppSecretForm">
                        @csrf
                        
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
                            <label for="ppp_profile_id" class="col-sm-3 col-form-label">{{ __('PPP Profile') }} <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <select class="form-control @error('ppp_profile_id') is-invalid @enderror"
                                        id="ppp_profile_id" name="ppp_profile_id" required>
                                    <option value="">{{ __('Select PPP Profile') }}</option>
                                    @foreach($profiles as $profile)
                                        <option value="{{ $profile->id }}"
                                                {{ old('ppp_profile_id', request('profile_id')) == $profile->id ? 'selected' : '' }}
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
                                <small id="profileInfo" class="form-text text-muted"></small>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="username" class="col-sm-3 col-form-label">{{ __('Username') }} <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <div class="input-group">
                                    <input type="text" class="form-control @error('username') is-invalid @enderror" 
                                           id="username" name="username" value="{{ old('username') }}" required
                                           placeholder="{{ __('Enter username') }}">
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-outline-secondary" id="generateUsername">
                                            <i class="fas fa-magic"></i> {{ __('Generate') }}
                                        </button>
                                    </div>
                                    @error('username')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="password" class="col-sm-3 col-form-label">{{ __('Password') }} <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <div class="input-group">
                                    <input type="password" class="form-control @error('password') is-invalid @enderror" 
                                           id="password" name="password" value="{{ old('password') }}" required
                                           placeholder="{{ __('Enter password') }}">
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-outline-secondary" id="generatePassword">
                                            <i class="fas fa-key"></i> {{ __('Generate') }}
                                        </button>
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
                                    <option value="pppoe" {{ old('service', 'pppoe') == 'pppoe' ? 'selected' : '' }}>PPPoE</option>
                                    <option value="pptp" {{ old('service') == 'pptp' ? 'selected' : '' }}>PPTP</option>
                                    <option value="l2tp" {{ old('service') == 'l2tp' ? 'selected' : '' }}>L2TP</option>
                                    <option value="sstp" {{ old('service') == 'sstp' ? 'selected' : '' }}>SSTP</option>
                                </select>
                                @error('service')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <hr>
                        <h6 class="text-primary">{{ __('Network Configuration') }}</h6>

                        <div class="form-group row">
                            <label for="local_address" class="col-sm-3 col-form-label">{{ __('Local Address') }}</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control @error('local_address') is-invalid @enderror" 
                                       id="local_address" name="local_address" value="{{ old('local_address') }}"
                                       placeholder="{{ __('e.g., 192.168.1.1 (optional)') }}">
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
                                       id="remote_address" name="remote_address" value="{{ old('remote_address') }}"
                                       placeholder="{{ __('e.g., 192.168.1.100 (optional)') }}">
                                @error('remote_address')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <hr>
                        <h6 class="text-primary">{{ __('Billing Information') }}</h6>

                        <div class="form-group row">
                            <label for="installation_date" class="col-sm-3 col-form-label">{{ __('Installation Date') }}</label>
                            <div class="col-sm-9">
                                <input type="date" class="form-control @error('installation_date') is-invalid @enderror" 
                                       id="installation_date" name="installation_date" value="{{ old('installation_date', date('Y-m-d')) }}">
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
                                       id="due_date" name="due_date" value="{{ old('due_date', date('Y-m-d', strtotime('+1 month'))) }}">
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
                                          id="comment" name="comment" rows="3">{{ old('comment') }}</textarea>
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
                                           {{ old('is_active', 1) ? 'checked' : '' }}>
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
                                    <i class="fas fa-save fa-sm text-white-50"></i> {{ __('Save PPP Secret') }}
                                </button>
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
            <!-- Information Card -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">{{ __('Information') }}</h6>
                </div>
                <div class="card-body">
                    <div class="text-center">
                        <i class="fas fa-key fa-3x text-gray-300 mb-3"></i>
                    </div>
                    <p class="text-gray-600 text-center">{{ __('Create a new PPP secret for customer internet access. Make sure to select the correct customer and profile.') }}</p>
                    
                    <div class="mt-4">
                        <h6 class="text-primary">{{ __('Tips:') }}</h6>
                        <ul class="text-sm text-gray-600">
                            <li>{{ __('Username should be unique across all secrets') }}</li>
                            <li>{{ __('Use a strong password for security') }}</li>
                            <li>{{ __('PPPoE is the most common service type') }}</li>
                            <li>{{ __('Set appropriate due date for billing') }}</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Selected Profile Info -->
            <div class="card shadow mb-4" id="profileCard" style="display: none;">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">{{ __('Selected Profile') }}</h6>
                </div>
                <div class="card-body">
                    <div id="profileDetails"></div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    // Generate username
    document.getElementById('generateUsername').addEventListener('click', function() {
        const customerSelect = document.getElementById('customer_id');
        const usernameInput = document.getElementById('username');
        
        if (customerSelect.value) {
            const customerName = customerSelect.options[customerSelect.selectedIndex].text.split(' (')[0];
            const randomNum = Math.floor(Math.random() * 1000);
            const username = customerName.toLowerCase().replace(/\s+/g, '') + randomNum;
            usernameInput.value = username;
        } else {
            alert('{{ __("Please select a customer first") }}');
        }
    });

    // Generate password
    document.getElementById('generatePassword').addEventListener('click', function() {
        const length = 8;
        const charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        let password = "";
        for (let i = 0, n = charset.length; i < length; ++i) {
            password += charset.charAt(Math.floor(Math.random() * n));
        }
        document.getElementById('password').value = password;
    });

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

    // Show profile information
    document.getElementById('ppp_profile_id').addEventListener('change', function() {
        const option = this.options[this.selectedIndex];
        const profileCard = document.getElementById('profileCard');
        const profileDetails = document.getElementById('profileDetails');
        const profileInfo = document.getElementById('profileInfo');
        
        if (this.value) {
            const speed = option.getAttribute('data-speed');
            const price = option.getAttribute('data-price');
            
            profileInfo.textContent = `Speed: ${speed}${price ? ', Price: Rp ' + parseInt(price).toLocaleString() + '/month' : ''}`;
            
            profileDetails.innerHTML = `
                <h6 class="text-primary">${option.text}</h6>
                <p class="text-sm">
                    <strong>Speed:</strong> ${speed}<br>
                    <strong>Price:</strong> ${price ? 'Rp ' + parseInt(price).toLocaleString() + '/month' : 'Free'}
                </p>
            `;
            profileCard.style.display = 'block';
        } else {
            profileCard.style.display = 'none';
            profileInfo.textContent = '';
        }
    });

    // Auto calculate due date based on installation date
    document.getElementById('installation_date').addEventListener('change', function() {
        const installDate = new Date(this.value);
        const dueDate = new Date(installDate);
        dueDate.setMonth(dueDate.getMonth() + 1);
        
        document.getElementById('due_date').value = dueDate.toISOString().split('T')[0];
    });

    // Trigger profile change on page load if profile is pre-selected
    window.addEventListener('load', function() {
        const profileSelect = document.getElementById('ppp_profile_id');
        if (profileSelect.value) {
            profileSelect.dispatchEvent(new Event('change'));
        }
    });
</script>
@endpush
