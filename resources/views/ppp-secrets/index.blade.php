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
            <button type="button" class="btn btn-sm btn-success shadow-sm" id="syncFromMikrotikBtn">
                <i class="fas fa-sync fa-sm text-white-50"></i> Sync from MikroTik
            </button>
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
    </div>

    <!-- Summary Cards -->
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
            @if($pppSecrets->count() > 0)
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                        <thead class="thead-light">
                            <tr>
                                <th width="40"><input type="checkbox" id="selectAll"></th>
                                <th>Username</th>
                                <th>Password</th>
                                <th>Service</th>
                                <th>Profile</th>
                                <th>Customer</th>
                                <th>Status</th>
                                <th>MikroTik</th>
                                <th width="200" class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pppSecrets as $secret)
                                <tr>
                                    <td>
                                        <input type="checkbox" class="secret-checkbox" value="{{ $secret->id }}">
                                    </td>
                                    <td>
                                        <strong>{{ $secret->username }}</strong>
                                        @if($secret->comment)
                                            <br><small class="text-muted">{{ $secret->comment }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <code class="text-monospace">{{ Str::mask($secret->password, '*', 1, -1) }}</code>
                                    </td>
                                    <td>
                                        <span class="badge badge-info">{{ strtoupper($secret->service) }}</span>
                                    </td>
                                    <td>
                                        @if($secret->pppProfile)
                                            <span class="badge badge-primary">{{ $secret->pppProfile->name }}</span>
                                        @else
                                            <span class="badge badge-secondary">{{ $secret->profile ?? 'N/A' }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($secret->customer)
                                            <strong>{{ $secret->customer->name }}</strong>
                                            @if($secret->customer->email)
                                                <br><small class="text-muted">{{ $secret->customer->email }}</small>
                                            @endif
                                        @else
                                            <span class="text-muted">No Customer</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($secret->is_active)
                                            <span class="badge badge-success">Active</span>
                                        @else
                                            <span class="badge badge-danger">Disabled</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($secret->mikrotik_id)
                                            <span class="badge badge-success" title="Synced with MikroTik">
                                                <i class="fas fa-wifi"></i> Synced
                                            </span>
                                        @else
                                            <span class="badge badge-warning" title="Not synced">
                                                <i class="fas fa-exclamation-triangle"></i> Local
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('ppp-secrets.show', $secret) }}" 
                                               class="btn btn-sm btn-info" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('ppp-secrets.edit', $secret) }}" 
                                               class="btn btn-sm btn-warning" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-danger" 
                                                    onclick="deleteSecret({{ $secret->id }})" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                        <div class="btn-group mt-1" role="group">
                                            @if($secret->is_active)
                                                <button type="button" class="btn btn-sm btn-secondary" 
                                                        onclick="disableSecret({{ $secret->id }})" title="Disable">
                                                    <i class="fas fa-ban"></i>
                                                </button>
                                            @else
                                                <button type="button" class="btn btn-sm btn-success" 
                                                        onclick="enableSecret({{ $secret->id }})" title="Enable">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            @endif
                                            <button type="button" class="btn btn-sm btn-primary" 
                                                    onclick="syncSecret({{ $secret->id }})" title="Sync to MikroTik">
                                                <i class="fas fa-sync"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div>
                        Showing {{ $pppSecrets->firstItem() }} to {{ $pppSecrets->lastItem() }} of {{ $pppSecrets->total() }} results
                    </div>
                    <div>
                        {{ $pppSecrets->links() }}
                    </div>
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-key fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No PPP Secrets Found</h5>
                    <p class="text-muted">Try syncing from MikroTik or add a new secret.</p>
                    <button type="button" class="btn btn-success" id="syncFromMikrotikBtn2">
                        <i class="fas fa-sync"></i> Sync from MikroTik
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('PPP Secrets page loaded successfully');
    
    // Initialize tooltips
    if (typeof $ !== 'undefined') {
        $('[data-toggle="tooltip"]').tooltip();
    }
    
    // Sync from MikroTik button
    const syncBtn = document.getElementById('syncFromMikrotikBtn');
    if (syncBtn) {
        syncBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: '📥 Sync PPP Secrets from MikroTik?',
                    html: '<p>This will download and sync PPP secrets from your MikroTik router.</p>',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, Start Sync!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Submit form
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = '{{ route("ppp-secrets.sync-from-mikrotik") }}';
                        
                        const token = document.createElement('input');
                        token.type = 'hidden';
                        token.name = '_token';
                        token.value = '{{ csrf_token() }}';
                        form.appendChild(token);
                        
                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            } else {
                // Fallback if SweetAlert not loaded
                if (confirm('Sync PPP Secrets from MikroTik?')) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '{{ route("ppp-secrets.sync-from-mikrotik") }}';
                    
                    const token = document.createElement('input');
                    token.type = 'hidden';
                    token.name = '_token';
                    token.value = '{{ csrf_token() }}';
                    form.appendChild(token);
                    
                    document.body.appendChild(form);
                    form.submit();
                }
            }
        });
    }
    
    // Refresh button
    const refreshBtn = document.getElementById('refreshBtn');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', function() {
            location.reload();
        });
    }
});

