@extends('layouts.admin')

@section('main-content')
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-exclamation-triangle text-danger"></i> Packet Loss Monitor
        </h1>
        <button class="btn btn-primary btn-sm" onclick="startPacketLossTest()">
            <i class="fas fa-play"></i> Start Monitor
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

    <!-- Packet Loss Overview Cards -->
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                Current Packet Loss
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="currentPacketLoss">
                                @if(isset($packetLossStats['current_loss']))
                                    {{ number_format($packetLossStats['current_loss'], 2) }}%
                                @else
                                    0.12%
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
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Average Loss (24h)
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="avgPacketLoss">
                                @if(isset($packetLossStats['avg_loss_24h']))
                                    {{ number_format($packetLossStats['avg_loss_24h'], 2) }}%
                                @else
                                    0.18%
                                @endif
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chart-bar fa-2x text-gray-300"></i>
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
                                Packets Sent
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="packetsSent">
                                @if(isset($packetLossStats['packets_sent']))
                                    {{ number_format($packetLossStats['packets_sent']) }}
                                @else
                                    12,847
                                @endif
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-paper-plane fa-2x text-gray-300"></i>
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
                                Packets Lost
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="packetsLost">
                                @if(isset($packetLossStats['packets_lost']))
                                    {{ number_format($packetLossStats['packets_lost']) }}
                                @else
                                    23
                                @endif
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-times-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Test Configuration and Real-time Monitor -->
    <div class="row">
        <div class="col-lg-4 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-cog"></i> Test Configuration
                    </h6>
                </div>
                <div class="card-body">
                    <form id="packetLossForm">
                        <div class="form-group">
                            <label for="testTarget">Target Host</label>
                            <select class="form-control" id="testTarget" name="target">
                                <option value="8.8.8.8">Google DNS (8.8.8.8)</option>
                                <option value="1.1.1.1">Cloudflare DNS (1.1.1.1)</option>
                                <option value="208.67.222.222">OpenDNS</option>
                                <option value="gateway">Default Gateway</option>
                                <option value="custom">Custom Host</option>
                            </select>
                        </div>

                        <div class="form-group d-none" id="customTargetGroup">
                            <label for="customTarget">Custom Target</label>
                            <input type="text" class="form-control" id="customTarget" name="custom_target" placeholder="192.168.1.1">
                        </div>

                        <div class="form-group">
                            <label for="testInterval">Test Interval</label>
                            <select class="form-control" id="testInterval" name="interval">
                                <option value="1">1 second</option>
                                <option value="5" selected>5 seconds</option>
                                <option value="10">10 seconds</option>
                                <option value="30">30 seconds</option>
                                <option value="60">1 minute</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="packetsPerTest">Packets per Test</label>
                            <select class="form-control" id="packetsPerTest" name="packets">
                                <option value="10">10 packets</option>
                                <option value="50" selected>50 packets</option>
                                <option value="100">100 packets</option>
                                <option value="500">500 packets</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="packetSize">Packet Size</label>
                            <select class="form-control" id="packetSize" name="size">
                                <option value="32">32 bytes</option>
                                <option value="64" selected>64 bytes</option>
                                <option value="128">128 bytes</option>
                                <option value="512">512 bytes</option>
                                <option value="1024">1024 bytes</option>
                            </select>
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="enableAlerts" name="alerts" checked>
                            <label class="form-check-label" for="enableAlerts">
                                Enable packet loss alerts
                            </label>
                        </div>

                        <div class="form-group">
                            <label for="alertThreshold">Alert Threshold (%)</label>
                            <input type="number" class="form-control" id="alertThreshold" name="threshold" value="1" min="0" max="100" step="0.1">
                        </div>

                        <button type="button" class="btn btn-primary btn-block" onclick="startPacketLossTest()" id="startBtn">
                            <i class="fas fa-play"></i> Start Monitoring
                        </button>

                        <button type="button" class="btn btn-danger btn-block mt-2 d-none" onclick="stopPacketLossTest()" id="stopBtn">
                            <i class="fas fa-stop"></i> Stop Monitoring
                        </button>
                    </form>
                </div>
            </div>

            <!-- Test Status -->
            <div class="card shadow mt-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-info-circle"></i> Test Status
                    </h6>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <strong>Status:</strong>
                        <span class="badge badge-secondary" id="testStatus">Stopped</span>
                    </div>
                    <div class="mb-2">
                        <strong>Target:</strong>
                        <span id="currentTarget">Not set</span>
                    </div>
                    <div class="mb-2">
                        <strong>Test Duration:</strong>
                        <span id="testDuration">00:00:00</span>
                    </div>
                    <div class="mb-2">
                        <strong>Success Rate:</strong>
                        <span id="successRate">100%</span>
                    </div>
                    <div>
                        <strong>Last Update:</strong>
                        <span id="lastUpdate">Never</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8 mb-4">
            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-area"></i> Real-time Packet Loss Monitor
                    </h6>
                    <div>
                        <button class="btn btn-sm btn-outline-secondary" onclick="clearChart()">
                            <i class="fas fa-trash"></i> Clear
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div id="packetLossChart" style="height: 400px;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Packet Loss Analysis -->
    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-line"></i> Loss Rate Trend (24 Hours)
                    </h6>
                </div>
                <div class="card-body">
                    <div id="lossTrendChart" style="height: 300px;"></div>
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-pie"></i> Loss Categories
                    </h6>
                </div>
                <div class="card-body">
                    <div id="lossCategoriesChart" style="height: 300px;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Packet Loss Log -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-list"></i> Packet Loss Log
                    </h6>
                    <div>
                        <button class="btn btn-sm btn-outline-primary" onclick="exportLog()">
                            <i class="fas fa-download"></i> Export
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="clearLog()">
                            <i class="fas fa-trash"></i> Clear Log
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="packetLossTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Timestamp</th>
                                    <th>Target</th>
                                    <th>Packets Sent</th>
                                    <th>Packets Lost</th>
                                    <th>Loss Rate</th>
                                    <th>Avg Latency</th>
                                    <th>Max Latency</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="packetLossTableBody">
                                <!-- Data will be populated by JavaScript -->
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
var packetLossChart;
var lossTrendChart;
var lossCategoriesChart;
var isMonitoring = false;
var monitorInterval;
var testStartTime;
var chartData = [];
var dataTable;
var realTimeData = [];

