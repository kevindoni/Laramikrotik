@extends('layouts.admin')

@section('title', 'Active PPP Connections')

@section('main-content')
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-0 text-gray-800">Active PPP Connections</h1>
        <p class="text-muted mb-0">Real-time monitoring of active PPPoE connections</p>
    </div>
    <div>
        <a href="{{ route('ppp-secrets.index') }}" class="btn btn-sm btn-outline-primary mr-2">
            <i class="fas fa-arrow-left"></i> Back to PPP Secrets
        </a>
        <button onclick="location.reload()" class="btn btn-sm btn-info" style="font-size: 0.775rem; padding: 0.25rem 0.5rem;">
            <i class="fas fa-sync-alt"></i>
        </button>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

@if(session('warning'))
    <div class="alert alert-warning">{{ session('warning') }}</div>
@endif

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Active Connections ({{ count($activeConnections) }})</h6>
    </div>
    <div class="card-body">
        @if(count($activeConnections) > 0)
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable">
                    <thead>
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
                        @foreach($activeConnections as $connection)
                            @php
                                $secret = $secrets->get($connection['name'] ?? '');
                            @endphp
                            <tr>
                                <td>
                                    <strong>{{ $connection['name'] ?? 'N/A' }}</strong>
                                    @if($secret)
                                        <br><small class="text-muted">{{ $secret->comment ?? '' }}</small>
                                    @endif
                                </td>
                                <td>
                                    @if($secret && $secret->customer)
                                        <a href="{{ route('customers.show', $secret->customer) }}">
                                            {{ $secret->customer->name }}
                                        </a>
                                        <br><small class="text-muted">{{ $secret->customer->phone ?? '' }}</small>
                                    @else
                                        <span class="text-muted">No customer assigned</span>
                                    @endif
                                </td>
                                <td>
                                    @if($secret && $secret->pppProfile)
                                        <a href="{{ route('ppp-profiles.show', $secret->pppProfile) }}">
                                            {{ $secret->pppProfile->name }}
                                        </a>
                                        <br><small class="text-muted">{{ $secret->pppProfile->rate_limit ?? 'Unlimited' }}</small>
                                    @else
                                        <span class="text-muted">{{ $connection['profile'] ?? 'default' }}</span>
                                    @endif
                                </td>
                                <td>
                                    <strong>{{ $connection['address'] ?? 'N/A' }}</strong>
                                    @if(isset($connection['radius']))
                                        <br><small class="text-muted">RADIUS: {{ $connection['radius'] }}</small>
                                    @endif
                                </td>
                                <td>{{ $connection['caller-id'] ?? 'N/A' }}</td>
                                <td>
                                    <div class="mb-1">
                                        <i class="fas fa-clock text-primary"></i> 
                                        <strong>{{ $connection['uptime'] ?? 'N/A' }}</strong>
                                    </div>
                                    @if(isset($connection['bytes-in']) || isset($connection['bytes-out']))
                                        <small class="text-muted">
                                            @if(isset($connection['bytes-in']))
                                                <i class="fas fa-download"></i> 
                                                {{ $connection['bytes-in'] ? number_format($connection['bytes-in'] / 1024 / 1024, 2) . ' MB' : '0 MB' }}
                                            @endif
                                            @if(isset($connection['bytes-out']))
                                                <br><i class="fas fa-upload"></i> 
                                                {{ $connection['bytes-out'] ? number_format($connection['bytes-out'] / 1024 / 1024, 2) . ' MB' : '0 MB' }}
                                            @endif
                                        </small>
                                    @endif
                                </td>
                                <td>
                                    @if(isset($connection['rate-limit']))
                                        @php
                                            $rates = explode('/', $connection['rate-limit']);
                                            $upload = $rates[1] ?? 'N/A';
                                        @endphp
                                        <span class="badge badge-success">
                                            <i class="fas fa-upload"></i> {{ $upload }}
                                        </span>
                                    @elseif($secret && $secret->pppProfile && $secret->pppProfile->rate_limit)
                                        @php
                                            $rates = explode('/', $secret->pppProfile->rate_limit);
                                            $upload = $rates[1] ?? 'N/A';
                                        @endphp
                                        <span class="badge badge-info">
                                            <i class="fas fa-upload"></i> {{ $upload }}
                                        </span>
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    @if(isset($connection['rate-limit']))
                                        @php
                                            $rates = explode('/', $connection['rate-limit']);
                                            $download = $rates[0] ?? 'N/A';
                                        @endphp
                                        <span class="badge badge-primary">
                                            <i class="fas fa-download"></i> {{ $download }}
                                        </span>
                                    @elseif($secret && $secret->pppProfile && $secret->pppProfile->rate_limit)
                                        @php
                                            $rates = explode('/', $secret->pppProfile->rate_limit);
                                            $download = $rates[0] ?? 'N/A';
                                        @endphp
                                        <span class="badge badge-info">
                                            <i class="fas fa-download"></i> {{ $download }}
                                        </span>
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge badge-primary">{{ $connection['service'] ?? 'pppoe' }}</span>
                                </td>
                                <td>
                                    @if($secret)
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('ppp-secrets.show', $secret) }}" class="btn btn-sm btn-info" title="View Secret">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <form action="{{ route('ppp-secrets.disconnect', $secret) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to disconnect this user?')">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-warning" title="Disconnect">
                                                    <i class="fas fa-unlink"></i>
                                                </button>
                                            </form>
                                        </div>
                                    @else
                                        <span class="text-muted">No secret found</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-4">
                <i class="fas fa-wifi text-muted" style="font-size: 3rem;"></i>
                <h5 class="mt-3 text-muted">No Active Connections</h5>
                <p class="text-muted">There are currently no active PPP connections.</p>
            </div>
        @endif
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
                            Total Connections
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">{{ count($activeConnections) }}</div>
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
                            Known Users
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            {{ $secrets->count() }}
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
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                            PPPoE Sessions
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            {{ collect($activeConnections)->where('service', 'pppoe')->count() }}
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-network-wired fa-2x text-gray-300"></i>
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
                            With Customers
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            {{ $secrets->whereNotNull('customer_id')->count() }}
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-user-check fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('#dataTable').DataTable({
        "pageLength": 25,
        "order": [[ 0, "asc" ]],
        "columnDefs": [
            { "orderable": false, "targets": 9 }
        ]
    });
    
    // Auto-refresh every 30 seconds
    let refreshInterval;
    let refreshEnabled = true;
    
    function startAutoRefresh() {
        refreshInterval = setInterval(function() {
            if (refreshEnabled) {
                console.log('Auto-refreshing active connections...');
                location.reload();
            }
        }, 30000); // 30 seconds
    }
    
    function stopAutoRefresh() {
        if (refreshInterval) {
            clearInterval(refreshInterval);
        }
    }
    
    // Add refresh controls
    const refreshControls = `
        <div class="mb-3">
            <button id="refreshNow" class="btn btn-sm btn-primary" style="font-size: 0.775rem; padding: 0.25rem 0.5rem;">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
            <button id="toggleAutoRefresh" class="btn btn-sm btn-secondary ml-2" style="font-size: 0.775rem; padding: 0.25rem 0.5rem;">
                <i class="fas fa-pause"></i> Auto-Refresh
            </button>
            <span class="ml-2 text-muted" style="font-size: 0.8rem;">
                <i class="fas fa-clock"></i> Every 30s
            </span>
        </div>
    `;
    
    $('.card-body').prepend(refreshControls);
    
    // Manual refresh
    $('#refreshNow').click(function() {
        $(this).html('<i class="fas fa-spinner fa-spin"></i> Refreshing...');
        location.reload();
    });
    
    // Toggle auto-refresh
    $('#toggleAutoRefresh').click(function() {
        refreshEnabled = !refreshEnabled;
        if (refreshEnabled) {
            $(this).html('<i class="fas fa-pause"></i> Auto-Refresh');
            $(this).removeClass('btn-success').addClass('btn-secondary');
            startAutoRefresh();
        } else {
            $(this).html('<i class="fas fa-play"></i> Enable');
            $(this).removeClass('btn-secondary').addClass('btn-success');
            stopAutoRefresh();
        }
    });
    
    // Start auto-refresh on page load
    startAutoRefresh();
    
    // Stop auto-refresh when user is inactive (optional)
    let userActivity = true;
    let activityTimer;
    
    function resetActivityTimer() {
        userActivity = true;
        clearTimeout(activityTimer);
        activityTimer = setTimeout(function() {
            userActivity = false;
        }, 300000); // 5 minutes of inactivity
    }
    
    // Track user activity
    $(document).on('mousedown keydown scroll touchstart', resetActivityTimer);
    resetActivityTimer();
});
</script>
@endpush
