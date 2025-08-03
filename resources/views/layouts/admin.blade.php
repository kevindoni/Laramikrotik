<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Laravel SB Admin 2">
    <meta name="author" content="Alejandro RH">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link href="{{ asset('vendor/fontawesome-free/css/all.min.css') }}" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">

    <!-- Styles -->
    <link href="{{ asset('css/sb-admin-2.min.css') }}" rel="stylesheet">
    
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap4.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.bootstrap4.min.css" rel="stylesheet">
    
    <!-- SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.27/dist/sweetalert2.min.css" rel="stylesheet">
    
    <!-- Toastr CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet">

    <!-- Favicon -->
    <link href="{{ asset('img/favicon.png') }}" rel="icon" type="image/png">
    
    @stack('styles')
</head>
<body id="page-top">

<!-- Page Wrapper -->
<div id="wrapper">
    <!-- Sidebar -->
    <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

        <!-- Sidebar - Brand -->
        <a class="sidebar-brand d-flex align-items-center justify-content-center" href="{{ url('/home') }}">
            <div class="sidebar-brand-icon rotate-n-15">
                <i class="fas fa-network-wired"></i>
            </div>
            <div class="sidebar-brand-text mx-3">MikroTik <sup>PPP</sup></div>
        </a>

        <!-- Divider -->
        <hr class="sidebar-divider my-0">

        <!-- Nav Item - Dashboard -->
        <li class="nav-item {{ Nav::isRoute('home') }}">
            <a class="nav-link" href="{{ route('home') }}">
                <i class="fas fa-fw fa-tachometer-alt"></i>
                <span>{{ __('Dashboard') }}</span></a>
        </li>

        <!-- Divider -->
        <hr class="sidebar-divider">

        <!-- Heading -->
        <div class="sidebar-heading">
            {{ __('Pelanggan') }}
        </div>

        <!-- Nav Item - Customers -->
        <li class="nav-item">
            <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseCustomers" aria-expanded="true" aria-controls="collapseCustomers">
                <i class="fas fa-fw fa-users"></i>
                <span>Kelola Pelanggan</span>
            </a>
            <div id="collapseCustomers" class="collapse" aria-labelledby="headingCustomers" data-parent="#accordionSidebar">
                <div class="bg-white py-2 collapse-inner rounded">
                    <h6 class="collapse-header">Manajemen Pelanggan:</h6>
                    <a class="collapse-item" href="{{ route('customers.index') }}">Data Pelanggan</a>
                    <a class="collapse-item" href="{{ route('customers.create') }}">Tambah Pelanggan</a>
                    <a class="collapse-item" href="{{ route('customers.inactive') }}">Pelanggan Nonaktif</a>
                </div>
            </div>
        </li>

        <!-- Nav Item - PPP Secrets -->
        <li class="nav-item">
            <a class="nav-link" href="{{ route('ppp-secrets.index') }}">
                <i class="fas fa-fw fa-key"></i>
                <span>PPP Secrets</span>
            </a>
        </li>

        <!-- Nav Item - PPP Profiles -->
        <li class="nav-item">
            <a class="nav-link" href="{{ route('ppp-profiles.index') }}">
                <i class="fas fa-fw fa-cogs"></i>
                <span>PPP Profiles</span>
            </a>
        </li>

        <!-- Divider -->
        <hr class="sidebar-divider">

        <!-- Heading -->
        <div class="sidebar-heading">
            {{ __('Billing') }}
        </div>

        <!-- Nav Item - Invoices -->
        <li class="nav-item">
            <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseInvoices" aria-expanded="true" aria-controls="collapseInvoices">
                <i class="fas fa-fw fa-file-invoice"></i>
                <span>Invoice & Tagihan</span>
            </a>
            <div id="collapseInvoices" class="collapse" aria-labelledby="headingInvoices" data-parent="#accordionSidebar">
                <div class="bg-white py-2 collapse-inner rounded">
                    <h6 class="collapse-header">Manajemen Invoice:</h6>
                    <a class="collapse-item" href="{{ route('invoices.index') }}">Semua Invoice</a>
                    <a class="collapse-item" href="{{ route('invoices.create') }}">Buat Invoice</a>
                    <a class="collapse-item" href="{{ route('invoices.generate-monthly') }}">Generate Bulanan</a>
                </div>
            </div>
        </li>

        <!-- Nav Item - Payments -->
        <li class="nav-item">
            <a class="nav-link" href="{{ route('payments.index') }}">
                <i class="fas fa-fw fa-money-bill-wave"></i>
                <span>Pembayaran</span>
            </a>
        </li>

        <!-- Nav Item - Alerts -->
        <li class="nav-item {{ Nav::isRoute('alerts.index') }}">
            <a class="nav-link" href="{{ route('alerts.index') }}">
                <i class="fas fa-fw fa-exclamation-triangle"></i>
                <span>Alerts</span>
                <span class="badge badge-danger ml-2" id="alert-count-badge" style="display: none;">0</span>
            </a>
        </li>

        <!-- Divider -->
        <hr class="sidebar-divider">

        <!-- Heading -->
        <div class="sidebar-heading">
            {{ __('Monitoring') }}
        </div>

        <!-- Nav Item - Network Monitoring -->
        <li class="nav-item">
            <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseMonitoring" aria-expanded="true" aria-controls="collapseMonitoring">
                <i class="fas fa-fw fa-chart-area"></i>
                <span>Network Monitor</span>
            </a>
            <div id="collapseMonitoring" class="collapse" aria-labelledby="headingMonitoring" data-parent="#accordionSidebar">
                <div class="bg-white py-2 collapse-inner rounded">
                    <h6 class="collapse-header">Connection Monitoring:</h6>
                    <a class="collapse-item" href="{{ route('ppp-secrets.active-connections') }}">
                        <i class="fas fa-wifi"></i> User Online
                    </a>
                    <a class="collapse-item" href="{{ route('usage-logs.index') }}">
                        <i class="fas fa-history"></i> Usage Logs
                    </a>
                    <a class="collapse-item" href="{{ route('usage-logs.statistics') }}">
                        <i class="fas fa-chart-pie"></i> Statistics
                    </a>
                    <a class="collapse-item" href="{{ route('usage-logs.active-connections') }}">
                        <i class="fas fa-link"></i> Active Connections
                    </a>
                </div>
            </div>
        </li>

        <!-- Nav Item - MikroTik System Monitoring -->
        <li class="nav-item">
            <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseMikrotikMonitor" aria-expanded="true" aria-controls="collapseMikrotikMonitor">
                <i class="fas fa-fw fa-server"></i>
                <span>MikroTik Monitor</span>
            </a>
            <div id="collapseMikrotikMonitor" class="collapse" aria-labelledby="headingMikrotikMonitor" data-parent="#accordionSidebar">
                <div class="bg-white py-2 collapse-inner rounded">
                    <h6 class="collapse-header">System Health:</h6>
                    <a class="collapse-item" href="{{ route('mikrotik.system-health') }}">
                        <i class="fas fa-heartbeat"></i> System Health
                    </a>
                    <a class="collapse-item" href="{{ route('mikrotik.temperature') }}">
                        <i class="fas fa-thermometer-half"></i> Temperature
                    </a>
                    <a class="collapse-item" href="{{ route('mikrotik.cpu-memory') }}">
                        <i class="fas fa-microchip"></i> CPU & Memory
                    </a>
                    <a class="collapse-item" href="{{ route('mikrotik.disk-usage') }}">
                        <i class="fas fa-hdd"></i> Disk Usage
                    </a>
                    <div class="collapse-divider"></div>
                    <h6 class="collapse-header">Network Health:</h6>
                    <a class="collapse-item" href="{{ route('mikrotik.interfaces') }}">
                        <i class="fas fa-ethernet"></i> Interfaces
                    </a>
                    <a class="collapse-item" href="{{ route('mikrotik.bandwidth') }}">
                        <i class="fas fa-tachometer-alt"></i> Bandwidth Monitor
                    </a>
                    <a class="collapse-item" href="{{ route('mikrotik.firewall') }}">
                        <i class="fas fa-shield-alt"></i> Firewall Stats
                    </a>
                    <a class="collapse-item" href="{{ route('mikrotik.routing') }}">
                        <i class="fas fa-route"></i> Routing Table
                    </a>
                </div>
            </div>
        </li>

        <!-- Nav Item - Network Performance -->
        <li class="nav-item">
            <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseNetworkPerf" aria-expanded="true" aria-controls="collapseNetworkPerf">
                <i class="fas fa-fw fa-signal"></i>
                <span>Network Performance</span>
            </a>
            <div id="collapseNetworkPerf" class="collapse" aria-labelledby="headingNetworkPerf" data-parent="#accordionSidebar">
                <div class="bg-white py-2 collapse-inner rounded">
                    <h6 class="collapse-header">Performance Tools:</h6>
                    <a class="collapse-item" href="{{ route('mikrotik.ping-test') }}">
                        <i class="fas fa-broadcast-tower"></i> Ping Test
                    </a>
                    <a class="collapse-item" href="{{ route('mikrotik.speed-test') }}">
                        <i class="fas fa-stopwatch"></i> Speed Test
                    </a>
                    <a class="collapse-item" href="{{ route('mikrotik.bandwidth-test') }}">
                        <i class="fas fa-chart-line"></i> Bandwidth Test
                    </a>
                    <a class="collapse-item" href="{{ route('mikrotik.latency-monitor') }}">
                        <i class="fas fa-clock"></i> Latency Monitor
                    </a>
                    <div class="collapse-divider"></div>
                    <h6 class="collapse-header">Quality Monitoring:</h6>
                    <a class="collapse-item" href="{{ route('mikrotik.quality-metrics') }}">
                        <i class="fas fa-chart-bar"></i> Quality Metrics
                    </a>
                    <a class="collapse-item" href="{{ route('mikrotik.packet-loss') }}">
                        <i class="fas fa-exclamation-triangle"></i> Packet Loss
                    </a>
                </div>
            </div>
        </li>

        <!-- Nav Item - Reports -->
        <li class="nav-item">
            <a class="nav-link" href="{{ route('usage-logs.statistics') }}">
                <i class="fas fa-fw fa-chart-line"></i>
                <span>Laporan</span>
            </a>
        </li>

        <!-- Divider -->
        <hr class="sidebar-divider">

        <!-- Heading -->
        <div class="sidebar-heading">
            {{ __('Pengaturan') }}
        </div>

        <!-- Nav Item - MikroTik Settings -->
        <li class="nav-item">
            <a class="nav-link" href="{{ route('mikrotik-settings.index') }}">
                <i class="fas fa-fw fa-server"></i>
                <span>Pengaturan MikroTik</span>
            </a>
        </li>

        <!-- Nav Item - Profile -->
        <li class="nav-item {{ Nav::isRoute('profile.edit') }}">
            <a class="nav-link" href="{{ route('profile.edit') }}">
                <i class="fas fa-fw fa-user"></i>
                <span>{{ __('Profile') }}</span>
            </a>
        </li>

        <!-- Nav Item - About -->
        <li class="nav-item {{ Nav::isRoute('about') }}">
            <a class="nav-link" href="{{ route('about') }}">
                <i class="fas fa-fw fa-hands-helping"></i>
                <span>{{ __('About') }}</span>
            </a>
        </li>

        <!-- Divider -->
        <hr class="sidebar-divider d-none d-md-block">

        <!-- Sidebar Toggler (Sidebar) -->
        <div class="text-center d-none d-md-inline">
            <button class="rounded-circle border-0" id="sidebarToggle"></button>
        </div>

    </ul>
    <!-- End of Sidebar -->

    <!-- Content Wrapper -->
    <div id="content-wrapper" class="d-flex flex-column">

        <!-- Main Content -->
        <div id="content">

            <!-- Topbar -->
            <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">

                <!-- Sidebar Toggle (Topbar) -->
                <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                    <i class="fa fa-bars"></i>
                </button>

                <!-- Topbar Search -->
                <form class="d-none d-sm-inline-block form-inline mr-auto ml-md-3 my-2 my-md-0 mw-100 navbar-search">
                    <div class="input-group">
                        <input type="text" class="form-control bg-light border-0 small" placeholder="Search for..." aria-label="Search" aria-describedby="basic-addon2">
                        <div class="input-group-append">
                            <button class="btn btn-primary" type="button">
                                <i class="fas fa-search fa-sm"></i>
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Topbar Navbar -->
                <ul class="navbar-nav ml-auto">

                    <!-- Nav Item - Search Dropdown (Visible Only XS) -->
                    <li class="nav-item dropdown no-arrow d-sm-none">
                        <a class="nav-link dropdown-toggle" href="#" id="searchDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-search fa-fw"></i>
                        </a>
                        <!-- Dropdown - Messages -->
                        <div class="dropdown-menu dropdown-menu-right p-3 shadow animated--grow-in" aria-labelledby="searchDropdown">
                            <form class="form-inline mr-auto w-100 navbar-search">
                                <div class="input-group">
                                    <input type="text" class="form-control bg-light border-0 small" placeholder="Search for..." aria-label="Search" aria-describedby="basic-addon2">
                                    <div class="input-group-append">
                                        <button class="btn btn-primary" type="button">
                                            <i class="fas fa-search fa-sm"></i>
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </li>

                    <!-- Nav Item - Alerts -->
                    <li class="nav-item dropdown no-arrow mx-1">
                        <a class="nav-link dropdown-toggle" href="#" id="alertsDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-bell fa-fw"></i>
                            <!-- Counter - Alerts -->
                            <span class="badge badge-danger badge-counter" id="topbar-alert-count" style="display: none;">0</span>
                        </a>
                        <!-- Dropdown - Alerts -->
                        <div class="dropdown-list dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="alertsDropdown">
                            <h6 class="dropdown-header">
                                Payment Alerts
                            </h6>
                            <div id="alert-dropdown-content">
                                <a class="dropdown-item text-center small text-gray-500" href="#">Loading alerts...</a>
                            </div>
                            <a class="dropdown-item text-center small text-gray-500" href="{{ route('alerts.index') }}">Show All Alerts</a>
                        </div>
                    </li>

                    <!-- Nav Item - Messages -->
                    <li class="nav-item dropdown no-arrow mx-1">
                        <a class="nav-link dropdown-toggle" href="#" id="messagesDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-envelope fa-fw"></i>
                            <!-- Counter - Messages -->
                            <span class="badge badge-danger badge-counter" id="messageCounter" style="display: none;">0</span>
                        </a>
                        <!-- Dropdown - Messages -->
                        <div class="dropdown-list dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="messagesDropdown">
                            <h6 class="dropdown-header">
                                Message Center
                                <button type="button" class="btn btn-link btn-sm float-right" id="markAllReadBtn" style="font-size: 12px; padding: 0;">
                                    <i class="fas fa-check-double"></i> Mark All Read
                                </button>
                            </h6>
                            <div id="notificationsList">
                                <div class="dropdown-item text-center py-3">
                                    <i class="fas fa-spinner fa-spin"></i> Loading...
                                </div>
                            </div>
                            <a class="dropdown-item text-center small text-gray-500" href="{{ route('notifications.index') }}">
                                <i class="fas fa-list"></i> View All Messages
                            </a>
                        </div>
                    </li>

                    <div class="topbar-divider d-none d-sm-block"></div>

                    <!-- Nav Item - User Information -->
                    <li class="nav-item dropdown no-arrow">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <span class="mr-2 d-none d-lg-inline text-gray-600 small">{{ Auth::user()->name }}</span>
                            <figure class="img-profile rounded-circle avatar font-weight-bold" data-initial="{{ Auth::user()->name[0] }}"></figure>
                        </a>
                        <!-- Dropdown - User Information -->
                        <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
                            <a class="dropdown-item" href="{{ route('profile.edit') }}">
                                <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
                                {{ __('Profile') }}
                            </a>
                            <a class="dropdown-item" href="{{ route('company-settings.index') }}">
                                <i class="fas fa-cogs fa-sm fa-fw mr-2 text-gray-400"></i>
                                {{ __('Settings') }}
                            </a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="#" data-toggle="modal" data-target="#logoutModal">
                                <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                                {{ __('Logout') }}
                            </a>
                        </div>
                    </li>

                </ul>

            </nav>
            <!-- End of Topbar -->

            <!-- Begin Page Content -->
            <div class="container-fluid">

                @yield('main-content')

            </div>
            <!-- /.container-fluid -->

        </div>
        <!-- End of Main Content -->

        <!-- Footer -->
        <footer class="sticky-footer bg-white">
            <div class="container my-auto">
                <div class="copyright text-center my-auto">
                    <span>Developer 
                        @if(isset($companySettings['developer_by']) && $companySettings['developer_by'])
                            @if(isset($companySettings['github_url']) && $companySettings['github_url'])
                                <a href="{{ $companySettings['github_url'] }}" target="_blank">{{ $companySettings['developer_by'] }}</a>
                            @else
                                {{ $companySettings['developer_by'] }}
                            @endif
                        @else
                            <a href="https://github.com/kevindoni" target="_blank">Kevin Doni</a>
                        @endif
                        . {{ now()->year }}
                    </span>
                </div>
            </div>
        </footer>
        <!-- End of Footer -->

    </div>
    <!-- End of Content Wrapper -->

