<?php

namespace App\Services;

use RouterOS\Client;
use RouterOS\Query;
use Exception;
use Illuminate\Support\Facades\Log;
use App\Models\MikrotikSetting;

class MikrotikApiService
{
    protected $client;
    protected $config;
    protected $isConnected = false;

    public function __construct()
    {
        $this->loadConfig();
    }

    /**
     * Load MikroTik configuration from database
     */
    private function loadConfig()
    {
        $this->config = MikrotikSetting::where('is_active', true)->first();
        
        if (!$this->config) {
            throw new Exception('No active MikroTik configuration found');
        }
    }

    /**
     * Connect to MikroTik RouterOS
     */
    public function connect()
    {
        try {
            $this->client = new Client([
                'host' => $this->config->host,
                'user' => $this->config->username,
                'pass' => $this->config->password,
                'port' => $this->config->port ?? 8728,
                'timeout' => $this->config->timeout ?? 30,
                'ssl' => $this->config->use_ssl ?? false,
            ]);

            $this->isConnected = true;
            Log::info('MikroTik connection established', ['host' => $this->config->host]);
            
            return true;
        } catch (Exception $e) {
            Log::error('MikroTik connection failed', [
                'host' => $this->config->host,
                'error' => $e->getMessage()
            ]);
            
            $this->isConnected = false;
            throw new Exception('Failed to connect to MikroTik: ' . $e->getMessage());
        }
    }

    /**
     * Disconnect from MikroTik
     */
    public function disconnect()
    {
        if ($this->client) {
            $this->client = null;
            $this->isConnected = false;
        }
    }

    /**
     * Check if connected
     */
    public function isConnected()
    {
        return $this->isConnected && $this->client !== null;
    }

    /**
     * Execute query with automatic connection handling
     */
    private function executeQuery(Query $query)
    {
        if (!$this->isConnected()) {
            $this->connect();
        }

        return $this->client->query($query)->read();
    }

    // ============ PPP PROFILE MANAGEMENT ============

