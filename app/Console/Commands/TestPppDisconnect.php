<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PppSecret;
use App\Services\MikrotikService;

class TestPppDisconnect extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ppp:test-disconnect {username?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test PPP session disconnect functionality';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $username = $this->argument('username');
        
        if (!$username) {
            $username = $this->ask('Enter username to disconnect');
        }
        
        $this->info("🔍 Testing disconnect functionality for username: {$username}");
        
        // Find the PPP secret
        $pppSecret = PppSecret::where('username', $username)->first();
        
        if (!$pppSecret) {
            $this->error("❌ PPP Secret with username '{$username}' not found!");
            return 1;
        }
        
        $this->info("✅ PPP Secret found: {$pppSecret->username}");
        
        // Check current connection status
        $this->info("📊 Checking current connection status...");
        $status = $pppSecret->getRealTimeConnectionStatus();
        
        if ($status && $status['status'] === 'connected') {
            $this->info("🌐 User is currently CONNECTED");
            $this->info("📍 IP Address: " . ($status['address'] ?? 'N/A'));
            $this->info("⏱️ Uptime: " . ($status['uptime'] ?? 'N/A'));
        } elseif ($status && $status['status'] === 'disconnected') {
            $this->warn("💤 User is already DISCONNECTED");
            $this->warn("Nothing to disconnect.");
            return 0;
        } elseif ($status && $status['status'] === 'timeout') {
            $this->warn("⏳ Connection status TIMEOUT");
            $this->warn("Cannot verify if user is connected due to slow router response.");
        } else {
            $this->warn("❓ Connection status UNKNOWN");
            $this->warn("Cannot determine if user is connected.");
        }
        
        // Ask for confirmation
        if (!$this->confirm("⚠️ Are you sure you want to disconnect this user? This will terminate their internet connection immediately.")) {
            $this->info("🚫 Disconnect cancelled by user");
            return 0;
        }
        
        try {
            $this->info("🔌 Connecting to MikroTik...");
            $mikrotikService = new MikrotikService();
            $mikrotikService->connect();
            
            $this->info("🔌 Attempting to disconnect session...");
            $result = $mikrotikService->disconnectPppConnection($username);
            
            if ($result) {
                $this->info("✅ Disconnect command executed successfully!");
                $this->info("🎉 User '{$username}' disconnect command has been sent to MikroTik");
                
                // Check status after disconnect with longer wait
                $this->info("🔄 Waiting for status to update...");
                sleep(3); // Wait a bit longer for status to update
                
                $newStatus = $pppSecret->getRealTimeConnectionStatus();
                if ($newStatus && $newStatus['status'] === 'disconnected') {
                    $this->info("✅ Confirmed: User is now DISCONNECTED");
                } elseif ($newStatus && $newStatus['status'] === 'timeout') {
                    $this->warn("⏳ Cannot verify final status due to timeout");
                    $this->info("💡 This is normal when the router is busy. The disconnect command was sent successfully.");
                    $this->info("📋 Recommendation: Check user status again in 1-2 minutes to confirm disconnection.");
                } elseif ($newStatus && $newStatus['status'] === 'connected') {
                    $this->warn("⚠️ User still appears connected");
                    $this->info("💡 This could mean:");
                    $this->info("   • User reconnected immediately (auto-reconnect enabled)");
                    $this->info("   • Disconnect command is still processing");
                    $this->info("   • User has multiple active sessions");
                } else {
                    $this->warn("❓ Status verification inconclusive");
                    $this->info("💡 Disconnect command was sent, but final status cannot be determined");
                }
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Error during disconnect: " . $e->getMessage());
            
            // Provide helpful troubleshooting tips
            $this->newLine();
            $this->warn("🔧 Troubleshooting Tips:");
            
            if (strpos($e->getMessage(), 'timeout') !== false || strpos($e->getMessage(), 'slow') !== false) {
                $this->info("   • Router is responding slowly - this is normal during high load");
                $this->info("   • The disconnect command may have been sent successfully");
                $this->info("   • Wait 1-2 minutes and check user status again");
                $this->info("   • Try using the web interface for immediate status updates");
            } elseif (strpos($e->getMessage(), 'already be offline') !== false) {
                $this->info("   • User may already be disconnected");
                $this->info("   • Check connection status to verify");
                $this->info("   • This is not an error if user was already offline");
            } else {
                $this->info("   • Check MikroTik router connectivity");
                $this->info("   • Verify user exists and has active connection");
                $this->info("   • Try again in a few moments");
            }
            
            return 1;
        }
        
        return 0;
    }
}
