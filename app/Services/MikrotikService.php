<?php

namespace App\Services;

use App\Models\MikrotikSetting;
use App\Models\PppProfile;
use App\Models\PppSecret;
use App\Models\UsageLog;
use Exception;
use RouterOS\Client;
use RouterOS\Config;
use RouterOS\Query;

class MikrotikService
{
    protected $client;
    protected $setting;

    /**
     * Create a new Mikrotik service instance.
     */
    public function __construct()
    {
        // Don't load setting in constructor to avoid database calls during app boot
        // Call loadActiveSetting() method when needed instead
    }

    /**
     * Load active MikroTik setting.
     */
    public function loadActiveSetting()
    {
        if (!$this->setting) {
            $this->setting = MikrotikSetting::getActive();
        }
        return $this->setting;
    }

    /**
     * Set the MikroTik setting.
     */
    public function setSetting($setting)
    {
        $this->setting = $setting;
        return $this;
    }

    /**
     * Connect to the Mikrotik router.
     */
    public function connect()
    {
        if (!$this->setting) {
            $this->loadActiveSetting();
        }
        
        if (!$this->setting) {
            throw new Exception('No active Mikrotik setting found.');
        }

        try {
            $port = $this->setting->port ?: ($this->setting->use_ssl ? 8729 : 8728);
            
            $config = new Config([
                'host' => $this->setting->host,
                'user' => $this->setting->username,
                'pass' => $this->setting->password,
                'port' => (int) $port,
                'timeout' => 60, // Increased timeout to 60 seconds for tunnel connections
                'attempts' => 1,  // Single attempt to avoid RouterOS library auto-retry
                'delay' => 2,     // Increased delay between attempts for slow connections
                'socket_timeout' => 60, // Socket timeout for slow tunnel connections
            ]);

            if ($this->setting->use_ssl) {
                $config->set('ssl', true);
                $config->set('ssl_options', [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                ]);
            }

            // Manual retry logic with better error handling for tunnel connections
            $lastException = null;
            $maxAttempts = 5; // Increased attempts for unstable connections
            
            for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
                try {
                    // Reset client for each attempt
                    $this->client = null;
                    
                    // Add longer delay for tunnel connections after first attempt
                    if ($attempt > 1) {
                        logger()->info("MikroTik connection attempt {$attempt}/{$maxAttempts} - waiting before retry", [
                            'host' => $this->setting->host,
                            'delay' => $attempt * 3
                        ]);
                        sleep($attempt * 3); // 3s, 6s, 9s, 12s delays
                    }
                    
                    // Create new client instance with enhanced socket options
                    $this->client = new Client($config);
                    
                    // Test the connection with a very simple query first
                    logger()->info("Testing basic API connectivity", ['attempt' => $attempt]);
                    
                    // Try system identity first (minimal data)
                    $query = new Query('/system/identity/print');
                    $result = $this->client->query($query)->read();
                    
                    // Verify we got a valid response
                    if (empty($result) || !is_array($result)) {
                        throw new Exception('Invalid response from MikroTik router');
                    }
                    
                    // Additional validation - try to read system clock (another minimal query)
                    logger()->info("Testing extended API connectivity", ['attempt' => $attempt]);
                    $clockQuery = new Query('/system/clock/print');
                    $clockResult = $this->client->query($clockQuery)->read();
                    
                    if (empty($clockResult) || !is_array($clockResult)) {
                        throw new Exception('API connection unstable - extended test failed');
                    }
                    
                    logger()->info("MikroTik connection successful", [
                        'host' => $this->setting->host,
                        'attempt' => $attempt,
                        'identity' => $result[0]['name'] ?? 'unknown'
                    ]);
                    
                    // Only update last connected timestamp if it's an actual model
                    if ($this->setting instanceof MikrotikSetting) {
                        $this->setting->updateLastConnected();
                    }
                    
                    return true;
                    
                } catch (Exception $e) {
                    $lastException = $e;
                    $this->client = null; // Reset client on failure
                    
                    $errorMsg = $e->getMessage();
                    
                    // Log attempt details for debugging with specific error analysis
                    logger()->warning("MikroTik connection attempt {$attempt}/{$maxAttempts} failed", [
                        'host' => $this->setting->host,
                        'port' => $port,
                        'error' => $errorMsg,
                        'attempt' => $attempt,
                        'error_type' => $this->analyzeErrorType($errorMsg)
                    ]);
                    
                    // For "Error reading X bytes" - this is often a timing issue with tunnels
                    if (strpos($errorMsg, 'Error reading') !== false && $attempt < $maxAttempts) {
                        logger()->info("Detected 'Error reading' issue - will retry with longer delay", [
                            'attempt' => $attempt,
                            'next_delay' => ($attempt + 1) * 3
                        ]);
                        continue;
                    }
                    
                    if ($attempt < $maxAttempts) {
                        continue;
                    }
                }
            }
            
            // All attempts failed, throw the last exception
            throw $lastException;
            
        } catch (Exception $e) {
            $errorMessage = 'Failed to connect to Mikrotik: ' . $e->getMessage();
            
            // Add specific troubleshooting information based on error type
            if (strpos($e->getMessage(), 'Connection refused') !== false) {
                $errorMessage .= "\n\nTroubleshooting:\n";
                $errorMessage .= "• Check if MikroTik API service is enabled: /ip service enable api\n";
                $errorMessage .= "• Verify the API port (default 8728) is not blocked by firewall\n";
                $errorMessage .= "• Ensure the MikroTik device is powered on and accessible";
                
            } elseif (strpos($e->getMessage(), 'Error reading') !== false) {
                $errorMessage .= "\n\nTroubleshooting for 'Error reading' issues:\n";
                $errorMessage .= "• This is common with tunnel/VPN connections to MikroTik\n";
                $errorMessage .= "• Try connecting directly via local IP if possible\n";
                $errorMessage .= "• Check tunnel stability: ping " . $this->setting->host . " -t\n";
                $errorMessage .= "• Verify MikroTik is not overloaded (check CPU usage)\n";
                $errorMessage .= "• Consider using SSL connection if available\n";
                $errorMessage .= "• Try connecting during off-peak hours\n";
                $errorMessage .= "• Check if MikroTik API has connection limits configured";
                
            } elseif (strpos($e->getMessage(), 'timeout') !== false || 
                     strpos($e->getMessage(), 'failed to respond') !== false ||
                     strpos($e->getMessage(), 'Unable to establish socket') !== false ||
                     strpos($e->getMessage(), 'Stream timed out') !== false) {
                $errorMessage .= "\n\nTroubleshooting:\n";
                $errorMessage .= "• Check network connectivity to " . $this->setting->host . "\n";
                $errorMessage .= "• Verify firewall settings on both client and MikroTik\n";
                $errorMessage .= "• Try pinging the host: ping " . $this->setting->host . "\n";
                $errorMessage .= "• Check if port " . $port . " is open: telnet " . $this->setting->host . " " . $port . "\n";
                $errorMessage .= "• Consider using VPN if connecting over internet\n";
                $errorMessage .= "• MikroTik may be overloaded - try again later";
                
            } elseif (strpos($e->getMessage(), 'Authentication failed') !== false || 
                     strpos($e->getMessage(), 'invalid user name or password') !== false) {
                $errorMessage .= "\n\nTroubleshooting:\n";
                $errorMessage .= "• Verify username and password are correct\n";
                $errorMessage .= "• Check if user has API permissions\n";
                $errorMessage .= "• Ensure user account is not disabled";
                
            } else {
                $errorMessage .= "\n\nTroubleshooting:\n";
                $errorMessage .= "• Verify MikroTik device is online and accessible\n";
                $errorMessage .= "• Check network configuration and routing\n";
                $errorMessage .= "• Ensure API service is running on MikroTik";
            }
            
            $errorMessage .= "\n\nConnection Details:";
            $errorMessage .= "\n• Host: " . $this->setting->host;
            $errorMessage .= "\n• Port: " . $port;
            $errorMessage .= "\n• SSL: " . ($this->setting->use_ssl ? 'yes' : 'no');
            $errorMessage .= "\n• Username: " . $this->setting->username;
            $errorMessage .= "\n• Connection Type: " . (strpos($this->setting->host, 'tunnel') !== false ? 'Tunnel/VPN' : 'Direct');
            
            throw new Exception($errorMessage);
        }
    }

    /**
     * Analyze error type for better logging and troubleshooting.
     */
    private function analyzeErrorType($errorMessage)
    {
        if (strpos($errorMessage, 'Error reading') !== false) {
            return 'API_READ_ERROR';
        } elseif (strpos($errorMessage, 'timeout') !== false) {
            return 'TIMEOUT_ERROR';
        } elseif (strpos($errorMessage, 'Connection refused') !== false) {
            return 'CONNECTION_REFUSED';
        } elseif (strpos($errorMessage, 'Authentication failed') !== false) {
            return 'AUTH_ERROR';
        } else {
            return 'UNKNOWN_ERROR';
        }
    }

    /**
     * Check if connected to the Mikrotik router.
     */
    public function isConnected()
    {
        return $this->client !== null;
    }

    /**
     * Get the RouterOS client instance.
     * 
     * @return \RouterOS\Client|null
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Test basic network connectivity to MikroTik host.
     */
    public function testNetworkConnectivity()
    {
        if (!$this->setting) {
            throw new Exception('No active Mikrotik setting found.');
        }

        $host = $this->setting->host;
        $port = $this->setting->port ?: ($this->setting->use_ssl ? 8729 : 8728);
        
        // Test if host is reachable
        $connection = @fsockopen($host, $port, $errno, $errstr, 10);
        
        if (!$connection) {
            $errorMsg = "Cannot reach {$host}:{$port}";
            if ($errno && $errstr) {
                $errorMsg .= " - Error {$errno}: {$errstr}";
            }
            
            // Add specific suggestions based on common errors
            $suggestions = [];
            if ($errno == 10060 || strpos($errstr, 'timeout') !== false) {
                $suggestions[] = "Connection timeout - check firewall settings";
                $suggestions[] = "Verify MikroTik is powered on and accessible";
            } elseif ($errno == 10061 || strpos($errstr, 'refused') !== false) {
                $suggestions[] = "Connection refused - API service may be disabled";
                $suggestions[] = "Check: /ip service enable api";
            }
            
            if (!empty($suggestions)) {
                $errorMsg .= "\n\nSuggestions:\n• " . implode("\n• ", $suggestions);
            }
            
            throw new Exception($errorMsg);
        }
        
        fclose($connection);
        return true;
    }

    /**
     * Perform connection diagnostics.
     */
    public function runDiagnostics()
    {
        $results = [];
        
        if (!$this->setting) {
            $results['error'] = 'No active MikroTik setting found';
            return $results;
        }

        $host = $this->setting->host;
        $port = $this->setting->port ?: ($this->setting->use_ssl ? 8729 : 8728);
        
        // Test 1: Basic network connectivity
        try {
            $this->testNetworkConnectivity();
            $results['network'] = 'OK - Host is reachable';
        } catch (Exception $e) {
            $results['network'] = 'FAILED - ' . $e->getMessage();
        }
        
        // Test 2: DNS resolution (if host is not IP)
        if (!filter_var($host, FILTER_VALIDATE_IP)) {
            $ip = gethostbyname($host);
            if ($ip === $host) {
                $results['dns'] = 'FAILED - Cannot resolve hostname';
            } else {
                $results['dns'] = "OK - Resolved to {$ip}";
            }
        } else {
            $results['dns'] = 'N/A - Using IP address';
        }
        
        // Test 3: Port accessibility
        $connection = @fsockopen($host, $port, $errno, $errstr, 5);
        if ($connection) {
            $results['port'] = "OK - Port {$port} is open";
            fclose($connection);
        } else {
            $results['port'] = "FAILED - Port {$port} is not accessible";
        }
        
        // Test 4: API connection attempt
        try {
            $this->connect();
            $results['api'] = 'OK - API connection successful';
        } catch (Exception $e) {
            $results['api'] = 'FAILED - ' . $e->getMessage();
        }
        
        return $results;
    }

    /**
     * Ensure connection to the Mikrotik router.
     */
    protected function ensureConnected()
    {
        if (!$this->isConnected()) {
            $this->connect();
        }
    }

    /**
     * Get all PPP profiles from MikroTik with enhanced timeout handling.
     */
    public function getPppProfiles()
    {
        // Ensure we're connected first
        if (!$this->isConnected()) {
            $this->connect();
        }
        
        $maxAttempts = 3;
        $lastException = null;
        
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                // Ensure we still have a client connection
                if (!$this->client) {
                    $this->connect();
                }
                
                // Set a longer timeout for data retrieval operations
                if ($this->client && method_exists($this->client, 'setTimeout')) {
                    $this->client->setTimeout(120); // 2 minutes timeout for profile retrieval
                }
                
                logger()->info("Attempting to retrieve PPP profiles", [
                    'attempt' => $attempt,
                    'host' => $this->setting->host ?? 'unknown',
                    'timeout' => 120
                ]);
                
                $query = new Query('/ppp/profile/print');
                $profiles = $this->client->query($query)->read();
                
                // Log successful retrieval for debugging
                logger()->info('Successfully retrieved PPP profiles from MikroTik', [
                    'count' => count($profiles),
                    'host' => $this->setting->host ?? 'unknown',
                    'attempt' => $attempt
                ]);
                
                return $profiles;
                
            } catch (Exception $e) {
                $lastException = $e;
                
                // Log the specific error for debugging
                logger()->error('Failed to get PPP profiles from MikroTik', [
                    'error' => $e->getMessage(),
                    'host' => $this->setting->host ?? 'unknown',
                    'timeout' => 120,
                    'attempt' => $attempt
                ]);
                
                // If it's a timeout or stream error and we have more attempts, retry with new connection
                if (($attempt < $maxAttempts) && 
                    (strpos($e->getMessage(), 'timeout') !== false || 
                     strpos($e->getMessage(), 'Stream timed out') !== false ||
                     strpos($e->getMessage(), 'Error reading') !== false)) {
                    
                    logger()->info("Data retrieval failed, reconnecting for retry", [
                        'attempt' => $attempt,
                        'next_attempt' => $attempt + 1,
                        'delay' => $attempt * 5
                    ]);
                    
                    // Reset connection and wait before retry
                    $this->client = null;
                    sleep($attempt * 5); // 5s, 10s delays
                    
                    // Don't try to reconnect here - let it happen at the start of next iteration
                    continue;
                }
                
                break; // Exit loop for non-retryable errors
            }
        }
        
        // All attempts failed
        $errorMsg = $lastException->getMessage();
        
        // Provide more specific error message for timeout issues
        if (strpos($errorMsg, 'timeout') !== false || 
            strpos($errorMsg, 'Stream timed out') !== false) {
            throw new Exception('Failed to get PPP profiles: Connection timed out after ' . $maxAttempts . ' attempts. The MikroTik router may be slow to respond or overloaded. Please try again later.');
        }
        
        throw new Exception('Failed to get PPP profiles: ' . $errorMsg);
    }

    /**
     * Get all PPP secrets from MikroTik with enhanced timeout handling and batching support.
     */
    public function getPppSecrets()
    {
        // Use the batching method for better reliability
        return $this->getAllPppSecrets(20);
    }

    /**
     * Get PPP secrets from MikroTik with pagination
     */
    public function getPppSecretsBatch($start = 0, $count = 20)
    {
        // Ensure we're connected first
        if (!$this->isConnected()) {
            $this->connect();
        }

        try {
            // Ensure we still have a client connection
            if (!$this->client) {
                $this->connect();
            }
            
            // Set a moderate timeout for batch requests
            if ($this->client && method_exists($this->client, 'setTimeout')) {
                $this->client->setTimeout(45); // 45 seconds timeout for batch
            }
            
            logger()->info("Attempting to retrieve PPP secrets batch", [
                'start' => $start,
                'count' => $count,
                'host' => $this->setting->host ?? 'unknown'
            ]);
            
            $query = new Query('/ppp/secret/print');
            // Note: RouterOS API for /ppp/secret/print doesn't support count/from parameters
            // We'll get all secrets and handle pagination in PHP if needed
            
            $secrets = $this->client->query($query)->read();
            
            // Debug log the structure of the first secret for troubleshooting
            if (!empty($secrets) && is_array($secrets)) {
                logger()->info('PPP secrets batch structure debug', [
                    'first_secret_keys' => array_keys($secrets[0] ?? []),
                    'first_secret_sample' => $secrets[0] ?? null,
                    'total_retrieved' => count($secrets)
                ]);
            }
            
            // Validate the response structure
            $validSecrets = [];
            foreach ($secrets as $secret) {
                if (!is_array($secret)) {
                    logger()->warning('Invalid secret structure - not an array', [
                        'secret_data' => $secret,
                        'secret_type' => gettype($secret)
                    ]);
                    continue;
                }
                
                if (!isset($secret['name']) || empty($secret['name'])) {
                    logger()->warning('Secret missing name field', [
                        'secret_keys' => array_keys($secret),
                        'secret_data' => $secret
                    ]);
                    continue;
                }
                
                $validSecrets[] = $secret;
            }
            
            logger()->info('Successfully retrieved PPP secrets batch', [
                'total_raw' => count($secrets),
                'valid_secrets' => count($validSecrets),
                'start' => $start,
                'requested' => $count,
                'host' => $this->setting->host ?? 'unknown'
            ]);
            
            return $validSecrets;
            
        } catch (Exception $e) {
            logger()->error('Failed to get PPP secrets batch', [
                'error' => $e->getMessage(),
                'start' => $start,
                'count' => $count,
                'host' => $this->setting->host ?? 'unknown',
                'trace' => $e->getTraceAsString()
            ]);
            
            throw new Exception('Failed to get PPP secrets batch: ' . $e->getMessage());
        }
    }

    /**
     * Get all PPP secrets with batch processing for better reliability
     */
    /**
     * Get all PPP secrets from MikroTik with enhanced timeout handling.
     */
    public function getAllPppSecrets()
    {
        // Ensure we're connected first
        if (!$this->isConnected()) {
            $this->connect();
        }
        
        $maxAttempts = 3;
        $lastException = null;
        
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                // Ensure we still have a client connection
                if (!$this->client) {
                    $this->connect();
                }
                
                // First, try to get count to see if there are any secrets
                logger()->info("Checking PPP secrets count", [
                    'attempt' => $attempt,
                    'host' => $this->setting->host ?? 'unknown'
                ]);
                
                $countQuery = new Query('/ppp/secret/print');
                $countQuery->equal('count-only', '');
                $countResult = $this->client->query($countQuery)->read();
                
                // Debug log the count result structure
                logger()->info("Count result debug", [
                    'raw_result' => $countResult,
                    'result_structure' => is_array($countResult) ? array_keys($countResult) : 'not array',
                    'first_element' => isset($countResult[0]) ? $countResult[0] : 'no first element'
                ]);
                
                $secretCount = 0;
                if (isset($countResult['after']['ret'])) {
                    $secretCount = (int)$countResult['after']['ret'];
                } elseif (isset($countResult[0]['after']['ret'])) {
                    $secretCount = (int)$countResult[0]['after']['ret'];
                } elseif (isset($countResult[0]['ret'])) {
                    $secretCount = (int)$countResult[0]['ret'];
                }
                
                logger()->info("Found PPP secrets", [
                    'count' => $secretCount,
                    'host' => $this->setting->host ?? 'unknown'
                ]);
                
                if ($secretCount === 0) {
                    logger()->info('No PPP secrets found on MikroTik');
                    return [];
                }
                
                // If there are too many secrets, warn about potential timeout
                if ($secretCount > 50) {
                    logger()->warning('Large number of PPP secrets detected', [
                        'count' => $secretCount,
                        'recommendation' => 'Consider implementing pagination'
                    ]);
                }
                
                // Set a longer timeout for data retrieval operations
                if ($this->client && method_exists($this->client, 'setTimeout')) {
                    $timeout = $secretCount > 20 ? 180 : 60; // Longer timeout for many secrets
                    $this->client->setTimeout($timeout);
                }
                
                logger()->info("Attempting to retrieve PPP secrets", [
                    'attempt' => $attempt,
                    'count' => $secretCount,
                    'host' => $this->setting->host ?? 'unknown',
                    'timeout' => $timeout ?? 60
                ]);
                
                // Try simple query without proplist first
                $query = new Query('/ppp/secret/print');
                
                try {
                    $secrets = $this->client->query($query)->read();
                    
                    // Log successful retrieval for debugging
                    logger()->info('Successfully retrieved PPP secrets from MikroTik', [
                        'count' => count($secrets),
                        'host' => $this->setting->host ?? 'unknown',
                        'attempt' => $attempt,
                        'method' => 'simple'
                    ]);
                    
                    return $secrets;
                    
                } catch (Exception $e) {
                    // If simple query fails, it might be a timeout issue
                    if (strpos($e->getMessage(), 'timeout') !== false || 
                        strpos($e->getMessage(), 'Stream timed out') !== false) {
                        throw $e; // Re-throw timeout errors to trigger retry logic
                    }
                    
                    // For other errors, try with proplist to reduce data
                    logger()->info('Simple query failed, trying with proplist', [
                        'error' => $e->getMessage(),
                        'attempt' => $attempt
                    ]);
                    
                    $query2 = new Query('/ppp/secret/print');
                    $query2->equal('proplist', 'name,password,profile,service,comment,disabled');
                    $secrets = $this->client->query($query2)->read();
                    
                    // Log successful retrieval for debugging
                    logger()->info('Successfully retrieved PPP secrets from MikroTik', [
                        'count' => count($secrets),
                        'host' => $this->setting->host ?? 'unknown',
                        'attempt' => $attempt,
                        'method' => 'proplist'
                    ]);
                    
                    return $secrets;
                }
                
            } catch (Exception $e) {
                $lastException = $e;
                
                // Log the specific error for debugging
                logger()->error('Failed to get PPP secrets from MikroTik', [
                    'error' => $e->getMessage(),
                    'attempt' => $attempt,
                    'host' => $this->setting->host ?? 'unknown',
                    'error_type' => get_class($e)
                ]);
                
                // For timeout errors, try to reconnect on next attempt
                if (strpos($e->getMessage(), 'timeout') !== false || 
                    strpos($e->getMessage(), 'Stream timed out') !== false ||
                    strpos($e->getMessage(), 'Error reading') !== false) {
                    
                    logger()->info('Detected timeout/connection error, will reconnect on next attempt', [
                        'attempt' => $attempt,
                        'max_attempts' => $maxAttempts
                    ]);
                    
                    // Reset connection for next attempt
                    $this->client = null;
                    
                    if ($attempt < $maxAttempts) {
                        sleep($attempt * 3); // Progressive delay: 3s, 6s
                        continue;
                    }
                }
                
                // For other errors, stop immediately
                break;
            }
        }
        
        // If we get here, all attempts failed
        $errorMsg = $lastException ? $lastException->getMessage() : 'Unknown error';
        
        // Provide user-friendly error messages for common timeout issues
        if (strpos($errorMsg, 'timeout') !== false || 
            strpos($errorMsg, 'Stream timed out') !== false) {
            throw new Exception('Failed to get PPP secrets: The MikroTik router has a large number of PPP secrets and queries are timing out. This may be due to system load or database performance issues on the router. Consider reducing the number of secrets or contact your network administrator to optimize the router performance.');
        }
        
        throw new Exception('Failed to get PPP secrets: ' . $errorMsg);
    }
    /**
     * Create a PPP profile on Mikrotik.
     */
    public function createPppProfile(PppProfile $profile)
    {
        $this->ensureConnected();

        try {
            $query = new Query('/ppp/profile/add');
            $query->equal('name', $profile->name);

            if ($profile->local_address) {
                $query->equal('local-address', $profile->local_address);
            }

            if ($profile->remote_address) {
                $query->equal('remote-address', $profile->remote_address);
            }

            if ($profile->rate_limit) {
                $query->equal('rate-limit', $profile->rate_limit);
            }

            if ($profile->parent_queue) {
                $query->equal('parent-queue', $profile->parent_queue);
            }

            if ($profile->only_one) {
                $query->equal('only-one', 'yes');
            } else {
                $query->equal('only-one', 'no');
            }

            if ($profile->description) {
                $query->equal('comment', $profile->description);
            }

            $response = $this->client->query($query)->read();
            $mikrotikId = isset($response[0]['.id']) ? $response[0]['.id'] : null;

            // Update profile with MikroTik ID
            if ($mikrotikId) {
                $profile->mikrotik_id = $mikrotikId;
                $profile->save();
                
                logger()->info('PPP profile created on MikroTik', [
                    'profile_name' => $profile->name,
                    'mikrotik_id' => $mikrotikId
                ]);
            }

            return $mikrotikId;
        } catch (Exception $e) {
            logger()->error('Failed to create PPP profile on MikroTik', [
                'profile_name' => $profile->name,
                'error' => $e->getMessage()
            ]);
            throw new Exception('Failed to create PPP profile: ' . $e->getMessage());
        }
    }

    /**
     * Update a PPP profile on Mikrotik.
     */
    public function updatePppProfile(PppProfile $profile, $mikrotikId = null)
    {
        $this->ensureConnected();

        try {
            // If no Mikrotik ID provided, find it by name
            if (!$mikrotikId && !$profile->mikrotik_id) {
                $existingProfiles = $this->getPppProfiles();
                foreach ($existingProfiles as $existingProfile) {
                    if ($existingProfile['name'] === $profile->name) {
                        $mikrotikId = $existingProfile['.id'];
                        $profile->mikrotik_id = $mikrotikId;
                        $profile->save();
                        break;
                    }
                }

                if (!$mikrotikId) {
                    throw new Exception("PPP profile '{$profile->name}' not found on Mikrotik. Creating new profile instead.");
                }
            } else {
                $mikrotikId = $mikrotikId ?: $profile->mikrotik_id;
            }

            $query = new Query('/ppp/profile/set');
            $query->equal('.id', $mikrotikId);

            if ($profile->local_address) {
                $query->equal('local-address', $profile->local_address);
            }

            if ($profile->remote_address) {
                $query->equal('remote-address', $profile->remote_address);
            }

            if ($profile->rate_limit) {
                $query->equal('rate-limit', $profile->rate_limit);
            }

            if ($profile->parent_queue) {
                $query->equal('parent-queue', $profile->parent_queue);
            }

            $query->equal('only-one', $profile->only_one ? 'yes' : 'no');

            if ($profile->description) {
                $query->equal('comment', $profile->description);
            }

            $this->client->query($query)->read();
            
            logger()->info('PPP profile updated on MikroTik', [
                'profile_name' => $profile->name,
                'mikrotik_id' => $mikrotikId
            ]);
            
            return true;
        } catch (Exception $e) {
            logger()->error('Failed to update PPP profile on MikroTik', [
                'profile_name' => $profile->name,
                'mikrotik_id' => $mikrotikId,
                'error' => $e->getMessage()
            ]);
            throw new Exception('Failed to update PPP profile: ' . $e->getMessage());
        }
    }

    /**
     * Delete a PPP profile from Mikrotik.
     */
    public function deletePppProfile($name, $mikrotikId = null)
    {
        $this->ensureConnected();

        try {
            // If no Mikrotik ID provided, find it by name
            if (!$mikrotikId) {
                $existingProfiles = $this->getPppProfiles();
                foreach ($existingProfiles as $profile) {
                    if ($profile['name'] === $name) {
                        $mikrotikId = $profile['.id'];
                        break;
                    }
                }

                if (!$mikrotikId) {
                    throw new Exception("PPP profile '{$name}' not found on Mikrotik.");
                }
            }

            $query = new Query('/ppp/profile/remove');
            $query->equal('.id', $mikrotikId);
            $this->client->query($query)->read();
            
            logger()->info('PPP profile deleted from MikroTik', [
                'profile_name' => $name,
                'mikrotik_id' => $mikrotikId
            ]);
            
            return true;
        } catch (Exception $e) {
            logger()->error('Failed to delete PPP profile from MikroTik', [
                'profile_name' => $name,
                'mikrotik_id' => $mikrotikId,
                'error' => $e->getMessage()
            ]);
            throw new Exception('Failed to delete PPP profile: ' . $e->getMessage());
        }
    }

    /**
     * Create a PPP secret on Mikrotik.
     */
    public function createPppSecret(PppSecret $secret)
    {
        $this->ensureConnected();

        try {
            $query = new Query('/ppp/secret/add');
            $query->equal('name', $secret->username);
            $query->equal('password', $secret->password);
            $query->equal('service', $secret->service);

            // Get profile name
            $profileName = $secret->pppProfile ? $secret->pppProfile->name : 'default';
            $query->equal('profile', $profileName);

            if ($secret->local_address) {
                $query->equal('local-address', $secret->local_address);
            }

            if ($secret->remote_address) {
                $query->equal('remote-address', $secret->remote_address);
            }

            // Set disabled status
            $query->equal('disabled', $secret->is_active ? 'no' : 'yes');

            if ($secret->comment) {
                $query->equal('comment', $secret->comment);
            }

            $response = $this->client->query($query)->read();
            $mikrotikId = isset($response[0]['.id']) ? $response[0]['.id'] : null;

            if ($mikrotikId) {
                $secret->mikrotik_id = $mikrotikId;
                $secret->save();
                
                logger()->info('PPP secret created on MikroTik', [
                    'username' => $secret->username,
                    'mikrotik_id' => $mikrotikId,
                    'profile' => $profileName
                ]);
            }

            return $mikrotikId;
        } catch (Exception $e) {
            logger()->error('Failed to create PPP secret on MikroTik', [
                'username' => $secret->username,
                'error' => $e->getMessage()
            ]);
            throw new Exception('Failed to create PPP secret: ' . $e->getMessage());
        }
    }

    /**
     * Update a PPP secret on Mikrotik.
     */
    public function updatePppSecret(PppSecret $secret)
    {
        $this->ensureConnected();

        try {
            // If no Mikrotik ID provided, find it by username
            if (!$secret->mikrotik_id) {
                $existingSecrets = $this->getPppSecrets();
                foreach ($existingSecrets as $existingSecret) {
                    if ($existingSecret['name'] === $secret->username) {
                        $secret->mikrotik_id = $existingSecret['.id'];
                        $secret->save();
                        break;
                    }
                }

                if (!$secret->mikrotik_id) {
                    throw new Exception("PPP secret '{$secret->username}' not found on Mikrotik. Creating new secret instead.");
                }
            }

            $query = new Query('/ppp/secret/set');
            $query->equal('.id', $secret->mikrotik_id);
            $query->equal('password', $secret->password);

            // Get profile name
            $profileName = $secret->pppProfile ? $secret->pppProfile->name : 'default';
            $query->equal('profile', $profileName);

            if ($secret->local_address) {
                $query->equal('local-address', $secret->local_address);
            }

            if ($secret->remote_address) {
                $query->equal('remote-address', $secret->remote_address);
            }

            // Set disabled status
            $query->equal('disabled', $secret->is_active ? 'no' : 'yes');

            if ($secret->comment) {
                $query->equal('comment', $secret->comment);
            }

            $this->client->query($query)->read();
            
            logger()->info('PPP secret updated on MikroTik', [
                'username' => $secret->username,
                'mikrotik_id' => $secret->mikrotik_id,
                'profile' => $profileName
            ]);
            
            return true;
        } catch (Exception $e) {
            logger()->error('Failed to update PPP secret on MikroTik', [
                'username' => $secret->username,
                'mikrotik_id' => $secret->mikrotik_id,
                'error' => $e->getMessage()
            ]);
            throw new Exception('Failed to update PPP secret: ' . $e->getMessage());
        }
    }

    /**
     * Delete a PPP secret from Mikrotik.
     */
    public function deletePppSecret(PppSecret $secret)
    {
        $this->ensureConnected();

        try {
            // If no Mikrotik ID provided, try to find it by username
            if (!$secret->mikrotik_id) {
                logger()->info('Searching for PPP secret in MikroTik', [
                    'username' => $secret->username
                ]);
                
                try {
                    // Try direct query to find secret by name (faster than getting all)
                    $query = new Query('/ppp/secret/print');
                    $query->where('name', $secret->username);
                    $result = $this->client->query($query)->read();
                    
                    if (!empty($result) && isset($result[0]['.id'])) {
                        $secret->mikrotik_id = $result[0]['.id'];
                        logger()->info('Found PPP secret in MikroTik via direct query', [
                            'username' => $secret->username,
                            'mikrotik_id' => $secret->mikrotik_id
                        ]);
                    } else {
                        logger()->warning('PPP secret not found in MikroTik via direct query', [
                            'username' => $secret->username
                        ]);
                        throw new Exception("PPP secret '{$secret->username}' not found on MikroTik. It may have been manually deleted from router or never synced.");
                    }
                } catch (Exception $e) {
                    logger()->warning('Failed to search PPP secret in MikroTik', [
                        'username' => $secret->username,
                        'error' => $e->getMessage()
                    ]);
                    
                    // Check if error message indicates not found vs timeout
                    if (strpos($e->getMessage(), 'timeout') !== false || strpos($e->getMessage(), 'timed out') !== false) {
                        throw new Exception("Unable to verify if PPP secret '{$secret->username}' exists on MikroTik (timeout). Router is slow to respond. Try again later or delete from database only.");
                    } else {
                        throw new Exception("PPP secret '{$secret->username}' not found on MikroTik. It may have been manually deleted from router or never synced.");
                    }
                }
            }

            // Delete the secret using its ID
            $query = new Query('/ppp/secret/remove');
            $query->equal('.id', $secret->mikrotik_id);
            $this->client->query($query)->read();
            
            logger()->info('PPP secret deleted from MikroTik', [
                'username' => $secret->username,
                'mikrotik_id' => $secret->mikrotik_id
            ]);
            
            return true;
        } catch (Exception $e) {
            logger()->error('Failed to delete PPP secret from MikroTik', [
                'username' => $secret->username,
                'mikrotik_id' => $secret->mikrotik_id,
                'error' => $e->getMessage()
            ]);
            throw new Exception('Failed to delete PPP secret: ' . $e->getMessage());
        }
    }

    /**
     * Push profile changes to MikroTik (create or update).
     */
    public function pushPppProfile(PppProfile $profile)
    {
        try {
            // Check if profile exists on MikroTik
            if ($profile->mikrotik_id) {
                // Try to update existing profile
                try {
                    return $this->updatePppProfile($profile);
                } catch (Exception $e) {
                    // If update fails, try to create new profile
                    logger()->warning('Profile update failed, trying to create new', [
                        'profile_name' => $profile->name,
                        'error' => $e->getMessage()
                    ]);
                    $profile->mikrotik_id = null;
                    return $this->createPppProfile($profile);
                }
            } else {
                // Create new profile
                return $this->createPppProfile($profile);
            }
        } catch (Exception $e) {
            throw new Exception('Failed to push PPP profile to MikroTik: ' . $e->getMessage());
        }
    }

    /**
     * Push secret changes to MikroTik (create or update).
     */
    public function pushPppSecret(PppSecret $secret)
    {
        try {
            // Check if secret exists on MikroTik
            if ($secret->mikrotik_id) {
                // Try to update existing secret
                try {
                    return $this->updatePppSecret($secret);
                } catch (Exception $e) {
                    // If update fails, try to create new secret
                    logger()->warning('Secret update failed, trying to create new', [
                        'username' => $secret->username,
                        'error' => $e->getMessage()
                    ]);
                    $secret->mikrotik_id = null;
                    return $this->createPppSecret($secret);
                }
            } else {
                // Create new secret
                return $this->createPppSecret($secret);
            }
        } catch (Exception $e) {
            throw new Exception('Failed to push PPP secret to MikroTik: ' . $e->getMessage());
        }
    }

    /**
     * Enable a PPP secret on Mikrotik.
     */
    public function enablePppSecret(PppSecret $secret)
    {
        $this->ensureConnected();

        try {
            // If no Mikrotik ID provided, find it by username
            if (!$secret->mikrotik_id) {
                $existingSecrets = $this->getPppSecrets();
                foreach ($existingSecrets as $existingSecret) {
                    if ($existingSecret['name'] === $secret->username) {
                        $secret->mikrotik_id = $existingSecret['.id'];
                        $secret->save();
                        break;
                    }
                }

                if (!$secret->mikrotik_id) {
                    throw new Exception("PPP secret '{$secret->username}' not found on Mikrotik.");
                }
            }

            $query = new Query('/ppp/secret/set');
            $query->equal('.id', $secret->mikrotik_id);
            $query->equal('disabled', 'no');
            $this->client->query($query)->read();

            $secret->is_active = true;
            $secret->save();

            return true;
        } catch (Exception $e) {
            throw new Exception('Failed to enable PPP secret: ' . $e->getMessage());
        }
    }

    /**
     * Disable a PPP secret on Mikrotik.
     */
    public function disablePppSecret(PppSecret $secret)
    {
        $this->ensureConnected();

        try {
            // If no Mikrotik ID provided, find it by username
            if (!$secret->mikrotik_id) {
                $existingSecrets = $this->getPppSecrets();
                foreach ($existingSecrets as $existingSecret) {
                    if ($existingSecret['name'] === $secret->username) {
                        $secret->mikrotik_id = $existingSecret['.id'];
                        $secret->save();
                        break;
                    }
                }

                if (!$secret->mikrotik_id) {
                    throw new Exception("PPP secret '{$secret->username}' not found on Mikrotik.");
                }
            }

            $query = new Query('/ppp/secret/set');
            $query->equal('.id', $secret->mikrotik_id);
            $query->equal('disabled', 'yes');
            $this->client->query($query)->read();

            $secret->is_active = false;
            $secret->save();

            return true;
        } catch (Exception $e) {
            throw new Exception('Failed to disable PPP secret: ' . $e->getMessage());
        }
    }

    /**
     * Get active PPP connections from Mikrotik.
     */
    public function getActivePppConnections()
    {
        $this->ensureConnected();

        try {
            // Create query for active PPP connections with limited results
            $query = new Query('/ppp/active/print');
            
            // Add count limit to prevent timeout on large datasets
            $query->equal('count-only', '');
            $countResult = $this->client->query($query)->read();
            $totalCount = isset($countResult[0]['ret']) ? (int) $countResult[0]['ret'] : 0;
            
            logger()->info('PPP active connections count check', ['total' => $totalCount]);
            
            // If too many connections, limit the result to prevent timeout
            $query = new Query('/ppp/active/print');
            if ($totalCount > 50) {
                // Limit results to prevent timeout
                $query->equal('count', '50');
                logger()->warning('Limiting PPP active query due to large dataset', ['total' => $totalCount]);
            }
            
            logger()->info('Getting active PPP connections');
            
            // Execute query with timeout protection
            $startTime = microtime(true);
            $result = $this->client->query($query)->read();
            $endTime = microtime(true);
            $executionTime = round(($endTime - $startTime) * 1000, 2); // in milliseconds
            
            logger()->info('Successfully retrieved active PPP connections', [
                'count' => count($result ?? []),
                'execution_time_ms' => $executionTime,
                'limited' => $totalCount > 50
            ]);
            
            // If query took too long, warn about it
            if ($executionTime > 5000) { // 5 seconds
                logger()->warning('Active PPP connections query took too long', [
                    'execution_time_ms' => $executionTime,
                    'recommendation' => 'Consider optimizing MikroTik performance'
                ]);
            }
            
            return $result ?? [];
            
        } catch (Exception $e) {
            // Enhanced error logging for timeout issues
            $errorMsg = $e->getMessage();
            
            logger()->warning('Failed to get active PPP connections', [
                'error' => $errorMsg,
                'error_type' => strpos($errorMsg, 'timeout') !== false ? 'timeout' : 'other',
                'recommendation' => 'Check MikroTik router performance and network connection'
            ]);
            
            // For timeout errors, provide more specific message
            if (strpos($errorMsg, 'timeout') !== false || 
                strpos($errorMsg, 'Stream timed out') !== false ||
                strpos($errorMsg, 'timed out') !== false) {
                throw new Exception('Router response timeout - please try again or check router performance');
            }
            
            throw new Exception('Failed to get active PPP connections: ' . $errorMsg);
        }
    }

    /**
     * Test basic connection with simple query.
     */
    public function testConnection()
    {
        $this->ensureConnected();

        try {
            // Simple system resource query for testing
            $query = new Query('/system/resource/print');
            
            $startTime = microtime(true);
            $result = $this->client->query($query)->read();
            $endTime = microtime(true);
            $executionTime = round(($endTime - $startTime) * 1000, 2);
            
            logger()->info('Connection test successful', [
                'execution_time_ms' => $executionTime
            ]);
            
            return [
                'success' => true,
                'execution_time' => $executionTime,
                'data' => $result
            ];
            
        } catch (Exception $e) {
            logger()->error('Connection test failed', [
                'error' => $e->getMessage()
            ]);
            
            throw new Exception('Connection test failed: ' . $e->getMessage());
        }
    }

    /**
     * Get lightweight PPP connection count.
     */
    public function getPppConnectionCount()
    {
        $this->ensureConnected();

        try {
            $query = new Query('/ppp/active/print');
            $query->equal('count-only', '');
            
            $startTime = microtime(true);
            $result = $this->client->query($query)->read();
            $endTime = microtime(true);
            $executionTime = round(($endTime - $startTime) * 1000, 2);
            
            $count = isset($result[0]['ret']) ? (int) $result[0]['ret'] : 0;
            
            logger()->info('PPP connection count retrieved', [
                'count' => $count,
                'execution_time_ms' => $executionTime
            ]);
            
            return [
                'count' => $count,
                'execution_time' => $executionTime
            ];
            
        } catch (Exception $e) {
            logger()->error('Failed to get PPP connection count', [
                'error' => $e->getMessage()
            ]);
            
            throw new Exception('Failed to get connection count: ' . $e->getMessage());
        }
    }

    /**
     * Disconnect an active PPP connection on Mikrotik.
     */
    public function disconnectPppConnection($username)
    {
        $this->ensureConnected();

        try {
            logger()->info('Attempting to disconnect PPP connection', [
                'username' => $username
            ]);
            
            // Since the MikroTik API doesn't support filtering by name in /ppp/active/print,
            // we need to get all connections and find the user manually.
            // However, if that times out, we'll try a direct approach.
            
            $connectionId = null;
            
            try {
                // Try to get active connections with a short timeout
                $query = new Query('/ppp/active/print');
                $connections = $this->client->query($query)->read();
                
                // Find the user in the connections
                foreach ($connections as $connection) {
                    if (isset($connection['name']) && $connection['name'] === $username) {
                        $connectionId = $connection['.id'];
                        logger()->info('Found connection ID for user', [
                            'username' => $username,
                            'connection_id' => $connectionId
                        ]);
                        break;
                    }
                }
                
            } catch (Exception $e) {
                logger()->warning('Failed to get active connections list, trying direct disconnect approaches', [
                    'username' => $username,
                    'error' => $e->getMessage()
                ]);
            }
            
            if ($connectionId) {
                // Method 1: Disconnect using found connection ID
                try {
                    $query = new Query('/ppp/active/remove');
                    $query->equal('.id', $connectionId);
                    $this->client->query($query)->read();
                    
                    logger()->info('Successfully disconnected PPP connection using connection ID', [
                        'username' => $username,
                        'connection_id' => $connectionId
                    ]);
                    
                    return true;
                } catch (Exception $e) {
                    logger()->warning('Failed to disconnect using connection ID', [
                        'username' => $username,
                        'connection_id' => $connectionId,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            // Method 2: Try systematic ID-based disconnect (brute force for active connections)
            // This assumes MikroTik uses sequential hex IDs starting from *0
            logger()->info('Trying systematic disconnect approach for user', [
                'username' => $username
            ]);
            
            $maxAttempts = 50; // Check up to 50 possible connection IDs
            $disconnectAttempted = false;
            
            for ($i = 0; $i < $maxAttempts; $i++) {
                $testId = '*' . strtoupper(dechex($i));
                
                try {
                    // Check if this ID exists and get its name
                    $checkQuery = new Query('/ppp/active/print');
                    $checkQuery->equal('.id', $testId);
                    $checkResult = $this->client->query($checkQuery)->read();
                    
                    if (!empty($checkResult) && isset($checkResult[0]['name'])) {
                        $foundName = $checkResult[0]['name'];
                        
                        if ($foundName === $username) {
                            // Found our user! Disconnect them
                            $removeQuery = new Query('/ppp/active/remove');
                            $removeQuery->equal('.id', $testId);
                            $this->client->query($removeQuery)->read();
                            
                            logger()->info('Successfully disconnected PPP connection using systematic search', [
                                'username' => $username,
                                'connection_id' => $testId,
                                'attempt' => $i
                            ]);
                            
                            $disconnectAttempted = true;
                            break;
                        }
                    }
                    
                } catch (Exception $e) {
                    // This ID doesn't exist or caused an error, continue to next
                    continue;
                }
                
                // Small delay to avoid overwhelming the router
                usleep(50000); // 0.05 seconds
            }
            
            if (!$disconnectAttempted) {
                logger()->warning('User not found in active connections after systematic search', [
                    'username' => $username,
                    'checked_ids' => $maxAttempts
                ]);
                throw new Exception("User '{$username}' not found in active connections. User may already be offline or the username may be incorrect.");
            }
            
            return true;
            
        } catch (Exception $e) {
            logger()->error('Failed to disconnect PPP connection', [
                'username' => $username,
                'error' => $e->getMessage()
            ]);
            
            // Don't re-throw timeout errors as complete failures since disconnect may have worked
            if (strpos($e->getMessage(), 'timeout') !== false || strpos($e->getMessage(), 'timed out') !== false) {
                throw new Exception("Disconnect command may have been sent but router response was slow. Please check user status manually to verify disconnection.");
            }
            
            throw $e;
        }
    }

    /**
     * Sync active PPP connections with the database.
     */
    public function syncActivePppConnections()
    {
        $this->ensureConnected();

        try {
            $activeConnections = $this->getActivePppConnections();
            $secrets = PppSecret::all()->keyBy('username');
            $now = now();

            // Close any active sessions that are no longer connected
            UsageLog::whereNull('disconnected_at')
                ->get()
                ->each(function ($log) use ($activeConnections, $now) {
                    $stillActive = false;
                    foreach ($activeConnections as $connection) {
                        if ($connection['name'] === $log->pppSecret->username) {
                            $stillActive = true;
                            break;
                        }
                    }

                    if (!$stillActive) {
                        $log->disconnected_at = $now;
                        $log->save();
                    }
                });

            // Create or update logs for active connections
            foreach ($activeConnections as $connection) {
                $username = $connection['name'];
                if (!isset($secrets[$username])) {
                    continue; // Skip if secret not in database
                }

                $secret = $secrets[$username];
                $log = UsageLog::where('ppp_secret_id', $secret->id)
                    ->whereNull('disconnected_at')
                    ->first();

                if (!$log) {
                    // Create new log
                    $log = new UsageLog([
                        'ppp_secret_id' => $secret->id,
                        'caller_id' => $connection['caller-id'] ?? null,
                        'uptime' => $connection['uptime'] ?? null,
                        'bytes_in' => $connection['bytes-in'] ?? null,
                        'bytes_out' => $connection['bytes-out'] ?? null,
                        'ip_address' => $connection['address'] ?? null,
                        'connected_at' => $now,
                        'session_id' => $connection['.id'] ?? null,
                    ]);
                } else {
                    // Update existing log
                    $log->uptime = $connection['uptime'] ?? null;
                    $log->bytes_in = $connection['bytes-in'] ?? null;
                    $log->bytes_out = $connection['bytes-out'] ?? null;
                    $log->ip_address = $connection['address'] ?? null;
                }

                $log->save();
            }

            return true;
        } catch (Exception $e) {
            throw new Exception('Failed to sync active PPP connections: ' . $e->getMessage());
        }
    }

    /**
     * Get system resource information from Mikrotik.
     */
    public function getSystemResources()
    {
        $this->ensureConnected();

        try {
            $query = new Query('/system/resource/print');
            $resources = $this->client->query($query)->read();
            return $resources[0] ?? [];
        } catch (Exception $e) {
            throw new Exception('Failed to get system resources: ' . $e->getMessage());
        }
    }

    /**
     * Get system identity from Mikrotik.
     */
    public function getSystemIdentity()
    {
        $this->ensureConnected();

        try {
            $query = new Query('/system/identity/print');
            $identity = $this->client->query($query)->read();
            return $identity[0]['name'] ?? 'Unknown';
        } catch (Exception $e) {
            throw new Exception('Failed to get system identity: ' . $e->getMessage());
        }
    }

    /**
     * Sync PPP profiles from MikroTik to database with enhanced error handling.
     */
    public function syncPppProfiles()
    {
        try {
            $mikrotikProfiles = $this->getPppProfiles();
            $syncedCount = 0;
            $skippedCount = 0;

            foreach ($mikrotikProfiles as $mtProfile) {
                // Skip default profiles
                if (in_array($mtProfile['name'], ['default', 'default-encryption'])) {
                    $skippedCount++;
                    continue;
                }

                $profileData = [
                    'name' => $mtProfile['name'],
                    'rate_limit' => $mtProfile['rate-limit'] ?? null,
                    'local_address' => $mtProfile['local-address'] ?? null,
                    'remote_address' => $mtProfile['remote-address'] ?? null,
                    'parent_queue' => $mtProfile['parent-queue'] ?? null,
                    'only_one' => isset($mtProfile['only-one']) && $mtProfile['only-one'] === 'yes',
                    'description' => $mtProfile['comment'] ?? null,
                    'is_active' => true,
                    'price' => 0, // Default price, needs to be set manually
                    'mikrotik_id' => $mtProfile['.id'],
                ];

                PppProfile::updateOrCreate(
                    ['name' => $mtProfile['name']],
                    $profileData
                );

                $syncedCount++;
            }

            return [
                'synced' => $syncedCount,
                'skipped' => $skippedCount,
                'total' => count($mikrotikProfiles)
            ];
        } catch (Exception $e) {
            throw new Exception('Failed to sync PPP profiles: ' . $e->getMessage());
        }
    }

    /**
     * Sync PPP secrets from MikroTik to database with enhanced error handling.
     */
    public function syncPppSecrets()
    {
        try {
            $mikrotikSecrets = $this->getPppSecrets();
            $syncedCount = 0;
            $skippedCount = 0;

            foreach ($mikrotikSecrets as $mtSecret) {
                // Find profile by name
                $profile = PppProfile::where('name', $mtSecret['profile'])->first();
                if (!$profile) {
                    $skippedCount++;
                    continue; // Skip if profile not found
                }

                $secretData = [
                    'username' => $mtSecret['name'],
                    'password' => $mtSecret['password'] ?? '',
                    'service' => $mtSecret['service'] ?? 'pppoe',
                    'ppp_profile_id' => $profile->id,
                    'local_address' => $mtSecret['local-address'] ?? null,
                    'remote_address' => $mtSecret['remote-address'] ?? null,
                    'is_active' => !isset($mtSecret['disabled']) || $mtSecret['disabled'] !== 'yes',
                    'comment' => $mtSecret['comment'] ?? null,
                    'mikrotik_id' => $mtSecret['.id'],
                    'installation_date' => now(),
                ];

                PppSecret::updateOrCreate(
                    ['username' => $mtSecret['name']],
                    $secretData
                );

                $syncedCount++;
            }

            return [
                'synced' => $syncedCount,
                'skipped' => $skippedCount,
                'total' => count($mikrotikSecrets)
            ];
        } catch (Exception $e) {
            throw new Exception('Failed to sync PPP secrets: ' . $e->getMessage());
        }
    }

    /**
     * Sync all data from MikroTik (profiles and secrets) with enhanced error handling.
     */
    public function syncAllFromMikrotik()
    {
        try {
            $results = [];
            
            // Sync profiles first
            logger()->info('Starting PPP profiles sync from MikroTik');
            $results['profiles'] = $this->syncPppProfiles();
            logger()->info('PPP profiles sync completed', $results['profiles']);
            
            // Then sync secrets
            logger()->info('Starting PPP secrets sync from MikroTik');
            $results['secrets'] = $this->syncPppSecrets();
            logger()->info('PPP secrets sync completed', $results['secrets']);

            // Update active connections
            logger()->info('Starting active connections sync');
            $this->syncActivePppConnections();
            logger()->info('Active connections sync completed');

            return $results;
        } catch (Exception $e) {
            logger()->error('Failed to sync all data from MikroTik', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new Exception('Failed to sync data from MikroTik: ' . $e->getMessage());
        }
    }

    /**
     * Check if a specific user is currently connected.
     */
    public function isUserConnected($username)
    {
        try {
            $activeConnections = $this->getActivePppConnections();
            
            foreach ($activeConnections as $connection) {
                if ($connection['name'] === $username) {
                    return [
                        'connected' => true,
                        'connection_data' => $connection
                    ];
                }
            }
            
            return ['connected' => false];
        } catch (Exception $e) {
            logger()->error('Failed to check user connection status', [
                'username' => $username,
                'error' => $e->getMessage()
            ]);
            
            return [
                'connected' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get connection status for multiple users.
     */
    public function getConnectionStatusForUsers($usernames)
    {
        try {
            $activeConnections = $this->getActivePppConnections();
            $statuses = [];
            
            // Initialize all users as disconnected
            foreach ($usernames as $username) {
                $statuses[$username] = ['connected' => false];
            }
            
            // Mark connected users
            foreach ($activeConnections as $connection) {
                $username = $connection['name'];
                if (in_array($username, $usernames)) {
                    $statuses[$username] = [
                        'connected' => true,
                        'connection_data' => $connection
                    ];
                }
            }
            
            return $statuses;
        } catch (Exception $e) {
            logger()->error('Failed to get connection status for users', [
                'usernames_count' => count($usernames),
                'error' => $e->getMessage()
            ]);
            
            // Return all as disconnected with error
            $statuses = [];
            foreach ($usernames as $username) {
                $statuses[$username] = [
                    'connected' => false,
                    'error' => $e->getMessage()
                ];
            }
            
            return $statuses;
        }
    }

    /**
     * Update PPP secret profile on MikroTik.
     */
    public function updatePppSecretProfile(PppSecret $secret, PppProfile $newProfile)
    {
        $this->ensureConnected();

        try {
            // If no Mikrotik ID provided, find it by username
            if (!$secret->mikrotik_id) {
                $existingSecrets = $this->getPppSecrets();
                foreach ($existingSecrets as $existingSecret) {
                    if ($existingSecret['name'] === $secret->username) {
                        $secret->mikrotik_id = $existingSecret['.id'];
                        $secret->save();
                        break;
                    }
                }

                if (!$secret->mikrotik_id) {
                    throw new Exception("PPP secret '{$secret->username}' not found on Mikrotik.");
                }
            }

            $query = new Query('/ppp/secret/set');
            $query->equal('.id', $secret->mikrotik_id);
            $query->equal('profile', $newProfile->name);
            
            $this->client->query($query)->read();
            
            logger()->info('PPP secret profile updated on MikroTik', [
                'username' => $secret->username,
                'mikrotik_id' => $secret->mikrotik_id,
                'old_profile' => $secret->pppProfile->name ?? 'unknown',
                'new_profile' => $newProfile->name
            ]);
            
            return true;
        } catch (Exception $e) {
            logger()->error('Failed to update PPP secret profile on MikroTik', [
                'username' => $secret->username,
                'mikrotik_id' => $secret->mikrotik_id,
                'new_profile' => $newProfile->name,
                'error' => $e->getMessage()
            ]);
            throw new Exception('Failed to update PPP secret profile: ' . $e->getMessage());
        }
    }
}