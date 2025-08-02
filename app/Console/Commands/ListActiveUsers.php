<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MikrotikService;
use RouterOS\Query;

class ListActiveUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ppp:list-active';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List active users by checking connection IDs systematically';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("🔍 Systematically checking active PPP connections...");
        $this->newLine();
        
        try {
            $mikrotikService = new MikrotikService();
            $mikrotikService->connect();
            $client = $mikrotikService->getClient();
            
            $this->info("✅ Connected to MikroTik successfully");
            $this->newLine();
            
            $foundUsers = [];
            $maxCheck = 30; // Check first 30 possible IDs
            
            $this->info("🔍 Checking connection IDs systematically...");
            
            for ($i = 0; $i < $maxCheck; $i++) {
                $testId = '*' . strtoupper(dechex($i));
                
                try {
                    $query = new Query('/ppp/active/print');
                    $query->equal('.id', $testId);
                    $result = $client->query($query)->read();
                    
                    if (!empty($result) && isset($result[0]['name'])) {
                        $connection = $result[0];
                        $foundUsers[] = [
                            'id' => $testId,
                            'name' => $connection['name'],
                            'address' => $connection['address'] ?? 'N/A',
                            'uptime' => $connection['uptime'] ?? 'N/A',
                            'service' => $connection['service'] ?? 'N/A'
                        ];
                        
                        $this->info("   [{$i}] ID: {$testId} -> {$connection['name']} (" . ($connection['address'] ?? 'N/A') . ")");
                    }
                    
                } catch (\Exception $e) {
                    // This ID doesn't exist, continue
                    continue;
                }
                
                // Small delay to not overwhelm router
                usleep(100000); // 0.1 seconds
            }
            
            $this->newLine();
            $this->info("📊 Summary of active connections found:");
            $this->info("Total active users: " . count($foundUsers));
            $this->newLine();
            
            if (!empty($foundUsers)) {
                $this->info("📋 Active users list:");
                foreach ($foundUsers as $index => $user) {
                    $this->info("   {$index}: {$user['name']} - {$user['address']} - {$user['uptime']}");
                }
                
                // Check for specific users we're interested in
                $testUsers = ['teguh', 'kenzi', 'sate1', 'anik'];
                $this->newLine();
                $this->info("🎯 Checking for specific users:");
                
                foreach ($testUsers as $testUser) {
                    $found = false;
                    foreach ($foundUsers as $user) {
                        if (strtolower($user['name']) === strtolower($testUser)) {
                            $this->info("   ✅ {$testUser} -> Found as '{$user['name']}' (ID: {$user['id']})");
                            $found = true;
                            break;
                        }
                    }
                    if (!$found) {
                        $this->warn("   ❌ {$testUser} -> Not found");
                    }
                }
            } else {
                $this->warn("❌ No active connections found");
                $this->info("💡 This could mean:");
                $this->info("   • Router is under heavy load");
                $this->info("   • All users are actually offline");
                $this->info("   • Connection ID format is different");
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}
