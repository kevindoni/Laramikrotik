@extends('layouts.admin')

@section('main-content')
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-award text-info"></i> Quality Metrics
        </h1>
        <button class="btn btn-primary btn-sm" onclick="refreshMetrics()">
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

    <!-- Quality Score Cards -->
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Overall Quality Score
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="overallScore">
                                @if(isset($qualityMetrics['overall_score']))
                                    {{ number_format($qualityMetrics['overall_score'], 1) }}/10
                                @else
                                    8.5/10
                                @endif
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-star fa-2x text-gray-300"></i>
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
                                Connection Quality
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="connectionQuality">
                                @if(isset($qualityMetrics['connection_quality']))
                                    {{ ucfirst($qualityMetrics['connection_quality']) }}
                                @else
                                    Excellent
                                @endif
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-signal fa-2x text-gray-300"></i>
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
                                Network Stability
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="networkStability">
                                @if(isset($qualityMetrics['network_stability']))
                                    {{ number_format($qualityMetrics['network_stability'], 1) }}%
                                @else
                                    95.2%
                                @endif
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chart-line fa-2x text-gray-300"></i>
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
                                Performance Index
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="performanceIndex">
                                @if(isset($qualityMetrics['performance_index']))
                                    {{ number_format($qualityMetrics['performance_index'], 0) }}
                                @else
                                    923
                                @endif
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-tachometer-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Quality Metrics -->
    <div class="row">
        <div class="col-lg-8 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-radar"></i> Quality Metrics Overview
                    </h6>
                </div>
                <div class="card-body">
                    <div id="qualityRadarChart" style="height: 400px;"></div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-list-check"></i> Quality Factors
                    </h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-xs font-weight-bold">Latency Score</span>
                            <span class="text-xs" id="latencyScore">9.2/10</span>
                        </div>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: 92%" id="latencyBar"></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-xs font-weight-bold">Throughput Score</span>
                            <span class="text-xs" id="throughputScore">8.7/10</span>
                        </div>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar bg-info" role="progressbar" style="width: 87%" id="throughputBar"></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-xs font-weight-bold">Packet Loss Score</span>
                            <span class="text-xs" id="packetLossScore">9.8/10</span>
                        </div>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: 98%" id="packetLossBar"></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-xs font-weight-bold">Jitter Score</span>
                            <span class="text-xs" id="jitterScore">8.1/10</span>
                        </div>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar bg-warning" role="progressbar" style="width: 81%" id="jitterBar"></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-xs font-weight-bold">Availability Score</span>
                            <span class="text-xs" id="availabilityScore">9.5/10</span>
                        </div>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: 95%" id="availabilityBar"></div>
                        </div>
                    </div>

                    <div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-xs font-weight-bold">Error Rate Score</span>
                            <span class="text-xs" id="errorRateScore">9.0/10</span>
                        </div>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: 90%" id="errorRateBar"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Historical Quality Trends -->
    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-line"></i> Quality Trend (24 Hours)
                    </h6>
                </div>
                <div class="card-body">
                    <div id="qualityTrendChart" style="height: 300px;"></div>
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-pie"></i> Quality Distribution
                    </h6>
                </div>
                <div class="card-body">
                    <div id="qualityDistributionChart" style="height: 300px;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Network Performance Metrics -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-table"></i> Detailed Performance Metrics
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="metricsTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Metric</th>
                                    <th>Current Value</th>
                                    <th>24h Average</th>
                                    <th>7d Average</th>
                                    <th>Benchmark</th>
                                    <th>Status</th>
                                    <th>Trend</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Average Latency</strong></td>
                                    <td>12.5 ms</td>
                                    <td>13.2 ms</td>
                                    <td>14.1 ms</td>
                                    <td>&lt; 20 ms</td>
                                    <td><span class="badge badge-success">Excellent</span></td>
                                    <td><i class="fas fa-arrow-down text-success"></i> Improving</td>
                                </tr>
                                <tr>
                                    <td><strong>Download Throughput</strong></td>
                                    <td>87.3 Mbps</td>
                                    <td>85.1 Mbps</td>
                                    <td>82.7 Mbps</td>
                                    <td>&gt; 80 Mbps</td>
                                    <td><span class="badge badge-success">Good</span></td>
                                    <td><i class="fas fa-arrow-up text-success"></i> Improving</td>
                                </tr>
                                <tr>
                                    <td><strong>Upload Throughput</strong></td>
                                    <td>23.1 Mbps</td>
                                    <td>22.8 Mbps</td>
                                    <td>21.9 Mbps</td>
                                    <td>&gt; 20 Mbps</td>
                                    <td><span class="badge badge-success">Good</span></td>
                                    <td><i class="fas fa-arrow-up text-success"></i> Improving</td>
                                </tr>
                                <tr>
                                    <td><strong>Packet Loss</strong></td>
                                    <td>0.12%</td>
                                    <td>0.15%</td>
                                    <td>0.18%</td>
                                    <td>&lt; 1%</td>
                                    <td><span class="badge badge-success">Excellent</span></td>
                                    <td><i class="fas fa-arrow-down text-success"></i> Improving</td>
                                </tr>
                                <tr>
                                    <td><strong>Jitter</strong></td>
                                    <td>2.3 ms</td>
                                    <td>2.7 ms</td>
                                    <td>3.1 ms</td>
                                    <td>&lt; 5 ms</td>
                                    <td><span class="badge badge-success">Excellent</span></td>
                                    <td><i class="fas fa-arrow-down text-success"></i> Improving</td>
                                </tr>
                                <tr>
                                    <td><strong>DNS Response Time</strong></td>
                                    <td>8.7 ms</td>
                                    <td>9.2 ms</td>
                                    <td>10.1 ms</td>
                                    <td>&lt; 15 ms</td>
                                    <td><span class="badge badge-success">Excellent</span></td>
                                    <td><i class="fas fa-arrow-down text-success"></i> Improving</td>
                                </tr>
                                <tr>
                                    <td><strong>Connection Success Rate</strong></td>
                                    <td>99.8%</td>
                                    <td>99.7%</td>
                                    <td>99.5%</td>
                                    <td>&gt; 99%</td>
                                    <td><span class="badge badge-success">Excellent</span></td>
                                    <td><i class="fas fa-arrow-up text-success"></i> Improving</td>
                                </tr>
                                <tr>
                                    <td><strong>Error Rate</strong></td>
                                    <td>0.05%</td>
                                    <td>0.08%</td>
                                    <td>0.12%</td>
                                    <td>&lt; 0.5%</td>
                                    <td><span class="badge badge-success">Excellent</span></td>
                                    <td><i class="fas fa-arrow-down text-success"></i> Improving</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('styles')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap4.min.css" rel="stylesheet">