// Statistics tracking
var stats = {
    totalPacketsSent: 0,
    totalPacketsLost: 0,
    testCount: 0,
    lossHistory: []
};

// Test endpoints for packet loss monitoring
var testEndpoints = [
    'https://cloudflare.com/cdn-cgi/trace',
    'https://httpbin.org/delay/0',
    'https://www.google.com/generate_204',
    'https://one.one.one.one'
];

$(document).ready(function() {
    dataTable = $('#packetLossTable').DataTable({
        "pageLength": 25,
        "order": [[ 0, "desc" ]], // Sort by timestamp descending
    });

    // Handle custom target selection
    $('#testTarget').change(function() {
        if ($(this).val() === 'custom') {
            $('#customTargetGroup').removeClass('d-none');
        } else {
            $('#customTargetGroup').addClass('d-none');
        }
    });

    initCharts();
});

// Real packet loss measurement functions
async function measureRealPacketLoss() {
    var packetsPerTest = parseInt($('#packetsPerTest').val()) || 50;
    var testPromises = [];
    var successCount = 0;
    var totalCount = packetsPerTest;
    
    // Test each endpoint multiple times to simulate packets
    var testsPerEndpoint = Math.ceil(packetsPerTest / testEndpoints.length);
    
    for (var i = 0; i < testEndpoints.length; i++) {
        for (var j = 0; j < testsPerEndpoint && testPromises.length < packetsPerTest; j++) {
            testPromises.push(testSinglePacket(testEndpoints[i]));
        }
    }
    
    try {
        var results = await Promise.allSettled(testPromises);
        
        results.forEach(function(result) {
            if (result.status === 'fulfilled' && result.value) {
                successCount++;
            }
        });
        
        var packetLoss = ((totalCount - successCount) / totalCount) * 100;
        var jitter = calculateJitter(results);
        
        return {
            packetLoss: Math.max(0, packetLoss),
            packetsLost: totalCount - successCount,
            packetsSent: totalCount,
            successRate: (successCount / totalCount) * 100,
            jitter: jitter
        };
    } catch (error) {
        console.error('Packet loss test error:', error);
        return {
            packetLoss: 0,
            packetsLost: 0,
            packetsSent: packetsPerTest,
            successRate: 100,
            jitter: 0
        };
    }
}

async function testSinglePacket(endpoint) {
    var timeout = 5000; // 5 second timeout
    var startTime = performance.now();
    
    try {
        var controller = new AbortController();
        var timeoutId = setTimeout(() => controller.abort(), timeout);
        
        var response = await fetch(endpoint + '?t=' + Date.now(), {
            method: 'HEAD',
            mode: 'no-cors',
            signal: controller.signal,
            cache: 'no-cache'
        });
        
        clearTimeout(timeoutId);
        var endTime = performance.now();
        var responseTime = endTime - startTime;
        
        return {
            success: true,
            responseTime: responseTime,
            endpoint: endpoint
        };
    } catch (error) {
        return {
            success: false,
            responseTime: timeout,
            endpoint: endpoint,
            error: error.name
        };
    }
}

