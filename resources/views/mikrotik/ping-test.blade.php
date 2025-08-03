@extends('layouts.admin')

@section('main-content')
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-broadcast-tower text-primary"></i> Ping Test Tool
        </h1>
    </div>

    <!-- Ping Test Form -->
    <div class="row">
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-satellite-dish"></i> Network Connectivity Test
                    </h6>
                </div>
                <div class="card-body">
                    <form id="pingForm">
                        <div class="form-group">
                            <label for="host">Target Host/IP Address:</label>
                            <input type="text" class="form-control" id="host" name="host" 
                                   placeholder="e.g., google.com or 8.8.8.8" required>
                        </div>
                        <div class="form-group">
                            <label for="count">Number of Pings:</label>
                            <select class="form-control" id="count" name="count">
                                <option value="4">4 pings</option>
                                <option value="10">10 pings</option>
                                <option value="20">20 pings</option>
                                <option value="50">50 pings</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-play"></i> Start Ping Test
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="clearResults()">
                            <i class="fas fa-trash"></i> Clear Results
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-info-circle"></i> Quick Ping Targets
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6 mb-3">
                            <button class="btn btn-outline-primary btn-block" onclick="quickPing('8.8.8.8')">
                                <i class="fab fa-google"></i> Google DNS
                            </button>
                        </div>
                        <div class="col-6 mb-3">
                            <button class="btn btn-outline-info btn-block" onclick="quickPing('1.1.1.1')">
                                <i class="fas fa-cloud"></i> Cloudflare DNS
                            </button>
                        </div>
                        <div class="col-6 mb-3">
                            <button class="btn btn-outline-success btn-block" onclick="quickPing('google.com')">
                                <i class="fas fa-globe"></i> Google.com
                            </button>
                        </div>
                        <div class="col-6 mb-3">
                            <button class="btn btn-outline-warning btn-block" onclick="quickPing('192.168.1.1')">
                                <i class="fas fa-router"></i> Local Gateway
                            </button>
                        </div>
                    </div>
                    
                    <div class="alert alert-info mt-3">
                        <h6><i class="fas fa-lightbulb"></i> Tips:</h6>
                        <ul class="mb-0 small">
                            <li>Test internet connectivity with public DNS servers</li>
                            <li>Check local network with gateway IP</li>
                            <li>Monitor latency and packet loss</li>
                            <li>Use multiple pings for better accuracy</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Results Section -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4" id="resultsCard" style="display: none;">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-line"></i> Ping Results
                    </h6>
                </div>
                <div class="card-body">
                    <div id="loadingIndicator" class="text-center" style="display: none;">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">Running ping test...</span>
                        </div>
                        <p class="mt-2">Running ping test, please wait...</p>
                    </div>
                    
                    <div id="pingResults"></div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('#pingForm').on('submit', function(e) {
        e.preventDefault();
        
        var host = $('#host').val();
        var count = $('#count').val();
        
        if (!host) {
            alert('Please enter a host or IP address');
            return;
        }
        
        runPingTest(host, count);
    });
});

function quickPing(host) {
    $('#host').val(host);
    $('#count').val('4');
    runPingTest(host, 4);
}

function runPingTest(host, count) {
    // Show results card and loading indicator
    $('#resultsCard').show();
    $('#loadingIndicator').show();
    $('#pingResults').empty();
    
    $.ajax({
        url: '{{ route("mikrotik.ping-test") }}',
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        data: {
            host: host,
            count: count
        },
        success: function(response) {
            $('#loadingIndicator').hide();
            
            if (response.success) {
                displayPingResults(response.results, host, count);
            } else {
                displayError(response.error || 'Ping test failed');
            }
        },
        error: function(xhr, status, error) {
            $('#loadingIndicator').hide();
            displayError('Network error: ' + error);
        }
    });
}

