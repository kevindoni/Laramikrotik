<?php

namespace App\Services;

use App\Models\PppProfile;
use App\Models\PppSecret;
use Exception;
use RouterOS\Query;
use Illuminate\Support\Facades\Log;

class CorrectMikrotikSync extends MikrotikService
{
    /**
     * Get PPP profiles using correct RouterOS API syntax.
     */
    public function getCorrectPppProfiles()
    {
        if (!$this->isConnected()) {
            $this->connect();
        }
        
        Log::info('Starting correct PPP profiles retrieval');
        
        try {
            // Set reasonable timeout
            if ($this->client && method_exists($this->client, 'setTimeout')) {
                $this->client->setTimeout(30);
            }
            
            // First get total count to know how many to retrieve
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
                Log::warning('Failed to get count', ['error' => $e->getMessage()]);
                $totalCount = 10; // Fallback
            }
            
            $allProfiles = [];
            $batchSize = 2; // Small batch size to avoid timeout
            
            // Use sequential retrieval with correct syntax
            for ($start = 0; $start < $totalCount; $start += $batchSize) {
                try {
                    Log::info('Retrieving batch', ['start' => $start, 'batch_size' => $batchSize]);
                    
                    // Use correct RouterOS API syntax for pagination
                    $query = new Query('/ppp/profile/print');
                    // Use proper RouterOS syntax: =from=start instead of equal()
                    $query->where('from', $start);
                    if ($start + $batchSize < $totalCount) {
                        $query->where('count', $batchSize);
                    }
                    
                    $batch = $this->client->query($query)->read();
                    
                    Log::info('Batch result', [
                        'start' => $start,
                        'result_count' => count($batch),
                        'raw_data' => json_encode($batch)
                    ]);
                    
                    // Check if we got actual profile data
                    if (!empty($batch) && is_array($batch)) {
                        $validProfiles = 0;
                        foreach ($batch as $key => $profile) {
                            // Skip error messages and non-profile data
                            if (is_array($profile) && 
                                !isset($profile['message']) && 
                                (isset($profile['.id']) || isset($profile['name']))) {
                                
                                $allProfiles[] = $profile;
                                $validProfiles++;
                                
                                Log::info('Added valid profile', [
                                    'name' => $profile['name'] ?? 'unnamed',
                                    'id' => $profile['.id'] ?? 'no-id'
                                ]);
                            } else {
                                Log::info('Skipped invalid profile entry', [
                                    'key' => $key,
                                    'data' => $profile
                                ]);
                            }
                        }
                        
                        // If no valid profiles in this batch, break
                        if ($validProfiles === 0) {
                            Log::info('No valid profiles in batch, ending retrieval', ['start' => $start]);
                            break;
                        }
                    } else {
                        Log::info('Empty batch, ending retrieval', ['start' => $start]);
                        break;
                    }
                    
                    // Small delay between batches
                    usleep(500000); // 0.5 seconds
                    
                } catch (Exception $e) {
                    Log::warning('Batch failed', [
                        'start' => $start,
                        'error' => $e->getMessage()
                    ]);
                    
                    // If it's a timeout, try alternative approach
                    if (strpos($e->getMessage(), 'timeout') !== false) {
                        Log::info('Trying alternative single profile retrieval');
                        try {
                            // Try to get just one profile at this position
                            $singleQuery = new Query('/ppp/profile/print');
                            $singleQuery->where('from', $start);
                            $singleQuery->where('count', 1);
                            
                            $singleResult = $this->client->query($singleQuery)->read();
                            if (!empty($singleResult) && is_array($singleResult)) {
                                foreach ($singleResult as $profile) {
                                    if (is_array($profile) && !isset($profile['message'])) {
                                        $allProfiles[] = $profile;
                                        Log::info('Added single profile', [
                                            'name' => $profile['name'] ?? 'unnamed'
                                        ]);
                                    }
                                }
                            }
                        } catch (Exception $e2) {
                            Log::warning('Single profile retrieval also failed', [
                                'error' => $e2->getMessage()
                            ]);
                        }
                    }
                    
                    // Continue to next batch
                    continue;
                }
            }
            
            Log::info('Correct profiles retrieval completed', [
                'total_profiles' => count($allProfiles),
                'expected_count' => $totalCount
            ]);
            
            return $allProfiles;
            
        } catch (Exception $e) {
            Log::error('Correct profiles retrieval failed', [
                'error' => $e->getMessage()
            ]);
            throw new Exception('Failed to get PPP profiles (correct method): ' . $e->getMessage());
        }
    }
    
    /**
     * Sync PPP profiles using correct method.
     */
    public function syncPppProfilesCorrect()
    {
        try {
            Log::info('Starting correct PPP profiles sync');
            
            $mikrotikProfiles = $this->getCorrectPppProfiles();
            $syncedCount = 0;
            $skippedCount = 0;
            $errors = [];

            Log::info('Processing profiles for correct sync', ['count' => count($mikrotikProfiles)]);

            foreach ($mikrotikProfiles as $mtProfile) {
                try {
                    $profileName = $mtProfile['name'] ?? ('profile_' . uniqid());
                    
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
                        'mikrotik_id' => $mtProfile['.id'] ?? null,
                    ];

                    $profile = PppProfile::updateOrCreate(
                        ['name' => $profileName],
                        $profileData
                    );

                    $syncedCount++;
                    
                    Log::info('Profile synced successfully', [
                        'name' => $profileName,
                        'mikrotik_id' => $mtProfile['.id'] ?? 'none',
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
            
            Log::info('Correct profiles sync completed successfully', $result);
            
            return $result;
            
        } catch (Exception $e) {
            Log::error('Correct profiles sync failed', [
                'error' => $e->getMessage()
            ]);
            throw new Exception('Failed to sync PPP profiles (correct method): ' . $e->getMessage());
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