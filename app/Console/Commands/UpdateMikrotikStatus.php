<?php

namespace App\Console\Commands;

use App\Models\MikrotikSetting;
use App\Services\MikrotikService;
use Illuminate\Console\Command;
use Exception;

class UpdateMikrotikStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mikrotik:update-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update MikroTik connection status and refresh last connected timestamp';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Updating MikroTik connection status...');

        $activeSetting = MikrotikSetting::getActive();
        
        if (!$activeSetting) {
            $this->error('❌ No active MikroTik setting found!');
            return 1;
        }

        $this->info("🎯 Testing connection to: {$activeSetting->name} ({$activeSetting->host})");

        try {
            $mikrotikService = app(MikrotikService::class);
            $mikrotikService->setSetting($activeSetting);
            
            // Test basic connectivity
            $mikrotikService->connect();
            $identity = $mikrotikService->getSystemIdentity();
            
            // Update last connected timestamp
            $activeSetting->updateLastConnected();
            
            $this->info("✅ Connection successful! Connected to: {$identity}");
            $this->info("📅 Last connected timestamp updated: " . $activeSetting->last_connected_at->format('Y-m-d H:i:s'));
            
            // Test additional functionality
            try {
                $profiles = $mikrotikService->getPppProfiles();
                $this->info("📁 PPP Profiles available: " . count($profiles));
                
                $secrets = $mikrotikService->getPppSecrets();
                $this->info("🔑 PPP Secrets available: " . count($secrets));
                
                // Try active connections with timeout handling
                try {
                    $activeConnections = $mikrotikService->getActivePppConnections();
                    $this->info("🌐 Active PPP connections: " . count($activeConnections));
                } catch (Exception $e) {
                    $this->warn("⚠️  Could not get active connections (timeout): " . $e->getMessage());
                }
                
            } catch (Exception $e) {
                $this->warn("⚠️  Some PPP functions unavailable: " . $e->getMessage());
            }
            
            $this->newLine();
            $this->info('🎉 Status update completed successfully!');
            return 0;
            
        } catch (Exception $e) {
            $this->error('❌ Connection failed: ' . $e->getMessage());
            
            // Show current status
            $currentStatus = $activeSetting->getConnectionStatus();
            $this->warn("Current status: {$currentStatus}");
            
            if ($activeSetting->last_connected_at) {
                $this->warn("Last successful connection: " . $activeSetting->last_connected_at->diffForHumans());
            } else {
                $this->warn("Never connected successfully");
            }
            
            return 1;
        }
    }
}