function displayPingResults(results, host, count) {
    var html = '';
    
    // Summary header
    html += '<div class="alert alert-info">';
    html += '<h5><i class="fas fa-info-circle"></i> Ping Summary for ' + host + '</h5>';
    html += '<p>Sent ' + count + ' packets</p>';
    html += '</div>';
    
    if (results && results.length > 0) {
        // Statistics calculation
        var successful = 0;
        var totalTime = 0;
        var minTime = null;
        var maxTime = null;
        
        // Results table
        html += '<div class="table-responsive">';
        html += '<table class="table table-bordered table-sm">';
        html += '<thead><tr>';
        html += '<th>Sequence</th>';
        html += '<th>Status</th>';
        html += '<th>Response Time</th>';
        html += '<th>TTL</th>';
        html += '<th>Size</th>';
        html += '</tr></thead>';
        html += '<tbody>';
        
        results.forEach(function(result, index) {
            html += '<tr>';
            html += '<td>' + (index + 1) + '</td>';
            
            // Handle both MikroTik and system ping formats
            var status, time, ttl, size;
            
            if (result.hasOwnProperty('seq')) {
                // MikroTik format
                status = 'success';
                var timeStr = result.time || '';
                if (timeStr.includes('ms')) {
                    time = parseFloat(timeStr.replace(/ms.*/, ''));
                } else {
                    time = parseFloat(timeStr);
                }
                ttl = result.ttl || '';
                size = result.size || '';
            } else {
                // System ping format
                status = result.status || 'timeout';
                time = result.time;
                ttl = result.ttl || '';
                size = result.size || '';
            }
            
            if (status === 'timeout' || time === null) {
                html += '<td><span class="badge badge-danger">Timeout</span></td>';
                html += '<td>-</td>';
            } else {
                successful++;
                totalTime += time;
                
                if (minTime === null || time < minTime) minTime = time;
                if (maxTime === null || time > maxTime) maxTime = time;
                
                html += '<td><span class="badge badge-success">Success</span></td>';
                html += '<td>' + time.toFixed(2) + 'ms</td>';
            }
            
            html += '<td>' + (ttl || '-') + '</td>';
            html += '<td>' + (size || '-') + '</td>';
            html += '</tr>';
        });
        
        html += '</tbody></table>';
        html += '</div>';
        
        // Statistics summary
        var packetLoss = ((count - successful) / count * 100).toFixed(1);
        var avgTime = successful > 0 ? (totalTime / successful).toFixed(2) : 0;
        
        html += '<div class="row mt-3">';
        html += '<div class="col-md-3">';
        html += '<div class="card bg-primary text-white">';
        html += '<div class="card-body text-center">';
        html += '<h5>' + successful + '/' + count + '</h5>';
        html += '<small>Successful</small>';
        html += '</div></div></div>';
        
        html += '<div class="col-md-3">';
        html += '<div class="card ' + (packetLoss > 0 ? 'bg-danger' : 'bg-success') + ' text-white">';
        html += '<div class="card-body text-center">';
        html += '<h5>' + packetLoss + '%</h5>';
        html += '<small>Packet Loss</small>';
        html += '</div></div></div>';
        
        html += '<div class="col-md-3">';
        html += '<div class="card bg-info text-white">';
        html += '<div class="card-body text-center">';
        html += '<h5>' + avgTime + 'ms</h5>';
        html += '<small>Avg Time</small>';
        html += '</div></div></div>';
        
        html += '<div class="col-md-3">';
        html += '<div class="card bg-warning text-white">';
        html += '<div class="card-body text-center">';
        html += '<h5>' + ((maxTime || 0).toFixed(2)) + 'ms</h5>';
        html += '<small>Max Time</small>';
        html += '</div></div></div>';
        
        html += '</div>';
        
    } else {
        html += '<div class="alert alert-warning">';
        html += '<i class="fas fa-exclamation-triangle"></i> No ping results received';
        html += '</div>';
    }
    
    $('#pingResults').html(html);
}

function displayError(error) {
    var html = '<div class="alert alert-danger">';
    html += '<i class="fas fa-exclamation-circle"></i> <strong>Error:</strong> ' + error;
    html += '</div>';
    
    $('#pingResults').html(html);
}

function clearResults() {
    $('#resultsCard').hide();
    $('#pingResults').empty();
    $('#host').val('');
}
</script>
@endpush
