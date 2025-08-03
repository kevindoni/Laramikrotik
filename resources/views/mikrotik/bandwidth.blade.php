@extends('layouts.admin')

@section('main-content')
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-tachometer-alt text-success"></i> Bandwidth Monitoring
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

    <!-- Bandwidth Summary Cards -->
    <div class="row">
        @if(!empty($bandwidthData))
            @php
                $totalRx = 0;
                $totalTx = 0;
                $activeInterfaces = 0;
                
                foreach($bandwidthData as $data) {
                    if(isset($data['rx-bits-per-second'])) {
                        $totalRx += $data['rx-bits-per-second'];
                        $activeInterfaces++;
                    }
                    if(isset($data['tx-bits-per-second'])) {
                        $totalTx += $data['tx-bits-per-second'];
                    }
                }
                
                $totalBandwidth = ($totalRx + $totalTx) / 1024 / 1024; // Convert to Mbps
            @endphp

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                    Total RX Rate
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    {{ number_format($totalRx / 1024 / 1024, 2) }} Mbps
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
                <div class="card border-left-info shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                    Total TX Rate
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    {{ number_format($totalTx / 1024 / 1024, 2) }} Mbps
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-upload fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                    Total Bandwidth
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    {{ number_format($totalBandwidth, 2) }} Mbps
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-exchange-alt fa-2x text-gray-300"></i>
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
                                    Active Interfaces
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    {{ $activeInterfaces }}
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-network-wired fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Bandwidth Chart -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-area"></i> Real-time Bandwidth Usage
                    </h6>
                </div>
                <div class="card-body">
                    <div id="bandwidthChart" style="height: 400px;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Interface Bandwidth Details -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-list"></i> Interface Bandwidth Details
                    </h6>
                </div>
                <div class="card-body">
                    @if(!empty($bandwidthData))
                        <div class="table-responsive">
                            <table class="table table-bordered" id="bandwidthTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Interface</th>
                                        <th>RX Rate</th>
                                        <th>TX Rate</th>
                                        <th>RX Packets/s</th>
                                        <th>TX Packets/s</th>
                                        <th>RX Utilization</th>
                                        <th>TX Utilization</th>
                                        <th>Total Bandwidth</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($bandwidthData as $interfaceName => $data)
                                        <tr>
                                            <td>
                                                <strong>{{ $interfaceName }}</strong>
                                                @php
                                                    $interface = collect($interfaces)->firstWhere('name', $interfaceName);
                                                @endphp
                                                @if($interface && isset($interface['running']) && $interface['running'] === 'true')
                                                    <span class="badge badge-success badge-sm ml-2">Running</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if(isset($data['rx-bits-per-second']))
                                                    @php $rxMbps = $data['rx-bits-per-second'] / 1024 / 1024; @endphp
                                                    <span class="badge 
                                                        @if($rxMbps > 10) badge-danger
                                                        @elseif($rxMbps > 5) badge-warning
                                                        @else badge-success
                                                        @endif
                                                    ">
                                                        {{ number_format($rxMbps, 2) }} Mbps
                                                    </span>
                                                @else
                                                    <span class="badge badge-secondary">0 Mbps</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if(isset($data['tx-bits-per-second']))
                                                    @php $txMbps = $data['tx-bits-per-second'] / 1024 / 1024; @endphp
                                                    <span class="badge 
                                                        @if($txMbps > 10) badge-danger
                                                        @elseif($txMbps > 5) badge-warning
                                                        @else badge-info
                                                        @endif
                                                    ">
                                                        {{ number_format($txMbps, 2) }} Mbps
                                                    </span>
                                                @else
                                                    <span class="badge badge-secondary">0 Mbps</span>
                                                @endif
                                            </td>
                                            <td>{{ number_format($data['rx-packets-per-second'] ?? 0) }}</td>
                                            <td>{{ number_format($data['tx-packets-per-second'] ?? 0) }}</td>
                                            <td>
                                                @php
                                                    // Assume 100Mbps interface capacity for calculation
                                                    $rxUtil = isset($data['rx-bits-per-second']) ? ($data['rx-bits-per-second'] / 1024 / 1024 / 100) * 100 : 0;
                                                @endphp
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar 
                                                        @if($rxUtil > 80) bg-danger
                                                        @elseif($rxUtil > 60) bg-warning
                                                        @else bg-success
                                                        @endif
                                                    " role="progressbar" style="width: {{ min($rxUtil, 100) }}%">
                                                        {{ number_format($rxUtil, 1) }}%
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                @php
                                                    $txUtil = isset($data['tx-bits-per-second']) ? ($data['tx-bits-per-second'] / 1024 / 1024 / 100) * 100 : 0;
                                                @endphp
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar 
                                                        @if($txUtil > 80) bg-danger
                                                        @elseif($txUtil > 60) bg-warning
                                                        @else bg-info
                                                        @endif
                                                    " role="progressbar" style="width: {{ min($txUtil, 100) }}%">
                                                        {{ number_format($txUtil, 1) }}%
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                @php
                                                    $totalBw = (($data['rx-bits-per-second'] ?? 0) + ($data['tx-bits-per-second'] ?? 0)) / 1024 / 1024;
                                                @endphp
                                                <strong>{{ number_format($totalBw, 2) }} Mbps</strong>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center text-muted">
                            <i class="fas fa-exclamation-circle fa-3x mb-3"></i>
                            <p>No bandwidth data available</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

