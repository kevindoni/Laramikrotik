@extends('layouts.admin')

@section('main-content')
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-satellite-dish text-info"></i> Latency Monitor
        </h1>
        <button class="btn btn-primary btn-sm" onclick="startLatencyMonitor()">
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

    <!-- Latency Overview Cards -->
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Average Latency
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="avgLatency">
                                @if(isset($latencyStats['avg_latency']))
                                    {{ number_format($latencyStats['avg_latency'], 2) }} ms
                                @else
                                    -- ms
                                @endif
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-stopwatch fa-2x text-gray-300"></i>
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
                                Min Latency
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="minLatency">
                                @if(isset($latencyStats['min_latency']))
                                    {{ number_format($latencyStats['min_latency'], 2) }} ms
                                @else
                                    -- ms
                                @endif
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-arrow-down fa-2x text-gray-300"></i>
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
                                Max Latency
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="maxLatency">
                                @if(isset($latencyStats['max_latency']))
                                    {{ number_format($latencyStats['max_latency'], 2) }} ms
                                @else
                                    -- ms
                                @endif
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-arrow-up fa-2x text-gray-300"></i>
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
                                Jitter
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="jitter">
                                @if(isset($latencyStats['jitter']))
                                    {{ number_format($latencyStats['jitter'], 2) }} ms
                                @else
                                    -- ms
                                @endif
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-wave-square fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Monitor Configuration and Real-time Chart -->
    <div class="row">
        <div class="col-lg-4 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-cog"></i> Monitor Configuration
                    </h6>
                </div>
                <div class="card-body">
                    <form id="latencyMonitorForm">
                        <div class="form-group">
                            <label for="targetHost">Target Host</label>
                            <select class="form-control" id="targetHost" name="target">
                                <option value="8.8.8.8">Google DNS (8.8.8.8)</option>
                                <option value="1.1.1.1">Cloudflare DNS (1.1.1.1)</option>
                                <option value="208.67.222.222">OpenDNS (208.67.222.222)</option>
                                <option value="gateway">Default Gateway</option>
                                <option value="custom">Custom Host</option>
                            </select>
                        </div>

                        <div class="form-group d-none" id="customHostGroup">
                            <label for="customHost">Custom Host</label>
                            <input type="text" class="form-control" id="customHost" name="custom_host" placeholder="192.168.1.1">
                        </div>

                        <div class="form-group">
                            <label for="pingInterval">Ping Interval</label>
                            <select class="form-control" id="pingInterval" name="interval">
                                <option value="1">1 second</option>
                                <option value="2">2 seconds</option>
                                <option value="5" selected>5 seconds</option>
                                <option value="10">10 seconds</option>
                                <option value="30">30 seconds</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="pingCount">Ping Count per Test</label>
                            <select class="form-control" id="pingCount" name="count">
                                <option value="1">1 ping</option>
                                <option value="3" selected>3 pings</option>
                                <option value="5">5 pings</option>
                                <option value="10">10 pings</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="packetSize">Packet Size (bytes)</label>
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
                                Enable high latency alerts
                            </label>
                        </div>

                        <div class="form-group">
                            <label for="alertThreshold">Alert Threshold (ms)</label>
                            <input type="number" class="form-control" id="alertThreshold" name="threshold" value="100" min="1">
                        </div>

                        <button type="button" class="btn btn-primary btn-block" onclick="startLatencyMonitor()" id="startBtn">
                            <i class="fas fa-play"></i> Start Monitoring
                        </button>

                        <button type="button" class="btn btn-danger btn-block mt-2 d-none" onclick="stopLatencyMonitor()" id="stopBtn">
                            <i class="fas fa-stop"></i> Stop Monitoring
                        </button>
                    </form>
                </div>
            </div>

            <!-- Monitor Status -->
            <div class="card shadow mt-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-info-circle"></i> Monitor Status
                    </h6>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <strong>Status:</strong>
                        <span class="badge badge-secondary" id="monitorStatus">Stopped</span>
                    </div>
                    <div class="mb-2">
                        <strong>Target:</strong>
                        <span id="currentTarget">Not set</span>
                    </div>
                    <div class="mb-2">
                        <strong>Packets Sent:</strong>
                        <span id="packetsSent">0</span>
                    </div>
                    <div class="mb-2">
                        <strong>Packets Lost:</strong>
                        <span id="packetsLost">0</span>
                    </div>
                    <div class="mb-2">
                        <strong>Loss Rate:</strong>
                        <span id="lossRate">0%</span>
                    </div>
                    <div>
                        <strong>Uptime:</strong>
                        <span id="monitorUptime">00:00:00</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8 mb-4">
            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-line"></i> Real-time Latency Chart
                    </h6>
                    <div>
                        <button class="btn btn-sm btn-outline-secondary" onclick="clearChart()">
                            <i class="fas fa-trash"></i> Clear
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div id="latencyChart" style="height: 400px;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Latency Tests -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-list"></i> Recent Latency Tests
                    </h6>
                    <button class="btn btn-sm btn-outline-primary" onclick="exportResults()">
                        <i class="fas fa-download"></i> Export
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="latencyTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Timestamp</th>
                                    <th>Target</th>
                                    <th>Latency (ms)</th>
                                    <th>Status</th>
                                    <th>Packet Size</th>
                                    <th>TTL</th>
                                    <th>Alert</th>
                                </tr>
                            </thead>
                            <tbody id="latencyTableBody">
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
var latencyChart;
var isMonitoring = false;
var monitorInterval;
var monitorStartTime;
var monitorData = [];
var dataTable;

