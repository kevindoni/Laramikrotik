@extends('layouts.admin')

@section('main-content')
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-heartbeat text-primary"></i> System Health
        </h1>
        <button class="btn btn-primary btn-sm" onclick="refreshData()">
            <i class="fas fa-sync-alt"></i> Refresh
        </button>
    </div>

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle"></i> {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if (session('warning'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle"></i> {{ session('warning') }}
            <button type="button" class="close" data-dismiss="alert">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if (session('info'))
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <i class="fas fa-info-circle"></i> {{ session('info') }}
            <button type="button" class="close" data-dismiss="alert">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <!-- System Resource Cards -->
    <div class="row">
        <!-- CPU Usage -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                CPU Usage
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="cpuUsage">
                                {{ isset($systemResource['cpu-load']) ? $systemResource['cpu-load'] . (strpos($systemResource['cpu-load'], '%') === false ? '%' : '') : 'N/A' }}
                            </div>
                            <div class="progress progress-sm mt-2">
                                <div class="progress-bar bg-primary" role="progressbar" 
                                     style="width: {{ isset($systemResource['cpu-load']) ? str_replace('%', '', $systemResource['cpu-load']) : 0 }}%"
                                     id="cpuProgress"></div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-microchip fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Memory Usage -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Memory Usage
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="memoryUsage">
                                @if(isset($systemResource['free-memory']) && isset($systemResource['total-memory']))
                                    @php
                                        // Handle both byte format and MiB format
                                        $totalMemory = $systemResource['total-memory'];
                                        $freeMemory = $systemResource['free-memory'];
                                        
                                        // Handle numeric (byte) format
                                        if (is_numeric($totalMemory)) {
                                            $totalMemory = $totalMemory / 1024 / 1024; // Convert bytes to MiB
                                        } elseif (is_string($totalMemory) && strpos($totalMemory, 'MiB') !== false) {
                                            $totalMemory = floatval(str_replace('MiB', '', $totalMemory));
                                        }
                                        
                                        if (is_numeric($freeMemory)) {
                                            $freeMemory = $freeMemory / 1024 / 1024; // Convert bytes to MiB
                                        } elseif (is_string($freeMemory) && strpos($freeMemory, 'MiB') !== false) {
                                            $freeMemory = floatval(str_replace('MiB', '', $freeMemory));
                                        }
                                        
                                        $usedMemory = $totalMemory - $freeMemory;
                                        $memoryPercent = round(($usedMemory / $totalMemory) * 100, 1);
                                    @endphp
                                    {{ $memoryPercent }}%
                                @else
                                    N/A
                                @endif
                            </div>
                            <div class="progress progress-sm mt-2">
                                <div class="progress-bar bg-success" role="progressbar" 
                                     style="width: {{ isset($memoryPercent) ? $memoryPercent : 0 }}%"
                                     id="memoryProgress"></div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-memory fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Disk Usage -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Disk Usage
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="diskUsage">
                                @if(isset($systemResource['free-hdd-space']) && isset($systemResource['total-hdd-space']))
                                    @php
                                        $totalDisk = $systemResource['total-hdd-space'];
                                        $freeDisk = $systemResource['free-hdd-space'];
                                        
                                        // Handle both byte format and MiB format
                                        if (is_string($totalDisk) && strpos($totalDisk, 'MiB') !== false) {
                                            $totalDisk = floatval(str_replace('MiB', '', $totalDisk));
                                        } else {
                                            $totalDisk = $totalDisk / 1024 / 1024; // Convert bytes to MiB
                                        }
                                        
                                        if (is_string($freeDisk) && strpos($freeDisk, 'MiB') !== false) {
                                            $freeDisk = floatval(str_replace('MiB', '', $freeDisk));
                                        } else {
                                            $freeDisk = $freeDisk / 1024 / 1024; // Convert bytes to MiB
                                        }
                                        
                                        $usedDisk = $totalDisk - $freeDisk;
                                        $diskPercent = round(($usedDisk / $totalDisk) * 100, 1);
                                    @endphp
                                    {{ $diskPercent }}%
                                @else
                                    N/A
                                @endif
                            </div>
                            <div class="progress progress-sm mt-2">
                                <div class="progress-bar bg-info" role="progressbar" 
                                     style="width: {{ isset($diskPercent) ? $diskPercent : 0 }}%"
                                     id="diskProgress"></div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-hdd fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Uptime -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                System Uptime
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="systemUptime">
                                {{ $systemResource['uptime'] ?? 'N/A' }}
                            </div>
                            <div class="mt-2 small text-gray-600">
                                <i class="fas fa-check-circle text-success"></i> System Running
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Temperature and Health Status Row -->
    <div class="row">
        <!-- Temperature Status -->
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-{{ !empty($systemHealth) && isset($systemHealth[0]['value']) && $systemHealth[0]['value'] < 60 ? 'success' : (!empty($systemHealth) && isset($systemHealth[0]['value']) && $systemHealth[0]['value'] < 75 ? 'warning' : 'danger') }} shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-{{ !empty($systemHealth) && isset($systemHealth[0]['value']) && $systemHealth[0]['value'] < 60 ? 'success' : (!empty($systemHealth) && isset($systemHealth[0]['value']) && $systemHealth[0]['value'] < 75 ? 'warning' : 'danger') }} text-uppercase mb-1">
                                CPU Temperature
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                @if(!empty($systemHealth) && isset($systemHealth[0]['value']))
                                    {{ $systemHealth[0]['value'] }}°C
                                @else
                                    N/A
                                @endif
                            </div>
                            <div class="mt-2 small">
                                @if(!empty($systemHealth) && isset($systemHealth[0]['value']))
                                    @php $temp = intval($systemHealth[0]['value']); @endphp
                                    @if($temp < 60)
                                        <i class="fas fa-snowflake text-success"></i> Normal
                                    @elseif($temp < 75)
                                        <i class="fas fa-thermometer-half text-warning"></i> Warm
                                    @else
                                        <i class="fas fa-fire text-danger"></i> Hot
                                    @endif
                                @else
                                    <i class="fas fa-question-circle text-muted"></i> Unknown
                                @endif
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-thermometer-half fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Health Status Overview -->
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Health Status
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                @if(!empty($systemHealth))
                                    @php 
                                        $healthCount = count($systemHealth);
                                        $healthStatus = 'All Systems OK';
                                    @endphp
                                    {{ $healthCount }} Sensor{{ $healthCount > 1 ? 's' : '' }}
                                @else
                                    No Sensors
                                @endif
                            </div>
                            <div class="mt-2 small text-gray-600">
                                @if(!empty($systemHealth))
                                    <i class="fas fa-shield-alt text-success"></i> {{ $healthStatus }}
                                @else
                                    <i class="fas fa-exclamation-triangle text-warning"></i> No Data
                                @endif
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-heartbeat fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Load Indicator -->
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                System Load
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                @php
                                    $cpuLoad = isset($systemResource['cpu-load']) ? intval(str_replace('%', '', $systemResource['cpu-load'])) : 0;
                                    $loadStatus = $cpuLoad < 25 ? 'Very Low' : ($cpuLoad < 50 ? 'Low' : ($cpuLoad < 75 ? 'Medium' : 'High'));
                                @endphp
                                {{ $loadStatus }}
                            </div>
                            <div class="mt-2 small text-gray-600">
                                <i class="fas fa-{{ $cpuLoad < 25 ? 'check-circle text-success' : ($cpuLoad < 75 ? 'exclamation-circle text-warning' : 'times-circle text-danger') }}"></i> 
                                CPU {{ $systemResource['cpu-load'] ?? 'N/A' }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-tachometer-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- System Overview Charts -->
    <div class="row">
        <div class="col-lg-4 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-pie"></i> Resource Usage Overview
                    </h6>
                </div>
                <div class="card-body">
                    <div id="resourceChart" style="height: 300px;"></div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-tachometer-alt"></i> System Performance
                    </h6>
                </div>
                <div class="card-body">
                    <div id="performanceChart" style="height: 300px;"></div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-microchip"></i> CPU Core Monitor
                    </h6>
                </div>
                <div class="card-body">
                    @if(isset($systemResourceMonitor['cpu-used-per-cpu']))
                        <div class="mb-3">
                            <small class="font-weight-bold text-muted">Real-time CPU Usage per Core</small>
                        </div>
                        @php
                            $cpuCores = is_array($systemResourceMonitor['cpu-used-per-cpu']) ? 
                                       $systemResourceMonitor['cpu-used-per-cpu'] : 
                                       explode('%', str_replace(['%', ' '], ['', ''], $systemResourceMonitor['cpu-used-per-cpu']));
                            $coreCount = count(array_filter($cpuCores, function($v) { return $v !== ''; }));
                        @endphp
                        @for($i = 0; $i < $coreCount && $i < 4; $i++)
                            @php
                                $coreUsage = isset($cpuCores[$i]) ? intval(str_replace('%', '', $cpuCores[$i])) : 0;
                                $progressColor = $coreUsage > 80 ? 'danger' : ($coreUsage > 60 ? 'warning' : 'success');
                            @endphp
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <small class="font-weight-bold">Core {{ $i + 1 }}</small>
                                    <small class="text-muted">{{ $coreUsage }}%</small>
                                </div>
                                <div class="progress progress-sm">
                                    <div class="progress-bar bg-{{ $progressColor }}" 
                                         role="progressbar" 
                                         style="width: {{ $coreUsage }}%"></div>
                                </div>
                            </div>
                        @endfor
                    @else
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-microchip fa-3x mb-3"></i>
                            <p>Real-time CPU core data unavailable</p>
                            <small>Overall CPU: {{ $systemResource['cpu-load'] ?? 'N/A' }}</small>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- System Details Row - 3 Cards Layout -->
    <div class="row">
        <!-- Real-time Monitoring -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow h-100">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-line mr-2"></i> Real-time Monitor
                    </h6>
                </div>
                <div class="card-body">
                    @if(!empty($systemResourceMonitor))
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>CPU Used:</strong></td>
                                    <td>
                                        <span class="badge badge-{{ isset($systemResourceMonitor['cpu-used']) && str_replace('%', '', $systemResourceMonitor['cpu-used']) > 80 ? 'danger' : (str_replace('%', '', $systemResourceMonitor['cpu-used']) > 60 ? 'warning' : 'success') }}">
                                            {{ $systemResourceMonitor['cpu-used'] ?? 'N/A' }}
                                        </span>
                                    </td>
                                </tr>
                                @if(isset($systemResourceMonitor['cpu-used-per-cpu']))
                                    <tr>
                                        <td><strong>CPU Per Core:</strong></td>
                                        <td>
                                            @if(is_array($systemResourceMonitor['cpu-used-per-cpu']))
                                                @foreach($systemResourceMonitor['cpu-used-per-cpu'] as $index => $coreUsage)
                                                    <small class="badge badge-secondary mr-1">C{{ $index + 1 }}: {{ $coreUsage }}</small>
                                                @endforeach
                                            @else
                                                {{ $systemResourceMonitor['cpu-used-per-cpu'] }}
                                            @endif
                                        </td>
                                    </tr>
                                @endif
                                <tr>
                                    <td><strong>Free Memory:</strong></td>
                                    <td>
                                        @if(isset($systemResourceMonitor['free-memory']))
                                            @php
                                                $freeMemStr = $systemResourceMonitor['free-memory'];
                                                if (strpos($freeMemStr, 'KiB') !== false) {
                                                    $freeMem = floatval(str_replace('KiB', '', $freeMemStr));
                                                    $freeMemMiB = $freeMem / 1024;
                                                } else {
                                                    $freeMemMiB = floatval($freeMemStr) / 1024 / 1024;
                                                }
                                            @endphp
                                            <span class="font-weight-bold">{{ number_format($freeMemMiB, 1) }} MiB</span>
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Monitor Time:</strong></td>
                                    <td>
                                        <small class="text-success">
                                            <i class="fas fa-circle"></i> Live
                                        </small>
                                        <span class="ml-1" id="monitorTime">{{ now()->format('H:i:s') }}</span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    @else
                        <div class="text-center text-muted py-3">
                            <i class="fas fa-exclamation-circle fa-2x mb-2"></i>
                            <p class="mb-0 small">Real-time data unavailable</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- System Resource Details -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow h-100">
                <div class="card-header py-3 d-flex align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-server mr-2"></i> System Details
                    </h6>
                </div>
                <div class="card-body">
                    @if(!empty($systemResource))
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <tbody>
                                    <tr>
                                        <td class="font-weight-semibold text-gray-800" style="width: 45%;">
                                            <i class="fas fa-microchip text-primary mr-1"></i>Board
                                        </td>
                                        <td class="text-gray-700 small">{{ $systemResource['board-name'] ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="font-weight-semibold text-gray-800">
                                            <i class="fas fa-cogs text-info mr-1"></i>Arch
                                        </td>
                                        <td class="text-gray-700 small">{{ $systemResource['architecture-name'] ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="font-weight-semibold text-gray-800">
                                            <i class="fas fa-code-branch text-success mr-1"></i>Version
                                        </td>
                                        <td class="text-gray-700">
                                            <span class="badge badge-primary badge-sm">{{ $systemResource['version'] ?? 'N/A' }}</span>
                                        </td>
                                    </tr>
                                    <tr class="bg-light">
                                        <td class="font-weight-semibold text-gray-800">
                                            <i class="fas fa-memory text-warning mr-1"></i>Memory
                                        </td>
                                        <td class="text-gray-700 small">
                                            @if(isset($systemResource['total-memory']))
                                                @if(is_numeric($systemResource['total-memory']))
                                                    <span class="font-weight-bold">{{ number_format($systemResource['total-memory'] / 1024 / 1024, 1) }}</span> MiB
                                                @else
                                                    {{ $systemResource['total-memory'] }}
                                                @endif
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="font-weight-semibold text-gray-800">
                                            <i class="fas fa-hdd text-primary mr-1"></i>Storage
                                        </td>
                                        <td class="text-gray-700 small">
                                            @if(isset($systemResource['total-hdd-space']))
                                                @if(is_numeric($systemResource['total-hdd-space']))
                                                    <span class="font-weight-bold">{{ number_format($systemResource['total-hdd-space'] / 1024 / 1024, 1) }}</span> MiB
                                                @else
                                                    {{ $systemResource['total-hdd-space'] }}
                                                @endif
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr class="bg-light">
                                        <td class="font-weight-semibold text-gray-800">
                                            <i class="fas fa-microchip text-danger mr-1"></i>CPU
                                        </td>
                                        <td class="text-gray-700 small">{{ $systemResource['cpu'] ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="font-weight-semibold text-gray-800">
                                            <i class="fas fa-layer-group text-info mr-1"></i>Cores
                                        </td>
                                        <td class="text-gray-700">
                                            <span class="badge badge-info badge-sm">{{ $systemResource['cpu-count'] ?? 'N/A' }}</span>
                                        </td>
                                    </tr>
                                    <tr class="bg-light">
                                        <td class="font-weight-semibold text-gray-800">
                                            <i class="fas fa-tachometer-alt text-warning mr-1"></i>Freq
                                        </td>
                                        <td class="text-gray-700 small">
                                            @if(isset($systemResource['cpu-frequency']))
                                                <span class="font-weight-bold">{{ $systemResource['cpu-frequency'] }}</span> MHz
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center text-muted py-3">
                            <i class="fas fa-exclamation-circle fa-2x mb-2"></i>
                            <p class="mb-0 small">System data unavailable</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Health Sensors -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow h-100">
                <div class="card-header py-3 d-flex align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-thermometer-half mr-2"></i> Health Sensors
                    </h6>
                    @if(!empty($systemHealth))
                        <span class="badge badge-info ml-auto">{{ count($systemHealth) }}</span>
                    @endif
                </div>
                <div class="card-body">
                    @if(!empty($systemHealth))
                        @foreach($systemHealth as $sensor)
                            <div class="mb-3 p-2 border-left border-{{ isset($sensor['value']) && isset($sensor['name']) && strpos($sensor['name'], 'temperature') !== false ? (intval($sensor['value']) < 60 ? 'success' : (intval($sensor['value']) < 75 ? 'warning' : 'danger')) : 'info' }} bg-light rounded">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="font-weight-bold text-gray-800 small">
                                            {{ $sensor['name'] ?? 'Unknown Sensor' }}
                                            @if(isset($sensor['name']) && strpos($sensor['name'], 'cpu-temperature') !== false)
                                                <span class="badge badge-primary badge-sm ml-1">CPU</span>
                                            @endif
                                        </div>
                                        <div class="text-{{ isset($sensor['value']) && isset($sensor['name']) && strpos($sensor['name'], 'temperature') !== false ? (intval($sensor['value']) < 60 ? 'success' : (intval($sensor['value']) < 75 ? 'warning' : 'danger')) : 'info' }}">
                                            @if(isset($sensor['value']))
                                                <strong>{{ $sensor['value'] }}</strong>
                                                @if(isset($sensor['type']) && strtolower($sensor['type']) === 'c')
                                                    °C
                                                @elseif(isset($sensor['name']) && strpos($sensor['name'], 'temperature') !== false)
                                                    °C
                                                @endif
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        @if(isset($sensor['status']))
                                            @if($sensor['status'] === 'ok')
                                                <i class="fas fa-check-circle text-success"></i>
                                            @elseif($sensor['status'] === 'warning')
                                                <i class="fas fa-exclamation-triangle text-warning"></i>
                                            @else
                                                <i class="fas fa-times-circle text-danger"></i>
                                            @endif
                                        @else
                                            @if(isset($sensor['value']) && isset($sensor['name']) && strpos($sensor['name'], 'temperature') !== false)
                                                @php $temp = intval($sensor['value']); @endphp
                                                @if($temp < 60)
                                                    <i class="fas fa-snowflake text-success"></i>
                                                @elseif($temp < 75)
                                                    <i class="fas fa-thermometer-half text-warning"></i>
                                                @else
                                                    <i class="fas fa-fire text-danger"></i>
                                                @endif
                                            @else
                                                <i class="fas fa-question-circle text-muted"></i>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                                @if(isset($sensor['status']))
                                    <small class="text-muted">
                                        Status: 
                                        @if($sensor['status'] === 'ok')
                                            OK
                                        @elseif($sensor['status'] === 'warning')
                                            Warning
                                        @else
                                            Critical
                                        @endif
                                    </small>
                                @else
                                    @if(isset($sensor['value']) && isset($sensor['name']) && strpos($sensor['name'], 'temperature') !== false)
                                        @php $temp = intval($sensor['value']); @endphp
                                        <small class="text-muted">
                                            Status: 
                                            @if($temp < 60)
                                                Normal
                                            @elseif($temp < 75)
                                                Warm
                                            @else
                                                Hot
                                            @endif
                                        </small>
                                    @endif
                                @endif
                            </div>
                        @endforeach
                    @else
                        <div class="text-center text-muted py-3">
                            <i class="fas fa-thermometer-half fa-2x mb-2"></i>
                            <p class="mb-0 small">No sensors available</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Interface Status -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3 d-flex align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-ethernet mr-2"></i> Interface Status
                    </h6>
                    <div class="d-flex align-items-center">
                        <small class="text-muted mr-3">
                            <i class="fas fa-clock mr-1"></i>Last updated: <span id="interfaceUpdateTime">{{ now()->format('H:i:s') }}</span>
                        </small>
                        <span class="badge badge-info">{{ count($interfaces ?? []) }} interfaces</span>
                    </div>
                </div>
                <div class="card-body">
                    @if(!empty($interfaces))
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead class="thead-light">
                                    <tr>
                                        <th style="width: 20%">
                                            <i class="fas fa-network-wired mr-1"></i>Interface
                                        </th>
                                        <th style="width: 12%">
                                            <i class="fas fa-tag mr-1"></i>Type
                                        </th>
                                        <th style="width: 12%">
                                            <i class="fas fa-power-off mr-1"></i>Status
                                        </th>
                                        <th style="width: 18%">
                                            <i class="fas fa-fingerprint mr-1"></i>MAC Address
                                        </th>
                                        <th style="width: 8%">
                                            <i class="fas fa-compress-arrows-alt mr-1"></i>MTU
                                        </th>
                                        <th style="width: 15%">
                                            <i class="fas fa-download mr-1 text-success"></i>RX Data
                                        </th>
                                        <th style="width: 15%">
                                            <i class="fas fa-upload mr-1 text-primary"></i>TX Data
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($interfaces as $interface)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    @if($interface['type'] === 'ether')
                                                        <i class="fas fa-ethernet text-primary mr-2"></i>
                                                    @elseif($interface['type'] === 'wifi' || $interface['type'] === 'wlan')
                                                        <i class="fas fa-wifi text-info mr-2"></i>
                                                    @elseif(str_contains($interface['type'], 'pppoe'))
                                                        <i class="fas fa-user-circle text-warning mr-2"></i>
                                                    @elseif(str_contains($interface['type'], 'vpn') || str_contains($interface['type'], 'ovpn') || str_contains($interface['type'], 'l2tp'))
                                                        <i class="fas fa-shield-alt text-success mr-2"></i>
                                                    @elseif($interface['type'] === 'loopback')
                                                        <i class="fas fa-circle text-secondary mr-2"></i>
                                                    @else
                                                        <i class="fas fa-network-wired text-muted mr-2"></i>
                                                    @endif
                                                    <div>
                                                        <div class="font-weight-bold text-gray-800">{{ $interface['name'] ?? 'Unknown' }}</div>
                                                        @if(isset($interface['comment']) && !empty($interface['comment']))
                                                            <small class="text-muted">{{ $interface['comment'] }}</small>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge badge-light border">{{ strtoupper($interface['type'] ?? 'N/A') }}</span>
                                            </td>
                                            <td>
                                                @if(isset($interface['running']) && $interface['running'] === 'true')
                                                    <span class="badge badge-success">
                                                        <i class="fas fa-check-circle mr-1"></i>Running
                                                    </span>
                                                @elseif(isset($interface['disabled']) && $interface['disabled'] === 'true')
                                                    <span class="badge badge-secondary">
                                                        <i class="fas fa-times-circle mr-1"></i>Disabled
                                                    </span>
                                                @else
                                                    <span class="badge badge-danger">
                                                        <i class="fas fa-exclamation-circle mr-1"></i>Down
                                                    </span>
                                                @endif
                                            </td>
                                            <td>
                                                @if(isset($interface['mac-address']) && $interface['mac-address'] !== 'N/A')
                                                    <code class="text-primary">{{ $interface['mac-address'] }}</code>
                                                @else
                                                    <span class="text-muted font-italic">N/A</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="font-weight-bold">{{ $interface['mtu'] ?? 'N/A' }}</span>
                                            </td>
                                            <td>
                                                @if(isset($interface['rx-byte']) && is_numeric($interface['rx-byte']))
                                                    @php
                                                        $rxMB = $interface['rx-byte'] / 1024 / 1024;
                                                    @endphp
                                                    <div class="d-flex align-items-center">
                                                        <i class="fas fa-arrow-down text-success mr-1"></i>
                                                        <span class="font-weight-bold">{{ number_format($rxMB, 2) }}</span>
                                                        <small class="text-muted ml-1">MB</small>
                                                    </div>
                                                    @if(isset($interface['rx-packet']))
                                                        <small class="text-muted">{{ number_format($interface['rx-packet']) }} packets</small>
                                                    @endif
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if(isset($interface['tx-byte']) && is_numeric($interface['tx-byte']))
                                                    @php
                                                        $txMB = $interface['tx-byte'] / 1024 / 1024;
                                                    @endphp
                                                    <div class="d-flex align-items-center">
                                                        <i class="fas fa-arrow-up text-primary mr-1"></i>
                                                        <span class="font-weight-bold">{{ number_format($txMB, 2) }}</span>
                                                        <small class="text-muted ml-1">MB</small>
                                                    </div>
                                                    @if(isset($interface['tx-packet']))
                                                        <small class="text-muted">{{ number_format($interface['tx-packet']) }} packets</small>
                                                    @endif
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Interface Summary -->
                        <div class="row mt-3">
                            @php
                                $runningCount = collect($interfaces)->where('running', 'true')->count();
                                $disabledCount = collect($interfaces)->where('disabled', 'true')->count();
                                $downCount = count($interfaces) - $runningCount - $disabledCount;
                            @endphp
                            <div class="col-md-4">
                                <div class="text-center p-2 bg-success-light rounded">
                                    <i class="fas fa-check-circle text-success fa-lg"></i>
                                    <div class="font-weight-bold text-success">{{ $runningCount }}</div>
                                    <small class="text-muted">Running</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-center p-2 bg-secondary-light rounded">
                                    <i class="fas fa-times-circle text-secondary fa-lg"></i>
                                    <div class="font-weight-bold text-secondary">{{ $disabledCount }}</div>
                                    <small class="text-muted">Disabled</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-center p-2 bg-danger-light rounded">
                                    <i class="fas fa-exclamation-circle text-danger fa-lg"></i>
                                    <div class="font-weight-bold text-danger">{{ $downCount }}</div>
                                    <small class="text-muted">Down</small>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-ethernet fa-3x mb-3 text-warning"></i>
                            <h5 class="text-gray-600">No Interface Data</h5>
                            <p class="mb-0">Interface information is not available at this time.</p>
                            <button class="btn btn-outline-primary btn-sm mt-2" onclick="refreshData()">
                                <i class="fas fa-sync-alt mr-1"></i>Refresh Data
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

@endsection

@push('styles')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
@endpush

@push('scripts')
<script>
// System resource data from server
var systemData = @json($systemResource ?? []);
var monitorData = @json($systemResourceMonitor ?? []);
var healthData = @json($systemHealth ?? []);

// Initialize charts when page loads
$(document).ready(function() {
    initResourceChart();
    initPerformanceChart();
    updateMonitorTime();
    
    // Auto refresh every 15 seconds for real-time monitoring
    setInterval(function() {
        refreshData();
    }, 15000);
    
    // Update monitor time every second
    setInterval(updateMonitorTime, 1000);
});

function updateMonitorTime() {
    var now = new Date();
    $('#monitorTime').text(now.toLocaleTimeString());
}

function initResourceChart() {
    // Parse resource usage data with better format handling
    var cpuUsage = 0;
    if (systemData['cpu-load']) {
        cpuUsage = parseFloat(systemData['cpu-load'].toString().replace('%', ''));
    }
    
    var memoryUsage = 0;
    if (systemData['free-memory'] && systemData['total-memory']) {
        var totalMem, freeMem;
        
        // Handle both MiB format and byte format
        if (typeof systemData['total-memory'] === 'string' && systemData['total-memory'].includes('MiB')) {
            totalMem = parseFloat(systemData['total-memory'].replace('MiB', ''));
            freeMem = parseFloat(systemData['free-memory'].replace('MiB', ''));
        } else {
            // Convert bytes to MiB
            totalMem = parseFloat(systemData['total-memory']) / 1024 / 1024;
            freeMem = parseFloat(systemData['free-memory']) / 1024 / 1024;
        }
        
        memoryUsage = ((totalMem - freeMem) / totalMem * 100);
    }
    
    var diskUsage = 0;
    if (systemData['free-hdd-space'] && systemData['total-hdd-space']) {
        var totalDisk, freeDisk;
        
        // Handle both MiB format and byte format
        if (typeof systemData['total-hdd-space'] === 'string' && systemData['total-hdd-space'].includes('MiB')) {
            totalDisk = parseFloat(systemData['total-hdd-space'].replace('MiB', ''));
            freeDisk = parseFloat(systemData['free-hdd-space'].replace('MiB', ''));
        } else {
            // Convert bytes to MiB
            totalDisk = parseFloat(systemData['total-hdd-space']) / 1024 / 1024;
            freeDisk = parseFloat(systemData['free-hdd-space']) / 1024 / 1024;
        }
        
        diskUsage = ((totalDisk - freeDisk) / totalDisk * 100);
    }

    var options = {
        series: [cpuUsage, memoryUsage, diskUsage],
        chart: {
            type: 'donut',
            height: 300,
            fontFamily: 'Nunito, sans-serif'
        },
        labels: ['CPU Usage', 'Memory Usage', 'Disk Usage'],
        colors: ['#4e73df', '#1cc88a', '#36b9cc'],
        plotOptions: {
            pie: {
                donut: {
                    size: '60%',
                    labels: {
                        show: true,
                        total: {
                            showAlways: true,
                            show: true,
                            label: 'System Load',
                            fontSize: '16px',
                            fontWeight: 600,
                            color: '#373d3f',
                            formatter: function (w) {
                                var avg = (cpuUsage + memoryUsage + diskUsage) / 3;
                                return avg.toFixed(1) + '%';
                            }
                        }
                    }
                }
            }
        },
        dataLabels: {
            enabled: true,
            formatter: function(val) {
                return val.toFixed(1) + '%';
            }
        },
        legend: {
            position: 'bottom',
            fontSize: '14px'
        },
        tooltip: {
            y: {
                formatter: function(val) {
                    return val.toFixed(1) + '%';
                }
            }
        }
    };

    var chart = new ApexCharts(document.querySelector("#resourceChart"), options);
    chart.render();
}

function initPerformanceChart() {
    // Create gauge-style chart for system performance with better parsing
    var cpuLoad = 0;
    if (systemData['cpu-load']) {
        cpuLoad = parseFloat(systemData['cpu-load'].toString().replace('%', ''));
    }
    
    var options = {
        series: [cpuLoad],
        chart: {
            height: 300,
            type: 'radialBar',
            fontFamily: 'Nunito, sans-serif'
        },
        plotOptions: {
            radialBar: {
                startAngle: -135,
                endAngle: 225,
                hollow: {
                    margin: 0,
                    size: '70%',
                    background: '#fff',
                    image: undefined,
                    position: 'front',
                    dropShadow: {
                        enabled: true,
                        top: 3,
                        left: 0,
                        blur: 4,
                        opacity: 0.24
                    }
                },
                track: {
                    background: '#fff',
                    strokeWidth: '67%',
                    margin: 0,
                    dropShadow: {
                        enabled: true,
                        top: -3,
                        left: 0,
                        blur: 4,
                        opacity: 0.35
                    }
                },
                dataLabels: {
                    show: true,
                    name: {
                        offsetY: -10,
                        show: true,
                        color: '#888',
                        fontSize: '17px'
                    },
                    value: {
                        formatter: function(val) {
                            return parseInt(val) + '%';
                        },
                        color: '#111',
                        fontSize: '36px',
                        show: true,
                    }
                }
            }
        },
        fill: {
            type: 'gradient',
            gradient: {
                shade: 'dark',
                type: 'horizontal',
                shadeIntensity: 0.5,
                gradientToColors: ['#ABE5A1'],
                inverseColors: true,
                opacityFrom: 1,
                opacityTo: 1,
                stops: [0, 100]
            }
        },
        colors: ['#4e73df'],
        labels: ['CPU Load'],
        stroke: {
            lineCap: 'round'
        }
    };

    var chart = new ApexCharts(document.querySelector("#performanceChart"), options);
    chart.render();
}

function refreshData() {
    // Show loading indicator
    $('body').append('<div id="loadingOverlay" class="position-fixed" style="top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.1);z-index:9999;"><div class="d-flex justify-content-center align-items-center h-100"><div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div></div></div>');
    
    setTimeout(function() {
        location.reload();
    }, 500);
}
</script>
@endpush
