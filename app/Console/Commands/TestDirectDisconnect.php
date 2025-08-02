<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MikrotikService;
use RouterOS\Query;

class TestDirectDisconnect extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ppp:test-direct {username}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test direct disconnect with detailed MikroTik API debugging';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $username = $this->argument('username');
        
        $this->info("🔍 Testing direct disconnect for: {$username}");
        $this->newLine();
        
        try {
            $mikrotikService = new MikrotikService();
            $mikrotikService->connect();
            $client = $mikrotikService->getClient();
            
            $this->info("✅ Connected to MikroTik successfully");
            
            // Step 1: Find the user in active connections
            $this->info("🔎 Step 1: Looking for user in active connections...");
            $query = new Query('/ppp/active/print');
            $query->equal('name', $username);
            $activeConnections = $client->query($query)->read();
            
            $this->info("📊 Query result: " . json_encode($activeConnections));
            
            if (empty($activeConnections)) {
                $this->warn("❌ User '{$username}' not found in active connections");
                $this->info("💡 User may already be offline");
                
                // Let's also try to get all active connections to see what's available
                $this->info("🔍 Getting all active connections for reference...");
                try {
                    $allQuery = new Query('/ppp/active/print');
                    $allConnections = $client->query($allQuery)->read();
                    $this->info("📊 Total active connections: " . count($allConnections));
                    
                    if (!empty($allConnections)) {
                        $this->info("📋 First few users:");
                        for ($i = 0; $i < min(3, count($allConnections)); $i++) {
                            $conn = $allConnections[$i];
                            $this->info("   • " . ($conn['name'] ?? 'Unknown') . " - " . ($conn['address'] ?? 'N/A'));
                        }
                    }
                } catch (\Exception $e) {
                    $this->error("❌ Failed to get all connections: " . $e->getMessage());
                }
                
                return 0;
            }
            
            $connection = $activeConnections[0];
            $connectionId = $connection['.id'];
            
            $this->info("✅ Found active connection:");
            $this->info("   • Connection ID: {$connectionId}");
            $this->info("   • IP Address: " . ($connection['address'] ?? 'N/A'));
            $this->info("   • Uptime: " . ($connection['uptime'] ?? 'N/A'));
            $this->info("   • Service: " . ($connection['service'] ?? 'N/A'));
            
            // Step 2: Try different disconnect methods
            $this->newLine();
            $this->info("🔌 Step 2: Testing disconnect methods...");
            
            if (!$this->confirm("Proceed with disconnect test?")) {
                return 0;
            }
            
            // Method 1: Remove by connection ID
            $this->info("🧪 Method 1: Remove by connection ID...");
            try {
                $query = new Query('/ppp/active/remove');
                $query->equal('.id', $connectionId);
                $response = $client->query($query)->read();
                
                $this->info("✅ Remove command sent successfully");
                $this->info("📤 Response: " . json_encode($response));
                
            } catch (\Exception $e) {
                $this->error("❌ Method 1 failed: " . $e->getMessage());
            }
            
            // Step 3: Verify disconnection
            $this->newLine();
            $this->info("🔍 Step 3: Verifying disconnection...");
            sleep(2); // Wait a moment
            
            $query = new Query('/ppp/active/print');
            $query->equal('name', $username);
            $checkConnections = $client->query($query)->read();
            
            if (empty($checkConnections)) {
                $this->info("✅ SUCCESS: User '{$username}' has been disconnected!");
            } else {
                $this->error("❌ FAILED: User '{$username}' is still connected");
                
                // Try alternative method
                $this->newLine();
                $this->info("🧪 Method 2: Alternative disconnect approach...");
                
                try {
                    // Try using terminate command
                    $query = new Query('/ppp/active/remove');
                    $query->equal('name', $username);
                    $response = $client->query($query)->read();
                    
                    $this->info("📤 Alternative method response: " . json_encode($response));
                    
                    sleep(2);
                    
                    // Check again
                    $query = new Query('/ppp/active/print');
                    $query->equal('name', $username);
                    $finalCheck = $client->query($query)->read();
                    
                    if (empty($finalCheck)) {
                        $this->info("✅ SUCCESS: Alternative method worked!");
                    } else {
                        $this->error("❌ Both methods failed - investigating further...");
                        
                        // Let's see if there are any special properties
                        $this->info("🔍 Current connection details:");
                        foreach ($finalCheck[0] as $key => $value) {
                            $this->info("   • {$key}: {$value}");
                        }
                    }
                    
                } catch (\Exception $e) {
                    $this->error("❌ Alternative method failed: " . $e->getMessage());
                }
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}