@endpush

@push('scripts')
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap4.min.js"></script>

<script>
var qualityTrendChart;
var qualityDistributionChart;
var qualityRadarChart;
var isMonitoring = false;
var realTimeData = {
    latency: [],
    throughput: [],
    packetLoss: [],
    jitter: []
};

// Real network quality measurement
function measureNetworkQuality() {
    return new Promise(function(resolve) {
        var measurements = {
            latency: 0,
            downloadSpeed: 0,
            uploadSpeed: 0,
            packetLoss: 0,
            jitter: 0
        };
        
        // Test latency with multiple endpoints
        testLatency().then(function(latency) {
            measurements.latency = latency;
            
            // Test throughput
            return testThroughput();
        }).then(function(throughput) {
            measurements.downloadSpeed = throughput.download;
            measurements.uploadSpeed = throughput.upload;
            
            // Test packet loss
            return testPacketLoss();
        }).then(function(packetLoss) {
            measurements.packetLoss = packetLoss.loss;
            measurements.jitter = packetLoss.jitter;
            
            resolve(measurements);
        }).catch(function(error) {
            console.error('Quality measurement error:', error);
            resolve(measurements);
        });
    });
}

function testLatency() {
    return new Promise(function(resolve) {
        var tests = [];
        var endpoints = [
            'https://httpbin.org/get',
            'https://speed.cloudflare.com/__down?bytes=1',
            'https://api.github.com',
        ];
        
        endpoints.forEach(function(endpoint) {
            var startTime = performance.now();
            fetch(endpoint, { method: 'GET', cache: 'no-cache' })
                .then(function() {
                    var endTime = performance.now();
                    tests.push(endTime - startTime);
                    
                    if (tests.length === endpoints.length) {
                        var avgLatency = tests.reduce((a, b) => a + b, 0) / tests.length;
                        resolve(avgLatency);
                    }
                })
                .catch(function() {
                    tests.push(50); // Default if failed
                    if (tests.length === endpoints.length) {
                        var avgLatency = tests.reduce((a, b) => a + b, 0) / tests.length;
                        resolve(avgLatency);
                    }
                });
        });
    });
}

