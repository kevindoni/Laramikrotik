<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MikrotikService;

class CheckActiveConnections extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ppp:check-active {username?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check if a specific user is in the active connections list';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $username = $this->argument('username');
        
        if (!$username) {
            $username = $this->ask('Enter username to check');
        }
        
        $this->info("🔍 Checking if '{$username}' is in active connections...");
        
        try {
            $mikrotikService = new MikrotikService();
            $mikrotikService->connect();
            
            $this->info("📡 Fetching active connections from MikroTik...");
            $activeConnections = $mikrotikService->getActivePppConnections();
            
            if (empty($activeConnections)) {
                $this->warn("⚠️ No active connections found or timeout occurred");
                return 1;
            }
            
            $this->info("✅ Found " . count($activeConnections) . " active connections");
            
            // Look for the specific user
            $found = false;
            $userConnection = null;
            
            foreach ($activeConnections as $connection) {
                if (isset($connection['name']) && $connection['name'] === $username) {
                    $found = true;
                    $userConnection = $connection;
                    break;
                }
            }
            
            if ($found) {
                $this->error("❌ User '{$username}' is still CONNECTED");
                $this->info("📊 Connection details:");
                $this->info("   • IP Address: " . ($userConnection['address'] ?? 'N/A'));
                $this->info("   • Uptime: " . ($userConnection['uptime'] ?? 'N/A'));
                $this->info("   • Service: " . ($userConnection['service'] ?? 'N/A'));
                $this->info("   • Caller ID: " . ($userConnection['caller-id'] ?? 'N/A'));
            } else {
                $this->info("✅ User '{$username}' is DISCONNECTED");
                $this->info("🎉 Not found in active connections list");
            }
            
            // Show first few active users for reference
            $this->newLine();
            $this->info("📋 Currently active users (first 5):");
            $count = 0;
            foreach ($activeConnections as $connection) {
                if ($count >= 5) break;
                $name = $connection['name'] ?? 'Unknown';
                $ip = $connection['address'] ?? 'N/A';
                $uptime = $connection['uptime'] ?? 'N/A';
                $this->info("   • {$name} - {$ip} - {$uptime}");
                $count++;
            }
            
            if (count($activeConnections) > 5) {
                $remaining = count($activeConnections) - 5;
                $this->info("   ... and {$remaining} more");
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Error checking active connections: " . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}
