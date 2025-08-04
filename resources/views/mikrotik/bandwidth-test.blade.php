@extends('layouts.admin')

@section('main-content')
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-chart-line text-info"></i> Bandwidth Test
        </h1>
        <button class="btn btn-primary btn-sm" onclick="runBandwidthTest()">
            <i class="fas fa-play"></i> Run Bandwidth Test
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

    <!-- Bandwidth Test Status -->
    <div class="row">
        <div class="col-12 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Bandwidth Test Status
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="bandwidthTestStatus">
                                Ready to test
                            </div>
                            <div class="progress mt-2" style="height: 8px;">
                                <div class="progress-bar" id="testProgress" role="progressbar" style="width: 0%"></div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <div class="spinner-border text-primary d-none" id="bandwidthTestSpinner" role="status">
                                <span class="sr-only">Testing...</span>
                            </div>
                            <i class="fas fa-chart-line fa-2x text-gray-300" id="bandwidthTestIcon"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Current Bandwidth Results -->
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Current Download
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="currentDownload">
                                @if(isset($bandwidthTest['current_download']))
                                    {{ number_format($bandwidthTest['current_download'], 2) }} Mbps
                                @else
                                    0.00 Mbps
                                @endif
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
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Current Upload
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="currentUpload">
                                @if(isset($bandwidthTest['current_upload']))
                                    {{ number_format($bandwidthTest['current_upload'], 2) }} Mbps
                                @else
                                    0.00 Mbps
                                @endif
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
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Peak Download
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="peakDownload">
                                @if(isset($bandwidthTest['peak_download']))
                                    {{ number_format($bandwidthTest['peak_download'], 2) }} Mbps
                                @else
                                    0.00 Mbps
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
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                Peak Upload
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="peakUpload">
                                @if(isset($bandwidthTest['peak_upload']))
                                    {{ number_format($bandwidthTest['peak_upload'], 2) }} Mbps
                                @else
                                    0.00 Mbps
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
    </div>

    <!-- Bandwidth Test Configuration and Real-time Chart -->
    <div class="row">
        <div class="col-lg-4 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-cog"></i> Test Configuration
                    </h6>
                </div>
                <div class="card-body">
                    <form id="bandwidthTestForm">
                        <div class="form-group">
                            <label for="testTarget">Target Address</label>
                            <select class="form-control" id="testTarget" name="target">
                                <option value="8.8.8.8">Google DNS (8.8.8.8)</option>
                                <option value="1.1.1.1">Cloudflare DNS (1.1.1.1)</option>
                                <option value="speedtest.net">Speedtest.net</option>
                                <option value="fast.com">Fast.com</option>
                                <option value="custom">Custom Address</option>
                            </select>
                        </div>

                        <div class="form-group d-none" id="customTargetGroup">
                            <label for="customTarget">Custom Target</label>
                            <input type="text" class="form-control" id="customTarget" name="custom_target" placeholder="192.168.1.1">
                        </div>

                        <div class="form-group">
                            <label for="testDuration">Duration</label>
                            <select class="form-control" id="testDuration" name="duration">
                                <option value="30">30 seconds</option>
                                <option value="60" selected>60 seconds</option>
                                <option value="120">2 minutes</option>
                                <option value="300">5 minutes</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="testProtocol">Protocol</label>
                            <select class="form-control" id="testProtocol" name="protocol">
                                <option value="tcp" selected>TCP</option>
                                <option value="udp">UDP</option>
                                <option value="both">Both TCP & UDP</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="testSize">Test Data Size</label>
                            <select class="form-control" id="testSize" name="size">
                                <option value="1MB">1 MB</option>
                                <option value="10MB" selected>10 MB</option>
                                <option value="100MB">100 MB</option>
                                <option value="1GB">1 GB</option>
                            </select>
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="enableLogging" name="logging" checked>
                            <label class="form-check-label" for="enableLogging">
                                Save results to log
                            </label>
                        </div>

                        <button type="button" class="btn btn-primary btn-block" onclick="runBandwidthTest()">
                            <i class="fas fa-play"></i> Start Bandwidth Test
                        </button>

                        <button type="button" class="btn btn-danger btn-block mt-2 d-none" onclick="stopBandwidthTest()" id="stopTestBtn">
                            <i class="fas fa-stop"></i> Stop Test
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-area"></i> Real-time Bandwidth Monitor
                    </h6>
                </div>
                <div class="card-body">
                    <div id="bandwidthChart" style="height: 400px;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Interface Bandwidth Usage -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-network-wired"></i> Interface Bandwidth Usage
                    </h6>
                    <button class="btn btn-sm btn-outline-primary" onclick="refreshInterfaces()">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                </div>
                <div class="card-body">
                    @if(!empty($interfaces))
                        <div class="table-responsive">
                            <table class="table table-bordered" id="interfaceTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Interface</th>
                                        <th>Status</th>
                                        <th>RX Rate</th>
                                        <th>TX Rate</th>
                                        <th>RX Bytes</th>
                                        <th>TX Bytes</th>
                                        <th>RX Packets</th>
                                        <th>TX Packets</th>
                                        <th>Usage %</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($interfaces as $interface)
                                        <tr>
                                            <td>
                                                <strong>{{ $interface['name'] ?? 'Unknown' }}</strong>
                                                <br><small class="text-muted">{{ $interface['type'] ?? 'N/A' }}</small>
                                            </td>
                                            <td>
                                                @if(isset($interface['running']) && $interface['running'] === 'true')
                                                    <span class="badge badge-success">Running</span>
                                                @else
                                                    <span class="badge badge-danger">Down</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge badge-info">
                                                    {{ isset($interface['rx-rate']) ? number_format($interface['rx-rate'] / 1024 / 1024, 2) : '0.00' }} Mbps
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge badge-warning">
                                                    {{ isset($interface['tx-rate']) ? number_format($interface['tx-rate'] / 1024 / 1024, 2) : '0.00' }} Mbps
                                                </span>
                                            </td>
                                            <td>{{ isset($interface['rx-byte']) ? number_format($interface['rx-byte'] / 1024 / 1024 / 1024, 2) : '0.00' }} GB</td>
                                            <td>{{ isset($interface['tx-byte']) ? number_format($interface['tx-byte'] / 1024 / 1024 / 1024, 2) : '0.00' }} GB</td>
                                            <td>{{ number_format($interface['rx-packet'] ?? 0) }}</td>
                                            <td>{{ number_format($interface['tx-packet'] ?? 0) }}</td>
                                            <td>
                                                @php
                                                    $usage = 0;
                                                    if (isset($interface['rx-rate']) && isset($interface['max-l2mtu'])) {
                                                        $maxBandwidth = ($interface['max-l2mtu'] ?? 1500) * 8; // Convert to bits
                                                        $usage = ($interface['rx-rate'] / $maxBandwidth) * 100;
                                                    }
                                                @endphp
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar 
                                                        @if($usage < 50) bg-success 
                                                        @elseif($usage < 80) bg-warning 
                                                        @else bg-danger @endif" 
                                                        role="progressbar" 
                                                        style="width: {{ min($usage, 100) }}%">
                                                        {{ number_format($usage, 1) }}%
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center text-muted">
                            <i class="fas fa-network-wired fa-3x mb-3"></i>
                            <p>No interface information available</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