</div>

<!-- Scroll to Top Button-->
<a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
</a>

<!-- Logout Modal-->
<div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">{{ __('Ready to Leave?') }}</h5>
                <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">Select "Logout" below if you are ready to end your current session.</div>
            <div class="modal-footer">
                <button class="btn btn-link" type="button" data-dismiss="modal">{{ __('Cancel') }}</button>
                <a class="btn btn-danger" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">{{ __('Logout') }}</a>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                    @csrf
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
<!-- Popper.js - Required for Bootstrap dropdowns -->
<script src="{{ asset('vendor/popper.js/popper.min.js') }}"></script>
<script src="{{ asset('vendor/bootstrap/js/bootstrap.min.js') }}"></script>
<script src="{{ asset('vendor/jquery-easing/jquery.easing.min.js') }}"></script>
<script src="{{ asset('js/sb-admin-2.min.js') }}"></script>

<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.bootstrap4.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.print.min.js"></script>

<!-- SweetAlert2 JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.27/dist/sweetalert2.all.min.js"></script>

<!-- Toastr JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

<script>
// Global configuration for Toastr
toastr.options = {
    "closeButton": true,
    "debug": false,
    "newestOnTop": false,
    "progressBar": true,
    "positionClass": "toast-top-right",
    "preventDuplicates": false,
    "onclick": null,
    "showDuration": "300",
    "hideDuration": "1000",
    "timeOut": "5000",
    "extendedTimeOut": "1000",
    "showEasing": "swing",
    "hideEasing": "linear",
    "showMethod": "fadeIn",
    "hideMethod": "fadeOut"
};

