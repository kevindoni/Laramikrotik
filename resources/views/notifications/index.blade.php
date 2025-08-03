@extends('layouts.admin')

@section('title', 'Notifications')

@section('main-content')
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-bell"></i>
            Notification Center
        </h1>
        <div>
            <button type="button" class="btn btn-success btn-sm" onclick="markAllAsRead()">
                <i class="fas fa-check-double"></i> Mark All as Read
            </button>
            <button type="button" class="btn btn-info btn-sm" onclick="refreshNotifications()">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
        </div>
    </div>

    <!-- Alert Messages -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i>
            <strong>Success!</strong> {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <!-- Notifications List -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-envelope"></i>
                Recent Notifications
            </h6>
        </div>
        <div class="card-body">
            @if($notifications->count() > 0)
                <div class="list-group">
                    @foreach($notifications as $notification)
                    <div class="list-group-item {{ $notification->is_read ? 'list-group-item-light' : 'list-group-item-primary' }} mb-2">
                        <div class="d-flex align-items-center">
                            <div class="mr-3">
                                <div class="icon-circle bg-{{ $notification->color }}">
                                    <i class="{{ $notification->icon }} text-white"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1 {{ $notification->is_read ? 'text-muted' : 'text-dark' }}">
                                            {{ $notification->title }}
                                            @if(!$notification->is_read)
                                                <span class="badge badge-primary badge-sm ml-2">New</span>
                                            @endif
                                        </h6>
                                        <p class="mb-1 {{ $notification->is_read ? 'text-muted' : '' }}">
                                            {{ $notification->message }}
                                        </p>
                                        <small class="text-muted">
                                            <i class="fas fa-clock"></i> {{ $notification->created_at->diffForHumans() }}
                                            @if($notification->is_read)
                                                • <i class="fas fa-check"></i> Read {{ $notification->read_at->diffForHumans() }}
                                            @endif
                                        </small>
                                        
                                        @if($notification->data)
                                            <div class="mt-2">
                                                @if(isset($notification->data['customer_name']))
                                                    <span class="badge badge-info">
                                                        <i class="fas fa-user"></i> {{ $notification->data['customer_name'] }}
                                                    </span>
                                                @endif
                                                @if(isset($notification->data['amount']))
                                                    <span class="badge badge-success">
                                                        <i class="fas fa-money-bill-wave"></i> Rp {{ number_format($notification->data['amount'], 0, ',', '.') }}
                                                    </span>
                                                @endif
                                                @if(isset($notification->data['payment_method']))
                                                    <span class="badge badge-secondary">
                                                        <i class="fas fa-credit-card"></i> {{ ucwords(str_replace('_', ' ', $notification->data['payment_method'])) }}
                                                    </span>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                    <div class="dropdown">
                                        <button class="btn btn-link btn-sm" type="button" data-toggle="dropdown">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-right">
                                            @if(!$notification->is_read)
                                                <a class="dropdown-item" href="#" onclick="markAsRead({{ $notification->id }})">
                                                    <i class="fas fa-check"></i> Mark as Read
                                                </a>
                                            @endif
                                            <a class="dropdown-item text-danger" href="#" onclick="deleteNotification({{ $notification->id }})">
                                                <i class="fas fa-trash"></i> Delete
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-center mt-4">
                    {{ $notifications->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-inbox fa-5x text-gray-300 mb-3"></i>
                    <h4 class="text-gray-500">No Notifications</h4>
                    <p class="text-muted">You're all caught up! No notifications to display.</p>
                </div>
            @endif
        </div>
    </div>
</div>

<style>
.icon-circle {
    height: 2.5rem;
    width: 2.5rem;
    border-radius: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.list-group-item-primary {
    background-color: rgba(0, 123, 255, 0.1);
    border-color: rgba(0, 123, 255, 0.2);
}
</style>

<script>
function markAsRead(notificationId) {
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
            location.reload();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        toastr.error('Failed to mark notification as read', 'Error');
    });
}

function markAllAsRead() {
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
            toastr.success('All notifications marked as read', 'Success');
            location.reload();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        toastr.error('Failed to mark notifications as read', 'Error');
    });
}

function deleteNotification(notificationId) {
    if (confirm('Are you sure you want to delete this notification?')) {
        fetch(`/notifications/${notificationId}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                toastr.success('Notification deleted', 'Success');
                location.reload();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            toastr.error('Failed to delete notification', 'Error');
        });
    }
}

function refreshNotifications() {
    location.reload();
}
</script>
@endsection
