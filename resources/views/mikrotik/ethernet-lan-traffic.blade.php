@extends('layouts.admin')

@section('title', 'Ethernet LAN Traffic Monitoring')

@section('styles')
<style>
    .traffic-card {
        border-left: 4px solid #4e73df;
        transition: all 0.3s ease;
    }
    .traffic-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    .utilization-bar {
        height: 8px;
        border-radius: 4px;
        background: linear-gradient(90deg, #28a745 0%, #ffc107 50%, #dc3545 100%);
    }
    .traffic-stat {
        font-size: 0.9rem;
        color: #6c757d;
    }
    .traffic-value {
        font-weight: bold;
        color: #495057;
    }
    .status-active {
        color: #28a745;
    }
    .status-inactive {
        color: #dc3545;
    }
    .chart-container {
        min-height: 300px;
    }
    .connection-status {
        margin-left: 15px;
    }
    .connection-status .badge {
        font-size: 0.8rem;
        padding: 0.4rem 0.6rem;
    }
    .traffic-card.updating {
        opacity: 0.8;
        transition: opacity 0.3s ease;
    }
    .real-time-indicator {
        animation: pulse 2s infinite;
    }
    @keyframes pulse {
        0% { opacity: 1; }
        50% { opacity: 0.5; }
        100% { opacity: 1; }
    }
</style>
@endsection

@section('main-content')
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-network-wired"></i> Ethernet LAN Traffic Monitoring
        </h1>
        <div>
            <span class="text-muted mr-2">
                <i class="fas fa-clock"></i> Last refresh: <span id="lastRefreshTime">{{ now()->format('H:i:s') }}</span>
            </span>
            <button class="btn btn-primary btn-sm" onclick="refreshData()">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
            <button class="btn btn-success btn-sm" id="toggleMonitoringBtn" onclick="toggleRealTimeMonitoring()">
                <i class="fas fa-pause"></i> Pause
            </button>
            <button class="btn btn-info btn-sm" onclick="exportData()">
                <i class="fas fa-download"></i> Export
            </button>
        </div>
    </div>

    @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle"></i> {{ session('warning') }}
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-times-circle"></i> {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        </div>
    @endif

    <!-- Ethernet Interfaces Overview -->
    <div class="row mb-4">
        @foreach($ethernetTraffic as $interfaceName => $interface)
        <div class="col-xl-6 col-lg-6 col-md-12 mb-4">
            <div class="card traffic-card" data-interface="{{ $interfaceName }}">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-ethernet"></i> {{ $interface['name'] }}
                    </h6>
                    <span class="badge badge-{{ $interface['status'] === 'active' ? 'success' : 'danger' }} status-badge">
                        {{ ucfirst($interface['status']) }}
                    </span>
                </div>
                <div class="card-body">
                    <!-- Interface Info -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <small class="traffic-stat">MAC Address:</small><br>
                            <span class="traffic-value">{{ $interface['mac_address'] }}</span>
                        </div>
                        <div class="col-md-6">
                            <small class="traffic-stat">MTU:</small><br>
                            <span class="traffic-value">{{ $interface['mtu'] }}</span>
                        </div>
                    </div>

                    <!-- Utilization -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <small class="traffic-stat">Utilization</small>
                            <small class="traffic-value utilization-text">{{ $interface['utilization'] }}%</small>
                        </div>
                        <div class="progress">
                            <div class="progress-bar bg-{{ $interface['utilization'] > 80 ? 'danger' : ($interface['utilization'] > 60 ? 'warning' : 'success') }} utilization-bar" 
                                 style="width: {{ $interface['utilization'] }}%"></div>
                        </div>
                    </div>

                    <!-- Traffic Stats -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="text-center p-2 border rounded">
                                <small class="traffic-stat">Download</small><br>
                                <span class="traffic-value download-speed">{{ number_format($interface['traffic']['rx_bits_per_second'] / 1000000, 1) }} Mbps</span><br>
                                <small class="text-muted rx-packets">{{ number_format($interface['traffic']['rx_packets_per_second']) }} pkt/s</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-center p-2 border rounded">
                                <small class="traffic-stat">Upload</small><br>
                                <span class="traffic-value upload-speed">{{ number_format($interface['traffic']['tx_bits_per_second'] / 1000000, 1) }} Mbps</span><br>
                                <small class="text-muted tx-packets">{{ number_format($interface['traffic']['tx_packets_per_second']) }} pkt/s</small>
                            </div>
                        </div>
                    </div>

                    <!-- Error Stats -->
                    <div class="row mt-3">
                        <div class="col-md-4">
                            <small class="traffic-stat">RX Errors:</small><br>
                            <span class="traffic-value text-{{ $interface['errors']['rx_errors'] > 0 ? 'danger' : 'success' }}">
                                {{ number_format($interface['errors']['rx_errors']) }}
                            </span>
                        </div>
                        <div class="col-md-4">
                            <small class="traffic-stat">TX Errors:</small><br>
                            <span class="traffic-value text-{{ $interface['errors']['tx_errors'] > 0 ? 'danger' : 'success' }}">
                                {{ number_format($interface['errors']['tx_errors']) }}
                            </span>
                        </div>
                        <div class="col-md-4">
                            <small class="traffic-stat">Collisions:</small><br>
                            <span class="traffic-value text-{{ $interface['errors']['collisions'] > 0 ? 'warning' : 'success' }}">
                                {{ number_format($interface['errors']['collisions']) }}
                            </span>
                        </div>
                    </div>
                    
                    <!-- Total Errors -->
                    <div class="row mt-2">
                        <div class="col-12">
                            <small class="traffic-stat">Total Errors:</small><br>
                            <span class="traffic-value error-count text-{{ ($interface['errors']['rx_errors'] + $interface['errors']['tx_errors'] + $interface['errors']['collisions']) > 0 ? 'danger' : 'success' }}">
                                {{ number_format($interface['errors']['rx_errors'] + $interface['errors']['tx_errors'] + $interface['errors']['collisions']) }}
                            </span>
                        </div>
                    </div>

                    <!-- Packet Stats -->
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <small class="traffic-stat">Total RX Packets:</small><br>
                            <span class="traffic-value">{{ number_format($interface['packets']['rx_packets']) }}</span>
                        </div>
                        <div class="col-md-6">
                            <small class="traffic-stat">Total TX Packets:</small><br>
                            <span class="traffic-value">{{ number_format($interface['packets']['tx_packets']) }}</span>
                        </div>
                    </div>

                    <!-- Last Updated -->
                    <div class="mt-3 text-center">
                        <small class="text-muted">
                            <i class="fas fa-clock"></i> Last updated: <span class="last-updated">{{ \Carbon\Carbon::parse($interface['last_updated'])->format('H:i:s') }}</span>
                        </small>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Traffic History Charts -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-line"></i> Traffic History (Last 24 Hours)
                    </h6>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="trafficHistoryChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Traffic Summary -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-table"></i> Traffic Summary
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="trafficSummaryTable">
                            <thead>
                                <tr>
                                    <th>Interface</th>
                                    <th>Status</th>
                                    <th>Download</th>
                                    <th>Upload</th>
                                    <th>Utilization</th>
                                    <th>Errors</th>
                                    <th>Last Updated</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($ethernetTraffic as $interfaceName => $interface)
                                <tr>
                                    <td>
                                        <strong>{{ $interface['name'] }}</strong><br>
                                        <small class="text-muted">{{ $interface['mac_address'] }}</small>
                                    </td>
                                    <td>
                                        <span class="badge badge-{{ $interface['status'] === 'active' ? 'success' : 'danger' }}">
                                            {{ ucfirst($interface['status']) }}
                                        </span>
                                    </td>
                                    <td>
                                        <strong>{{ number_format($interface['traffic']['rx_bits_per_second'] / 1000000, 1) }} Mbps</strong><br>
                                        <small class="text-muted">{{ number_format($interface['traffic']['rx_packets_per_second']) }} pkt/s</small>
                                    </td>
                                    <td>
                                        <strong>{{ number_format($interface['traffic']['tx_bits_per_second'] / 1000000, 1) }} Mbps</strong><br>
                                        <small class="text-muted">{{ number_format($interface['traffic']['tx_packets_per_second']) }} pkt/s</small>
                                    </td>
                                    <td>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar bg-{{ $interface['utilization'] > 80 ? 'danger' : ($interface['utilization'] > 60 ? 'warning' : 'success') }}" 
                                                 style="width: {{ $interface['utilization'] }}%">
                                                {{ $interface['utilization'] }}%
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @php
                                            $totalErrors = $interface['errors']['rx_errors'] + $interface['errors']['tx_errors'] + $interface['errors']['collisions'];
                                        @endphp
                                        <span class="badge badge-{{ $totalErrors > 0 ? 'danger' : 'success' }}">
                                            {{ $totalErrors }} errors
                                        </span>
                                    </td>
                                    <td>
                                        <small>{{ \Carbon\Carbon::parse($interface['last_updated'])->format('H:i:s') }}</small>
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
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let trafficHistoryChart;

// Real-time monitoring variables
let refreshInterval;
let isRefreshing = false;
let consecutiveErrors = 0;
const MAX_CONSECUTIVE_ERRORS = 5;
const REFRESH_INTERVAL = 2000; // 2 seconds
const ERROR_BACKOFF_MULTIPLIER = 2;

// Global functions accessible from onclick attributes
function refreshData() {
    // Prevent multiple simultaneous requests
    if (isRefreshing) {
        console.log('Refresh already in progress, skipping...');
        return;
    }
    
    isRefreshing = true;
    
    // Show loading indicator
    const refreshBtn = document.querySelector('button[onclick="refreshData()"]');
    const originalText = refreshBtn.innerHTML;
    refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Refreshing...';
    refreshBtn.disabled = true;
    
    // Fetch new data via AJAX
    console.log('Fetching real-time traffic data...');
    fetch('/real-time-traffic')
        .then(response => {
            console.log('Response status:', response.status);
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.status === 'success') {
                // Update traffic data without page reload
                updateTrafficData(data.ethernetTraffic);
                updateLastRefreshTime();
                
                // Reset error counter on success
                consecutiveErrors = 0;
                updateConnectionStatus(true);
                
                // Reset to normal interval
                if (refreshInterval) {
                    clearInterval(refreshInterval);
                    refreshInterval = setInterval(refreshData, REFRESH_INTERVAL);
                }
            } else {
                throw new Error(data.message || 'Unknown error');
            }
        })
        .catch(error => {
            console.error('Error refreshing data:', error);
            consecutiveErrors++;
            
            // Update connection status
            updateConnectionStatus(false, `Error: ${error.message}`);
            
            // Implement exponential backoff for errors
            if (consecutiveErrors >= MAX_CONSECUTIVE_ERRORS) {
                const backoffInterval = REFRESH_INTERVAL * Math.pow(ERROR_BACKOFF_MULTIPLIER, Math.min(consecutiveErrors - MAX_CONSECUTIVE_ERRORS, 3));
                console.warn(`Too many consecutive errors (${consecutiveErrors}), backing off to ${backoffInterval}ms`);
                
                if (refreshInterval) {
                    clearInterval(refreshInterval);
                    refreshInterval = setInterval(refreshData, backoffInterval);
                }
            }
            
            // Don't show alert for auto-refresh, only log
            if (!refreshBtn.disabled) {
                console.warn('Auto-refresh failed, will retry in 2 seconds');
            }
        })
        .finally(() => {
            isRefreshing = false;
            
            // Restore button only if it was manually clicked
            if (refreshBtn.disabled) {
                refreshBtn.innerHTML = originalText;
                refreshBtn.disabled = false;
            }
        });
}

