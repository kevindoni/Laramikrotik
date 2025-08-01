@extends('layouts.admin')

@section('main-content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">{{ __('MikroTik Setting Details') }}</h1>
        <div>
            <a href="{{ route('mikrotik-settings.edit', $mikrotikSetting) }}" class="btn btn-sm btn-warning shadow-sm">
                <i class="fas fa-edit fa-sm text-white-50"></i> {{ __('Edit') }}
            </a>
            <a href="{{ route('mikrotik-settings.index') }}" class="btn btn-sm btn-primary shadow-sm">
                <i class="fas fa-arrow-left fa-sm text-white-50"></i> {{ __('Back') }}
            </a>
        </div>
    </div>

    {{-- Flash Messages --}}
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

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">{{ __('Connection Information') }}</h6>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <td class="font-weight-bold" style="width: 200px;">{{ __('Setting ID') }}</td>
                            <td>: {{ $mikrotikSetting->id }}</td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold">{{ __('Setting Name') }}</td>
                            <td>: {{ $mikrotikSetting->name }}</td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold">{{ __('Host/IP Address') }}</td>
                            <td>: <code>{{ $mikrotikSetting->host }}</code></td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold">{{ __('Port') }}</td>
                            <td>: {{ $mikrotikSetting->port ?: 'Default (8728/8729)' }}</td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold">{{ __('Username') }}</td>
                            <td>: <code>{{ $mikrotikSetting->username }}</code></td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold">{{ __('Password') }}</td>
                            <td>: <code>{{ str_repeat('*', strlen($mikrotikSetting->password ?? '')) }}</code></td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold">{{ __('SSL Connection') }}</td>
                            <td>: 
                                @if($mikrotikSetting->use_ssl)
                                    <span class="badge badge-success">{{ __('Enabled') }}</span>
                                @else
                                    <span class="badge badge-secondary">{{ __('Disabled') }}</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold">{{ __('Connection Type') }}</td>
                            <td>: 
                                @php
                                    // Detect connection type based on available data
                                    $connectionType = 'api'; // Default is API
                                    $connectionBadge = 'badge-info';
                                    $connectionLabel = 'API';
                                    
                                    // Check if it's a tunnel/VPN connection based on hostname
                                    if (strpos($mikrotikSetting->host, 'tunnel') !== false) {
                                        $connectionType = 'tunnel';
                                        $connectionBadge = 'badge-primary';
                                        $connectionLabel = 'Tunnel/VPN';
                                    } elseif (strpos($mikrotikSetting->host, 'vpn') !== false) {
                                        $connectionType = 'vpn';
                                        $connectionBadge = 'badge-success';
                                        $connectionLabel = 'VPN';
                                    } elseif (filter_var($mikrotikSetting->host, FILTER_VALIDATE_IP)) {
                                        // Direct IP connection
                                        $connectionType = 'direct';
                                        $connectionBadge = 'badge-secondary';
                                        $connectionLabel = 'Direct IP';
                                    }
                                    
                                    // Adjust label if SSL is enabled
                                    if ($mikrotikSetting->use_ssl) {
                                        $connectionLabel .= ' (SSL)';
                                        $connectionBadge = 'badge-success';
                                    }
                                @endphp
                                <span class="badge {{ $connectionBadge }}">{{ __($connectionLabel) }}</span>
                                
                                @if($mikrotikSetting->use_ssl)
                                    <span class="badge badge-info ml-1">
                                        <i class="fas fa-lock mr-1"></i>{{ __('Encrypted') }}
                                    </span>
                                @endif
                                
                                @if(strpos($mikrotikSetting->host, 'tunnel') !== false || strpos($mikrotikSetting->host, 'vpn') !== false)
                                    <br><small class="text-muted">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        {{ __('Remote connection via tunnel/VPN') }}
                                    </small>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold">{{ __('Status') }}</td>
                            <td>: 
                                @if($mikrotikSetting->is_active)
                                    <span class="badge badge-success">{{ __('Active') }}</span>
                                @else
                                    <span class="badge badge-secondary">{{ __('Inactive') }}</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold">{{ __('Description') }}</td>
                            <td>: {{ $mikrotikSetting->description ?: '-' }}</td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold">{{ __('Last Connected') }}</td>
                            <td>: 
                                @if($mikrotikSetting->last_connected_at)
                                    {{ $mikrotikSetting->last_connected_at->format('d M Y H:i:s') }}
                                    <small class="text-muted">({{ $mikrotikSetting->last_connected_at->diffForHumans() }})</small>
                                @else
                                    <span class="text-muted">{{ __('Never') }}</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold">{{ __('Connection Status') }}</td>
                            <td>: 
                                @php
                                    $connectionStatus = $mikrotikSetting->getConnectionStatus();
                                @endphp
                                @switch($connectionStatus)
                                    @case('connected')
                                        <span class="badge badge-success">
                                            <i class="fas fa-check-circle mr-1"></i>{{ __('Recently Connected') }}
                                        </span>
                                        @break
                                    @case('inactive')
                                        <span class="badge badge-secondary">
                                            <i class="fas fa-pause-circle mr-1"></i>{{ __('Inactive') }}
                                        </span>
                                        @break
                                    @default
                                        <span class="badge badge-warning">
                                            <i class="fas fa-question-circle mr-1"></i>{{ __('Unknown') }}
                                        </span>
                                @endswitch
                                
                                @if($connectionStatus === 'connected' && $mikrotikSetting->last_connected_at)
                                    <br><small class="text-success">
                                        <i class="fas fa-clock mr-1"></i>
                                        {{ __('Last successful connection') }}: {{ $mikrotikSetting->last_connected_at->diffForHumans() }}
                                    </small>
                                @elseif($connectionStatus === 'inactive')
                                    <br><small class="text-muted">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        {{ __('This setting is not currently active') }}
                                    </small>
                                @else
                                    <br><small class="text-muted">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        {{ __('Click "Test Connection" to check status') }}
                                    </small>
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">{{ __('Connection Test') }}</h6>
                </div>
                <div class="card-body">
                    <div class="text-center">
                        <button class="btn btn-info btn-block" onclick="testConnection()" id="testBtn">
                            <i class="fas fa-wifi"></i> {{ __('Test Connection') }}
                        </button>
                        <small class="text-muted mt-2 d-block">{{ __('Test connectivity to this MikroTik router') }}</small>
                        
                        @if($mikrotikSetting->is_active)
                            <div class="mt-2">
                                <button class="btn btn-outline-info btn-sm" onclick="runDiagnostics()" id="diagnosticsBtn">
                                    <i class="fas fa-stethoscope"></i> {{ __('Run Diagnostics') }}
                                </button>
                            </div>
                        @endif
                    </div>
                    
                    <div id="connectionResult" class="mt-3" style="display: none;">
                        <div class="alert" role="alert">
                            <div id="connectionMessage"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">{{ __('Quick Stats') }}</h6>
                </div>
                <div class="card-body">
                    <div class="row no-gutters">
                        <div class="col-12 mb-3">
                            <div class="text-center">
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    @if($mikrotikSetting->is_active)
                                        {{ \App\Models\PppSecret::count() }}
                                    @else
                                        -
                                    @endif
                                </div>
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                    {{ __('PPP Secrets') }}
                                </div>
                            </div>
                        </div>
                        <div class="col-12 mb-3">
                            <div class="text-center">
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    @if($mikrotikSetting->is_active)
                                        {{ \App\Models\PppProfile::count() }}
                                    @else
                                        -
                                    @endif
                                </div>
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                    {{ __('PPP Profiles') }}
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="text-center">
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    @if($mikrotikSetting->is_active)
                                        {{ \App\Models\UsageLog::whereDate('created_at', today())->count() }}
                                    @else
                                        -
                                    @endif
                                </div>
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                    {{ __('Today\'s Sessions') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">{{ __('Actions') }}</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('mikrotik-settings.edit', $mikrotikSetting) }}" class="btn btn-warning btn-sm w-100">
                            <i class="fas fa-edit"></i> {{ __('Edit Setting') }}
                        </a>
                        
                        @if(!$mikrotikSetting->is_active)
                            <form action="{{ route('mikrotik-settings.set-active', $mikrotikSetting) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-success btn-sm w-100"
                                        onclick="return confirm('{{ __('Set this setting as active? This will deactivate the current active setting.') }}')">
                                    <i class="fas fa-check-circle"></i> {{ __('Set as Active') }}
                                </button>
                            </form>
                        @else
                            <div class="alert alert-info alert-sm mb-2">
                                <i class="fas fa-info-circle mr-1"></i>
                                {{ __('This is the currently active setting') }}
                            </div>
                        @endif
                        
                        <form action="{{ route('mikrotik-settings.destroy', $mikrotikSetting) }}" method="POST" 
                              onsubmit="return confirm('{{ __('Are you sure you want to delete this MikroTik setting?') }}')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm w-100" 
                                    {{ $mikrotikSetting->is_active ? 'disabled title="Cannot delete active setting"' : '' }}>
                                <i class="fas fa-trash"></i> {{ __('Delete Setting') }}
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
let isTestingConnection = false; // Prevent multiple simultaneous requests
let isRunningDiagnostics = false; // Prevent multiple simultaneous diagnostics

function testConnection() {
    // Prevent multiple clicks
    if (isTestingConnection) {
        return;
    }
    
    isTestingConnection = true;
    
    // Show loading state
    const btn = document.getElementById('testBtn');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> {{ __('Testing...') }}';

    // Hide previous result
    const connectionResult = document.getElementById('connectionResult');
    if (connectionResult) {
        connectionResult.style.display = 'none';
    }

    // Make AJAX request
    fetch('{{ route("mikrotik-settings.test-ajax", $mikrotikSetting) }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({})
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        return response.json();
    })
    .then(data => {
        showConnectionResult(data.success, data.message, data.details || {});
    })
    .catch(error => {
        showConnectionResult(false, 'Error: ' + error.message, {});
        console.error('Connection test error:', error);
    })
    .finally(() => {
        // Restore button state
        btn.disabled = false;
        btn.innerHTML = originalText;
        isTestingConnection = false; // Reset flag
    });
}

function runDiagnostics() {
    // Prevent multiple clicks
    if (isRunningDiagnostics) {
        return;
    }
    
    isRunningDiagnostics = true;
    
    // Show loading state
    const btn = document.getElementById('diagnosticsBtn');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> {{ __('Running...') }}';

    // Hide previous result
    const connectionResult = document.getElementById('connectionResult');
    if (connectionResult) {
        connectionResult.style.display = 'none';
    }

    // Make AJAX request
    fetch('{{ route("mikrotik-settings.diagnostics", $mikrotikSetting) }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({})
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            showDiagnosticsResult(data.diagnostics);
        } else {
            showConnectionResult(false, 'Diagnostics failed: ' + data.message, {});
        }
    })
    .catch(error => {
        showConnectionResult(false, 'Diagnostics error: ' + error.message, {});
        console.error('Diagnostics error:', error);
    })
    .finally(() => {
        // Restore button state
        btn.disabled = false;
        btn.innerHTML = originalText;
        isRunningDiagnostics = false; // Reset flag
    });
}