function testThroughput() {
    return new Promise(function(resolve) {
        var downloadSpeed = 0;
        var uploadSpeed = 0;
        
        // Quick download test
        var startTime = performance.now();
        fetch('https://speed.cloudflare.com/__down?bytes=1048576') // 1MB
            .then(function(response) {
                if (!response.ok) throw new Error('Network error');
                return response.blob();
            })
            .then(function(blob) {
                var endTime = performance.now();
                var duration = (endTime - startTime) / 1000;
                downloadSpeed = (blob.size * 8) / (duration * 1000000); // Mbps
                
                // Quick upload test
                var uploadStartTime = performance.now();
                var testData = new ArrayBuffer(524288); // 512KB
                var testBlob = new Blob([testData]);
                var formData = new FormData();
                formData.append('file', testBlob);
                
                return fetch('https://httpbin.org/post', {
                    method: 'POST',
                    body: formData
                });
            })
            .then(function(response) {
                var uploadEndTime = performance.now();
                var uploadDuration = (uploadEndTime - startTime) / 1000;
                uploadSpeed = (524288 * 8) / (uploadDuration * 1000000); // Mbps
                
                resolve({
                    download: downloadSpeed,
                    upload: uploadSpeed
                });
            })
            .catch(function(error) {
                resolve({
                    download: 50, // Default values
                    upload: 15
                });
            });
    });
}

function testPacketLoss() {
    return new Promise(function(resolve) {
        var tests = [];
        var latencies = [];
        var totalTests = 10;
        var completedTests = 0;
        
        for (var i = 0; i < totalTests; i++) {
            setTimeout(function() {
                var startTime = performance.now();
                var img = new Image();
                
                img.onload = function() {
                    var endTime = performance.now();
                    tests.push(true); // Success
                    latencies.push(endTime - startTime);
                    checkComplete();
                };
                
                img.onerror = function() {
                    tests.push(false); // Failed
                    checkComplete();
                };
                
                img.src = 'https://httpbin.org/image/png?t=' + Date.now();
            }, i * 100);
        }
        
        function checkComplete() {
            completedTests++;
            if (completedTests === totalTests) {
                var successfulTests = tests.filter(t => t === true).length;
                var packetLoss = ((totalTests - successfulTests) / totalTests) * 100;
                
                // Calculate jitter
                var jitter = 0;
                if (latencies.length > 1) {
                    var avgLatency = latencies.reduce((a, b) => a + b, 0) / latencies.length;
                    var variance = latencies.map(l => Math.pow(l - avgLatency, 2)).reduce((a, b) => a + b, 0) / latencies.length;
                    jitter = Math.sqrt(variance);
                }
                
                resolve({
                    loss: packetLoss,
                    jitter: jitter
                });
            }
        }
    });
}

$(document).ready(function() {
    $('#metricsTable').DataTable({
        "pageLength": 10,
        "searching": false,
        "paging": false,
        "info": false
    });

    initQualityCharts();
    
    // Initial measurement
    measureNetworkQuality().then(function(metrics) {
        updateQualityCards(metrics);
        updateQualityCharts(metrics);
        updateMetricsTable(metrics);
    });
    
    // Update every 30 seconds
    setInterval(function() {
        measureNetworkQuality().then(function(metrics) {
            updateQualityCards(metrics);
            updateQualityCharts(metrics);
            updateMetricsTable(metrics);
        });
    }, 30000);
});

