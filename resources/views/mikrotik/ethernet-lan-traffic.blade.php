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
            <button class="btn btn-primary btn-sm" onclick="refreshData()">
                <i class="fas fa-sync-alt"></i> Refresh
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
            <div class="card traffic-card">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-ethernet"></i> {{ $interface['name'] }}
                    </h6>
                    <span class="badge badge-{{ $interface['status'] === 'active' ? 'success' : 'danger' }}">
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
                            <small class="traffic-value">{{ $interface['utilization'] }}%</small>
                        </div>
                        <div class="progress">
                            <div class="progress-bar bg-{{ $interface['utilization'] > 80 ? 'danger' : ($interface['utilization'] > 60 ? 'warning' : 'success') }}" 
                                 style="width: {{ $interface['utilization'] }}%"></div>
                        </div>
                    </div>

                    <!-- Traffic Stats -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="text-center p-2 border rounded">
                                <small class="traffic-stat">Download</small><br>
                                <span class="traffic-value">{{ number_format($interface['traffic']['rx_bits_per_second'] / 1000000, 1) }} Mbps</span><br>
                                <small class="text-muted">{{ number_format($interface['traffic']['rx_packets_per_second']) }} pkt/s</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-center p-2 border rounded">
                                <small class="traffic-stat">Upload</small><br>
                                <span class="traffic-value">{{ number_format($interface['traffic']['tx_bits_per_second'] / 1000000, 1) }} Mbps</span><br>
                                <small class="text-muted">{{ number_format($interface['traffic']['tx_packets_per_second']) }} pkt/s</small>
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
                            <i class="fas fa-clock"></i> Last updated: {{ \Carbon\Carbon::parse($interface['last_updated'])->diffForHumans() }}
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

document.addEventListener('DOMContentLoaded', function() {
    initializeTrafficChart();
    initializeDataTable();
    
    // Auto-refresh every 30 seconds
    setInterval(refreshData, 30000);
});

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

function refreshData() {
    // Show loading indicator
    const refreshBtn = document.querySelector('button[onclick="refreshData()"]');
    const originalText = refreshBtn.innerHTML;
    refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Refreshing...';
    refreshBtn.disabled = true;
    
    // Fetch new data
    fetch('/mikrotik/ethernet-lan-traffic?json=1')
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                // Update the page with new data
                location.reload();
            } else {
                console.error('Failed to refresh data:', data.message);
                alert('Failed to refresh data: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error refreshing data:', error);
            alert('Error refreshing data. Please try again.');
        })
        .finally(() => {
            // Restore button
            refreshBtn.innerHTML = originalText;
            refreshBtn.disabled = false;
        });
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