<?php

namespace App\Services;

use App\Models\PppProfile;
use App\Models\PppSecret;
use App\Models\MikrotikSetting;
use RouterOS\Client;
use RouterOS\Config;
use RouterOS\Query;
use Exception;
use Illuminate\Support\Facades\Log;

class RealMikrotikSync
{
    private $client;
    private $setting;

    public function __construct()
    {
        // Load settings from database like MikrotikService does
        $this->setting = MikrotikSetting::getActive();
        if (!$this->setting) {
            throw new Exception('No active MikroTik setting found in database.');
        }
    }

    private function connect()
    {
        if (!$this->client) {
            try {
                $port = $this->setting->port ?: ($this->setting->use_ssl ? 8729 : 8728);
                
                $config = new Config([
                    'host' => $this->setting->host,
                    'user' => $this->setting->username,
                    'pass' => $this->setting->password,
                    'port' => (int) $port,
                    'timeout' => 30,
                    'attempts' => 1,
                    'delay' => 2,
                ]);

                if ($this->setting->use_ssl) {
                    $config->set('ssl', true);
                    $config->set('ssl_options', [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true,
                    ]);
                }

                $this->client = new Client($config);
                
                // Test connection with simple query
                $query = new Query('/system/identity/print');
                $result = $this->client->query($query)->read();
                
                if (empty($result) || !is_array($result)) {
                    throw new Exception('Invalid response from MikroTik router');
                }
                
                Log::info('RealMikrotikSync: Connection successful', [
                    'host' => $this->setting->host,
                    'identity' => $result[0]['name'] ?? 'unknown'
                ]);
                
            } catch (Exception $e) {
                $this->client = null;
                throw new Exception('Failed to connect to MikroTik: ' . $e->getMessage());
            }
        }
        return $this->client;
    }

    public function syncRealDataFromMikrotik()
    {
        try {
            $client = $this->connect();
            
            $results = [
                'profiles_synced' => 0,
                'profiles_skipped' => 0,
                'secrets_synced' => 0,
                'secrets_skipped' => 0,
                'errors' => [],
                'real_data' => true
            ];

            Log::info('🔄 Starting REAL data sync from MikroTik...');

            // Sync PPP Profiles - get REAL data from router
            try {
                $profiles = $this->getRealPppProfiles($client);
                Log::info('📋 Found ' . count($profiles) . ' REAL profiles from MikroTik');
                
                foreach ($profiles as $profile) {
                    try {
                        $existingProfile = PppProfile::where('name', $profile['name'])->first();
                        
                        if (!$existingProfile) {
                            PppProfile::create([
                                'name' => $profile['name'],
                                'local_address' => $profile['local-address'] ?? null,
                                'remote_address' => $profile['remote-address'] ?? null,
                                'rate_limit' => $profile['rate-limit'] ?? null,
                                'session_timeout' => $profile['session-timeout'] ?? null,
                                'idle_timeout' => $profile['idle-timeout'] ?? null,
                                'only_one' => isset($profile['only-one']) ? ($profile['only-one'] === 'yes') : false,
                                'change_tcp_mss' => isset($profile['change-tcp-mss']) ? ($profile['change-tcp-mss'] === 'yes') : false,
                                'use_compression' => isset($profile['use-compression']) ? ($profile['use-compression'] === 'yes') : false,
                                'use_encryption' => isset($profile['use-encryption']) ? ($profile['use-encryption'] === 'yes') : false,
                                'is_active' => !isset($profile['disabled']) || $profile['disabled'] !== 'true',
                                'comment' => ($profile['comment'] ?? '') . ' - REAL MikroTik Data',
                                'price' => 0, // Default price
                                'auto_sync' => true,
                                'mikrotik_id' => $profile['.id'] ?? null,
                            ]);
                            $results['profiles_synced']++;
                            Log::info("✅ Created REAL profile: {$profile['name']}");
                        } else {
                            $results['profiles_skipped']++;
                            Log::info("⏭️ Profile already exists: {$profile['name']}");
                        }
                    } catch (Exception $e) {
                        $error = "Profile sync error: " . $e->getMessage();
                        $results['errors'][] = $error;
                        Log::error($error);
                    }
                }
            } catch (Exception $e) {
                $error = "Failed to get REAL PPP Profiles: " . $e->getMessage();
                $results['errors'][] = $error;
                Log::error($error);
            }

            // Sync PPP Secrets - get REAL data from router
            try {
                $secrets = $this->getRealPppSecrets($client);
                Log::info('👤 Found ' . count($secrets) . ' REAL secrets from MikroTik');
                
                foreach ($secrets as $secret) {
                    try {
                        $existingSecret = PppSecret::where('username', $secret['name'])->first();
                        
                        if (!$existingSecret) {
                            // Find matching profile
                            $profile = null;
                            if (isset($secret['profile'])) {
                                $profile = PppProfile::where('name', $secret['profile'])->first();
                            }

                            PppSecret::create([
                                'customer_id' => null, // Will be linked later
                                'ppp_profile_id' => $profile ? $profile->id : null,
                                'username' => $secret['name'],
                                'password' => $secret['password'] ?? 'N/A',
                                'service' => $secret['service'] ?? 'pppoe',
                                'remote_address' => $secret['remote-address'] ?? null,
                                'local_address' => $secret['local-address'] ?? null,
                                'mikrotik_id' => $secret['.id'] ?? null,
                                'is_active' => !isset($secret['disabled']) || $secret['disabled'] !== 'true',
                                'comment' => ($secret['comment'] ?? '') . ' - REAL MikroTik Data',
                                'installation_date' => now()->toDateString(),
                                'auto_sync' => true,
                            ]);
                            $results['secrets_synced']++;
                            Log::info("✅ Created REAL secret: {$secret['name']}");
                        } else {
                            $results['secrets_skipped']++;
                            Log::info("⏭️ Secret already exists: {$secret['name']}");
                        }
                    } catch (Exception $e) {
                        $error = "Secret sync error: " . $e->getMessage();
                        $results['errors'][] = $error;
                        Log::error($error);
                    }
                }
            } catch (Exception $e) {
                $error = "Failed to get REAL PPP Secrets: " . $e->getMessage();
                $results['errors'][] = $error;
                Log::error($error);
            }

            return $results;

        } catch (Exception $e) {
            Log::error('❌ Failed to sync REAL data from MikroTik: ' . $e->getMessage());
            throw $e;
        }
    }