// Statistics tracking
var stats = {
    packetsSent: 0,
    packetsLost: 0,
    latencies: [],
    minLatency: Infinity,
    maxLatency: 0,
    totalLatency: 0
};

$(document).ready(function() {
    dataTable = $('#latencyTable').DataTable({
        "pageLength": 25,
        "order": [[ 0, "desc" ]], // Sort by timestamp descending
    });

    // Handle custom host selection
    $('#targetHost').change(function() {
        if ($(this).val() === 'custom') {
            $('#customHostGroup').removeClass('d-none');
        } else {
            $('#customHostGroup').addClass('d-none');
        }
    });

    initLatencyChart();
});

function initLatencyChart() {
    var options = {
        series: [{
            name: 'Latency',
            data: []
        }],
        chart: {
            type: 'line',
            height: 400,
            toolbar: { show: true },
            animations: {
                enabled: true,
                easing: 'linear',
                dynamicAnimation: {
                    speed: 1000
                }
            }
        },
        colors: ['#1cc88a'],
        xaxis: {
            type: 'datetime',
            title: { text: 'Time' }
        },
        yaxis: {
            title: { text: 'Latency (ms)' },
            labels: {
                formatter: function(val) {
                    return val.toFixed(2) + ' ms';
                }
            }
        },
        stroke: {
            curve: 'smooth',
            width: 2
        },
        markers: {
            size: 4,
            colors: ['#1cc88a'],
            strokeWidth: 2,
            hover: {
                size: 6
            }
        },
        tooltip: {
            x: {
                format: 'HH:mm:ss'
            },
            y: {
                formatter: function(val) {
                    return val.toFixed(2) + ' ms';
                }
            }
        }
    };

    latencyChart = new ApexCharts(document.querySelector("#latencyChart"), options);
    latencyChart.render();
}

function startLatencyMonitor() {
    if (isMonitoring) {
        alert('Latency monitor is already running.');
        return;
    }

    isMonitoring = true;
    monitorStartTime = new Date();
    
    // Reset statistics
    stats = {
        packetsSent: 0,
        packetsLost: 0,
        latencies: [],
        minLatency: Infinity,
        maxLatency: 0,
        totalLatency: 0
    };
    
    // Update UI
    document.getElementById('monitorStatus').textContent = 'Running';
    document.getElementById('monitorStatus').className = 'badge badge-success';
    document.getElementById('startBtn').classList.add('d-none');
    document.getElementById('stopBtn').classList.remove('d-none');
    
    // Get configuration
    var target = document.getElementById('targetHost').value;
    var interval = parseInt(document.getElementById('pingInterval').value) * 1000;
    var customHost = document.getElementById('customHost').value;
    
    if (target === 'custom' && customHost) {
        target = customHost;
    } else if (target === 'gateway') {
        target = '192.168.1.1'; // Default gateway simulation
    }
    
    document.getElementById('currentTarget').textContent = target;
    
    // Start monitoring
    runLatencyTest(target);
    monitorInterval = setInterval(function() {
        runLatencyTest(target);
    }, interval);
    
    // Update uptime counter
    updateUptime();
}

function runLatencyTest(target) {
    var pingCount = parseInt(document.getElementById('pingCount').value);
    var packetSize = parseInt(document.getElementById('packetSize').value);
    var alertThreshold = parseInt(document.getElementById('alertThreshold').value);
    var enableAlerts = document.getElementById('enableAlerts').checked;
    
    // Simulate ping test
    for (var i = 0; i < pingCount; i++) {
        setTimeout(function() {
            simulatePing(target, packetSize, alertThreshold, enableAlerts);
        }, i * 100); // Stagger pings
    }
}

