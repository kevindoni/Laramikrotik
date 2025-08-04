@extends('layouts.admin')

@section('main-content')
<div class="container-fluid">

    <!-- Alert Messages -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i>
            <strong>Berhasil!</strong> {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle"></i>
            <strong>Error!</strong> {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-exclamation-triangle text-warning"></i>
            Payment Alerts
        </h1>
        <div class="btn-group">
            <button type="button" class="btn btn-warning btn-sm" data-toggle="modal" data-target="#autoBlockModal">
                <i class="fas fa-robot"></i> Auto Block Overdue
            </button>
            <a href="{{ route('alerts.index') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-sync-alt"></i> Refresh
            </a>
        </div>
    </div>

    <!-- Alert Summary Cards -->
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Due Soon (1-7 Days)</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $alertCounts['upcoming'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                Overdue</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $alertCounts['overdue'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
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
                                Should be Blocked</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $alertCounts['to_block'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-ban fa-2x text-gray-300"></i>
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
                                Paid Today</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $alertCounts['paid_today'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-money-check-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Overdue Users -->
    @if($overdueUsers->count() > 0)
    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-danger text-white">
            <h6 class="m-0 font-weight-bold">
                <i class="fas fa-exclamation-triangle"></i>
                Overdue Payments ({{ $overdueUsers->count() }})
            </h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                    <thead class="thead-light">
                        <tr>
                            <th>Username</th>
                            <th>Customer</th>
                            <th>Current Profile</th>
                            <th>Due Date</th>
                            <th>Days Overdue</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($overdueUsers as $user)
                        <tr class="table-danger">
                            <td>
                                <strong>{{ $user->username }}</strong>
                                @if($user->customer)
                                    <br><small class="text-muted">{{ $user->customer->name }}</small>
                                @endif
                            </td>
                            <td>
                                @if($user->customer)
                                    {{ $user->customer->name }}
                                    @if($user->customer->phone)
                                        <br><small><i class="fas fa-phone"></i> {{ $user->customer->phone }}</small>
                                    @endif
                                @else
                                    <span class="text-muted">No customer data</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge badge-{{ $user->pppProfile->name === 'Blokir' ? 'danger' : 'primary' }}">
                                    {{ $user->pppProfile->name ?? 'default' }}
                                </span>
                            </td>
                            <td>
                                <span class="text-danger">
                                    <i class="fas fa-calendar-times"></i>
                                    {{ $user->due_date->format('d M Y') }}
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-danger">
                                    {{ $today->diffInDays($user->due_date) }} days
                                </span>
                            </td>
                            <td>
                                @if($user->pppProfile->name !== 'Blokir')
                                    <button type="button" class="btn btn-danger btn-sm" 
                                            onclick="blockUser({{ $user->id }}, '{{ $user->username }}')">
                                        <i class="fas fa-ban"></i> Block
                                    </button>
                                @else
                                    <button type="button" class="btn btn-success btn-sm" 
                                            onclick="unblockUser({{ $user->id }}, '{{ $user->username }}')">
                                        <i class="fas fa-unlock"></i> Unblock
                                    </button>
                                @endif
                                <button type="button" class="btn btn-primary btn-sm" 
                                        onclick="markAsPaid({{ $user->id }}, '{{ $user->username }}')">
                                    <i class="fas fa-money-check-alt"></i> Mark as Paid
                                </button>
                                <a href="{{ route('ppp-secrets.show', $user->id) }}" class="btn btn-info btn-sm">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    <!-- Due Soon Users -->
    @if($upcomingDueUsers->count() > 0)
    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-warning text-dark">
            <h6 class="m-0 font-weight-bold">
                <i class="fas fa-clock"></i>
                Due Soon (Next 7 Days) - {{ $upcomingDueUsers->count() }}
            </h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                    <thead class="thead-light">
                        <tr>
                            <th>Username</th>
                            <th>Customer</th>
                            <th>Profile</th>
                            <th>Due Date</th>
                            <th>Days Left</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($upcomingDueUsers as $user)
                        <tr class="{{ $user->due_date->isToday() ? 'table-warning' : ($user->due_date->isTomorrow() ? 'table-info' : '') }}">
                            <td>
                                <strong>{{ $user->username }}</strong>
                            </td>
                            <td>
                                @if($user->customer)
                                    {{ $user->customer->name }}
                                    @if($user->customer->phone)
                                        <br><small><i class="fas fa-phone"></i> {{ $user->customer->phone }}</small>
                                    @endif
                                @else
                                    <span class="text-muted">No customer data</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge badge-primary">
                                    {{ $user->pppProfile->name ?? 'default' }}
                                </span>
                            </td>
                            <td>
                                <i class="fas fa-calendar-alt text-{{ $user->due_date->isToday() ? 'danger' : ($user->due_date->isTomorrow() ? 'warning' : 'info') }}"></i>
                                {{ $user->due_date->format('d M Y') }}
                                @if($user->due_date->isToday())
                                    <span class="badge badge-danger ml-1">TODAY</span>
                                @elseif($user->due_date->isTomorrow())
                                    <span class="badge badge-warning ml-1">TOMORROW</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge badge-{{ $user->due_date->isToday() ? 'danger' : ($user->due_date->isTomorrow() ? 'warning' : 'info') }}">
                                    {{ $user->due_date->diffInDays($today) }} days
                                </span>
                            </td>
                            <td>
                                @if($user->due_date->isToday() || $user->due_date->isTomorrow())
                                    <button type="button" class="btn btn-warning btn-sm" 
                                            onclick="blockUser({{ $user->id }}, '{{ $user->username }}')">
                                        <i class="fas fa-exclamation-triangle"></i> Block Now
                                    </button>
                                @endif
                                <button type="button" class="btn btn-primary btn-sm" 
                                        onclick="markAsPaid({{ $user->id }}, '{{ $user->username }}')">
                                    <i class="fas fa-money-check-alt"></i> Mark as Paid
                                </button>
                                <a href="{{ route('ppp-secrets.show', $user->id) }}" class="btn btn-info btn-sm">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    @if($alertCounts['total'] == 0)
    <div class="card shadow">
        <div class="card-body text-center py-5">
            <i class="fas fa-check-circle fa-5x text-success mb-3"></i>
            <h4 class="text-success">All Clear!</h4>
            <p class="text-muted">No payment alerts at the moment. All users are up to date with their payments.</p>
        </div>
    </div>
    @endif

</div>

<!-- Auto Block Modal -->
<div class="modal fade" id="autoBlockModal" tabindex="-1" role="dialog" aria-labelledby="autoBlockModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="autoBlockModalLabel">
                    <i class="fas fa-robot"></i> Auto Block Overdue Users
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Warning!</strong> This will automatically block all users with overdue payments by changing their profile to "Blokir".
                </div>
                <p>Users to be blocked: <strong>{{ $alertCounts['to_block'] }}</strong></p>
                @if(!$blokirProfile)
                    <div class="alert alert-danger">
                        <i class="fas fa-times-circle"></i>
                        Profile "Blokir" not found. Please create this profile first.
                    </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                @if($blokirProfile)
                    <form action="{{ route('alerts.auto-block') }}" method="POST" style="display: inline;">
                        @csrf
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-robot"></i> Execute Auto Block
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Payment History Modal -->
<div class="modal fade" id="paymentHistoryModal" tabindex="-1" role="dialog" aria-labelledby="paymentHistoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="paymentHistoryModalLabel">
                    <i class="fas fa-history"></i> Payment Confirmation
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="paymentForm" method="POST">
                    @csrf
                    <div class="row">
                        <div class="col-md-6">
                            <h6><i class="fas fa-user"></i> User Information</h6>
                            <div class="card bg-light">
                                <div class="card-body">
                                    <p><strong>Username:</strong> <span id="modal-username"></span></p>
                                    <p><strong>Customer:</strong> <span id="modal-customer"></span></p>
                                    <p><strong>Current Profile:</strong> <span id="modal-profile"></span></p>
                                    <p><strong>Current Due Date:</strong> <span id="modal-due-date"></span></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6><i class="fas fa-money-check-alt"></i> Payment Details</h6>
                            <div class="form-group">
                                <label for="payment-amount">Payment Amount (Rp)</label>
                                <input type="number" class="form-control" id="payment-amount" name="payment_amount" placeholder="Enter payment amount" required>
                            </div>
                            <div class="form-group">
                                <label for="payment-method">Payment Method</label>
                                <select class="form-control" id="payment-method" name="payment_method" required>
                                    <option value="">Select Payment Method</option>
                                    <option value="cash">Cash</option>
                                    <option value="bank_transfer">Bank Transfer</option>
                                    <option value="e_wallet">E-Wallet</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="payment-notes">Notes (Optional)</label>
                                <textarea class="form-control" id="payment-notes" name="payment_notes" rows="3" placeholder="Additional notes..."></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-success">
                        <i class="fas fa-info-circle"></i>
                        <strong>Confirmation:</strong> This will extend the due date by 30 days and unblock the user if currently blocked.
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" onclick="confirmPayment()">
                    <i class="fas fa-check"></i> Confirm Payment
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function blockUser(userId, username) {
    Swal.fire({
        title: '🚫 Block User?',
        html: `
            <div class="text-left">
                <p class="mb-3">Are you sure you want to block user <strong>"${username}"</strong>?</p>
                <div class="alert alert-warning">
                    <strong>This will:</strong>
                    <ul class="mb-0 mt-2">
                        <li>Change their profile to "Blokir"</li>
                        <li>Disconnect their current session</li>
                        <li>Prevent further connections</li>
                    </ul>
                </div>
            </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="fas fa-ban"></i> Yes, Block User!',
        cancelButtonText: '<i class="fas fa-times"></i> Cancel',
        customClass: {
            confirmButton: 'btn btn-danger mx-2',
            cancelButton: 'btn btn-secondary mx-2'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // Create form and submit
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/alerts/block/${userId}`;
            
            // Add CSRF token
            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = '{{ csrf_token() }}';
            form.appendChild(csrfToken);
            
            document.body.appendChild(form);
            form.submit();
        }
    });
}

function unblockUser(userId, username) {
    Swal.fire({
        title: '✅ Unblock User?',
        html: `
            <div class="text-left">
                <p class="mb-3">Are you sure you want to unblock user <strong>"${username}"</strong>?</p>
                <div class="alert alert-success">
                    <strong>This will:</strong>
                    <ul class="mb-0 mt-2">
                        <li>Restore their default profile</li>
                        <li>Allow them to connect again</li>
                        <li>Remove blocking restrictions</li>
                    </ul>
                </div>
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="fas fa-check"></i> Yes, Unblock User!',
        cancelButtonText: '<i class="fas fa-times"></i> Cancel',
        customClass: {
            confirmButton: 'btn btn-success mx-2',
            cancelButton: 'btn btn-secondary mx-2'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // Create form and submit
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/alerts/unblock/${userId}`;
        
        // Add CSRF token
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = '{{ csrf_token() }}';
        form.appendChild(csrfToken);
        
        document.body.appendChild(form);
        form.submit();
    }
}

function markAsPaid(userId, username) {
    // Set modal data
    $('#modal-username').text(username);
    
    // Get user data from table row to populate modal
    const userRow = $(`button[onclick*="markAsPaid(${userId}"]`).closest('tr');
    const customer = userRow.find('td:nth-child(2)').text().trim();
    const profile = userRow.find('.badge').text().trim();
    const dueDate = userRow.find('td:nth-child(4)').text().trim();
    
    $('#modal-customer').text(customer);
    $('#modal-profile').text(profile);
    $('#modal-due-date').text(dueDate);
    
    // Set form action
    $('#paymentForm').attr('action', `/alerts/mark-paid/${userId}`);
    
    // Show modal
    $('#paymentHistoryModal').modal('show');
}

function confirmPayment() {
    const form = $('#paymentForm')[0];
    const amount = $('#payment-amount').val();
    const method = $('#payment-method').val();
    
    if (!amount || !method) {
        toastr.warning('Please fill in payment amount and method', 'Incomplete Data');
        return;
    }
    
    form.submit();
}

// Auto refresh alerts every 5 minutes
setInterval(function() {
    location.reload();
}, 300000);

// Show toast notifications for session messages
@if(session('success'))
    toastr.success('{!! addslashes(session('success')) !!}', 'Berhasil!', {
        closeButton: true,
        progressBar: true,
        timeOut: 10000
    });
@endif

@if(session('error'))
    toastr.error('{!! addslashes(session('error')) !!}', 'Error!', {
        closeButton: true,
        progressBar: true,
        timeOut: 10000
    });
@endif
</script>
@endpush
