@extends('layouts.admin')

@section('title', 'Usage Statistics')

@section('main-content')
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Usage Statistics</h1>
            <p class="text-muted mb-0">Data usage analytics and insights for {{ $startDate }} to {{ $endDate }}</p>
        </div>
        <div>
            <a href="{{ route('usage-logs.index') }}" class="btn btn-sm btn-outline-primary mr-2">
                <i class="fas fa-arrow-left"></i> Back to Usage Logs
            </a>
            <button onclick="syncFromMikrotik()" class="btn btn-sm btn-warning mr-2" style="font-size: 0.775rem; padding: 0.25rem 0.5rem;">
                <i class="fas fa-download"></i> Sync from MikroTik
            </button>
            <button onclick="location.reload()" class="btn btn-sm btn-info" style="font-size: 0.775rem; padding: 0.25rem 0.5rem;">
                <i class="fas fa-sync-alt"></i>
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <!-- Date Range Filter -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-calendar-alt"></i> Date Range Filter
            </h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('usage-logs.statistics') }}">
                <div class="row align-items-end">
                    <div class="col-md-3">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input type="date" class="form-control" name="start_date" id="start_date" 
                               value="{{ $startDate }}" max="{{ date('Y-m-d') }}">
                    </div>
                    <div class="col-md-3">
                        <label for="end_date" class="form-label">End Date</label>
                        <input type="date" class="form-control" name="end_date" id="end_date" 
                               value="{{ $endDate }}" max="{{ date('Y-m-d') }}">
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex">
                            <button type="submit" class="btn btn-sm btn-primary mr-2" style="font-size: 0.775rem; padding: 0.25rem 0.5rem;">
                                <i class="fas fa-search"></i> Filter
                            </button>
                            <a href="{{ route('usage-logs.statistics') }}" class="btn btn-sm btn-secondary" style="font-size: 0.775rem; padding: 0.25rem 0.5rem;">
                                <i class="fas fa-sync-alt"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Summary Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Data Usage
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $totalStats['total_bytes_formatted'] }}
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
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Average Daily Usage
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $totalStats['avg_daily_bytes_formatted'] }}
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
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Peak Daily Usage
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $totalStats['max_daily_bytes_formatted'] }}
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
                                Total Sessions
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ number_format($totalStats['total_sessions']) }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-history fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Additional Statistics Row -->
    <div class="row mb-4">
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Unique Active Users
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ number_format($totalStats['total_unique_users']) }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Avg Daily Sessions
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ number_format($totalStats['avg_daily_sessions'], 1) }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                Peak Daily Sessions
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ number_format($totalStats['max_daily_sessions']) }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-signal fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Daily Usage Chart -->
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-area"></i> Daily Data Usage Trend
                    </h6>
                </div>
                <div class="card-body">
                    <div id="usageChart" style="width: 100%; height: 400px; min-height: 400px; background-color: #ffffff; border: 1px dashed #e3e6f0; border-radius: 5px;"></div>
                    @if(empty($dailyStats) || $dailyStats->isEmpty())
                        <div class="text-center py-4" id="noDataMessage">
                            <i class="fas fa-chart-bar text-muted" style="font-size: 3rem;"></i>
                            <h5 class="mt-3 text-muted">No Data Available</h5>
                            <p class="text-muted">No usage data found for the selected date range.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Top Users -->
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-trophy"></i> Top Data Users
                    </h6>
                </div>
                <div class="card-body">
                    @if($topUsers->isEmpty())
                        <div class="text-center py-4">
                            <i class="fas fa-users text-muted" style="font-size: 3rem;"></i>
                            <h5 class="mt-3 text-muted">No Users Found</h5>
                            <p class="text-muted">No user data for the selected period.</p>
                        </div>
                    @else
                        @foreach($topUsers as $index => $user)
                            <div class="d-flex align-items-center mb-3">
                                <div class="mr-3">
                                    @if($index === 0)
                                        <i class="fas fa-trophy text-warning" style="font-size: 1.5rem;"></i>
                                    @elseif($index === 1)
                                        <i class="fas fa-medal text-secondary" style="font-size: 1.5rem;"></i>
                                    @elseif($index === 2)
                                        <i class="fas fa-award text-info" style="font-size: 1.5rem;"></i>
                                    @else
                                        <span class="badge badge-light" style="font-size: 1rem; padding: 0.5rem;">{{ $index + 1 }}</span>
                                    @endif
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <strong>{{ $user['username'] ?? 'Unknown User' }}</strong>
                                            @if($user['customer_name'])
                                                <br><small class="text-muted">{{ $user['customer_name'] }}</small>
                                            @endif
                                        </div>
                                        <div class="text-right">
                                            <strong class="text-primary">{{ $user['total_bytes_formatted'] }}</strong>
                                            <br><small class="text-muted">{{ number_format($user['session_count']) }} sessions</small>
                                        </div>
                                    </div>
                                    <div class="progress mt-2" style="height: 5px;">
                                        @php
                                            $maxBytes = $topUsers->first()['total_bytes'] ?? 1;
                                            $percentage = $maxBytes > 0 ? ($user['total_bytes'] / $maxBytes) * 100 : 0;
                                        @endphp
                                        <div class="progress-bar bg-primary" role="progressbar" 
                                             style="width: {{ $percentage }}%"></div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Daily Statistics Table -->
    @if(!$dailyStats->isEmpty())
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-table"></i> Daily Statistics Breakdown
                </h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="dailyStatsTable">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Data Usage</th>
                                <th>Unique Users</th>
                                <th>Total Sessions</th>
                                <th>Avg per User</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($dailyStats as $stat)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($stat['date'])->format('d M Y') }}</td>
                                    <td>
                                        <strong>{{ $stat['total_bytes_formatted'] }}</strong>
                                    </td>
                                    <td>
                                        <span class="badge badge-info">{{ number_format($stat['unique_users']) }}</span>
                                    </td>
                                    <td>
                                        <span class="badge badge-primary">{{ number_format($stat['session_count']) }}</span>
                                    </td>
                                    <td>
                                        @if($stat['unique_users'] > 0)
                                            {{ \App\Models\UsageLog::formatBytes($stat['total_bytes'] / $stat['unique_users']) }}
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts@latest"></script>
<script>
$(document).ready(function() {
    // Initialize DataTable for daily stats
    $('#dailyStatsTable').DataTable({
        "pageLength": 15,
        "order": [[ 0, "desc" ]],
        "responsive": true
    });

    // Check if ApexCharts is loaded
    if (typeof ApexCharts === 'undefined') {
        $('#usageChart').html('<div class="alert alert-danger text-center"><i class="fas fa-exclamation-triangle"></i> ApexCharts library failed to load</div>');
        return;
    }
    
    // Initialize chart
    initializeChart();
    
    function initializeChart() {
        const chartContainer = document.getElementById('usageChart');
        if (!chartContainer) {
            return;
        }
        
        @if(!empty($chartData))
            const chartData = @json($chartData);
            
            // Check if we have valid data
            const hasValidData = chartData && 
                                 Array.isArray(chartData.labels) && 
                                 chartData.labels.length > 0 && 
                                 Array.isArray(chartData.bytes) && 
                                 chartData.bytes.length > 0;
            
            if (hasValidData) {
                // Convert bytes to MB and ensure positive values
                const dataUsageMB = chartData.bytes.map(bytes => {
                    // Handle negative or null values
                    const validBytes = Math.max(0, bytes || 0);
                    return parseFloat((validBytes / 1024 / 1024).toFixed(2));
                });
                
                // Ensure users array has same length and valid values
                const validUsers = chartData.users.map(users => Math.max(0, users || 0));
                
                // Simple chart configuration
                const options = {
                    chart: {
                        type: 'line',
                        height: 380,
                        toolbar: {
                            show: true,
                            tools: {
                                download: true,
                                selection: false,
                                zoom: true,
                                zoomin: true,
                                zoomout: true,
                                pan: true,
                                reset: true
                            }
                        },
                        animations: {
                            enabled: true,
                            easing: 'easeinout',
                            speed: 800
                        }
                    },
                    series: [{
                        name: 'Data Usage (MB)',
                        data: dataUsageMB,
                        type: 'area'
                    }, {
                        name: 'Active Users',
                        data: validUsers,
                        type: 'line'
                    }],
                    xaxis: {
                        categories: chartData.labels.map(date => {
                            return new Date(date).toLocaleDateString('en-US', { 
                                month: 'short', 
                                day: 'numeric'
                            });
                        }),
                        title: {
                            text: 'Date',
                            style: {
                                fontSize: '12px',
                                fontWeight: 'bold'
                            }
                        }
                    },
                    yaxis: [{
                        title: {
                            text: 'Data Usage (MB)',
                            style: {
                                color: '#4e73df',
                                fontSize: '12px'
                            }
                        },
                        labels: {
                            style: {
                                colors: '#4e73df'
                            },
                            formatter: function (value) {
                                if (value >= 1024) {
                                    return (value / 1024).toFixed(1) + ' GB';
                                }
                                return value.toFixed(0) + ' MB';
                            }
                        }
                    }, {
                        opposite: true,
                        title: {
                            text: 'Active Users',
                            style: {
                                color: '#e74a3b',
                                fontSize: '12px'
                            }
                        },
                        labels: {
                            style: {
                                colors: '#e74a3b'
                            }
                        }
                    }],
                    colors: ['#4e73df', '#e74a3b'],
                    stroke: {
                        width: [2, 3],
                        curve: 'smooth'
                    },
                    fill: {
                        type: ['gradient', 'solid'],
                        gradient: {
                            shadeIntensity: 1,
                            opacityFrom: 0.4,
                            opacityTo: 0.1,
                            stops: [0, 90, 100]
                        }
                    },
                    title: {
                        text: 'Daily Usage Trends',
                        align: 'center',
                        style: {
                            fontSize: '16px',
                            fontWeight: 'bold',
                            color: '#5a5c69'
                        }
                    },
                    legend: {
                        show: true,
                        position: 'top',
                        horizontalAlign: 'center'
                    },
                    tooltip: {
                        shared: true,
                        intersect: false,
                        y: [{
                            formatter: function (value) {
                                if (value >= 1024) {
                                    return (value / 1024).toFixed(2) + ' GB';
                                }
                                return value.toFixed(1) + ' MB';
                            }
                        }, {
                            formatter: function (value) {
                                return value + ' users';
                            }
                        }]
                    },
                    grid: {
                        borderColor: '#e3e6f0',
                        strokeDashArray: 3
                    },
                    markers: {
                        size: [0, 5],
                        strokeWidth: 2,
                        strokeColors: '#ffffff',
                        hover: {
                            size: 7
                        }
                    }
                };

                try {
                    // Clear container first
                    chartContainer.innerHTML = '';
                    
                    const chart = new ApexCharts(chartContainer, options);
                    
                    chart.render().then(() => {
                        // Chart rendered successfully
                    }).catch((error) => {
                        chartContainer.innerHTML = `<div class="alert alert-warning text-center">
                            <i class="fas fa-exclamation-triangle"></i> 
                            Chart rendering failed: ${error.message}
                        </div>`;
                    });
                    
                } catch (error) {
                    chartContainer.innerHTML = `<div class="alert alert-danger text-center">
                        <i class="fas fa-times-circle"></i> 
                        Chart creation failed: ${error.message}
                    </div>`;
                }
                
            } else {
                chartContainer.innerHTML = `<div class="text-center text-muted py-5">
                    <i class="fas fa-chart-area fa-3x mb-3"></i>
                    <p class="mb-0">No usage data available for chart display</p>
                </div>`;
            }
        @else
            chartContainer.innerHTML = `<div class="text-center text-muted py-5">
                <i class="fas fa-chart-area fa-3x mb-3"></i>
                <p class="mb-0">No data loaded from server</p>
            </div>`;
        @endif
    }
});