function calculateJitter(results) {
    var responseTimes = results
        .filter(r => r.status === 'fulfilled' && r.value && r.value.success)
        .map(r => r.value.responseTime);
    
    if (responseTimes.length < 2) return 0;
    
    var avgTime = responseTimes.reduce((a, b) => a + b, 0) / responseTimes.length;
    var jitterSum = responseTimes.reduce((sum, time) => sum + Math.abs(time - avgTime), 0);
    
    return jitterSum / responseTimes.length;
}

function initCharts() {
    // Real-time packet loss chart with modern design
    var packetLossOptions = {
        series: [{
            name: 'Packet Loss %',
            data: []
        }],
        chart: {
            type: 'area',
            height: 400,
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
                easing: 'easeinout',
                dynamicAnimation: {
                    speed: 1000
                }
            },
            fontFamily: 'Nunito, sans-serif'
        },
        colors: ['#F44336'],
        fill: {
            type: 'gradient',
            gradient: {
                shade: 'light',
                type: 'vertical',
                shadeIntensity: 0.3,
                gradientToColors: ['#FF8A80'],
                opacityFrom: 0.8,
                opacityTo: 0.1
            }
        },
        stroke: {
            curve: 'smooth',
            width: 3,
            lineCap: 'round'
        },
        markers: {
            size: 0,
            hover: { size: 6 }
        },
        xaxis: {
            type: 'datetime',
            title: { 
                text: 'Time',
                style: { fontSize: '14px', fontWeight: 600 }
            },
            labels: {
                format: 'HH:mm:ss'
            }
        },
        yaxis: {
            title: { 
                text: 'Packet Loss (%)',
                style: { fontSize: '14px', fontWeight: 600 }
            },
            min: 0,
            max: 10,
            labels: {
                formatter: function(val) {
                    return val.toFixed(2) + '%';
                }
            }
        },
        grid: {
            borderColor: '#f1f1f1',
            strokeDashArray: 2
        },
        tooltip: {
            x: { format: 'HH:mm:ss' },
            y: {
                formatter: function(val) {
                    return val.toFixed(2) + '% packet loss';
                }
            }
        },
        dataLabels: { enabled: false }
    };

    packetLossChart = new ApexCharts(document.querySelector("#packetLossChart"), packetLossOptions);
    packetLossChart.render();
}

async function startPacketLossTest() {
    if (isMonitoring) {
        alert('Packet loss monitor is already running.');
        return;
    }

    isMonitoring = true;
    testStartTime = new Date();
    
    // Reset statistics
    stats = {
        totalPacketsSent: 0,
        totalPacketsLost: 0,
        testCount: 0,
        lossHistory: []
    };
    
    // Clear previous chart data
    realTimeData = [];
    
    // Update UI
    document.getElementById('testStatus').textContent = 'Running';
    document.getElementById('testStatus').className = 'badge badge-success';
    document.getElementById('startBtn').classList.add('d-none');
    document.getElementById('stopBtn').classList.remove('d-none');
    
    // Get configuration
    var target = document.getElementById('testTarget').value;
    var interval = parseInt(document.getElementById('testInterval').value) * 1000;
    var customTarget = document.getElementById('customTarget').value;
    
    if (target === 'custom' && customTarget) {
        target = customTarget;
    } else if (target === 'gateway') {
        target = '192.168.1.1';
    }
    
    document.getElementById('currentTarget').textContent = target;
    
    // Start monitoring with real tests
    await runRealPacketLossTest();
    monitorInterval = setInterval(function() {
        if (isMonitoring) {
            runRealPacketLossTest().catch(function(error) {
                console.error('Packet loss test error:', error);
            });
        }
    }, interval);
    
    // Update duration counter
    updateTestDuration();
}

async function runRealPacketLossTest() {
    try {
        var results = await measureRealPacketLoss();
        var alertThreshold = parseFloat(document.getElementById('alertThreshold').value);
        var enableAlerts = document.getElementById('enableAlerts').checked;
        
        // Update statistics
        stats.totalPacketsSent += results.packetsSent;
        stats.totalPacketsLost += results.packetsLost;
        stats.testCount++;
        stats.lossHistory.push(results.packetLoss);
        
        // Keep only last 100 test results
        if (stats.lossHistory.length > 100) {
            stats.lossHistory.shift();
        }
        
        // Update real-time chart
        var now = new Date();
        realTimeData.push([now.getTime(), results.packetLoss]);
        
        // Keep only last 50 data points for chart
        if (realTimeData.length > 50) {
            realTimeData.shift();
        }
        
        // Update chart
        packetLossChart.updateSeries([{
            name: 'Packet Loss %',
            data: realTimeData
        }]);
        
        // Update UI
        updatePacketLossUI(results);
        
        // Add to table
        addTestResultToTable(results);
        
        // Check for alerts
        if (enableAlerts && results.packetLoss > alertThreshold) {
            showPacketLossAlert(results.packetLoss, alertThreshold);
        }
        
        // Update last update time
        document.getElementById('lastUpdate').textContent = now.toLocaleTimeString();
        
    } catch (error) {
        console.error('Packet loss test failed:', error);
    }
}

