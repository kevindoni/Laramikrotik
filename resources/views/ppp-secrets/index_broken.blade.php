@extends('layouts.admin')

@section('title', 'PPP Secrets')

@push('styles')
<style>
.badge-sm {
    font-size: 0.65em;
    padding: 0.25em 0.4em;
}

.badge-lg {
    font-size: 0.9em;
    padding: 0.5em 0.75em;
}

.table-hover tbody tr:hover {
    background-color: rgba(0,0,0,.02);
}

.custom-control-input:checked ~ .custom-control-label::before {
    background-color: #007bff;
    border-color: #007bff;
}

.copy-btn {
    transition: all 0.2s ease-in-out;
}

.copy-btn:hover {
    transform: scale(1.1);
}

.btn-group .btn {
    border-radius: 0.25rem;
    margin-left: 2px;
}

.btn-group .btn:first-child {
    margin-left: 0;
}

#bulkActions {
    background: linear-gradient(90deg, #f8f9fa 0%, #e9ecef 100%);
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    padding: 0.75rem;
}

.thead-light th {
    background-color: #f8f9fa;
    border-color: #dee2e6;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 0.05em;
}

.table td {
    vertical-align: middle;
    border-color: #dee2e6;
}

.dataTables_wrapper .dataTables_paginate .paginate_button {
    padding: 0.375rem 0.75rem;
    margin-left: 0.125rem;
    border: 1px solid #dee2e6;
    border-radius: 0.25rem;
}

.dataTables_wrapper .dataTables_paginate .paginate_button:hover {
    background: #e9ecef;
    border-color: #adb5bd;
}

.dataTables_wrapper .dataTables_paginate .paginate_button.current {
    background: #007bff;
    border-color: #007bff;
    color: white !important;
}

.dataTables_filter input {
    border: 1px solid #ced4da;
    border-radius: 0.25rem;
    padding: 0.375rem 0.75rem;
}

.dataTables_length select {
    border: 1px solid #ced4da;
    border-radius: 0.25rem;
    padding: 0.375rem 0.75rem;
}

.card {
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
    border: 1px solid #e3e6f0;
}

.text-muted {
    color: #6c757d !important;
}

.alert-info {
    background-color: #d1ecf1;
    border-color: #bee5eb;
    color: #0c5460;
}

@media (max-width: 768px) {
    .btn-group {
        flex-direction: column;
    }
    
    .btn-group .btn {
        margin-left: 0;
        margin-bottom: 2px;
    }
    
    #bulkActions {
        flex-direction: column;
        align-items: stretch;
    }
    
    #bulkActions .btn {
        margin-bottom: 0.5rem;
    }
    
    #bulkActions .btn:last-child {
        margin-bottom: 0;
    }
}
</style>
@endpush

