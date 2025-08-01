@extends('layouts.admin')

@section('main-content')
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">{{ __('Edit PPP Profile') }}</h1>
        <div>
            <a href="{{ route('ppp-profiles.show', $pppProfile) }}" class="d-none d-sm-inline-block btn btn-sm btn-info shadow-sm">
                <i class="fas fa-eye fa-sm text-white-50"></i> {{ __('View Profile') }}
            </a>
            <a href="{{ route('ppp-profiles.index') }}" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
                <i class="fas fa-arrow-left fa-sm text-white-50"></i> {{ __('Back to PPP Profiles') }}
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
            <!-- PPP Profile Form -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">{{ __('PPP Profile Information') }}</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('ppp-profiles.update', $pppProfile) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="form-group row">
                            <label for="name" class="col-sm-3 col-form-label">{{ __('Profile Name') }} <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                       id="name" name="name" value="{{ old('name', $pppProfile->name) }}" required
                                       placeholder="{{ __('e.g., 10M-Package, Premium-50M') }}">
                                @error('name')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="description" class="col-sm-3 col-form-label">{{ __('Description') }}</label>
                            <div class="col-sm-9">
                                <textarea class="form-control @error('description') is-invalid @enderror" 
                                          id="description" name="description" rows="3">{{ old('description', $pppProfile->description) }}</textarea>
                                @error('description')
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
                                       id="local_address" name="local_address" value="{{ old('local_address', $pppProfile->local_address) }}"
                                       placeholder="{{ __('e.g., 192.168.1.1') }}">
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
                                       id="remote_address" name="remote_address" value="{{ old('remote_address', $pppProfile->remote_address) }}"
                                       placeholder="{{ __('e.g., 192.168.1.0/24') }}">
                                @error('remote_address')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label">{{ __('Rate Limit') }}</label>
                            <div class="col-sm-9">
                                <div class="row">
                                    <div class="col-sm-6">
                                        <input type="text" class="form-control @error('download_speed') is-invalid @enderror" 
                                               id="download_speed" name="download_speed" 
                                               value="{{ old('download_speed', $pppProfile->speeds['download']) }}"
                                               placeholder="{{ __('Download (e.g., 10M)') }}">
                                        @error('download_speed')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>
                                    <div class="col-sm-6">
                                        <input type="text" class="form-control @error('upload_speed') is-invalid @enderror" 
                                               id="upload_speed" name="upload_speed" 
                                               value="{{ old('upload_speed', $pppProfile->speeds['upload']) }}"
                                               placeholder="{{ __('Upload (e.g., 2M)') }}">
                                        @error('upload_speed')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>
                                </div>
                                <small class="form-text text-muted">{{ __('Leave empty for unlimited. Use format like 10M, 1G, 512K') }}</small>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="parent_queue" class="col-sm-3 col-form-label">{{ __('Parent Queue') }}</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control @error('parent_queue') is-invalid @enderror" 
                                       id="parent_queue" name="parent_queue" value="{{ old('parent_queue', $pppProfile->parent_queue) }}"
                                       placeholder="{{ __('e.g., main-queue') }}">
                                @error('parent_queue')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <hr>
                        <h6 class="text-primary">{{ __('Profile Settings') }}</h6>

                        <div class="form-group row">
                            <label for="price" class="col-sm-3 col-form-label">{{ __('Monthly Price') }}</label>
                            <div class="col-sm-9">
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">Rp</span>
                                    </div>
                                    <input type="number" class="form-control @error('price') is-invalid @enderror" 
                                           id="price" name="price" value="{{ old('price', $pppProfile->price) }}" min="0" step="1000"
                                           placeholder="{{ __('Enter monthly price') }}">
                                    @error('price')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="only_one" class="col-sm-3 col-form-label">{{ __('Only One Session') }}</label>
                            <div class="col-sm-9">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="only_one" name="only_one" value="1" 
                                           {{ old('only_one', $pppProfile->only_one) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="only_one">
                                        {{ __('Allow only one active session per user') }}
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="is_active" class="col-sm-3 col-form-label">{{ __('Status') }}</label>
                            <div class="col-sm-9">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" 
                                           {{ old('is_active', $pppProfile->is_active) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_active">
                                        {{ __('Active Profile') }}
                                    </label>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <div class="form-group row">
                            <div class="col-sm-9 offset-sm-3">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save fa-sm text-white-50"></i> {{ __('Update Profile') }}
                                </button>
                                <a href="{{ route('ppp-profiles.show', $pppProfile) }}" class="btn btn-info">
                                    {{ __('View Profile') }}
                                </a>
                                <a href="{{ route('ppp-profiles.index') }}" class="btn btn-secondary">
                                    {{ __('Cancel') }}
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Profile Summary Card -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">{{ __('Profile Summary') }}</h6>
                </div>
                <div class="card-body">
                    <div class="text-center">
                        <i class="fas fa-cogs fa-3x text-gray-300 mb-3"></i>
                    </div>
                    
                    <div class="mt-4">
                        <h6 class="text-primary">{{ __('Current Status:') }}</h6>
                        <p>
                            <span class="badge badge-{{ $pppProfile->is_active ? 'success' : 'danger' }}">
                                {{ $pppProfile->is_active ? __('Active') : __('Inactive') }}
                            </span>
                        </p>
                        
                        <h6 class="text-primary">{{ __('Usage Statistics:') }}</h6>
                        <ul class="text-sm text-gray-600">
                            <li>{{ __('Total PPP Secrets:') }} {{ $pppProfile->pppSecrets->count() }}</li>
                            <li>{{ __('Active Secrets:') }} {{ $pppProfile->activePppSecrets->count() }}</li>
                        </ul>
                        
                        <h6 class="text-primary mt-3">{{ __('Current Configuration:') }}</h6>
                        <p class="text-sm text-gray-600">
                            <strong>{{ __('Speed:') }}</strong> {{ $pppProfile->formatted_rate_limit }}<br>
                            <strong>{{ __('Price:') }}</strong> {{ $pppProfile->price ? 'Rp ' . number_format($pppProfile->price, 0, ',', '.') : __('Free') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
