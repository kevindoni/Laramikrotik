@extends('layouts.admin')

@section('main-content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">{{ __('Usage Log Details') }}</h1>
        <div>
            <a href="{{ route('usage-logs.edit', $usageLog) }}" class="btn btn-sm btn-warning shadow-sm">
                <i class="fas fa-edit fa-sm text-white-50"></i> {{ __('Edit') }}
            </a>
            <a href="{{ route('usage-logs.index') }}" class="btn btn-sm btn-primary shadow-sm">
                <i class="fas fa-arrow-left fa-sm text-white-50"></i> {{ __('Back') }}
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">{{ __('Session Information') }}</h6>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <td class="font-weight-bold" style="width: 200px;">{{ __('Log ID') }}</td>
                            <td>: {{ $usageLog->id }}</td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold">{{ __('Customer') }}</td>
                            <td>: 
                                @if($usageLog->customer)
                                    <a href="{{ route('customers.show', $usageLog->customer) }}" class="text-decoration-none">
                                        {{ $usageLog->customer->name }}
                                    </a>
                                @else
                                    N/A
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold">{{ __('Username') }}</td>
                            <td>: <code>{{ $usageLog->username }}</code></td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold">{{ __('Session ID') }}</td>
                            <td>: <code>{{ $usageLog->session_id ?: '-' }}</code></td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold">{{ __('Session Start') }}</td>
                            <td>: {{ $usageLog->session_start ? $usageLog->session_start->format('d M Y H:i:s') : '-' }}</td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold">{{ __('Session End') }}</td>
                            <td>: {{ $usageLog->session_end ? $usageLog->session_end->format('d M Y H:i:s') : '-' }}</td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold">{{ __('Session Duration') }}</td>
                            <td>: 
                                @if($usageLog->session_start && $usageLog->session_end)
                                    @php
                                        $duration = $usageLog->session_start->diff($usageLog->session_end);
                                        $hours = $duration->h + ($duration->days * 24);
                                        $minutes = $duration->i;
                                        $seconds = $duration->s;
                                    @endphp
                                    {{ sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds) }}
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold">{{ __('IP Address') }}</td>
                            <td>: <code>{{ $usageLog->ip_address ?: '-' }}</code></td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold">{{ __('MAC Address') }}</td>
                            <td>: <code>{{ $usageLog->mac_address ?: '-' }}</code></td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold">{{ __('NAS Port') }}</td>
                            <td>: {{ $usageLog->nas_port ?: '-' }}</td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold">{{ __('Terminate Cause') }}</td>
                            <td>: 
                                @if($usageLog->terminate_cause)
                                    <span class="badge badge-info">{{ ucwords(str_replace('_', ' ', $usageLog->terminate_cause)) }}</span>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold">{{ __('Created At') }}</td>
                            <td>: {{ $usageLog->created_at->format('d M Y H:i:s') }}</td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold">{{ __('Updated At') }}</td>
                            <td>: {{ $usageLog->updated_at->format('d M Y H:i:s') }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">{{ __('Data Usage') }}</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="font-weight-bold">{{ __('Upload') }}</span>
                            <span class="text-success">{{ formatBytes($usageLog->upload_bytes) }}</span>
                        </div>
                        <div class="progress" style="height: 10px;">
                            @php
                                $totalBytes = $usageLog->upload_bytes + $usageLog->download_bytes;
                                $uploadPercentage = $totalBytes > 0 ? ($usageLog->upload_bytes / $totalBytes) * 100 : 0;
                            @endphp
                            <div class="progress-bar bg-success" role="progressbar" style="width: {{ $uploadPercentage }}%"></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="font-weight-bold">{{ __('Download') }}</span>
                            <span class="text-info">{{ formatBytes($usageLog->download_bytes) }}</span>
                        </div>
                        <div class="progress" style="height: 10px;">
                            @php
                                $downloadPercentage = $totalBytes > 0 ? ($usageLog->download_bytes / $totalBytes) * 100 : 0;
                            @endphp
                            <div class="progress-bar bg-info" role="progressbar" style="width: {{ $downloadPercentage }}%"></div>
                        </div>
                    </div>

                    <hr>

                    <div class="d-flex justify-content-between">
                        <span class="font-weight-bold">{{ __('Total Usage') }}</span>
                        <span class="h6 text-primary">{{ formatBytes($totalBytes) }}</span>
                    </div>

                    <div class="mt-3">
                        <small class="text-muted">
                            <strong>{{ __('Raw Values:') }}</strong><br>
                            {{ __('Upload') }}: {{ number_format($usageLog->upload_bytes) }} bytes<br>
                            {{ __('Download') }}: {{ number_format($usageLog->download_bytes) }} bytes
                        </small>
                    </div>
                </div>
            </div>

            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">{{ __('Actions') }}</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('usage-logs.edit', $usageLog) }}" class="btn btn-warning btn-sm">
                            <i class="fas fa-edit"></i> {{ __('Edit Usage Log') }}
                        </a>
                        <form action="{{ route('usage-logs.destroy', $usageLog) }}" method="POST" class="delete-usage-log-form">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm w-100">
                                <i class="fas fa-trash"></i> {{ __('Delete Usage Log') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@php
    function formatBytes($bytes, $precision = 2) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
@endphp

@push('scripts')
<script>
$(document).ready(function() {
    // Delete usage log confirmation with SweetAlert
    $('.delete-usage-log-form').on('submit', function(e) {
        e.preventDefault();
        const form = this;
        
        Swal.fire({
            title: '🗑️ Delete Usage Log?',
            html: `
                <div class="text-left">
                    <p class="mb-3">Are you sure you want to delete this usage log?</p>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> <strong>Warning:</strong>
                        <ul class="mb-0 mt-2">
                            <li>This usage log record will be permanently deleted</li>
                            <li>Historical data will be lost</li>
                            <li>This action cannot be undone</li>
                        </ul>
                    </div>
                </div>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-trash"></i> Yes, Delete!',
            cancelButtonText: '<i class="fas fa-times"></i> Cancel',
            customClass: {
                confirmButton: 'btn btn-danger mx-2',
                cancelButton: 'btn btn-secondary mx-2'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });
});
</script>
@endpush