    private function getRealPppProfiles($client)
    {
        Log::info('🔍 Querying REAL PPP Profiles from MikroTik...');
        
        // Use simple query without complex filters to avoid timeout
        $query = new Query('/ppp/profile/print');
        $response = $client->query($query)->read();
        
        Log::info('📊 Raw PPP Profiles response: ' . json_encode($response));
        return $response;
    }

    private function getRealPppSecrets($client)
    {
        Log::info('🔍 Querying REAL PPP Secrets from MikroTik...');
        
        // Use simple query without complex filters to avoid timeout
        $query = new Query('/ppp/secret/print');
        $response = $client->query($query)->read();
        
        Log::info('📊 Raw PPP Secrets response: ' . json_encode($response));
        return $response;
    }

    public function syncRealPppProfiles()
    {
        try {
            $client = $this->connect();
            $profiles = $this->getRealPppProfiles($client);
            
            $synced = 0;
            $skipped = 0;
            
            foreach ($profiles as $profile) {
                $existing = PppProfile::where('name', $profile['name'])->first();
                if (!$existing) {
                    PppProfile::create([
                        'name' => $profile['name'],
                        'local_address' => $profile['local-address'] ?? null,
                        'remote_address' => $profile['remote-address'] ?? null,
                        'rate_limit' => $profile['rate-limit'] ?? null,
                        'session_timeout' => $profile['session-timeout'] ?? null,
                        'idle_timeout' => $profile['idle-timeout'] ?? null,
                        'only_one' => isset($profile['only-one']) ? ($profile['only-one'] === 'yes') : false,
                        'change_tcp_mss' => isset($profile['change-tcp-mss']) ? ($profile['change-tcp-mss'] === 'yes') : false,
                        'use_compression' => isset($profile['use-compression']) ? ($profile['use-compression'] === 'yes') : false,
                        'use_encryption' => isset($profile['use-encryption']) ? ($profile['use-encryption'] === 'yes') : false,
                        'is_active' => !isset($profile['disabled']) || $profile['disabled'] !== 'true',
                        'comment' => ($profile['comment'] ?? '') . ' - REAL MikroTik Data',
                        'price' => 0,
                        'auto_sync' => true,
                        'mikrotik_id' => $profile['.id'] ?? null,
                    ]);
                    $synced++;
                } else {
                    $skipped++;
                }
            }
            
            return ['synced' => $synced, 'skipped' => $skipped, 'total_found' => count($profiles)];
            
        } catch (Exception $e) {
            Log::error('Failed to sync REAL PPP Profiles: ' . $e->getMessage());
            throw $e;
        }
    }

    public function syncRealPppSecrets()
    {
        try {
            $client = $this->connect();
            $secrets = $this->getRealPppSecrets($client);
            
            $synced = 0;
            $skipped = 0;
            
            foreach ($secrets as $secret) {
                $existing = PppSecret::where('username', $secret['name'])->first();
                if (!$existing) {
                    // Find matching profile
                    $profile = null;
                    if (isset($secret['profile'])) {
                        $profile = PppProfile::where('name', $secret['profile'])->first();
                    }

                    PppSecret::create([
                        'customer_id' => null,
                        'ppp_profile_id' => $profile ? $profile->id : null,
                        'username' => $secret['name'],
                        'password' => $secret['password'] ?? 'N/A',
                        'service' => $secret['service'] ?? 'pppoe',
                        'remote_address' => $secret['remote-address'] ?? null,
                        'local_address' => $secret['local-address'] ?? null,
                        'mikrotik_id' => $secret['.id'] ?? null,
                        'is_active' => !isset($secret['disabled']) || $secret['disabled'] !== 'true',
                        'comment' => ($secret['comment'] ?? '') . ' - REAL MikroTik Data',
                        'installation_date' => now()->toDateString(),
                        'auto_sync' => true,
                    ]);
                    $synced++;
                } else {
                    $skipped++;
                }
            }
            
            return ['synced' => $synced, 'skipped' => $skipped, 'total_found' => count($secrets)];
            
        } catch (Exception $e) {
            Log::error('Failed to sync REAL PPP Secrets: ' . $e->getMessage());
            throw $e;
        }
    }
}