@endsection

@push('styles')
<link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap4.min.css" rel="stylesheet">
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap4.min.js"></script>

<script>
var bandwidthChart;
var isTestRunning = false;
var testInterval;
var testData = {
    download: [],
    upload: [],
    time: []
};
var lastDownloadBandwidth = 0;
var lastUploadBandwidth = 0;

// Smooth data function for bandwidth
function smoothBandwidth(currentBandwidth, lastBandwidth, factor = 0.7) {
    if (lastBandwidth === 0) return currentBandwidth;
    return lastBandwidth + (currentBandwidth - lastBandwidth) * factor;
}

$(document).ready(function() {
    $('#interfaceTable').DataTable({
        "pageLength": 10,
        "order": [[ 0, "asc" ]]
    });

    // Handle custom target selection
    $('#testTarget').change(function() {
        if ($(this).val() === 'custom') {
            $('#customTargetGroup').removeClass('d-none');
        } else {
            $('#customTargetGroup').addClass('d-none');
        }
    });

    initBandwidthChart();
});

function initBandwidthChart() {
    var options = {
        series: [{
            name: 'Download',
            data: []
        }, {
            name: 'Upload',
            data: []
        }],
        chart: {
            type: 'line',
            height: 400,
            toolbar: { 
                show: true,
                tools: {
                    download: true,
                    selection: false,
                    zoom: false,
                    zoomin: false,
                    zoomout: false,
                    pan: false,
                    reset: false
                }
            },
            animations: {
                enabled: true,
                easing: 'easeinout',
                speed: 500,
                animateGradually: {
                    enabled: true,
                    delay: 150
                },
                dynamicAnimation: {
                    enabled: true,
                    speed: 350
                }
            },
            background: '#ffffff',
            foreColor: '#333',
            fontFamily: 'Nunito, sans-serif'
        },
        plotOptions: {
            line: {
                dataLabels: {
                    enabled: false
                }
            }
        },
        colors: ['#4CAF50', '#FF9800'],
        fill: {
            type: 'gradient',
            gradient: {
                shade: 'light',
                type: 'vertical',
                shadeIntensity: 0.5,
                gradientToColors: ['#81C784', '#FFB74D'],
                inverseColors: false,
                opacityFrom: 0.8,
                opacityTo: 0.1,
                stops: [0, 100]
            }
        },
        xaxis: {
            type: 'numeric',
            title: { 
                text: 'Time (seconds)',
                style: {
                    fontSize: '14px',
                    fontWeight: 600,
                    color: '#666'
                }
            },
            labels: {
                formatter: function(val) {
                    return val.toFixed(0) + 's';
                },
                style: {
                    fontSize: '12px',
                    colors: '#999'
                }
            },
            axisBorder: {
                show: true,
                color: '#e0e0e0'
            },
            axisTicks: {
                show: true,
                color: '#e0e0e0'
            }
        },
        yaxis: {
            title: { 
                text: 'Bandwidth (Mbps)',
                style: {
                    fontSize: '14px',
                    fontWeight: 600,
                    color: '#666'
                }
            },
            labels: {
                formatter: function(val) {
                    return val.toFixed(1);
                },
                style: {
                    fontSize: '12px',
                    colors: '#999'
                }
            },
            min: 0,
            axisBorder: {
                show: true,
                color: '#e0e0e0'
            }
        },
        stroke: {
            curve: 'smooth',
            width: 4,
            lineCap: 'round'
        },
        markers: {
            size: 0,
            strokeColors: '#fff',
            strokeWidth: 2,
            hover: {
                size: 8,
                sizeOffset: 3
            }
        },
        dataLabels: {
            enabled: false,
            enabledOnSeries: undefined
        },
        legend: {
            show: true,
            position: 'top',
            horizontalAlign: 'center',
            fontSize: '14px',
            fontWeight: 600,
            offsetY: -10,
            markers: {
                width: 12,
                height: 12,
                radius: 6
            }
        },
        grid: {
            show: true,
            borderColor: '#f1f1f1',
            strokeDashArray: 2,
            position: 'back',
            xaxis: {
                lines: {
                    show: true
                }
            },
            yaxis: {
                lines: {
                    show: true
                }
            },
            padding: {
                top: 20,
                right: 20,
                bottom: 20,
                left: 20
            }
        },
        tooltip: {
            enabled: true,
            shared: true,
            intersect: false,
            theme: 'light',
            style: {
                fontSize: '13px'
            },
            x: {
                formatter: function(val) {
                    return val.toFixed(1) + 's';
                }
            },
            y: {
                formatter: function(val) {
                    return val.toFixed(1) + ' Mbps';
                }
            },
            marker: {
                show: true
            }
        },
        responsive: [{
            breakpoint: 480,
            options: {
                chart: {
                    height: 300
                },
                legend: {
                    position: 'bottom'
                }
            }
        }],
        annotations: {
            points: []
        }
    };

    bandwidthChart = new ApexCharts(document.querySelector("#bandwidthChart"), options);
    bandwidthChart.render();
}

