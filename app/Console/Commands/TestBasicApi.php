<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MikrotikService;
use RouterOS\Query;

class TestBasicApi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ppp:test-basic';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test basic MikroTik API functionality';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("🔍 Testing basic MikroTik API functionality...");
        $this->newLine();
        
        try {
            $mikrotikService = new MikrotikService();
            $mikrotikService->connect();
            $client = $mikrotikService->getClient();
            
            $this->info("✅ Connected to MikroTik successfully");
            
            // Test 1: Get system identity
            $this->info("🧪 Test 1: Getting system identity...");
            try {
                $query = new Query('/system/identity/print');
                $result = $client->query($query)->read();
                $this->info("✅ System identity: " . json_encode($result));
            } catch (\Exception $e) {
                $this->error("❌ Failed: " . $e->getMessage());
            }
            
            $this->newLine();
            
            // Test 2: Get interface count
            $this->info("🧪 Test 2: Getting interface count...");
            try {
                $query = new Query('/interface/print');
                $query->equal('type', 'ether');
                $result = $client->query($query)->read();
                $this->info("✅ Found " . count($result) . " ethernet interfaces");
            } catch (\Exception $e) {
                $this->error("❌ Failed: " . $e->getMessage());
            }
            
            $this->newLine();
            
            // Test 3: Try PPP active print with very short timeout
            $this->info("🧪 Test 3: Testing PPP active print (basic)...");
            try {
                $query = new Query('/ppp/active/print');
                $result = $client->query($query)->read();
                $this->info("✅ PPP active print succeeded");
                $this->info("📊 Result type: " . gettype($result));
                $this->info("📊 Result count: " . (is_array($result) ? count($result) : 'N/A'));
                
                if (is_array($result) && count($result) > 0) {
                    $this->info("📋 First connection structure:");
                    $first = $result[0];
                    foreach ($first as $key => $value) {
                        $this->info("   • {$key}: {$value}");
                    }
                }
                
            } catch (\Exception $e) {
                $this->error("❌ PPP active print failed: " . $e->getMessage());
                
                // Test 4: Try alternative approaches
                $this->info("🧪 Test 4: Trying PPP interface list...");
                try {
                    $query = new Query('/interface/print');
                    $query->equal('type', 'pppoe-in');
                    $result = $client->query($query)->read();
                    $this->info("✅ Found " . count($result) . " PPPoE interfaces");
                } catch (\Exception $e2) {
                    $this->error("❌ PPPoE interfaces failed: " . $e2->getMessage());
                }
            }
            
            $this->newLine();
            $this->info("🏁 Basic API tests completed!");
            
        } catch (\Exception $e) {
            $this->error("❌ Connection error: " . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}
