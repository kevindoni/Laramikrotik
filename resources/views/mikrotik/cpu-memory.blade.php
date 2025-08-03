@extends('layouts.admin')

@section('main-content')
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-microchip text-info"></i> CPU & Memory Monitoring
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

    <!-- CPU & Memory Summary Cards -->
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                CPU Usage
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ isset($systemResource['cpu-load']) ? $systemResource['cpu-load'] . '%' : 'N/A' }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-microchip fa-2x text-gray-300"></i>
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
                                Memory Usage
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                @if(isset($systemResource['free-memory']) && isset($systemResource['total-memory']))
                                    @php
                                        $usedMemory = $systemResource['total-memory'] - $systemResource['free-memory'];
                                        $memoryPercent = round(($usedMemory / $systemResource['total-memory']) * 100, 1);
                                    @endphp
                                    {{ $memoryPercent }}%
                                @else
                                    N/A
                                @endif
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-memory fa-2x text-gray-300"></i>
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
                                Total Memory
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ isset($systemResource['total-memory']) ? number_format($systemResource['total-memory'] / 1024 / 1024, 0) . ' MB' : 'N/A' }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-hdd fa-2x text-gray-300"></i>
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
                                Free Memory
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ isset($systemResource['free-memory']) ? number_format($systemResource['free-memory'] / 1024 / 1024, 0) . ' MB' : 'N/A' }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chart-pie fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row">
        <!-- CPU Chart -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-area"></i> CPU Usage History
                    </h6>
                </div>
                <div class="card-body">
                    <div id="cpuChart" style="height: 300px;"></div>
                </div>
            </div>
        </div>

        <!-- Memory Chart -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-line"></i> Memory Usage History
                    </h6>
                </div>
                <div class="card-body">
                    <div id="memoryChart" style="height: 300px;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- System Details -->
    <div class="row">
        <div class="col-lg-8 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-info-circle"></i> System Information
                    </h6>
                </div>
                <div class="card-body">
                    @if(!empty($systemResource))
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>Architecture:</strong></td>
                                    <td>{{ $systemResource['architecture-name'] ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Board Name:</strong></td>
                                    <td>{{ $systemResource['board-name'] ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Version:</strong></td>
                                    <td>{{ $systemResource['version'] ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Uptime:</strong></td>
                                    <td>{{ $systemResource['uptime'] ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>CPU Count:</strong></td>
                                    <td>{{ $systemResource['cpu-count'] ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>CPU Frequency:</strong></td>
                                    <td>{{ $systemResource['cpu-frequency'] ?? 'N/A' }} MHz</td>
                                </tr>
                            </table>
                        </div>
                    @else
                        <div class="text-center text-muted">
                            <i class="fas fa-exclamation-circle fa-3x mb-3"></i>
                            <p>Unable to retrieve system information</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-pie"></i> Memory Breakdown
                    </h6>
                </div>
                <div class="card-body">
                    <div id="memoryPieChart" style="height: 300px;"></div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('styles')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
@endpush

@push('scripts')
<script>
// CPU Chart
document.addEventListener('DOMContentLoaded', function() {
    @if(!empty($cpuHistory))
        var cpuData = @json($cpuHistory);
        
        var cpuOptions = {
            series: [{
                name: 'CPU Usage (%)',
                data: cpuData.map(item => item.cpu_usage)
            }],
            chart: {
                type: 'area',
                height: 300,
                toolbar: { show: true }
            },
            colors: ['#4e73df'],
            dataLabels: { enabled: false },
            stroke: { curve: 'smooth', width: 2 },
            xaxis: {
                categories: cpuData.map(item => item.time),
                title: { text: 'Time' }
            },
            yaxis: {
                title: { text: 'CPU Usage (%)' },
                min: 0,
                max: 100
            },
            fill: { type: 'gradient' },
            tooltip: {
                y: { formatter: function(val) { return val + '%'; }}
            }
        };

        var cpuChart = new ApexCharts(document.querySelector("#cpuChart"), cpuOptions);
        cpuChart.render();
    @else
        document.getElementById('cpuChart').innerHTML = '<div class="text-center text-muted py-5"><p>No CPU data available</p></div>';
    @endif

    // Memory Chart
    @if(!empty($memoryHistory))
        var memoryData = @json($memoryHistory);
        
        var memoryOptions = {
            series: [{
                name: 'Memory Usage (%)',
                data: memoryData.map(item => item.memory_usage)
            }],
            chart: {
                type: 'line',
                height: 300,
                toolbar: { show: true }
            },
            colors: ['#1cc88a'],
            dataLabels: { enabled: false },
            stroke: { curve: 'smooth', width: 3 },
            xaxis: {
                categories: memoryData.map(item => item.time),
                title: { text: 'Time' }
            },
            yaxis: {
                title: { text: 'Memory Usage (%)' },
                min: 0,
                max: 100
            },
            tooltip: {
                y: { formatter: function(val) { return val + '%'; }}
            }
        };

        var memoryChart = new ApexCharts(document.querySelector("#memoryChart"), memoryOptions);
        memoryChart.render();
    @else
        document.getElementById('memoryChart').innerHTML = '<div class="text-center text-muted py-5"><p>No memory data available</p></div>';
    @endif

    // Memory Pie Chart
    @if(isset($systemResource['free-memory']) && isset($systemResource['total-memory']))
        @php
            $usedMemory = $systemResource['total-memory'] - $systemResource['free-memory'];
            $freeMemory = $systemResource['free-memory'];
        @endphp
        
        var memoryPieOptions = {
            series: [{{ round($usedMemory / 1024 / 1024, 0) }}, {{ round($freeMemory / 1024 / 1024, 0) }}],
            chart: { type: 'pie', height: 300 },
            labels: ['Used Memory', 'Free Memory'],
            colors: ['#e74c3c', '#2ecc71'],
            legend: { position: 'bottom' },
            tooltip: {
                y: { formatter: function(val) { return val + ' MB'; }}
            }
        };

        var memoryPieChart = new ApexCharts(document.querySelector("#memoryPieChart"), memoryPieOptions);
        memoryPieChart.render();
    @else
        document.getElementById('memoryPieChart').innerHTML = '<div class="text-center text-muted py-5"><p>No memory data available</p></div>';
    @endif
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