// Function to sync usage logs from MikroTik
function syncFromMikrotik() {
    // Show loading state
    const button = event.target.closest('button');
    const originalHtml = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Syncing...';
    
    // Make AJAX request to sync
    fetch('{{ route("usage-logs.sync-from-mikrotik") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Show success message
            showAlert('success', data.message);
            // Reload page after short delay to show updated data
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            showAlert('danger', data.message || 'Sync failed');
        }
    })
    .catch(error => {
        // More detailed error messages
        let errorMessage = 'Network error occurred during sync';
        
        if (error.name === 'TypeError' && error.message.includes('fetch')) {
            errorMessage = 'Connection failed - please check your internet connection';
        } else if (error.message.includes('HTTP 500')) {
            errorMessage = 'Server error - please try again in a moment';
        } else if (error.message.includes('HTTP 408') || error.message.includes('timeout')) {
            errorMessage = 'Request timeout - MikroTik may be slow, please try again';
        } else if (error.message.includes('HTTP 404')) {
            errorMessage = 'Sync endpoint not found - please refresh the page';
        } else if (error.message.includes('HTTP')) {
            errorMessage = `Server returned error: ${error.message}`;
        }
        
        showAlert('danger', errorMessage);
    })
    .finally(() => {
        // Restore button state
        button.disabled = false;
        button.innerHTML = originalHtml;
    });
}

// Function to show alert messages
function showAlert(type, message) {
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    `;
    
    // Insert alert after page header
    const pageHeader = document.querySelector('.d-sm-flex');
    if (pageHeader && pageHeader.parentNode) {
        pageHeader.parentNode.insertAdjacentHTML('afterend', alertHtml);
    }
}
</script>
@endpush
