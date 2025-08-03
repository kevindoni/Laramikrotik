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

class HotspotToPppSync
{
    private $client;
    private $setting;

    public function __construct()
    {
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
                
                // Test connection
                $query = new Query('/system/identity/print');
                $result = $this->client->query($query)->read();
                
                Log::info('HotspotToPppSync: Connection successful', [
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

    public function syncRealHotspotDataToPpp()
    {
        try {
            $client = $this->connect();
            
            $results = [
                'profiles_synced' => 0,
                'profiles_skipped' => 0,
                'secrets_synced' => 0,
                'secrets_skipped' => 0,
                'errors' => [],
                'real_data' => true,
                'data_source' => 'hotspot'
            ];

            Log::info('🔄 Starting REAL Hotspot to PPP sync...');

            // Step 1: Sync Hotspot Profiles as PPP Profiles
            try {
                $hotspotProfiles = $this->getRealHotspotProfiles($client);
                Log::info('📋 Found ' . count($hotspotProfiles) . ' REAL Hotspot profiles');
                
                foreach ($hotspotProfiles as $profile) {
                    try {
                        $existingProfile = PppProfile::where('name', $profile['name'])->first();
                        
                        if (!$existingProfile) {
                            PppProfile::create([
                                'name' => $profile['name'],
                                'local_address' => null,
                                'remote_address' => null,
                                'rate_limit' => $profile['rate-limit'] ?? null,
                                'session_timeout' => null,
                                'idle_timeout' => null,
                                'only_one' => isset($profile['only-one']) ? ($profile['only-one'] === 'yes') : false,
                                'change_tcp_mss' => isset($profile['change-tcp-mss']) ? ($profile['change-tcp-mss'] === 'yes') : false,
                                'use_compression' => isset($profile['use-compression']) ? ($profile['use-compression'] === 'yes') : false,
                                'use_encryption' => isset($profile['use-encryption']) ? ($profile['use-encryption'] === 'yes') : false,
                                'is_active' => !isset($profile['disabled']) || $profile['disabled'] !== 'true',
                                'comment' => ($profile['default'] ?? 'Hotspot Profile') . ' - REAL MikroTik Hotspot Data',
                                'price' => 50000, // Default price
                                'auto_sync' => true,
                                'mikrotik_id' => $profile['.id'] ?? null,
                            ]);
                            $results['profiles_synced']++;
                            Log::info("✅ Created REAL hotspot profile: {$profile['name']}");
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
                $error = "Failed to get REAL Hotspot Profiles: " . $e->getMessage();
                $results['errors'][] = $error;
                Log::error($error);
            }

            // Step 2: Sync Hotspot Users as PPP Secrets
            try {
                $hotspotUsers = $this->getRealHotspotUsers($client);
                Log::info('👤 Found ' . count($hotspotUsers) . ' REAL Hotspot users');
                
                foreach ($hotspotUsers as $user) {
                    try {
                        $existingSecret = PppSecret::where('username', $user['name'])->first();
                        
                        if (!$existingSecret) {
                            // Find matching profile
                            $profile = null;
                            if (isset($user['profile'])) {
                                $profile = PppProfile::where('name', $user['profile'])->first();
                            }
                            // If no specific profile, use first available profile
                            if (!$profile) {
                                $profile = PppProfile::first();
                            }

                            PppSecret::create([
                                'customer_id' => null,
                                'ppp_profile_id' => $profile ? $profile->id : null,
                                'username' => $user['name'],
                                'password' => $user['password'] ?? 'N/A',
                                'service' => 'hotspot', // Mark as hotspot
                                'remote_address' => null,
                                'local_address' => null,
                                'mikrotik_id' => $user['.id'] ?? null,
                                'is_active' => !isset($user['disabled']) || $user['disabled'] !== 'true',
                                'comment' => 'REAL MikroTik Hotspot User - ' . ($user['comment'] ?? 'No comment'),
                                'installation_date' => now()->toDateString(),
                                'auto_sync' => true,
                            ]);
                            $results['secrets_synced']++;
                            Log::info("✅ Created REAL hotspot user: {$user['name']}");
                        } else {
                            $results['secrets_skipped']++;
                            Log::info("⏭️ Secret already exists: {$user['name']}");
                        }
                    } catch (Exception $e) {
                        $error = "Secret sync error: " . $e->getMessage();
                        $results['errors'][] = $error;
                        Log::error($error);
                    }
                }
            } catch (Exception $e) {
                $error = "Failed to get REAL Hotspot Users: " . $e->getMessage();
                $results['errors'][] = $error;
                Log::error($error);
            }

            return $results;

        } catch (Exception $e) {
            Log::error('❌ Failed to sync REAL Hotspot data: ' . $e->getMessage());
            throw $e;
        }
    }

    private function getRealHotspotProfiles($client)
    {
        Log::info('🔍 Querying REAL Hotspot Profiles from MikroTik...');
        
        $query = new Query('/ip/hotspot/profile/print');
        $response = $client->query($query)->read();
        
        Log::info('📊 Raw Hotspot Profiles response: ' . json_encode($response));
        return $response;
    }

    private function getRealHotspotUsers($client)
    {
        Log::info('🔍 Querying REAL Hotspot Users from MikroTik...');
        
        $query = new Query('/ip/hotspot/user/print');
        $response = $client->query($query)->read();
        
        Log::info('📊 Raw Hotspot Users response: ' . json_encode($response));
        return $response;
    }

    public function syncRealHotspotProfiles()
    {
        try {
            $client = $this->connect();
            $profiles = $this->getRealHotspotProfiles($client);
            
            $synced = 0;
            $skipped = 0;
            
            foreach ($profiles as $profile) {
                $existing = PppProfile::where('name', $profile['name'])->first();
                if (!$existing) {
                    PppProfile::create([
                        'name' => $profile['name'],
                        'rate_limit' => $profile['rate-limit'] ?? null,
                        'only_one' => isset($profile['only-one']) ? ($profile['only-one'] === 'yes') : false,
                        'is_active' => !isset($profile['disabled']) || $profile['disabled'] !== 'true',
                        'comment' => 'REAL MikroTik Hotspot Profile - ' . ($profile['default'] ?? 'Custom Profile'),
                        'price' => 50000,
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
            Log::error('Failed to sync REAL Hotspot Profiles: ' . $e->getMessage());
            throw $e;
        }
    }

    public function syncRealHotspotUsers()
    {
        try {
            $client = $this->connect();
            $users = $this->getRealHotspotUsers($client);
            
            $synced = 0;
            $skipped = 0;
            
            foreach ($users as $user) {
                $existing = PppSecret::where('username', $user['name'])->first();
                if (!$existing) {
                    // Find matching profile or use first available
                    $profile = null;
                    if (isset($user['profile'])) {
                        $profile = PppProfile::where('name', $user['profile'])->first();
                    }
                    if (!$profile) {
                        $profile = PppProfile::first();
                    }

                    PppSecret::create([
                        'customer_id' => null,
                        'ppp_profile_id' => $profile ? $profile->id : null,
                        'username' => $user['name'],
                        'password' => $user['password'] ?? 'N/A',
                        'service' => 'hotspot',
                        'mikrotik_id' => $user['.id'] ?? null,
                        'is_active' => !isset($user['disabled']) || $user['disabled'] !== 'true',
                        'comment' => 'REAL MikroTik Hotspot User - ' . ($user['comment'] ?? 'No comment'),
                        'installation_date' => now()->toDateString(),
                        'auto_sync' => true,
                    ]);
                    $synced++;
                } else {
                    $skipped++;
                }
            }
            
            return ['synced' => $synced, 'skipped' => $skipped, 'total_found' => count($users)];
            
        } catch (Exception $e) {
            Log::error('Failed to sync REAL Hotspot Users: ' . $e->getMessage());
            throw $e;
        }
    }
}