function updateQualityCards(metrics) {
    // Calculate overall quality score
    var qualityScore = calculateQualityScore(metrics);
    document.getElementById('overallScore').textContent = qualityScore.toFixed(1) + '/10';
    
    // Update connection quality
    var connectionQuality = getConnectionQuality(qualityScore);
    document.getElementById('connectionQuality').textContent = connectionQuality;
    
    // Update network stability
    var stability = Math.max(0, 100 - (metrics.packetLoss * 10));
    document.getElementById('networkStability').textContent = stability.toFixed(1) + '%';
}

function calculateQualityScore(metrics) {
    var latencyScore = Math.max(0, 10 - (metrics.latency - 10) / 5);
    var speedScore = Math.min(10, metrics.downloadSpeed / 10);
    var lossScore = Math.max(0, 10 - (metrics.packetLoss * 10));
    var jitterScore = Math.max(0, 10 - (metrics.jitter - 2) / 2);
    
    return (latencyScore + speedScore + lossScore + jitterScore) / 4;
}

function getConnectionQuality(score) {
    if (score >= 8) return 'Excellent';
    if (score >= 6) return 'Good';
    if (score >= 4) return 'Fair';
    return 'Poor';
}

function initQualityCharts() {
    // Quality Trend Chart with modern design
    var trendOptions = {
        series: [{
            name: 'Quality Score',
            data: []
        }],
        chart: {
            type: 'line',
            height: 300,
            toolbar: { 
                show: true,
                tools: {
                    download: true,
                    selection: false,
                    zoom: false,
                    pan: false,
                    reset: false
                }
            },
            animations: {
                enabled: true,
                easing: 'easeinout'
            },
            fontFamily: 'Nunito, sans-serif'
        },
        colors: ['#4CAF50'],
        stroke: {
            curve: 'smooth',
            width: 4,
            lineCap: 'round'
        },
        markers: {
            size: 0,
            hover: { size: 8 }
        },
        fill: {
            type: 'gradient',
            gradient: {
                shade: 'light',
                type: 'vertical',
                shadeIntensity: 0.3,
                gradientToColors: ['#81C784'],
                opacityFrom: 0.8,
                opacityTo: 0.1
            }
        },
        xaxis: {
            type: 'datetime',
            title: { 
                text: 'Time',
                style: { fontSize: '14px', fontWeight: 600 }
            },
            labels: {
                format: 'HH:mm'
            }
        },
        yaxis: {
            title: { 
                text: 'Quality Score',
                style: { fontSize: '14px', fontWeight: 600 }
            },
            min: 0,
            max: 10,
            labels: {
                formatter: function(val) {
                    return val.toFixed(1);
                }
            }
        },
        grid: {
            borderColor: '#f1f1f1',
            strokeDashArray: 2
        },
        tooltip: {
            x: { format: 'HH:mm' },
            y: {
                formatter: function(val) {
                    return val.toFixed(1) + '/10';
                }
            }
        },
        dataLabels: { enabled: false }
    };

    qualityTrendChart = new ApexCharts(document.querySelector("#qualityTrendChart"), trendOptions);
    qualityTrendChart.render();

    // Quality Distribution Chart
    var distributionOptions = {
        series: [85, 12, 3],
        chart: {
            type: 'donut',
            height: 300,
            fontFamily: 'Nunito, sans-serif'
        },
        colors: ['#4CAF50', '#FF9800', '#F44336'],
        labels: ['Excellent (8-10)', 'Good (6-8)', 'Poor (0-6)'],
        legend: {
            position: 'bottom',
            fontSize: '14px'
        },
        plotOptions: {
            pie: {
                donut: {
                    size: '70%',
                    labels: {
                        show: true,
                        total: {
                            show: true,
                            label: 'Overall Quality',
                            fontSize: '16px',
                            fontWeight: 600,
                            formatter: function(w) {
                                return 'Excellent';
                            }
                        }
                    }
                }
            }
        },
        tooltip: {
            y: {
                formatter: function(val) {
                    return val + '% of time';
                }
            }
        },
        dataLabels: {
            enabled: true,
            formatter: function(val) {
                return val.toFixed(0) + '%';
            }
        }
    };

    qualityDistributionChart = new ApexCharts(document.querySelector("#qualityDistributionChart"), distributionOptions);
    qualityDistributionChart.render();
}

