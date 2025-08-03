@extends('layouts.admin')

@section('title', 'MikroTik Settings')

@section('main-content')
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">MikroTik Settings</h1>
            @php
                $activeSetting = \App\Models\MikrotikSetting::getActive();
            @endphp
            @if($activeSetting)
                <div class="mt-2">
                    <span class="badge badge-primary">Active: {{ $activeSetting->name }}</span>
                    <span id="global-connection-status" class="badge badge-secondary ml-2">
                        <i class="fas fa-spinner fa-spin mr-1"></i>Checking...
                    </span>
                </div>
            @endif
        </div>
        <div>
            <a href="{{ route('mikrotik-settings.create') }}" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
                <i class="fas fa-plus fa-sm text-white-50"></i> Add New Setting
            </a>
        </div>
    </div>

    <!-- Alert Messages -->
    <div id="alert-container">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif
    </div>

    <!-- DataTable -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">MikroTik Connection Settings</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Host</th>
                            <th>Port</th>
                            <th>Username</th>
                            <th>SSL</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($settings as $setting)
                        <tr>
                            <td>
                                <strong>{{ $setting->name }}</strong>
                                @if($setting->is_active)
                                    <span class="badge badge-success ml-2">Active</span>
                                @endif
                                @if($setting->description)
                                    <br><small class="text-muted">{{ $setting->description }}</small>
                                @endif
                            </td>
                            <td>{{ $setting->host }}</td>
                            <td>{{ $setting->port }}</td>
                            <td>{{ $setting->username }}</td>
                            <td>
                                @if($setting->use_ssl)
                                    <span class="badge badge-info">SSL</span>
                                @else
                                    <span class="badge badge-secondary">No SSL</span>
                                @endif
                            </td>
                            <td>
                                @if(strpos($setting->host, 'tunnel') !== false)
                                    <span class="badge badge-primary">Tunnel/VPN</span>
                                @else
                                    <span class="badge badge-secondary">Direct</span>
                                @endif
                            </td>
                            <td>
                                <div id="connection-status-{{ $setting->id }}">
                                    @php
                                        $connectionStatus = $setting->getConnectionStatus();
                                    @endphp
                                    @switch($connectionStatus)
                                        @case('connected')
                                            <span class="badge badge-success">
                                                <i class="fas fa-check-circle mr-1"></i>Connected
                                            </span>
                                            @if($setting->last_connected_at)
                                                <br><small class="text-muted">
                                                    {{ $setting->last_connected_at->diffForHumans() }}
                                                </small>
                                            @endif
                                            @break
                                        @case('inactive')
                                            <span class="badge badge-secondary">
                                                <i class="fas fa-pause-circle mr-1"></i>Inactive
                                            </span>
                                            @break
                                        @default
                                            <span class="badge badge-light">
                                                <i class="fas fa-question-circle mr-1"></i>Unknown
                                            </span>
                                            <br><small class="text-muted">Click test to check</small>
                                    @endswitch
                                </div>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <!-- Test Connection Button -->
                                    <button class="btn btn-sm btn-outline-primary test-connection" 
                                            data-setting-id="{{ $setting->id }}"
                                            data-url="{{ route('mikrotik-settings.test-ajax', $setting) }}"
                                            title="Test connection to {{ $setting->host }}">
                                        <i class="fas fa-wifi"></i>
                                    </button>
                                    
                                    <!-- Diagnostics Button (only for active setting) -->
                                    @if($setting->is_active)
                                        <button class="btn btn-sm btn-outline-info diagnostics-btn" 
                                                data-setting-id="{{ $setting->id }}"
                                                data-url="{{ route('mikrotik-settings.diagnostics', $setting) }}"
                                                title="Run detailed diagnostics">
                                            <i class="fas fa-stethoscope"></i>
                                        </button>
                                    @endif
                                    
                                    <!-- View Button -->
                                    <a href="{{ route('mikrotik-settings.show', $setting) }}" 
                                       class="btn btn-sm btn-info" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    
                                    <!-- Edit Button -->
                                    <a href="{{ route('mikrotik-settings.edit', $setting) }}" 
                                       class="btn btn-sm btn-warning" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    
                                    <!-- Activate Button (only if not active) -->
                                    @if(!$setting->is_active)
                                        <form action="{{ route('mikrotik-settings.set-active', $setting) }}" 
                                              method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success" 
                                                    title="Activate"
                                                    onclick="return confirm('Activate this setting?')">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        </form>
                                    @endif
                                    
                                    <!-- Delete Button (only if not active) -->
                                    @if(!$setting->is_active)
                                        <form action="{{ route('mikrotik-settings.destroy', $setting) }}" 
                                              method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" 
                                                    title="Delete"
                                                    onclick="return confirm('Are you sure you want to delete this setting?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-center">
                {{ $settings->links() }}
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    let isTestingConnection = false; // Global flag to prevent multiple simultaneous tests
    
    // Auto-test connection for active setting on page load
    autoTestActiveConnection();
    
    // Auto-refresh connection status every 5 minutes for active setting
    setInterval(function() {
        const activeRow = $('tr').has('.badge-success:contains("Active")');
        if (activeRow.length > 0) {
            const testButton = activeRow.find('.test-connection');
            if (testButton.length > 0 && !isTestingConnection) {
                testConnectionInternal(testButton, false); // Silent auto-refresh
            }
        }
    }, 300000); // 5 minutes
    
    // Test connection functionality
    $('.test-connection').click(function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const button = $(this);
        
        // Clear any existing alerts first
        clearAlerts();
        
        // Use internal function with alert display
        testConnectionInternal(button, true);
    });

    // Diagnostics functionality
    $(document).on('click', '.diagnostics-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const button = $(this);
        const url = button.data('url');
        
        // Prevent double-clicking
        if (button.prop('disabled')) {
            return false;
        }
        
        button.prop('disabled', true);
        button.html('<i class="fas fa-spinner fa-spin"></i> Running...');
        
        $.ajax({
            url: url,
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            timeout: 30000, // 30 seconds timeout for diagnostics
            success: function(response) {
                if (response.success) {
                    const diagnostics = response.diagnostics;
                    let diagnosticsHtml = '<div class="row">';
                    
                    // Network Test
                    diagnosticsHtml += `<div class="col-md-6 mb-2">
                        <strong>🌐 Network:</strong> 
                        <span class="${diagnostics.network.includes('OK') ? 'text-success' : 'text-danger'}">
                            ${diagnostics.network}
                        </span>
                    </div>`;
                    
                    // DNS Test
                    diagnosticsHtml += `<div class="col-md-6 mb-2">
                        <strong>🔍 DNS:</strong> 
                        <span class="${diagnostics.dns.includes('OK') ? 'text-success' : (diagnostics.dns.includes('N/A') ? 'text-muted' : 'text-danger')}">
                            ${diagnostics.dns}
                        </span>
                    </div>`;
                    
                    // Port Test
                    diagnosticsHtml += `<div class="col-md-6 mb-2">
                        <strong>🔌 Port:</strong> 
                        <span class="${diagnostics.port.includes('OK') ? 'text-success' : 'text-danger'}">
                            ${diagnostics.port}
                        </span>
                    </div>`;
                    
                    // API Test
                    diagnosticsHtml += `<div class="col-md-6 mb-2">
                        <strong>⚡ API:</strong> 
                        <span class="${diagnostics.api.includes('OK') ? 'text-success' : 'text-danger'}">
                            ${diagnostics.api}
                        </span>
                    </div>`;
                    
                    diagnosticsHtml += '</div>';
                    
                    showAlert('info', `<strong>🔧 Connection Diagnostics</strong><br><br>${diagnosticsHtml}`);
                } else {
                    showAlert('error', 'Diagnostics failed: ' + response.message);
                }
            },
            error: function(xhr) {
                let message = 'Diagnostics failed!';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                showAlert('error', message);
            },
            complete: function() {
                button.prop('disabled', false);
                button.html('<i class="fas fa-stethoscope"></i> Diagnostics');
            }
        });
    });
    
    function clearAlerts() {
        // Remove any existing dynamic alerts
        $('.alert-dynamic').remove();
    }
    
    function showAlert(type, message, isAutoTest = true) {
        // Clear any existing dynamic alerts first
        clearAlerts();
        
        let alertClass = 'alert-info';
        if (type === 'success') alertClass = 'alert-success';
        else if (type === 'error') alertClass = 'alert-danger';
        else if (type === 'warning') alertClass = 'alert-warning';
        
        let extraClass = isAutoTest === false ? 'alert-auto-test' : '';
        
        const alertHtml = `
            <div class="alert ${alertClass} alert-dismissible fade show alert-dynamic ${extraClass}" role="alert">
                ${message}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        `;
        $('#alert-container').append(alertHtml);
        
        // Auto-hide after different times based on type
        const timeout = type === 'info' ? 10000 : (type === 'success' ? 8000 : 12000);
        setTimeout(function() {
            $('.alert-dynamic').alert('close');
        }, timeout);
    }
    
    function autoTestActiveConnection() {
        // Find the active setting (the one with "Active" badge)
        const activeRow = $('tr').has('.badge-success:contains("Active")');
        if (activeRow.length > 0) {
            const testButton = activeRow.find('.test-connection');
            if (testButton.length > 0) {
                // Show a subtle loading message
                showAlert('info', '<i class="fas fa-spinner fa-spin mr-2"></i>Auto-testing connection to active MikroTik setting...', false);
                
                // Update global status to testing
                $('#global-connection-status').html('<i class="fas fa-spinner fa-spin mr-1"></i>Testing...');
                
                // Delay the auto-test by 1 second to ensure page is fully loaded
                setTimeout(function() {
                    testConnectionInternal(testButton, false); // Don't show alert for auto-test
                }, 1000);
            }
        } else {
            // No active setting, update global status
            $('#global-connection-status').html('<i class="fas fa-pause-circle mr-1"></i>No Active Setting');
            $('#global-connection-status').removeClass('badge-success badge-danger').addClass('badge-secondary');
        }
    }
    
    function updateGlobalConnectionStatus(success, isActive = true) {
        if (!isActive) {
            $('#global-connection-status').html('<i class="fas fa-pause-circle mr-1"></i>Inactive');
            $('#global-connection-status').removeClass('badge-success badge-danger').addClass('badge-secondary');
        } else if (success) {
            $('#global-connection-status').html('<i class="fas fa-check-circle mr-1"></i>Connected');
            $('#global-connection-status').removeClass('badge-secondary badge-danger').addClass('badge-success');
        } else {
            $('#global-connection-status').html('<i class="fas fa-times-circle mr-1"></i>Failed');
            $('#global-connection-status').removeClass('badge-secondary badge-success').addClass('badge-danger');
        }
    }
    
    function testConnectionInternal(button, showAlertOnSuccess = true) {
        // Prevent multiple simultaneous tests
        if (isTestingConnection) {
            return false;
        }
        
        const settingId = button.data('setting-id');
        const statusElement = $('#connection-status-' + settingId);
        const url = button.data('url');
        
        // Prevent double-clicking
        if (button.prop('disabled')) {
            return false;
        }
        
        isTestingConnection = true;
        
        // Show loading state (but don't change button text for auto-test)
        if (showAlertOnSuccess) {
            button.prop('disabled', true);
            button.html('<i class="fas fa-spinner fa-spin"></i> Testing...');
        }
        statusElement.html('<span class="badge badge-warning"><i class="fas fa-spinner fa-spin mr-1"></i>Testing...</span>');
        
        // Make AJAX request
        $.ajax({
            url: url,
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            timeout: 60000, // 60 seconds timeout for connection test
            success: function(response) {
                if (response.success) {
                    // Update status with success badge
                    let statusHtml = '<span class="badge badge-success"><i class="fas fa-check-circle mr-1"></i>Connected</span>';
                    
                    // Add connection type if available
                    if (response.details && response.details.connection_type) {
                        statusHtml += ` <small class="text-muted">(${response.details.connection_type})</small>`;
                    }
                    
                    statusElement.html(statusHtml);
                    
                    // Update global status if this is the active setting
                    const isActiveSetting = statusElement.closest('tr').find('.badge-success:contains("Active")').length > 0;
                    if (isActiveSetting) {
                        updateGlobalConnectionStatus(true, true);
                    }
                    
                    if (showAlertOnSuccess) {
                        let alertType = 'success';
                        let message = response.message;
                        
                        // If SSL was recommended, show it as info instead of success
                        if (response.suggestion === 'ssl_recommended') {
                            alertType = 'info';
                            message += `<br><br><strong>💡 Recommendation:</strong> Enable SSL and use port ${response.details.recommended_port || 8729} for better stability.`;
                        }
                        
                        showAlert(alertType, message);
                    }
                } else {
                    // Show failed status with retry option
                    statusElement.html('<span class="badge badge-danger"><i class="fas fa-times-circle mr-1"></i>Failed</span>');
                    
                    // Update global status if this is the active setting
                    const isActiveSetting = statusElement.closest('tr').find('.badge-success:contains("Active")').length > 0;
                    if (isActiveSetting) {
                        updateGlobalConnectionStatus(false, true);
                    }
                    
                    if (showAlertOnSuccess) {
                        let message = response.message || 'Connection test failed!';
                        
                        // Add retry button to error message
                        message += '<br><br><button class="btn btn-sm btn-outline-primary mt-2 retry-connection" data-setting-id="' + settingId + '" data-url="' + url + '"><i class="fas fa-redo mr-1"></i>Try Again</button>';
                        
                        showAlert('error', message);
                    }
                }
            },
            error: function(xhr) {
                statusElement.html('<span class="badge badge-danger"><i class="fas fa-exclamation-triangle mr-1"></i>Error</span>');
                
                // Update global status if this is the active setting
                const isActiveSetting = statusElement.closest('tr').find('.badge-success:contains("Active")').length > 0;
                if (isActiveSetting) {
                    updateGlobalConnectionStatus(false, true);
                }
                
                if (showAlertOnSuccess) {
                    let message = 'Connection test failed!';
                    
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    } else if (xhr.status === 500) {
                        message = '❌ Server error occurred during connection test.<br><br>💡 Troubleshooting:<br>• Check server logs for details<br>• Verify MikroTik API service is running<br>• Check network connectivity';
                    } else if (xhr.status === 404) {
                        message = '❌ Connection test endpoint not found.<br><br>💡 Please contact system administrator.';
                    } else if (xhr.status === 0) {
                        message = '❌ Network error or request timeout.<br><br>💡 Troubleshooting:<br>• Check internet connection<br>• Verify tunnel/VPN is stable<br>• Try again in a few moments';
                    } else if (xhr.status === 408 || xhr.statusText === 'timeout') {
                        message = '❌ Connection test timed out.<br><br>💡 This usually indicates:<br>• Very slow network connection<br>• MikroTik router is overloaded<br>• Tunnel/VPN connection is unstable<br>• Try connecting during off-peak hours';
                    }
                    
                    showAlert('error', message);
                }
            },
            complete: function() {
                if (showAlertOnSuccess) {
                    button.prop('disabled', false);
                    button.html('<i class="fas fa-wifi"></i> Test');
                }
                isTestingConnection = false; // Reset flag
            }
        });
    }

    // Retry connection functionality (for dynamic buttons in alerts)
    $(document).on('click', '.retry-connection', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const settingId = $(this).data('setting-id');
        const url = $(this).data('url');
        const originalButton = $(`.test-connection[data-setting-id="${settingId}"]`);
        
        // Clear alerts and retry
        clearAlerts();
        testConnectionInternal(originalButton, true);
    });
});
</script>
@endpush
@endsection
