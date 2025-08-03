@extends('layouts.admin')

@section('title', 'Invoices')

@section('main-content')
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Invoice Management</h1>
        <div>
            <a href="{{ route('invoices.create') }}" class="btn btn-sm btn-primary shadow-sm mr-2">
                <i class="fas fa-plus fa-sm text-white-50"></i> Create Invoice
            </a>
            <a href="{{ route('invoices.generate-monthly') }}" class="btn btn-sm btn-info shadow-sm">
                <i class="fas fa-calendar fa-sm text-white-50"></i> Generate Monthly
            </a>
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
            <form method="GET" action="{{ route('invoices.index') }}">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="search">Search</label>
                            <input type="text" class="form-control" name="search" 
                                   value="{{ request('search') }}" 
                                   placeholder="Invoice number, customer name...">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select class="form-control" name="status">
                                <option value="">All Status</option>
                                <option value="unpaid" {{ request('status') == 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                                <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Paid</option>
                                <option value="overdue" {{ request('status') == 'overdue' ? 'selected' : '' }}>Overdue</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="date_from">Date From</label>
                            <input type="date" class="form-control" name="date_from" 
                                   value="{{ request('date_from') }}">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="date_to">Date To</label>
                            <input type="date" class="form-control" name="date_to" 
                                   value="{{ request('date_to') }}">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="period">Period</label>
                            <input type="month" class="form-control" name="period" 
                                   value="{{ request('period') }}">
                        </div>
                    </div>
                    <div class="col-md-1">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <div class="d-flex">
                                <button type="submit" class="btn btn-primary mr-1">
                                    <i class="fas fa-search"></i>
                                </button>
                                <a href="{{ route('invoices.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-refresh"></i>
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
                                Total Invoices</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $statistics['total_invoices'] ?? $invoices->total() }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-file-invoice fa-2x text-gray-300"></i>
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
                                Paid Amount</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                Rp {{ number_format($statistics['paid_amount'] ?? 0, 0, ',', '.') }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
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
                                Outstanding</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                Rp {{ number_format($statistics['outstanding'] ?? 0, 0, ',', '.') }}
                            </div>
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
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $statistics['overdue_count'] ?? 0 }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- DataTable -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Invoice List</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Invoice #</th>
                            <th>Customer</th>
                            <th>Period</th>
                            <th>Amount</th>
                            <th>Due Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invoices as $invoice)
                        <tr>
                            <td>
                                <strong>{{ $invoice->invoice_number }}</strong>
                                <br><small class="text-muted">{{ $invoice->invoice_date->format('d/m/Y') }}</small>
                            </td>
                            <td>
                                @if($invoice->customer)
                                    <a href="{{ route('customers.show', $invoice->customer) }}">
                                        {{ $invoice->customer->name }}
                                    </a>
                                    @if($invoice->customer->phone)
                                        <br><small class="text-muted">{{ $invoice->customer->phone }}</small>
                                    @endif
                                @else
                                    <span class="text-muted">No customer</span>
                                @endif
                            </td>
                            <td>
                                {{ $invoice->service_period ?? 'Monthly Service' }}
                            </td>
                            <td>
                                <strong>Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</strong>
                                @if($invoice->total_paid > 0)
                                    <br><small class="text-success">
                                        Paid: Rp {{ number_format($invoice->total_paid, 0, ',', '.') }}
                                    </small>
                                @endif
                            </td>
                            <td>
                                {{ $invoice->due_date->format('d/m/Y') }}
                                @if($invoice->due_date->isPast() && $invoice->status !== 'paid')
                                    <br><span class="badge badge-danger">Overdue</span>
                                @elseif($invoice->due_date->diffInDays(now()) <= 3 && $invoice->status !== 'paid')
                                    <br><span class="badge badge-warning">Due Soon</span>
                                @endif
                            </td>
                            <td>
                                @php
                                    $statusClass = [
                                        'unpaid' => 'warning',
                                        'paid' => 'success',
                                        'overdue' => 'danger'
                                    ];
                                @endphp
                                <span class="badge badge-{{ $statusClass[$invoice->status] ?? 'secondary' }}">
                                    {{ ucfirst($invoice->status) }}
                                </span>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="{{ route('invoices.show', $invoice) }}" 
                                       class="btn btn-sm btn-info" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    
                                    <a href="{{ route('invoices.preview', $invoice) }}" 
                                       class="btn btn-sm btn-secondary" title="Preview Invoice" target="_blank">
                                        <i class="fas fa-search"></i>
                                    </a>
                                    
                                    <a href="{{ route('invoices.download', $invoice) }}" 
                                       class="btn btn-sm btn-success" title="Download PDF" target="_blank">
                                        <i class="fas fa-download"></i>
                                    </a>
                                    
                                    @if($invoice->status !== 'paid')
                                        <a href="{{ route('invoices.edit', $invoice) }}" 
                                           class="btn btn-sm btn-warning" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    @endif
                                    
                                    @if($invoice->status === 'pending')
                                        <a href="{{ route('payments.create', ['invoice' => $invoice->id]) }}" 
                                           class="btn btn-sm btn-primary" title="Add Payment">
                                            <i class="fas fa-money-bill"></i>
                                        </a>
                                    @endif
                                    
                                    @if($invoice->status !== 'paid')
                                        <form action="{{ route('invoices.send-reminder', $invoice) }}" 
                                              method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-info" 
                                                    title="Send Reminder"
                                                    onclick="return confirm('Send payment reminder to customer?')">
                                                <i class="fas fa-bell"></i>
                                            </button>
                                        </form>
                                    @endif
                                    
                                    @if($invoice->status === 'draft')
                                        <form action="{{ route('invoices.destroy', $invoice) }}" 
                                              method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" 
                                                    title="Delete"
                                                    onclick="return confirm('Are you sure you want to delete this invoice?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center">No invoices found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-center">
                {{ $invoices->withQueryString()->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
