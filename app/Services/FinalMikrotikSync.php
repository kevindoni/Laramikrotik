<?php

namespace App\Services;

use App\Models\PppProfile;
use App\Models\PppSecret;
use Exception;
use RouterOS\Query;
use Illuminate\Support\Facades\Log;

class FinalMikrotikSync extends MikrotikService
{
    /**
     * Get PPP profiles using micro-batch approach with extreme short timeouts.
     */
    public function getFinalPppProfiles()
    {
        if (!$this->isConnected()) {
            $this->connect();
        }
        
        Log::info('Starting final PPP profiles retrieval with micro-batching');
        
        try {
            // Set very short timeout for micro operations
            if ($this->client && method_exists($this->client, 'setTimeout')) {
                $this->client->setTimeout(10); // Only 10 seconds
            }
            
            // First, try to get count
            $totalCount = 0;
            try {
                $countQuery = new Query('/ppp/profile/print');
                $countQuery->equal('count-only', '');
                $countResult = $this->client->query($countQuery)->read();
                
                if (isset($countResult['after']['ret'])) {
                    $totalCount = (int) $countResult['after']['ret'];
                    Log::info('Got total profile count', ['count' => $totalCount]);
                }
            } catch (Exception $e) {
                Log::warning('Failed to get count, proceeding with unknown total', [
                    'error' => $e->getMessage()
                ]);
                $totalCount = 10; // Assume some reasonable number
            }
            
            $allProfiles = [];
            $batchSize = 1; // Ultra small batch - 1 profile at a time
            
            for ($start = 0; $start < $totalCount; $start += $batchSize) {
                try {
                    Log::info('Retrieving micro-batch', [
                        'start' => $start,
                        'batch_size' => $batchSize
                    ]);
                    
                    // Reconnect for each batch to ensure fresh connection
                    if (!$this->client) {
                        $this->connect();
                        if (method_exists($this->client, 'setTimeout')) {
                            $this->client->setTimeout(10);
                        }
                    }
                    
                    // Use print with from/count parameters
                    $query = new Query('/ppp/profile/print');
                    $query->equal('from', $start);
                    $query->equal('count', $batchSize);
                    
                    $batch = $this->client->query($query)->read();
                    
                    Log::info('Micro-batch result', [
                        'start' => $start,
                        'result_count' => count($batch),
                        'result_type' => gettype($batch)
                    ]);
                    
                    // Process batch results
                    if (!empty($batch) && is_array($batch)) {
                        foreach ($batch as $key => $profile) {
                            if (is_array($profile) && isset($profile['.id']) && isset($profile['name'])) {
                                $allProfiles[] = $profile;
                                Log::info('Added profile from micro-batch', [
                                    'name' => $profile['name'],
                                    'id' => $profile['.id']
                                ]);
                            } else {
                                Log::info('Skipped invalid profile in micro-batch', [
                                    'key' => $key,
                                    'profile' => $profile
                                ]);
                            }
                        }
                    } else {
                        Log::info('Empty or invalid micro-batch, breaking', [
                            'start' => $start
                        ]);
                        break; // No more data
                    }
                    
                    // Small delay between micro-batches
                    usleep(200000); // 0.2 seconds
                    
                } catch (Exception $e) {
                    Log::warning('Micro-batch failed', [
                        'start' => $start,
                        'error' => $e->getMessage()
                    ]);
                    
                    // For micro-batches, don't retry - just continue to next
                    continue;
                }
            }
            
            Log::info('Final profiles retrieval completed', [
                'total_profiles' => count($allProfiles),
                'expected_count' => $totalCount
            ]);
            
            return $allProfiles;
            
        } catch (Exception $e) {
            Log::error('Final profiles retrieval failed completely', [
                'error' => $e->getMessage()
            ]);
            throw new Exception('Failed to get PPP profiles (final method): ' . $e->getMessage());
        }
    }
    
    /**
     * Sync PPP profiles using final working method.
     */
    public function syncPppProfilesFinal()
    {
        try {
            Log::info('Starting final PPP profiles sync');
            
            $mikrotikProfiles = $this->getFinalPppProfiles();
            $syncedCount = 0;
            $skippedCount = 0;
            $errors = [];

            Log::info('Processing profiles for final sync', ['count' => count($mikrotikProfiles)]);

            foreach ($mikrotikProfiles as $mtProfile) {
                try {
                    $profileName = $mtProfile['name'] ?? 'unknown';
                    
                    // Skip default profiles
                    if (in_array($profileName, ['default', 'default-encryption'])) {
                        $skippedCount++;
                        Log::info('Skipped default profile', ['name' => $profileName]);
                        continue;
                    }

                    $profileData = [
                        'name' => $profileName,
                        'rate_limit' => $mtProfile['rate-limit'] ?? null,
                        'local_address' => $mtProfile['local-address'] ?? null,
                        'remote_address' => $mtProfile['remote-address'] ?? null,
                        'parent_queue' => $mtProfile['parent-queue'] ?? null,
                        'only_one' => isset($mtProfile['only-one']) && $mtProfile['only-one'] === 'yes',
                        'description' => $mtProfile['comment'] ?? null,
                        'is_active' => true,
                        'price' => 0,
                        'mikrotik_id' => $mtProfile['.id'],
                    ];

                    $profile = PppProfile::updateOrCreate(
                        ['name' => $profileName],
                        $profileData
                    );

                    $syncedCount++;
                    
                    Log::info('Profile synced successfully', [
                        'name' => $profileName,
                        'mikrotik_id' => $mtProfile['.id'],
                        'database_id' => $profile->id
                    ]);
                    
                } catch (Exception $e) {
                    $errorMsg = "Profile '{$profileName}': " . $e->getMessage();
                    $errors[] = $errorMsg;
                    Log::error('Failed to sync individual profile', [
                        'profile_name' => $profileName,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $result = [
                'synced' => $syncedCount,
                'skipped' => $skippedCount,
                'total' => count($mikrotikProfiles),
                'errors' => $errors
            ];
            
            Log::info('Final profiles sync completed successfully', $result);
            
            return $result;
            
        } catch (Exception $e) {
            Log::error('Final profiles sync failed', [
                'error' => $e->getMessage()
            ]);
            throw new Exception('Failed to sync PPP profiles (final method): ' . $e->getMessage());
        }
    }
    
    /**
     * Get sync statistics.
     */
    public function getSyncStatistics()
    {
        return [
            'database' => [
                'profiles' => PppProfile::count(),
                'secrets' => PppSecret::count(),
                'active_profiles' => PppProfile::where('is_active', true)->count(),
                'active_secrets' => PppSecret::where('is_active', true)->count(),
            ],
            'last_sync' => [
                'profiles' => PppProfile::max('updated_at'),
                'secrets' => PppSecret::max('updated_at'),
            ],
            'connection_status' => $this->isConnected() ? 'connected' : 'disconnected'
        ];
    }
}