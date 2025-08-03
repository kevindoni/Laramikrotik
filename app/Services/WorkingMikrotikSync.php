<?php

namespace App\Services;

use App\Models\PppProfile;
use App\Models\PppSecret;
use Exception;
use RouterOS\Query;
use Illuminate\Support\Facades\Log;

class WorkingMikrotikSync extends MikrotikService
{
    /**
     * Get PPP profiles using working property query approach.
     */
    public function getWorkingPppProfiles()
    {
        if (!$this->isConnected()) {
            $this->connect();
        }
        
        Log::info('Starting working PPP profiles retrieval');
        
        try {
            // Use property query that works (shown in debug)
            $query = new Query('/ppp/profile/print');
            $query->equal('?=.id');
            $query->equal('?=name');
            $query->equal('?=rate-limit');
            $query->equal('?=local-address');
            $query->equal('?=remote-address');
            $query->equal('?=only-one');
            $query->equal('?=comment');
            
            // Set reasonable timeout
            if ($this->client && method_exists($this->client, 'setTimeout')) {
                $this->client->setTimeout(45);
            }
            
            Log::info('Executing property query for PPP profiles');
            $result = $this->client->query($query)->read();
            
            Log::info('Raw query result', ['result' => $result]);
            
            // Convert indexed result to sequential array
            $profiles = [];
            foreach ($result as $key => $profile) {
                // Skip entries without proper ID and name
                if (!is_array($profile) || !isset($profile['.id']) || !isset($profile['name'])) {
                    Log::info('Skipping profile entry without proper structure', [
                        'key' => $key,
                        'has_id' => isset($profile['.id']),
                        'has_name' => isset($profile['name'])
                    ]);
                    continue;
                }
                
                $profiles[] = $profile;
                Log::info('Added profile to results', [
                    'name' => $profile['name'],
                    'id' => $profile['.id']
                ]);
            }
            
            Log::info('Working profiles retrieval completed', [
                'total_raw_results' => count($result),
                'valid_profiles' => count($profiles)
            ]);
            
            return $profiles;
            
        } catch (Exception $e) {
            Log::error('Working profiles retrieval failed', [
                'error' => $e->getMessage()
            ]);
            throw new Exception('Failed to get PPP profiles (working method): ' . $e->getMessage());
        }
    }
    
    /**
     * Sync PPP profiles using working method.
     */
    public function syncPppProfilesWorking()
    {
        try {
            Log::info('Starting working PPP profiles sync');
            
            $mikrotikProfiles = $this->getWorkingPppProfiles();
            $syncedCount = 0;
            $skippedCount = 0;
            $errors = [];

            Log::info('Processing profiles for sync', ['count' => count($mikrotikProfiles)]);

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
            
            Log::info('Working profiles sync completed successfully', $result);
            
            return $result;
            
        } catch (Exception $e) {
            Log::error('Working profiles sync failed', [
                'error' => $e->getMessage()
            ]);
            throw new Exception('Failed to sync PPP profiles (working method): ' . $e->getMessage());
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