@section('main-content')
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">PPP Secret Management</h1>
        <div>
            <a href="{{ route('ppp-secrets.generate-username') }}" class="btn btn-sm btn-secondary shadow-sm mr-2" id="generate-username">
                <i class="fas fa-user fa-sm text-white-50"></i> Generate Username
            </a>
            <a href="{{ route('ppp-secrets.generate-password') }}" class="btn btn-sm btn-secondary shadow-sm mr-2" id="generate-password">
                <i class="fas fa-key fa-sm text-white-50"></i> Generate Password
            </a>
            <a href="{{ route('ppp-secrets.create') }}" class="btn btn-sm btn-primary shadow-sm mr-2">
                <i class="fas fa-plus fa-sm text-white-50"></i> Add New Secret
            </a>
            <a href="{{ route('ppp-secrets.active-connections') }}" class="btn btn-sm btn-info shadow-sm mr-2">
                <i class="fas fa-wifi fa-sm text-white-50"></i> Active Connections
            </a>
            <form action="{{ route('ppp-secrets.sync-from-mikrotik') }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-sm btn-success shadow-sm"
                        onclick="return confirm('📥 Sync PPP Secrets from MikroTik?\n\n✅ This will:\n• Download PPP secrets from router\n• Update existing secrets in database\n• Create new secrets if not found\n• Auto-create profiles if missing\n• Safe merge - no data loss\n\n⏱️ This may take a few moments...')">
                    <i class="fas fa-sync fa-sm text-white-50"></i> Sync from MikroTik
                </button>
            </form>
            <div class="btn-group ml-2" role="group">
                <button type="button" class="btn btn-sm btn-outline-info dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-cog fa-sm"></i> Debug
                </button>
                <div class="dropdown-menu">
                    <a class="dropdown-item" href="#" onclick="testConnection()">
                        <i class="fas fa-wifi"></i> Test Connection
                    </a>
                    <a class="dropdown-item" href="#" onclick="testSync()">
                        <i class="fas fa-sync"></i> Test Sync (3 secrets)
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Alert Messages -->
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

    <!-- Search & Filter -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Search & Filter</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('ppp-secrets.index') }}">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="search">Search</label>
                            <input type="text" class="form-control" name="search" 
                                   value="{{ request('search') }}" 
                                   placeholder="Username, customer name...">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select class="form-control" name="status">
                                <option value="">All Status</option>
                                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Disabled</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="service">Service</label>
                            <select class="form-control" name="service">
                                <option value="">All Services</option>
                                <option value="pppoe" {{ request('service') == 'pppoe' ? 'selected' : '' }}>PPPoE</option>
                                <option value="pptp" {{ request('service') == 'pptp' ? 'selected' : '' }}>PPTP</option>
                                <option value="l2tp" {{ request('service') == 'l2tp' ? 'selected' : '' }}>L2TP</option>
                                <option value="sstp" {{ request('service') == 'sstp' ? 'selected' : '' }}>SSTP</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="profile">Profile</label>
                            <select class="form-control" name="profile">
                                <option value="">All Profiles</option>
                                @if(isset($profiles))
                                    @foreach($profiles as $profile)
                                        <option value="{{ $profile->id }}" {{ request('profile') == $profile->id ? 'selected' : '' }}>
                                            {{ $profile->name }}
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <div class="d-flex">
                                <button type="submit" class="btn btn-primary mr-2">
                                    <i class="fas fa-search"></i> Search
                                </button>
                                <a href="{{ route('ppp-secrets.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-redo"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Secrets</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $pppSecrets->total() }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-key fa-2x text-gray-300"></i>
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
                                Active Secrets</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $pppSecrets->where('is_active', true)->count() }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check fa-2x text-gray-300"></i>
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
                                Synced with MikroTik</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $pppSecrets->whereNotNull('mikrotik_id')->count() }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-wifi fa-2x text-gray-300"></i>
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
                                PPPoE Secrets</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $pppSecrets->where('service', 'pppoe')->count() }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-network-wired fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- DataTable -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-key mr-2"></i>PPP Secret List
            </h6>
            <div class="card-header-actions">
                <button type="button" id="refreshBtn" class="btn btn-sm btn-outline-primary" 
                        title="Refresh Table" data-toggle="tooltip">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>
        </div>
        <div class="card-body">
            <!-- Bulk Actions (Hidden by default) -->
            <div id="bulkActions" class="mb-3 d-none align-items-center">
                <div class="alert alert-info mb-0 mr-3 flex-grow-1">
                    <i class="fas fa-info-circle"></i>
                    <span id="selectedCount">0</span> secret(s) selected
                </div>
                <button type="button" id="bulkDeleteBtn" class="btn btn-danger btn-sm mr-2">
                    <i class="fas fa-trash"></i> Delete Selected
                </button>
                <button type="button" id="bulkEnableBtn" class="btn btn-success btn-sm mr-2">
                    <i class="fas fa-check"></i> Enable Selected
                </button>
                <button type="button" id="bulkDisableBtn" class="btn btn-warning btn-sm mr-2">
                    <i class="fas fa-ban"></i> Disable Selected
                </button>
                <button type="button" id="bulkSyncBtn" class="btn btn-info btn-sm mr-2">
                    <i class="fas fa-sync"></i> Sync Selected
                </button>
                <button type="button" id="clearSelectionBtn" class="btn btn-secondary btn-sm">
                    <i class="fas fa-times"></i> Clear
                </button>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="secretsTable" width="100%" cellspacing="0">
                    <thead class="thead-light">
                        <tr>
                            <th width="30">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="selectAll">
                                    <label class="custom-control-label" for="selectAll"></label>
                                </div>
                            </th>
                            <th>Username</th>
                            <th>Customer</th>
                            <th>Profile</th>
                            <th>Service</th>
                            <th>Status</th>
                            <th width="200">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pppSecrets as $secret)
                        <tr>
                            <td>
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" 
                                           id="secret_{{ $secret->id }}" 
                                           name="selected_secrets[]" 
                                           value="{{ $secret->id }}">
                                    <label class="custom-control-label" for="secret_{{ $secret->id }}"></label>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div>
                                        <strong>{{ $secret->username }}</strong>
                                        <button class="btn btn-link btn-sm p-0 ml-2 copy-btn" 
                                                data-copy="{{ $secret->username }}" 
                                                title="Copy username">
                                            <i class="fas fa-copy text-muted"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="mt-1">
                                    @if($secret->mikrotik_id)
                                        <span class="badge badge-info badge-sm">
                                            <i class="fas fa-wifi"></i> Synced
                                        </span>
                                    @else
                                        <span class="badge badge-warning badge-sm">
                                            <i class="fas fa-exclamation-triangle"></i> Local Only
                                        </span>
                                    @endif
                                    
                                    @if($secret->auto_sync)
                                        <span class="badge badge-success badge-sm">
                                            <i class="fas fa-sync-alt"></i> Auto-Sync
                                        </span>
                                    @endif
                                </div>
                                @if($secret->comment)
                                    <div class="mt-1">
                                        <small class="text-muted">{{ $secret->comment }}</small>
                                    </div>
                                @endif
                            </td>
                            <td>
                                @if($secret->customer)
                                    <div class="mb-1">
                                        <a href="{{ route('customers.show', $secret->customer) }}" class="text-decoration-none">
                                            <strong>{{ $secret->customer->name }}</strong>
                                        </a>
                                    </div>
                                    @if($secret->customer->phone)
                                        <div>
                                            <small class="text-muted">
                                                <i class="fas fa-phone"></i> {{ $secret->customer->phone }}
                                            </small>
                                        </div>
                                    @endif
                                @else
                                    <span class="text-muted">
                                        <i class="fas fa-user-slash"></i> No customer
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if($secret->pppProfile)
                                    <div class="mb-1">
                                        <span class="badge badge-primary badge-lg">{{ $secret->pppProfile->name }}</span>
                                    </div>
                                    @if($secret->pppProfile->rate_limit)
                                        @php
                                            $rates = explode('/', $secret->pppProfile->rate_limit);
                                            $download = $rates[0] ?? '';
                                            $upload = $rates[1] ?? '';
                                        @endphp
                                        <div class="mt-1">
                                            <small class="text-muted">
                                                <span class="badge badge-outline-primary badge-sm">
                                                    <i class="fas fa-download"></i> {{ $download }}
                                                </span>
                                                <span class="badge badge-outline-success badge-sm">
                                                    <i class="fas fa-upload"></i> {{ $upload }}
                                                </span>
                                            </small>
                                        </div>
                                    @else
                                        <div class="mt-1">
                                            <small class="text-muted">
                                                <span class="badge badge-secondary badge-sm">Unlimited</span>
                                            </small>
                                        </div>
                                    @endif
                                @else
                                    <span class="text-muted">
                                        <i class="fas fa-cog-slash"></i> No profile
                                    </span>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge badge-{{ $secret->service == 'pppoe' ? 'success' : 'info' }} badge-lg">
                                    @if($secret->service == 'pppoe')
                                        <i class="fas fa-ethernet"></i>
                                    @elseif($secret->service == 'pptp')
                                        <i class="fas fa-lock"></i>
                                    @elseif($secret->service == 'l2tp')
                                        <i class="fas fa-shield-alt"></i>
                                    @else
                                        <i class="fas fa-network-wired"></i>
                                    @endif
                                    {{ strtoupper($secret->service) }}
                                </span>
                            </td>
                            <td class="text-center">
                                @if($secret->is_active)
                                    <span class="badge badge-success badge-lg">
                                        <i class="fas fa-check-circle"></i> Active
                                    </span>
                                @else
                                    <span class="badge badge-danger badge-lg">
                                        <i class="fas fa-times-circle"></i> Disabled
                                    </span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="{{ route('ppp-secrets.show', $secret) }}" 
                                       class="btn btn-sm btn-info" 
                                       title="View Secret"
                                       data-toggle="tooltip">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('ppp-secrets.edit', $secret) }}" 
                                       class="btn btn-sm btn-warning" 
                                       title="Edit Secret"
                                       data-toggle="tooltip">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-sm btn-secondary dropdown-toggle" 
                                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
                                                title="Actions"
                                                data-toggle="tooltip">
                                            <i class="fas fa-cog"></i>
                                        </button>
                                        <div class="dropdown-menu">
                                            <!-- Enable/Disable -->
                                            @if($secret->is_active)
                                                <form action="{{ route('ppp-secrets.disable', $secret) }}" 
                                                      method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="dropdown-item" 
                                                            onclick="return confirm('Disable this PPP secret?')">
                                                        <i class="fas fa-ban text-danger"></i> Disable (Isolir)
                                                    </button>
                                                </form>
                                            @else
                                                <form action="{{ route('ppp-secrets.enable', $secret) }}" 
                                                      method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="dropdown-item" 
                                                            onclick="return confirm('Enable this PPP secret?')">
                                                        <i class="fas fa-check text-success"></i> Enable
                                                    </button>
                                                </form>
                                            @endif
                                            
                                            <!-- Disconnect -->
                                            <form action="{{ route('ppp-secrets.disconnect', $secret) }}" 
                                                  method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="dropdown-item" 
                                                        onclick="return confirm('Disconnect active session?')">
                                                    <i class="fas fa-plug text-warning"></i> Disconnect Session
                                                </button>
                                            </form>
                                            
                                            <div class="dropdown-divider"></div>
                                            
                                            <!-- Sync -->
                                            <form action="{{ route('ppp-secrets.sync-to-mikrotik', $secret) }}" 
                                                  method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="dropdown-item"
                                                        onclick="return confirm('📤 Upload User Account to MikroTik?\n\n• This user will be created/updated on router\n• Changes will be made directly to MikroTik')">
                                                    <i class="fas fa-upload text-success"></i> Push to MikroTik
                                                </button>
                                            </form>
                                            
                                            <!-- Auto-sync toggle -->
                                            <form action="{{ route('mikrotik-settings.toggle-secret-auto-sync', $secret->id) }}" 
                                                  method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="dropdown-item">
                                                    @if($secret->auto_sync)
                                                        <i class="fas fa-pause text-warning"></i> Disable Auto-Sync
                                                    @else
                                                        <i class="fas fa-play text-success"></i> Enable Auto-Sync
                                                    @endif
                                                </button>
                                            </form>
                                            
                                            <div class="dropdown-divider"></div>
                                            
                                            <!-- Delete -->
                                            <form action="{{ route('ppp-secrets.destroy', $secret) }}" 
                                                  method="POST" class="d-inline delete-form">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="dropdown-item text-danger" 
                                                        title="Delete Secret"
                                                        data-toggle="tooltip">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <div class="text-muted">
                                    <i class="fas fa-inbox fa-3x mb-3"></i>
                                    <p class="mb-0">No PPP secrets found.</p>
                                    <small>Create your first secret or sync from MikroTik.</small>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div class="text-muted">
                    Showing {{ $pppSecrets->firstItem() ?? 0 }} to {{ $pppSecrets->lastItem() ?? 0 }} 
                    of {{ $pppSecrets->total() }} secrets
                </div>
                <div>
                    {{ $pppSecrets->withQueryString()->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Temporary disable DataTables for debugging
    console.log('Initializing PPP Secrets page...');
    
    // Just initialize basic functionality without DataTables for now
    $('[data-toggle="tooltip"]').tooltip();
    
    // Debug: Check if table structure is correct
    console.log('Table found:', $('#secretsTable').length > 0);
    console.log('Table headers count:', $('#secretsTable thead tr th').length);
    console.log('Table rows count:', $('#secretsTable tbody tr').length);
    
    // Show/hide bulk actions based on checkbox selection
    function toggleBulkActions() {
        const checkedBoxes = $('input[name="selected_secrets[]"]:checked');
        const count = checkedBoxes.length;
        
        if (count > 0) {
            $('#bulkActions').removeClass('d-none').addClass('d-flex');
            $('#selectedCount').text(count);
        } else {
            $('#bulkActions').addClass('d-none').removeClass('d-flex');
        }
    }

    // Handle select all checkbox
    $('#selectAll').change(function() {
        $('input[name="selected_secrets[]"]').prop('checked', this.checked);
        toggleBulkActions();
    });

    // Handle individual checkboxes
    $(document).on('change', 'input[name="selected_secrets[]"]', function() {
        toggleBulkActions();
        // Update select all checkbox state
        const total = $('input[name="selected_secrets[]"]').length;
        const checked = $('input[name="selected_secrets[]"]:checked').length;
        $('#selectAll').prop('indeterminate', checked > 0 && checked < total);
        $('#selectAll').prop('checked', checked === total);
    });

    // Clear selection
    $('#clearSelectionBtn').click(function() {
        $('input[name="selected_secrets[]"], #selectAll').prop('checked', false);
        toggleBulkActions();
    });

    // Bulk delete functionality
    $('#bulkDeleteBtn').click(function() {
        const selectedIds = [];
        $('input[name="selected_secrets[]"]:checked').each(function() {
            selectedIds.push($(this).val());
        });

        if (selectedIds.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'No Selection',
                text: 'Please select secrets to delete.',
                confirmButtonClass: 'btn btn-primary'
            });
            return;
        }

        Swal.fire({
            title: 'Delete Selected Secrets?',
            text: `You are about to delete ${selectedIds.length} secret(s). This action cannot be undone!`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete them!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                // Create form for bulk delete
                const form = $('<form>', {
                    method: 'POST',
                    action: '{{ route("ppp-secrets.bulk-delete") }}'
                });
                
                form.append($('<input>', {
                    type: 'hidden',
                    name: '_token',
                    value: '{{ csrf_token() }}'
                }));
                form.append($('<input>', {
                    type: 'hidden',
                    name: '_method',
                    value: 'DELETE'
                }));
                
                selectedIds.forEach(id => {
                    form.append($('<input>', {
                        type: 'hidden',
                        name: 'selected_secrets[]',
                        value: id
                    }));
                });
                
                $('body').append(form);
                form.submit();
            }
        });
    });

    // Bulk enable functionality
    $('#bulkEnableBtn').click(function() {
        bulkAction('{{ route("ppp-secrets.bulk-enable") }}', 'Enable Selected Secrets?', 'enable');
    });

    // Bulk disable functionality
    $('#bulkDisableBtn').click(function() {
        bulkAction('{{ route("ppp-secrets.bulk-disable") }}', 'Disable Selected Secrets?', 'disable');
    });

    // Bulk sync functionality
    $('#bulkSyncBtn').click(function() {
        bulkAction('{{ route("ppp-secrets.bulk-sync") }}', 'Sync Selected Secrets?', 'sync');
    });

    // Generic bulk action function
    function bulkAction(actionUrl, title, verb) {
        const selectedIds = [];
        $('input[name="selected_secrets[]"]:checked').each(function() {
            selectedIds.push($(this).val());
        });

        if (selectedIds.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'No Selection',
                text: `Please select secrets to ${verb}.`,
                confirmButtonClass: 'btn btn-primary'
            });
            return;
        }

        Swal.fire({
            title: title,
            text: `You are about to ${verb} ${selectedIds.length} secret(s).`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#007bff',
            cancelButtonColor: '#6c757d',
            confirmButtonText: `Yes, ${verb} them!`,
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                // Create form for bulk action
                const form = $('<form>', {
                    method: 'POST',
                    action: actionUrl
                });
                
                form.append($('<input>', {
                    type: 'hidden',
                    name: '_token',
                    value: '{{ csrf_token() }}'
                }));
                
                selectedIds.forEach(id => {
                    form.append($('<input>', {
                        type: 'hidden',
                        name: 'selected_secrets[]',
                        value: id
                    }));
                });
                
                $('body').append(form);
                form.submit();
            }
        });
    }

    // Copy to clipboard functionality
    function copyToClipboard(text, element) {
        navigator.clipboard.writeText(text).then(function() {
            // Show success feedback
            const originalHtml = $(element).html();
            $(element).html('<i class="fas fa-check text-success"></i>');
            setTimeout(() => {
                $(element).html(originalHtml);
            }, 1000);
            
            // Show toast notification
            toastr.success('Copied to clipboard!', 'Success', {
                timeOut: 2000,
                positionClass: 'toast-top-right'
            });
        }).catch(function(err) {
            toastr.error('Failed to copy', 'Error');
        });
    }

    // Make copyToClipboard globally accessible for SweetAlert buttons
    window.copyToClipboard = function(text) {
        navigator.clipboard.writeText(text).then(function() {
            toastr.success('Copied to clipboard!', 'Success', {
                timeOut: 2000,
                positionClass: 'toast-top-right'
            });
        }).catch(function(err) {
            toastr.error('Failed to copy', 'Error');
        });
    };

    // Handle copy button clicks
    $(document).on('click', '.copy-btn', function(e) {
        e.preventDefault();
        const text = $(this).data('copy');
        copyToClipboard(text, this);
    });

    // Delete confirmation with SweetAlert2
    $('.delete-form').on('submit', function(e) {
        e.preventDefault();
        const form = this;
        
        Swal.fire({
            title: 'Delete Secret?',
            text: "This action cannot be undone!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });

    // Generate username with enhanced UI
    $('#generate-username').click(function(e) {
        e.preventDefault();
        const $btn = $(this);
        const originalText = $btn.html();
        
        // Show loading state
        $btn.html('<i class="fas fa-spinner fa-spin"></i> Generating...').prop('disabled', true);
        
        $.get('{{ route("ppp-secrets.generate-username") }}')
            .done(function(data) {
                Swal.fire({
                    title: 'Username Generated!',
                    html: `
                        <div class="mb-3">
                            <strong>New Username:</strong>
                            <div class="input-group mt-2">
                                <input type="text" class="form-control text-center font-weight-bold" 
                                       value="${data.username}" id="generated-username" readonly>
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary" type="button" 
                                            onclick="copyToClipboard('${data.username}')" 
                                            title="Copy to clipboard">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    `,
                    icon: 'success',
                    confirmButtonText: '<i class="fas fa-check"></i> Got it!',
                    confirmButtonClass: 'btn btn-primary'
                });
            })
            .fail(function() {
                Swal.fire({
                    title: 'Error!',
                    text: 'Failed to generate username. Please try again.',
                    icon: 'error'
                });
            })
            .always(function() {
                $btn.html(originalText).prop('disabled', false);
            });
    });
    
    // Generate password with enhanced UI
    $('#generate-password').click(function(e) {
        e.preventDefault();
        const $btn = $(this);
        const originalText = $btn.html();
        
        // Show loading state
        $btn.html('<i class="fas fa-spinner fa-spin"></i> Generating...').prop('disabled', true);
        
        $.get('{{ route("ppp-secrets.generate-password") }}')
            .done(function(data) {
                Swal.fire({
                    title: 'Password Generated!',
                    html: `
                        <div class="mb-3">
                            <strong>New Password:</strong>
                            <div class="input-group mt-2">
                                <input type="text" class="form-control text-center font-weight-bold" 
                                       value="${data.password}" id="generated-password" readonly>
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary" type="button" 
                                            onclick="copyToClipboard('${data.password}')" 
                                            title="Copy to clipboard">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                            </div>
                            <small class="text-muted mt-2 d-block">
                                <i class="fas fa-info-circle"></i> 
                                Strong password with letters, numbers, and symbols
                            </small>
                        </div>
                    `,
                    icon: 'success',
                    confirmButtonText: '<i class="fas fa-check"></i> Got it!',
                    confirmButtonClass: 'btn btn-primary'
                });
            })
            .fail(function() {
                Swal.fire({
                    title: 'Error!',
                    text: 'Failed to generate password. Please try again.',
                    icon: 'error'
                });
            })
            .always(function() {
                $btn.html(originalText).prop('disabled', false);
            });
    });

    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);

    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();

    // Refresh page functionality
    $('#refreshBtn').click(function() {
        location.reload();
    });
});

// Debug functions - defined globally
function testConnection() {
    // Check if SweetAlert is available
    if (typeof Swal === 'undefined') {
        alert('SweetAlert library not loaded. Testing connection...');
        console.log('Fallback: SweetAlert not available');
    }
    
    $.get('{{ route("ppp-secrets.test-connection") }}')
        .done(function(data) {
            if (data.success) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: '✅ Connection Test Success!',
                        html: `<div class="text-left">
                            <strong>Host:</strong> ${data.host}<br>
                            <strong>Status:</strong> Connected successfully
                        </div>`,
                        icon: 'success'
                    });
                } else {
                    alert(`✅ Connection Test Success!\nHost: ${data.host}\nStatus: Connected successfully`);
                }
            } else {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: '❌ Connection Test Failed',
                        text: data.error,
                        icon: 'error'
                    });
                } else {
                    alert(`❌ Connection Test Failed\n${data.error}`);
                }
            }
        })
        .fail(function(xhr) {
            const errorMsg = xhr.responseJSON?.message || 'Failed to test connection.';
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Error',
                    text: errorMsg,
                    icon: 'error'
                });
            } else {
                alert(`Error: ${errorMsg}`);
            }
        });
}

