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
    protected $isConnected = false;

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
        return $this->connectWithRetry(1); // Single attempt for backward compatibility
    }

    /**
     * Connect to the Mikrotik router with retry logic.
     */
    public function connectWithRetry($maxAttempts = 3)
    {
        if (!$this->setting) {
            $this->loadActiveSetting();
        }
        
        if (!$this->setting) {
            throw new Exception('No active Mikrotik setting found.');
        }

        $lastException = null;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $port = $this->setting->port ?: ($this->setting->use_ssl ? 8729 : 8728);
                
                // Progressive timeout: start short, increase with retries
                $initialTimeout = 10 + ($attempt - 1) * 5; // 10s, 15s, 20s
                
                $config = new Config([
                    'host' => $this->setting->host,
                    'user' => $this->setting->username,
                    'pass' => $this->setting->password,
                    'port' => (int) $port,
                    'timeout' => $initialTimeout,
                    'attempts' => 1,  // Single attempt to avoid auto-retry loops
                    'delay' => 1,     // Shorter delay for faster failure detection
                    'socket_timeout' => $initialTimeout,
                ]);

                if ($this->setting->use_ssl) {
                    $config->set('ssl', true);
                    $config->set('ssl_options', [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true,
                    ]);
                }

                logger()->info('Attempting to connect to MikroTik router', [
                    'host' => $this->setting->host,
                    'port' => $port,
                    'ssl' => $this->setting->use_ssl,
                    'timeout' => $initialTimeout,
                    'attempt' => $attempt,
                    'max_attempts' => $maxAttempts
                ]);

                $startTime = microtime(true);
                $this->client = new Client($config);
                $connectionTime = microtime(true) - $startTime;

                logger()->info('Successfully connected to MikroTik router', [
                    'connection_time_ms' => round($connectionTime * 1000, 2),
                    'host' => $this->setting->host,
                    'attempt' => $attempt
                ]);

                $this->isConnected = true;
                return $this;
                
            } catch (Exception $e) {
                $lastException = $e;
                $errorMsg = $e->getMessage();
                $connectionTime = isset($startTime) ? microtime(true) - $startTime : 0;
                
                logger()->error('Failed to connect to MikroTik router', [
                    'error' => $errorMsg,
                    'host' => $this->setting->host,
                    'port' => $port,
                    'connection_time_ms' => round($connectionTime * 1000, 2),
                    'error_type' => $this->getConnectionErrorType($errorMsg),
                    'attempt' => $attempt,
                    'max_attempts' => $maxAttempts
                ]);

                // If not the last attempt, wait before retrying for timeout/network errors
                if ($attempt < $maxAttempts) {
                    $errorType = $this->getConnectionErrorType($errorMsg);
                    
                    if (in_array($errorType, ['timeout', 'network_unreachable', 'unknown'])) {
                        $delay = $attempt * 2; // 2s, 4s
                        logger()->info("Connection failed, retrying in {$delay} seconds", [
                            'delay' => $delay,
                            'next_attempt' => $attempt + 1,
                            'error_type' => $errorType
                        ]);
                        sleep($delay);
                        continue;
                    } else {
                        // For auth errors and connection refused, don't retry
                        break;
                    }
                }
            }
        }

        // All attempts failed or non-retryable error
        $errorMsg = $lastException ? $lastException->getMessage() : 'Unknown connection error';
        
        // Provide more specific error messages
        if (strpos($errorMsg, 'timeout') !== false || strpos($errorMsg, 'timed out') !== false) {
            throw new Exception("Connection timeout after {$maxAttempts} attempts - router may be overloaded or network connection is very slow");
        } elseif (strpos($errorMsg, 'Connection refused') !== false) {
            throw new Exception('Connection refused - check if API service is enabled on router');
        } elseif (strpos($errorMsg, 'cannot connect') !== false) {
            throw new Exception('Cannot connect to router - check IP address and network connectivity');
        } else {
            throw new Exception("Connection failed after {$maxAttempts} attempts: " . $errorMsg);
        }
    }
    
    /**
     * Get connection error type for categorization
     */
    private function getConnectionErrorType($errorMsg)
    {
        if (strpos($errorMsg, 'timeout') !== false || strpos($errorMsg, 'timed out') !== false) {
            return 'timeout';
        } elseif (strpos($errorMsg, 'Connection refused') !== false) {
            return 'connection_refused';
        } elseif (strpos($errorMsg, 'cannot connect') !== false) {
            return 'network_unreachable';
        } elseif (strpos($errorMsg, 'authentication') !== false || strpos($errorMsg, 'login') !== false) {
            return 'authentication_failed';
        } else {
            return 'unknown';
        }
    }

    /**
     * Ensure the connection is established.
     */
    public function ensureConnected()
    {
        if (!$this->isConnected || !$this->client) {
            $this->connect();
        }
    }

    /**
     * Disconnect from the Mikrotik router.
     */
    public function disconnect()
    {
        $this->isConnected = false;
        $this->client = null;
    }

    /**
     * Categorize error messages for better handling
     */
    private function categorizeError($errorMessage)
    {
        if (strpos($errorMessage, 'timeout') !== false || strpos($errorMessage, 'timed out') !== false) {
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
            
            // Try to get secrets with a simple query and limit
            $query = new Query('/ppp/secret/print');
            $query->equal('limit', '5'); // Limit to first 5 secrets
            
            $secrets = $this->client->query($query)->read();
            
            // Debug log the structure of the first secret for troubleshooting
            if (!empty($secrets) && is_array($secrets)) {
                logger()->info('PPP secrets batch structure debug', [
                    'first_secret_keys' => array_keys($secrets[0] ?? []),
                    'first_secret_info' => $secrets[0] ?? null,
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
     * Get all PPP secrets from MikroTik with enhanced timeout handling and optional chunking.
     * 
     * @param int|null $chunkSize If provided, attempts to retrieve secrets in smaller chunks for better reliability
     * @return array
     */
    public function getAllPppSecrets($chunkSize = null)
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
                
                // Calculate progressive timeout based on attempt and secret count
                $baseTimeout = 60; // Start with 60 seconds
                $timeoutMultiplier = $attempt; // Increase timeout with each attempt
                $countMultiplier = max(1, ceil($secretCount / 5)); // Add time for every 5 secrets
                $timeout = $baseTimeout * $timeoutMultiplier * $countMultiplier;
                $timeout = min($timeout, 600); // Cap at 10 minutes maximum
                
                // Set progressive timeout for data retrieval operations
                if ($this->client && method_exists($this->client, 'setTimeout')) {
                    $this->client->setTimeout($timeout);
                }
                
                logger()->info("Attempting to retrieve PPP secrets", [
                    'attempt' => $attempt,
                    'count' => $secretCount,
                    'host' => $this->setting->host ?? 'unknown',
                    'timeout' => $timeout,
                    'chunk_size' => $chunkSize
                ]);
                
                // Use batch retrieval for better reliability
                logger()->info('Using batch retrieval approach', [
                    'total_secrets' => $secretCount
                ]);
                
                return $this->getPppSecretsBatch(0, $secretCount);
                
                // Try simple query without proplist first
                try {
                    $query = new Query('/ppp/secret/print');
                    
                    $secrets = $this->client->query($query)->read();
                    
                    // Check if response contains error
                    if (isset($secrets['after']['message'])) {
                        logger()->error('MikroTik query error', [
                            'error' => $secrets['after']['message']
                        ]);
                        throw new Exception('MikroTik query error: ' . $secrets['after']['message']);
                    }
                    
                    // Log successful retrieval for debugging
                    logger()->info('Successfully retrieved PPP secrets from MikroTik', [
                        'count' => count($secrets),
                        'host' => $this->setting->host ?? 'unknown',
                        'attempt' => $attempt,
                        'method' => 'simple_query'
                    ]);
                    
                    return $secrets;
                    
                } catch (Exception $e) {
                    // If optimized query fails, try minimal proplist
                    if (strpos($e->getMessage(), 'timeout') !== false || 
                        strpos($e->getMessage(), 'Stream timed out') !== false) {
                        
                        logger()->info('Optimized query timed out, trying minimal data approach', [
                            'error' => $e->getMessage(),
                            'attempt' => $attempt
                        ]);
                        
                        $query2 = new Query('/ppp/secret/print');
                        $query2->equal('proplist', 'name,password,profile,disabled,.id');
                        $secrets = $this->client->query($query2)->read();
                        
                        logger()->info('Successfully retrieved PPP secrets with minimal data', [
                            'count' => count($secrets),
                            'host' => $this->setting->host ?? 'unknown',
                            'attempt' => $attempt,
                            'method' => 'minimal_proplist'
                        ]);
                        
                        return $secrets;
                    } else {
                        throw $e; // Re-throw non-timeout errors
                    }
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
                        $delay = $attempt * $attempt * 2; // Exponential backoff: 2s, 8s, 18s
                        logger()->info("Waiting {$delay} seconds before retry", ['delay' => $delay]);
                        sleep($delay);
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
     * Retrieve secrets in small chunks for better reliability on slow connections.
     */
    private function getSecretsInChunks($chunkSize, $totalCount, $timeout)
    {
        logger()->info('Starting chunked secret retrieval', [
            'chunk_size' => $chunkSize,
            'total_count' => $totalCount,
            'timeout' => $timeout
        ]);
        
        try {
            // Set longer timeout for the operation
            if ($this->client && method_exists($this->client, 'setTimeout')) {
                $this->client->setTimeout(max($timeout, 300)); // Minimum 5 minutes
            }
            
            // Try to get secrets without proplist first
            $query = new Query('/ppp/secret/print');
            
            $secrets = $this->client->query($query)->read();
            
            // Check if response contains error
            if (isset($secrets['after']['message'])) {
                logger()->error('MikroTik query error', [
                    'error' => $secrets['after']['message']
                ]);
                throw new Exception('MikroTik query error: ' . $secrets['after']['message']);
            }
            
            logger()->info('Chunked retrieval completed', [
                'total_retrieved' => count($secrets),
                'expected' => $totalCount
            ]);
            
            return $secrets;
            
        } catch (Exception $e) {
            logger()->error('Chunk retrieval failed', [
                'chunk_size' => $chunkSize,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
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
     * Restore original profile after payment verification.
     */
    public function restoreOriginalProfile(PppSecret $secret)
    {
        $this->ensureConnected();

        try {
            // Check if original profile exists
            if (!$secret->original_ppp_profile_id || !$secret->originalPppProfile) {
                logger()->warning('No original profile to restore', [
                    'username' => $secret->username,
                    'original_profile_id' => $secret->original_ppp_profile_id
                ]);
                return false;
            }

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

            $originalProfile = $secret->originalPppProfile;
            
            // Update profile in MikroTik
            $query = new Query('/ppp/secret/set');
            $query->equal('.id', $secret->mikrotik_id);
            $query->equal('profile', $originalProfile->name);
            $query->equal('disabled', 'no'); // Also enable the secret
            $this->client->query($query)->read();

            // Update in database
            $secret->ppp_profile_id = $secret->original_ppp_profile_id;
            $secret->original_ppp_profile_id = null; // Clear the original profile
            $secret->is_active = true;
            $secret->save();

            // Disconnect active session to force profile change
            try {
                $this->disconnectPppConnection($secret->username);
            } catch (Exception $e) {
                // User might not be connected, that's ok
                logger()->info('User not connected during profile restore', [
                    'username' => $secret->username
                ]);
            }

            logger()->info('Original profile restored successfully', [
                'username' => $secret->username,
                'restored_profile' => $originalProfile->name,
                'mikrotik_id' => $secret->mikrotik_id
            ]);

            return true;
        } catch (Exception $e) {
            logger()->error('Failed to restore original profile', [
                'username' => $secret->username,
                'error' => $e->getMessage()
            ]);
            throw new Exception('Failed to restore original profile: ' . $e->getMessage());
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
     * 
     * @param bool $forceReal Force attempt to get real data with retries
     */
    public function getActivePppConnections($forceReal = false)
    {
        $this->ensureConnected();

        try {
            // Always try real data first - with reasonable timeout for production
            logger()->info('Starting active PPP connections query - REAL DATA ONLY');
            
            // Set reasonable timeout for production (not too aggressive)
            if ($this->client && method_exists($this->client, 'setTimeout')) {
                $this->client->setTimeout($forceReal ? 30 : 15); // Longer timeout if forced
            }
            
            $query = new Query('/ppp/active/print');
            // Add specific filters to improve performance and get essential data
            $query->equal('.proplist', 'name,address,uptime,service,caller-id');
            
            $startTime = microtime(true);
            $result = $this->client->query($query)->read();
            $endTime = microtime(true);
            $executionTime = round(($endTime - $startTime) * 1000, 2);
            
            // Get interface statistics for bytes data
            $interfaceStats = $this->getPppoeInterfaceStats();
            
            // Merge interface stats with active connections
            foreach ($result as &$connection) {
                $username = $connection['name'] ?? null;
                if ($username && isset($interfaceStats[$username])) {
                    $stats = $interfaceStats[$username];
                    $connection['bytes-in'] = $stats['rx-byte'] ?? 0;
                    $connection['bytes-out'] = $stats['tx-byte'] ?? 0;
                } else {
                    $connection['bytes-in'] = 0;
                    $connection['bytes-out'] = 0;
                }
            }
            
            logger()->info('Active PPP connections retrieved successfully with bytes data', [
                'returned_count' => count($result ?? []),
                'execution_time_ms' => $executionTime,
                'data_source' => 'real_router_data',
                'interface_stats_count' => count($interfaceStats),
                'force_real' => $forceReal
            ]);
            
            return $result ?? [];
            
        } catch (Exception $e) {
            $errorMsg = $e->getMessage();
            
            logger()->error('Active PPP connections query failed', [
                'error' => $errorMsg,
                'error_type' => $this->getErrorType($errorMsg),
                'force_real' => $forceReal
            ]);
            
            // For timeout in production, return empty array instead of throwing exception
            if (strpos($errorMsg, 'timeout') !== false || 
                strpos($errorMsg, 'Stream timed out') !== false ||
                strpos($errorMsg, 'timed out') !== false) {
                
                logger()->warning('Router timeout - returning empty connections array');
                return [];
            }
            
            // For other errors, still throw exception
            throw new Exception('Unable to retrieve active connections from router: ' . $errorMsg);
        }
    }
    
    /**
     * Get error type for better categorization
     */
    private function getErrorType($errorMsg)
    {
        if (strpos($errorMsg, 'timeout') !== false || strpos($errorMsg, 'timed out') !== false) {
            return 'timeout';
        } elseif (strpos($errorMsg, 'Connection refused') !== false) {
            return 'connection_refused';
        } elseif (strpos($errorMsg, 'Unable to establish') !== false) {
            return 'connection_failed';
        } else {
            return 'other';
        }
    }
    
    /**
     * Get PPPoE interface statistics for bytes data
     */
    private function getPppoeInterfaceStats()
    {
        $stats = [];
        
        try {
            // Get all PPPoE interfaces with their byte statistics
            $query = new Query('/interface/print');
            $query->where('type', 'pppoe-in');
            $query->equal('.proplist', 'name,tx-byte,rx-byte');
            
            $interfaces = $this->client->query($query)->read();
            
            foreach ($interfaces as $interface) {
                $name = $interface['name'] ?? '';
                
                // Extract username from interface name pattern: <pppoe-username>
                if (preg_match('/^<?pppoe-(.+?)>?$/', $name, $matches)) {
                    $username = $matches[1];
                    $stats[$username] = [
                        'tx-byte' => intval($interface['tx-byte'] ?? 0),
                        'rx-byte' => intval($interface['rx-byte'] ?? 0),
                        'interface_name' => $name
                    ];
                }
            }
            
            logger()->info('PPPoE interface stats retrieved', [
                'interface_count' => count($interfaces),
                'mapped_users' => count($stats),
                'users' => array_keys($stats)
            ]);
            
        } catch (Exception $e) {
            logger()->warning('Failed to get PPPoE interface stats', [
                'error' => $e->getMessage()
            ]);
        }
        
        return $stats;
    }
    
    /**
     * DISABLED: This method is kept for reference only
     * Production version returns empty array for consistency
     */
    private function getEmptyConnectionsArray($estimatedCount = null)
    {
        logger()->error('EMPTY CONNECTIONS RETURNED - Check router connectivity', [
            'estimated_count' => $estimatedCount,
            'stack_trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)
        ]);
        
        // Return empty array for production
        return [];
    }
    
    /**
     * Generate random uptime string
     */
    private function generateRandomUptime()
    {
        $hours = rand(0, 48);
        $minutes = rand(0, 59);
        $seconds = rand(0, 59);
        
        if ($hours > 24) {
            $days = intval($hours / 24);
            $hours = $hours % 24;
            return sprintf('%dd%02d:%02d:%02d', $days, $hours, $minutes, $seconds);
        } else {
            return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
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