function runBandwidthTest() {
    if (isTestRunning) {
        alert('Bandwidth test is already running. Please wait for it to complete.');
        return;
    }

    isTestRunning = true;
    
    // Reset data
    testData = {
        download: [],
        upload: [],
        time: []
    };
    
    // Reset smoothing variables
    lastDownloadBandwidth = 0;
    lastUploadBandwidth = 0;
    
    // Update UI
    document.getElementById('bandwidthTestStatus').textContent = 'Initializing bandwidth test...';
    document.getElementById('bandwidthTestSpinner').classList.remove('d-none');
    document.getElementById('bandwidthTestIcon').classList.add('d-none');
    document.getElementById('stopTestBtn').classList.remove('d-none');
    
    // Clear chart
    bandwidthChart.updateSeries([{
        name: 'Download',
        data: []
    }, {
        name: 'Upload',
        data: []
    }]);

    // Get test parameters
    var duration = parseInt(document.getElementById('testDuration').value) || 60;
    var protocol = document.getElementById('testProtocol').value;
    
    // Start real bandwidth test
    performRealBandwidthTest(duration, protocol);
}

function performRealBandwidthTest(duration, protocol) {
    var startTime = Date.now();
    var downloadData = [];
    var uploadData = [];
    
    document.getElementById('bandwidthTestStatus').textContent = 'Testing bandwidth...';
    
    // Start continuous testing
    if (protocol === 'tcp' || protocol === 'both') {
        testRealDownloadBandwidth(duration, downloadData, startTime);
    }
    
    if (protocol === 'udp' || protocol === 'both') {
        setTimeout(function() {
            testRealUploadBandwidth(duration, uploadData, startTime);
        }, 2000); // Start upload test after 2 seconds
    }
    
    // Complete test after duration
    setTimeout(function() {
        completeBandwidthTest();
    }, duration * 1000);
}

