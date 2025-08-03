@extends('layouts.admin')

@section('main-content')
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-shield-alt text-danger"></i> Firewall Statistics
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

    <!-- Firewall Summary Cards -->
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Rules
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ count($firewallRules) }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-shield-alt fa-2x text-gray-300"></i>
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
                                Active Rules
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ collect($firewallRules)->where('disabled', '!=', 'true')->count() }}
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
                                Disabled Rules
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ collect($firewallRules)->where('disabled', 'true')->count() }}
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
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Total Packets
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ number_format(collect($firewallStats)->sum('packets')) }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exchange-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Firewall Rules Table -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-list"></i> Firewall Rules
                    </h6>
                </div>
                <div class="card-body">
                    @if(!empty($firewallRules))
                        <div class="table-responsive">
                            <table class="table table-bordered" id="firewallTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Rule #</th>
                                        <th>Chain</th>
                                        <th>Action</th>
                                        <th>Protocol</th>
                                        <th>Source</th>
                                        <th>Destination</th>
                                        <th>Port</th>
                                        <th>Bytes</th>
                                        <th>Packets</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($firewallRules as $index => $rule)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>
                                                <span class="badge badge-secondary">{{ $rule['chain'] ?? 'N/A' }}</span>
                                            </td>
                                            <td>
                                                @php
                                                    $action = $rule['action'] ?? 'unknown';
                                                    $badgeClass = 'secondary';
                                                    switch($action) {
                                                        case 'accept': $badgeClass = 'success'; break;
                                                        case 'drop': $badgeClass = 'danger'; break;
                                                        case 'reject': $badgeClass = 'warning'; break;
                                                        default: $badgeClass = 'secondary'; break;
                                                    }
                                                @endphp
                                                <span class="badge badge-{{ $badgeClass }}">{{ $action }}</span>
                                            </td>
                                            <td>{{ $rule['protocol'] ?? 'any' }}</td>
                                            <td>
                                                @if(isset($rule['src-address']))
                                                    <code>{{ $rule['src-address'] }}</code>
                                                @else
                                                    any
                                                @endif
                                            </td>
                                            <td>
                                                @if(isset($rule['dst-address']))
                                                    <code>{{ $rule['dst-address'] }}</code>
                                                @else
                                                    any
                                                @endif
                                            </td>
                                            <td>{{ $rule['dst-port'] ?? 'any' }}</td>
                                            <td>{{ number_format($rule['bytes'] ?? 0) }}</td>
                                            <td>{{ number_format($rule['packets'] ?? 0) }}</td>
                                            <td>
                                                @if(isset($rule['disabled']) && $rule['disabled'] === 'true')
                                                    <span class="badge badge-warning">Disabled</span>
                                                @else
                                                    <span class="badge badge-success">Active</span>
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
                            <p>No firewall rules found</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Firewall Statistics Chart -->
    @if(!empty($firewallStats))
    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-bar"></i> Top Rules by Packets
                    </h6>
                </div>
                <div class="card-body">
                    <div id="packetsChart" style="height: 300px;"></div>
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-pie"></i> Actions Distribution
                    </h6>
                </div>
                <div class="card-body">
                    <div id="actionsChart" style="height: 300px;"></div>
                </div>
            </div>
        </div>
    </div>
    @endif

@endsection

@push('styles')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap4.min.css" rel="stylesheet">
@endpush

@push('scripts')
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap4.min.js"></script>

<script>
$(document).ready(function() {
    $('#firewallTable').DataTable({
        "pageLength": 25,
        "order": [[ 8, "desc" ]], // Sort by packets
        "columnDefs": [
            { "orderable": false, "targets": [9] } // Status column
        ]
    });
});

document.addEventListener('DOMContentLoaded', function() {
    @if(!empty($firewallStats))
        // Top Rules by Packets Chart
        var topRules = @json(collect($firewallStats)->sortByDesc('packets')->take(10)->values());
        
        var packetsOptions = {
            series: [{
                name: 'Packets',
                data: topRules.map(rule => rule.packets)
            }],
            chart: {
                type: 'bar',
                height: 300,
                toolbar: { show: true }
            },
            colors: ['#4e73df'],
            xaxis: {
                categories: topRules.map((rule, index) => 'Rule ' + (index + 1)),
                title: { text: 'Firewall Rules' }
            },
            yaxis: {
                title: { text: 'Packets Count' }
            },
            tooltip: {
                y: {
                    formatter: function(val) {
                        return val.toLocaleString() + ' packets';
                    }
                }
            }
        };

        var packetsChart = new ApexCharts(document.querySelector("#packetsChart"), packetsOptions);
        packetsChart.render();

        // Actions Distribution Chart
        var actions = {};
        @foreach($firewallRules as $rule)
            var action = '{{ $rule["action"] ?? "unknown" }}';
            if (actions[action]) {
                actions[action]++;
            } else {
                actions[action] = 1;
            }
        @endforeach

        var actionLabels = Object.keys(actions);
        var actionValues = Object.values(actions);

        var actionsOptions = {
            series: actionValues,
            chart: {
                type: 'pie',
                height: 300
            },
            labels: actionLabels,
            colors: ['#1cc88a', '#e74c3c', '#f39c12', '#6f42c1', '#20c9a6'],
            legend: {
                position: 'bottom'
            },
            tooltip: {
                y: {
                    formatter: function(val) {
                        return val + ' rules';
                    }
                }
            }
        };

        var actionsChart = new ApexCharts(document.querySelector("#actionsChart"), actionsOptions);
        actionsChart.render();
    @endif
});

function refreshData() {
    location.reload();
}

// Auto refresh every 60 seconds
setInterval(function() {
    refreshData();
}, 60000);
</script>
@endpush
