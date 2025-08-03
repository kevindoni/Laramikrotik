<?php

namespace App\Http\Controllers;

use App\Models\PppSecret;
use App\Models\UsageLog;
use App\Services\MikrotikService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UsageLogController extends Controller
{
    protected $mikrotikService;

    /**
     * Create a new controller instance.
     *
     * @param  \App\Services\MikrotikService  $mikrotikService
     * @return void
     */
    public function __construct(MikrotikService $mikrotikService)
    {
        $this->mikrotikService = $mikrotikService;
    }

    /**
     * Display a listing of the usage logs.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = UsageLog::with(['pppSecret', 'pppSecret.customer'])
            ->orderBy('connected_at', 'desc');

        // Filter by PPP Secret
        if ($request->has('ppp_secret_id') && $request->ppp_secret_id) {
            $query->where('ppp_secret_id', $request->ppp_secret_id);
        }

        // Filter by date range
        if ($request->has('start_date') && $request->start_date) {
            $query->where('connected_at', '>=', $request->start_date . ' 00:00:00');
        }

        if ($request->has('end_date') && $request->end_date) {
            $query->where('connected_at', '<=', $request->end_date . ' 23:59:59');
        }

        // Filter by active sessions
        if ($request->has('active') && $request->active) {
            $query->whereNull('disconnected_at');
        }

        $logs = $query->paginate(15);

        // Get list of PPP Secrets for filter dropdown
        $pppSecrets = PppSecret::with('customer')
            ->orderBy('username')
            ->get()
            ->map(function ($secret) {
                return [
                    'id' => $secret->id,
                    'label' => $secret->username . ' (' . ($secret->customer->name ?? 'No Customer') . ')'
                ];
            });

        return view('usage-logs.index', [
            'usageLogs' => $logs,
            'pppSecrets' => $pppSecrets,
            'filters' => $request->only(['ppp_secret_id', 'start_date', 'end_date', 'active'])
        ]);
    }

    /**
     * Show the form for creating a new usage log.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $customers = \App\Models\Customer::orderBy('name')->get();
        
        return view('usage-logs.create', compact('customers'));
    }

    /**
     * Store a newly created usage log in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'username' => 'required|string|max:255',
            'session_id' => 'nullable|string',
            'session_start' => 'nullable|date',
            'session_end' => 'nullable|date|after:session_start',
            'upload_bytes' => 'nullable|integer|min:0',
            'download_bytes' => 'nullable|integer|min:0',
            'ip_address' => 'nullable|ip',
            'mac_address' => 'nullable|string',
            'nas_port' => 'nullable|string',
            'terminate_cause' => 'nullable|string',
        ]);

        $usageLog = UsageLog::create($request->all());

        return redirect()->route('usage-logs.show', $usageLog)
            ->with('success', 'Usage log created successfully.');
    }

    /**
     * Show the form for editing the specified usage log.
     *
     * @param  \App\Models\UsageLog  $usageLog
     * @return \Illuminate\Http\Response
     */
    public function edit(UsageLog $usageLog)
    {
        $customers = \App\Models\Customer::orderBy('name')->get();
        
        return view('usage-logs.edit', compact('usageLog', 'customers'));
    }

    /**
     * Update the specified usage log in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\UsageLog  $usageLog
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, UsageLog $usageLog)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'username' => 'required|string|max:255',
            'session_id' => 'nullable|string',
            'session_start' => 'nullable|date',
            'session_end' => 'nullable|date|after:session_start',
            'upload_bytes' => 'nullable|integer|min:0',
            'download_bytes' => 'nullable|integer|min:0',
            'ip_address' => 'nullable|ip',
            'mac_address' => 'nullable|string',
            'nas_port' => 'nullable|string',
            'terminate_cause' => 'nullable|string',
        ]);

        $usageLog->update($request->all());

        return redirect()->route('usage-logs.show', $usageLog)
            ->with('success', 'Usage log updated successfully.');
    }

    /**
     * Remove the specified usage log from storage.
     *
     * @param  \App\Models\UsageLog  $usageLog
     * @return \Illuminate\Http\Response
     */
    public function destroy(UsageLog $usageLog)
    {
        $usageLog->delete();

        return redirect()->route('usage-logs.index')
            ->with('success', 'Usage log deleted successfully.');
    }

    /**
     * Display the specified usage log.
     *
     * @param  \App\Models\UsageLog  $usageLog
     * @return \Illuminate\Http\Response
     */
    public function show(UsageLog $usageLog)
    {
        $usageLog->load(['pppSecret', 'pppSecret.customer']);
        
        return view('usage-logs.show', ['log' => $usageLog]);
    }

    /**
     * Sync active connections from MikroTik with the local database.
     *
     * @return \Illuminate\Http\Response
     */
    public function syncActiveConnections()
    {
        try {
            $this->mikrotikService->connect();
            $activeConnections = $this->mikrotikService->getActivePppConnections();
            
            $syncCount = 0;
            $newCount = 0;
            $errorCount = 0;
            $errors = [];
            
            foreach ($activeConnections as $connection) {
                try {
                    // Find the PPP Secret by username
                    $pppSecret = PppSecret::where('username', $connection['name'])->first();
                    
                    if (!$pppSecret) {
                        $errors[] = "PPP Secret not found for username: {$connection['name']}";
                        $errorCount++;
                        continue;
                    }
                    
                    // Check if there's an active session for this PPP Secret
                    $activeLog = UsageLog::where('ppp_secret_id', $pppSecret->id)
                        ->whereNull('disconnected_at')
                        ->first();
                    
                    if ($activeLog) {
                        // Update the existing log
                        $activeLog->update([
                            'caller_id' => $connection['caller-id'] ?? null,
                            'uptime' => $connection['uptime'] ?? null,
                            'bytes_in' => $connection['bytes-in'] ?? 0,
                            'bytes_out' => $connection['bytes-out'] ?? 0,
                            'ip_address' => $connection['address'] ?? null,
                        ]);
                        $syncCount++;
                    } else {
                        // Create a new log
                        UsageLog::create([
                            'ppp_secret_id' => $pppSecret->id,
                            'caller_id' => $connection['caller-id'] ?? null,
                            'uptime' => $connection['uptime'] ?? null,
                            'bytes_in' => $connection['bytes-in'] ?? 0,
                            'bytes_out' => $connection['bytes-out'] ?? 0,
                            'ip_address' => $connection['address'] ?? null,
                            'connected_at' => now(),
                            'session_id' => $connection['.id'] ?? null,
                        ]);
                        $newCount++;
                    }
                } catch (Exception $e) {
                    $errors[] = "Error processing connection {$connection['name']}: {$e->getMessage()}";
                    $errorCount++;
                }
            }
            
            // Close any active sessions that are no longer active on MikroTik
            $activeUsernames = collect($activeConnections)->pluck('name')->toArray();
            $closedCount = 0;
            
            $activeLogs = UsageLog::whereNull('disconnected_at')->get();
            foreach ($activeLogs as $log) {
                if (!$log->pppSecret) {
                    // If the PPP Secret has been deleted, close the log
                    $log->update(['disconnected_at' => now()]);
                    $closedCount++;
                    continue;
                }
                
                if (!in_array($log->pppSecret->username, $activeUsernames)) {
                    $log->update(['disconnected_at' => now()]);
                    $closedCount++;
                }
            }
            
            $message = "Sync completed: {$syncCount} updated, {$newCount} new, {$closedCount} closed";
            if ($errorCount > 0) {
                $message .= ", {$errorCount} errors";
            }
            
            return redirect()->route('usage-logs.index')
                ->with('success', $message)
                ->with('errors', $errors);
        } catch (Exception $e) {
            return redirect()->route('usage-logs.index')
                ->with('error', 'Failed to sync active connections: ' . $e->getMessage());
        }
    }

    /**
     * Display active connections from MikroTik.
     *
     * @return \Illuminate\Http\Response
     */
    public function activeConnections()
    {
        try {
            $this->mikrotikService->connect();
            $activeConnections = $this->mikrotikService->getActivePppConnections();
            
            // Get all PPP Secrets to match with active connections
            $pppSecrets = PppSecret::with('customer', 'pppProfile')->get()->keyBy('username');
            
            // Enhance the active connections with local data
            foreach ($activeConnections as &$connection) {
                $username = $connection['name'] ?? null;
                $connection['local_data'] = null;
                
                if ($username && isset($pppSecrets[$username])) {
                    $secret = $pppSecrets[$username];
                    $connection['local_data'] = [
                        'id' => $secret->id,
                        'customer_name' => $secret->customer->name ?? 'No Customer',
                        'profile_name' => $secret->pppProfile->name ?? 'No Profile',
                        'comment' => $secret->comment,
                    ];
                }
            }
            
            return view('usage-logs.active-connections', [
                'connections' => $activeConnections,
                'connectionCount' => count($activeConnections)
            ]);
        } catch (Exception $e) {
            return redirect()->route('usage-logs.index')
                ->with('error', 'Failed to get active connections: ' . $e->getMessage());
        }
    }

    /**
     * Display usage statistics.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function statistics(Request $request)
    {
        // Default to current month if no date range provided
        $startDate = $request->start_date ?? date('Y-m-01');
        $endDate = $request->end_date ?? date('Y-m-t');
        
        // Get top users by data usage using enhanced SQL with validation
        $topUsersRaw = DB::select("
            SELECT 
                ppp_secret_id,
                GREATEST(0, SUM(GREATEST(0, COALESCE(bytes_in, 0)) + GREATEST(0, COALESCE(bytes_out, 0)))) as total_bytes,
                GREATEST(0, SUM(GREATEST(0, COALESCE(bytes_in, 0)))) as total_bytes_in,
                GREATEST(0, SUM(GREATEST(0, COALESCE(bytes_out, 0)))) as total_bytes_out,
                COUNT(*) as session_count,
                GREATEST(0, SUM(TIMESTAMPDIFF(SECOND, connected_at, IFNULL(disconnected_at, NOW())))) as total_seconds
            FROM usage_logs
            WHERE connected_at >= ? AND connected_at <= ?
            AND (bytes_in IS NOT NULL OR bytes_out IS NOT NULL)
            GROUP BY ppp_secret_id
            HAVING total_bytes > 0
            ORDER BY total_bytes DESC
            LIMIT 5
        ", [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        
        // Load the related models and format the data
        $topUsers = collect();
        foreach ($topUsersRaw as $userRaw) {
            $pppSecret = PppSecret::with('customer')->find($userRaw->ppp_secret_id);
            
            $topUsers->push([
                'ppp_secret_id' => $userRaw->ppp_secret_id,
                'username' => $pppSecret->username ?? 'Unknown',
                'customer_name' => $pppSecret->customer->name ?? 'No Customer',
                'total_bytes' => (int) $userRaw->total_bytes,
                'total_bytes_formatted' => UsageLog::formatBytes((int) $userRaw->total_bytes),
                'total_bytes_in_formatted' => UsageLog::formatBytes((int) $userRaw->total_bytes_in),
                'total_bytes_out_formatted' => UsageLog::formatBytes((int) $userRaw->total_bytes_out),
                'session_count' => $userRaw->session_count,
                'total_duration' => UsageLog::formatDuration($userRaw->total_seconds),
            ]);
        }
        
        // Get daily usage statistics with enhanced data validation
        $dailyStats = collect();
        
        // Get daily totals using raw SQL with better data handling
        $dailyTotals = DB::select("
            SELECT 
                DATE(connected_at) as date,
                GREATEST(0, SUM(GREATEST(0, COALESCE(bytes_in, 0)) + GREATEST(0, COALESCE(bytes_out, 0)))) as total_bytes,
                COUNT(DISTINCT ppp_secret_id) as unique_users,
                COUNT(*) as session_count
            FROM usage_logs 
            WHERE connected_at >= ? AND connected_at <= ?
            AND (bytes_in IS NOT NULL OR bytes_out IS NOT NULL)
            GROUP BY DATE(connected_at)
            ORDER BY date
        ", [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        
        foreach ($dailyTotals as $daily) {
            $totalBytes = max(0, (int) $daily->total_bytes); // Ensure positive
            $uniqueUsers = max(1, (int) $daily->unique_users); // Avoid division by zero
            
            $dailyStats->push([
                'date' => $daily->date,
                'total_bytes' => $totalBytes,
                'total_bytes_formatted' => UsageLog::formatBytes($totalBytes),
                'unique_users' => $daily->unique_users,
                'session_count' => $daily->session_count,
                'avg_per_user' => $totalBytes / $uniqueUsers,
                'avg_per_user_formatted' => UsageLog::formatBytes($totalBytes / $uniqueUsers),
            ]);
        }
        
        // Get total statistics for the period with enhanced validation
        $totalBytes = DB::select("
            SELECT GREATEST(0, SUM(GREATEST(0, COALESCE(bytes_in, 0)) + GREATEST(0, COALESCE(bytes_out, 0)))) as total
            FROM usage_logs
            WHERE connected_at >= ? AND connected_at <= ?
            AND (bytes_in IS NOT NULL OR bytes_out IS NOT NULL)
        ", [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])[0]->total ?? 0;
        
        // Calculate average and max daily usage with proper validation
        $daysWithData = max(1, $dailyStats->count()); // Avoid division by zero
        $avgDailyBytes = $daysWithData > 0 ? (int) $totalBytes / $daysWithData : 0;
        
        // Get the maximum daily usage with validation
        $maxDailyBytes = $dailyStats->max('total_bytes') ?? 0;
        
        $totalSessions = UsageLog::whereBetween('connected_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->count();
        
        $avgDailySessions = $dailyStats->avg(function ($stat) {
            return (int) $stat['session_count'];
        });
        
        $maxDailySessions = $dailyStats->max(function ($stat) {
            return (int) $stat['session_count'];
        });
        
        $totalStats = [
            'total_bytes' => (int) $totalBytes,
            'total_bytes_formatted' => UsageLog::formatBytes((int) $totalBytes),
            'avg_daily_bytes' => (int) $avgDailyBytes,
            'avg_daily_bytes_formatted' => UsageLog::formatBytes((int) $avgDailyBytes),
            'max_daily_bytes' => (int) $maxDailyBytes,
            'max_daily_bytes_formatted' => UsageLog::formatBytes((int) $maxDailyBytes),
            'total_sessions' => $totalSessions,
            'avg_daily_sessions' => round($avgDailySessions ?? 0, 2),
            'max_daily_sessions' => $maxDailySessions ?? 0,
            'total_unique_users' => PppSecret::whereHas('usageLogs', function ($query) use ($startDate, $endDate) {
                $query->whereBetween('connected_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
            })->count(),
        ];
        
        // Prepare chart data for daily usage with validation
        $chartData = [
            'labels' => $dailyStats->pluck('date')->toArray(),
            'bytes' => $dailyStats->pluck('total_bytes')->map(function($bytes) {
                // Ensure positive values for chart and handle nulls
                return max(0, (int) ($bytes ?? 0));
            })->toArray(),
            'users' => $dailyStats->pluck('unique_users')->map(function($users) {
                return max(0, (int) ($users ?? 0));
            })->toArray(),
            'sessions' => $dailyStats->pluck('session_count')->map(function($sessions) {
                return max(0, (int) ($sessions ?? 0));
            })->toArray(),
        ];
        
        return view('usage-logs.statistics', [
            'topUsers' => $topUsers,
            'dailyStats' => $dailyStats,
            'totalStats' => $totalStats,
            'chartData' => $chartData, // Remove json_encode, let Blade handle it
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }

    /**
     * Display usage logs for a specific PPP Secret.
     *
     * @param  \App\Models\PppSecret  $pppSecret
     * @return \Illuminate\Http\Response
     */
    public function forPppSecret(PppSecret $pppSecret)
    {
        $logs = UsageLog::where('ppp_secret_id', $pppSecret->id)
            ->orderBy('connected_at', 'desc')
            ->paginate(15);
        
        return view('usage-logs.for-ppp-secret', [
            'pppSecret' => $pppSecret,
            'logs' => $logs,
        ]);
    }

    /**
     * Sync usage logs from MikroTik router.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function syncFromMikrotik()
    {
        try {
            $usageLogService = new \App\Services\UsageLogService($this->mikrotikService);
            $result = $usageLogService->syncFromMikrotik();

            if ($result['success']) {
                $message = "✅ Successfully synced {$result['synced']} active connections";
                
                if (!empty($result['errors'])) {
                    $errorCount = count($result['errors']);
                    $message .= " with {$errorCount} warnings";
                }
                
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'synced' => $result['synced'],
                    'errors' => $result['errors'] ?? []
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to sync: ' . $result['message']
                ], 500);
            }

        } catch (Exception $e) {
            logger()->error('Failed to sync usage logs from web interface', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Determine error type for better user experience
            $errorMessage = $e->getMessage();
            $statusCode = 500;
            
            if (strpos($errorMessage, 'timeout') !== false) {
                $statusCode = 408; // Request Timeout
                $errorMessage = 'MikroTik connection timeout - please try again';
            } elseif (strpos($errorMessage, 'connection') !== false) {
                $statusCode = 503; // Service Unavailable
                $errorMessage = 'Cannot connect to MikroTik router - please check connection';
            } elseif (strpos($errorMessage, 'No active MikroTik') !== false) {
                $statusCode = 422; // Unprocessable Entity
                $errorMessage = 'No active MikroTik configuration found';
            }

            return response()->json([
                'success' => false,
                'message' => $errorMessage,
                'error_type' => $statusCode === 408 ? 'timeout' : ($statusCode === 503 ? 'connection' : 'server')
            ], $statusCode);
        }
    }
}