function toggleRealTimeMonitoring() {
    const toggleBtn = document.getElementById('toggleMonitoringBtn');
    const isMonitoring = refreshInterval !== null;
    
    if (isMonitoring) {
        // Pause monitoring
        stopRealTimeMonitoring();
        toggleBtn.innerHTML = '<i class="fas fa-play"></i> Resume';
        toggleBtn.className = 'btn btn-warning btn-sm';
        updateConnectionStatus(false, 'Paused');
    } else {
        // Resume monitoring
        startRealTimeMonitoring();
        toggleBtn.innerHTML = '<i class="fas fa-pause"></i> Pause';
        toggleBtn.className = 'btn btn-success btn-sm';
        updateConnectionStatus(true);
    }
}

// Initialize real-time monitoring
document.addEventListener('DOMContentLoaded', function() {
    initializeTrafficChart();
    initializeDataTable();
    
    // Start real-time monitoring
    startRealTimeMonitoring();
    
    // Add connection status indicator
    addConnectionStatusIndicator();
});

function startRealTimeMonitoring() {
    // Clear any existing interval
    if (refreshInterval) {
        clearInterval(refreshInterval);
    }
    
    // Start new interval
    refreshInterval = setInterval(refreshData, REFRESH_INTERVAL);
    console.log('Real-time monitoring started with 2-second interval');
}

