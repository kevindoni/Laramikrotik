<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MikrotikService;
use RouterOS\Query;

class TestConnectionId extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ppp:test-id {position}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test disconnecting by converting position to connection ID';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $position = (int) $this->argument('position');
        
        $this->info("🔍 Testing disconnect for position: {$position}");
        $this->info("💡 Based on your manual check, this should be user at index {$position}");
        $this->newLine();
        
        if (!$this->confirm("Proceed with disconnect test?")) {
            return 0;
        }
        
        try {
            $mikrotikService = new MikrotikService();
            $mikrotikService->connect();
            $client = $mikrotikService->getClient();
            
            $this->info("✅ Connected to MikroTik successfully");
            
            // In MikroTik, connection IDs are usually in hex format starting with *
            // Position 12 would typically be *C (hex for 12)
            $connectionId = '*' . strtoupper(dechex($position));
            
            $this->info("🎯 Calculated connection ID: {$connectionId}");
            $this->info("📋 This assumes standard MikroTik ID numbering");
            $this->newLine();
            
            // Method 1: Try direct remove with calculated ID
            $this->info("🔌 Attempting disconnect with ID: {$connectionId}");
            try {
                $query = new Query('/ppp/active/remove');
                $query->equal('.id', $connectionId);
                $response = $client->query($query)->read();
                
                $this->info("✅ Command sent successfully!");
                $this->info("📤 Response: " . json_encode($response));
                
                // Check if response indicates success or error
                if (isset($response['after']['message'])) {
                    $message = $response['after']['message'];
                    if (strpos($message, 'no such item') !== false) {
                        $this->warn("⚠️ Connection ID not found - may be wrong calculation");
                    } else {
                        $this->warn("⚠️ Response message: {$message}");
                    }
                } else {
                    $this->info("✅ No error message - disconnect likely successful!");
                }
                
            } catch (\Exception $e) {
                $this->error("❌ Method failed: " . $e->getMessage());
                
                // Try alternative ID formats
                $this->info("🔄 Trying alternative ID formats...");
                
                $alternativeIds = [
                    (string) $position,  // Plain number
                    '*' . str_pad(dechex($position), 2, '0', STR_PAD_LEFT), // Zero-padded hex
                    '*' . $position,     // Star + number
                ];
                
                foreach ($alternativeIds as $altId) {
                    try {
                        $this->info("🧪 Trying ID: {$altId}");
                        $query = new Query('/ppp/active/remove');
                        $query->equal('.id', $altId);
                        $response = $client->query($query)->read();
                        
                        $this->info("✅ Alternative ID worked: {$altId}");
                        $this->info("📤 Response: " . json_encode($response));
                        break;
                        
                    } catch (\Exception $e2) {
                        $this->warn("⚠️ ID {$altId} failed: " . $e2->getMessage());
                    }
                }
            }
            
            $this->newLine();
            $this->info("⏳ Waiting 5 seconds...");
            sleep(5);
            
            $this->info("✅ Disconnect test completed!");
            $this->info("🔍 Please check manually: /ppp active print");
            
        } catch (\Exception $e) {
            $this->error("❌ Connection error: " . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}
