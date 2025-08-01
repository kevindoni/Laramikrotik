@extends('layouts.admin')

@section('main-content')

    <!-- Page Heading -->
    <h1 class="h3 mb-4 text-gray-800">{{ __('Dashboard Monitoring PPPoE/PPP') }}</h1>

    @if (session('success'))
    <div class="alert alert-success border-left-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    @endif

    @if (session('status'))
        <div class="alert alert-success border-left-success" role="alert">
            {{ session('status') }}
        </div>
    @endif

    <div class="row">

        <!-- Total Pelanggan -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Pelanggan</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $widget['total_customers'] ?? 0 }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pelanggan Aktif -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Pelanggan Aktif</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $widget['active_customers'] ?? 0 }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-check fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pendapatan Bulan Ini -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Pendapatan Bulan Ini</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">Rp {{ number_format($widget['monthly_revenue'] ?? 0, 0, ',', '.') }}</div>
                            @if(($widget['monthly_revenue'] ?? 0) == 0 && ($widget['last_month_revenue'] ?? 0) > 0)
                                <div class="text-xs text-muted">
                                    Bulan lalu: Rp {{ number_format($widget['last_month_revenue'], 0, ',', '.') }}
                                </div>
                            @endif
                            @if(($widget['projected_revenue'] ?? 0) > 0)
                                <div class="text-xs text-warning">
                                    Proyeksi: Rp {{ number_format($widget['projected_revenue'], 0, ',', '.') }}
                                </div>
                            @endif
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-rupiah-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- User Online -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">User Online</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $widget['online_users'] ?? 0 }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-wifi fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MikroTik & PPP Statistics -->
    <div class="row mb-4">
        <div class="col-lg-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">MikroTik & PPP Statistics</h6>
                    <div class="dropdown no-arrow">
                        <a class="dropdown-toggle" href="{{ route('ppp-secrets.active-connections') }}" role="button">
                            <i class="fas fa-external-link-alt fa-sm fa-fw text-gray-400"></i>
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- PPP Profiles -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-info shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">PPP Profiles</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $widget['total_profiles'] ?? 0 }}</div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-cogs fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- PPP Secrets -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-secondary shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">PPP Secrets</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $widget['total_secrets'] ?? 0 }}</div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-key fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Active Secrets -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-success shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Active Secrets</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $widget['active_secrets'] ?? 0 }}</div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Online Now -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-warning shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Online Now</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $widget['online_users'] ?? 0 }}</div>
                                            <div class="text-xs text-muted">
                                                @if(isset($widget['active_connections']) && count($widget['active_connections']) > 0)
                                                    Real-time from MikroTik
                                                @else
                                                    From database
                                                @endif
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-wifi fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Connections (if available) -->
                    @if(isset($widget['active_connections']) && count($widget['active_connections']) > 0)
                        <div class="mt-3">
                            <h6 class="font-weight-bold text-gray-800 mb-3">Recent Active Connections</h6>
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm">
                                    <thead>
                                        <tr>
                                            <th>Username</th>
                                            <th>IP Address</th>
                                            <th>Uptime</th>
                                            <th>Service</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach(array_slice($widget['active_connections'], 0, 5) as $connection)
                                            <tr>
                                                <td><strong>{{ $connection['name'] ?? 'N/A' }}</strong></td>
                                                <td>{{ $connection['address'] ?? 'N/A' }}</td>
                                                <td>{{ $connection['uptime'] ?? 'N/A' }}</td>
                                                <td>
                                                    <span class="badge badge-primary badge-sm">
                                                        {{ strtoupper($connection['service'] ?? 'pppoe') }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="text-center">
                                <a href="{{ route('ppp-secrets.active-connections') }}" class="btn btn-primary btn-sm">
                                    <i class="fas fa-external-link-alt"></i> View All Active Connections
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row">

        <!-- Status Invoice dan Tagihan -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Status Invoice & Tagihan</h6>
                </div>
                <div class="card-body">
                    <h4 class="small font-weight-bold">Invoice Tertunda <span class="float-right">{{ $widget['pending_invoices'] ?? 0 }} Invoice</span></h4>
                    <div class="progress mb-4">
                        <div class="progress-bar bg-warning" role="progressbar" style="width: {{ $widget['pending_invoices'] > 0 ? min(($widget['pending_invoices'] / max($widget['total_customers'], 1)) * 100, 100) : 0 }}%" aria-valuenow="{{ $widget['pending_invoices'] ?? 0 }}" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    
                    <h4 class="small font-weight-bold">Invoice Overdue <span class="float-right">{{ $widget['overdue_invoices'] ?? 0 }} Invoice</span></h4>
                    <div class="progress mb-4">
                        <div class="progress-bar bg-danger" role="progressbar" style="width: {{ $widget['overdue_invoices'] > 0 ? min(($widget['overdue_invoices'] / max($widget['total_customers'], 1)) * 100, 100) : 0 }}%" aria-valuenow="{{ $widget['overdue_invoices'] ?? 0 }}" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    
                    <h4 class="small font-weight-bold">Pelanggan Aktif <span class="float-right">{{ round(($widget['active_customers'] / max($widget['total_customers'], 1)) * 100) }}%</span></h4>
                    <div class="progress mb-4">
                        <div class="progress-bar bg-success" role="progressbar" style="width: {{ ($widget['active_customers'] / max($widget['total_customers'], 1)) * 100 }}%" aria-valuenow="{{ $widget['active_customers'] ?? 0 }}" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    
                    <h4 class="small font-weight-bold">User Online <span class="float-right">{{ round(($widget['online_users'] / max($widget['total_customers'], 1)) * 100) }}%</span></h4>
                    <div class="progress">
                        <div class="progress-bar bg-info" role="progressbar" style="width: {{ ($widget['online_users'] / max($widget['total_customers'], 1)) * 100 }}%" aria-valuenow="{{ $widget['online_users'] ?? 0 }}" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="row">
                <div class="col-lg-6 mb-4">
                    <div class="card bg-primary text-white shadow">
                        <div class="card-body">
                            <a href="{{ route('customers.create') }}" class="text-white text-decoration-none">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-user-plus fa-2x mr-3"></i>
                                    <div>
                                        <div class="font-weight-bold">Tambah Pelanggan</div>
                                        <div class="small">Daftarkan pelanggan baru</div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 mb-4">
                    <div class="card bg-success text-white shadow">
                        <div class="card-body">
                            <a href="{{ route('invoices.create') }}" class="text-white text-decoration-none">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-file-invoice fa-2x mr-3"></i>
                                    <div>
                                        <div class="font-weight-bold">Buat Invoice</div>
                                        <div class="small">Generate tagihan bulanan</div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 mb-4">
                    <div class="card bg-info text-white shadow">
                        <div class="card-body">
                            <a href="{{ route('ppp-secrets.active-connections') }}" class="text-white text-decoration-none">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-network-wired fa-2x mr-3"></i>
                                    <div>
                                        <div class="font-weight-bold">Monitor PPP</div>
                                        <div class="small">Lihat koneksi aktif</div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 mb-4">
                    <div class="card bg-warning text-white shadow">
                        <div class="card-body">
                            <a href="{{ route('usage-logs.statistics') }}" class="text-white text-decoration-none">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-chart-line fa-2x mr-3"></i>
                                    <div>
                                        <div class="font-weight-bold">Laporan</div>
                                        <div class="small">Analisis data usage</div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-4">

            <!-- Pembayaran Terbaru -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Pembayaran Terbaru</h6>
                </div>
                <div class="card-body">
                    @if(isset($widget['recent_payments']) && $widget['recent_payments']->count() > 0)
                        @foreach($widget['recent_payments'] as $payment)
                        <div class="d-flex align-items-center mb-3">
                            <div class="mr-3">
                                <div class="icon-circle bg-success">
                                    <i class="fas fa-money-bill-wave text-white"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1">
                                <div class="font-weight-bold">{{ $payment->customer->name ?? 'N/A' }}</div>
                                <div class="small text-gray-500">
                                    Rp {{ number_format($payment->amount, 0, ',', '.') }} - {{ $payment->payment_date->format('d M Y') }}
                                </div>
                            </div>
                            <div class="text-success">
                                <i class="fas fa-check-circle"></i>
                            </div>
                        </div>
                        @endforeach
                    @else
                        <p class="text-center text-muted">Belum ada pembayaran terbaru</p>
                    @endif
                    <div class="text-center">
                        <a href="{{ route('payments.index') }}" class="btn btn-primary btn-sm">Lihat Semua Pembayaran</a>
                    </div>
                </div>
            </div>

            <!-- Pelanggan Baru -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Pelanggan Baru</h6>
                </div>
                <div class="card-body">
                    @if(isset($widget['recent_customers']) && $widget['recent_customers']->count() > 0)
                        @foreach($widget['recent_customers'] as $customer)
                        <div class="d-flex align-items-center mb-3">
                            <div class="mr-3">
                                <div class="icon-circle bg-primary">
                                    <i class="fas fa-user text-white"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1">
                                <div class="font-weight-bold">{{ $customer->name }}</div>
                                <div class="small text-gray-500">
                                    {{ $customer->email }} - {{ $customer->created_at->format('d M Y') }}
                                </div>
                            </div>
                            <div class="text-{{ $customer->is_active ? 'success' : 'danger' }}">
                                <i class="fas fa-circle"></i>
                            </div>
                        </div>
                        @endforeach
                    @else
                        <p class="text-center text-muted">Belum ada pelanggan baru</p>
                    @endif
                    <div class="text-center">
                        <a href="{{ route('customers.index') }}" class="btn btn-primary btn-sm">Lihat Semua Pelanggan</a>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection

@push('styles')
<style>
.icon-circle {
    height: 2.5rem;
    width: 2.5rem;
    border-radius: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>
@endpush