@endsection

@push('styles')
<!-- ApexCharts CSS -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<!-- DataTables CSS -->
<link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap4.min.css" rel="stylesheet">
@endpush

@push('scripts')
<!-- DataTables -->
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap4.min.js"></script>

<script>
// Initialize DataTable
$(document).ready(function() {
    $('#bandwidthTable').DataTable({
        "pageLength": 25,
        "order": [[ 7, "desc" ]], // Sort by total bandwidth
        "columnDefs": [
            { "orderable": false, "targets": [5, 6] } // Progress bars columns
        ]
    });
});

// Bandwidth Chart
document.addEventListener('DOMContentLoaded', function() {
    @if(!empty($bandwidthData))
        var interfaceNames = @json(array_keys($bandwidthData));
        var rxData = [];
        var txData = [];
        
        @foreach($bandwidthData as $interfaceName => $data)
            rxData.push({{ isset($data['rx-bits-per-second']) ? round($data['rx-bits-per-second'] / 1024 / 1024, 2) : 0 }});
            txData.push({{ isset($data['tx-bits-per-second']) ? round($data['tx-bits-per-second'] / 1024 / 1024, 2) : 0 }});
        @endforeach
        
        var options = {
            series: [{
                name: 'RX Rate (Mbps)',
                data: rxData
            }, {
                name: 'TX Rate (Mbps)',
                data: txData
            }],
            chart: {
                type: 'bar',
                height: 400,
                toolbar: {
                    show: true
                }
            },
            colors: ['#28a745', '#007bff'],
            dataLabels: {
                enabled: true,
                formatter: function(val) {
                    return val + ' Mbps';
                }
            },
            xaxis: {
                categories: interfaceNames,
                title: {
                    text: 'Interfaces'
                }
            },
            yaxis: {
                title: {
                    text: 'Bandwidth (Mbps)'
                }
            },
            grid: {
                borderColor: '#e0e6ed'
            },
            legend: {
                position: 'top',
                horizontalAlign: 'center'
            },
            tooltip: {
                y: {
                    formatter: function(val) {
                        return val + ' Mbps';
                    }
                }
            }
        };

        var chart = new ApexCharts(document.querySelector("#bandwidthChart"), options);
        chart.render();
    @else
        document.getElementById('bandwidthChart').innerHTML = 
            '<div class="text-center text-muted py-5">' +
            '<i class="fas fa-chart-area fa-3x mb-3"></i>' +
            '<p>No bandwidth data available</p>' +
            '</div>';
    @endif
});

function refreshData() {
    location.reload();
}

// Auto refresh every 10 seconds for real-time monitoring
setInterval(function() {
    refreshData();
}, 10000);
</script>
@endpush
