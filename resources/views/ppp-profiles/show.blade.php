@extends('layouts.admin')

@section('main-content')
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">{{ __('PPP Profile Details') }} - {{ $pppProfile->name }}</h1>
        <div>
            <a href="{{ route('ppp-profiles.edit', $pppProfile) }}" class="d-none d-sm-inline-block btn btn-sm btn-warning shadow-sm">
                <i class="fas fa-edit fa-sm text-white-50"></i> {{ __('Edit Profile') }}
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

    <!-- Content Row -->
    <div class="row">
        <div class="col-lg-8">
            <!-- Profile Information -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">{{ __('Profile Information') }}</h6>
                    <span class="badge badge-{{ $pppProfile->is_active ? 'success' : 'danger' }}">
                        {{ $pppProfile->is_active ? __('Active') : __('Inactive') }}
                    </span>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="40%">{{ __('Profile Name') }}</th>
                                    <td>{{ $pppProfile->name }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('Description') }}</th>
                                    <td>{{ $pppProfile->description ?: __('No description') }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('Monthly Price') }}</th>
                                    <td>
                                        @if($pppProfile->price)
                                            <span class="h5 text-success">Rp {{ number_format($pppProfile->price, 0, ',', '.') }}</span>
                                        @else
                                            <span class="text-muted">{{ __('Free') }}</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>{{ __('Speed Limit') }}</th>
                                    <td>
                                        <span class="badge badge-info badge-lg">{{ $pppProfile->formatted_rate_limit }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>{{ __('Session Limit') }}</th>
                                    <td>
                                        <span class="badge badge-{{ $pppProfile->only_one ? 'warning' : 'secondary' }}">
                                            {{ $pppProfile->only_one ? __('One Session Only') : __('Multiple Sessions') }}
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="40%">{{ __('Local Address') }}</th>
                                    <td>{{ $pppProfile->local_address ?: __('Not configured') }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('Remote Address') }}</th>
                                    <td>{{ $pppProfile->remote_address ?: __('Not configured') }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('Parent Queue') }}</th>
                                    <td>{{ $pppProfile->parent_queue ?: __('Not configured') }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('Created') }}</th>
                                    <td>{{ $pppProfile->created_at->format('d M Y H:i') }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('Last Updated') }}</th>
                                    <td>{{ $pppProfile->updated_at->format('d M Y H:i') }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PPP Secrets Using This Profile -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">{{ __('PPP Secrets Using This Profile') }} ({{ $pppProfile->pppSecrets->count() }})</h6>
                    <a href="{{ route('ppp-secrets.create', ['profile_id' => $pppProfile->id]) }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus"></i> {{ __('Add PPP Secret') }}
                    </a>
                </div>
                <div class="card-body">
                    @if($pppProfile->pppSecrets->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>{{ __('Username') }}</th>
                                        <th>{{ __('Customer') }}</th>
                                        <th>{{ __('Status') }}</th>
                                        <th>{{ __('Created') }}</th>
                                        <th>{{ __('Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($pppProfile->pppSecrets as $secret)
                                    <tr>
                                        <td>{{ $secret->username }}</td>
                                        <td>
                                            @if($secret->customer)
                                                <a href="{{ route('customers.show', $secret->customer) }}">{{ $secret->customer->name }}</a>
                                            @else
                                                <span class="text-muted">{{ __('No Customer') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge badge-{{ $secret->is_active ? 'success' : 'danger' }}">
                                                {{ $secret->is_active ? __('Active') : __('Inactive') }}
                                            </span>
                                        </td>
                                        <td>{{ $secret->created_at->format('d M Y') }}</td>
                                        <td>
                                            <a href="{{ route('ppp-secrets.show', $secret) }}" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('ppp-secrets.edit', $secret) }}" class="btn btn-sm btn-warning">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-key fa-3x text-gray-300 mb-3"></i>
                            <p class="text-muted">{{ __('No PPP secrets are using this profile yet.') }}</p>
                            <a href="{{ route('ppp-secrets.create', ['profile_id' => $pppProfile->id]) }}" class="btn btn-primary">
                                <i class="fas fa-plus"></i> {{ __('Add First PPP Secret') }}
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Statistics Card -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">{{ __('Statistics') }}</h6>
                </div>
                <div class="card-body">
                    <div class="row no-gutters align-items-center mb-3">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">{{ __('Total Secrets') }}</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $pppProfile->pppSecrets->count() }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-key fa-2x text-gray-300"></i>
                        </div>
                    </div>

                    <div class="row no-gutters align-items-center mb-3">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">{{ __('Active Secrets') }}</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $pppProfile->activePppSecrets->count() }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>

                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">{{ __('Monthly Revenue') }}</div>
                            <div class="h6 mb-0 font-weight-bold text-gray-800">
                                @if($pppProfile->price)
                                    Rp {{ number_format($pppProfile->price * $pppProfile->activePppSecrets->count(), 0, ',', '.') }}
                                @else
                                    {{ __('Free Profile') }}
                                @endif
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-money-bill-wave fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Profile Configuration Card -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">{{ __('Technical Details') }}</h6>
                </div>
                <div class="card-body">
                    <h6 class="text-primary">{{ __('Speed Configuration:') }}</h6>
                    @if($pppProfile->rate_limit)
                        <p class="text-sm">
                            <strong>{{ __('Download:') }}</strong> {{ $pppProfile->speeds['download'] ?: __('Unlimited') }}<br>
                            <strong>{{ __('Upload:') }}</strong> {{ $pppProfile->speeds['upload'] ?: __('Unlimited') }}
                        </p>
                    @else
                        <p class="text-sm text-muted">{{ __('No speed limit configured') }}</p>
                    @endif

                    <h6 class="text-primary mt-3">{{ __('Network Settings:') }}</h6>
                    <ul class="text-sm text-gray-600">
                        <li><strong>{{ __('Local:') }}</strong> {{ $pppProfile->local_address ?: __('Not set') }}</li>
                        <li><strong>{{ __('Remote:') }}</strong> {{ $pppProfile->remote_address ?: __('Not set') }}</li>
                        <li><strong>{{ __('Queue:') }}</strong> {{ $pppProfile->parent_queue ?: __('Not set') }}</li>
                    </ul>
                </div>
            </div>

            <!-- Quick Actions Card -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">{{ __('Quick Actions') }}</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('ppp-secrets.create', ['profile_id' => $pppProfile->id]) }}" class="btn btn-outline-primary btn-block">
                            <i class="fas fa-key"></i> {{ __('Add PPP Secret') }}
                        </a>
                        
                        <a href="{{ route('ppp-profiles.edit', $pppProfile) }}" class="btn btn-outline-warning btn-block">
                            <i class="fas fa-edit"></i> {{ __('Edit Profile') }}
                        </a>

                        <hr>

                        <form action="{{ route('ppp-profiles.destroy', $pppProfile) }}" method="POST" id="deleteProfileForm">
                            @csrf
                            @method('DELETE')
                            <button type="button" class="btn btn-outline-danger btn-block" id="deleteProfileBtn"
                                    {{ $pppProfile->pppSecrets->count() > 0 ? 'disabled title="Cannot delete profile with active PPP secrets"' : '' }}>
                                <i class="fas fa-trash"></i> {{ __('Delete Profile') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // SweetAlert for Delete Profile
    $('#deleteProfileBtn').click(function(e) {
        e.preventDefault();
        
        // Check if button is disabled
        if ($(this).prop('disabled')) {
            return;
        }
        
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: '🗑️ Delete PPP Profile?',
                html: '<div class="text-left"><p>Are you sure you want to delete this profile?</p><ul class="mb-0"><li><strong>This action cannot be undone</strong></li><li>Profile will be permanently removed</li><li>Make sure no PPP secrets are using this profile</li></ul></div>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fas fa-trash"></i> Yes, Delete Profile!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    $('#deleteProfileForm').submit();
                }
            });
        } else {
            // Fallback
            if (confirm('Are you sure you want to delete this profile? This action cannot be undone.')) {
                $('#deleteProfileForm').submit();
            }
        }
    });
});
</script>
@endpush