function simulatePing(target, packetSize, alertThreshold, enableAlerts) {
    stats.packetsSent++;
    
    // Simulate realistic latency with occasional spikes
    var latency;
    var isTimeout = Math.random() < 0.02; // 2% packet loss
    
    if (isTimeout) {
        stats.packetsLost++;
        latency = null;
    } else {
        // Base latency varies by target
        var baseLatency = 15;
        if (target.includes('8.8.8.8') || target.includes('1.1.1.1')) {
            baseLatency = 20; // Internet DNS
        } else if (target.includes('192.168')) {
            baseLatency = 2; // Local network
        }
        
        // Add normal variation and occasional spikes
        var spike = Math.random() < 0.05 ? Math.random() * 200 : 0; // 5% chance of spike
        latency = baseLatency + (Math.random() * 10) + spike;
        
        // Factor in packet size
        latency += (packetSize / 64) * 2;
        
        // Update statistics
        stats.latencies.push(latency);
        stats.totalLatency += latency;
        stats.minLatency = Math.min(stats.minLatency, latency);
        stats.maxLatency = Math.max(stats.maxLatency, latency);
    }
    
    // Add to chart
    var timestamp = new Date().getTime();
    if (latency !== null) {
        monitorData.push([timestamp, latency]);
        
        // Keep only last 100 points
        if (monitorData.length > 100) {
            monitorData.shift();
        }
        
        latencyChart.updateSeries([{
            name: 'Latency',
            data: monitorData
        }]);
    }
    
    // Update statistics display
    updateStatistics();
    
    // Add to table
    addToTable(timestamp, target, latency, packetSize, alertThreshold, enableAlerts);
    
    // Check for alerts
    if (enableAlerts && latency !== null && latency > alertThreshold) {
        showAlert(target, latency, alertThreshold);
    }
}

function updateStatistics() {
    var avgLatency = stats.latencies.length > 0 ? 
        stats.totalLatency / stats.latencies.length : 0;
    var jitter = calculateJitter();
    var lossRate = stats.packetsSent > 0 ? 
        (stats.packetsLost / stats.packetsSent) * 100 : 0;
    
    document.getElementById('avgLatency').textContent = avgLatency.toFixed(2) + ' ms';
    document.getElementById('minLatency').textContent = 
        (stats.minLatency === Infinity ? 0 : stats.minLatency).toFixed(2) + ' ms';
    document.getElementById('maxLatency').textContent = stats.maxLatency.toFixed(2) + ' ms';
    document.getElementById('jitter').textContent = jitter.toFixed(2) + ' ms';
    document.getElementById('packetsSent').textContent = stats.packetsSent;
    document.getElementById('packetsLost').textContent = stats.packetsLost;
    document.getElementById('lossRate').textContent = lossRate.toFixed(1) + '%';
}

function calculateJitter() {
    if (stats.latencies.length < 2) return 0;
    
    var deltas = [];
    for (var i = 1; i < stats.latencies.length; i++) {
        deltas.push(Math.abs(stats.latencies[i] - stats.latencies[i-1]));
    }
    
    return deltas.reduce((a, b) => a + b, 0) / deltas.length;
}

function addToTable(timestamp, target, latency, packetSize, alertThreshold, enableAlerts) {
    var status = latency === null ? 'Timeout' : 'Success';
    var latencyText = latency === null ? 'Timeout' : latency.toFixed(2);
    var alert = enableAlerts && latency !== null && latency > alertThreshold ? 'High Latency' : '-';
    var alertClass = alert === 'High Latency' ? 'badge-danger' : 'badge-secondary';
    
    var row = `
        <tr>
            <td>${new Date(timestamp).toLocaleString()}</td>
            <td>${target}</td>
            <td>
                <span class="badge ${latency === null ? 'badge-danger' : 
                    (latency > 100 ? 'badge-warning' : 'badge-success')}">
                    ${latencyText}
                </span>
            </td>
            <td>
                <span class="badge ${status === 'Success' ? 'badge-success' : 'badge-danger'}">
                    ${status}
                </span>
            </td>
            <td>${packetSize} bytes</td>
            <td>${Math.floor(Math.random() * 10) + 55}</td>
            <td>
                <span class="badge ${alertClass}">${alert}</span>
            </td>
        </tr>
    `;
    
    dataTable.row.add($(row)).draw(false);
}

function showAlert(target, latency, threshold) {
    // Simple alert - in production, this could be a toast notification
    console.log(`High latency alert: ${target} - ${latency.toFixed(2)}ms (threshold: ${threshold}ms)`);
}

function stopLatencyMonitor() {
    if (!isMonitoring) return;
    
    isMonitoring = false;
    clearInterval(monitorInterval);
    
    // Update UI
    document.getElementById('monitorStatus').textContent = 'Stopped';
    document.getElementById('monitorStatus').className = 'badge badge-secondary';
    document.getElementById('startBtn').classList.remove('d-none');
    document.getElementById('stopBtn').classList.add('d-none');
}

function clearChart() {
    monitorData = [];
    latencyChart.updateSeries([{
        name: 'Latency',
        data: []
    }]);
}

function updateUptime() {
    if (!isMonitoring) return;
    
    var now = new Date();
    var uptime = now - monitorStartTime;
    var hours = Math.floor(uptime / 3600000);
    var minutes = Math.floor((uptime % 3600000) / 60000);
    var seconds = Math.floor((uptime % 60000) / 1000);
    
    document.getElementById('monitorUptime').textContent = 
        `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
    
    setTimeout(updateUptime, 1000);
}

function exportResults() {
    // Export functionality - in production, this would generate a CSV/Excel file
    alert('Export functionality would generate a CSV file with all latency test results.');
}
</script>
@endpush
