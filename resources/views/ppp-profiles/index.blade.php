@extends('layouts.admin')

@section('title', 'PPP Profiles')

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
        <h1 class="h3 mb-0 text-gray-800">PPP Profile Management</h1>
        <div>
            <a href="{{ route('ppp-profiles.create') }}" class="btn btn-sm btn-primary shadow-sm mr-2">
                <i class="fas fa-plus fa-sm text-white-50"></i> Add New Profile
            </a>
            <form action="{{ route('ppp-profiles.sync-from-mikrotik') }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-sm btn-info shadow-sm"
                        onclick="return confirm('📥 Download PPP Profiles from MikroTik?\n\n• Profiles will be updated in database\n• No existing data will be lost\n• Safe merge operation')">
                    <i class="fas fa-sync fa-sm text-white-50"></i> Sync from MikroTik
                </button>
            </form>
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
            <form method="GET" action="{{ route('ppp-profiles.index') }}">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="search">Search</label>
                            <input type="text" class="form-control" name="search" 
                                   value="{{ request('search') }}" 
                                   placeholder="Profile name...">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select class="form-control" name="status">
                                <option value="">All Status</option>
                                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="rate_limit">Rate Limit</label>
                            <select class="form-control" name="rate_limit">
                                <option value="">All Rates</option>
                                <option value="1M/1M" {{ request('rate_limit') == '1M/1M' ? 'selected' : '' }}>1M/1M</option>
                                <option value="2M/2M" {{ request('rate_limit') == '2M/2M' ? 'selected' : '' }}>2M/2M</option>
                                <option value="5M/5M" {{ request('rate_limit') == '5M/5M' ? 'selected' : '' }}>5M/5M</option>
                                <option value="10M/10M" {{ request('rate_limit') == '10M/10M' ? 'selected' : '' }}>10M/10M</option>
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
                                <a href="{{ route('ppp-profiles.index') }}" class="btn btn-secondary">
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
                                Total Profiles</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $pppProfiles->total() }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-cog fa-2x text-gray-300"></i>
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
                                Active Profiles</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $pppProfiles->where('is_active', true)->count() }}
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
                                {{ $pppProfiles->whereNotNull('mikrotik_id')->count() }}
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
                                Secrets Using</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $pppProfiles->sum(function($profile) { return $profile->pppSecrets->count(); }) }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
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
                <i class="fas fa-cog mr-2"></i>PPP Profile List
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
                    <span id="selectedCount">0</span> profile(s) selected
                </div>
                <button type="button" id="bulkDeleteBtn" class="btn btn-danger btn-sm mr-2">
                    <i class="fas fa-trash"></i> Delete Selected
                </button>
                <button type="button" id="bulkSyncBtn" class="btn btn-info btn-sm mr-2">
                    <i class="fas fa-sync"></i> Sync Selected
                </button>
                <button type="button" id="clearSelectionBtn" class="btn btn-secondary btn-sm">
                    <i class="fas fa-times"></i> Clear
                </button>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="profilesTable" width="100%" cellspacing="0">
                    <thead class="thead-light">
                        <tr>
                            <th width="30">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="selectAll">
                                    <label class="custom-control-label" for="selectAll"></label>
                                </div>
                            </th>
                            <th>Profile Name</th>
                            <th>Rate Limit</th>
                            <th>Configuration</th>
                            <th>Usage</th>
                            <th>Status</th>
                            <th width="200">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pppProfiles as $profile)
                        <tr>
                            <td>
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" 
                                           id="profile_{{ $profile->id }}" 
                                           name="selected_profiles[]" 
                                           value="{{ $profile->id }}">
                                    <label class="custom-control-label" for="profile_{{ $profile->id }}"></label>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div>
                                        <strong>{{ $profile->name }}</strong>
                                        <button class="btn btn-link btn-sm p-0 ml-2 copy-btn" 
                                                data-copy="{{ $profile->name }}" 
                                                title="Copy profile name">
                                            <i class="fas fa-copy text-muted"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="mt-1">
                                    @if($profile->mikrotik_id)
                                        <span class="badge badge-info badge-sm">
                                            <i class="fas fa-wifi"></i> Synced
                                        </span>
                                    @else
                                        <span class="badge badge-warning badge-sm">
                                            <i class="fas fa-exclamation-triangle"></i> Local Only
                                        </span>
                                    @endif
                                    
                                    @if($profile->auto_sync)
                                        <span class="badge badge-success badge-sm">
                                            <i class="fas fa-sync-alt"></i> Auto-Sync
                                        </span>
                                    @endif
                                </div>
                                @if($profile->description)
                                    <div class="mt-1">
                                        <small class="text-muted">{{ $profile->description }}</small>
                                    </div>
                                @endif
                            </td>
                            <td>
                                @if($profile->rate_limit)
                                    @php
                                        $rates = explode('/', $profile->rate_limit);
                                        $download = $rates[0] ?? '';
                                        $upload = $rates[1] ?? '';
                                    @endphp
                                    <div class="mb-1">
                                        <span class="badge badge-primary">
                                            <i class="fas fa-download"></i> {{ $download }}
                                        </span>
                                        <span class="badge badge-success">
                                            <i class="fas fa-upload"></i> {{ $upload }}
                                        </span>
                                    </div>
                                @else
                                    <span class="badge badge-secondary">Unlimited</span>
                                @endif
                                @if($profile->burst_limit)
                                    <br><small class="text-muted"><i class="fas fa-rocket"></i> Burst: {{ $profile->burst_limit }}</small>
                                @endif
                                @if($profile->burst_threshold)
                                    <br><small class="text-muted"><i class="fas fa-tachometer-alt"></i> Threshold: {{ $profile->burst_threshold }}</small>
                                @endif
                            </td>
                            <td>
                                @if($profile->session_timeout)
                                    <div class="mb-1">
                                        <small><strong><i class="fas fa-clock"></i> Session:</strong> {{ $profile->session_timeout }}s</small>
                                    </div>
                                @endif
                                @if($profile->idle_timeout)
                                    <div class="mb-1">
                                        <small><strong><i class="fas fa-pause"></i> Idle:</strong> {{ $profile->idle_timeout }}s</small>
                                    </div>
                                @endif
                                @if($profile->keepalive_timeout)
                                    <div class="mb-1">
                                        <small><strong><i class="fas fa-heartbeat"></i> Keepalive:</strong> {{ $profile->keepalive_timeout }}s</small>
                                    </div>
                                @endif
                                @if($profile->local_address)
                                    <div class="mb-1">
                                        <small><strong><i class="fas fa-map-marker-alt"></i> Local IP:</strong> {{ $profile->local_address }}</small>
                                    </div>
                                @endif
                                @if($profile->remote_address)
                                    <div class="mb-1">
                                        <small><strong><i class="fas fa-globe"></i> Remote IP:</strong> {{ $profile->remote_address }}</small>
                                    </div>
                                @endif
                            </td>
                            <td>
                                <div class="text-center">
                                    <span class="badge badge-secondary badge-lg">
                                        <i class="fas fa-users"></i> {{ $profile->pppSecrets->count() }}
                                    </span>
                                    @if($profile->pppSecrets->count() > 0)
                                        <div class="mt-1">
                                            <small class="text-success">
                                                <i class="fas fa-check-circle"></i> {{ $profile->pppSecrets->where('is_active', true)->count() }} active
                                            </small>
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td class="text-center">
                                @if($profile->is_active)
                                    <span class="badge badge-success badge-lg">
                                        <i class="fas fa-check-circle"></i> Active
                                    </span>
                                @else
                                    <span class="badge badge-danger badge-lg">
                                        <i class="fas fa-times-circle"></i> Inactive
                                    </span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="{{ route('ppp-profiles.show', $profile) }}" 
                                       class="btn btn-sm btn-info" 
                                       title="View Profile"
                                       data-toggle="tooltip">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('ppp-profiles.edit', $profile) }}" 
                                       class="btn btn-sm btn-warning" 
                                       title="Edit Profile"
                                       data-toggle="tooltip">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-sm btn-secondary dropdown-toggle" 
                                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
                                                title="Sync Options"
                                                data-toggle="tooltip">
                                            <i class="fas fa-sync"></i>
                                        </button>
                                        <div class="dropdown-menu">
                                            <form action="{{ route('ppp-profiles.sync-to-mikrotik', $profile) }}" 
                                                  method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="dropdown-item"
                                                        onclick="return confirm('📤 Upload Profile to MikroTik?\n\n• This profile will be created/updated on router\n• Changes will be made directly to MikroTik')">
                                                    <i class="fas fa-upload text-success"></i> Push to MikroTik
                                                </button>
                                            </form>
                                            
                                            <div class="dropdown-divider"></div>
                                            
                                            <form action="{{ route('mikrotik-settings.toggle-profile-auto-sync', $profile->id) }}" 
                                                  method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="dropdown-item">
                                                    @if($profile->auto_sync)
                                                        <i class="fas fa-pause text-warning"></i> Disable Auto-Sync
                                                    @else
                                                        <i class="fas fa-play text-success"></i> Enable Auto-Sync
                                                    @endif
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                    
                                    @if($profile->pppSecrets->count() == 0)
                                        <form action="{{ route('ppp-profiles.destroy', $profile) }}" 
                                              method="POST" class="d-inline delete-form">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" 
                                                    title="Delete Profile"
                                                    data-toggle="tooltip">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    @else
                                        <button class="btn btn-sm btn-danger" 
                                                title="Cannot delete - profile is in use" 
                                                data-toggle="tooltip"
                                                disabled>
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <div class="text-muted">
                                    <i class="fas fa-inbox fa-3x mb-3"></i>
                                    <p class="mb-0">No PPP profiles found.</p>
                                    <small>Create your first profile or sync from MikroTik.</small>
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
                    Showing {{ $pppProfiles->firstItem() ?? 0 }} to {{ $pppProfiles->lastItem() ?? 0 }} 
                    of {{ $pppProfiles->total() }} profiles
                </div>
                <div>
                    {{ $pppProfiles->withQueryString()->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Initialize DataTable with proper configuration
    $('#profilesTable').DataTable({
        "responsive": true,
        "lengthChange": true,
        "autoWidth": false,
        "order": [[ 1, "asc" ]],
        "pageLength": 25,
        "language": {
            "search": "Search Profiles:",
            "lengthMenu": "Show _MENU_ profiles per page",
            "info": "Showing _START_ to _END_ of _TOTAL_ profiles",
            "infoEmpty": "No profiles found",
            "infoFiltered": "(filtered from _MAX_ total profiles)",
            "zeroRecords": "No matching profiles found",
            "paginate": {
                "first": "First",
                "last": "Last", 
                "next": "Next",
                "previous": "Previous"
            }
        },
        "buttons": [
            {
                extend: 'copy',
                text: '<i class="fas fa-copy"></i> Copy',
                className: 'btn btn-secondary btn-sm'
            },
            {
                extend: 'excel',
                text: '<i class="fas fa-file-excel"></i> Excel',
                className: 'btn btn-success btn-sm'
            },
            {
                extend: 'pdf',
                text: '<i class="fas fa-file-pdf"></i> PDF',
                className: 'btn btn-danger btn-sm'
            },
            {
                extend: 'print',
                text: '<i class="fas fa-print"></i> Print',
                className: 'btn btn-info btn-sm'
            }
        ],
        "dom": '<"row"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6"p>><"row"<"col-md-12"B>>',
        "columnDefs": [
            { "orderable": false, "targets": [0, -1] } // Disable sorting on checkbox and Action column
        ]
    });

    // Show/hide bulk actions based on checkbox selection
    function toggleBulkActions() {
        const checkedBoxes = $('input[name="selected_profiles[]"]:checked');
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
        $('input[name="selected_profiles[]"]').prop('checked', this.checked);
        toggleBulkActions();
    });

    // Handle individual checkboxes
    $(document).on('change', 'input[name="selected_profiles[]"]', function() {
        toggleBulkActions();
        // Update select all checkbox state
        const total = $('input[name="selected_profiles[]"]').length;
        const checked = $('input[name="selected_profiles[]"]:checked').length;
        $('#selectAll').prop('indeterminate', checked > 0 && checked < total);
        $('#selectAll').prop('checked', checked === total);
    });

    // Clear selection
    $('#clearSelectionBtn').click(function() {
        $('input[name="selected_profiles[]"], #selectAll').prop('checked', false);
        toggleBulkActions();
    });

    // Bulk delete functionality
    $('#bulkDeleteBtn').click(function() {
        const selectedIds = [];
        $('input[name="selected_profiles[]"]:checked').each(function() {
            selectedIds.push($(this).val());
        });

        if (selectedIds.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'No Selection',
                text: 'Please select profiles to delete.',
                confirmButtonClass: 'btn btn-primary'
            });
            return;
        }

        Swal.fire({
            title: 'Delete Selected Profiles?',
            text: `You are about to delete ${selectedIds.length} profile(s). This action cannot be undone!`,
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
                    action: '{{ route("ppp-profiles.bulk-delete") }}'
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
                        name: 'selected_profiles[]',
                        value: id
                    }));
                });
                
                $('body').append(form);
                form.submit();
            }
        });
    });

    // Bulk sync functionality
    $('#bulkSyncBtn').click(function() {
        const selectedIds = [];
        $('input[name="selected_profiles[]"]:checked').each(function() {
            selectedIds.push($(this).val());
        });

        if (selectedIds.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'No Selection',
                text: 'Please select profiles to sync.',
                confirmButtonClass: 'btn btn-primary'
            });
            return;
        }

        Swal.fire({
            title: 'Sync Selected Profiles?',
            text: `You are about to sync ${selectedIds.length} profile(s) to MikroTik.`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#007bff',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, sync them!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                // Create form for bulk sync
                const form = $('<form>', {
                    method: 'POST',
                    action: '{{ route("ppp-profiles.bulk-sync") }}'
                });
                
                form.append($('<input>', {
                    type: 'hidden',
                    name: '_token',
                    value: '{{ csrf_token() }}'
                }));
                
                selectedIds.forEach(id => {
                    form.append($('<input>', {
                        type: 'hidden',
                        name: 'selected_profiles[]',
                        value: id
                    }));
                });
                
                $('body').append(form);
                form.submit();
            }
        });
    });

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
            title: 'Delete Profile?',
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
</script>
@endpush