function showConnectionResult(success, message, details) {
    const resultDiv = document.getElementById('connectionResult');
    const messageDiv = document.getElementById('connectionMessage');
    const alertDiv = resultDiv.querySelector('.alert');
    
    let content = '';
    
    if (success) {
        alertDiv.className = 'alert alert-success';
        content = '<strong>✅ {{ __('Connection Successful!') }}</strong><br>' + message;
        
        if (details.identity) {
            content += '<br><br><strong>{{ __('Router Identity:') }}</strong> ' + details.identity;
        }
        
        if (details.connection_type) {
            content += '<br><strong>{{ __('Connection Type:') }}</strong> ' + details.connection_type;
        }
    } else {
        alertDiv.className = 'alert alert-danger';
        content = '<strong>❌ {{ __('Connection Failed!') }}</strong><br>' + message;
    }
    
    messageDiv.innerHTML = content;
    resultDiv.style.display = 'block';
}

function showDiagnosticsResult(diagnostics) {
    const resultDiv = document.getElementById('connectionResult');
    const messageDiv = document.getElementById('connectionMessage');
    const alertDiv = resultDiv.querySelector('.alert');
    
    alertDiv.className = 'alert alert-info';
    
    let content = '<strong>🔧 {{ __('Connection Diagnostics') }}</strong><br><br>';
    content += '<div class="row">';
    
    // Network Test
    const networkStatus = diagnostics.network.includes('OK') ? 'text-success' : 'text-danger';
    content += `<div class="col-md-6 mb-2">
        <strong>🌐 {{ __('Network:') }}</strong> 
        <span class="${networkStatus}">${diagnostics.network}</span>
    </div>`;
    
    // DNS Test
    const dnsStatus = diagnostics.dns.includes('OK') ? 'text-success' : 
                     (diagnostics.dns.includes('N/A') ? 'text-muted' : 'text-danger');
    content += `<div class="col-md-6 mb-2">
        <strong>🔍 {{ __('DNS:') }}</strong> 
        <span class="${dnsStatus}">${diagnostics.dns}</span>
    </div>`;
    
    // Port Test
    const portStatus = diagnostics.port.includes('OK') ? 'text-success' : 'text-danger';
    content += `<div class="col-md-6 mb-2">
        <strong>🔌 {{ __('Port:') }}</strong> 
        <span class="${portStatus}">${diagnostics.port}</span>
    </div>`;
    
    // API Test
    const apiStatus = diagnostics.api.includes('OK') ? 'text-success' : 'text-danger';
    content += `<div class="col-md-6 mb-2">
        <strong>⚡ {{ __('API:') }}</strong> 
        <span class="${apiStatus}">${diagnostics.api}</span>
    </div>`;
    
    content += '</div>';
    
    messageDiv.innerHTML = content;
    resultDiv.style.display = 'block';
}
</script>
@endpush