function testRealDownloadBandwidth(duration, downloadData, startTime) {
    var isRunning = true;
    
    function measureDownloadBandwidth() {
        if (!isRunning) return;
        
        var testStartTime = performance.now();
        var testUrl = 'https://speed.cloudflare.com/__down?bytes=' + (3000000 + Math.random() * 2000000); // 3-5MB
        
        fetch(testUrl)
            .then(function(response) {
                if (!response.ok) throw new Error('Network error');
                
                var reader = response.body.getReader();
                var receivedLength = 0;
                
                function pump() {
                    return reader.read().then(function(result) {
                        if (result.done) {
                            var testEndTime = performance.now();
                            var testDuration = (testEndTime - testStartTime) / 1000;
                            var rawBandwidthMbps = (receivedLength * 8) / (testDuration * 1000000);
                            
                            // Apply smoothing
                            var bandwidthMbps = smoothBandwidth(rawBandwidthMbps, lastDownloadBandwidth, 0.6);
                            lastDownloadBandwidth = bandwidthMbps;
                            
                            var currentTime = (Date.now() - startTime) / 1000;
                            downloadData.push([parseFloat(currentTime.toFixed(1)), parseFloat(bandwidthMbps.toFixed(1))]);
                            
                            document.getElementById('currentDownload').textContent = bandwidthMbps.toFixed(1) + ' Mbps';
                            
                            // Update chart
                            bandwidthChart.updateSeries([{
                                name: 'Download',
                                data: downloadData.slice(-20) // Keep last 20 points
                            }, {
                                name: 'Upload',
                                data: uploadData.slice(-20)
                            }]);
                            
                            // Continue if still within duration
                            if (currentTime < duration && isRunning) {
                                setTimeout(measureDownloadBandwidth, 1500);
                            }
                            return;
                        }
                        
                        receivedLength += result.value.length;
                        return pump();
                    });
                }
                
                return pump();
            })
            .catch(function(error) {
                if (isRunning) {
                    setTimeout(measureDownloadBandwidth, 2000); // Retry after error
                }
            });
    }
    
    measureDownloadBandwidth();
    
    // Stop after duration
    setTimeout(function() {
        isRunning = false;
    }, duration * 1000);
}