// Global CSRF token for AJAX requests
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});

// Load alert counts on page load
$(document).ready(function() {
    loadAlertCounts();
    
    // Update alert counts every 5 minutes
    setInterval(loadAlertCounts, 300000);
});

function loadAlertCounts() {
    $.ajax({
        url: '{{ route("alerts.count") }}',
        method: 'GET',
        success: function(response) {
            const totalAlerts = response.total || 0;
            
            // Update sidebar badge
            const sidebarBadge = $('#alert-count-badge');
            if (totalAlerts > 0) {
                sidebarBadge.text(totalAlerts).show();
            } else {
                sidebarBadge.hide();
            }
            
            // Update topbar badge
            const topbarBadge = $('#topbar-alert-count');
            if (totalAlerts > 0) {
                topbarBadge.text(totalAlerts > 99 ? '99+' : totalAlerts).show();
            } else {
                topbarBadge.hide();
            }
            
            // Update dropdown content
            updateAlertDropdown(response);
        },
        error: function() {
            console.log('Failed to load alert counts');
        }
    });
}

function updateAlertDropdown(alertData) {
    const dropdownContent = $('#alert-dropdown-content');
    let content = '';
    
    if (alertData.total === 0) {
        content = '<a class="dropdown-item text-center small text-gray-500" href="#">No alerts at this time</a>';
    } else {
        if (alertData.overdue > 0) {
            content += `
                <a class="dropdown-item d-flex align-items-center" href="{{ route('alerts.index') }}">
                    <div class="mr-3">
                        <div class="icon-circle bg-danger">
                            <i class="fas fa-exclamation-triangle text-white"></i>
                        </div>
                    </div>
                    <div>
                        <div class="small text-gray-500">Overdue Payments</div>
                        <span class="font-weight-bold">${alertData.overdue} users with overdue payments</span>
                    </div>
                </a>
            `;
        }
        
        if (alertData.upcoming > 0) {
            content += `
                <a class="dropdown-item d-flex align-items-center" href="{{ route('alerts.index') }}">
                    <div class="mr-3">
                        <div class="icon-circle bg-warning">
                            <i class="fas fa-clock text-white"></i>
                        </div>
                    </div>
                    <div>
                        <div class="small text-gray-500">Payment Due Soon</div>
                        <span class="font-weight-bold">${alertData.upcoming} users due within 24 hours</span>
                    </div>
                </a>
            `;
        }
        
        if (alertData.to_block > 0) {
            content += `
                <a class="dropdown-item d-flex align-items-center" href="{{ route('alerts.index') }}">
                    <div class="mr-3">
                        <div class="icon-circle bg-danger">
                            <i class="fas fa-ban text-white"></i>
                        </div>
                    </div>
                    <div>
                        <div class="small text-gray-500">Users to Block</div>
                        <span class="font-weight-bold">${alertData.to_block} users need to be blocked</span>
                    </div>
                </a>
            `;
        }
    }
    
    dropdownContent.html(content);
}

