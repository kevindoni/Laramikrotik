@extends('layouts.admin')

@section('main-content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">{{ __('Edit Usage Log') }}</h1>
        <a href="{{ route('usage-logs.index') }}" class="btn btn-sm btn-primary shadow-sm">
            <i class="fas fa-arrow-left fa-sm text-white-50"></i> {{ __('Back') }}
        </a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger border-left-danger" role="alert">
            <ul class="pl-4 my-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">{{ __('Usage Log Information') }}</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('usage-logs.update', $usageLog) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="form-group row">
                            <label for="customer_id" class="col-sm-3 col-form-label">{{ __('Customer') }} <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <select class="form-control @error('customer_id') is-invalid @enderror" id="customer_id" name="customer_id" required>
                                    <option value="">{{ __('Select Customer') }}</option>
                                    @foreach($customers as $customer)
                                        <option value="{{ $customer->id }}" {{ old('customer_id', $usageLog->customer_id) == $customer->id ? 'selected' : '' }}>
                                            {{ $customer->name }} ({{ $customer->email }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="username" class="col-sm-3 col-form-label">{{ __('Username') }} <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control @error('username') is-invalid @enderror" 
                                       id="username" name="username" value="{{ old('username', $usageLog->username) }}" required>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="session_id" class="col-sm-3 col-form-label">{{ __('Session ID') }}</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control" id="session_id" name="session_id" value="{{ old('session_id', $usageLog->session_id) }}">
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="session_start" class="col-sm-3 col-form-label">{{ __('Session Start') }}</label>
                            <div class="col-sm-9">
                                <input type="datetime-local" class="form-control" id="session_start" name="session_start" 
                                       value="{{ old('session_start', $usageLog->session_start ? $usageLog->session_start->format('Y-m-d\TH:i') : '') }}">
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="session_end" class="col-sm-3 col-form-label">{{ __('Session End') }}</label>
                            <div class="col-sm-9">
                                <input type="datetime-local" class="form-control" id="session_end" name="session_end" 
                                       value="{{ old('session_end', $usageLog->session_end ? $usageLog->session_end->format('Y-m-d\TH:i') : '') }}">
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="upload_bytes" class="col-sm-3 col-form-label">{{ __('Upload (Bytes)') }}</label>
                            <div class="col-sm-9">
                                <input type="number" class="form-control" id="upload_bytes" name="upload_bytes" 
                                       value="{{ old('upload_bytes', $usageLog->upload_bytes) }}" min="0">
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="download_bytes" class="col-sm-3 col-form-label">{{ __('Download (Bytes)') }}</label>
                            <div class="col-sm-9">
                                <input type="number" class="form-control" id="download_bytes" name="download_bytes" 
                                       value="{{ old('download_bytes', $usageLog->download_bytes) }}" min="0">
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="ip_address" class="col-sm-3 col-form-label">{{ __('IP Address') }}</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control" id="ip_address" name="ip_address" 
                                       value="{{ old('ip_address', $usageLog->ip_address) }}" placeholder="192.168.1.100">
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="mac_address" class="col-sm-3 col-form-label">{{ __('MAC Address') }}</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control" id="mac_address" name="mac_address" 
                                       value="{{ old('mac_address', $usageLog->mac_address) }}" placeholder="00:11:22:33:44:55">
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="nas_port" class="col-sm-3 col-form-label">{{ __('NAS Port') }}</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control" id="nas_port" name="nas_port" value="{{ old('nas_port', $usageLog->nas_port) }}">
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="terminate_cause" class="col-sm-3 col-form-label">{{ __('Terminate Cause') }}</label>
                            <div class="col-sm-9">
                                <select class="form-control" id="terminate_cause" name="terminate_cause">
                                    <option value="">{{ __('Select Terminate Cause') }}</option>
                                    <option value="user_request" {{ old('terminate_cause', $usageLog->terminate_cause) == 'user_request' ? 'selected' : '' }}>{{ __('User Request') }}</option>
                                    <option value="lost_carrier" {{ old('terminate_cause', $usageLog->terminate_cause) == 'lost_carrier' ? 'selected' : '' }}>{{ __('Lost Carrier') }}</option>
                                    <option value="lost_service" {{ old('terminate_cause', $usageLog->terminate_cause) == 'lost_service' ? 'selected' : '' }}>{{ __('Lost Service') }}</option>
                                    <option value="idle_timeout" {{ old('terminate_cause', $usageLog->terminate_cause) == 'idle_timeout' ? 'selected' : '' }}>{{ __('Idle Timeout') }}</option>
                                    <option value="session_timeout" {{ old('terminate_cause', $usageLog->terminate_cause) == 'session_timeout' ? 'selected' : '' }}>{{ __('Session Timeout') }}</option>
                                    <option value="admin_reset" {{ old('terminate_cause', $usageLog->terminate_cause) == 'admin_reset' ? 'selected' : '' }}>{{ __('Admin Reset') }}</option>
                                    <option value="admin_reboot" {{ old('terminate_cause', $usageLog->terminate_cause) == 'admin_reboot' ? 'selected' : '' }}>{{ __('Admin Reboot') }}</option>
                                    <option value="port_error" {{ old('terminate_cause', $usageLog->terminate_cause) == 'port_error' ? 'selected' : '' }}>{{ __('Port Error') }}</option>
                                    <option value="nas_error" {{ old('terminate_cause', $usageLog->terminate_cause) == 'nas_error' ? 'selected' : '' }}>{{ __('NAS Error') }}</option>
                                    <option value="nas_request" {{ old('terminate_cause', $usageLog->terminate_cause) == 'nas_request' ? 'selected' : '' }}>{{ __('NAS Request') }}</option>
                                    <option value="other" {{ old('terminate_cause', $usageLog->terminate_cause) == 'other' ? 'selected' : '' }}>{{ __('Other') }}</option>
                                </select>
                            </div>
                        </div>

                        <hr>

                        <div class="form-group row">
                            <div class="col-sm-9 offset-sm-3">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save fa-sm text-white-50"></i> {{ __('Update Usage Log') }}
                                </button>
                                <a href="{{ route('usage-logs.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
