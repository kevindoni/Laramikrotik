@extends('layouts.admin')

@section('main-content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">{{ __('Edit MikroTik Setting') }}</h1>
        <a href="{{ route('mikrotik-settings.index') }}" class="btn btn-sm btn-primary shadow-sm">
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

    {{-- Flash Messages --}}
    <div id="alert-container">
        @if (session('success'))
            <div class="alert alert-success border-left-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger border-left-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">{{ __('Connection Settings') }}</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('mikrotik-settings.update', $mikrotikSetting) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="form-group row">
                            <label for="name" class="col-sm-3 col-form-label">{{ __('Setting Name') }} <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                       id="name" name="name" value="{{ old('name', $mikrotikSetting->name) }}" required>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="host" class="col-sm-3 col-form-label">{{ __('Host/IP Address') }} <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control @error('host') is-invalid @enderror" 
                                       id="host" name="host" value="{{ old('host', $mikrotikSetting->host) }}" 
                                       placeholder="192.168.1.1" required>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="port" class="col-sm-3 col-form-label">{{ __('Port') }}</label>
                            <div class="col-sm-9">
                                <input type="number" class="form-control" id="port" name="port" 
                                       value="{{ old('port', $mikrotikSetting->port) }}" min="1" max="65535">
                                <small class="form-text text-muted">{{ __('Default: 8728 (API), 8729 (API-SSL)') }}</small>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="username" class="col-sm-3 col-form-label">{{ __('Username') }} <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control @error('username') is-invalid @enderror" 
                                       id="username" name="username" value="{{ old('username', $mikrotikSetting->username) }}" 
                                       autocomplete="off" required>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="password" class="col-sm-3 col-form-label">{{ __('Password') }}</label>
                            <div class="col-sm-9">
                                <input type="password" class="form-control @error('password') is-invalid @enderror" 
                                       id="password" name="password" autocomplete="new-password">
                                <small class="form-text text-muted">{{ __('Leave blank to keep current password') }}</small>
                            </div>
                        </div>

                        <div class="form-group row">
                            <div class="col-sm-9 offset-sm-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="use_ssl" name="use_ssl" value="1" 
                                           {{ old('use_ssl', $mikrotikSetting->use_ssl) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="use_ssl">
                                        {{ __('Use SSL Connection') }}
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="form-group row">
                            <div class="col-sm-9 offset-sm-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" 
                                           {{ old('is_active', $mikrotikSetting->is_active) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_active">
                                        {{ __('Set as Active Connection') }}
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="description" class="col-sm-3 col-form-label">{{ __('Description') }}</label>
                            <div class="col-sm-9">
                                <textarea class="form-control" id="description" name="description" rows="3">{{ old('description', $mikrotikSetting->description) }}</textarea>
                            </div>
                        </div>

                        <hr>

                        <div class="form-group row">
                            <div class="col-sm-9 offset-sm-3">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save fa-sm text-white-50"></i> {{ __('Update Setting') }}
                                </button>
                                <button type="button" class="btn btn-info" onclick="testConnection()">
                                    <i class="fas fa-plug fa-sm text-white-50"></i> {{ __('Test Connection') }}
                                </button>
                                <button type="button" class="btn btn-warning" onclick="runDiagnostics()">
                                    <i class="fas fa-stethoscope fa-sm text-white-50"></i> {{ __('Diagnostics') }}
                                </button>
                                <a href="{{ route('mikrotik-settings.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <!-- Information Panel -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-info">{{ __('Information') }}</h6>
                </div>
                <div class="card-body">
                    <!-- Current Status -->
                    <div class="mb-4">
                        <h6 class="text-primary">{{ __('Current Status') }}</h6>
                        <div class="d-flex align-items-center mb-2">
                            <strong class="mr-2">{{ __('Status:') }}</strong>
                            @if($mikrotikSetting->is_active)
                                <span class="badge badge-success">{{ __('Active') }}</span>
                            @else
                                <span class="badge badge-secondary">{{ __('Inactive') }}</span>
                            @endif
                        </div>
                        <div class="d-flex align-items-center mb-2">
                            <strong class="mr-2">{{ __('Connection:') }}</strong>
                            @php
                                $connectionStatus = $mikrotikSetting->getConnectionStatus();
                            @endphp
                            @switch($connectionStatus)
                                @case('connected')
                                    <span class="badge badge-success">{{ __('Connected') }}</span>
                                    @break
                                @case('inactive')
                                    <span class="badge badge-secondary">{{ __('Inactive') }}</span>
                                    @break
                                @default
                                    <span class="badge badge-secondary">{{ __('Unknown') }}</span>
                            @endswitch
                        </div>
                        @if($mikrotikSetting->last_connected_at)
                            <small class="text-muted">
                                {{ __('Last connected:') }} {{ $mikrotikSetting->last_connected_at->diffForHumans() }}
                            </small>
                        @endif
                    </div>

                    <!-- Current Settings Info -->
                    <div class="mb-4">
                        <h6 class="text-primary">{{ __('Current Settings') }}</h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>{{ __('Host:') }}</strong></td>
                                    <td>{{ $mikrotikSetting->host }}</td>
                                </tr>
                                <tr>
                                    <td><strong>{{ __('Port:') }}</strong></td>
                                    <td>{{ $mikrotikSetting->port ?: '8728' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>{{ __('Username:') }}</strong></td>
                                    <td>{{ $mikrotikSetting->username }}</td>
                                </tr>
                                <tr>
                                    <td><strong>{{ __('SSL:') }}</strong></td>
                                    <td>
                                        @if($mikrotikSetting->use_ssl)
                                            <span class="badge badge-info">{{ __('Enabled') }}</span>
                                        @else
                                            <span class="badge badge-secondary">{{ __('Disabled') }}</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- Help Information -->
                    <div class="mb-4">
                        <h6 class="text-primary">{{ __('Quick Help') }}</h6>
                        <div class="alert alert-info py-2">
                            <small>
                                <i class="fas fa-info-circle mr-1"></i>
                                {{ __('Test the connection before saving to ensure your settings are correct.') }}
                            </small>
                        </div>
                        <div class="alert alert-warning py-2">
                            <small>
                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                {{ __('Leave password blank to keep the current password unchanged.') }}
                            </small>
                        </div>
                    </div>

                    <!-- Default Ports Info -->
                    <div class="mb-4">
                        <h6 class="text-primary">{{ __('Default Ports') }}</h6>
                        <div class="row text-center">
                            <div class="col-6">
                                <div class="border rounded p-2">
                                    <div class="text-primary font-weight-bold">8728</div>
                                    <small class="text-muted">{{ __('API') }}</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="border rounded p-2">
                                    <div class="text-info font-weight-bold">8729</div>
                                    <small class="text-muted">{{ __('API-SSL') }}</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Timestamps -->
                    <div class="border-top pt-3">
                        <small class="text-muted">
                            <div><strong>{{ __('Created:') }}</strong> {{ $mikrotikSetting->created_at->format('M d, Y H:i') }}</div>
                            <div><strong>{{ __('Updated:') }}</strong> {{ $mikrotikSetting->updated_at->format('M d, Y H:i') }}</div>
                        </small>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-success">{{ __('Quick Actions') }}</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('mikrotik-settings.show', $mikrotikSetting) }}" class="btn btn-outline-info btn-sm">
                            <i class="fas fa-eye"></i> {{ __('View Details') }}
                        </a>
                        
                        @if(!$mikrotikSetting->is_active)
                        <form action="{{ route('mikrotik-settings.set-active', $mikrotikSetting) }}" method="POST" class="mb-0">
                            @csrf
                            <button type="submit" class="btn btn-outline-success btn-sm w-100" 
                                    onclick="return confirm('{{ __('Set this as active connection?') }}')">
                                <i class="fas fa-check"></i> {{ __('Set as Active') }}
                            </button>
                        </form>
                        @endif
                        
                        <a href="{{ route('mikrotik-settings.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-list"></i> {{ __('View All Settings') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Test connection functionality
    window.testConnection = function() {
        // Clear any existing alerts first
        clearAlerts();
        
        // Get form data
        const formData = new FormData();
        formData.append('host', document.getElementById('host').value);
        formData.append('port', document.getElementById('port').value);
        formData.append('username', document.getElementById('username').value);
        formData.append('password', document.getElementById('password').value);
        formData.append('use_ssl', document.getElementById('use_ssl').checked ? 1 : 0);
        formData.append('_token', document.querySelector('input[name="_token"]').value);

        // Show loading state
        const btn = event.target;
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin fa-sm text-white-50"></i> Testing...';

        // Make AJAX request
        fetch('{{ route("mikrotik-settings.test-ajax", $mikrotikSetting) }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('success', '✅ ' + data.message);
            } else {
                showAlert('error', '❌ ' + data.message);
            }
        })
        .catch(error => {
            showAlert('error', '❌ Connection test failed! Error: ' + error.message);
        })
        .finally(() => {
            // Restore button state
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
    };

    // Run diagnostics functionality
    window.runDiagnostics = function() {
        // Clear any existing alerts first
        clearAlerts();

        // Show loading state
        const btn = event.target;
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin fa-sm text-white-50"></i> Running...';

        // Make AJAX request
        fetch('{{ route("mikrotik-settings.diagnostics", $mikrotikSetting) }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let message = '🔍 Diagnostics Results:\n\n';
                for (const [test, result] of Object.entries(data.diagnostics)) {
                    const icon = result.startsWith('OK') ? '✅' : result.startsWith('FAILED') ? '❌' : 'ℹ️';
                    message += `${icon} ${test.toUpperCase()}: ${result}\n`;
                }
                showAlert('info', message.replace(/\n/g, '<br>'));
            } else {
                showAlert('error', '❌ ' + data.message);
            }
        })
        .catch(error => {
            showAlert('error', '❌ Diagnostics failed! Error: ' + error.message);
        })
        .finally(() => {
            // Restore button state
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
    };
    
    function clearAlerts() {
        // Remove any existing dynamic alerts
        $('.alert-dynamic').remove();
    }
    
    function showAlert(type, message) {
        // Clear any existing dynamic alerts first
        clearAlerts();
        
        const alertClass = type === 'success' ? 'alert-success border-left-success' : 
                          type === 'info' ? 'alert-info border-left-info' :
                          'alert-danger border-left-danger';
        const iconClass = type === 'success' ? 'fas fa-check-circle' : 
                         type === 'info' ? 'fas fa-info-circle' :
                         'fas fa-exclamation-triangle';
        
        const alertHtml = `
            <div class="alert ${alertClass} alert-dismissible fade show alert-dynamic" role="alert">
                <i class="${iconClass} mr-2"></i>${message}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        `;
        $('#alert-container').append(alertHtml);
        
        // Auto-hide after 12 seconds for info (diagnostics), 8 seconds for success, 10 seconds for error
        const timeout = type === 'info' ? 12000 : (type === 'success' ? 8000 : 10000);
        setTimeout(function() {
            $('.alert-dynamic').alert('close');
        }, timeout);
        
        // Scroll to top to show the alert
        $('html, body').animate({
            scrollTop: 0
        }, 300);
    }
});
</script>
@endpush