    /**
     * Get all PPP profiles
     */
    public function getPppProfiles()
    {
        try {
            $query = new Query('/ppp/profile/print');
            return $this->executeQuery($query);
        } catch (Exception $e) {
            Log::error('Failed to get PPP profiles', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Create PPP profile
     */
    public function createPppProfile($data)
    {
        try {
            $query = new Query('/ppp/profile/add');
            $query->equal('name', $data['name']);
            $query->equal('rate-limit', $data['rate_limit']);
            
            if (isset($data['local_address'])) {
                $query->equal('local-address', $data['local_address']);
            }
            if (isset($data['remote_address'])) {
                $query->equal('remote-address', $data['remote_address']);
            }
            if (isset($data['dns_server'])) {
                $query->equal('dns-server', $data['dns_server']);
            }
            if (isset($data['only_one']) && $data['only_one']) {
                $query->equal('only-one', 'yes');
            }

            return $this->executeQuery($query);
        } catch (Exception $e) {
            Log::error('Failed to create PPP profile', ['error' => $e->getMessage(), 'data' => $data]);
            throw $e;
        }
    }

    /**
     * Update PPP profile
     */
    public function updatePppProfile($id, $data)
    {
        try {
            $query = new Query('/ppp/profile/set');
            $query->equal('.id', $id);
            
            if (isset($data['name'])) $query->equal('name', $data['name']);
            if (isset($data['rate_limit'])) $query->equal('rate-limit', $data['rate_limit']);
            if (isset($data['local_address'])) $query->equal('local-address', $data['local_address']);
            if (isset($data['remote_address'])) $query->equal('remote-address', $data['remote_address']);
            if (isset($data['dns_server'])) $query->equal('dns-server', $data['dns_server']);
            
            return $this->executeQuery($query);
        } catch (Exception $e) {
            Log::error('Failed to update PPP profile', ['error' => $e->getMessage(), 'id' => $id]);
            throw $e;
        }
    }

    /**
     * Delete PPP profile
     */
    public function deletePppProfile($id)
    {
        try {
            $query = new Query('/ppp/profile/remove');
            $query->equal('.id', $id);
            
            return $this->executeQuery($query);
        } catch (Exception $e) {
            Log::error('Failed to delete PPP profile', ['error' => $e->getMessage(), 'id' => $id]);
            throw $e;
        }
    }

    // ============ PPP SECRET MANAGEMENT ============

    /**
     * Get all PPP secrets
     */
    public function getPppSecrets()
    {
        try {
            $query = new Query('/ppp/secret/print');
            return $this->executeQuery($query);
        } catch (Exception $e) {
            Log::error('Failed to get PPP secrets', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Create PPP secret
     */
    public function createPppSecret($data)
    {
        try {
            $query = new Query('/ppp/secret/add');
            $query->equal('name', $data['username']);
            $query->equal('password', $data['password']);
            $query->equal('service', $data['service'] ?? 'pppoe');
            $query->equal('profile', $data['profile']);
            
            if (isset($data['local_address'])) {
                $query->equal('local-address', $data['local_address']);
            }
            if (isset($data['remote_address'])) {
                $query->equal('remote-address', $data['remote_address']);
            }
            if (isset($data['comment'])) {
                $query->equal('comment', $data['comment']);
            }
            if (isset($data['disabled']) && $data['disabled']) {
                $query->equal('disabled', 'yes');
            }

            return $this->executeQuery($query);
        } catch (Exception $e) {
            Log::error('Failed to create PPP secret', ['error' => $e->getMessage(), 'data' => $data]);
            throw $e;
        }
    }

    /**
     * Update PPP secret
     */
    public function updatePppSecret($id, $data)
    {
        try {
            $query = new Query('/ppp/secret/set');
            $query->equal('.id', $id);
            
            if (isset($data['username'])) $query->equal('name', $data['username']);
            if (isset($data['password'])) $query->equal('password', $data['password']);
            if (isset($data['service'])) $query->equal('service', $data['service']);
            if (isset($data['profile'])) $query->equal('profile', $data['profile']);
            if (isset($data['local_address'])) $query->equal('local-address', $data['local_address']);
            if (isset($data['remote_address'])) $query->equal('remote-address', $data['remote_address']);
            if (isset($data['comment'])) $query->equal('comment', $data['comment']);
            if (isset($data['disabled'])) {
                $query->equal('disabled', $data['disabled'] ? 'yes' : 'no');
            }
            
            return $this->executeQuery($query);
        } catch (Exception $e) {
            Log::error('Failed to update PPP secret', ['error' => $e->getMessage(), 'id' => $id]);
            throw $e;
        }
    }

    /**
     * Delete PPP secret
     */
    public function deletePppSecret($id)
    {
        try {
            $query = new Query('/ppp/secret/remove');
            $query->equal('.id', $id);
            
            return $this->executeQuery($query);
        } catch (Exception $e) {
            Log::error('Failed to delete PPP secret', ['error' => $e->getMessage(), 'id' => $id]);
            throw $e;
        }
    }

    /**
     * Enable PPP secret
     */
    public function enablePppSecret($id)
    {
        try {
            $query = new Query('/ppp/secret/enable');
            $query->equal('.id', $id);
            
            return $this->executeQuery($query);
        } catch (Exception $e) {
            Log::error('Failed to enable PPP secret', ['error' => $e->getMessage(), 'id' => $id]);
            throw $e;
        }
    }

    /**
     * Disable PPP secret (Isolir)
     */
    public function disablePppSecret($id)
    {
        try {
            $query = new Query('/ppp/secret/disable');
            $query->equal('.id', $id);
            
            return $this->executeQuery($query);
        } catch (Exception $e) {
            Log::error('Failed to disable PPP secret', ['error' => $e->getMessage(), 'id' => $id]);
            throw $e;
        }
    }

    // ============ PPP ACTIVE SESSIONS ============

    /**
     * Get active PPP sessions
     */
    public function getActivePppSessions()
    {
        try {
            $query = new Query('/ppp/active/print');
            return $this->executeQuery($query);
        } catch (Exception $e) {
            Log::error('Failed to get active PPP sessions', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Disconnect PPP session
     */
    public function disconnectPppSession($id)
    {
        try {
            $query = new Query('/ppp/active/remove');
            $query->equal('.id', $id);
            
            return $this->executeQuery($query);
        } catch (Exception $e) {
            Log::error('Failed to disconnect PPP session', ['error' => $e->getMessage(), 'id' => $id]);
            throw $e;
        }
    }

    // ============ SYSTEM INFO ============

    /**
     * Get system info
     */
    public function getSystemInfo()
    {
        try {
            $query = new Query('/system/resource/print');
            return $this->executeQuery($query);
        } catch (Exception $e) {
            Log::error('Failed to get system info', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Test connection
     */
    public function testConnection()
    {
        try {
            $this->connect();
            $result = $this->getSystemInfo();
            $this->disconnect();
            
            return !empty($result);
        } catch (Exception $e) {
            return false;
        }
    }
}
