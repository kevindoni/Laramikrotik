@extends('layouts.admin')

@section('title', 'Usage Logs')

@section('main-content')
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Usage Log Management</h1>
        <div>
            <a href="{{ route('usage-logs.active-connections') }}" class="btn btn-sm btn-info shadow-sm mr-2">
                <i class="fas fa-wifi fa-sm text-white-50"></i> Active Connections
            </a>
            <a href="{{ route('usage-logs.statistics') }}" class="btn btn-sm btn-primary shadow-sm">
                <i class="fas fa-chart-bar fa-sm text-white-50"></i> Statistics
            </a>
        </div>
    </div>

    <!-- Alert Messages -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <!-- Search & Filter -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Search & Filter</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('usage-logs.index') }}">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="search">Search</label>
                            <input type="text" class="form-control" name="search" 
                                   value="{{ request('search') }}" 
                                   placeholder="Username, customer name...">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="date_from">Date From</label>
                            <input type="date" class="form-control" name="date_from" 
                                   value="{{ request('date_from') }}">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="date_to">Date To</label>
                            <input type="date" class="form-control" name="date_to" 
                                   value="{{ request('date_to') }}">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="connection_type">Type</label>
                            <select class="form-control" name="connection_type">
                                <option value="">All Types</option>
                                <option value="pppoe" {{ request('connection_type') == 'pppoe' ? 'selected' : '' }}>PPPoE</option>
                                <option value="pptp" {{ request('connection_type') == 'pptp' ? 'selected' : '' }}>PPTP</option>
                                <option value="l2tp" {{ request('connection_type') == 'l2tp' ? 'selected' : '' }}>L2TP</option>
                                <option value="sstp" {{ request('connection_type') == 'sstp' ? 'selected' : '' }}>SSTP</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select class="form-control" name="status">
                                <option value="">All Status</option>
                                <option value="connected" {{ request('status') == 'connected' ? 'selected' : '' }}>Connected</option>
                                <option value="disconnected" {{ request('status') == 'disconnected' ? 'selected' : '' }}>Disconnected</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-1">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <div class="d-flex">
                                <button type="submit" class="btn btn-primary mr-1">
                                    <i class="fas fa-search"></i>
                                </button>
                                <a href="{{ route('usage-logs.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-refresh"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
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
                                Total Sessions</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $usageLogs->total() }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-history fa-2x text-gray-300"></i>
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
                                Active Now</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $activeConnections ?? 0 }}
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
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Total Data Transfer</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $totalDataTransfer ?? '0 MB' }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-download fa-2x text-gray-300"></i>
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
                                Avg Session Time</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $avgSessionTime ?? '0 min' }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- DataTable -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Usage Log List</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Connection</th>
                            <th>Session Time</th>
                            <th>Data Transfer</th>
                            <th>IP Address</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($usageLogs as $log)
                        <tr>
                            <td>
                                @if($log->pppSecret)
                                    <strong>{{ $log->pppSecret->username }}</strong>
                                    @if($log->pppSecret->customer)
                                        <br><small class="text-muted">{{ $log->pppSecret->customer->name }}</small>
                                    @endif
                                @else
                                    <span class="text-muted">Unknown User</span>
                                @endif
                            </td>
                            <td>
                                @if($log->connected_at)
                                    <small><strong>Start:</strong> {{ $log->connected_at->format('d/m/Y H:i:s') }}</small>
                                @else
                                    <small class="text-muted">No connection time</small>
                                @endif
                                
                                @if($log->disconnected_at)
                                    <br><small><strong>End:</strong> {{ $log->disconnected_at->format('d/m/Y H:i:s') }}</small>
                                @else
                                    <br><small class="text-success"><strong>Status:</strong> Active</small>
                                @endif
                                
                                @if($log->session_id)
                                    <br><span class="badge badge-info">{{ substr($log->session_id, 0, 8) }}...</span>
                                @endif
                            </td>
                            <td>
                                @if($log->uptime)
                                    <strong>{{ $log->uptime }}</strong>
                                @elseif($log->connected_at && $log->disconnected_at)
                                    @php
                                        $duration = $log->connected_at->diffInSeconds($log->disconnected_at);
                                        $hours = floor($duration / 3600);
                                        $minutes = floor(($duration % 3600) / 60);
                                        $seconds = $duration % 60;
                                    @endphp
                                    <strong>{{ sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds) }}</strong>
                                @elseif($log->connected_at)
                                    @php
                                        $duration = $log->connected_at->diffInSeconds(now());
                                        $hours = floor($duration / 3600);
                                        $minutes = floor(($duration % 3600) / 60);
                                        $seconds = $duration % 60;
                                    @endphp
                                    <strong class="text-success">{{ sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds) }}</strong>
                                    <br><small class="text-success">Live</small>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($log->bytes_in || $log->bytes_out)
                                    <small><strong>In:</strong> {{ $log->bytes_in ? number_format($log->bytes_in / 1024 / 1024, 2) . ' MB' : '0 MB' }}</small>
                                    <br><small><strong>Out:</strong> {{ $log->bytes_out ? number_format($log->bytes_out / 1024 / 1024, 2) . ' MB' : '0 MB' }}</small>
                                    <br><small class="text-primary"><strong>Total:</strong> {{ number_format(($log->bytes_in + $log->bytes_out) / 1024 / 1024, 2) }} MB</small>
                                @else
                                    <span class="text-muted">No data</span>
                                @endif
                            </td>
                            <td>
                                @if($log->ip_address)
                                    <code>{{ $log->ip_address }}</code>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                                @if($log->caller_id)
                                    <br><small class="text-muted">{{ $log->caller_id }}</small>
                                @endif
                            </td>
                            <td>
                                @if($log->disconnected_at)
                                    <span class="badge badge-secondary">Disconnected</span>
                                    @if($log->terminate_reason)
                                        <br><small class="text-muted">{{ $log->terminate_reason }}</small>
                                    @endif
                                @else
                                    <span class="badge badge-success">Active</span>
                                    @if($log->connected_at && $log->connected_at->diffInMinutes(now()) > 0)
                                        <br><small class="text-muted">{{ $log->connected_at->diffForHumans() }}</small>
                                    @endif
                                @endif
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="{{ route('usage-logs.show', $log) }}" 
                                       class="btn btn-sm btn-info" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    
                                    @if($log->pppSecret && $log->pppSecret->customer)
                                        <a href="{{ route('usage-logs.for-customer', $log->pppSecret->customer) }}" 
                                           class="btn btn-sm btn-primary" title="Customer Usage">
                                            <i class="fas fa-user"></i>
                                        </a>
                                    @endif
                                    
                                    @if(!$log->disconnect_time)
                                        <form action="{{ route('ppp-secrets.disconnect', $log->pppSecret) }}" 
                                              method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-warning disconnect-session-btn" 
                                                    title="Disconnect Session">
                                                <i class="fas fa-plug"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center">No usage logs found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-center">
                {{ $usageLogs->withQueryString()->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Disconnect session confirmation with SweetAlert
    $('form').on('submit', function(e) {
        if ($(this).find('.disconnect-session-btn').length > 0) {
            e.preventDefault();
            const form = this;
            
            Swal.fire({
                title: '🔌 Disconnect Session?',
                html: `
                    <div class="text-left">
                        <p class="mb-3">Are you sure you want to disconnect this active session?</p>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> <strong>Note:</strong>
                            <ul class="mb-0 mt-2">
                                <li>User will be immediately disconnected</li>
                                <li>They can reconnect if their account is active</li>
                                <li>Current session data will be lost</li>
                            </ul>
                        </div>
                    </div>
                `,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ffc107',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fas fa-plug"></i> Yes, Disconnect!',
                cancelButtonText: '<i class="fas fa-times"></i> Cancel',
                customClass: {
                    confirmButton: 'btn btn-warning mx-2',
                    cancelButton: 'btn btn-secondary mx-2'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        }
    });
});
</script>
@endpush
