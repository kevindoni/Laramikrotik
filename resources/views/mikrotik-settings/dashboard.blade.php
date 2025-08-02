@extends('layouts.admin')

@section('main-content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">{{ __('MikroTik Dashboard') }}</h1>
        <div>
            @if($mikrotikSetting)
                <a href="{{ route('mikrotik-settings.show', $mikrotikSetting) }}" class="btn btn-sm btn-info shadow-sm">
                    <i class="fas fa-cog fa-sm text-white-50"></i> {{ __('Settings') }}
                </a>
            @endif
            <a href="{{ route('mikrotik-settings.index') }}" class="btn btn-sm btn-primary shadow-sm">
                <i class="fas fa-list fa-sm text-white-50"></i> {{ __('All Settings') }}
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

    @if(!$mikrotikSetting)
        <div class="alert alert-warning border-left-warning" role="alert">
            <div class="text-truncate font-weight-bold">
                <i class="fas fa-exclamation-triangle mr-1"></i>
                {{ __('No Active MikroTik Setting') }}
            </div>
            <p class="mb-0">{{ __('You need to create and activate a MikroTik setting first.') }}</p>
            <a href="{{ route('mikrotik-settings.create') }}" class="btn btn-warning btn-sm mt-2">
                <i class="fas fa-plus mr-1"></i>{{ __('Create MikroTik Setting') }}
            </a>
        </div>
    @else
        {{-- Real-time Connection Status --}}
        <div class="row">
            <div class="col-lg-6">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-wifi mr-1"></i>{{ __('Real-time Connection Status') }}
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="text-center">
                            @switch($connectionStatus)
                                @case('connected')
                                    <div class="mb-3">
                                        <i class="fas fa-check-circle fa-3x text-success"></i>
                                    </div>
                                    <h5 class="text-success font-weight-bold">{{ __('Connected') }}</h5>
                                    <p class="text-muted">{{ __('Successfully connected to MikroTik') }}</p>
                                    @if($systemInfo && $systemInfo['identity'])
                                        <p><strong>{{ __('Router') }}:</strong> {{ $systemInfo['identity'] }}</p>
                                    @endif
                                    @break
                                
                                @case('failed')
                                    <div class="mb-3">
                                        <i class="fas fa-times-circle fa-3x text-danger"></i>
                                    </div>
                                    <h5 class="text-danger font-weight-bold">{{ __('Cannot connect to MikroTik') }}</h5>
                                    <p class="text-muted">{{ __('Connection failed or timed out') }}</p>
                                    @break
                                
                                @case('inactive')
                                    <div class="mb-3">
                                        <i class="fas fa-pause-circle fa-3x text-secondary"></i>
                                    </div>
                                    <h5 class="text-secondary font-weight-bold">{{ __('Inactive') }}</h5>
                                    <p class="text-muted">{{ __('MikroTik setting is not active') }}</p>
                                    @break
                                
                                @default
                                    <div class="mb-3">
                                        <i class="fas fa-question-circle fa-3x text-warning"></i>
                                    </div>
                                    <h5 class="text-warning font-weight-bold">{{ __('Status Unknown') }}</h5>
                                    <p class="text-muted">{{ __('Unable to determine connection status') }}</p>
                            @endswitch
                            
                            {{-- Connection timestamps --}}
                            <div class="mt-4">
                                <div class="row text-sm">
                                    <div class="col-12 mb-2">
                                        <strong>{{ __('Last Connection') }}:</strong>
                                        @if($mikrotikSetting->last_connected_at)
                                            <span class="text-success">
                                                {{ $mikrotikSetting->last_connected_at->format('d M Y H:i:s') }}
                                            </span>
                                            <br><small class="text-muted">{{ $mikrotikSetting->last_connected_at->diffForHumans() }}</small>
                                        @else
                                            <span class="text-muted">{{ __('Never') }}</span>
                                        @endif
                                    </div>
                                    
                                    <div class="col-12 mb-2">
                                        <strong>{{ __('Last Disconnection') }}:</strong>
                                        @if($mikrotikSetting->last_disconnected_at)
                                            <span class="text-danger">
                                                {{ $mikrotikSetting->last_disconnected_at->format('d M Y H:i:s') }}
                                            </span>
                                        @else
                                            <span class="text-success">{{ __('Still connected') }}</span>
                                        @endif
                                    </div>
                                    
                                    <div class="col-12">
                                        <strong>{{ __('Session Duration') }}:</strong>
                                        @if($mikrotikSetting->last_connected_at)
                                            @if($connectionStatus === 'connected')
                                                <span class="text-info">
                                                    {{ $mikrotikSetting->last_connected_at->diffForHumans(null, true) }}
                                                </span>
                                            @else
                                                <span class="text-muted">{{ __('N/A') }}</span>
                                            @endif
                                        @else
                                            <span class="text-muted">{{ __('N/A') }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            
                            {{-- Action buttons --}}
                            <div class="mt-4">
                                <button class="btn btn-info btn-sm" onclick="refreshStatus()" id="refreshBtn">
                                    <i class="fas fa-sync-alt"></i> {{ __('Refresh Status') }}
                                </button>
                                
                                @if($connectionStatus === 'failed' || $connectionStatus === 'unknown')
                                    <button class="btn btn-warning btn-sm ml-2" onclick="testConnection()" id="testBtn">
                                        <i class="fas fa-plug"></i> {{ __('Test Connection') }}
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            {{-- System Information --}}
            <div class="col-lg-6">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-server mr-1"></i>{{ __('System Information') }}
                        </h6>
                    </div>
                    <div class="card-body">
                        @if($systemInfo)
                            <table class="table table-borderless table-sm">
                                <tr>
                                    <td class="font-weight-bold">{{ __('Router Identity') }}</td>
                                    <td>{{ $systemInfo['identity'] ?? '-' }}</td>
                                </tr>
                                @if(isset($systemInfo['resources']))
                                    <tr>
                                        <td class="font-weight-bold">{{ __('Board Name') }}</td>
                                        <td>{{ $systemInfo['resources']['board-name'] ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="font-weight-bold">{{ __('Version') }}</td>
                                        <td>{{ $systemInfo['resources']['version'] ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="font-weight-bold">{{ __('CPU Load') }}</td>
                                        <td>
                                            @if(isset($systemInfo['resources']['cpu-load']))
                                                <span class="badge {{ $systemInfo['resources']['cpu-load'] > 80 ? 'badge-danger' : ($systemInfo['resources']['cpu-load'] > 60 ? 'badge-warning' : 'badge-success') }}">
                                                    {{ $systemInfo['resources']['cpu-load'] }}%
                                                </span>
                                            @else
                                                -
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="font-weight-bold">{{ __('Free Memory') }}</td>
                                        <td>
                                            @if(isset($systemInfo['resources']['free-memory']) && isset($systemInfo['resources']['total-memory']))
                                                @php
                                                    $freeMemory = $systemInfo['resources']['free-memory'];
                                                    $totalMemory = $systemInfo['resources']['total-memory'];
                                                    $usedPercent = round((($totalMemory - $freeMemory) / $totalMemory) * 100);
                                                @endphp
                                                {{ number_format($freeMemory / 1048576, 2) }} MB 
                                                <span class="badge {{ $usedPercent > 80 ? 'badge-danger' : ($usedPercent > 60 ? 'badge-warning' : 'badge-success') }}">
                                                    {{ 100 - $usedPercent }}% free
                                                </span>
                                            @else
                                                -
                                            @endif
                                        </td>
                                    </tr>
                                @endif
                            </table>
                        @else
                            <div class="text-center text-muted">
                                <i class="fas fa-info-circle fa-2x mb-2"></i>
                                <p>{{ __('System information not available') }}</p>
                                @if($connectionStatus !== 'connected')
                                    <small>{{ __('Connect to MikroTik to view system information') }}</small>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Active PPP Connections --}}
        <div class="row">
            <div class="col-12">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-users mr-1"></i>{{ __('Active PPP Connections') }}
                        </h6>
                    </div>
                    <div class="card-body">
                        @if($activeConnections !== null)
                            @if(count($activeConnections) > 0)
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th>{{ __('Name') }}</th>
                                                <th>{{ __('Address') }}</th>
                                                <th>{{ __('Uptime') }}</th>
                                                <th>{{ __('Service') }}</th>
                                                <th>{{ __('Status') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($activeConnections as $connection)
                                                <tr>
                                                    <td>{{ $connection['name'] ?? '-' }}</td>
                                                    <td>{{ $connection['address'] ?? '-' }}</td>
                                                    <td>{{ $connection['uptime'] ?? '-' }}</td>
                                                    <td>{{ $connection['service'] ?? 'pppoe' }}</td>
                                                    <td>
                                                        <span class="badge badge-success">
                                                            <i class="fas fa-circle mr-1"></i>{{ __('Active') }}
                                                        </span>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-center text-muted">
                                    <i class="fas fa-user-slash fa-2x mb-2"></i>
                                    <p>{{ __('No active PPP connections') }}</p>
                                    @if($connectionStatus === 'connected')
                                        <small>{{ __('All users are currently offline') }}</small>
                                    @else
                                        <small>{{ __('Unable to retrieve connection data due to timeout or connection issues') }}</small>
                                    @endif
                                </div>
                            @endif
                        @else
                            <div class="text-center text-muted">
                                <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                                <p>{{ __('PPP connection data unavailable') }}</p>
                                <small>{{ __('Connection to MikroTik timed out or failed') }}</small>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Quick Stats --}}
        <div class="row">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                    {{ __('PPP Secrets') }}
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    {{ \App\Models\PppSecret::count() }}
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-users fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                    {{ __('PPP Profiles') }}
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    {{ \App\Models\PppProfile::count() }}
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-cog fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-info shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                    {{ __('Active Connections') }}
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    {{ $activeConnections !== null ? count($activeConnections) : '-' }}
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-wifi fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-warning shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                    {{ __('Today\'s Sessions') }}
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    {{ \App\Models\UsageLog::whereDate('created_at', today())->count() }}
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-chart-area fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
<script>
let isRefreshing = false;
let isTesting = false;

function refreshStatus() {
    if (isRefreshing) return;
    
    isRefreshing = true;
    const btn = document.getElementById('refreshBtn');
    const originalText = btn.innerHTML;
    
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> {{ __('Refreshing...') }}';
    
    // Reload the page to get fresh data
    window.location.reload();
}

function testConnection() {
    if (isTesting) return;
    
    isTesting = true;
    const btn = document.getElementById('testBtn');
    const originalText = btn.innerHTML;
    
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> {{ __('Testing...') }}';
    
    // Test connection via AJAX
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
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Connection successful, reload to show updated status
            window.location.reload();
        } else {
            alert('{{ __('Connection test failed') }}: ' + data.message);
        }
    })
    .catch(error => {
        alert('{{ __('Error testing connection') }}: ' + error.message);
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        isTesting = false;
    });
}

// Auto-refresh every 30 seconds if connection is active
@if($connectionStatus === 'connected')
    setInterval(function() {
        if (!isRefreshing && !isTesting) {
            refreshStatus();
        }
    }, 30000);
@endif
</script>
@endpush
