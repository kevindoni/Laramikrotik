@extends('layouts.admin')

@section('main-content')
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">{{ __('Customer Details') }} - {{ $customer->name }}</h1>
        <div>
            <a href="{{ route('customers.edit', $customer) }}" class="d-none d-sm-inline-block btn btn-sm btn-warning shadow-sm">
                <i class="fas fa-edit fa-sm text-white-50"></i> {{ __('Edit Customer') }}
            </a>
            <a href="{{ route('customers.index') }}" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
                <i class="fas fa-arrow-left fa-sm text-white-50"></i> {{ __('Back to Customers') }}
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success border-left-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <!-- Content Row -->
    <div class="row">
        <div class="col-lg-8">
            <!-- Customer Information -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">{{ __('Customer Information') }}</h6>
                    <span class="badge badge-{{ $customer->is_active ? 'success' : 'danger' }}">
                        {{ $customer->is_active ? __('Active') : __('Inactive') }}
                    </span>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="30%">{{ __('Full Name') }}</th>
                                    <td>{{ $customer->name }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('Phone Number') }}</th>
                                    <td>
                                        <a href="tel:{{ $customer->phone }}">{{ $customer->phone }}</a>
                                        <a href="https://wa.me/{{ $customer->phone }}" target="_blank" class="btn btn-sm btn-success ml-2">
                                            <i class="fab fa-whatsapp"></i> WhatsApp
                                        </a>
                                    </td>
                                </tr>
                                <tr>
                                    <th>{{ __('Email Address') }}</th>
                                    <td>
                                        @if($customer->email)
                                            <a href="mailto:{{ $customer->email }}">{{ $customer->email }}</a>
                                        @else
                                            <span class="text-muted">{{ __('Not provided') }}</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>{{ __('Address') }}</th>
                                    <td>{{ $customer->address }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('Location') }}</th>
                                    <td>{{ $customer->location ?: __('Not specified') }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="40%">{{ __('Identity Card Type') }}</th>
                                    <td>{{ $customer->identity_card_type ?: __('Not specified') }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('Identity Card') }}</th>
                                    <td>{{ $customer->identity_card ?: __('Not provided') }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('ID Number') }}</th>
                                    <td>{{ $customer->identity_card_number ?: __('Not provided') }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('Coordinates') }}</th>
                                    <td>
                                        @if($customer->coordinates)
                                            {{ $customer->coordinates }}
                                            <a href="https://maps.google.com/?q={{ $customer->coordinates }}" target="_blank" class="btn btn-sm btn-info ml-2">
                                                <i class="fas fa-map-marker-alt"></i> {{ __('View on Map') }}
                                            </a>
                                        @else
                                            <span class="text-muted">{{ __('Not provided') }}</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>{{ __('Registered Date') }}</th>
                                    <td>{{ $customer->registered_date ? $customer->registered_date->format('d M Y') : __('Not specified') }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('Member Since') }}</th>
                                    <td>{{ $customer->created_at->format('d M Y') }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    @if($customer->notes)
                        <div class="mt-3">
                            <h6 class="text-primary">{{ __('Notes') }}</h6>
                            <div class="alert alert-info">
                                {{ $customer->notes }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- PPP Secrets -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">{{ __('PPP Secrets') }} ({{ $customer->pppSecrets->count() }})</h6>
                    <a href="{{ route('ppp-secrets.create', ['customer_id' => $customer->id]) }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus"></i> {{ __('Add PPP Secret') }}
                    </a>
                </div>
                <div class="card-body">
                    @if($customer->pppSecrets->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>{{ __('Username') }}</th>
                                        <th>{{ __('Profile') }}</th>
                                        <th>{{ __('Status') }}</th>
                                        <th>{{ __('Created') }}</th>
                                        <th>{{ __('Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($customer->pppSecrets as $secret)
                                    <tr>
                                        <td>{{ $secret->username }}</td>
                                        <td>{{ $secret->pppProfile->name ?? __('No Profile') }}</td>
                                        <td>
                                            <span class="badge badge-{{ $secret->is_active ? 'success' : 'danger' }}">
                                                {{ $secret->is_active ? __('Active') : __('Inactive') }}
                                            </span>
                                        </td>
                                        <td>{{ $secret->created_at->format('d M Y') }}</td>
                                        <td>
                                            <a href="{{ route('ppp-secrets.show', $secret) }}" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('ppp-secrets.edit', $secret) }}" class="btn btn-sm btn-warning">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-key fa-3x text-gray-300 mb-3"></i>
                            <p class="text-muted">{{ __('No PPP secrets found for this customer.') }}</p>
                            <a href="{{ route('ppp-secrets.create', ['customer_id' => $customer->id]) }}" class="btn btn-primary">
                                <i class="fas fa-plus"></i> {{ __('Add First PPP Secret') }}
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Recent Invoices -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">{{ __('Recent Invoices') }}</h6>
                    <a href="{{ route('invoices.create', ['customer_id' => $customer->id]) }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus"></i> {{ __('Create Invoice') }}
                    </a>
                </div>
                <div class="card-body">
                    @if($customer->invoices->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>{{ __('Invoice #') }}</th>
                                        <th>{{ __('Date') }}</th>
                                        <th>{{ __('Amount') }}</th>
                                        <th>{{ __('Status') }}</th>
                                        <th>{{ __('Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($customer->invoices->take(5) as $invoice)
                                    <tr>
                                        <td>{{ $invoice->invoice_number }}</td>
                                        <td>{{ $invoice->invoice_date->format('d M Y') }}</td>
                                        <td>Rp {{ number_format($invoice->amount, 0, ',', '.') }}</td>
                                        <td>
                                            <span class="badge badge-{{ $invoice->status === 'paid' ? 'success' : ($invoice->status === 'overdue' ? 'danger' : 'warning') }}">
                                                {{ ucfirst($invoice->status) }}
                                            </span>
                                        </td>
                                        <td>
                                            <a href="{{ route('invoices.show', $invoice) }}" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if($customer->invoices->count() > 5)
                            <div class="text-center mt-3">
                                <a href="{{ route('invoices.index', ['customer_id' => $customer->id]) }}" class="btn btn-outline-primary">
                                    {{ __('View All Invoices') }} ({{ $customer->invoices->count() }})
                                </a>
                            </div>
                        @endif
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-file-invoice fa-3x text-gray-300 mb-3"></i>
                            <p class="text-muted">{{ __('No invoices found for this customer.') }}</p>
                            <a href="{{ route('invoices.create', ['customer_id' => $customer->id]) }}" class="btn btn-primary">
                                <i class="fas fa-plus"></i> {{ __('Create First Invoice') }}
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Statistics Card -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">{{ __('Statistics') }}</h6>
                </div>
                <div class="card-body">
                    <div class="row no-gutters align-items-center mb-3">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">{{ __('PPP Secrets') }}</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $customer->pppSecrets->count() }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-key fa-2x text-gray-300"></i>
                        </div>
                    </div>

                    <div class="row no-gutters align-items-center mb-3">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">{{ __('Active Secrets') }}</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $customer->activePppSecrets->count() }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>

                    <div class="row no-gutters align-items-center mb-3">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">{{ __('Total Invoices') }}</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $customer->invoices->count() }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-file-invoice fa-2x text-gray-300"></i>
                        </div>
                    </div>

                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">{{ __('Unpaid Invoices') }}</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $customer->unpaidInvoices->count() }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions Card -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">{{ __('Quick Actions') }}</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('ppp-secrets.create', ['customer_id' => $customer->id]) }}" class="btn btn-outline-primary btn-block">
                            <i class="fas fa-key"></i> {{ __('Add PPP Secret') }}
                        </a>
                        
                        <a href="{{ route('invoices.create', ['customer_id' => $customer->id]) }}" class="btn btn-outline-info btn-block">
                            <i class="fas fa-file-invoice"></i> {{ __('Create Invoice') }}
                        </a>
                        
                        @if($customer->phone)
                        <a href="https://wa.me/{{ $customer->phone }}" target="_blank" class="btn btn-outline-success btn-block">
                            <i class="fab fa-whatsapp"></i> {{ __('Send WhatsApp') }}
                        </a>
                        @endif
                        
                        @if($customer->email)
                        <a href="mailto:{{ $customer->email }}" class="btn btn-outline-secondary btn-block">
                            <i class="fas fa-envelope"></i> {{ __('Send Email') }}
                        </a>
                        @endif

                        <hr>

                        <form action="{{ route('customers.destroy', $customer) }}" method="POST" onsubmit="return confirm('{{ __('Are you sure you want to delete this customer? This action cannot be undone.') }}')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger btn-block">
                                <i class="fas fa-trash"></i> {{ __('Delete Customer') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
