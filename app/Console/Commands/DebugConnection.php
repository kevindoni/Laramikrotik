<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MikrotikService;
use RouterOS\Query;

class DebugConnection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ppp:debug {username}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Debug connection query to see exact data returned';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $username = $this->argument('username');
        
        $this->info("🔍 Debugging connection query for: {$username}");
        $this->newLine();
        
        try {
            $mikrotikService = new MikrotikService();
            $mikrotikService->connect();
            $client = $mikrotikService->getClient();
            
            $this->info("✅ Connected to MikroTik successfully");
            
            // Try the query and see what we get back
            $this->info("🔍 Executing query for user: {$username}");
            $query = new Query('/ppp/active/print');
            $query->equal('name', $username);
            $result = $client->query($query)->read();
            
            $this->info("📊 Raw result:");
            $this->info(json_encode($result, JSON_PRETTY_PRINT));
            $this->newLine();
            
            $this->info("📊 Result analysis:");
            $this->info("   • Type: " . gettype($result));
            $this->info("   • Count: " . (is_array($result) ? count($result) : 'N/A'));
            
            if (is_array($result)) {
                foreach ($result as $index => $item) {
                    $this->info("   • Item {$index}: " . gettype($item));
                    if (is_array($item)) {
                        foreach ($item as $key => $value) {
                            $this->info("     - {$key}: {$value}");
                        }
                    }
                }
            }
            
            // Also try without the equal filter to see if we get any results
            $this->newLine();
            $this->info("🔍 Trying query without filter (getting all - limited to first 3):");
            
            try {
                $allQuery = new Query('/ppp/active/print');
                $allResult = $client->query($allQuery)->read();
                
                $this->info("📊 All connections count: " . (is_array($allResult) ? count($allResult) : 'N/A'));
                
                if (is_array($allResult) && count($allResult) > 0) {
                    $this->info("📋 First few connections:");
                    for ($i = 0; $i < min(3, count($allResult)); $i++) {
                        if (isset($allResult[$i]) && is_array($allResult[$i])) {
                            $conn = $allResult[$i];
                            $name = $conn['name'] ?? 'No name';
                            $id = $conn['.id'] ?? 'No ID';
                            $this->info("   • [{$i}] ID: {$id}, Name: {$name}");
                        }
                    }
                    
                    // Check if our target user is in the full list
                    $found = false;
                    foreach ($allResult as $index => $conn) {
                        if (is_array($conn) && isset($conn['name']) && $conn['name'] === $username) {
                            $found = true;
                            $this->info("🎯 FOUND target user '{$username}' at index {$index}:");
                            $this->info("   • ID: " . ($conn['.id'] ?? 'No ID'));
                            $this->info("   • Address: " . ($conn['address'] ?? 'No address'));
                            break;
                        }
                    }
                    
                    if (!$found) {
                        $this->warn("❌ Target user '{$username}' NOT found in full list");
                        $this->info("📋 Available users:");
                        foreach ($allResult as $conn) {
                            if (is_array($conn) && isset($conn['name'])) {
                                $this->info("   • " . $conn['name']);
                            }
                        }
                    }
                }
                
            } catch (\Exception $e) {
                $this->error("❌ Failed to get all connections: " . $e->getMessage());
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}