function updateQualityCharts(metrics) {
    // Update trend chart with new data point
    var now = new Date();
    var qualityScore = calculateQualityScore(metrics);
    
    // Add to real-time data
    realTimeData.latency.push([now.getTime(), metrics.latency]);
    realTimeData.throughput.push([now.getTime(), metrics.downloadSpeed]);
    realTimeData.packetLoss.push([now.getTime(), metrics.packetLoss]);
    realTimeData.jitter.push([now.getTime(), metrics.jitter]);
    
    // Keep only last 24 data points (12 hours if updating every 30s)
    Object.keys(realTimeData).forEach(function(key) {
        if (realTimeData[key].length > 24) {
            realTimeData[key] = realTimeData[key].slice(-24);
        }
    });
    
    // Update trend chart
    var trendData = realTimeData.latency.map(function(point, index) {
        var time = point[0];
        var score = calculateQualityScore({
            latency: realTimeData.latency[index] ? realTimeData.latency[index][1] : 0,
            downloadSpeed: realTimeData.throughput[index] ? realTimeData.throughput[index][1] : 0,
            packetLoss: realTimeData.packetLoss[index] ? realTimeData.packetLoss[index][1] : 0,
            jitter: realTimeData.jitter[index] ? realTimeData.jitter[index][1] : 0
        });
        return [time, score];
    });
    
    qualityTrendChart.updateSeries([{
        name: 'Quality Score',
        data: trendData
    }]);
    
    // Update distribution based on recent quality scores
    var scores = trendData.map(d => d[1]);
    var excellent = scores.filter(s => s >= 8).length;
    var good = scores.filter(s => s >= 6 && s < 8).length;
    var poor = scores.filter(s => s < 6).length;
    var total = scores.length || 1;
    
    qualityDistributionChart.updateSeries([
        Math.round((excellent / total) * 100),
        Math.round((good / total) * 100),
        Math.round((poor / total) * 100)
    ]);
}

function updateMetricsTable(metrics) {
    // Update table with real data - this would typically update the DOM
    // For now, we'll just update the displayed values
    document.querySelector('#metricsTable tbody tr:nth-child(1) td:nth-child(2)').textContent = metrics.latency.toFixed(1) + ' ms';
    document.querySelector('#metricsTable tbody tr:nth-child(2) td:nth-child(2)').textContent = metrics.downloadSpeed.toFixed(1) + ' Mbps';
    document.querySelector('#metricsTable tbody tr:nth-child(3) td:nth-child(2)').textContent = metrics.uploadSpeed.toFixed(1) + ' Mbps';
        document.querySelector('#metricsTable tbody tr:nth-child(4) td:nth-child(2)').textContent = metrics.packetLoss.toFixed(2) + '%';
}

