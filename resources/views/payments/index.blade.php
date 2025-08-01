@extends('layouts.admin')

@section('title', 'Payments')

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

.amount-display {
    font-family: 'Courier New', monospace;
    font-weight: bold;
}

.payment-method-badge {
    font-size: 0.8em;
    font-weight: 600;
    padding: 0.4em 0.6em;
}

.reference-code {
    font-family: 'Courier New', monospace;
    background-color: #f8f9fa;
    padding: 0.2em 0.4em;
    border-radius: 0.25rem;
    border: 1px solid #dee2e6;
    font-size: 0.85em;
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
        <h1 class="h3 mb-0 text-gray-800">Payment Management</h1>
        <div>
            <a href="{{ route('payments.export') }}" class="btn btn-sm btn-success shadow-sm mr-2">
                <i class="fas fa-file-excel fa-sm text-white-50"></i> Export Excel
            </a>
            <a href="{{ route('payments.report') }}" class="btn btn-sm btn-info shadow-sm mr-2">
                <i class="fas fa-chart-bar fa-sm text-white-50"></i> Payment Report
            </a>
            <a href="{{ route('payments.create') }}" class="btn btn-sm btn-primary shadow-sm">
                <i class="fas fa-plus fa-sm text-white-50"></i> Add Payment
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
            <form method="GET" action="{{ route('payments.index') }}">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="search">Search</label>
                            <input type="text" class="form-control" name="search" 
                                   value="{{ request('search') }}" 
                                   placeholder="Customer name, invoice number...">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="method">Payment Method</label>
                            <select class="form-control" name="method">
                                <option value="">All Methods</option>
                                <option value="cash" {{ request('method') == 'cash' ? 'selected' : '' }}>Cash</option>
                                <option value="bank_transfer" {{ request('method') == 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                                <option value="e_wallet" {{ request('method') == 'e_wallet' ? 'selected' : '' }}>E-Wallet</option>
                                <option value="credit_card" {{ request('method') == 'credit_card' ? 'selected' : '' }}>Credit Card</option>
                                <option value="other" {{ request('method') == 'other' ? 'selected' : '' }}>Other</option>
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
                            <label for="amount_min">Min Amount</label>
                            <input type="number" class="form-control" name="amount_min" 
                                   value="{{ request('amount_min') }}" step="0.01">
                        </div>
                    </div>
                    <div class="col-md-1">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <div class="d-flex">
                                <button type="submit" class="btn btn-sm btn-primary mr-1">
                                    <i class="fas fa-search"></i>
                                </button>
                                <a href="{{ route('payments.index') }}" class="btn btn-sm btn-secondary">
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
                                Total Payments</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $totalCount ?? $payments->total() }}</div>
                            <div class="text-xs text-muted mt-1">All time records</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-receipt fa-2x text-gray-300"></i>
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
                                @if($todayTotal > 0)
                                    Today's Total
                                @else
                                    Last 7 Days
                                @endif
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                @if($todayTotal > 0)
                                    Rp {{ number_format($todayTotal, 0, ',', '.') }}
                                @else
                                    Rp {{ number_format($recentTotal ?? 0, 0, ',', '.') }}
                                @endif
                            </div>
                            <div class="text-xs text-muted mt-1">
                                @if($todayTotal > 0)
                                    {{ now()->format('d M Y') }}
                                @else
                                    Recent activity
                                @endif
                            </div>
                        </div>
                        <div class="col-auto">
                            @if($todayTotal > 0)
                                <i class="fas fa-calendar-day fa-2x text-gray-300"></i>
                            @else
                                <i class="fas fa-calendar-week fa-2x text-gray-300"></i>
                            @endif
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
                                @if($monthTotal > 0)
                                    This Month
                                @else
                                    Last 30 Days
                                @endif
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                @if($monthTotal > 0)
                                    Rp {{ number_format($monthTotal, 0, ',', '.') }}
                                @else
                                    Rp {{ number_format($last30DaysTotal ?? 0, 0, ',', '.') }}
                                @endif
                            </div>
                            <div class="text-xs text-muted mt-1">
                                @if($monthTotal > 0)
                                    {{ now()->format('F Y') }}
                                @else
                                    Rolling 30 days
                                @endif
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar fa-2x text-gray-300"></i>
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
                                Average Payment</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                Rp {{ number_format($avgAmount ?? 0, 0, ',', '.') }}
                            </div>
                            <div class="text-xs text-muted mt-1">Per transaction</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chart-line fa-2x text-gray-300"></i>
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
                <i class="fas fa-credit-card mr-2"></i>Payment List
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
                    <span id="selectedCount">0</span> payment(s) selected
                </div>
                <button type="button" id="bulkDeleteBtn" class="btn btn-danger btn-sm mr-2">
                    <i class="fas fa-trash"></i> Delete Selected
                </button>
                <button type="button" id="bulkExportBtn" class="btn btn-success btn-sm mr-2">
                    <i class="fas fa-file-excel"></i> Export Selected
                </button>
                <button type="button" id="bulkPrintBtn" class="btn btn-info btn-sm mr-2">
                    <i class="fas fa-print"></i> Print Receipts
                </button>
                <button type="button" id="clearSelectionBtn" class="btn btn-secondary btn-sm">
                    <i class="fas fa-times"></i> Clear
                </button>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="paymentsTable" width="100%" cellspacing="0">
                    <thead class="thead-light">
                        <tr>
                            <th width="30">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="selectAll">
                                    <label class="custom-control-label" for="selectAll"></label>
                                </div>
                            </th>
                            <th>Date</th>
                            <th>Customer</th>
                            <th>Invoice</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Reference</th>
                            <th width="200">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payments as $payment)
                        <tr>
                            <td>
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" 
                                           id="payment_{{ $payment->id }}" 
                                           name="selected_payments[]" 
                                           value="{{ $payment->id }}">
                                    <label class="custom-control-label" for="payment_{{ $payment->id }}"></label>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div>
                                        <strong>{{ $payment->payment_date->format('d/m/Y') }}</strong>
                                        <div class="mt-1">
                                            <small class="text-muted">
                                                <i class="fas fa-clock"></i> {{ $payment->payment_date->format('H:i') }}
                                            </small>
                                        </div>
                                    </div>
                                </div>
                                @if($payment->created_at->diffInDays(now()) <= 7)
                                    <div class="mt-1">
                                        <span class="badge badge-success badge-sm">
                                            <i class="fas fa-star"></i> Recent
                                        </span>
                                    </div>
                                @endif
                            </td>
                            <td>
                                @if($payment->customer)
                                    <div class="mb-1">
                                        <a href="{{ route('customers.show', $payment->customer) }}" class="text-decoration-none">
                                            <strong>{{ $payment->customer->name }}</strong>
                                        </a>
                                        <button class="btn btn-link btn-sm p-0 ml-1 copy-btn" 
                                                data-copy="{{ $payment->customer->name }}" 
                                                title="Copy customer name">
                                            <i class="fas fa-copy text-muted"></i>
                                        </button>
                                    </div>
                                    @if($payment->customer->phone)
                                        <div>
                                            <small class="text-muted">
                                                <i class="fas fa-phone"></i> {{ $payment->customer->phone }}
                                            </small>
                                            <button class="btn btn-link btn-sm p-0 ml-1 copy-btn" 
                                                    data-copy="{{ $payment->customer->phone }}" 
                                                    title="Copy phone">
                                                <i class="fas fa-copy text-muted"></i>
                                            </button>
                                        </div>
                                    @endif
                                @else
                                    <span class="text-muted">
                                        <i class="fas fa-user-slash"></i> No customer
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if($payment->invoice)
                                    <div class="mb-1">
                                        <a href="{{ route('invoices.show', $payment->invoice) }}" class="text-decoration-none">
                                            <span class="badge badge-primary badge-lg">{{ $payment->invoice->invoice_number }}</span>
                                        </a>
                                        <button class="btn btn-link btn-sm p-0 ml-1 copy-btn" 
                                                data-copy="{{ $payment->invoice->invoice_number }}" 
                                                title="Copy invoice number">
                                            <i class="fas fa-copy text-muted"></i>
                                        </button>
                                    </div>
                                    @if($payment->invoice->description)
                                        <div class="mt-1">
                                            <small class="text-muted">{{ Str::limit($payment->invoice->description, 30) }}</small>
                                        </div>
                                    @endif
                                @else
                                    <span class="text-muted">
                                        <i class="fas fa-file-slash"></i> No invoice
                                    </span>
                                @endif
                            </td>
                            <td>
                                <div class="amount-display text-success">
                                    <strong>Rp {{ number_format($payment->amount, 0, ',', '.') }}</strong>
                                    <button class="btn btn-link btn-sm p-0 ml-1 copy-btn" 
                                            data-copy="{{ $payment->amount }}" 
                                            title="Copy amount">
                                        <i class="fas fa-copy text-muted"></i>
                                    </button>
                                </div>
                                @if($payment->amount >= 1000000)
                                    <div class="mt-1">
                                        <span class="badge badge-warning badge-sm">
                                            <i class="fas fa-exclamation-triangle"></i> High Amount
                                        </span>
                                    </div>
                                @endif
                            </td>
                            <td class="text-center">
                                @php
                                    $methodConfig = [
                                        'cash' => ['class' => 'success', 'icon' => 'fas fa-money-bill-wave'],
                                        'bank_transfer' => ['class' => 'primary', 'icon' => 'fas fa-university'],
                                        'e_wallet' => ['class' => 'info', 'icon' => 'fas fa-mobile-alt'],
                                        'credit_card' => ['class' => 'warning', 'icon' => 'fas fa-credit-card'],
                                        'other' => ['class' => 'secondary', 'icon' => 'fas fa-question-circle']
                                    ];
                                    $config = $methodConfig[$payment->payment_method] ?? $methodConfig['other'];
                                @endphp
                                <span class="badge badge-{{ $config['class'] }} badge-lg payment-method-badge">
                                    <i class="{{ $config['icon'] }}"></i>
                                    {{ ucwords(str_replace('_', ' ', $payment->payment_method)) }}
                                </span>
                            </td>
                            <td>
                                @if($payment->reference_number)
                                    <div class="mb-1">
                                        <span class="reference-code">{{ $payment->reference_number }}</span>
                                        <button class="btn btn-link btn-sm p-0 ml-1 copy-btn" 
                                                data-copy="{{ $payment->reference_number }}" 
                                                title="Copy reference">
                                            <i class="fas fa-copy text-muted"></i>
                                        </button>
                                    </div>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                                @if($payment->notes)
                                    <div class="mt-1">
                                        <small class="text-muted" title="{{ $payment->notes }}">
                                            <i class="fas fa-sticky-note"></i> {{ Str::limit($payment->notes, 20) }}
                                        </small>
                                    </div>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="{{ route('payments.show', $payment) }}" 
                                       class="btn btn-sm btn-info" 
                                       title="View Payment"
                                       data-toggle="tooltip">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('payments.edit', $payment) }}" 
                                       class="btn btn-sm btn-warning" 
                                       title="Edit Payment"
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
                                            <!-- Print Receipt -->
                                            <a href="{{ route('payments.receipt', $payment) }}" 
                                               class="dropdown-item" target="_blank">
                                                <i class="fas fa-print text-success"></i> Print Receipt
                                            </a>
                                            
                                            <!-- Send Receipt Email -->
                                            @if($payment->customer && $payment->customer->email)
                                                <form action="{{ route('payments.send-receipt', $payment) }}" 
                                                      method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="dropdown-item" 
                                                            onclick="return confirm('Send receipt to customer email?')">
                                                        <i class="fas fa-envelope text-primary"></i> Email Receipt
                                                    </button>
                                                </form>
                                            @endif
                                            
                                            <!-- Download Receipt PDF -->
                                            <a href="{{ route('payments.download-receipt', $payment) }}" 
                                               class="dropdown-item">
                                                <i class="fas fa-download text-info"></i> Download PDF
                                            </a>
                                            
                                            <div class="dropdown-divider"></div>
                                            
                                            <!-- Mark as Verified -->
                                            @if(!$payment->is_verified)
                                                <form action="{{ route('payments.verify', $payment) }}" 
                                                      method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="dropdown-item" 
                                                            onclick="return confirm('Mark this payment as verified?')">
                                                        <i class="fas fa-check-circle text-success"></i> Mark Verified
                                                    </button>
                                                </form>
                                            @endif
                                            
                                            <div class="dropdown-divider"></div>
                                            
                                            <!-- Delete -->
                                            <form action="{{ route('payments.destroy', $payment) }}" 
                                                  method="POST" class="d-inline delete-form">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="dropdown-item text-danger" 
                                                        title="Delete Payment"
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
                            <td colspan="8" class="text-center py-4">
                                <div class="text-muted">
                                    <i class="fas fa-inbox fa-3x mb-3"></i>
                                    <p class="mb-0">No payments found.</p>
                                    <small>Add your first payment or adjust the search filters.</small>
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
                    Showing {{ $payments->firstItem() ?? 0 }} to {{ $payments->lastItem() ?? 0 }} 
                    of {{ $payments->total() }} payments
                </div>
                <div>
                    {{ $payments->withQueryString()->links() }}
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
    $('#paymentsTable').DataTable({
        "responsive": true,
        "lengthChange": true,
        "autoWidth": false,
        "order": [[ 1, "desc" ]], // Order by date descending
        "pageLength": 25,
        "language": {
            "search": "Search Payments:",
            "lengthMenu": "Show _MENU_ payments per page",
            "info": "Showing _START_ to _END_ of _TOTAL_ payments",
            "infoEmpty": "No payments found",
            "infoFiltered": "(filtered from _MAX_ total payments)",
            "zeroRecords": "No matching payments found",
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
        const checkedBoxes = $('input[name="selected_payments[]"]:checked');
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
        $('input[name="selected_payments[]"]').prop('checked', this.checked);
        toggleBulkActions();
    });

    // Handle individual checkboxes
    $(document).on('change', 'input[name="selected_payments[]"]', function() {
        toggleBulkActions();
        // Update select all checkbox state
        const total = $('input[name="selected_payments[]"]').length;
        const checked = $('input[name="selected_payments[]"]:checked').length;
        $('#selectAll').prop('indeterminate', checked > 0 && checked < total);
        $('#selectAll').prop('checked', checked === total);
    });

    // Clear selection
    $('#clearSelectionBtn').click(function() {
        $('input[name="selected_payments[]"], #selectAll').prop('checked', false);
        toggleBulkActions();
    });

    // Bulk delete functionality
    $('#bulkDeleteBtn').click(function() {
        const selectedIds = [];
        $('input[name="selected_payments[]"]:checked').each(function() {
            selectedIds.push($(this).val());
        });

        if (selectedIds.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'No Selection',
                text: 'Please select payments to delete.',
                confirmButtonClass: 'btn btn-primary'
            });
            return;
        }

        Swal.fire({
            title: 'Delete Selected Payments?',
            text: `You are about to delete ${selectedIds.length} payment(s). This action cannot be undone!`,
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
                    action: '{{ route("payments.bulk-delete") }}'
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
                        name: 'selected_payments[]',
                        value: id
                    }));
                });
                
                $('body').append(form);
                form.submit();
            }
        });
    });

    // Bulk export functionality
    $('#bulkExportBtn').click(function() {
        const selectedIds = [];
        $('input[name="selected_payments[]"]:checked').each(function() {
            selectedIds.push($(this).val());
        });

        if (selectedIds.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'No Selection',
                text: 'Please select payments to export.',
                confirmButtonClass: 'btn btn-primary'
            });
            return;
        }

        // Create form for bulk export
        const form = $('<form>', {
            method: 'POST',
            action: '{{ route("payments.bulk-export") }}'
        });
        
        form.append($('<input>', {
            type: 'hidden',
            name: '_token',
            value: '{{ csrf_token() }}'
        }));
        
        selectedIds.forEach(id => {
            form.append($('<input>', {
                type: 'hidden',
                name: 'selected_payments[]',
                value: id
            }));
        });
        
        $('body').append(form);
        form.submit();
    });

    // Bulk print functionality
    $('#bulkPrintBtn').click(function() {
        const selectedIds = [];
        $('input[name="selected_payments[]"]:checked').each(function() {
            selectedIds.push($(this).val());
        });

        if (selectedIds.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'No Selection',
                text: 'Please select payments to print receipts.',
                confirmButtonClass: 'btn btn-primary'
            });
            return;
        }

        // Open bulk print in new window
        const url = '{{ route("payments.bulk-print") }}?' + 
                   selectedIds.map(id => `payments[]=${id}`).join('&');
        window.open(url, '_blank');
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

    // Make copyToClipboard globally accessible for all contexts
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
            title: 'Delete Payment?',
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

    // Amount formatting on hover
    $('.amount-display').hover(
        function() {
            const amount = $(this).find('strong').text().replace(/[^\d]/g, '');
            $(this).attr('title', `Exact amount: Rp ${parseInt(amount).toLocaleString('id-ID')}`);
        }
    );

    // Enhanced search functionality
    let searchTimeout;
    $('input[name="search"]').on('input', function() {
        clearTimeout(searchTimeout);
        const query = $(this).val();
        
        if (query.length >= 3) {
            searchTimeout = setTimeout(function() {
                // Perform live search (optional - requires AJAX endpoint)
                // performLiveSearch(query);
            }, 500);
        }
    });

    // Date range validation
    $('input[name="date_from"], input[name="date_to"]').change(function() {
        const dateFrom = $('input[name="date_from"]').val();
        const dateTo = $('input[name="date_to"]').val();
        
        if (dateFrom && dateTo && new Date(dateFrom) > new Date(dateTo)) {
            toastr.warning('Date From cannot be later than Date To', 'Invalid Date Range');
            $(this).val('');
        }
    });

    // Keyboard shortcuts
    $(document).keydown(function(e) {
        // Ctrl+A to select all
        if (e.ctrlKey && e.keyCode === 65) {
            e.preventDefault();
            $('#selectAll').prop('checked', true).trigger('change');
        }
        
        // Escape to clear selection
        if (e.keyCode === 27) {
            $('#clearSelectionBtn').click();
        }
        
        // Ctrl+N for new payment
        if (e.ctrlKey && e.keyCode === 78) {
            e.preventDefault();
            window.location.href = '{{ route("payments.create") }}';
        }
    });

    // Enhanced payment method badges with icons
    $('.payment-method-badge').each(function() {
        const method = $(this).text().toLowerCase();
        const $this = $(this);
        
        // Add animation on hover
        $this.hover(
            function() { $(this).addClass('shadow-sm'); },
            function() { $(this).removeClass('shadow-sm'); }
        );
    });

    // Reference number validation display
    $('.reference-code').each(function() {
        const ref = $(this).text();
        if (ref.length > 10) {
            $(this).attr('title', `Full reference: ${ref}`);
        }
    });
});
</script>
@endpush