function stopRealTimeMonitoring() {
    if (refreshInterval) {
        clearInterval(refreshInterval);
        refreshInterval = null;
        console.log('Real-time monitoring stopped');
    }
}

function addConnectionStatusIndicator() {
    const header = document.querySelector('.d-sm-flex.align-items-center.justify-content-between');
    if (header) {
        const statusDiv = document.createElement('div');
        statusDiv.className = 'connection-status';
        statusDiv.innerHTML = `
            <span class="badge badge-success" id="connectionStatus">
                <i class="fas fa-wifi"></i> Connected
            </span>
        `;
        header.appendChild(statusDiv);
    }
}

function updateConnectionStatus(isConnected, message = '') {
    const statusElement = document.getElementById('connectionStatus');
    if (statusElement) {
        if (isConnected) {
            statusElement.className = 'badge badge-success';
            statusElement.innerHTML = '<i class="fas fa-wifi"></i> Connected';
        } else {
            statusElement.className = 'badge badge-danger';
            statusElement.innerHTML = `<i class="fas fa-exclamation-triangle"></i> ${message || 'Disconnected'}`;
        }
    }
}

function initializeTrafficChart() {
    const ctx = document.getElementById('trafficHistoryChart').getContext('2d');
    
    // Prepare data for chart
    const chartData = @json($trafficHistory);
    const labels = [];
    const datasets = [];
    
    // Get timestamps from first interface
    const firstInterface = Object.keys(chartData)[0];
    if (firstInterface && chartData[firstInterface]) {
        chartData[firstInterface].forEach((point, index) => {
            labels.push(new Date(point.timestamp).toLocaleTimeString());
        });
    }
    
    // Create datasets for each interface
    Object.keys(chartData).forEach((interfaceName, index) => {
        const colors = ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b'];
        
        datasets.push({
            label: `${interfaceName} - Download`,
            data: chartData[interfaceName].map(point => point.rx_bits_per_second / 1000000),
            borderColor: colors[index % colors.length],
            backgroundColor: colors[index % colors.length] + '20',
            borderWidth: 2,
            fill: false,
            tension: 0.4
        });
        
        datasets.push({
            label: `${interfaceName} - Upload`,
            data: chartData[interfaceName].map(point => point.tx_bits_per_second / 1000000),
            borderColor: colors[index % colors.length],
            backgroundColor: colors[index % colors.length] + '10',
            borderWidth: 2,
            borderDash: [5, 5],
            fill: false,
            tension: 0.4
        });
    });
    
    trafficHistoryChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: datasets
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Ethernet LAN Traffic History'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Bandwidth (Mbps)'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Time'
                    }
                }
            }
        }
    });
}