function testSync() {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Testing Sync...',
            text: 'Retrieving sample secrets from MikroTik',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
    } else {
        console.log('Testing sync - SweetAlert fallback');
    }
    
    $.get('{{ route("ppp-secrets.test-sync") }}')
        .done(function(data) {
            if (data.success) {
                let message = `Secrets found: ${data.count}\n\n`;
                
                if (data.sample && data.sample.length > 0) {
                    message += 'Sample secrets:\n';
                    data.sample.forEach((secret, index) => {
                        message += `${index + 1}. ${secret.name || 'unnamed'}\n`;
                    });
                }
                
                if (typeof Swal !== 'undefined') {
                    let sampleHtml = '<div class="text-left">';
                    sampleHtml += `<strong>Secrets found:</strong> ${data.count}<br><br>`;
                    
                    if (data.sample && data.sample.length > 0) {
                        sampleHtml += '<strong>Sample secrets:</strong><br>';
                        data.sample.forEach((secret, index) => {
                            sampleHtml += `${index + 1}. ${secret.name || 'unnamed'}<br>`;
                        });
                    }
                    sampleHtml += '</div>';
                    
                    Swal.fire({
                        title: '✅ Sync Test Success!',
                        html: sampleHtml,
                        icon: 'success'
                    });
                } else {
                    alert(`✅ Sync Test Success!\n${message}`);
                }
            } else {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: '❌ Sync Test Failed',
                        text: data.error,
                        icon: 'error'
                    });
                } else {
                    alert(`❌ Sync Test Failed\n${data.error}`);
                }
            }
        })
        .fail(function(xhr) {
            const errorMsg = xhr.responseJSON?.message || 'Failed to test sync.';
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Error',
                    text: errorMsg,
                    icon: 'error'
                });
            } else {
                alert(`Error: ${errorMsg}`);
            }
        });
}
</script>
@endpush
