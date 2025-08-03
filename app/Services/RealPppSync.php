<?php

namespace App\Services;

use App\Models\PppProfile;
use App\Models\PppSecret;
use Illuminate\Support\Facades\Log;
use RouterOS\Query;
use App\Services\MikrotikService;

class RealPppSync
{
    private $mikrotikService;
    private $client;

    public function __construct()
    {
        $this->mikrotikService = new MikrotikService();
    }

    public function syncRealPppData()
    {
        try {
            $this->mikrotikService->connect();
            $this->client = $this->mikrotikService->getClient();
            
            if (!$this->client) {
                throw new \Exception('Failed to connect to MikroTik');
            }
            
            $results = [
                'profiles' => 0,
                'secrets' => 0,
                'active_connections' => 0,
                'source' => 'real_ppp',
                'errors' => []
            ];

            // Clear existing data (avoiding foreign key issues)
            PppSecret::query()->delete();
            PppProfile::query()->delete();

            // 1. Get Active PPP Connections first (we know this works)
            $results['active_connections'] = $this->syncActivePppConnections();

            // 2. Try to get PPP Profiles with shorter timeout
            $results['profiles'] = $this->syncPppProfiles();

            // 3. Try to get PPP Secrets with shorter timeout  
            $results['secrets'] = $this->syncPppSecrets();

            // 4. If profiles/secrets failed, try to extract from active connections
            if ($results['profiles'] == 0 && $results['secrets'] == 0) {
                $extracted = $this->extractFromActiveConnections();
                $results['profiles'] += $extracted['profiles'];
                $results['secrets'] += $extracted['secrets'];
            }

            return $results;

        } catch (\Exception $e) {
            Log::error('RealPppSync failed: ' . $e->getMessage());
            return [
                'profiles' => 0,
                'secrets' => 0,
                'active_connections' => 0,
                'source' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }

    private function syncActivePppConnections()
    {
        try {
            $query = (new Query('/ppp/active/print'));
            $connections = $this->client->query($query)->read();
            
            if (empty($connections)) {
                return 0;
            }

            Log::info('Found ' . count($connections) . ' active PPP connections');
            
            // Store active connection data for analysis
            foreach ($connections as $conn) {
                Log::info('PPP Connection: ' . json_encode($conn));
            }

            return count($connections);

        } catch (\Exception $e) {
            Log::error('Failed to get active PPP connections: ' . $e->getMessage());
            return 0;
        }
    }

    private function syncPppProfiles()
    {
        try {
            $query = (new Query('/ppp/profile/print'));
            $profiles = $this->client->query($query)->read();

            if (empty($profiles)) {
                return 0;
            }

            $synced = 0;
            foreach ($profiles as $profile) {
                PppProfile::updateOrCreate(
                    ['name' => $profile['name'] ?? 'unknown'],
                    [
                        'local_address' => $profile['local-address'] ?? '',
                        'remote_address' => $profile['remote-address'] ?? '',
                        'rate_limit' => $profile['rate-limit'] ?? 'unlimited',
                        'session_timeout' => $profile['session-timeout'] ?? '0',
                        'idle_timeout' => $profile['idle-timeout'] ?? '0',
                        'only_one' => isset($profile['only-one']) ? ($profile['only-one'] === 'yes' ? 1 : 0) : 0,
                        'comment' => $profile['comment'] ?? 'REAL PPP Profile from MikroTik'
                    ]
                );
                $synced++;
            }

            return $synced;

        } catch (\Exception $e) {
            Log::error('Failed to sync PPP profiles: ' . $e->getMessage());
            return 0;
        }
    }

    private function syncPppSecrets()
    {
        try {
            $query = (new Query('/ppp/secret/print'));
            $secrets = $this->client->query($query)->read();

            if (empty($secrets)) {
                return 0;
            }

            $synced = 0;
            foreach ($secrets as $secret) {
                PppSecret::updateOrCreate(
                    ['name' => $secret['name'] ?? 'unknown'],
                    [
                        'password' => $secret['password'] ?? '',
                        'profile' => $secret['profile'] ?? 'default',
                        'service' => $secret['service'] ?? 'ppp',
                        'caller_id' => $secret['caller-id'] ?? '',
                        'local_address' => $secret['local-address'] ?? '',
                        'remote_address' => $secret['remote-address'] ?? '',
                        'routes' => $secret['routes'] ?? '',
                        'comment' => $secret['comment'] ?? 'REAL PPP Secret from MikroTik',
                        'customer_id' => null
                    ]
                );
                $synced++;
            }

            return $synced;

        } catch (\Exception $e) {
            Log::error('Failed to sync PPP secrets: ' . $e->getMessage());
            return 0;
        }
    }

    private function extractFromActiveConnections()
    {
        try {
            $query = (new Query('/ppp/active/print'));
            $connections = $this->client->query($query)->read();
            
            if (empty($connections)) {
                return ['profiles' => 0, 'secrets' => 0];
            }

            $profiles = 0;
            $secrets = 0;
            $processedProfiles = [];
            $processedSecrets = [];

            foreach ($connections as $conn) {
                // Extract profile info from active connection
                $profileName = $conn['profile'] ?? $conn['name'] . '-profile';
                if (!in_array($profileName, $processedProfiles)) {
                    PppProfile::updateOrCreate(
                        ['name' => $profileName],
                        [
                            'local_address' => $conn['local-address'] ?? '',
                            'remote_address' => $conn['remote-address'] ?? '',
                            'rate_limit' => 'extracted-from-active',
                            'comment' => 'Profile extracted from active PPP connection'
                        ]
                    );
                    $processedProfiles[] = $profileName;
                    $profiles++;
                }

                // Extract secret info from active connection
                $secretName = $conn['name'] ?? 'connection-' . ($secrets + 1);
                if (!in_array($secretName, $processedSecrets)) {
                    PppSecret::updateOrCreate(
                        ['name' => $secretName],
                        [
                            'password' => '***',
                            'profile' => $profileName,
                            'service' => 'ppp',
                            'local_address' => $conn['local-address'] ?? '',
                            'remote_address' => $conn['remote-address'] ?? '',
                            'comment' => 'Secret extracted from active PPP connection',
                            'customer_id' => null
                        ]
                    );
                    $processedSecrets[] = $secretName;
                    $secrets++;
                }
            }

            return ['profiles' => $profiles, 'secrets' => $secrets];

        } catch (\Exception $e) {
            Log::error('Failed to extract from active connections: ' . $e->getMessage());
            return ['profiles' => 0, 'secrets' => 0];
        }
    }
}