function initializeDataTable() {
    $('#trafficSummaryTable').DataTable({
        responsive: true,
        order: [[0, 'asc']],
        pageLength: 10,
        language: {
            search: "Cari:",
            lengthMenu: "Tampilkan _MENU_ data per halaman",
            zeroRecords: "Tidak ada data yang ditemukan",
            info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
            infoEmpty: "Menampilkan 0 sampai 0 dari 0 data",
            infoFiltered: "(difilter dari _MAX_ total data)",
            paginate: {
                first: "Pertama",
                last: "Terakhir",
                next: "Selanjutnya",
                previous: "Sebelumnya"
            }
        }
    });
}

function updateTrafficData(ethernetTraffic) {
    // Update each interface card with new data
    Object.keys(ethernetTraffic).forEach(interfaceName => {
        const interface = ethernetTraffic[interfaceName];
        const card = document.querySelector(`[data-interface="${interfaceName}"]`);
        
        if (card) {
            // Add updating effect
            card.classList.add('updating');
            
            // Remove updating effect after animation
            setTimeout(() => {
                card.classList.remove('updating');
            }, 300);
            // Update utilization
            const utilizationBar = card.querySelector('.utilization-bar');
            const utilizationText = card.querySelector('.utilization-text');
            if (utilizationBar && utilizationText) {
                utilizationBar.style.width = `${interface.utilization}%`;
                utilizationText.textContent = `${interface.utilization}%`;
            }
            
            // Update download speed
            const downloadSpeed = card.querySelector('.download-speed');
            if (downloadSpeed) {
                downloadSpeed.textContent = `${(interface.traffic.rx_bits_per_second / 1000000).toFixed(1)} Mbps`;
            }
            
            // Update upload speed
            const uploadSpeed = card.querySelector('.upload-speed');
            if (uploadSpeed) {
                uploadSpeed.textContent = `${(interface.traffic.tx_bits_per_second / 1000000).toFixed(1)} Mbps`;
            }
            
            // Update packet rates
            const rxPackets = card.querySelector('.rx-packets');
            const txPackets = card.querySelector('.tx-packets');
            if (rxPackets) rxPackets.textContent = interface.traffic.rx_packets_per_second;
            if (txPackets) txPackets.textContent = interface.traffic.tx_packets_per_second;
            
            // Update error counts
            const errorCount = card.querySelector('.error-count');
            if (errorCount) {
                const totalErrors = interface.errors.rx_errors + interface.errors.tx_errors + interface.errors.collisions;
                errorCount.textContent = totalErrors;
            }
            
            // Update status badge
            const statusBadge = card.querySelector('.status-badge');
            if (statusBadge) {
                statusBadge.className = `badge badge-${interface.status === 'active' ? 'success' : 'danger'} status-badge`;
                statusBadge.textContent = interface.status.charAt(0).toUpperCase() + interface.status.slice(1);
            }
            
            // Update last updated time
            const lastUpdated = card.querySelector('.last-updated');
            if (lastUpdated) {
                lastUpdated.textContent = new Date(interface.last_updated).toLocaleTimeString();
            }
        }
    });
    
    // Update summary table
    updateSummaryTable(ethernetTraffic);
}

