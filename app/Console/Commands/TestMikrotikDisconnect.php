<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MikrotikService;
use RouterOS\Query;

class TestMikrotikDisconnect extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ppp:test-api {username}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test MikroTik API disconnect with proper syntax';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $username = $this->argument('username');
        
        $this->info("🔍 Testing MikroTik API disconnect for: {$username}");
        $this->newLine();
        
        try {
            $mikrotikService = new MikrotikService();
            $mikrotikService->connect();
            $client = $mikrotikService->getClient();
            
            $this->info("✅ Connected to MikroTik successfully");
            
            // Step 1: Get ALL active connections first
            $this->info("🔎 Step 1: Getting all active connections...");
            $query = new Query('/ppp/active/print');
            $allConnections = $client->query($query)->read();
            
            $this->info("📊 Total active connections: " . count($allConnections));
            
            // Find our user
            $targetConnection = null;
            $targetIndex = null;
            
            foreach ($allConnections as $index => $connection) {
                if (isset($connection['name']) && $connection['name'] === $username) {
                    $targetConnection = $connection;
                    $targetIndex = $index;
                    break;
                }
            }
            
            if (!$targetConnection) {
                $this->warn("❌ User '{$username}' not found in active connections");
                
                $this->info("📋 Available users:");
                foreach ($allConnections as $conn) {
                    $this->info("   • " . ($conn['name'] ?? 'Unknown'));
                }
                return 0;
            }
            
            $connectionId = $targetConnection['.id'];
            
            $this->info("✅ Found user '{$username}':");
            $this->info("   • Connection ID: {$connectionId}");
            $this->info("   • IP Address: " . ($targetConnection['address'] ?? 'N/A'));
            $this->info("   • Uptime: " . ($targetConnection['uptime'] ?? 'N/A'));
            $this->info("   • Index: {$targetIndex}");
            
            if (!$this->confirm("Proceed with disconnect?")) {
                return 0;
            }
            
            // Step 2: Disconnect using connection ID
            $this->newLine();
            $this->info("🔌 Step 2: Disconnecting using connection ID...");
            
            $query = new Query('/ppp/active/remove');
            $query->equal('.id', $connectionId);
            $response = $client->query($query)->read();
            
            $this->info("📤 Disconnect response: " . json_encode($response));
            
            // Step 3: Verify disconnection
            $this->newLine();
            $this->info("⏳ Step 3: Waiting 3 seconds for disconnection...");
            sleep(3);
            
            $this->info("🔍 Checking if user is still connected...");
            $query = new Query('/ppp/active/print');
            $newConnections = $client->query($query)->read();
            
            $stillConnected = false;
            foreach ($newConnections as $conn) {
                if (isset($conn['name']) && $conn['name'] === $username) {
                    $stillConnected = true;
                    break;
                }
            }
            
            if ($stillConnected) {
                $this->error("❌ User '{$username}' is STILL CONNECTED");
                $this->warn("🤔 This suggests either:");
                $this->warn("   • Auto-reconnect is enabled and user reconnected immediately");
                $this->warn("   • MikroTik disconnect command was ignored");
                $this->warn("   • User has multiple active sessions");
                
                // Let's check if connection ID changed (indicating reconnection)
                $newTargetConnection = null;
                foreach ($newConnections as $conn) {
                    if (isset($conn['name']) && $conn['name'] === $username) {
                        $newTargetConnection = $conn;
                        break;
                    }
                }
                
                if ($newTargetConnection && $newTargetConnection['.id'] !== $connectionId) {
                    $this->info("🔄 CONNECTION ID CHANGED - User auto-reconnected!");
                    $this->info("   • Old ID: {$connectionId}");
                    $this->info("   • New ID: " . $newTargetConnection['.id']);
                    $this->info("   • New Uptime: " . ($newTargetConnection['uptime'] ?? 'N/A'));
                } else {
                    $this->warn("🚫 Same connection ID - Disconnect may have failed");
                }
                
            } else {
                $this->info("✅ SUCCESS: User '{$username}' has been disconnected!");
                $this->info("🎉 User is no longer in active connections list");
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}