// Notification Management
function loadNotifications() {
    fetch('{{ route("notifications.api") }}')
        .then(response => response.json())
        .then(data => {
            updateNotificationDropdown(data.notifications);
            updateNotificationCounter(data.unread_count);
        })
        .catch(error => {
            console.error('Error loading notifications:', error);
        });
}

function updateNotificationDropdown(notifications) {
    const notificationsList = document.getElementById('notificationsList');
    
    if (notifications.length === 0) {
        notificationsList.innerHTML = `
            <div class="dropdown-item text-center py-3 text-muted">
                <i class="fas fa-inbox"></i><br>No new notifications
            </div>
        `;
        return;
    }
    
    let content = '';
    notifications.forEach(notification => {
        const iconColorClass = getIconColorClass(notification.color);
        const readClass = notification.is_read ? 'text-muted' : '';
        
        content += `
            <a class="dropdown-item d-flex align-items-center notification-item ${readClass}" 
               href="#" 
               data-notification-id="${notification.id}"
               onclick="markNotificationAsRead(${notification.id}, event)">
                <div class="mr-3">
                    <div class="icon-circle bg-${notification.color}">
                        <i class="${notification.icon} text-white"></i>
                    </div>
                </div>
                <div class="flex-grow-1">
                    <div class="font-weight-bold text-truncate" style="max-width: 280px;">
                        ${notification.title}
                    </div>
                    <div class="small text-truncate text-gray-500" style="max-width: 280px;">
                        ${notification.message}
                    </div>
                    <div class="small text-gray-400">
                        <i class="fas fa-clock"></i> ${notification.time_ago}
                    </div>
                </div>
                ${!notification.is_read ? '<div class="ml-2"><span class="badge badge-primary badge-sm">New</span></div>' : ''}
            </a>
        `;
    });
    
    notificationsList.innerHTML = content;
}