function updateSummaryTable(ethernetTraffic) {
    const tableBody = document.querySelector('#trafficSummaryTable tbody');
    if (tableBody) {
        tableBody.innerHTML = '';
        
        Object.keys(ethernetTraffic).forEach(interfaceName => {
            const interface = ethernetTraffic[interfaceName];
            const row = document.createElement('tr');
            
            row.innerHTML = `
                <td>${interface.name}</td>
                <td><span class="badge badge-${interface.status === 'active' ? 'success' : 'danger'}">${interface.status}</span></td>
                <td>${(interface.traffic.rx_bits_per_second / 1000000).toFixed(1)} Mbps</td>
                <td>${(interface.traffic.tx_bits_per_second / 1000000).toFixed(1)} Mbps</td>
                <td>${interface.utilization}%</td>
                <td>${interface.errors.rx_errors + interface.errors.tx_errors + interface.errors.collisions}</td>
                <td>${new Date(interface.last_updated).toLocaleTimeString()}</td>
            `;
            
            tableBody.appendChild(row);
        });
    }
}

function updateLastRefreshTime() {
    const refreshTimeElement = document.getElementById('lastRefreshTime');
    if (refreshTimeElement) {
        refreshTimeElement.textContent = new Date().toLocaleTimeString();
    }
}

function exportData() {
    // Create CSV content
    let csvContent = "data:text/csv;charset=utf-8,";
    csvContent += "Interface,Status,Download (Mbps),Upload (Mbps),Utilization (%),Errors,Last Updated\n";
    
    @foreach($ethernetTraffic as $interfaceName => $interface)
    csvContent += "{{ $interface['name'] }},{{ $interface['status'] }},{{ number_format($interface['traffic']['rx_bits_per_second'] / 1000000, 1) }},{{ number_format($interface['traffic']['tx_bits_per_second'] / 1000000, 1) }},{{ $interface['utilization'] }},{{ $interface['errors']['rx_errors'] + $interface['errors']['tx_errors'] + $interface['errors']['collisions'] }},{{ \Carbon\Carbon::parse($interface['last_updated'])->format('Y-m-d H:i:s') }}\n";
    @endforeach
    
    // Create download link
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", "ethernet_lan_traffic_{{ date('Y-m-d_H-i-s') }}.csv");
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}
</script>
@endsection 