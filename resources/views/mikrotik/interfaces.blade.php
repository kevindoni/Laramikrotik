@extends('layouts.admin')

@section('main-content')
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-ethernet text-info"></i> Interface Monitoring
        </h1>
        <button class="btn btn-primary btn-sm" onclick="refreshData()">
            <i class="fas fa-sync-alt"></i> Refresh
        </button>
    </div>

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle"></i> {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <!-- Interface Summary Cards -->
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Interfaces
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ count($interfaces) }}
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
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Running Interfaces
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ collect($interfaces)->where('running', 'true')->count() }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
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
                                Disabled Interfaces
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ collect($interfaces)->where('disabled', 'true')->count() }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-pause-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                Down Interfaces
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ collect($interfaces)->where('running', '!=', 'true')->where('disabled', '!=', 'true')->count() }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Interface Details Table -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-list"></i> Interface Details
                    </h6>
                </div>
                <div class="card-body">
                    @if(!empty($interfaces))
                        <div class="table-responsive">
                            <table class="table table-bordered" id="interfaceTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Interface</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                        <th>MAC Address</th>
                                        <th>MTU</th>
                                        <th>Speed</th>
                                        <th>RX Bytes</th>
                                        <th>TX Bytes</th>
                                        <th>RX Packets</th>
                                        <th>TX Packets</th>
                                        <th>Errors</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($interfaces as $interface)
                                        <tr>
                                            <td>
                                                <strong>{{ $interface['name'] ?? 'Unknown' }}</strong>
                                                @if(isset($interface['comment']) && $interface['comment'])
                                                    <br><small class="text-muted">{{ $interface['comment'] }}</small>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge badge-secondary">{{ $interface['type'] ?? 'N/A' }}</span>
                                            </td>
                                            <td>
                                                @if(isset($interface['running']) && $interface['running'] === 'true')
                                                    <span class="badge badge-success">
                                                        <i class="fas fa-circle"></i> Running
                                                    </span>
                                                @elseif(isset($interface['disabled']) && $interface['disabled'] === 'true')
                                                    <span class="badge badge-secondary">
                                                        <i class="fas fa-pause"></i> Disabled
                                                    </span>
                                                @else
                                                    <span class="badge badge-danger">
                                                        <i class="fas fa-times"></i> Down
                                                    </span>
                                                @endif
                                            </td>
                                            <td>
                                                <code>{{ $interface['mac-address'] ?? 'N/A' }}</code>
                                            </td>
                                            <td>{{ $interface['mtu'] ?? 'N/A' }}</td>
                                            <td>
                                                @if(isset($interface['actual-mtu']))
                                                    {{ $interface['actual-mtu'] }}
                                                @else
                                                    {{ $interface['default-name'] ?? 'N/A' }}
                                                @endif
                                            </td>
                                            <td>
                                                @if(isset($interface['rx-byte']))
                                                    {{ number_format($interface['rx-byte'] / 1024 / 1024, 2) }} MB
                                                @else
                                                    0 MB
                                                @endif
                                            </td>
                                            <td>
                                                @if(isset($interface['tx-byte']))
                                                    {{ number_format($interface['tx-byte'] / 1024 / 1024, 2) }} MB
                                                @else
                                                    0 MB
                                                @endif
                                            </td>
                                            <td>{{ number_format($interface['rx-packet'] ?? 0) }}</td>
                                            <td>{{ number_format($interface['tx-packet'] ?? 0) }}</td>
                                            <td>
                                                @php
                                                    $rxErrors = $interface['rx-error'] ?? 0;
                                                    $txErrors = $interface['tx-error'] ?? 0;
                                                    $totalErrors = $rxErrors + $txErrors;
                                                @endphp
                                                @if($totalErrors > 0)
                                                    <span class="badge badge-warning">{{ $totalErrors }}</span>
                                                @else
                                                    <span class="badge badge-success">0</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center text-muted">
                            <i class="fas fa-exclamation-circle fa-3x mb-3"></i>
                            <p>No interface data available</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Interface Statistics -->
    @if(!empty($interfaceStats))
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-area"></i> Real-time Traffic Statistics
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Interface</th>
                                    <th>RX Rate</th>
                                    <th>TX Rate</th>
                                    <th>RX Packets/s</th>
                                    <th>TX Packets/s</th>
                                    <th>Total Rate</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($interfaceStats as $stat)
                                    <tr>
                                        <td><strong>{{ $stat['name'] ?? 'Unknown' }}</strong></td>
                                        <td>
                                            @if(isset($stat['rx-bits-per-second']))
                                                {{ number_format($stat['rx-bits-per-second'] / 1024 / 1024, 2) }} Mbps
                                            @else
                                                0 Mbps
                                            @endif
                                        </td>
                                        <td>
                                            @if(isset($stat['tx-bits-per-second']))
                                                {{ number_format($stat['tx-bits-per-second'] / 1024 / 1024, 2) }} Mbps
                                            @else
                                                0 Mbps
                                            @endif
                                        </td>
                                        <td>{{ number_format($stat['rx-packets-per-second'] ?? 0) }}</td>
                                        <td>{{ number_format($stat['tx-packets-per-second'] ?? 0) }}</td>
                                        <td>
                                            @php
                                                $rxRate = $stat['rx-bits-per-second'] ?? 0;
                                                $txRate = $stat['tx-bits-per-second'] ?? 0;
                                                $totalRate = ($rxRate + $txRate) / 1024 / 1024;
                                            @endphp
                                            <strong>{{ number_format($totalRate, 2) }} Mbps</strong>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

@endsection

@push('scripts')
<!-- DataTables -->
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap4.min.js"></script>

<script>
$(document).ready(function() {
    $('#interfaceTable').DataTable({
        "pageLength": 25,
        "order": [[ 0, "asc" ]],
        "columnDefs": [
            { "orderable": false, "targets": [2] } // Status column
        ]
    });
});

function refreshData() {
    location.reload();
}

// Auto refresh every 30 seconds
setInterval(function() {
    refreshData();
}, 30000);
</script>
@endpush