function updateNotificationCounter(count) {
    const counter = document.getElementById('messageCounter');
    if (count > 0) {
        counter.textContent = count > 99 ? '99+' : count;
        counter.style.display = 'inline';
    } else {
        counter.style.display = 'none';
    }
}

function getIconColorClass(color) {
    const colorMap = {
        'success': 'text-success',
        'info': 'text-info',
        'warning': 'text-warning',
        'danger': 'text-danger',
        'primary': 'text-primary'
    };
    return colorMap[color] || 'text-info';
}

function markNotificationAsRead(notificationId, event) {
    event.preventDefault();
    
    fetch(`/notifications/${notificationId}/read`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update UI to show as read
            const notificationItem = document.querySelector(`[data-notification-id="${notificationId}"]`);
            if (notificationItem) {
                notificationItem.classList.add('text-muted');
                const newBadge = notificationItem.querySelector('.badge-primary');
                if (newBadge) {
                    newBadge.remove();
                }
            }
            
            // Refresh notification count
            loadNotifications();
        }
    })
    .catch(error => {
        console.error('Error marking notification as read:', error);
    });
}

// Mark all notifications as read
document.getElementById('markAllReadBtn').addEventListener('click', function(e) {
    e.preventDefault();
    
    fetch('{{ route("notifications.read-all") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadNotifications();
            // Show success message
            toastr.success('All notifications marked as read', 'Success');
        }
    })
    .catch(error => {
        console.error('Error marking all notifications as read:', error);
        toastr.error('Failed to mark notifications as read', 'Error');
    });
});

// Load notifications on page load
document.addEventListener('DOMContentLoaded', function() {
    loadNotifications();
    
    // Refresh notifications every 30 seconds
    setInterval(loadNotifications, 30000);
});
</script>

@stack('scripts')
</body>
</html>