// Debug functions
function testConnection() {
    if (typeof Swal !== 'undefined') {
        Swal.fire('Connection Test', 'This would test the MikroTik connection', 'info');
    } else {
        alert('This would test the MikroTik connection');
    }
}

function testSync() {
    if (typeof Swal !== 'undefined') {
        Swal.fire('Sync Test', 'This would test the sync functionality', 'info');
    } else {
        alert('This would test the sync functionality');
    }
}

// Delete secret function
function deleteSecret(secretId) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: '🗑️ Delete PPP Secret?',
            html: '<p>Are you sure you want to delete this PPP secret?</p><p class="text-danger"><strong>⚠️ This action cannot be undone!</strong></p>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-trash"></i> Yes, Delete!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = `/ppp-secrets/${secretId}`;
                
                const methodField = document.createElement('input');
                methodField.type = 'hidden';
                methodField.name = '_method';
                methodField.value = 'DELETE';
                form.appendChild(methodField);
                
                const tokenField = document.createElement('input');
                tokenField.type = 'hidden';
                tokenField.name = '_token';
                tokenField.value = '{{ csrf_token() }}';
                form.appendChild(tokenField);
                
                document.body.appendChild(form);
                form.submit();
            }
        });
    } else {
        if (confirm('Are you sure you want to delete this PPP secret?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/ppp-secrets/${secretId}`;
            
            const methodField = document.createElement('input');
            methodField.type = 'hidden';
            methodField.name = '_method';
            methodField.value = 'DELETE';
            form.appendChild(methodField);
            
            const tokenField = document.createElement('input');
            tokenField.type = 'hidden';
            tokenField.name = '_token';
            tokenField.value = '{{ csrf_token() }}';
            form.appendChild(tokenField);
            
            document.body.appendChild(form);
            form.submit();
        }
    }
}

// Enable secret function
function enableSecret(secretId) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: '✅ Enable PPP Secret?',
            html: '<p>Are you sure you want to enable this PPP secret?</p>',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-check"></i> Yes, Enable!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = `/ppp-secrets/${secretId}/enable`;
                
                const tokenField = document.createElement('input');
                tokenField.type = 'hidden';
                tokenField.name = '_token';
                tokenField.value = '{{ csrf_token() }}';
                form.appendChild(tokenField);
                
                document.body.appendChild(form);
                form.submit();
            }
        });
    }
}

// Disable secret function
function disableSecret(secretId) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: '🚫 Disable PPP Secret?',
            html: '<p>Are you sure you want to disable this PPP secret?</p><p class="text-warning">The user will be unable to connect until re-enabled.</p>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ffc107',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-ban"></i> Yes, Disable!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = `/ppp-secrets/${secretId}/disable`;
                
                const tokenField = document.createElement('input');
                tokenField.type = 'hidden';
                tokenField.name = '_token';
                tokenField.value = '{{ csrf_token() }}';
                form.appendChild(tokenField);
                
                document.body.appendChild(form);
                form.submit();
            }
        });
    }
}

// Sync secret function
function syncSecret(secretId) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: '🔄 Sync Secret to MikroTik?',
            html: '<p>This will sync this PPP secret to your MikroTik router.</p>',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#007bff',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-sync"></i> Yes, Sync!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = `/ppp-secrets/${secretId}/sync-to-mikrotik`;
                
                const tokenField = document.createElement('input');
                tokenField.type = 'hidden';
                tokenField.name = '_token';
                tokenField.value = '{{ csrf_token() }}';
                form.appendChild(tokenField);
                
                document.body.appendChild(form);
                form.submit();
            }
        });
    }
}
</script>
@endpush
