<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MikrotikService;
use RouterOS\Query;

class SimpleDisconnect extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ppp:simple-disconnect {username}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Simple disconnect using a lightweight query approach';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $username = $this->argument('username');
        
        $this->info("🎯 Simple disconnect test for: {$username}");
        $this->newLine();
        
        if (!$this->confirm("Proceed with disconnect?")) {
            return 0;
        }
        
        try {
            $mikrotikService = new MikrotikService();
            $mikrotikService->connect();
            $client = $mikrotikService->getClient();
            
            $this->info("✅ Connected to MikroTik successfully");
            
            // Try to get just one connection at a time with a filter
            $this->info("🔍 Looking for user with minimal query...");
            
            try {
                // Try a more targeted query approach
                $query = new Query('/ppp/active/print');
                $query->equal('name', $username);
                $result = $client->query($query)->read();
                
                $this->info("📊 Query result count: " . count($result));
                
                if (!empty($result)) {
                    $connection = $result[0];
                    $connectionId = $connection['.id'] ?? null;
                    
                    if ($connectionId) {
                        $this->info("✅ Found connection ID: {$connectionId}");
                        
                        // Now try to remove it
                        $this->info("🔌 Attempting to remove connection...");
                        $removeQuery = new Query('/ppp/active/remove');
                        $removeQuery->equal('.id', $connectionId);
                        $removeResponse = $client->query($removeQuery)->read();
                        
                        $this->info("📤 Remove response: " . json_encode($removeResponse));
                        
                        if (empty($removeResponse) || !isset($removeResponse['after']['message'])) {
                            $this->info("✅ SUCCESS: User '{$username}' has been disconnected!");
                        } else {
                            $this->warn("⚠️ Response: " . $removeResponse['after']['message']);
                        }
                        
                    } else {
                        $this->error("❌ Connection ID not found in result");
                    }
                } else {
                    $this->warn("⚠️ User '{$username}' not found in active connections");
                }
                
            } catch (\Exception $e) {
                $this->error("❌ Query failed: " . $e->getMessage());
                
                // If the targeted query fails, let's try a brute force approach
                $this->info("🔄 Trying brute force disconnect...");
                
                // Try all possible connection IDs from *0 to *20
                for ($i = 0; $i <= 20; $i++) {
                    $testId = '*' . strtoupper(dechex($i));
                    
                    try {
                        $this->info("🧪 Testing ID: {$testId}");
                        
                        // First check if this ID exists
                        $checkQuery = new Query('/ppp/active/print');
                        $checkQuery->equal('.id', $testId);
                        $checkResult = $client->query($checkQuery)->read();
                        
                        if (!empty($checkResult)) {
                            $foundConnection = $checkResult[0];
                            $foundName = $foundConnection['name'] ?? 'Unknown';
                            
                            $this->info("   📍 ID {$testId} = User: {$foundName}");
                            
                            if ($foundName === $username) {
                                $this->info("   🎯 MATCH FOUND! Disconnecting...");
                                
                                $removeQuery = new Query('/ppp/active/remove');
                                $removeQuery->equal('.id', $testId);
                                $removeResponse = $client->query($removeQuery)->read();
                                
                                $this->info("   ✅ Disconnect command sent!");
                                $this->info("   📤 Response: " . json_encode($removeResponse));
                                break;
                            }
                        }
                        
                    } catch (\Exception $e3) {
                        // Silently continue - this ID doesn't exist
                        continue;
                    }
                    
                    // Add a small delay to avoid overwhelming the router
                    usleep(100000); // 0.1 second
                }
            }
            
            $this->newLine();
            $this->info("⏳ Waiting 3 seconds for changes to take effect...");
            sleep(3);
            
            $this->info("✅ Disconnect process completed!");
            $this->info("🔍 Please verify with: /ppp active print");
            
        } catch (\Exception $e) {
            $this->error("❌ Connection error: " . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}
