@extends('layouts.admin')

@section('main-content')
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-thermometer-half text-danger"></i> Temperature Monitoring
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

    <!-- Temperature Cards -->
    <div class="row">
        @if(!empty($systemHealth))
            @foreach($systemHealth as $sensor)
                @if(isset($sensor['name']) && (strpos($sensor['name'], 'temperature') !== false || strpos($sensor['name'], 'temp') !== false))
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card shadow h-100 py-2 
                            @if(isset($sensor['value']))
                                @php
                                    $temp = (float) str_replace(['°C', 'C', '°'], '', $sensor['value']);
                                @endphp
                                @if($temp > 70)
                                    border-left-danger
                                @elseif($temp > 60)
                                    border-left-warning
                                @else
                                    border-left-success
                                @endif
                            @else
                                border-left-secondary
                            @endif
                        ">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-uppercase mb-1">
                                            {{ ucfirst(str_replace(['-', '_'], ' ', $sensor['name'])) }}
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold">
                                            {{ $sensor['value'] ?? 'N/A' }}
                                        </div>
                                        @if(isset($sensor['value']))
                                            <div class="mt-2">
                                                @if($temp > 70)
                                                    <span class="badge badge-danger">Critical</span>
                                                @elseif($temp > 60)
                                                    <span class="badge badge-warning">High</span>
                                                @elseif($temp > 40)
                                                    <span class="badge badge-success">Normal</span>
                                                @else
                                                    <span class="badge badge-info">Cool</span>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-thermometer-half fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            @endforeach
        @else
            <div class="col-12">
                <div class="card border-left-warning shadow h-100 py-2">
                    <div class="card-body text-center">
                        <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                        <h5>No Temperature Data Available</h5>
                        <p class="text-muted">Temperature sensors are not available on this device or access is restricted.</p>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Temperature Chart -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-line"></i> Temperature History (24 Hours)
                    </h6>
                </div>
                <div class="card-body">
                    <div id="temperatureChart" style="height: 400px;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Temperature Details -->
    <div class="row">
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-list"></i> Temperature Sensors
                    </h6>
                </div>
                <div class="card-body">
                    @if(!empty($systemHealth))
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Sensor Name</th>
                                        <th>Current Temperature</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($systemHealth as $sensor)
                                        @if(isset($sensor['name']) && (strpos($sensor['name'], 'temperature') !== false || strpos($sensor['name'], 'temp') !== false))
                                            <tr>
                                                <td>{{ ucfirst(str_replace(['-', '_'], ' ', $sensor['name'])) }}</td>
                                                <td>
                                                    <strong>{{ $sensor['value'] ?? 'N/A' }}</strong>
                                                </td>
                                                <td>
                                                    @if(isset($sensor['value']))
                                                        @php
                                                            $temp = (float) str_replace(['°C', 'C', '°'], '', $sensor['value']);
                                                        @endphp
                                                        @if($temp > 70)
                                                            <span class="badge badge-danger">Critical</span>
                                                        @elseif($temp > 60)
                                                            <span class="badge badge-warning">High</span>
                                                        @elseif($temp > 40)
                                                            <span class="badge badge-success">Normal</span>
                                                        @else
                                                            <span class="badge badge-info">Cool</span>
                                                        @endif
                                                    @else
                                                        <span class="badge badge-secondary">Unknown</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center text-muted">
                            <i class="fas fa-thermometer-empty fa-3x mb-3"></i>
                            <p>No temperature sensor data available</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-info-circle"></i> Temperature Guidelines
                    </h6>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h6><i class="fas fa-lightbulb"></i> Temperature Ranges:</h6>
                        <ul class="mb-0">
                            <li><span class="badge badge-info">0°C - 40°C</span> Cool - Optimal operating range</li>
                            <li><span class="badge badge-success">41°C - 60°C</span> Normal - Acceptable range</li>
                            <li><span class="badge badge-warning">61°C - 70°C</span> High - Monitor closely</li>
                            <li><span class="badge badge-danger">71°C+</span> Critical - Immediate attention required</li>
                        </ul>
                    </div>

                    <div class="alert alert-warning">
                        <h6><i class="fas fa-exclamation-triangle"></i> Important Notes:</h6>
                        <ul class="mb-0">
                            <li>High temperatures can cause hardware damage</li>
                            <li>Ensure proper ventilation and cooling</li>
                            <li>Clean dust from heat sinks regularly</li>
                            <li>Monitor temperature during high load periods</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('styles')
<!-- ApexCharts CSS -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
@endpush

@push('scripts')
<script>
// Temperature Chart
document.addEventListener('DOMContentLoaded', function() {
    @if(!empty($temperatureHistory))
        var temperatureData = @json($temperatureHistory);
        
        var options = {
            series: [{
                name: 'Temperature',
                data: temperatureData.map(item => item.temperature)
            }],
            chart: {
                type: 'line',
                height: 400,
                toolbar: {
                    show: true
                }
            },
            colors: ['#e74c3c'],
            dataLabels: {
                enabled: false
            },
            stroke: {
                curve: 'smooth',
                width: 2
            },
            xaxis: {
                categories: temperatureData.map(item => item.time),
                title: {
                    text: 'Time'
                }
            },
            yaxis: {
                title: {
                    text: 'Temperature (°C)'
                },
                min: 0,
                max: 100
            },
            grid: {
                borderColor: '#e0e6ed'
            },
            tooltip: {
                x: {
                    format: 'HH:mm'
                },
                y: {
                    formatter: function(val) {
                        return val + '°C';
                    }
                }
            },
            annotations: {
                yaxis: [
                    {
                        y: 60,
                        borderColor: '#f39c12',
                        label: {
                            text: 'High Temp Warning',
                            style: {
                                color: '#fff',
                                background: '#f39c12'
                            }
                        }
                    },
                    {
                        y: 70,
                        borderColor: '#e74c3c',
                        label: {
                            text: 'Critical Temp',
                            style: {
                                color: '#fff',
                                background: '#e74c3c'
                            }
                        }
                    }
                ]
            }
        };

        var chart = new ApexCharts(document.querySelector("#temperatureChart"), options);
        chart.render();
    @else
        document.getElementById('temperatureChart').innerHTML = 
            '<div class="text-center text-muted py-5">' +
            '<i class="fas fa-chart-line fa-3x mb-3"></i>' +
            '<p>No temperature history data available</p>' +
            '</div>';
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
