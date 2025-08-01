<?php

namespace App\Console\Commands;

use App\Models\MikrotikSetting;
use App\Services\MikrotikService;
use Exception;
use Illuminate\Console\Command;

class MikrotikConnectionTest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mikrotik:test-connection {--ssl : Try SSL connection} {--port= : Override port}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test MikroTik connection with different options';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $setting = MikrotikSetting::getActive();
        
        if (!$setting) {
            $this->error('❌ No active MikroTik setting found.');
            return 1;
        }

        $this->info("🔗 Testing connection to: {$setting->name} ({$setting->host})");
        
        // Test current settings first
        $this->testConnection($setting, 'Current Settings');
        
        // Test with SSL if requested or if regular connection failed
        if ($this->option('ssl') || !$this->testConnection($setting, 'Current Settings', false)) {
            $this->info("\n🔒 Trying SSL connection...");
            
            $sslSetting = clone $setting;
            $sslSetting->use_ssl = true;
            $sslSetting->port = $this->option('port') ?: ($setting->port == '8728' ? '8729' : $setting->port);
            
            $this->testConnection($sslSetting, 'SSL Settings');
        }
        
        // Test with different port if provided
        if ($this->option('port')) {
            $this->info("\n🔧 Testing with custom port...");
            
            $portSetting = clone $setting;
            $portSetting->port = $this->option('port');
            
            $this->testConnection($portSetting, 'Custom Port');
        }

        return 0;
    }

    /**
     * Test connection with given settings.
     */
    private function testConnection($setting, $label, $showResult = true)
    {
        try {
            $service = new MikrotikService();
            $service->setSetting($setting);
            
            if ($showResult) {
                $this->info("\n📋 {$label}:");
                $this->info("   Host: {$setting->host}");
                $this->info("   Port: {$setting->port}");
                $this->info("   SSL: " . ($setting->use_ssl ? 'yes' : 'no'));
                $this->info("   Username: {$setting->username}");
            }
            
            // Test basic connectivity
            $this->line("   🔍 Testing network connectivity...");
            $service->testNetworkConnectivity();
            $this->info("   ✅ Network: OK");
            
            // Test API connection
            $this->line("   🔍 Testing API connection...");
            $service->connect();
            $this->info("   ✅ API: Connected");
            
            // Test system identity
            $this->line("   🔍 Getting system identity...");
            $identity = $service->getSystemIdentity();
            $this->info("   ✅ Identity: {$identity}");
            
            // Test getting profiles (quick test) - but handle potential client reset
            $this->line("   🔍 Testing data retrieval...");
            try {
                // Ensure we're still connected
                if (!$service->isConnected()) {
                    $service->connect();
                }
                $profiles = $service->getPppProfiles();
                $this->info("   ✅ Data: Retrieved " . count($profiles) . " profiles");
            } catch (Exception $dataException) {
                $this->error("   ❌ Data retrieval failed: " . $dataException->getMessage());
                throw $dataException;
            }
            
            if ($showResult) {
                $this->info("\n🎉 Connection test SUCCESSFUL!");
            }
            
            return true;
            
        } catch (Exception $e) {
            if ($showResult) {
                $this->error("\n❌ Connection test FAILED: " . $e->getMessage());
            }
            return false;
        }
    }
}
