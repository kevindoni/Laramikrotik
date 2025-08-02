<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\PppProfile;
use App\Models\PppSecret;
use App\Services\MikrotikService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PppSecretController extends Controller
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
     * Display a listing of the PPP secrets.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $pppSecrets = PppSecret::with(['customer', 'pppProfile'])
            ->orderBy('username')
            ->paginate(15);

        return view('ppp-secrets.index', compact('pppSecrets'));
    }

    /**
     * Show the form for creating a new PPP secret.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $customers = Customer::orderBy('name')->get();
        $profiles = PppProfile::where('is_active', true)->orderBy('name')->get();
        $customerId = $request->input('customer_id');
        $customer = null;

        if ($customerId) {
            $customer = Customer::find($customerId);
        }

        return view('ppp-secrets.create', compact('customers', 'profiles', 'customer'));
    }

    /**
     * Store a newly created PPP secret in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,id',
            'ppp_profile_id' => 'required|exists:ppp_profiles,id',
            'username' => 'required|string|max:255|unique:ppp_secrets',
            'password' => 'required|string|min:6|max:255',
            'service' => 'required|string|in:pppoe,pptp,l2tp,ovpn',
            'local_address' => 'nullable|string|max:255',
            'remote_address' => 'nullable|string|max:255',
            'comment' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
            'installation_date' => 'nullable|date',
            'due_date' => 'nullable|date',
            'create_invoice' => 'boolean',
            'sync_with_mikrotik' => 'boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->route('ppp-secrets.create')
                ->withErrors($validator)
                ->withInput();
        }

        $data = $validator->validated();
        $createInvoice = $data['create_invoice'] ?? false;
        $syncWithMikrotik = $data['sync_with_mikrotik'] ?? false;
        unset($data['create_invoice'], $data['sync_with_mikrotik']);

        // Set default dates if not provided
        if (empty($data['installation_date'])) {
            $data['installation_date'] = now();
        }

        if (empty($data['due_date'])) {
            $data['due_date'] = Carbon::parse($data['installation_date'])->addMonth();
        }

        $secret = PppSecret::create($data);

        // Create invoice if requested
        if ($createInvoice) {
            $profile = PppProfile::find($data['ppp_profile_id']);
            $customer = Customer::find($data['customer_id']);

            $invoice = new Invoice([
                'customer_id' => $customer->id,
                'ppp_secret_id' => $secret->id,
                'invoice_date' => now(),
                'due_date' => now()->addDays(7),
                'amount' => $profile->price,
                'tax' => 0, // Default tax
                'total_amount' => $profile->price,
                'status' => 'unpaid',
                'notes' => 'Initial installation invoice for ' . $secret->username,
            ]);

            $invoice->invoice_number = Invoice::generateInvoiceNumber();
            $invoice->save();
        }

        // Sync with MikroTik if requested
        if ($syncWithMikrotik) {
            try {
                $this->mikrotikService->connect();
                $this->mikrotikService->createPppSecret($secret);
                $message = 'PPP secret created successfully and synced with MikroTik.';
            } catch (Exception $e) {
                return redirect()->route('ppp-secrets.show', $secret->id)
                    ->with('error', 'PPP secret created but failed to sync with MikroTik: ' . $e->getMessage());
            }
        } else {
            $message = 'PPP secret created successfully.';
        }

        return redirect()->route('ppp-secrets.show', $secret->id)
            ->with('success', $message);
    }

    /**
     * Display the specified PPP secret.
     *
     * @param  \App\Models\PppSecret  $pppSecret
     * @return \Illuminate\Http\Response
     */
    public function show(PppSecret $pppSecret)
    {
        $pppSecret->load(['customer', 'pppProfile', 'invoices', 'usageLogs']);
        
        // Get real-time connection status
        $realTimeStatus = $pppSecret->getRealTimeConnectionStatus();
        
        return view('ppp-secrets.show', [
            'pppSecret' => $pppSecret,
            'realTimeStatus' => $realTimeStatus
        ]);
    }

    /**
     * Show the form for editing the specified PPP secret.
     *
     * @param  \App\Models\PppSecret  $pppSecret
     * @return \Illuminate\Http\Response
     */
    public function edit(PppSecret $pppSecret)
    {
        $customers = Customer::orderBy('name')->get();
        $profiles = PppProfile::where('is_active', true)->orderBy('name')->get();
        
        return view('ppp-secrets.edit', [
            'pppSecret' => $pppSecret,
            'customers' => $customers,
            'profiles' => $profiles
        ]);
    }

    /**
     * Update the specified PPP secret in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\PppSecret  $pppSecret
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, PppSecret $pppSecret)
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,id',
            'ppp_profile_id' => 'required|exists:ppp_profiles,id',
            'username' => 'required|string|max:255|unique:ppp_secrets,username,' . $pppSecret->id,
            'password' => 'required|string|min:6|max:255',
            'service' => 'required|string|in:pppoe,pptp,l2tp,ovpn',
            'local_address' => 'nullable|string|max:255',
            'remote_address' => 'nullable|string|max:255',
            'comment' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
            'installation_date' => 'nullable|date',
            'due_date' => 'nullable|date',
            'sync_with_mikrotik' => 'boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->route('ppp-secrets.edit', $pppSecret->id)
                ->withErrors($validator)
                ->withInput();
        }

        $data = $validator->validated();
        $syncWithMikrotik = $data['sync_with_mikrotik'] ?? false;
        unset($data['sync_with_mikrotik']);

        // Store the old username for MikroTik update
        $oldUsername = $pppSecret->username;
        $oldProfileName = $pppSecret->pppProfile->name;
        $newProfileName = PppProfile::find($data['ppp_profile_id'])->name;
        
        $pppSecret->update($data);

        // Sync with MikroTik if requested
        if ($syncWithMikrotik) {
            try {
                $this->mikrotikService->connect();
                
                // If username changed, we need to delete and recreate
                if ($oldUsername !== $pppSecret->username || $oldProfileName !== $newProfileName) {
                    // Try to delete the old secret
                    try {
                        $tempSecret = clone $pppSecret;
                        $tempSecret->username = $oldUsername;
                        $this->mikrotikService->deletePppSecret($tempSecret);
                    } catch (Exception $e) {
                        // Log but continue if we can't delete the old secret
                        logger()->error('Failed to delete old PPP secret: ' . $e->getMessage());
                    }
                    
                    // Create the new secret
                    $this->mikrotikService->createPppSecret($pppSecret);
                } else {
                    $this->mikrotikService->updatePppSecret($pppSecret);
                }
                
                $message = 'PPP secret updated successfully and synced with MikroTik.';
            } catch (Exception $e) {
                return redirect()->route('ppp-secrets.show', $pppSecret->id)
                    ->with('error', 'PPP secret updated but failed to sync with MikroTik: ' . $e->getMessage());
            }
        } else {
            $message = 'PPP secret updated successfully.';
        }

        return redirect()->route('ppp-secrets.show', $pppSecret->id)
            ->with('success', $message);
    }

    /**
     * Remove the specified PPP secret from storage.
     *
     * @param  \App\Models\PppSecret  $pppSecret
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, PppSecret $pppSecret)
    {
        // Check if secret has invoices
        if ($pppSecret->invoices()->count() > 0) {
            return redirect()->route('ppp-secrets.show', $pppSecret->id)
                ->with('error', 'Cannot delete PPP secret with invoices. Please delete all invoices first.');
        }

        // By default, sync with MikroTik unless explicitly disabled
        $syncWithMikrotik = $request->input('sync_with_mikrotik', true);
        $forceDelete = $request->input('force_delete', false);

        // Delete from MikroTik if sync is enabled
        if ($syncWithMikrotik) {
            try {
                $this->mikrotikService->connect();
                $this->mikrotikService->deletePppSecret($pppSecret);
                
                logger()->info('PPP secret deleted from both database and MikroTik', [
                    'username' => $pppSecret->username,
                    'mikrotik_id' => $pppSecret->mikrotik_id
                ]);
            } catch (Exception $e) {
                logger()->error('Failed to delete PPP secret from MikroTik', [
                    'username' => $pppSecret->username,
                    'error' => $e->getMessage()
                ]);
                
                // If force delete is not enabled, give user options
                if (!$forceDelete) {
                    $errorMessage = 'Failed to delete PPP secret from MikroTik: ' . $e->getMessage();
                    
                    // Check if it's a "not found" or "timeout" error
                    $isNotFound = strpos($e->getMessage(), 'not found') !== false;
                    $isTimeout = strpos($e->getMessage(), 'timeout') !== false || strpos($e->getMessage(), 'slow to respond') !== false;
                    
                    if ($isNotFound || $isTimeout) {
                        $errorMessage .= "\n\nThis could mean:";
                        if ($isNotFound) {
                            $errorMessage .= "\n• Secret was manually deleted from MikroTik router";
                            $errorMessage .= "\n• Secret was never synced to MikroTik";
                        }
                        if ($isTimeout) {
                            $errorMessage .= "\n• MikroTik router is slow to respond";
                            $errorMessage .= "\n• Network connection is unstable";
                        }
                        $errorMessage .= "\n\nYou can still delete from database only by unchecking the MikroTik sync option.";
                    }
                    
                    return redirect()->route('ppp-secrets.show', $pppSecret->id)
                        ->with('error', $errorMessage);
                }
                
                // If force delete is enabled, continue with database delete but log the issue
                logger()->warning('Force deleting PPP secret from database despite MikroTik error', [
                    'username' => $pppSecret->username,
                    'mikrotik_error' => $e->getMessage()
                ]);
            }
        }

        // Delete from database
        $username = $pppSecret->username;
        $pppSecret->delete();

        if ($syncWithMikrotik && !$forceDelete) {
            $message = "PPP secret '{$username}' deleted successfully from both database and MikroTik.";
        } elseif ($syncWithMikrotik && $forceDelete) {
            $message = "PPP secret '{$username}' deleted from database. MikroTik deletion failed but was forced.";
        } else {
            $message = "PPP secret '{$username}' deleted from database only.";
        }

        return redirect()->route('ppp-secrets.index')
            ->with('success', $message);
    }

    /**
     * Enable the specified PPP secret.
     *
     * @param  \App\Models\PppSecret  $pppSecret
     * @return \Illuminate\Http\Response
     */
    public function enable(PppSecret $pppSecret)
    {
        try {
            $this->mikrotikService->connect();
            $this->mikrotikService->enablePppSecret($pppSecret);
            
            return redirect()->route('ppp-secrets.show', $pppSecret->id)
                ->with('success', 'PPP secret enabled successfully.');
        } catch (Exception $e) {
            return redirect()->route('ppp-secrets.show', $pppSecret->id)
                ->with('error', 'Failed to enable PPP secret: ' . $e->getMessage());
        }
    }

    /**
     * Disable the specified PPP secret.
     *
     * @param  \App\Models\PppSecret  $pppSecret
     * @return \Illuminate\Http\Response
     */
    public function disable(PppSecret $pppSecret)
    {
        try {
            $this->mikrotikService->connect();
            $this->mikrotikService->disablePppSecret($pppSecret);
            
            return redirect()->route('ppp-secrets.show', $pppSecret->id)
                ->with('success', 'PPP secret disabled successfully.');
        } catch (Exception $e) {
            return redirect()->route('ppp-secrets.show', $pppSecret->id)
                ->with('error', 'Failed to disable PPP secret: ' . $e->getMessage());
        }
    }

    /**
     * Disconnect the active connection for the specified PPP secret.
     *
     * @param  \App\Models\PppSecret  $pppSecret
     * @return \Illuminate\Http\Response
     */
    public function disconnect(PppSecret $pppSecret)
    {
        try {
            $this->mikrotikService->connect();
            $result = $this->mikrotikService->disconnectPppConnection($pppSecret->username);
            
            if ($result) {
                return redirect()->route('ppp-secrets.show', $pppSecret->id)
                    ->with('success', "Disconnect command sent successfully for user '{$pppSecret->username}'. The user should be disconnected within moments. Note: Due to high router load, status verification may be delayed. Please check the connection status manually if needed.");
            }
            
        } catch (Exception $e) {
            $errorMessage = $e->getMessage();
            
            // Provide user-friendly messages for common scenarios
            if (strpos($errorMessage, 'slow to respond') !== false || strpos($errorMessage, 'timeout') !== false) {
                return redirect()->route('ppp-secrets.show', $pppSecret->id)
                    ->with('warning', "Disconnect command sent but router is responding slowly due to high load. The user may have been disconnected successfully. Please check manually in a few minutes: '/ppp active print' on MikroTik console.");
            } elseif (strpos($errorMessage, 'not found in active connections') !== false) {
                return redirect()->route('ppp-secrets.show', $pppSecret->id)
                    ->with('info', "User '{$pppSecret->username}' was not found in active connections. The user may already be offline or has reconnected since the last check.");
            } elseif (strpos($errorMessage, 'may have been sent') !== false) {
                return redirect()->route('ppp-secrets.show', $pppSecret->id)
                    ->with('warning', "Disconnect command sent but verification failed due to router load. Please check user status manually: '/ppp active print' on MikroTik console.");
            } else {
                return redirect()->route('ppp-secrets.show', $pppSecret->id)
                    ->with('error', 'Failed to disconnect PPP connection: ' . $errorMessage);
            }
        }
        
        return redirect()->route('ppp-secrets.show', $pppSecret->id)
            ->with('error', 'Unexpected error occurred during disconnect operation.');
    }

    /**
     * Generate a random password.
     *
     * @return \Illuminate\Http\Response
     */
    public function generatePassword()
    {
        $password = Str::random(8);
        return response()->json(['password' => $password]);
    }

    /**
     * Generate a unique username.
     *
     * @return \Illuminate\Http\Response
     */
    public function generateUsername()
    {
        // Get the highest existing username number
        $lastSecret = PppSecret::where('username', 'like', 'user%')
            ->orderBy('username', 'desc')
            ->first();

        $nextNumber = 1;
        if ($lastSecret) {
            $matches = [];
            if (preg_match('/user(\d+)/', $lastSecret->username, $matches)) {
                $nextNumber = (int)$matches[1] + 1;
            }
        }

        $username = 'user' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
        
        // Ensure username is unique
        while (PppSecret::where('username', $username)->exists()) {
            $nextNumber++;
            $username = 'user' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
        }

        return response()->json(['username' => $username]);
    }

    /**
     * Sync all PPP secrets with MikroTik.
     *
     * @return \Illuminate\Http\Response
     */
    public function syncAll()
    {
        try {
            $this->mikrotikService->connect();
            $secrets = PppSecret::with('pppProfile')->where('is_active', true)->get();
            $mikrotikSecrets = $this->mikrotikService->getPppSecrets();
            
            // Create array of MikroTik secret usernames for easy lookup
            $mikrotikUsernames = [];
            foreach ($mikrotikSecrets as $secret) {
                $mikrotikUsernames[] = $secret['name'];
            }
            
            $created = 0;
            $updated = 0;
            $errors = [];
            
            foreach ($secrets as $secret) {
                try {
                    if (in_array($secret->username, $mikrotikUsernames)) {
                        $this->mikrotikService->updatePppSecret($secret);
                        $updated++;
                    } else {
                        $this->mikrotikService->createPppSecret($secret);
                        $created++;
                    }
                } catch (Exception $e) {
                    $errors[] = "Failed to sync secret '{$secret->username}': {$e->getMessage()}";
                }
            }
            
            $message = "Sync completed: {$created} secrets created, {$updated} secrets updated.";
            
            if (count($errors) > 0) {
                $message .= " Errors: " . implode("; ", $errors);
                return redirect()->route('ppp-secrets.index')
                    ->with('warning', $message);
            }
            
            return redirect()->route('ppp-secrets.index')
                ->with('success', $message);
                
        } catch (Exception $e) {
            return redirect()->route('ppp-secrets.index')
                ->with('error', 'Failed to sync with MikroTik: ' . $e->getMessage());
        }
    }

    /**
     * Import PPP secrets from MikroTik with enhanced error handling for various edge cases.
     *
     * @return \Illuminate\Http\Response
     */
    public function import()
    {
        try {
            $this->mikrotikService->connect();
            
            $mikrotikSecrets = [];
            $connectionError = null;
            
            // Try multiple approaches to get secrets
            try {
                // First try: Use batching for better reliability
                $mikrotikSecrets = $this->mikrotikService->getAllPppSecrets(10); // Smaller batch
            } catch (Exception $e) {
                $connectionError = $e->getMessage();
                logger()->warning('Batch import failed, trying single query', ['error' => $e->getMessage()]);
                
                try {
                    // Fallback: Try single large query with longer timeout
                    $reflection = new ReflectionClass($this->mikrotikService);
                    $clientProperty = $reflection->getProperty('client');
                    $clientProperty->setAccessible(true);
                    $client = $clientProperty->getValue($this->mikrotikService);
                    
                    if ($client) {
                        $query = new \RouterOS\Query('/ppp/secret/print');
                        $mikrotikSecrets = $client->query($query)->read();
                        logger()->info('Fallback single query succeeded', ['count' => count($mikrotikSecrets)]);
                    }
                } catch (Exception $fallbackError) {
                    logger()->error('Both batch and fallback failed', [
                        'batch_error' => $connectionError,
                        'fallback_error' => $fallbackError->getMessage()
                    ]);
                    throw new Exception("Unable to retrieve secrets from MikroTik. Batch error: {$connectionError}. Fallback error: {$fallbackError->getMessage()}");
                }
            }
            
            // Validate response
            if (!is_array($mikrotikSecrets)) {
                throw new Exception('Invalid response from MikroTik: Expected array, got ' . gettype($mikrotikSecrets));
            }
            
            if (empty($mikrotikSecrets)) {
                return redirect()->route('ppp-secrets.index')
                    ->with('warning', 'No PPP secrets found on MikroTik router. The router may be empty or connection may be unstable.');
            }
            
            logger()->info('Retrieved secrets for import', [
                'count' => count($mikrotikSecrets),
                'first_secret_keys' => count($mikrotikSecrets) > 0 ? array_keys($mikrotikSecrets[0]) : [],
                'sample_secret' => count($mikrotikSecrets) > 0 ? $mikrotikSecrets[0] : null
            ]);
            
            $profiles = PppProfile::all()->keyBy('name');
            
            $created = 0;
            $updated = 0;
            $skipped = 0;
            $errors = [];
            
            foreach ($mikrotikSecrets as $index => $mikrotikSecret) {
                try {
                    // Validate that this is a proper array with expected structure
                    if (!is_array($mikrotikSecret)) {
                        $errors[] = "Skipped entry #{$index}: Not an array (got " . gettype($mikrotikSecret) . ")";
                        $skipped++;
                        continue;
                    }
                    
                    // Validate required fields exist
                    if (!isset($mikrotikSecret['name']) || empty($mikrotikSecret['name'])) {
                        $availableKeys = array_keys($mikrotikSecret);
                        $errors[] = "Skipped entry #{$index}: Missing 'name' field. Available keys: " . implode(', ', $availableKeys);
                        $skipped++;
                        continue;
                    }
                    
                    $username = $mikrotikSecret['name'];
                    $secret = PppSecret::where('username', $username)->first();
                    
                    // Skip if profile doesn't exist in our database
                    $profileName = $mikrotikSecret['profile'] ?? 'default';
                    if (!isset($profiles[$profileName])) {
                        $errors[] = "Skipped secret '{$username}': Profile '{$profileName}' not found in database.";
                        $skipped++;
                        continue;
                    }
                    
                    $data = [
                        'username' => $username,
                        'password' => $mikrotikSecret['password'] ?? 'password', // Default password
                        'ppp_profile_id' => $profiles[$profileName]->id,
                        'service' => $mikrotikSecret['service'] ?? 'pppoe',
                        'local_address' => $mikrotikSecret['local-address'] ?? null,
                        'remote_address' => $mikrotikSecret['remote-address'] ?? null,
                        'is_active' => ($mikrotikSecret['disabled'] ?? 'no') !== 'yes',
                        'comment' => $mikrotikSecret['comment'] ?? null,
                        'installation_date' => now(),
                        'due_date' => now()->addMonth(),
                        'mikrotik_id' => $mikrotikSecret['.id'] ?? null,
                    ];
                    
                    if ($secret) {
                        // Update existing secret
                        $secret->update($data);
                        $updated++;
                    } else {
                        // Create new secret - need a customer
                        // For import, we'll create a temporary customer if needed
                        $tempCustomer = Customer::firstOrCreate(
                            ['name' => 'Imported Customer'],
                            [
                                'address' => 'Imported from MikroTik',
                                'is_active' => true,
                                'registered_date' => now(),
                            ]
                        );
                        
                        $data['customer_id'] = $tempCustomer->id;
                        PppSecret::create($data);
                        $created++;
                    }
                } catch (Exception $e) {
                    $secretInfo = 'Unknown secret';
                    if (is_array($mikrotikSecret)) {
                        if (isset($mikrotikSecret['name'])) {
                            $secretInfo = "'{$mikrotikSecret['name']}'";
                        } else {
                            $secretInfo = 'Secret with keys: ' . implode(', ', array_keys($mikrotikSecret));
                        }
                    } else {
                        $secretInfo = 'Non-array data: ' . gettype($mikrotikSecret);
                    }
                    
                    $errors[] = "Failed to import {$secretInfo}: {$e->getMessage()}";
                    logger()->error('Secret import failed', [
                        'secret_data' => $mikrotikSecret,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }
            
            $message = "Import completed: {$created} secrets created, {$updated} secrets updated, {$skipped} secrets skipped.";
            
            if (count($errors) > 0) {
                $message .= " Errors: " . implode("; ", array_slice($errors, 0, 3)); // Show first 3 errors
                if (count($errors) > 3) {
                    $message .= " and " . (count($errors) - 3) . " more errors.";
                }
                return redirect()->route('ppp-secrets.index')
                    ->with('warning', $message);
            }
            
            return redirect()->route('ppp-secrets.index')
                ->with('success', $message);
                
        } catch (Exception $e) {
            $errorMessage = 'Failed to import from MikroTik: ' . $e->getMessage();
            
            // Provide more helpful error messages for common issues
            if (strpos($e->getMessage(), 'timeout') !== false || 
                strpos($e->getMessage(), 'Stream timed out') !== false) {
                $errorMessage .= '\n\nThis error usually occurs when the MikroTik router is slow to respond or has many secrets. Try again later when the router is less busy.';
            }
            
            logger()->error('PPP secrets import failed completely', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('ppp-secrets.index')
                ->with('error', $errorMessage);
        }
    }

    /**
     * Sync active connections with the database.
     *
     * @return \Illuminate\Http\Response
     */
    public function syncActiveConnections()
    {
        try {
            $this->mikrotikService->connect();
            $this->mikrotikService->syncActivePppConnections();
            
            return redirect()->route('ppp-secrets.index')
                ->with('success', 'Active connections synced successfully.');
                
        } catch (Exception $e) {
            return redirect()->route('ppp-secrets.index')
                ->with('error', 'Failed to sync active connections: ' . $e->getMessage());
        }
    }

    /**
     * Display active PPP connections.
     *
     * @return \Illuminate\Http\Response
     */
    public function activeConnections()
    {
        try {
            $this->mikrotikService->connect();
            $activeConnections = $this->mikrotikService->getActivePppConnections();
            
            // Get usernames to find matching secrets
            $usernames = [];
            foreach ($activeConnections as $connection) {
                $usernames[] = $connection['name'];
            }
            
            // Get secrets for these connections
            $secrets = PppSecret::with(['customer', 'pppProfile'])
                ->whereIn('username', $usernames)
                ->get()
                ->keyBy('username');
            
            return view('ppp-secrets.active', [
                'activeConnections' => $activeConnections,
                'secrets' => $secrets
            ]);
                
        } catch (Exception $e) {
            return redirect()->route('ppp-secrets.index')
                ->with('error', 'Failed to get active connections: ' . $e->getMessage());
        }
    }

    /**
     * Sync secrets from MikroTik to local database.
     *
     * @return \Illuminate\Http\Response
     */
    public function syncFromMikrotik()
    {
        return $this->import();
    }

    /**
     * Sync individual secret to MikroTik.
     *
     * @param  \App\Models\PppSecret  $pppSecret
     * @return \Illuminate\Http\Response
     */
    public function syncToMikrotik(PppSecret $pppSecret)
    {
        try {
            $this->mikrotikService->connect();
            
            // Check if secret exists on MikroTik
            $mikrotikSecrets = $this->mikrotikService->getPppSecrets();
            $exists = false;
            
            foreach ($mikrotikSecrets as $secret) {
                if ($secret['name'] === $pppSecret->username) {
                    $exists = true;
                    break;
                }
            }
            
            if ($exists) {
                $this->mikrotikService->updatePppSecret($pppSecret);
                $message = 'PPP secret updated on MikroTik successfully.';
            } else {
                $this->mikrotikService->createPppSecret($pppSecret);
                $message = 'PPP secret created on MikroTik successfully.';
            }
            
            return redirect()->route('ppp-secrets.show', $pppSecret)
                ->with('success', $message);
                
        } catch (Exception $e) {
            return redirect()->route('ppp-secrets.show', $pppSecret)
                ->with('error', 'Failed to sync secret to MikroTik: ' . $e->getMessage());
        }
    }

    /**
     * Bulk delete selected PPP secrets.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function bulkDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'selected_secrets' => 'required|array|min:1',
            'selected_secrets.*' => 'required|integer|exists:ppp_secrets,id',
            'sync_with_mikrotik' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->route('ppp-secrets.index')
                ->with('error', 'Invalid selection. Please select valid secrets.');
        }

        $secretIds = $request->input('selected_secrets');
        $syncWithMikrotik = $request->input('sync_with_mikrotik', true);
        $secrets = PppSecret::whereIn('id', $secretIds)->get();
        
        $deleted = 0;
        $skipped = 0;
        $errors = [];
        $mikrotikErrors = [];

        foreach ($secrets as $secret) {
            try {
                // Check if secret has invoices
                if ($secret->invoices()->count() > 0) {
                    $skipped++;
                    $errors[] = "Secret '{$secret->username}' skipped - has invoices";
                    continue;
                }

                // Delete from MikroTik if sync is enabled
                if ($syncWithMikrotik) {
                    try {
                        $this->mikrotikService->connect();
                        $this->mikrotikService->deletePppSecret($secret);
                    } catch (Exception $e) {
                        $mikrotikErrors[] = "Failed to delete '{$secret->username}' from MikroTik: {$e->getMessage()}";
                        // Continue to delete from database even if MikroTik fails
                    }
                }

                $secret->delete();
                $deleted++;
            } catch (Exception $e) {
                $errors[] = "Failed to delete secret '{$secret->username}': {$e->getMessage()}";
            }
        }

        $message = "Bulk delete completed: {$deleted} secrets deleted";
        if ($syncWithMikrotik) {
            $message .= " from both database and MikroTik";
        } else {
            $message .= " from database only";
        }
        
        if ($skipped > 0) {
            $message .= ", {$skipped} secrets skipped";
        }

        $allErrors = array_merge($errors, $mikrotikErrors);
        if (count($allErrors) > 0) {
            $message .= ". Issues: " . implode("; ", $allErrors);
            return redirect()->route('ppp-secrets.index')
                ->with('warning', $message);
        }

        return redirect()->route('ppp-secrets.index')
            ->with('success', $message);
    }

    /**
     * Bulk enable selected PPP secrets.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function bulkEnable(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'selected_secrets' => 'required|array|min:1',
            'selected_secrets.*' => 'required|integer|exists:ppp_secrets,id',
        ]);

        if ($validator->fails()) {
            return redirect()->route('ppp-secrets.index')
                ->with('error', 'Invalid selection. Please select valid secrets.');
        }

        try {
            $this->mikrotikService->connect();
            
            $secretIds = $request->input('selected_secrets');
            $secrets = PppSecret::whereIn('id', $secretIds)->get();
            
            $enabled = 0;
            $errors = [];

            foreach ($secrets as $secret) {
                try {
                    $this->mikrotikService->enablePppSecret($secret);
                    $secret->update(['is_active' => true]);
                    $enabled++;
                } catch (Exception $e) {
                    $errors[] = "Failed to enable secret '{$secret->username}': {$e->getMessage()}";
                }
            }

            $message = "Bulk enable completed: {$enabled} secrets enabled";

            if (count($errors) > 0) {
                $message .= ". Errors: " . implode("; ", $errors);
                return redirect()->route('ppp-secrets.index')
                    ->with('warning', $message);
            }

            return redirect()->route('ppp-secrets.index')
                ->with('success', $message);

        } catch (Exception $e) {
            return redirect()->route('ppp-secrets.index')
                ->with('error', 'Failed to connect to MikroTik: ' . $e->getMessage());
        }
    }

    /**
     * Bulk disable selected PPP secrets.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function bulkDisable(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'selected_secrets' => 'required|array|min:1',
            'selected_secrets.*' => 'required|integer|exists:ppp_secrets,id',
        ]);

        if ($validator->fails()) {
            return redirect()->route('ppp-secrets.index')
                ->with('error', 'Invalid selection. Please select valid secrets.');
        }

        try {
            $this->mikrotikService->connect();
            
            $secretIds = $request->input('selected_secrets');
            $secrets = PppSecret::whereIn('id', $secretIds)->get();
            
            $disabled = 0;
            $errors = [];

            foreach ($secrets as $secret) {
                try {
                    $this->mikrotikService->disablePppSecret($secret);
                    $secret->update(['is_active' => false]);
                    $disabled++;
                } catch (Exception $e) {
                    $errors[] = "Failed to disable secret '{$secret->username}': {$e->getMessage()}";
                }
            }

            $message = "Bulk disable completed: {$disabled} secrets disabled";

            if (count($errors) > 0) {
                $message .= ". Errors: " . implode("; ", $errors);
                return redirect()->route('ppp-secrets.index')
                    ->with('warning', $message);
            }

            return redirect()->route('ppp-secrets.index')
                ->with('success', $message);

        } catch (Exception $e) {
            return redirect()->route('ppp-secrets.index')
                ->with('error', 'Failed to connect to MikroTik: ' . $e->getMessage());
        }
    }

    /**
     * Bulk sync selected PPP secrets to MikroTik.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function bulkSync(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'selected_secrets' => 'required|array|min:1',
            'selected_secrets.*' => 'required|integer|exists:ppp_secrets,id',
        ]);

        if ($validator->fails()) {
            return redirect()->route('ppp-secrets.index')
                ->with('error', 'Invalid selection. Please select valid secrets.');
        }

        try {
            $this->mikrotikService->connect();
            
            $secretIds = $request->input('selected_secrets');
            $secrets = PppSecret::whereIn('id', $secretIds)->get();
            
            // Get existing MikroTik secrets
            $mikrotikSecrets = $this->mikrotikService->getPppSecrets();
            $mikrotikUsernames = array_column($mikrotikSecrets, 'name');
            
            $created = 0;
            $updated = 0;
            $errors = [];

            foreach ($secrets as $secret) {
                try {
                    if (in_array($secret->username, $mikrotikUsernames)) {
                        $this->mikrotikService->updatePppSecret($secret);
                        $updated++;
                    } else {
                        $this->mikrotikService->createPppSecret($secret);
                        $created++;
                    }
                } catch (Exception $e) {
                    $errors[] = "Failed to sync secret '{$secret->username}': {$e->getMessage()}";
                }
            }

            $message = "Bulk sync completed: {$created} secrets created, {$updated} secrets updated on MikroTik";

            if (count($errors) > 0) {
                $message .= ". Errors: " . implode("; ", $errors);
                return redirect()->route('ppp-secrets.index')
                    ->with('warning', $message);
            }

            return redirect()->route('ppp-secrets.index')
                ->with('success', $message);

        } catch (Exception $e) {
            return redirect()->route('ppp-secrets.index')
                ->with('error', 'Failed to connect to MikroTik: ' . $e->getMessage());
        }
    }
}