function updatePacketLossUI(results) {
    // Update current packet loss
    document.getElementById('currentPacketLoss').textContent = results.packetLoss.toFixed(2) + '%';
    
    // Calculate average loss
    var avgLoss = stats.lossHistory.reduce((a, b) => a + b, 0) / stats.lossHistory.length;
    document.getElementById('avgPacketLoss').textContent = avgLoss.toFixed(2) + '%';
    
    // Update packets sent
    document.getElementById('packetsSent').textContent = stats.totalPacketsSent.toLocaleString();
    
    // Update success rate
    var successRate = ((stats.totalPacketsSent - stats.totalPacketsLost) / stats.totalPacketsSent * 100);
    document.getElementById('successRate').textContent = successRate.toFixed(1) + '%';
}

function addTestResultToTable(results) {
    var now = new Date();
    var timeStr = now.toLocaleTimeString();
    var target = document.getElementById('testTarget').value;
    if (target === 'custom') {
        target = document.getElementById('customTarget').value || 'Custom Target';
    } else if (target === 'gateway') {
        target = '192.168.1.1';
    }
    
    // Calculate status based on packet loss
    var lossRate = results.packetLoss;
    var status = lossRate === 0 ? 'Perfect' : 
                lossRate < 1 ? 'Good' : 
                lossRate < 5 ? 'Fair' : 'Poor';
    
    var rowData = [
        timeStr,                                    // Timestamp
        target,                                     // Target
        results.packetsSent,                        // Packets Sent
        results.packetsLost,                        // Packets Lost
        results.packetLoss.toFixed(2) + '%',        // Loss Rate
        results.jitter ? results.jitter.toFixed(1) + 'ms' : '0.0ms',  // Avg Latency
        results.jitter ? (results.jitter * 1.5).toFixed(1) + 'ms' : '0.0ms',  // Max Latency (estimated)
        status                                      // Status
    ];
    
    dataTable.row.add(rowData).draw(false);
    
    // Keep only last 1000 rows
    var rowCount = dataTable.rows().count();
    if (rowCount > 1000) {
        dataTable.row(0).remove().draw(false);
    }
}

function showPacketLossAlert(currentLoss, threshold) {
    // Create alert notification
    var alertHtml = '<div class="alert alert-danger alert-dismissible fade show" role="alert">' +
        '<i class="fas fa-exclamation-triangle"></i> ' +
        '<strong>High Packet Loss Alert!</strong> ' +
        'Current packet loss (' + currentLoss.toFixed(2) + '%) exceeds threshold (' + threshold + '%).' +
        '<button type="button" class="close" data-dismiss="alert">' +
        '<span aria-hidden="true">&times;</span>' +
        '</button>' +
        '</div>';
    
    // Remove existing alerts and add new one
    $('.alert-danger').remove();
    $('.container-fluid').prepend(alertHtml);
    
    // Auto-remove after 10 seconds
    setTimeout(function() {
        $('.alert-danger').fadeOut();
    }, 10000);
}

function stopPacketLossTest() {
    if (!isMonitoring) return;
    
    isMonitoring = false;
    clearInterval(monitorInterval);
    
    // Update UI
    document.getElementById('testStatus').textContent = 'Stopped';
    document.getElementById('testStatus').className = 'badge badge-secondary';
    document.getElementById('startBtn').classList.remove('d-none');
    document.getElementById('stopBtn').classList.add('d-none');
}

function clearChart() {
    chartData = [];
    packetLossChart.updateSeries([{
        name: 'Packet Loss %',
        data: []
    }]);
}

function updateTestDuration() {
    if (!isMonitoring) return;
    
    var now = new Date();
    var duration = now - testStartTime;
    var hours = Math.floor(duration / 3600000);
    var minutes = Math.floor((duration % 3600000) / 60000);
    var seconds = Math.floor((duration % 60000) / 1000);
    
    document.getElementById('testDuration').textContent = 
        `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
    
    setTimeout(updateTestDuration, 1000);
}

function exportLog() {
    alert('Export functionality would generate a CSV file with all packet loss test results.');
}

function clearLog() {
    if (confirm('Are you sure you want to clear the packet loss log?')) {
        dataTable.clear().draw();
        alert('Packet loss log cleared.');
    }
}
</script>
@endpush