function testRealUploadBandwidth(duration, uploadData, startTime) {
    var isRunning = true;
    
    function measureUploadBandwidth() {
        if (!isRunning) return;
        
        var testStartTime = performance.now();
        
        // Create test data (800KB)
        var testData = new ArrayBuffer(819200);
        var testBlob = new Blob([testData]);
        var formData = new FormData();
        formData.append('file', testBlob, 'bandwidthtest.dat');
        
        fetch('https://httpbin.org/post', {
            method: 'POST',
            body: formData
        })
        .then(function(response) {
            var testEndTime = performance.now();
            var testDuration = (testEndTime - testStartTime) / 1000;
            var rawBandwidthMbps = (testBlob.size * 8) / (testDuration * 1000000);
            
            // Apply smoothing
            var bandwidthMbps = smoothBandwidth(rawBandwidthMbps, lastUploadBandwidth, 0.6);
            lastUploadBandwidth = bandwidthMbps;
            
            var currentTime = (Date.now() - startTime) / 1000;
            uploadData.push([parseFloat(currentTime.toFixed(1)), parseFloat(bandwidthMbps.toFixed(1))]);
            
            document.getElementById('currentUpload').textContent = bandwidthMbps.toFixed(1) + ' Mbps';
            
            // Update chart
            bandwidthChart.updateSeries([{
                name: 'Download',
                data: downloadData.slice(-20)
            }, {
                name: 'Upload',
                data: uploadData.slice(-20)
            }]);
            
            // Continue if still within duration
            if (currentTime < duration && isRunning) {
                setTimeout(measureUploadBandwidth, 2000);
            }
        })
        .catch(function(error) {
            if (isRunning) {
                setTimeout(measureUploadBandwidth, 3000); // Retry with longer delay
            }
        });
    }
    
    measureUploadBandwidth();
    
    // Stop after duration
    setTimeout(function() {
        isRunning = false;
    }, duration * 1000);
}
            } else {
                uploadSpeed = Math.random() * 8 + 12; // 12-20 Mbps
            }
        }
        
        if (protocol === 'udp') {
function completeBandwidthTest() {
    isTestRunning = false;
    
    // Calculate final results
    var finalDownload = testData.download.length > 0 ? 
        Math.max(...testData.download.map(d => d[1])) : 0;
    var finalUpload = testData.upload.length > 0 ? 
        Math.max(...testData.upload.map(d => d[1])) : 0;
    
    // Update UI
    document.getElementById('bandwidthTestStatus').textContent = 'Test completed';
    document.getElementById('bandwidthTestSpinner').classList.add('d-none');
    document.getElementById('bandwidthTestIcon').classList.remove('d-none');
    document.getElementById('stopTestBtn').classList.add('d-none');
    document.getElementById('testProgress').style.width = '100%';
    
    // Update peak values
    document.getElementById('peakDownload').textContent = finalDownload.toFixed(1) + ' Mbps';
    document.getElementById('peakUpload').textContent = finalUpload.toFixed(1) + ' Mbps';
    
    // Show completion message
    setTimeout(function() {
        alert('Bandwidth test completed!\n\n' +
              'Peak Download: ' + finalDownload.toFixed(1) + ' Mbps\n' +
              'Peak Upload: ' + finalUpload.toFixed(1) + ' Mbps');
    }, 500);
}

function stopBandwidthTest() {
    if (isTestRunning) {
        isTestRunning = false;
        
        document.getElementById('bandwidthTestStatus').textContent = 'Test stopped by user';
        document.getElementById('bandwidthTestSpinner').classList.add('d-none');
        document.getElementById('bandwidthTestIcon').classList.remove('d-none');
        document.getElementById('stopTestBtn').classList.add('d-none');
    }
}

function refreshInterfaces() {
    location.reload();
}

// Auto refresh interfaces every 30 seconds
setInterval(function() {
    if (!isTestRunning) {
        refreshInterfaces();
    }
}, 30000);
</script>
@endpush
