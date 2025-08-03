@extends('layouts.admin')

@section('title', 'Active PPP Connections')

@push('styles')
<style>
.badge-sm {
    font-size: 0.65em;
    padding: 0.25em 0.4em;
}

.badge-lg {
    font-size: 0.9em;
    padding: 0.5em 0.75em;
}

.table-hover tbody tr:hover {
    background-color: rgba(0,0,0,.02);
}

.card {
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
    border: 1px solid #e3e6f0;
}

.text-muted {
    color: #6c757d !important;
}

.production-indicator {
    background: linear-gradient(45deg, #fff3cd, #ffeaa7);
    border-left: 4px solid #f39c12;
    border-radius: 0.375rem;
    padding: 1rem;
    margin-bottom: 1rem;
}

.real-data-indicator {
    background: linear-gradient(45deg, #d4edda, #00b894);
    border-left: 4px solid #28a745;
    border-radius: 0.375rem;
    padding: 1rem;
    margin-bottom: 1rem;
}

.refresh-timer {
    font-weight: bold;
    color: #007bff;
}

@media (max-width: 768px) {
    .table-responsive {
        font-size: 0.875rem;
    }
}
</style>
@endpush

@section('main-content')
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Active PPP Connections</h1>
            <p class="mb-0 text-muted">Real-time monitoring of active PPPoE connections</p>
        </div>
        <div>
            <button type="button" id="refreshBtn" class="btn btn-sm btn-primary shadow-sm mr-2">
                <i class="fas fa-sync-alt"></i> Refresh Now
            </button>
            <a href="{{ route('usage-logs.index') }}" class="btn btn-sm btn-secondary shadow-sm">
                <i class="fas fa-arrow-left"></i> Back to Logs
            </a>
        </div>
    </div>

    <!-- Data Type Indicator - ALWAYS REAL DATA -->
    <div class="real-data-indicator">
        <div class="d-flex align-items-center">
            <div class="mr-3">
                <i class="fas fa-check-circle fa-2x text-success"></i>
            </div>
            <div class="flex-grow-1">
                <h5 class="mb-1 text-success">
                    <i class="fas fa-wifi"></i> Live Router Data
                </h5>
                <p class="mb-0">
                    Displaying real-time active connection data from MikroTik router.
                </p>
                <small class="text-muted">
                    <i class="fas fa-clock"></i> 
                    Auto-refresh: <span class="refresh-timer" id="refreshTimer">30s</span>
                </small>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Connections</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $connectionCount ?? 0 }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-wifi fa-2x text-gray-300"></i>
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
                                Known Users</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ collect($connections ?? [])->filter(function($conn) { return isset($conn['local_data']); })->count() }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-check fa-2x text-gray-300"></i>
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
                                PPPoE Sessions</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ collect($connections ?? [])->where('service', 'pppoe')->count() }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-ethernet fa-2x text-gray-300"></i>
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
                                With Customers</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ collect($connections ?? [])->filter(function($conn) { return isset($conn['local_data']['customer_name']) && $conn['local_data']['customer_name'] !== 'No Customer'; })->count() }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Connections Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-wifi mr-2"></i>Active Connections ({{ $connectionCount ?? 0 }})
            </h6>
            <div class="card-header-actions">
                <span class="badge badge-success mr-2">
                    <i class="fas fa-wifi"></i> Live Data
                </span>
                <button type="button" id="refreshBtn2" class="btn btn-sm btn-outline-primary" 
                        title="Refresh Table" data-toggle="tooltip">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="connectionsTable" width="100%" cellspacing="0">
                    <thead class="thead-light">
                        <tr>
                            <th>Username</th>
                            <th>Customer</th>
                            <th>Profile</th>
                            <th>Address</th>
                            <th>Caller ID</th>
                            <th>Uptime & Data Usage</th>
                            <th>Upload Speed</th>
                            <th>Download Speed</th>
                            <th>Service</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($connections as $connection)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div>
                                        <strong>{{ $connection['name'] ?? 'Unknown' }}</strong>
                                    </div>
                                </div>
                                @if(isset($connection['.id']))
                                    <small class="text-muted">ID: {{ $connection['.id'] }}</small>
                                @endif
                            </td>
                            <td>
                                @if(isset($connection['local_data']['customer_name']))
                                    <div class="mb-1">
                                        <strong>{{ $connection['local_data']['customer_name'] }}</strong>
                                    </div>
                                @else
                                    <span class="text-muted">
                                        <i class="fas fa-user-slash"></i> No customer assigned
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if(isset($connection['local_data']['profile_name']))
                                    <span class="badge badge-primary">{{ $connection['local_data']['profile_name'] }}</span>
                                @else
                                    <span class="text-muted">default</span>
                                @endif
                            </td>
                            <td>
                                <div>
                                    <strong>{{ $connection['address'] ?? 'N/A' }}</strong>
                                </div>
                                @if(isset($connection['radius']) && $connection['radius'] === 'true')
                                    <small class="text-success">
                                        <i class="fas fa-shield-alt"></i> RADIUS: true
                                    </small>
                                @endif
                            </td>
                            <td>
                                <code class="text-muted">{{ $connection['caller-id'] ?? 'N/A' }}</code>
                            </td>
                            <td>
                                <div class="mb-1">
                                    <i class="fas fa-clock text-primary"></i>
                                    <strong>{{ $connection['uptime'] ?? '00:00:00' }}</strong>
                                </div>
                                @if(isset($connection['total_usage']))
                                    <small class="text-info">
                                        <i class="fas fa-chart-area"></i> {{ $connection['total_usage'] }}
                                    </small>
                                    <br>
                                    <small class="text-muted">
                                        <i class="fas fa-download"></i> {{ $connection['formatted_bytes_in'] ?? '0 B' }}
                                        <i class="fas fa-upload ml-1"></i> {{ $connection['formatted_bytes_out'] ?? '0 B' }}
                                    </small>
                                @else
                                    <small class="text-muted">No usage data</small>
                                @endif
                            </td>
                            <td class="text-center">
                                @if(isset($connection['local_data']['upload_speed']))
                                    <span class="badge badge-warning">
                                        <i class="fas fa-upload"></i> {{ $connection['local_data']['upload_speed'] }}
                                    </span>
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if(isset($connection['local_data']['download_speed']))
                                    <span class="badge badge-success">
                                        <i class="fas fa-download"></i> {{ $connection['local_data']['download_speed'] }}
                                    </span>
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge badge-success">
                                    <i class="fas fa-ethernet"></i>
                                    {{ strtoupper($connection['service'] ?? 'pppoe') }}
                                </span>
                            </td>
                            <td>
                                @if(isset($connection['local_data']['id']))
                                    <a href="{{ route('ppp-secrets.show', $connection['local_data']['id']) }}" 
                                       class="btn btn-sm btn-info" 
                                       title="View Secret">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                @else
                                    <span class="text-muted">No secret found</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center py-4">
                                <div class="text-muted">
                                    <i class="fas fa-wifi fa-3x mb-3"></i>
                                    <p class="mb-0">No active connections found.</p>
                                    <small>Users will appear here when they connect via PPPoE.</small>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

    @if($connectionCount > 0)
        <div class="d-flex justify-content-between align-items-center mt-3">
            <div class="text-muted">
                Showing 1 to {{ $connectionCount }} of {{ $connectionCount }} entries
            </div>
            <div>
                <span class="badge badge-info">
                    <i class="fas fa-clock"></i> Auto-refresh: Every 30s
                </span>
            </div>
        </div>
    @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();

    // Refresh functionality
    $('#refreshBtn, #refreshBtn2').click(function() {
        location.reload();
    });

    // Auto-refresh timer for production
    let refreshTimer = 30;
    const timerElement = document.getElementById('refreshTimer');
    
    const updateTimer = () => {
        if (timerElement) {
            timerElement.textContent = refreshTimer + 's';
        }
        
        if (refreshTimer <= 0) {
            location.reload();
        } else {
            refreshTimer--;
        }
    };
    
    // Update timer every second
    setInterval(updateTimer, 1000);
    
    // Add pulsing effect to real-time indicator
    setInterval(function() {
        $('.real-data-indicator .fa-check-circle').fadeOut(500).fadeIn(500);
    }, 2000);
});
</script>
@endpush
