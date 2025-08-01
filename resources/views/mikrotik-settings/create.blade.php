@extends('layouts.admin')

@section('title', 'Add MikroTik Setting')

@section('main-content')
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Add MikroTik Setting</h1>
        <a href="{{ route('mikrotik-settings.index') }}" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm">
            <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to Settings
        </a>
    </div>

    <!-- Form Card -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">MikroTik Connection Information</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('mikrotik-settings.store') }}" method="POST">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name">Setting Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                           id="name" name="name" value="{{ old('name') }}" 
                                           placeholder="e.g., Main MikroTik Router" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="host">Host/IP Address <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('host') is-invalid @enderror" 
                                           id="host" name="host" value="{{ old('host') }}" 
                                           placeholder="e.g., 192.168.1.1 or vpn.mikrotik.com" required>
                                    @error('host')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">
                                        You can use IP address, domain name, or VPN address
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="port">API Port <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control @error('port') is-invalid @enderror" 
                                           id="port" name="port" value="{{ old('port', 8728) }}" 
                                           min="1" max="65535" required>
                                    @error('port')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">
                                        Default: 8728 (API), 8729 (API-SSL)
                                    </small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="timeout">Timeout (seconds) <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control @error('timeout') is-invalid @enderror" 
                                           id="timeout" name="timeout" value="{{ old('timeout', 30) }}" 
                                           min="1" max="300" required>
                                    @error('timeout')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="username">API Username <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('username') is-invalid @enderror" 
                                           id="username" name="username" value="{{ old('username') }}" 
                                           placeholder="e.g., admin" required>
                                    @error('username')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="password">API Password <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control @error('password') is-invalid @enderror" 
                                           id="password" name="password" required>
                                    @error('password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" name="description" rows="3" 
                                      placeholder="Optional description for this setting">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <hr>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="use_ssl" 
                                               name="use_ssl" value="1" {{ old('use_ssl') ? 'checked' : '' }}>
                                        <label class="form-check-label" for="use_ssl">
                                            Use SSL Connection
                                        </label>
                                    </div>
                                    <small class="form-text text-muted">
                                        Enable if using API-SSL port (usually 8729)
                                    </small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="is_active" 
                                               name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_active">
                                            Set as Active Connection
                                        </label>
                                    </div>
                                    <small class="form-text text-muted">
                                        This will be used as the primary MikroTik connection
                                    </small>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Setting
                            </button>
                            <button type="button" class="btn btn-secondary ml-2" id="test-connection">
                                <i class="fas fa-wifi"></i> Test Connection
                            </button>
                            <a href="{{ route('mikrotik-settings.index') }}" class="btn btn-secondary ml-2">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Connection Tips</h6>
                </div>
                <div class="card-body">
                    <h6 class="font-weight-bold">Remote Access Options:</h6>
                    <ul class="small">
                        <li><strong>VPN Connection:</strong> vpn.mikrotik.com:8728</li>
                        <li><strong>Public IP:</strong> 203.0.113.1:8728</li>
                        <li><strong>DDNS:</strong> router.dyndns.org:8728</li>
                        <li><strong>Local Network:</strong> 192.168.1.1:8728</li>
                    </ul>
                    
                    <hr>
                    
                    <h6 class="font-weight-bold">API Setup on MikroTik:</h6>
                    <ol class="small">
                        <li>Go to IP → Services</li>
                        <li>Enable "api" service</li>
                        <li>Set API port (default: 8728)</li>
                        <li>Create API user with proper permissions</li>
                        <li>Test connection from this form</li>
                    </ol>
                    
                    <hr>
                    
                    <h6 class="font-weight-bold">Required Permissions:</h6>
                    <ul class="small">
                        <li>API access</li>
                        <li>PPP read/write</li>
                        <li>System read</li>
                        <li>User read/write</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    $('#test-connection').click(function() {
        const button = $(this);
        const form = button.closest('form');
        
        // Get form data
        const data = {
            name: $('#name').val(),
            host: $('#host').val(),
            port: $('#port').val(),
            username: $('#username').val(),
            password: $('#password').val(),
            timeout: $('#timeout').val(),
            use_ssl: $('#use_ssl').is(':checked'),
            _token: $('meta[name="csrf-token"]').attr('content')
        };
        
        // Validate required fields
        if (!data.name || !data.host || !data.port || !data.username || !data.password) {
            showAlert('error', 'Please fill in all required fields before testing connection.');
            return;
        }
        
        // Show loading state
        button.prop('disabled', true);
        button.html('<i class="fas fa-spinner fa-spin"></i> Testing Connection...');
        
        // Make AJAX request
        $.ajax({
            url: '{{ route("mikrotik-settings.store") }}',
            method: 'POST',
            data: { ...data, test_only: true },
            success: function(response) {
                showAlert('success', 'Connection test successful! You can now save the setting.');
            },
            error: function(xhr) {
                const message = xhr.responseJSON?.message || 'Connection test failed! Please check your settings.';
                showAlert('error', message);
            },
            complete: function() {
                button.prop('disabled', false);
                button.html('<i class="fas fa-wifi"></i> Test Connection');
            }
        });
    });
    
    function showAlert(type, message) {
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        const alertHtml = `
            <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        `;
        $('.container-fluid').prepend(alertHtml);
        
        // Auto-hide after 5 seconds
        setTimeout(function() {
            $('.alert').alert('close');
        }, 5000);
    }
});
</script>
@endpush
@endsection