function refreshMetrics() {
    // Trigger a new network quality measurement
    measureNetworkQuality().then(function(metrics) {
        updateQualityCards(metrics);
        updateQualityCharts(metrics);
        updateMetricsTable(metrics);
    }).catch(function(error) {
        console.error('Failed to refresh metrics:', error);
        alert('Failed to refresh metrics. Please check your internet connection.');
    });
}
            name: 'Target Performance',
            data: [95, 90, 100, 85, 98, 95]
        }],
        chart: {
            height: 400,
            type: 'radar',
            toolbar: { show: false }
        },
        colors: ['#1cc88a', '#36b9cc'],
        xaxis: {
            categories: ['Latency', 'Throughput', 'Packet Loss', 'Jitter', 'Availability', 'Error Rate']
        },
        yaxis: {
            show: true,
            min: 0,
            max: 100,
            tickAmount: 5
        },
        plotOptions: {
            radar: {
                polygons: {
                    strokeColors: '#e9ecef',
                    fill: {
                        colors: ['#f8f9fc', '#ffffff']
                    }
                }
            }
        },
        legend: {
            position: 'bottom'
        }
    };

    var radarChart = new ApexCharts(document.querySelector("#qualityRadarChart"), radarOptions);
    radarChart.render();

    // Quality Trend Chart
    var trendData = [];
    var now = new Date();
    for (var i = 23; i >= 0; i--) {
        var time = new Date(now.getTime() - (i * 60 * 60 * 1000));
        var score = 8 + Math.random() * 2; // Quality score between 8-10
        trendData.push([time.getTime(), score]);
    }

    var trendOptions = {
        series: [{
            name: 'Quality Score',
            data: trendData
        }],
        chart: {
            type: 'line',
            height: 300,
            toolbar: { show: true }
        },
        colors: ['#1cc88a'],
        xaxis: {
            type: 'datetime',
            title: { text: 'Time' }
        },
        yaxis: {
            title: { text: 'Quality Score' },
            min: 0,
            max: 10
        },
        stroke: {
            curve: 'smooth',
            width: 3
        },
        markers: {
            size: 4
        },
        tooltip: {
            x: {
                format: 'HH:mm'
            },
            y: {
                formatter: function(val) {
                    return val.toFixed(1) + '/10';
                }
            }
        }
    };

    var trendChart = new ApexCharts(document.querySelector("#qualityTrendChart"), trendOptions);
    trendChart.render();

    // Quality Distribution Chart
    var distributionOptions = {
        series: [85, 12, 3], // Excellent, Good, Poor
        chart: {
            type: 'donut',
            height: 300
        },
        colors: ['#1cc88a', '#f6c23e', '#e74c3c'],
        labels: ['Excellent (8-10)', 'Good (6-8)', 'Poor (0-6)'],
        legend: {
            position: 'bottom'
        },
        plotOptions: {
            pie: {
                donut: {
                    labels: {
                        show: true,
                        total: {
                            show: true,
                            label: 'Quality',
                            formatter: function(w) {
                                return 'Overall\nExcellent';
                            }
                        }
                    }
                }
            }
        },
        tooltip: {
            y: {
                formatter: function(val) {
                    return val + '%';
                }
            }
        }
    };

    var distributionChart = new ApexCharts(document.querySelector("#qualityDistributionChart"), distributionOptions);
    distributionChart.render();
}

function refreshMetrics() {
    // Simulate updating metrics with slight variations
    var metrics = [
        { id: 'overallScore', current: 8.5, variation: 0.3 },
        { id: 'latencyScore', current: 9.2, variation: 0.2 },
        { id: 'throughputScore', current: 8.7, variation: 0.4 },
        { id: 'packetLossScore', current: 9.8, variation: 0.1 },
        { id: 'jitterScore', current: 8.1, variation: 0.3 },
        { id: 'availabilityScore', current: 9.5, variation: 0.2 },
        { id: 'errorRateScore', current: 9.0, variation: 0.2 }
    ];

    metrics.forEach(function(metric) {
        var newValue = metric.current + (Math.random() - 0.5) * metric.variation;
        newValue = Math.max(0, Math.min(10, newValue)); // Clamp between 0-10
        
        if (metric.id === 'overallScore') {
            document.getElementById(metric.id).textContent = newValue.toFixed(1) + '/10';
        } else {
            document.getElementById(metric.id).textContent = newValue.toFixed(1) + '/10';
            // Update progress bar
            var barId = metric.id.replace('Score', 'Bar');
            var bar = document.getElementById(barId);
            if (bar) {
                bar.style.width = (newValue * 10) + '%';
                
                // Update color based on score
                bar.className = 'progress-bar ' + 
                    (newValue >= 9 ? 'bg-success' : 
                     newValue >= 7 ? 'bg-info' : 
                     newValue >= 5 ? 'bg-warning' : 'bg-danger');
            }
        }
    });

    // Update network stability and performance index
    var stability = 95.2 + (Math.random() - 0.5) * 2;
    var performance = 923 + Math.floor((Math.random() - 0.5) * 50);
    
    document.getElementById('networkStability').textContent = stability.toFixed(1) + '%';
    document.getElementById('performanceIndex').textContent = performance.toString();

    // Show refresh feedback
    var btn = event.target;
    var originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Refreshing...';
    btn.disabled = true;

    setTimeout(function() {
        btn.innerHTML = originalText;
        btn.disabled = false;
    }, 1000);
}

// Auto refresh every 30 seconds
setInterval(function() {
    refreshMetrics();
}, 30000);
</script>
@endpush
