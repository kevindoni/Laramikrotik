@extends('layouts.admin')

@section('main-content')
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-route text-info"></i> Routing Table
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

    <!-- Routing Summary Cards -->
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Routes
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $routingStats['total_routes'] ?? count($routes) }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-route fa-2x text-gray-300"></i>
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
                                Active Routes
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $routingStats['active_routes'] ?? collect($routes)->where('active', 'true')->count() }}
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
                                Static Routes
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $routingStats['static_routes'] ?? collect($routes)->where('static', 'true')->count() }}
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
                                Dynamic Routes
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $routingStats['dynamic_routes'] ?? collect($routes)->where('dynamic', 'true')->count() }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-random fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Routes Table -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-list"></i> Routing Table
                    </h6>
                    <div>
                        <button class="btn btn-sm btn-outline-primary" onclick="filterRoutes('all')">All</button>
                        <button class="btn btn-sm btn-outline-success" onclick="filterRoutes('active')">Active</button>
                        <button class="btn btn-sm btn-outline-warning" onclick="filterRoutes('static')">Static</button>
                        <button class="btn btn-sm btn-outline-info" onclick="filterRoutes('dynamic')">Dynamic</button>
                    </div>
                </div>
                <div class="card-body">
                    @if(!empty($routes))
                        <div class="table-responsive">
                            <table class="table table-bordered" id="routingTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Destination</th>
                                        <th>Gateway</th>
                                        <th>Interface</th>
                                        <th>Distance</th>
                                        <th>Scope</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                        <th>Comment</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($routes as $route)
                                        <tr data-route-type="{{ isset($route['static']) && $route['static'] === 'true' ? 'static' : 'dynamic' }}"
                                            data-route-status="{{ isset($route['active']) && $route['active'] === 'true' ? 'active' : 'inactive' }}">
                                            <td>
                                                <code>{{ $route['dst-address'] ?? 'N/A' }}</code>
                                                @if(isset($route['dst-address']) && $route['dst-address'] === '0.0.0.0/0')
                                                    <span class="badge badge-primary badge-sm ml-1">Default</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if(isset($route['gateway']))
                                                    <code>{{ $route['gateway'] }}</code>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge badge-secondary">{{ $route['interface'] ?? 'N/A' }}</span>
                                            </td>
                                            <td>{{ $route['distance'] ?? 'N/A' }}</td>
                                            <td>{{ $route['scope'] ?? 'N/A' }}</td>
                                            <td>
                                                @if(isset($route['static']) && $route['static'] === 'true')
                                                    <span class="badge badge-warning">Static</span>
                                                @elseif(isset($route['dynamic']) && $route['dynamic'] === 'true')
                                                    <span class="badge badge-info">Dynamic</span>
                                                @elseif(isset($route['connect']) && $route['connect'] === 'true')
                                                    <span class="badge badge-success">Connected</span>
                                                @else
                                                    <span class="badge badge-secondary">Other</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if(isset($route['active']) && $route['active'] === 'true')
                                                    <span class="badge badge-success">
                                                        <i class="fas fa-circle"></i> Active
                                                    </span>
                                                @else
                                                    <span class="badge badge-danger">
                                                        <i class="fas fa-times"></i> Inactive
                                                    </span>
                                                @endif
                                                
                                                @if(isset($route['disabled']) && $route['disabled'] === 'true')
                                                    <span class="badge badge-secondary ml-1">Disabled</span>
                                                @endif
                                            </td>
                                            <td>
                                                <small class="text-muted">{{ $route['comment'] ?? '-' }}</small>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center text-muted">
                            <i class="fas fa-exclamation-circle fa-3x mb-3"></i>
                            <p>No routing information available</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Route Types Chart -->
    @if(!empty($routes))
    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-pie"></i> Route Types Distribution
                    </h6>
                </div>
                <div class="card-body">
                    <div id="routeTypesChart" style="height: 300px;"></div>
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-bar"></i> Route Status Overview
                    </h6>
                </div>
                <div class="card-body">
                    <div id="routeStatusChart" style="height: 300px;"></div>
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
var dataTable;

$(document).ready(function() {
    dataTable = $('#routingTable').DataTable({
        "pageLength": 25,
        "order": [[ 0, "asc" ]], // Sort by destination
        "columnDefs": [
            { "orderable": false, "targets": [6, 7] } // Status and comment columns
        ]
    });
});

function filterRoutes(type) {
    if (type === 'all') {
        dataTable.search('').draw();
    } else if (type === 'active') {
        dataTable.column(6).search('Active').draw();
    } else if (type === 'static') {
        dataTable.column(5).search('Static').draw();
    } else if (type === 'dynamic') {
        dataTable.column(5).search('Dynamic').draw();
    }
}

document.addEventListener('DOMContentLoaded', function() {
    @if(!empty($routes))
        // Route Types Chart
        var routeTypes = {
            'Static': 0,
            'Dynamic': 0,
            'Connected': 0,
            'Other': 0
        };

        @foreach($routes as $route)
            @if(isset($route['static']) && $route['static'] === 'true')
                routeTypes['Static']++;
            @elseif(isset($route['dynamic']) && $route['dynamic'] === 'true')
                routeTypes['Dynamic']++;
            @elseif(isset($route['connect']) && $route['connect'] === 'true')
                routeTypes['Connected']++;
            @else
                routeTypes['Other']++;
            @endif
        @endforeach

        var typesOptions = {
            series: Object.values(routeTypes),
            chart: {
                type: 'pie',
                height: 300
            },
            labels: Object.keys(routeTypes),
            colors: ['#f39c12', '#3498db', '#2ecc71', '#95a5a6'],
            legend: {
                position: 'bottom'
            },
            tooltip: {
                y: {
                    formatter: function(val) {
                        return val + ' routes';
                    }
                }
            }
        };

        var typesChart = new ApexCharts(document.querySelector("#routeTypesChart"), typesOptions);
        typesChart.render();

        // Route Status Chart
        var routeStatus = {
            'Active': {{ collect($routes)->where('active', 'true')->count() }},
            'Inactive': {{ collect($routes)->where('active', '!=', 'true')->count() }}
        };

        var statusOptions = {
            series: [{
                name: 'Routes',
                data: Object.values(routeStatus)
            }],
            chart: {
                type: 'bar',
                height: 300,
                toolbar: { show: true }
            },
            colors: ['#1cc88a', '#e74c3c'],
            xaxis: {
                categories: Object.keys(routeStatus),
                title: { text: 'Route Status' }
            },
            yaxis: {
                title: { text: 'Number of Routes' }
            },
            tooltip: {
                y: {
                    formatter: function(val) {
                        return val + ' routes';
                    }
                }
            }
        };

        var statusChart = new ApexCharts(document.querySelector("#routeStatusChart"), statusOptions);
        statusChart.render();
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
