@extends('layouts.admin')

@section('main-content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">{{ __('User Details') }}</h1>
        <div>
            <a href="{{ route('users.edit', $user) }}" class="btn btn-sm btn-warning shadow-sm">
                <i class="fas fa-edit fa-sm text-white-50"></i> {{ __('Edit') }}
            </a>
            <a href="{{ route('users.index') }}" class="btn btn-sm btn-primary shadow-sm">
                <i class="fas fa-arrow-left fa-sm text-white-50"></i> {{ __('Back') }}
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">{{ __('User Information') }}</h6>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <td class="font-weight-bold" style="width: 200px;">{{ __('User ID') }}</td>
                            <td>: {{ $user->id }}</td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold">{{ __('Full Name') }}</td>
                            <td>: {{ $user->name }}</td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold">{{ __('Email Address') }}</td>
                            <td>: <a href="mailto:{{ $user->email }}" class="text-decoration-none">{{ $user->email }}</a></td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold">{{ __('Role') }}</td>
                            <td>: 
                                @switch($user->role ?? 'user')
                                    @case('admin')
                                        <span class="badge badge-danger">{{ __('Admin') }}</span>
                                        @break
                                    @case('manager')
                                        <span class="badge badge-warning">{{ __('Manager') }}</span>
                                        @break
                                    @case('operator')
                                        <span class="badge badge-info">{{ __('Operator') }}</span>
                                        @break
                                    @default
                                        <span class="badge badge-secondary">{{ __('User') }}</span>
                                @endswitch
                            </td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold">{{ __('Phone Number') }}</td>
                            <td>: {{ $user->phone ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold">{{ __('Address') }}</td>
                            <td>: {{ $user->address ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold">{{ __('Status') }}</td>
                            <td>: 
                                @if($user->is_active ?? true)
                                    <span class="badge badge-success">{{ __('Active') }}</span>
                                @else
                                    <span class="badge badge-danger">{{ __('Inactive') }}</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold">{{ __('Email Verified') }}</td>
                            <td>: 
                                @if($user->email_verified_at)
                                    <span class="badge badge-success">{{ __('Verified') }}</span>
                                    <br><small class="text-muted">{{ $user->email_verified_at->format('d M Y H:i') }}</small>
                                @else
                                    <span class="badge badge-warning">{{ __('Not Verified') }}</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold">{{ __('Member Since') }}</td>
                            <td>: {{ $user->created_at->format('d M Y') }} ({{ $user->created_at->diffForHumans() }})</td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold">{{ __('Last Updated') }}</td>
                            <td>: {{ $user->updated_at->format('d M Y H:i') }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">{{ __('Account Statistics') }}</h6>
                </div>
                <div class="card-body">
                    <div class="row no-gutters">
                        <div class="col-12 mb-3">
                            <div class="text-center">
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    {{ \App\Models\Customer::where('created_by', $user->id)->count() }}
                                </div>
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                    {{ __('Customers Created') }}
                                </div>
                            </div>
                        </div>
                        <div class="col-12 mb-3">
                            <div class="text-center">
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    {{ \App\Models\Invoice::where('created_by', $user->id)->count() }}
                                </div>
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                    {{ __('Invoices Created') }}
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="text-center">
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    {{ \App\Models\Payment::where('created_by', $user->id)->count() }}
                                </div>
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                    {{ __('Payments Recorded') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">{{ __('Actions') }}</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('users.edit', $user) }}" class="btn btn-warning btn-sm">
                            <i class="fas fa-edit"></i> {{ __('Edit User') }}
                        </a>
                        
                        @if(!$user->email_verified_at)
                            <button class="btn btn-info btn-sm" onclick="alert('{{ __('Email verification feature not implemented yet.') }}')">
                                <i class="fas fa-envelope-check"></i> {{ __('Send Verification Email') }}
                            </button>
                        @endif
                        
                        @if($user->id !== auth()->id())
                            <form action="{{ route('users.destroy', $user) }}" method="POST" 
                                  onsubmit="return confirm('{{ __('Are you sure you want to delete this user? This action cannot be undone.') }}')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm w-100">
                                    <i class="fas fa-trash"></i> {{ __('Delete User') }}
                                </button>
                            </form>
                        @else
                            <small class="text-muted">{{ __('You cannot delete your own account.') }}</small>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
