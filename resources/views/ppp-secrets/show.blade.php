@extends('layouts.admin')

@section('main-content')
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">{{ __('PPP Secret Details') }} - {{ $pppSecret->username }}</h1>
        <div>
            <a href="{{ route('ppp-secrets.edit', $pppSecret) }}" class="d-none d-sm-inline-block btn btn-sm btn-warning shadow-sm">
                <i class="fas fa-edit fa-sm text-white-50"></i> {{ __('Edit Secret') }}
            </a>
            <a href="{{ route('ppp-secrets.index') }}" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
                <i class="fas fa-arrow-left fa-sm text-white-50"></i> {{ __('Back to PPP Secrets') }}
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

    @if (session('warning'))
        <div class="alert alert-warning border-left-warning alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle mr-2"></i>{{ session('warning') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if (session('info'))
        <div class="alert alert-info border-left-info alert-dismissible fade show" role="alert">
            <i class="fas fa-info-circle mr-2"></i>{{ session('info') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger border-left-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle mr-2"></i>{{ session('error') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <!-- Content Row -->
    <div class="row">
        <div class="col-lg-8">
            <!-- PPP Secret Information -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">{{ __('PPP Secret Information') }}</h6>
                    <span class="badge badge-{{ $pppSecret->is_active ? 'success' : 'danger' }}">
                        {{ $pppSecret->is_active ? __('Active') : __('Inactive') }}
                    </span>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="40%">{{ __('Username') }}</th>
                                    <td><code>{{ $pppSecret->username }}</code></td>
                                </tr>
                                <tr>
                                    <th>{{ __('Password') }}</th>
                                    <td>
                                        <span id="password-hidden">••••••••</span>
                                        <span id="password-visible" style="display: none;"><code>{{ $pppSecret->password }}</code></span>
                                        <button type="button" class="btn btn-sm btn-outline-secondary ml-2" id="togglePassword">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <th>{{ __('Service Type') }}</th>
                                    <td><span class="badge badge-info">{{ strtoupper($pppSecret->service) }}</span></td>
                                </tr>
                                <tr>
                                    <th>{{ __('Customer') }}</th>
                                    <td>
                                        @if($pppSecret->customer)
                                            <a href="{{ route('customers.show', $pppSecret->customer) }}">{{ $pppSecret->customer->name }}</a>
                                            <br><small class="text-muted">{{ $pppSecret->customer->phone }}</small>
                                        @else
                                            <span class="text-muted">{{ __('No Customer Assigned') }}</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>{{ __('PPP Profile') }}</th>
                                    <td>
                                        @if($pppSecret->pppProfile)
                                            <a href="{{ route('ppp-profiles.show', $pppSecret->pppProfile) }}">{{ $pppSecret->pppProfile->name }}</a>
                                            <br><small class="text-muted">{{ $pppSecret->pppProfile->formatted_rate_limit }}</small>
                                        @else
                                            <span class="text-muted">{{ __('No Profile Assigned') }}</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="40%">{{ __('Local Address') }}</th>
                                    <td>
                                        @php
                                            $effectiveLocalAddress = $pppSecret->effective_local_address;
                                            $isFromProfile = !$pppSecret->local_address && $pppSecret->pppProfile && $pppSecret->pppProfile->local_address;
                                        @endphp
                                        @if($effectiveLocalAddress)
                                            <code>{{ $effectiveLocalAddress }}</code>
                                            @if($isFromProfile)
                                                <small class="text-muted">(from profile)</small>
                                            @endif
                                        @else
                                            <span class="text-muted">{{ __('Not configured') }}</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>{{ __('Remote Address') }}</th>
                                    <td>
                                        @php
                                            $effectiveRemoteAddress = $pppSecret->effective_remote_address;
                                            $isFromProfile = !$pppSecret->remote_address && $pppSecret->pppProfile && $pppSecret->pppProfile->remote_address;
                                        @endphp
                                        @if($effectiveRemoteAddress)
                                            <code>{{ $effectiveRemoteAddress }}</code>
                                            @if($isFromProfile)
                                                <small class="text-muted">(from profile)</small>
                                            @endif
                                        @else
                                            <span class="text-muted">{{ __('Not configured') }}</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>{{ __('Installation Date') }}</th>
                                    <td>{{ $pppSecret->installation_date ? $pppSecret->installation_date->format('d M Y') : __('Not set') }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('Due Date') }}</th>
                                    <td>
                                        @if($pppSecret->due_date)
                                            {{ $pppSecret->due_date->format('d M Y') }}
                                            @if($pppSecret->isOverdue())
                                                <span class="badge badge-danger ml-2">{{ __('Overdue') }}</span>
                                            @endif
                                        @else
                                            {{ __('Not set') }}
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>{{ __('Created') }}</th>
                                    <td>{{ $pppSecret->created_at->format('d M Y H:i') }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    @if($pppSecret->comment)
                        <div class="mt-3">
                            <h6 class="text-primary">{{ __('Comment') }}</h6>
                            <div class="alert alert-info">
                                {{ $pppSecret->comment }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Connection Status -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">{{ __('Real-time Connection Status') }}</h6>
                    <div>
                        @if($realTimeStatus && isset($realTimeStatus['is_fallback']) && $realTimeStatus['is_fallback'])
                            <span class="badge badge-warning mr-2">
                                <i class="fas fa-exclamation-triangle"></i> Fallback Data
                            </span>
                        @elseif($realTimeStatus && $realTimeStatus['status'] === 'unknown')
                            <span class="badge badge-warning mr-2">
                                <i class="fas fa-question-circle"></i> Router Slow
                            </span>
                        @elseif($realTimeStatus && $realTimeStatus['status'] === 'timeout')
                            <span class="badge badge-danger mr-2">
                                <i class="fas fa-clock"></i> Timeout
                            </span>
                        @elseif($realTimeStatus)
                            <span class="badge badge-success mr-2">
                                <i class="fas fa-check"></i> Live Data
                            </span>
                        @endif
                        
                        <div class="btn-group" role="group">
                            <a href="{{ route('ppp-secrets.show', $pppSecret) }}?force_refresh=1" 
                               class="btn btn-sm btn-outline-primary"
                               title="Quick refresh (3s timeout)">
                                <i class="fas fa-sync-alt"></i>
                            </a>
                            <a href="{{ route('ppp-secrets.show', $pppSecret) }}?force_refresh=1&aggressive=1" 
                               class="btn btn-sm btn-outline-success"
                               title="Deep refresh (extended timeout)">
                                <i class="fas fa-search-plus"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="text-center">
                                @if($realTimeStatus && $realTimeStatus['status'] === 'connected')
                                    <i class="fas fa-wifi fa-3x text-success mb-2"></i>
                                    <h6 class="text-success">{{ __('Connected') }}</h6>
                                    @if(isset($realTimeStatus['address']))
                                        <small class="text-muted">IP: {{ $realTimeStatus['address'] }}</small>
                                    @endif
                                @elseif($realTimeStatus && $realTimeStatus['status'] === 'disconnected')
                                    <i class="fas fa-wifi fa-3x text-gray-300 mb-2"></i>
                                    <h6 class="text-muted">{{ __('Disconnected') }}</h6>
                                @elseif($realTimeStatus && $realTimeStatus['status'] === 'timeout')
                                    <i class="fas fa-hourglass-half fa-3x text-warning mb-2"></i>
                                    <h6 class="text-warning">{{ __('Connection Timeout') }}</h6>
                                    <small class="text-muted">{{ __('MikroTik router is slow to respond') }}</small>
                                @elseif($realTimeStatus && ($realTimeStatus['status'] === 'unknown' || (isset($realTimeStatus['is_fallback']) && $realTimeStatus['is_fallback'])))
                                    <i class="fas fa-question-circle fa-3x text-warning mb-2"></i>
                                    <h6 class="text-warning">{{ __('Status Unknown') }}</h6>
                                    <small class="text-muted">{{ __('Router performance issues - showing approximate status') }}</small>
                                @else
                                    <i class="fas fa-exclamation-triangle fa-3x text-warning mb-2"></i>
                                    <h6 class="text-warning">{{ __('Status Unknown') }}</h6>
                                    <small class="text-muted">{{ __('Cannot connect to MikroTik') }}</small>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-8">
                            @if($realTimeStatus && $realTimeStatus['status'] === 'connected')
                                <table class="table table-sm table-borderless">
                                    @if(isset($realTimeStatus['address']))
                                    <tr>
                                        <th>{{ __('Current IP Address') }}</th>
                                        <td><code>{{ $realTimeStatus['address'] }}</code></td>
                                    </tr>
                                    @endif
                                    @if(isset($realTimeStatus['uptime']))
                                    <tr>
                                        <th>{{ __('Session Uptime') }}</th>
                                        <td>{{ $realTimeStatus['uptime'] }}</td>
                                    </tr>
                                    @endif
                                    @if(isset($realTimeStatus['caller_id']))
                                    <tr>
                                        <th>{{ __('Caller ID') }}</th>
                                        <td>{{ $realTimeStatus['caller_id'] }}</td>
                                    </tr>
                                    @endif
                                    @if(isset($realTimeStatus['service']))
                                    <tr>
                                        <th>{{ __('Service Type') }}</th>
                                        <td><span class="badge badge-info">{{ strtoupper($realTimeStatus['service']) }}</span></td>
                                    </tr>
                                    @endif
                                </table>
                            @elseif($realTimeStatus && ($realTimeStatus['status'] === 'timeout' || $realTimeStatus['status'] === 'unknown'))
                                <div class="alert alert-warning alert-sm mb-3">
                                    <i class="fas fa-exclamation-triangle mr-1"></i>
                                    <strong>{{ __('Real-time data unavailable') }}</strong><br>
                                    {{ $realTimeStatus['message'] ?? __('MikroTik router is responding slowly. Showing last known connection data instead.') }}
                                    @if(isset($realTimeStatus['suggestion']))
                                        <br><small>{{ $realTimeStatus['suggestion'] }}</small>
                                    @endif
                                    @if(isset($realTimeStatus['is_fallback']) && $realTimeStatus['is_fallback'])
                                        <br><small class="text-info"><i class="fas fa-info-circle"></i> Try the "Deep Refresh" button for more accurate data (may take longer).</small>
                                    @endif
                                </div>
                                @if($pppSecret->latestUsageLog)
                                    <table class="table table-sm table-borderless">
                                        <tr>
                                            <th>{{ __('Last Connection') }}</th>
                                            <td>{{ $pppSecret->latestUsageLog->connected_at ? $pppSecret->latestUsageLog->connected_at->format('d M Y H:i:s') : __('Never') }}</td>
                                        </tr>
                                        <tr>
                                            <th>{{ __('Last Disconnection') }}</th>
                                            <td>{{ $pppSecret->latestUsageLog->disconnected_at ? $pppSecret->latestUsageLog->disconnected_at->format('d M Y H:i:s') : __('Still connected') }}</td>
                                        </tr>
                                        <tr>
                                            <th>{{ __('Session Duration') }}</th>
                                            <td>{{ $pppSecret->latestUsageLog->session_duration ?: __('N/A') }}</td>
                                        </tr>
                                        <tr>
                                            <th>{{ __('Data Source') }}</th>
                                            <td><small class="text-muted">{{ __('Historical data (last updated: ') }}{{ $pppSecret->latestUsageLog->updated_at->format('d M Y H:i') }})</small></td>
                                        </tr>
                                    </table>
                                @else
                                    <div class="text-center py-2">
                                        <p class="text-muted">{{ __('No historical connection data available.') }}</p>
                                        <button class="btn btn-sm btn-outline-primary" onclick="retryConnection()">
                                            <i class="fas fa-sync-alt"></i> {{ __('Retry Connection') }}
                                        </button>
                                    </div>
                                @endif
                            @elseif($pppSecret->latestUsageLog)
                                <table class="table table-sm table-borderless">
                                    <tr>
                                        <th>{{ __('Last Connection') }}</th>
                                        <td>{{ $pppSecret->latestUsageLog->connected_at ? $pppSecret->latestUsageLog->connected_at->format('d M Y H:i:s') : __('Never') }}</td>
                                    </tr>
                                    <tr>
                                        <th>{{ __('Last Disconnection') }}</th>
                                        <td>{{ $pppSecret->latestUsageLog->disconnected_at ? $pppSecret->latestUsageLog->disconnected_at->format('d M Y H:i:s') : __('Still connected') }}</td>
                                    </tr>
                                    <tr>
                                        <th>{{ __('Session Duration') }}</th>
                                        <td>{{ $pppSecret->latestUsageLog->session_duration ?: __('N/A') }}</td>
                                    </tr>
                                </table>
                            @else
                                <div class="text-center py-2">
                                    <p class="text-muted">{{ __('No connection history found.') }}</p>
                                    @if(!$realTimeStatus)
                                        <button class="btn btn-sm btn-outline-primary" onclick="window.location.reload()">
                                            <i class="fas fa-sync-alt"></i> {{ __('Refresh Status') }}
                                        </button>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                    @if($realTimeStatus)
                        <div class="text-right mt-2">
                            <small class="text-muted">
                                {{ __('Last updated: ') }}{{ now()->format('H:i:s') }}
                                @if(isset($realTimeStatus['cached_at']))
                                    ({{ __('cached data') }})
                                @endif
                                @if(isset($realTimeStatus['method']))
                                    - {{ $realTimeStatus['method'] }}
                                @endif
                                @if(isset($realTimeStatus['query_time_ms']))
                                    - {{ $realTimeStatus['query_time_ms'] }}ms
                                @endif
                            </small>
                            
                            @if($realTimeStatus['status'] === 'connected')
                                <span class="badge badge-success ml-2">
                                    <i class="fas fa-check-circle mr-1"></i>{{ __('Live') }}
                                </span>
                            @elseif($realTimeStatus['status'] === 'timeout' || $realTimeStatus['status'] === 'unknown')
                                <span class="badge badge-warning ml-2">
                                    <i class="fas fa-exclamation-triangle mr-1"></i>{{ __('Degraded') }}
                                </span>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            <!-- Recent Invoices -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">{{ __('Recent Invoices') }}</h6>
                    <a href="{{ route('invoices.create', ['ppp_secret_id' => $pppSecret->id]) }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus"></i> {{ __('Create Invoice') }}
                    </a>
                </div>
                <div class="card-body">
                    @if($pppSecret->invoices->count() > 0)
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
                                    @foreach($pppSecret->invoices->take(5) as $invoice)
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
                        @if($pppSecret->invoices->count() > 5)
                            <div class="text-center mt-3">
                                <a href="{{ route('invoices.index', ['ppp_secret_id' => $pppSecret->id]) }}" class="btn btn-outline-primary">
                                    {{ __('View All Invoices') }} ({{ $pppSecret->invoices->count() }})
                                </a>
                            </div>
                        @endif
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-file-invoice fa-3x text-gray-300 mb-3"></i>
                            <p class="text-muted">{{ __('No invoices found for this PPP secret.') }}</p>
                            <a href="{{ route('invoices.create', ['ppp_secret_id' => $pppSecret->id]) }}" class="btn btn-primary">
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
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">{{ __('Connection Status') }}</div>
                            <div class="h6 mb-0 font-weight-bold text-gray-800">
                                @if($realTimeStatus && $realTimeStatus['status'] === 'connected')
                                    <span class="text-success">{{ __('Online') }}</span>
                                @elseif($realTimeStatus && $realTimeStatus['status'] === 'disconnected')
                                    <span class="text-muted">{{ __('Offline') }}</span>
                                @else
                                    <span class="text-warning">{{ __('Unknown') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-auto">
                            @if($realTimeStatus && $realTimeStatus['status'] === 'connected')
                                <i class="fas fa-wifi fa-2x text-success"></i>
                            @elseif($realTimeStatus && $realTimeStatus['status'] === 'disconnected')
                                <i class="fas fa-wifi fa-2x text-gray-300"></i>
                            @else
                                <i class="fas fa-exclamation-triangle fa-2x text-warning"></i>
                            @endif
                        </div>
                    </div>

                    <div class="row no-gutters align-items-center mb-3">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">{{ __('Total Invoices') }}</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $pppSecret->invoices->count() }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-file-invoice fa-2x text-gray-300"></i>
                        </div>
                    </div>

                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">{{ __('Unpaid Invoices') }}</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $pppSecret->unpaidInvoices->count() }}</div>
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
                        <a href="{{ route('invoices.create', ['ppp_secret_id' => $pppSecret->id]) }}" class="btn btn-outline-primary btn-block">
                            <i class="fas fa-file-invoice"></i> {{ __('Create Invoice') }}
                        </a>
                        
                        <a href="{{ route('ppp-secrets.edit', $pppSecret) }}" class="btn btn-outline-warning btn-block">
                            <i class="fas fa-edit"></i> {{ __('Edit Secret') }}
                        </a>

                        @if($pppSecret->customer)
                        <a href="{{ route('customers.show', $pppSecret->customer) }}" class="btn btn-outline-info btn-block">
                            <i class="fas fa-user"></i> {{ __('View Customer') }}
                        </a>
                        @endif

                        @if($pppSecret->pppProfile)
                        <a href="{{ route('ppp-profiles.show', $pppSecret->pppProfile) }}" class="btn btn-outline-secondary btn-block">
                            <i class="fas fa-cogs"></i> {{ __('View Profile') }}
                        </a>
                        @endif

                        {{-- Disconnect Session Button --}}
                        @if($realTimeStatus && $realTimeStatus['status'] === 'connected')
                            <form action="{{ route('ppp-secrets.disconnect', $pppSecret) }}" method="POST" onsubmit="return confirm('{{ __('Are you sure you want to disconnect this user session? This will immediately terminate their internet connection.') }}')">
                                @csrf
                                <button type="submit" class="btn btn-outline-danger btn-block">
                                    <i class="fas fa-unlink"></i> {{ __('Disconnect Session') }}
                                </button>
                            </form>
                        @elseif($realTimeStatus && $realTimeStatus['status'] === 'disconnected')
                            <button type="button" class="btn btn-outline-secondary btn-block" disabled>
                                <i class="fas fa-user-slash"></i> {{ __('User Offline') }}
                            </button>
                        @elseif($realTimeStatus && $realTimeStatus['status'] === 'timeout')
                            <button type="button" class="btn btn-outline-warning btn-block" disabled title="{{ __('Cannot disconnect - connection status unknown due to timeout') }}">
                                <i class="fas fa-hourglass-half"></i> {{ __('Connection Timeout') }}
                            </button>
                        @else
                            <button type="button" class="btn btn-outline-secondary btn-block" disabled title="{{ __('Cannot disconnect - connection status unknown') }}">
                                <i class="fas fa-question-circle"></i> {{ __('Status Unknown') }}
                            </button>
                        @endif

                        <hr>

                        {{-- Delete Form with MikroTik Sync Option --}}
                        <form action="{{ route('ppp-secrets.destroy', $pppSecret) }}" method="POST" id="deleteForm">
                            @csrf
                            @method('DELETE')
                            
                            <div class="form-group">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="syncWithMikrotik" name="sync_with_mikrotik" value="1" checked>
                                    <label class="custom-control-label" for="syncWithMikrotik">
                                        {{ __('Also delete from MikroTik router') }}
                                    </label>
                                    <small class="form-text text-muted">
                                        {{ __('Uncheck this if you only want to delete from database') }}
                                    </small>
                                </div>
                            </div>
                            
                            <button type="button" class="btn btn-outline-danger btn-block" onclick="confirmDelete()">
                                <i class="fas fa-trash"></i> {{ __('Delete Secret') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Profile Info Card -->
            @if($pppSecret->pppProfile)
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">{{ __('Profile Details') }}</h6>
                </div>
                <div class="card-body">
                    <h6 class="text-primary">{{ $pppSecret->pppProfile->name }}</h6>
                    <p class="text-sm">
                        <strong>{{ __('Speed:') }}</strong> {{ $pppSecret->pppProfile->formatted_rate_limit }}<br>
                        <strong>{{ __('Price:') }}</strong> {{ $pppSecret->pppProfile->price ? 'Rp ' . number_format($pppSecret->pppProfile->price, 0, ',', '.') . '/month' : __('Free') }}
                    </p>
                </div>
            </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
<script>
    // Toggle password visibility
    document.getElementById('togglePassword').addEventListener('click', function() {
        const hiddenSpan = document.getElementById('password-hidden');
        const visibleSpan = document.getElementById('password-visible');
        const icon = this.querySelector('i');
        
        if (hiddenSpan.style.display !== 'none') {
            hiddenSpan.style.display = 'none';
            visibleSpan.style.display = 'inline';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            hiddenSpan.style.display = 'inline';
            visibleSpan.style.display = 'none';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    });

    @if($realTimeStatus && $realTimeStatus['status'] === 'timeout')
        // Auto-refresh for timeout status every 60 seconds
        let refreshTimer = setTimeout(function() {
            console.log('Auto-refreshing due to timeout status...');
            window.location.reload();
        }, 60000); // 60 seconds

        // Add visual countdown
        let countdownSeconds = 60;
        function updateCountdown() {
            const countdownElement = document.getElementById('refresh-countdown');
            if (countdownElement && countdownSeconds > 0) {
                countdownSeconds--;
                countdownElement.textContent = countdownSeconds + 's';
                setTimeout(updateCountdown, 1000);
            } else if (countdownElement) {
                countdownElement.textContent = 'Refreshing...';
            }
        }

        // Start countdown immediately
        document.addEventListener('DOMContentLoaded', function() {
            updateCountdown();
        });

        // Clear timer if user manually refreshes
        window.addEventListener('beforeunload', function() {
            clearTimeout(refreshTimer);
        });
    @endif

    // Retry connection function
    function retryConnection() {
        const button = event.target.closest('button');
        const originalText = button.innerHTML;
        
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> {{ __('Retrying...') }}';
        button.disabled = true;
        
        // Reload page after a short delay to show the loading state
        setTimeout(function() {
            window.location.reload();
        }, 500);
    }

    // Force refresh function (bypasses cache)
    function forceRefresh() {
        const button = event.target.closest('button');
        const originalText = button.innerHTML;
        
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> {{ __('Force Refreshing...') }}';
        button.disabled = true;
        
        // Add cache-busting parameter and reload
        const url = new URL(window.location);
        url.searchParams.set('force_refresh', Date.now());
        window.location.href = url.toString();
    }

    // Delete confirmation with MikroTik sync option
    function confirmDelete() {
        const syncCheckbox = document.getElementById('syncWithMikrotik');
        const syncWithMikrotik = syncCheckbox.checked;
        
        let message = '{{ __('Are you sure you want to delete this PPP secret?') }}\n\n';
        
        if (syncWithMikrotik) {
            message += '✅ {{ __('Will be deleted from:') }}\n';
            message += '• {{ __('Application database') }}\n';
            message += '• {{ __('MikroTik router') }}\n\n';
            message += '⚠️ {{ __('This action cannot be undone!') }}';
        } else {
            message += '⚠️ {{ __('Will only be deleted from application database') }}\n';
            message += '📝 {{ __('Secret will remain on MikroTik router') }}\n\n';
            message += '{{ __('You can manually delete it from MikroTik later if needed.') }}';
        }
        
        if (confirm(message)) {
            document.getElementById('deleteForm').submit();
        }
    }
</script>
@endpush
