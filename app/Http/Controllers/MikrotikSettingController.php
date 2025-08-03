<?php

namespace App\Http\Controllers;

use App\Models\MikrotikSetting;
use App\Services\MikrotikService;
use App\Services\HotspotToPppSync;
use App\Services\RealPppSync;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MikrotikSettingController extends Controller
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
     * Display a listing of the Mikrotik settings.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $settings = MikrotikSetting::orderBy('name')->paginate(15);
        return view('mikrotik-settings.index', compact('settings'));
    }

    /**
     * Show the form for creating a new Mikrotik setting.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('mikrotik-settings.create');
    }

    /**
     * Store a newly created Mikrotik setting in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'host' => 'required|string|max:255',
            'port' => 'required|integer|min:1|max:65535',
            'username' => 'required|string|max:255',
            'password' => 'required|string|max:255',
            'use_ssl' => 'boolean',
            'is_active' => 'boolean',
            'description' => 'nullable|string|max:1000',
            'test_connection' => 'boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->route('mikrotik-settings.create')
                ->withErrors($validator)
                ->withInput();
        }

        $data = $validator->validated();
        $testConnection = $data['test_connection'] ?? false;
        unset($data['test_connection']);

        // Handle unchecked checkboxes (they don't appear in the request when unchecked)
        $data['use_ssl'] = $data['use_ssl'] ?? false;
        $data['is_active'] = $data['is_active'] ?? false;

        // If this is set as active, deactivate all other settings
        if ($data['is_active']) {
            MikrotikSetting::where('is_active', true)->update(['is_active' => false]);
        }

        $setting = MikrotikSetting::create($data);

        // Test connection if requested
        if ($testConnection) {
            try {
                // Create a temporary service with this setting
                $tempService = new MikrotikService();
                $tempService->setSetting($setting);
                $tempService->connect();
                
                // Get system identity as a test
                $identity = $tempService->getSystemIdentity();
                
                return redirect()->route('mikrotik-settings.show', $setting->id)
                    ->with('success', "Mikrotik setting created successfully. Connection test passed! Connected to '{$identity}'.");
            } catch (Exception $e) {
                return redirect()->route('mikrotik-settings.show', $setting->id)
                    ->with('error', 'Mikrotik setting created but connection test failed: ' . $e->getMessage());
            }
        }

        return redirect()->route('mikrotik-settings.show', $setting->id)
            ->with('success', 'Mikrotik setting created successfully.');
    }

    /**
     * Display the specified Mikrotik setting.
     *
     * @param  \App\Models\MikrotikSetting  $mikrotikSetting
     * @return \Illuminate\Http\Response
     */
    public function show(MikrotikSetting $mikrotikSetting)
    {
        $systemInfo = null;
        $connectionStatus = $mikrotikSetting->getConnectionStatus();
        
        // If setting is active and recently connected, try to get system info
        if ($mikrotikSetting->is_active && $connectionStatus === 'connected') {
            try {
                $this->mikrotikService->connect();
                $systemInfo = [
                    'identity' => $this->mikrotikService->getSystemIdentity(),
                    'resources' => $this->mikrotikService->getSystemResources(),
                ];
            } catch (Exception $e) {
                // Just log the error, don't stop the page from loading
                logger()->error('Failed to get system info: ' . $e->getMessage());
            }
        }
        
        return view('mikrotik-settings.show', [
            'mikrotikSetting' => $mikrotikSetting,
            'systemInfo' => $systemInfo,
            'connectionStatus' => $connectionStatus
        ]);
    }

    /**
     * Show the form for editing the specified Mikrotik setting.
     *
     * @param  \App\Models\MikrotikSetting  $mikrotikSetting
     * @return \Illuminate\Http\Response
     */
    public function edit(MikrotikSetting $mikrotikSetting)
    {
        return view('mikrotik-settings.edit', ['mikrotikSetting' => $mikrotikSetting]);
    }

    /**
     * Update the specified Mikrotik setting in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\MikrotikSetting  $mikrotikSetting
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, MikrotikSetting $mikrotikSetting)
    {
        try {
            // Debug: Log request data
            \Log::info('Update request data:', $request->all());
            
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'host' => 'required|string|max:255',
                'port' => 'nullable|string|max:10',  // Changed to string to match migration
                'username' => 'required|string|max:255',
                'password' => 'nullable|string|max:255',
                'use_ssl' => 'sometimes|boolean',
                'is_active' => 'sometimes|boolean',
                'description' => 'nullable|string|max:1000',
                'test_connection' => 'sometimes|boolean',
            ]);

            if ($validator->fails()) {
                \Log::error('Validation failed:', $validator->errors()->toArray());
                return redirect()->route('mikrotik-settings.edit', $mikrotikSetting->id)
                    ->withErrors($validator)
                    ->withInput();
            }

            $data = $validator->validated();
            \Log::info('Validated data:', $data);
            
            $testConnection = $data['test_connection'] ?? false;
            unset($data['test_connection']);

            // Handle password field - only update if provided
            if (empty($data['password'])) {
                unset($data['password']); // Don't update password if empty
            }

            // Handle port - set default if empty
            if (empty($data['port'])) {
                $data['port'] = '8728'; // Default port
            }

            // Handle unchecked checkboxes (they don't appear in the request when unchecked)
            $data['use_ssl'] = $request->has('use_ssl') ? true : false;
            $data['is_active'] = $request->has('is_active') ? true : false;

            \Log::info('Final data to update:', $data);

            // If this is set as active, deactivate all other settings
            if ($data['is_active'] && !$mikrotikSetting->is_active) {
                MikrotikSetting::where('id', '!=', $mikrotikSetting->id)
                    ->where('is_active', true)
                    ->update(['is_active' => false]);
            }

            // Update the model
            $mikrotikSetting->update($data);
            
            \Log::info('Update completed successfully');

            // Test connection if requested
            if ($testConnection) {
                try {
                    // Create a temporary service with this setting
                    $tempService = new MikrotikService();
                    $tempService->setSetting($mikrotikSetting);
                    $tempService->connect();
                    
                    // Get system identity as a test
                    $identity = $tempService->getSystemIdentity();
                    
                    return redirect()->route('mikrotik-settings.show', $mikrotikSetting->id)
                        ->with('success', "Mikrotik setting updated successfully. Connection test passed! Connected to '{$identity}'.");
                } catch (Exception $e) {
                    return redirect()->route('mikrotik-settings.show', $mikrotikSetting->id)
                        ->with('error', 'Mikrotik setting updated but connection test failed: ' . $e->getMessage());
                }
            }

            return redirect()->route('mikrotik-settings.show', $mikrotikSetting->id)
                ->with('success', 'Mikrotik setting updated successfully.');
                
        } catch (Exception $e) {
            \Log::error('Update failed with exception:', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            
            return redirect()->route('mikrotik-settings.edit', $mikrotikSetting->id)
                ->with('error', 'Failed to update Mikrotik setting: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified Mikrotik setting from storage.
     *
     * @param  \App\Models\MikrotikSetting  $mikrotikSetting
     * @return \Illuminate\Http\Response
     */
    public function destroy(MikrotikSetting $mikrotikSetting)
    {
        // If this is the only setting, don't allow deletion
        if (MikrotikSetting::count() <= 1) {
            return redirect()->route('mikrotik-settings.show', $mikrotikSetting->id)
                ->with('error', 'Cannot delete the only Mikrotik setting. Create another setting first.');
        }

        // If this is the active setting, don't allow deletion
        if ($mikrotikSetting->is_active) {
            return redirect()->route('mikrotik-settings.show', $mikrotikSetting->id)
                ->with('error', 'Cannot delete the active Mikrotik setting. Set another setting as active first.');
        }

        $mikrotikSetting->delete();

        return redirect()->route('mikrotik-settings.index')
            ->with('success', 'Mikrotik setting deleted successfully.');
    }

    /**
     * Set the specified Mikrotik setting as active.
     *
     * @param  \App\Models\MikrotikSetting  $mikrotikSetting
     * @return \Illuminate\Http\Response
     */
    public function setActive(MikrotikSetting $mikrotikSetting)
    {
        // Deactivate all settings
        MikrotikSetting::where('is_active', true)->update(['is_active' => false]);
        
        // Activate this setting
        $mikrotikSetting->is_active = true;
        $mikrotikSetting->save();
        
        return redirect()->route('mikrotik-settings.show', $mikrotikSetting->id)
            ->with('success', 'Mikrotik setting set as active successfully.');
    }

    /**
     * Test connection to the specified Mikrotik setting.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\MikrotikSetting  $mikrotikSetting
     * @return \Illuminate\Http\Response
     */
    public function testConnection(Request $request, MikrotikSetting $mikrotikSetting)
    {
        try {
            // If request has form data (from edit page), use those values temporarily
            if ($request->has('host')) {
                $tempSetting = new MikrotikSetting([
                    'host' => $request->input('host'),
                    'port' => $request->input('port', 8728),
                    'username' => $request->input('username'),
                    'password' => $request->input('password'),
                    'use_ssl' => $request->boolean('use_ssl'),
                ]);
                
                // Create a temporary service with form data
                $tempService = new MikrotikService();
                $tempService->setSetting($tempSetting);
            } else {
                // Use existing setting
                $tempService = new MikrotikService();
                $tempService->setSetting($mikrotikSetting);
            }
            
            $tempService->connect();
            
            // Get system identity as a test
            $identity = $tempService->getSystemIdentity();
            
            // Update last connected timestamp for existing setting
            if (!$request->has('host')) {
                $mikrotikSetting->updateLastConnected();
            }
            
            // Return JSON response for AJAX requests
            if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Connection successful! Connected to '{$identity}'."
                ]);
            }
            
            return redirect()->route('mikrotik-settings.show', $mikrotikSetting->id)
                ->with('success', "Connection test passed! Connected to '{$identity}'.");
                
        } catch (Exception $e) {
            // Return JSON response for AJAX requests
            if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Connection failed: ' . $e->getMessage()
                ]);
            }
            
            return redirect()->route('mikrotik-settings.show', $mikrotikSetting->id)
                ->with('error', 'Connection test failed: ' . $e->getMessage());
        }
    }

    /**
     * Test connection to the specified Mikrotik setting via AJAX.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\MikrotikSetting  $mikrotikSetting
     * @return \Illuminate\Http\JsonResponse
     */
    public function testConnectionAjax(Request $request, MikrotikSetting $mikrotikSetting)
    {
        try {
            logger()->info('AJAX connection test started', [
                'setting_id' => $mikrotikSetting->id,
                'setting_name' => $mikrotikSetting->name,
                'has_form_data' => $request->has('host')
            ]);

            // If request has form data (from edit page), use those values
            if ($request->has('host')) {
                // Get form data
                $host = $request->input('host');
                $port = $request->input('port', '8728');
                $username = $request->input('username');
                $password = $request->input('password');
                $use_ssl = $request->boolean('use_ssl');
                
                logger()->info('Using form data for connection test', [
                    'host' => $host,
                    'port' => $port,
                    'username' => $username,
                    'use_ssl' => $use_ssl
                ]);
                
                // Validate required fields
                if (empty($host) || empty($username)) {
                    logger()->warning('Missing required fields for connection test');
                    return response()->json([
                        'success' => false,
                        'message' => 'Host and Username are required for connection test.'
                    ]);
                }
                
                // Use current password from database if form password is empty
                if (empty($password)) {
                    $password = $mikrotikSetting->password;
                    logger()->info('Using saved password from database');
                }
                
                // Create stdClass object instead of Eloquent model to avoid database operations
                $tempSetting = (object) [
                    'host' => $host,
                    'port' => is_numeric($port) ? $port : '8728', // Keep as string to match migration
                    'username' => $username,
                    'password' => $password,
                    'use_ssl' => $use_ssl,
                ];
                
                // Create a temporary service with form data
                $tempService = new MikrotikService();
                $tempService->setSetting($tempSetting);
            } else {
                // Use existing setting
                logger()->info('Using saved setting for connection test', [
                    'host' => $mikrotikSetting->host,
                    'port' => $mikrotikSetting->port,
                    'username' => $mikrotikSetting->username,
                    'use_ssl' => $mikrotikSetting->use_ssl
                ]);
                
                $tempService = new MikrotikService();
                $tempService->setSetting($mikrotikSetting);
            }
            
            // First test network connectivity
            logger()->info('Testing network connectivity...');
            $tempService->testNetworkConnectivity();
            logger()->info('Network connectivity test passed');
            
            // Then test API connection with fallback to SSL if available
            try {
                logger()->info('Testing API connection...');
                $tempService->connect();
                $identity = $tempService->getSystemIdentity();
                logger()->info('API connection test passed', ['identity' => $identity]);
                
                // Update last connected timestamp for existing setting (only if using saved settings)
                if (!$request->has('host') && $mikrotikSetting instanceof \App\Models\MikrotikSetting) {
                    $mikrotikSetting->updateLastConnected();
                    logger()->info('Updated last connected timestamp');
                }
                
                return response()->json([
                    'success' => true,
                    'message' => "✅ Connection successful! Connected to '{$identity}'.",
                    'details' => [
                        'identity' => $identity,
                        'connection_type' => strpos($mikrotikSetting->host, 'tunnel') !== false ? 'Tunnel/VPN' : 'Direct'
                    ]
                ]);
                
            } catch (Exception $e) {
                logger()->warning('Initial API connection failed', [
                    'error' => $e->getMessage(),
                    'will_try_ssl' => true
                ]);
                
                // If connection failed and we're not using SSL, try SSL as fallback
                $currentSetting = $request->has('host') ? $tempSetting : $mikrotikSetting;
                
                if (!$currentSetting->use_ssl && strpos($e->getMessage(), 'Error reading') !== false) {
                    try {
                        logger()->info('Trying SSL connection as fallback...');
                        
                        // Try with SSL enabled
                        $sslSetting = $request->has('host') ? clone $tempSetting : clone $mikrotikSetting;
                        $sslSetting->use_ssl = true;
                        $sslSetting->port = $sslSetting->port == '8728' ? '8729' : $sslSetting->port;
                        
                        $sslService = new MikrotikService();
                        $sslService->setSetting($sslSetting);
                        $sslService->connect();
                        $identity = $sslService->getSystemIdentity();
                        
                        logger()->info('SSL connection successful', ['identity' => $identity]);
                        
                        return response()->json([
                            'success' => true,
                            'message' => "✅ Connection successful with SSL! Connected to '{$identity}'. 💡 Consider enabling SSL for better stability over tunnel connections.",
                            'suggestion' => 'ssl_recommended',
                            'details' => [
                                'identity' => $identity,
                                'connection_type' => 'SSL',
                                'recommended_port' => $sslSetting->port
                            ]
                        ]);
                        
                    } catch (Exception $sslError) {
                        logger()->error('SSL connection also failed', [
                            'ssl_error' => $sslError->getMessage()
                        ]);
                        // SSL also failed, return original error with enhanced message
                        throw $e;
                    }
                } else {
                    throw $e;
                }
            }
                
        } catch (Exception $e) {
            logger()->error('Connection test failed completely', [
                'error' => $e->getMessage(),
                'setting_id' => $mikrotikSetting->id ?? 'unknown'
            ]);
            
            // Enhanced error message with troubleshooting
            $errorMessage = $e->getMessage();
            $troubleshooting = [];
            
            // Add specific troubleshooting based on error type
            if (strpos($errorMessage, 'Error reading') !== false) {
                $troubleshooting[] = "🔧 Common with tunnel/VPN connections";
                $troubleshooting[] = "🔧 Try enabling SSL connection";
                $troubleshooting[] = "🔧 Check tunnel stability";
            } elseif (strpos($errorMessage, 'timeout') !== false) {
                $troubleshooting[] = "🔧 Check network connectivity";
                $troubleshooting[] = "🔧 Verify firewall settings";
                $troubleshooting[] = "🔧 Try connecting during off-peak hours";
            } elseif (strpos($errorMessage, 'Connection refused') !== false) {
                $troubleshooting[] = "🔧 Enable API service: /ip service enable api";
                $troubleshooting[] = "🔧 Check API port in firewall";
                $troubleshooting[] = "🔧 Verify MikroTik is online";
            }
            
            $fullMessage = "❌ Connection failed: {$errorMessage}";
            if (!empty($troubleshooting)) {
                $fullMessage .= "\n\n💡 Troubleshooting:\n• " . implode("\n• ", $troubleshooting);
            }
            
            return response()->json([
                'success' => false,
                'message' => $fullMessage,
                'error_type' => $this->classifyError($errorMessage),
                'troubleshooting' => $troubleshooting
            ]);
        }
    }

    /**
     * Classify error type for better handling.
     */
    private function classifyError($errorMessage)
    {
        if (strpos($errorMessage, 'Error reading') !== false) {
            return 'read_error';
        } elseif (strpos($errorMessage, 'timeout') !== false) {
            return 'timeout';
        } elseif (strpos($errorMessage, 'Connection refused') !== false) {
            return 'connection_refused';
        } elseif (strpos($errorMessage, 'Authentication') !== false) {
            return 'auth_error';
        } else {
            return 'unknown';
        }
    }

    /**
     * Run connection diagnostics.
     */
    public function diagnostics(Request $request, MikrotikSetting $mikrotikSetting)
    {
        try {
            $tempService = new MikrotikService();
            $tempService->setSetting($mikrotikSetting);
            
            $results = $tempService->runDiagnostics();
            
            return response()->json([
                'success' => true,
                'diagnostics' => $results
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Diagnostics failed: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Display the dashboard with Mikrotik system information.
     *
     * @return \Illuminate\Http\Response
     */
    public function dashboard()
    {
        $setting = MikrotikSetting::getActive();
        $systemInfo = null;
        $activeConnections = null;
        $connectionStatus = 'disconnected';
        
        if ($setting) {
            $connectionStatus = $setting->getConnectionStatus();
            
            // Try to get fresh system info regardless of connection status
            try {
                $this->mikrotikService->setSetting($setting);
                $this->mikrotikService->connect();
                
                // Get basic system info (this is usually fast)
                $systemInfo = [
                    'identity' => $this->mikrotikService->getSystemIdentity(),
                    'resources' => $this->mikrotikService->getSystemResources(),
                ];
                
                // Update connection status to connected since we successfully connected
                $connectionStatus = 'connected';
                $setting->updateLastConnected();
                
                // Try to get active connections with timeout handling
                try {
                    $activeConnections = $this->mikrotikService->getActivePppConnections();
                } catch (Exception $e) {
                    // Log timeout errors for active connections but don't fail
                    logger()->warning('Failed to get active PPP connections for dashboard', [
                        'error' => $e->getMessage(),
                        'setting' => $setting->name
                    ]);
                    $activeConnections = []; // Empty array to indicate no connections could be retrieved
                }
                
            } catch (Exception $e) {
                // Failed to connect, update status
                $connectionStatus = 'failed';
                logger()->error('Failed to connect to MikroTik for dashboard', [
                    'error' => $e->getMessage(),
                    'setting' => $setting ? $setting->name : 'none'
                ]);
            }
        }
        
        return view('mikrotik-settings.dashboard', [
            'mikrotikSetting' => $setting,
            'systemInfo' => $systemInfo,
            'activeConnections' => $activeConnections,
            'connectionStatus' => $connectionStatus
        ]);
    }

    /**
     * Get sync status and statistics.
     */
    public function syncStatus()
    {
        try {
            $stats = $this->mikrotikService->getSyncStatistics();
            
            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get sync status: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Sync all data from MikroTik using REAL Hotspot data.
     */
    public function syncAll(Request $request)
    {
        try {
            // Try REAL PPP data first (profiles and secrets)
            $realPppService = new RealPppSync();
            $results = $realPppService->syncRealPppData();
            
            $message = "✅ REAL PPP Data Sync completed successfully!\n";
            $message .= "📋 Profiles: {$results['profiles']} synced\n";
            $message .= "👤 Secrets: {$results['secrets']} synced\n";
            $message .= "🔗 Active Connections: {$results['active_connections']} found\n";
            $message .= "🌐 Data Source: {$results['source']} (REAL MikroTik PPP data)";
            
            if (isset($results['error'])) {
                $message .= "\n\n⚠️ Note: " . $results['error'];
            }
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'data' => $results
                ]);
            }
            
            return redirect()->back()->with('success', $message);
        } catch (Exception $e) {
            // Fallback: Use HotspotToPppSync if PPP data fails
            try {
                $hotspotService = new HotspotToPppSync();
                $results = $hotspotService->syncRealHotspotDataToPpp();
                
                $message = "⚠️ Fallback to Hotspot data!\n";
                $message .= "📋 Profiles: {$results['profiles_synced']} synced, {$results['profiles_skipped']} skipped\n";
                $message .= "👤 Users: {$results['secrets_synced']} synced, {$results['secrets_skipped']} skipped\n";
                $message .= "🌐 Data Source: {$results['data_source']} (REAL MikroTik Hotspot data)";
                
                if (!empty($results['errors'])) {
                    $message .= "\n\n⚠️ Notes: " . implode(', ', $results['errors']);
                }
                
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => $message,
                        'data' => $results
                    ]);
                }
                
                return redirect()->back()->with('success', $message);
            } catch (Exception $e2) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Sync failed: ' . $e->getMessage()
                    ]);
                }
                
                return redirect()->back()->with('error', 'Sync failed: ' . $e->getMessage());
            }
        }
    }
    
    /**
     * Create demo data to demonstrate sync functionality
     */
    private function createDemoData()
    {
        // Create demo profiles
        $profiles = [
            [
                'name' => 'mikrotik_10mbps',
                'rate_limit' => '10M/10M',
                'description' => 'MikroTik 10Mbps Package - Demo',
                'price' => 100000,
                'is_active' => true,
                'mikrotik_id' => 'demo_profile_1'
            ],
            [
                'name' => 'mikrotik_20mbps',
                'rate_limit' => '20M/20M',
                'description' => 'MikroTik 20Mbps Package - Demo',
                'price' => 175000,
                'is_active' => true,
                'mikrotik_id' => 'demo_profile_2'
            ]
        ];
        
        foreach ($profiles as $profileData) {
            \App\Models\PppProfile::updateOrCreate(
                ['name' => $profileData['name']],
                $profileData
            );
        }
        
        // Get the first profile for secrets
        $defaultProfile = \App\Models\PppProfile::first();
        
        // Create demo secrets
        $secrets = [
            [
                'username' => 'customer_001',
                'password' => 'pass001',
                'service' => 'pppoe',
                'ppp_profile_id' => $defaultProfile->id,
                'comment' => 'Demo Customer 1 - MikroTik Sync',
                'is_active' => true,
                'installation_date' => now(),
                'mikrotik_id' => 'demo_secret_1'
            ],
            [
                'username' => 'customer_002',
                'password' => 'pass002',
                'service' => 'pppoe',
                'ppp_profile_id' => $defaultProfile->id,
                'comment' => 'Demo Customer 2 - MikroTik Sync',
                'is_active' => true,
                'installation_date' => now(),
                'mikrotik_id' => 'demo_secret_2'
            ],
            [
                'username' => 'customer_003',
                'password' => 'pass003',
                'service' => 'pppoe',
                'ppp_profile_id' => $defaultProfile->id,
                'comment' => 'Demo Customer 3 - MikroTik Sync',
                'is_active' => true,
                'installation_date' => now(),
                'mikrotik_id' => 'demo_secret_3'
            ]
        ];
        
        foreach ($secrets as $secretData) {
            \App\Models\PppSecret::updateOrCreate(
                ['username' => $secretData['username']],
                $secretData
            );
        }
    }

    /**
     * Sync only PPP profiles from MikroTik (using REAL Hotspot profiles).
     */
    public function syncProfiles(Request $request)
    {
        try {
            // Try hotspot profiles first (REAL data)
            $hotspotService = new HotspotToPppSync();
            $results = $hotspotService->syncRealHotspotProfiles();
            
            $message = "✅ REAL Profile sync completed! {$results['synced']} synced, {$results['skipped']} skipped from {$results['total_found']} total Hotspot profiles.";
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'data' => $results
                ]);
            }
            
            return redirect()->back()->with('success', $message);
        } catch (Exception $e) {
            // Fallback to robust sync
            try {
                $robustService = new \App\Services\RobustMikrotikSync();
                $results = $robustService->syncPppProfilesRobust();
                
                $message = "⚠️ Fallback profile sync completed! {$results['synced']} synced, {$results['skipped']} skipped from {$results['total']} total profiles.";
                
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => $message,
                        'data' => $results
                    ]);
                }
                
                return redirect()->back()->with('success', $message);
            } catch (Exception $e2) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Profile sync failed: ' . $e->getMessage()
                    ]);
                }
                
                return redirect()->back()->with('error', 'Profile sync failed: ' . $e->getMessage());
            }
        }
    }

    /**
     * Sync only PPP secrets from MikroTik (using REAL Hotspot users).
     */
    public function syncSecrets(Request $request)
    {
        try {
            // Try REAL PPP Secrets first
            $realPppService = new RealPppSync();
            $results = $realPppService->syncRealPppData();
            
            $message = "✅ REAL PPP Secret sync completed! {$results['secrets']} secrets synced from router.";
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'data' => $results
                ]);
            }
            
            return redirect()->back()->with('success', $message);
        } catch (Exception $e) {
            // Fallback to hotspot users if PPP secrets fail
            try {
                $hotspotService = new HotspotToPppSync();
                $results = $hotspotService->syncRealHotspotUsers();
                
                $message = "⚠️ Fallback to Hotspot users! {$results['synced']} synced, {$results['skipped']} skipped from {$results['total_found']} total Hotspot users.";
                
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => $message,
                        'data' => $results
                    ]);
                }
                
                return redirect()->back()->with('success', $message);
            } catch (Exception $e2) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Secret sync failed: ' . $e->getMessage()
                    ]);
                }
                
                return redirect()->back()->with('error', 'Secret sync failed: ' . $e->getMessage());
            }
        }
    }

    /**
     * Push all local profiles to MikroTik.
     */
    public function pushProfiles(Request $request)
    {
        try {
            $profiles = \App\Models\PppProfile::where('is_active', true)->get();
            $pushedCount = 0;
            $errors = [];

            foreach ($profiles as $profile) {
                try {
                    $this->mikrotikService->pushPppProfile($profile);
                    $pushedCount++;
                } catch (Exception $e) {
                    $errors[] = "Profile '{$profile->name}': " . $e->getMessage();
                }
            }

            $message = "Push profiles completed! {$pushedCount} profiles pushed to MikroTik.";
            if (!empty($errors)) {
                $message .= "\n\nErrors:\n• " . implode("\n• ", $errors);
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'data' => [
                        'pushed' => $pushedCount,
                        'total' => $profiles->count(),
                        'errors' => $errors
                    ]
                ]);
            }
            
            return redirect()->back()->with('success', $message);
        } catch (Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Push profiles failed: ' . $e->getMessage()
                ]);
            }
            
            return redirect()->back()->with('error', 'Push profiles failed: ' . $e->getMessage());
        }
    }

    /**
     * Push all local secrets to MikroTik.
     */
    public function pushSecrets(Request $request)
    {
        try {
            $secrets = \App\Models\PppSecret::with('pppProfile')->get();
            $pushedCount = 0;
            $errors = [];

            foreach ($secrets as $secret) {
                try {
                    $this->mikrotikService->pushPppSecret($secret);
                    $pushedCount++;
                } catch (Exception $e) {
                    $errors[] = "Secret '{$secret->username}': " . $e->getMessage();
                }
            }

            $message = "Push secrets completed! {$pushedCount} secrets pushed to MikroTik.";
            if (!empty($errors)) {
                $message .= "\n\nErrors:\n• " . implode("\n• ", $errors);
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'data' => [
                        'pushed' => $pushedCount,
                        'total' => $secrets->count(),
                        'errors' => $errors
                    ]
                ]);
            }
            
            return redirect()->back()->with('success', $message);
        } catch (Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Push secrets failed: ' . $e->getMessage()
                ]);
            }
            
            return redirect()->back()->with('error', 'Push secrets failed: ' . $e->getMessage());
        }
    }

    /**
     * Push all local data to MikroTik.
     */
    public function pushAll(Request $request)
    {
        try {
            $profileResults = [];
            $secretResults = [];

            // Push profiles first
            $profiles = \App\Models\PppProfile::where('is_active', true)->get();
            $pushedProfiles = 0;
            $profileErrors = [];

            foreach ($profiles as $profile) {
                try {
                    $this->mikrotikService->pushPppProfile($profile);
                    $pushedProfiles++;
                } catch (Exception $e) {
                    $profileErrors[] = "Profile '{$profile->name}': " . $e->getMessage();
                }
            }

            // Then push secrets
            $secrets = \App\Models\PppSecret::with('pppProfile')->get();
            $pushedSecrets = 0;
            $secretErrors = [];

            foreach ($secrets as $secret) {
                try {
                    $this->mikrotikService->pushPppSecret($secret);
                    $pushedSecrets++;
                } catch (Exception $e) {
                    $secretErrors[] = "Secret '{$secret->username}': " . $e->getMessage();
                }
            }

            $message = "Push all completed!\n";
            $message .= "Profiles: {$pushedProfiles} pushed from {$profiles->count()} total\n";
            $message .= "Secrets: {$pushedSecrets} pushed from {$secrets->count()} total";

            $allErrors = array_merge($profileErrors, $secretErrors);
            if (!empty($allErrors)) {
                $message .= "\n\nErrors:\n• " . implode("\n• ", $allErrors);
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'data' => [
                        'profiles' => [
                            'pushed' => $pushedProfiles,
                            'total' => $profiles->count(),
                            'errors' => $profileErrors
                        ],
                        'secrets' => [
                            'pushed' => $pushedSecrets,
                            'total' => $secrets->count(),
                            'errors' => $secretErrors
                        ]
                    ]
                ]);
            }
            
            return redirect()->back()->with('success', $message);
        } catch (Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Push all failed: ' . $e->getMessage()
                ]);
            }
            
            return redirect()->back()->with('error', 'Push all failed: ' . $e->getMessage());
        }
    }

    /**
     * Toggle auto-sync for a specific profile.
     */
    public function toggleProfileAutoSync(Request $request, $profileId)
    {
        try {
            $profile = \App\Models\PppProfile::findOrFail($profileId);
            $profile->auto_sync = !$profile->auto_sync;
            $profile->save();

            $status = $profile->auto_sync ? 'enabled' : 'disabled';
            $message = "Auto-sync {$status} for profile '{$profile->name}'.";

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'auto_sync' => $profile->auto_sync
                ]);
            }
            
            return redirect()->back()->with('success', $message);
        } catch (Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to toggle auto-sync: ' . $e->getMessage()
                ]);
            }
            
            return redirect()->back()->with('error', 'Failed to toggle auto-sync: ' . $e->getMessage());
        }
    }

    /**
     * Toggle auto-sync for a specific secret.
     */
    public function toggleSecretAutoSync(Request $request, $secretId)
    {
        try {
            $secret = \App\Models\PppSecret::findOrFail($secretId);
            $secret->auto_sync = !$secret->auto_sync;
            $secret->save();

            $status = $secret->auto_sync ? 'enabled' : 'disabled';
            $message = "Auto-sync {$status} for secret '{$secret->username}'.";

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'auto_sync' => $secret->auto_sync
                ]);
            }
            
            return redirect()->back()->with('success', $message);
        } catch (Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to toggle auto-sync: ' . $e->getMessage()
                ]);
            }
            
            return redirect()->back()->with('error', 'Failed to toggle auto-sync: ' . $e->getMessage());
        }
    }
}