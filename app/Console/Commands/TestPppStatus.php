<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PppSecret;

class TestPppStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ppp:test-status {username?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test PPP real-time connection status for a specific user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $username = $this->argument('username') ?: 'anik';
        
        $this->info("🔍 Testing real-time connection status for user: {$username}");
        
        // Find the PPP secret
        $pppSecret = PppSecret::where('username', $username)->first();
        
        if (!$pppSecret) {
            $this->error("❌ PPP Secret with username '{$username}' not found!");
            return 1;
        }
        
        $this->info("✅ PPP Secret found: {$pppSecret->username}");
        
        // Test real-time status
        try {
            $status = $pppSecret->getRealTimeConnectionStatus();
            
            if ($status === null) {
                $this->warn("⚠️  Status is NULL - likely connection or setting issues");
            } elseif (isset($status['status'])) {
                $statusText = strtoupper($status['status']);
                
                switch ($status['status']) {
                    case 'connected':
                        $this->info("📊 Connection Status: ✅ {$statusText}");
                        $this->info("🌐 IP Address: " . ($status['address'] ?? 'N/A'));
                        $this->info("⏱️  Uptime: " . ($status['uptime'] ?? 'N/A'));
                        $this->info("📞 Caller ID: " . ($status['caller_id'] ?? 'N/A'));
                        $this->info("🔧 Service: " . ($status['service'] ?? 'N/A'));
                        break;
                        
                    case 'disconnected':
                        $this->info("📊 Connection Status: ❌ {$statusText}");
                        $this->info("💤 User is currently offline");
                        break;
                        
                    case 'timeout':
                        $this->warn("📊 Connection Status: ⏳ {$statusText}");
                        $this->warn("🐌 MikroTik router is slow to respond - PPP info timed out");
                        break;
                        
                    default:
                        $this->warn("📊 Connection Status: ❓ {$statusText}");
                }
            } else {
                $this->warn("⚠️  Invalid status format returned");
                $this->line(json_encode($status, JSON_PRETTY_PRINT));
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Error getting real-time status: " . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}
