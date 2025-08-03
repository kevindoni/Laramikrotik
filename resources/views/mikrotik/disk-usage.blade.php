@extends('layouts.admin')

@section('main-content')
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-hdd text-warning"></i> Disk Usage Monitoring
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

    <!-- Disk Summary Cards -->
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Storage
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ isset($systemResource['total-hdd-space']) ? number_format($systemResource['total-hdd-space'] / 1024 / 1024, 0) . ' MB' : 'N/A' }}
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
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Free Space
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ isset($systemResource['free-hdd-space']) ? number_format($systemResource['free-hdd-space'] / 1024 / 1024, 0) . ' MB' : 'N/A' }}
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
                                Used Space
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                @if(isset($systemResource['total-hdd-space']) && isset($systemResource['free-hdd-space']))
                                    @php
                                        $usedSpace = $systemResource['total-hdd-space'] - $systemResource['free-hdd-space'];
                                    @endphp
                                    {{ number_format($usedSpace / 1024 / 1024, 0) }} MB
                                @else
                                    N/A
                                @endif
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
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
                                Usage Percentage
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                @if(isset($systemResource['total-hdd-space']) && isset($systemResource['free-hdd-space']))
                                    @php
                                        $usedSpace = $systemResource['total-hdd-space'] - $systemResource['free-hdd-space'];
                                        $usagePercent = round(($usedSpace / $systemResource['total-hdd-space']) * 100, 1);
                                    @endphp
                                    {{ $usagePercent }}%
                                @else
                                    N/A
                                @endif
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

    <!-- Disk Usage Chart -->
    <div class="row">
        <div class="col-lg-8 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-bar"></i> Disk Usage Overview
                    </h6>
                </div>
                <div class="card-body">
                    @if(isset($systemResource['total-hdd-space']) && isset($systemResource['free-hdd-space']))
                        @php
                            $totalSpace = $systemResource['total-hdd-space'] / 1024 / 1024; // Convert to MB
                            $freeSpace = $systemResource['free-hdd-space'] / 1024 / 1024;
                            $usedSpace = $totalSpace - $freeSpace;
                            $usagePercent = round(($usedSpace / $totalSpace) * 100, 1);
                        @endphp
                        
                        <div class="mb-3">
                            <div class="d-flex justify-content-between">
                                <span><strong>Storage Usage</strong></span>
                                <span>{{ number_format($usedSpace, 0) }} MB / {{ number_format($totalSpace, 0) }} MB</span>
                            </div>
                            <div class="progress" style="height: 25px;">
                                <div class="progress-bar 
                                    @if($usagePercent > 90) bg-danger
                                    @elseif($usagePercent > 75) bg-warning
                                    @else bg-success
                                    @endif
                                " role="progressbar" style="width: {{ $usagePercent }}%">
                                    {{ $usagePercent }}%
                                </div>
                            </div>
                        </div>

                        <div id="diskChart" style="height: 300px;"></div>
                    @else
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-exclamation-circle fa-3x mb-3"></i>
                            <p>No disk usage data available</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-info-circle"></i> Storage Information
                    </h6>
                </div>
                <div class="card-body">
                    @if(!empty($systemResource))
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>Total Space:</strong></td>
                                    <td>{{ isset($systemResource['total-hdd-space']) ? number_format($systemResource['total-hdd-space'] / 1024 / 1024, 0) . ' MB' : 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Free Space:</strong></td>
                                    <td>{{ isset($systemResource['free-hdd-space']) ? number_format($systemResource['free-hdd-space'] / 1024 / 1024, 0) . ' MB' : 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Used Space:</strong></td>
                                    <td>
                                        @if(isset($systemResource['total-hdd-space']) && isset($systemResource['free-hdd-space']))
                                            {{ number_format(($systemResource['total-hdd-space'] - $systemResource['free-hdd-space']) / 1024 / 1024, 0) }} MB
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Usage:</strong></td>
                                    <td>
                                        @if(isset($systemResource['total-hdd-space']) && isset($systemResource['free-hdd-space']))
                                            @php
                                                $usagePercent = round((($systemResource['total-hdd-space'] - $systemResource['free-hdd-space']) / $systemResource['total-hdd-space']) * 100, 1);
                                            @endphp
                                            <span class="badge 
                                                @if($usagePercent > 90) badge-danger
                                                @elseif($usagePercent > 75) badge-warning
                                                @else badge-success
                                                @endif
                                            ">{{ $usagePercent }}%</span>
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                    @endif

                    <div class="alert alert-info mt-3">
                        <h6><i class="fas fa-lightbulb"></i> Storage Tips:</h6>
                        <ul class="mb-0 small">
                            <li>Monitor disk usage regularly</li>
                            <li>Clean old log files periodically</li>
                            <li>Remove unnecessary files</li>
                            <li>Consider storage expansion if needed</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Disk Details Table -->
    @if(!empty($diskInfo))
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-list"></i> Detailed Disk Information
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Disk</th>
                                    <th>Size</th>
                                    <th>Used</th>
                                    <th>Free</th>
                                    <th>Usage %</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($diskInfo as $disk)
                                    <tr>
                                        <td><strong>{{ $disk['name'] ?? 'Unknown' }}</strong></td>
                                        <td>{{ isset($disk['size']) ? number_format($disk['size'] / 1024 / 1024, 0) . ' MB' : 'N/A' }}</td>
                                        <td>{{ isset($disk['used']) ? number_format($disk['used'] / 1024 / 1024, 0) . ' MB' : 'N/A' }}</td>
                                        <td>{{ isset($disk['free']) ? number_format($disk['free'] / 1024 / 1024, 0) . ' MB' : 'N/A' }}</td>
                                        <td>
                                            @if(isset($disk['used']) && isset($disk['size']) && $disk['size'] > 0)
                                                @php $diskUsage = round(($disk['used'] / $disk['size']) * 100, 1); @endphp
                                                <span class="badge 
                                                    @if($diskUsage > 90) badge-danger
                                                    @elseif($diskUsage > 75) badge-warning
                                                    @else badge-success
                                                    @endif
                                                ">{{ $diskUsage }}%</span>
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                        <td>
                                            @if(isset($disk['status']))
                                                <span class="badge badge-success">{{ $disk['status'] }}</span>
                                            @else
                                                <span class="badge badge-secondary">Unknown</span>
                                            @endif
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

@push('styles')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    @if(isset($systemResource['total-hdd-space']) && isset($systemResource['free-hdd-space']))
        @php
            $totalSpace = round($systemResource['total-hdd-space'] / 1024 / 1024, 0);
            $freeSpace = round($systemResource['free-hdd-space'] / 1024 / 1024, 0);
            $usedSpace = $totalSpace - $freeSpace;
        @endphp

        var diskOptions = {
            series: [{{ $usedSpace }}, {{ $freeSpace }}],
            chart: {
                type: 'donut',
                height: 300
            },
            labels: ['Used Space', 'Free Space'],
            colors: ['#e74c3c', '#2ecc71'],
            legend: {
                position: 'bottom'
            },
            tooltip: {
                y: {
                    formatter: function(val) {
                        return val + ' MB';
                    }
                }
            },
            plotOptions: {
                pie: {
                    donut: {
                        size: '70%',
                        labels: {
                            show: true,
                            total: {
                                show: true,
                                label: 'Total Storage',
                                formatter: function() {
                                    return '{{ $totalSpace }} MB';
                                }
                            }
                        }
                    }
                }
            }
        };

        var diskChart = new ApexCharts(document.querySelector("#diskChart"), diskOptions);
        diskChart.render();
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
