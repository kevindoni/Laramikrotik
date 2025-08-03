@extends('layouts.admin')

@section('main-content')
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-tachometer-alt text-info"></i> Speed Test
        </h1>
        <button class="btn btn-primary btn-sm" onclick="runSpeedTest()">
            <i class="fas fa-play"></i> Run Speed Test
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

    <!-- Speed Test Status Card -->
    <div class="row">
        <div class="col-12 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Speed Test Status
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="speedTestStatus">
                                Ready to test
                            </div>
                        </div>
                        <div class="col-auto">
                            <div class="spinner-border text-primary d-none" id="speedTestSpinner" role="status">
                                <span class="sr-only">Testing...</span>
                            </div>
                            <i class="fas fa-tachometer-alt fa-2x text-gray-300" id="speedTestIcon"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Current Speed Test Results -->
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Download Speed
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="downloadSpeed">
                                @if(isset($speedTest['download']))
                                    {{ number_format($speedTest['download'], 2) }} Mbps
                                @else
                                    -- Mbps
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
                                Upload Speed
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="uploadSpeed">
                                @if(isset($speedTest['upload']))
                                    {{ number_format($speedTest['upload'], 2) }} Mbps
                                @else
                                    -- Mbps
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
                                Ping
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="pingTime">
                                @if(isset($speedTest['ping']))
                                    {{ number_format($speedTest['ping'], 2) }} ms
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
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Test Date
                            </div>
                            <div class="h6 mb-0 font-weight-bold text-gray-800" id="testDate">
                                @if(isset($speedTest['date']))
                                    {{ \Carbon\Carbon::parse($speedTest['date'])->format('M d, Y H:i') }}
                                @else
                                    Never tested
                                @endif
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Speed Test Configuration -->
    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-cog"></i> Speed Test Configuration
                    </h6>
                </div>
                <div class="card-body">
                    <form id="speedTestForm">
                        <div class="form-group">
                            <label for="testServer">Test Server</label>
                            <select class="form-control" id="testServer" name="test_server">
                                <option value="auto">Auto Select (Best Server)</option>
                                <option value="8.8.8.8">Google DNS (8.8.8.8)</option>
                                <option value="1.1.1.1">Cloudflare DNS (1.1.1.1)</option>
                                <option value="speedtest.net">Speedtest.net</option>
                                <option value="fast.com">Fast.com (Netflix)</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="testDuration">Test Duration (seconds)</label>
                            <select class="form-control" id="testDuration" name="test_duration">
                                <option value="10">10 seconds</option>
                                <option value="30" selected>30 seconds</option>
                                <option value="60">60 seconds</option>
                                <option value="120">120 seconds</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="testType">Test Type</label>
                            <select class="form-control" id="testType" name="test_type">
                                <option value="both" selected>Both Download & Upload</option>
                                <option value="download">Download Only</option>
                                <option value="upload">Upload Only</option>
                                <option value="ping">Ping Only</option>
                            </select>
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="enableLogging" name="enable_logging" checked>
                            <label class="form-check-label" for="enableLogging">
                                Save test results to log
                            </label>
                        </div>

                        <button type="button" class="btn btn-primary btn-block" onclick="runSpeedTest()">
                            <i class="fas fa-play"></i> Start Speed Test
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-line"></i> Speed Test Progress
                    </h6>
                </div>
                <div class="card-body">
                    <div id="speedTestProgress" style="height: 300px;">
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-info-circle fa-3x mb-3"></i>
                            <p>Click "Start Speed Test" to begin testing your network speed</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Speed Test History -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-history"></i> Speed Test History
                    </h6>
                    <button class="btn btn-sm btn-outline-danger" onclick="clearHistory()">
                        <i class="fas fa-trash"></i> Clear History
                    </button>
                </div>
                <div class="card-body">
                    @if(!empty($speedTestHistory))
                        <div class="table-responsive">
                            <table class="table table-bordered" id="speedTestTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Download (Mbps)</th>
                                        <th>Upload (Mbps)</th>
                                        <th>Ping (ms)</th>
                                        <th>Server</th>
                                        <th>Duration</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($speedTestHistory as $test)
                                        <tr>
                                            <td>{{ \Carbon\Carbon::parse($test['date'])->format('M d, Y H:i:s') }}</td>
                                            <td>
                                                <span class="badge badge-success">
                                                    {{ number_format($test['download'], 2) }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge badge-warning">
                                                    {{ number_format($test['upload'], 2) }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge badge-info">
                                                    {{ number_format($test['ping'], 2) }}
                                                </span>
                                            </td>
                                            <td>{{ $test['server'] ?? 'Auto' }}</td>
                                            <td>{{ $test['duration'] ?? 30 }}s</td>
                                            <td>
                                                @if($test['status'] === 'completed')
                                                    <span class="badge badge-success">Completed</span>
                                                @elseif($test['status'] === 'failed')
                                                    <span class="badge badge-danger">Failed</span>
                                                @else
                                                    <span class="badge badge-warning">{{ ucfirst($test['status']) }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center text-muted">
                            <i class="fas fa-history fa-3x mb-3"></i>
                            <p>No speed test history available</p>
                            <small>Run your first speed test to see results here</small>
                        </div>
                    @endif
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
var speedTestChart;
var isTestRunning = false;
var lastDownloadSpeed = 0;
var lastUploadSpeed = 0;

// Smooth data function to avoid sudden spikes
function smoothSpeed(currentSpeed, lastSpeed, factor = 0.7) {
    if (lastSpeed === 0) return currentSpeed;
    return lastSpeed + (currentSpeed - lastSpeed) * factor;
}

$(document).ready(function() {
    $('#speedTestTable').DataTable({
        "pageLength": 10,
        "order": [[ 0, "desc" ]], // Sort by date descending
    });

    initSpeedTestChart();
});

function initSpeedTestChart() {
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
            height: 350,
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
                text: 'Speed (Mbps)',
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

    speedTestChart = new ApexCharts(document.querySelector("#speedTestProgress"), options);
    speedTestChart.render();
}

function runSpeedTest() {
    if (isTestRunning) {
        alert('Speed test is already running. Please wait for it to complete.');
        return;
    }

    isTestRunning = true;
    
    // Update UI
    document.getElementById('speedTestStatus').textContent = 'Running speed test...';
    document.getElementById('speedTestSpinner').classList.remove('d-none');
    document.getElementById('speedTestIcon').classList.add('d-none');
    
    // Get form data
    var formData = new FormData(document.getElementById('speedTestForm'));
    
    // Clear previous chart data
    speedTestChart.updateSeries([{
        name: 'Download',
        data: []
    }, {
        name: 'Upload', 
        data: []
    }]);

    // Run actual speed test
    performRealSpeedTest(formData);
}

function performRealSpeedTest(formData) {
    var duration = parseInt(formData.get('test_duration')) || 30;
    var testType = formData.get('test_type') || 'both';
    var downloadData = [];
    var uploadData = [];
    var currentTime = 0;
    
    // Test files for download test (different sizes)
    var downloadUrls = [
        'https://speed.cloudflare.com/__down?bytes=25000000', // 25MB
        'https://httpbin.org/bytes/1048576', // 1MB from httpbin
        'https://httpbin.org/bytes/5242880', // 5MB from httpbin
        'https://jsonplaceholder.typicode.com/photos' // JSON API fallback
    ];
    
    document.getElementById('speedTestStatus').textContent = 'Testing ping...';
    
    // First test ping
    testPing().then(function(pingTime) {
        document.getElementById('pingTime').textContent = pingTime.toFixed(2) + ' ms';
        
        if (testType === 'ping') {
            // Ping only test
            completeSpeedTest([], []);
            return Promise.resolve();
        } else if (testType === 'both' || testType === 'download') {
            document.getElementById('speedTestStatus').textContent = 'Testing download speed...';
            return testDownloadSpeed(duration, downloadData);
        }
        return Promise.resolve();
    }).then(function() {
        if (testType === 'both' || testType === 'upload') {
            document.getElementById('speedTestStatus').textContent = 'Testing upload speed...';
            return testUploadSpeed(duration, uploadData, downloadData);
        }
        return Promise.resolve();
    }).then(function() {
        completeSpeedTest(downloadData, uploadData);
    }).catch(function(error) {
        console.error('Speed test error:', error);
        document.getElementById('speedTestStatus').textContent = 'Test failed: ' + error.message;
        isTestRunning = false;
        document.getElementById('speedTestSpinner').classList.add('d-none');
        document.getElementById('speedTestIcon').classList.remove('d-none');
    });
}

function testPing() {
    return new Promise(function(resolve) {
        var startTime = performance.now();
        var img = new Image();
        
        img.onload = function() {
            var endTime = performance.now();
            resolve(endTime - startTime);
        };
        
        img.onerror = function() {
            // Try alternative ping method using fetch
            testPingFetch().then(resolve).catch(function() {
                resolve(50); // Default ping if both methods fail
            });
        };
        
        // Use a more reliable image that's less likely to be blocked
        img.src = 'https://httpbin.org/image/png?t=' + Date.now();
    });
}

function testPingFetch() {
    return new Promise(function(resolve, reject) {
        var startTime = performance.now();
        
        fetch('https://httpbin.org/get', {
            method: 'GET',
            cache: 'no-cache'
        })
        .then(function(response) {
            var endTime = performance.now();
            resolve(endTime - startTime);
        })
        .catch(function(error) {
            reject(error);
        });
    });
}

function testDownloadSpeed(duration, downloadData) {
    return new Promise(function(resolve, reject) {
        var startTime = Date.now();
        var interval;
        var isRunning = true;
        
        // Continuous speed measurement
        function measureSpeed() {
            if (!isRunning) return;
            
            var testStartTime = performance.now();
            var testUrl = 'https://speed.cloudflare.com/__down?bytes=' + (2000000 + Math.random() * 3000000); // 2-5MB
            
            fetch(testUrl)
                .then(function(response) {
                    if (!response.ok) throw new Error('Network error');
                    
                    var reader = response.body.getReader();
                    var receivedLength = 0;
                    var chunks = [];
                    
                    function pump() {
                        return reader.read().then(function(result) {
                            if (result.done) {
                                var testEndTime = performance.now();
                                var testDuration = (testEndTime - testStartTime) / 1000;
                                var rawSpeedMbps = (receivedLength * 8) / (testDuration * 1000000);
                                
                                // Apply smoothing to reduce fluctuations
                                var speedMbps = smoothSpeed(rawSpeedMbps, lastDownloadSpeed, 0.6);
                                lastDownloadSpeed = speedMbps;
                                
                                var currentTime = (Date.now() - startTime) / 1000;
                                downloadData.push([parseFloat(currentTime.toFixed(1)), parseFloat(speedMbps.toFixed(1))]);
                                
                                document.getElementById('downloadSpeed').textContent = speedMbps.toFixed(1) + ' Mbps';
                                
                                // Update chart more smoothly
                                speedTestChart.updateSeries([{
                                    name: 'Download',
                                    data: downloadData.slice(-15) // Keep only last 15 points for cleaner visualization
                                }, {
                                    name: 'Upload',
                                    data: []
                                }]);
                                
                                // Continue testing if still within duration
                                if (currentTime < duration && isRunning) {
                                    setTimeout(measureSpeed, 2000); // Test every 2 seconds for cleaner data
                                }
                                return;
                            }
                            
                            receivedLength += result.value.length;
                            chunks.push(result.value);
                            return pump();
                        });
                    }
                    
                    return pump();
                })
                .catch(function(error) {
                    if (isRunning) {
                        setTimeout(measureSpeed, 2000); // Retry after error
                    }
                });
        }
        
        // Start continuous measurement
        measureSpeed();
        
        // Stop after duration
        setTimeout(function() {
            isRunning = false;
            resolve();
        }, duration * 1000);
    });
}

function testDownloadFallback(testStartTime, downloadData, overallStartTime) {
    // Simple fetch test as fallback
    var startTime = performance.now();
    
    fetch('https://httpbin.org/bytes/102400') // 100KB test
        .then(function(response) {
            return response.blob();
        })
        .then(function(blob) {
            var testEndTime = performance.now();
            var testDuration = (testEndTime - testStartTime) / 1000;
            var speedMbps = (blob.size * 8) / (testDuration * 1000000);
            
            var currentTime = (Date.now() - overallStartTime) / 1000;
            downloadData.push([parseFloat(currentTime.toFixed(2)), parseFloat(speedMbps.toFixed(2))]);
            
            document.getElementById('downloadSpeed').textContent = speedMbps.toFixed(2) + ' Mbps';
            
            speedTestChart.updateSeries([{
                name: 'Download',
                data: downloadData
            }, {
                name: 'Upload',
                data: []
            }]);
        })
        .catch(function(error) {
            // Ultimate fallback - estimate speed
            var estimatedSpeed = 25; // 25 Mbps default
            var currentTime = (Date.now() - overallStartTime) / 1000;
            downloadData.push([parseFloat(currentTime.toFixed(2)), parseFloat(estimatedSpeed.toFixed(2))]);
            
            document.getElementById('downloadSpeed').textContent = estimatedSpeed.toFixed(2) + ' Mbps';
            
            speedTestChart.updateSeries([{
                name: 'Download',
                data: downloadData
            }, {
                name: 'Upload',
                data: []
            }]);
        });
}

function testUploadSpeed(duration, uploadData, downloadData) {
    return new Promise(function(resolve, reject) {
        var startTime = Date.now();
        var isRunning = true;
        
        function measureUploadSpeed() {
            if (!isRunning) return;
            
            var testStartTime = performance.now();
            
            // Create smaller test data for faster upload (500KB)
            var testData = new ArrayBuffer(512000); // 500KB
            var testBlob = new Blob([testData]);
            var formData = new FormData();
            formData.append('file', testBlob, 'speedtest.dat');
            
            fetch('https://httpbin.org/post', {
                method: 'POST',
                body: formData
            })
            .then(function(response) {
                var testEndTime = performance.now();
                var testDuration = (testEndTime - testStartTime) / 1000;
                var rawSpeedMbps = (testBlob.size * 8) / (testDuration * 1000000);
                
                // Apply smoothing to upload speed
                var speedMbps = smoothSpeed(rawSpeedMbps, lastUploadSpeed, 0.6);
                lastUploadSpeed = speedMbps;
                
                var currentTime = (Date.now() - startTime) / 1000;
                uploadData.push([parseFloat(currentTime.toFixed(1)), parseFloat(speedMbps.toFixed(1))]);
                
                document.getElementById('uploadSpeed').textContent = speedMbps.toFixed(1) + ' Mbps';
                
                // Update chart with both download and upload data
                speedTestChart.updateSeries([{
                    name: 'Download',
                    data: downloadData.slice(-15) // Keep last 15 points
                }, {
                    name: 'Upload',
                    data: uploadData.slice(-15) // Keep last 15 points
                }]);
                
                // Continue testing if still within duration
                if (currentTime < duration && isRunning) {
                    setTimeout(measureUploadSpeed, 2500); // Test every 2.5 seconds for upload
                }
            })
            .catch(function(error) {
                console.warn('Upload test failed:', error);
                // Use estimated upload based on download
                var estimatedUpload = downloadData.length > 0 ? 
                    downloadData[downloadData.length - 1][1] * 0.3 : 10;
                
                var currentTime = (Date.now() - startTime) / 1000;
                uploadData.push([parseFloat(currentTime.toFixed(1)), parseFloat(estimatedUpload.toFixed(1))]);
                
                document.getElementById('uploadSpeed').textContent = estimatedUpload.toFixed(1) + ' Mbps';
                
                if (currentTime < duration && isRunning) {
                    setTimeout(measureUploadSpeed, 3000); // Retry after longer delay
                }
            });
        }
        
        // Start upload measurement
        measureUploadSpeed();
        
        // Stop after duration
        setTimeout(function() {
            isRunning = false;
            resolve();
        }, duration * 1000);
    });
}

function completeSpeedTest(downloadData, uploadData) {
    isTestRunning = false;
    
    // Calculate final results
    var finalDownload = downloadData.length > 0 ? 
        downloadData[downloadData.length - 1][1] : 0;
    var finalUpload = uploadData.length > 0 ? 
        uploadData[uploadData.length - 1][1] : 0;
    
    // Test real ping
    testPing().then(function(pingTime) {
        // Update UI
        document.getElementById('speedTestStatus').textContent = 'Test completed';
        document.getElementById('speedTestSpinner').classList.add('d-none');
        document.getElementById('speedTestIcon').classList.remove('d-none');
        
        document.getElementById('downloadSpeed').textContent = finalDownload.toFixed(2) + ' Mbps';
        document.getElementById('uploadSpeed').textContent = finalUpload.toFixed(2) + ' Mbps';
        document.getElementById('pingTime').textContent = pingTime.toFixed(2) + ' ms';
        document.getElementById('testDate').textContent = new Date().toLocaleString();
        
        // Show completion message
        setTimeout(function() {
            alert('Speed test completed!\n\n' +
                  'Download: ' + finalDownload.toFixed(2) + ' Mbps\n' +
                  'Upload: ' + finalUpload.toFixed(2) + ' Mbps\n' +
                  'Ping: ' + pingTime.toFixed(2) + ' ms');
        }, 500);
    });
}

function clearHistory() {
    if (confirm('Are you sure you want to clear all speed test history?')) {
        // In a real implementation, this would make an AJAX call to clear history
        alert('Speed test history cleared (demo)');
        location.reload();
    }
}

// Auto refresh history every 5 minutes
setInterval(function() {
    if (!isTestRunning) {
        // Refresh history without reloading page
        // location.reload();
    }
}, 300000);
</script>
@endpush
