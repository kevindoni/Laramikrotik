<?php

namespace App\Services;

use App\Models\PppProfile;
use App\Models\PppSecret;
use Exception;
use RouterOS\Query;
use Illuminate\Support\Facades\Log;

class OptimizedMikrotikService extends MikrotikService
{
    /**
     * Get PPP profiles with optimized batch processing and timeout handling.
     */
    public function getOptimizedPppProfiles($batchSize = 3)
    {
        if (!$this->isConnected()) {
            $this->connect();
        }
        
        Log::info('Starting optimized PPP profiles retrieval', ['batch_size' => $batchSize]);
        
        $allProfiles = [];
        $start = 0;
        $maxAttempts = 2; // Reduced attempts for faster failure detection
        
        try {
            do {
                $batch = null;
                $lastException = null;
                
                for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
                    try {
                        if (!$this->client) {
                            $this->connect();
                        }
                        
                        Log::info("Retrieving profiles batch", [
                            'start' => $start,
                            'count' => $batchSize,
                            'attempt' => $attempt
                        ]);
                        
                        // Use optimized query with smaller timeout
                        $query = new Query('/ppp/profile/print');
                        $query->equal('count', $batchSize);
                        $query->equal('from', $start);
                        
                        // Set shorter timeout for batch operations
                        if ($this->client && method_exists($this->client, 'setTimeout')) {
                            $this->client->setTimeout(30); // 30 seconds per batch
                        }
                        
                        $batch = $this->client->query($query)->read();
                        
                        Log::info("Successfully retrieved profiles batch", [
                            'count' => count($batch),
                            'start' => $start,
                            'attempt' => $attempt
                        ]);
                        
                        break; // Success, exit retry loop
                        
                    } catch (Exception $e) {
                        $lastException = $e;
                        
                        Log::warning("Batch retrieval failed", [
                            'start' => $start,
                            'attempt' => $attempt,
                            'error' => $e->getMessage()
                        ]);
                        
                        if ($attempt < $maxAttempts) {
                            $this->client = null; // Reset connection
                            sleep(1); // Short delay
                            continue;
                        }
                        break;
                    }
                }
                
                if ($batch === null) {
                    throw $lastException ?: new Exception('Failed to retrieve batch after all attempts');
                }
                
                // If batch is empty, we're done
                if (empty($batch)) {
                    Log::info("Reached end of profiles - empty batch", ['start' => $start]);
                    break;
                }
                
                $allProfiles = array_merge($allProfiles, $batch);
                $start += $batchSize;
                
                Log::info("Batch processed successfully", [
                    'batch_count' => count($batch),
                    'total_so_far' => count($allProfiles),
                    'next_start' => $start
                ]);
                
                // Small delay between batches
                if (count($batch) === $batchSize) {
                    usleep(100000); // 0.1 second
                }
                
            } while (count($batch) === $batchSize);
            
            Log::info("Completed optimized profiles retrieval", [
                'total_count' => count($allProfiles),
                'batch_size' => $batchSize
            ]);
            
            return $allProfiles;
            
        } catch (Exception $e) {
            Log::error('Optimized profiles retrieval failed', [
                'error' => $e->getMessage(),
                'profiles_retrieved' => count($allProfiles)
            ]);
            
            throw new Exception('Failed to get PPP profiles: ' . $e->getMessage());
        }
    }
    
    /**
     * Sync PPP profiles with optimized approach.
     */
    public function syncPppProfilesOptimized()
    {
        try {
            Log::info('Starting optimized PPP profiles sync');
            
            $mikrotikProfiles = $this->getOptimizedPppProfiles(3); // Very small batches
            $syncedCount = 0;
            $skippedCount = 0;
            $errors = [];

            foreach ($mikrotikProfiles as $mtProfile) {
                try {
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
                        'price' => 0,
                        'mikrotik_id' => $mtProfile['.id'],
                    ];

                    PppProfile::updateOrCreate(
                        ['name' => $mtProfile['name']],
                        $profileData
                    );

                    $syncedCount++;
                    
                    Log::info('Profile synced', [
                        'name' => $mtProfile['name'],
                        'mikrotik_id' => $mtProfile['.id']
                    ]);
                    
                } catch (Exception $e) {
                    $errors[] = "Profile '{$mtProfile['name']}': " . $e->getMessage();
                    Log::error('Failed to sync individual profile', [
                        'profile_name' => $mtProfile['name'] ?? 'unknown',
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
            
            Log::info('Optimized profiles sync completed', $result);
            
            return $result;
            
        } catch (Exception $e) {
            Log::error('Optimized profiles sync failed', [
                'error' => $e->getMessage()
            ]);
            throw new Exception('Failed to sync PPP profiles (optimized): ' . $e->getMessage());
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