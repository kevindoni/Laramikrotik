<?php

namespace App\Console\Commands;

use App\Models\MikrotikSetting;
use App\Services\MikrotikService;
use Illuminate\Console\Command;
use Exception;

class TestMikrotikConnection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mikrotik:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test MikroTik connection and show diagnostics';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Testing MikroTik Connection...');
        $this->newLine();

        // Check if there are any MikroTik settings
        $settings = MikrotikSetting::all();
        
        if ($settings->isEmpty()) {
            $this->error('❌ No MikroTik settings found!');
            $this->info('💡 Please create a MikroTik setting first at: /mikrotik-settings');
            return 1;
        }

        $this->info('📋 Available MikroTik Settings:');
        foreach ($settings as $setting) {
            $status = $setting->is_active ? '✅ Active' : '⏸️  Inactive';
            $this->line("  • {$setting->name} ({$setting->host}:{$setting->port}) - {$status}");
        }
        $this->newLine();

        // Get active setting
        $activeSetting = MikrotikSetting::getActive();
        
        if (!$activeSetting) {
            $this->error('❌ No active MikroTik setting found!');
            $this->info('💡 Please activate a MikroTik setting first.');
            return 1;
        }

        $this->info("🎯 Testing active setting: {$activeSetting->name}");
        $this->info("🔗 Host: {$activeSetting->host}:{$activeSetting->port}");
        $this->info("👤 Username: {$activeSetting->username}");
        $this->info("🔒 SSL: " . ($activeSetting->use_ssl ? 'Enabled' : 'Disabled'));
        $this->newLine();

        // Test connection
        try {
            $mikrotikService = app(MikrotikService::class);
            $mikrotikService->setSetting($activeSetting);
            
            $this->info('🔄 Attempting to connect...');
            $mikrotikService->connect();
            
            $this->info('✅ Connection successful!');
            $this->newLine();
            
            // Get system information
            $this->info('📊 Getting system information...');
            try {
                $identity = $mikrotikService->getSystemIdentity();
                $resources = $mikrotikService->getSystemResources();
                
                $this->info("🏷️  Router Identity: {$identity}");
                $this->info("💾 Board Name: {$resources['board-name']}");
                $this->info("🔢 Version: {$resources['version']}");
                $this->info("⚡ CPU Load: {$resources['cpu-load']}%");
                $this->info("💿 Free Memory: " . round($resources['free-memory'] / 1024 / 1024, 2) . " MB");
                $this->info("💾 Total Memory: " . round($resources['total-memory'] / 1024 / 1024, 2) . " MB");
                
            } catch (Exception $e) {
                $this->warn("⚠️  Could not get system info: {$e->getMessage()}");
            }
            
            $this->newLine();
            
            // Test PPP functionality
            $this->info('🔍 Testing PPP functionality...');
            try {
                $profiles = $mikrotikService->getPppProfiles();
                $secrets = $mikrotikService->getPppSecrets();
                $activeConnections = $mikrotikService->getActivePppConnections();
                
                $this->info("📁 PPP Profiles: " . count($profiles));
                $this->info("🔑 PPP Secrets: " . count($secrets));
                $this->info("🌐 Active Connections: " . count($activeConnections));
                
            } catch (Exception $e) {
                $this->warn("⚠️  Could not get PPP info: {$e->getMessage()}");
            }
            
            $this->newLine();
            $this->info('🎉 All tests completed successfully!');
            return 0;
            
        } catch (Exception $e) {
            $this->error('❌ Connection failed!');
            $this->error("Error: {$e->getMessage()}");
            $this->newLine();
            
            // Provide troubleshooting tips
            $this->info('💡 Troubleshooting Tips:');
            $this->line('  • Check if MikroTik is powered on and accessible');
            $this->line('  • Verify network connectivity to MikroTik');
            $this->line('  • Check if API service is enabled: /ip service enable api');
            $this->line('  • Verify username and password');
            $this->line('  • Check firewall rules on MikroTik');
            $this->line('  • Try different port (8728 for non-SSL, 8729 for SSL)');
            
            return 1;
        }
    }
}
