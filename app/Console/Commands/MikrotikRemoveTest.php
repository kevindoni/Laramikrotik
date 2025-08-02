<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MikrotikService;
use RouterOS\Query;

class MikrotikRemoveTest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ppp:remove-test {username}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test different MikroTik remove methods';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $username = $this->argument('username');
        
        $this->info("🧪 Testing different MikroTik remove methods for: {$username}");
        $this->newLine();
        
        if (!$this->confirm("This will attempt to disconnect the user. Continue?")) {
            return 0;
        }
        
        try {
            $mikrotikService = new MikrotikService();
            $mikrotikService->connect();
            $client = $mikrotikService->getClient();
            
            $this->info("✅ Connected to MikroTik successfully");
            $this->newLine();
            
            // Method 1: Standard remove command
            $this->info("🧪 Method 1: Standard /ppp/active/remove");
            try {
                $query = new Query('/ppp/active/remove');
                $query->equal('name', $username);
                $response = $client->query($query)->read();
                $this->info("✅ Method 1 response: " . json_encode($response));
            } catch (\Exception $e) {
                $this->warn("⚠️ Method 1 failed: " . $e->getMessage());
            }
            
            sleep(2);
            
            // Method 2: Kill command
            $this->info("🧪 Method 2: /ppp/active/kill");
            try {
                $query = new Query('/ppp/active/kill');
                $query->equal('name', $username);
                $response = $client->query($query)->read();
                $this->info("✅ Method 2 response: " . json_encode($response));
            } catch (\Exception $e) {
                $this->warn("⚠️ Method 2 failed: " . $e->getMessage());
            }
            
            sleep(2);
            
            // Method 3: Using where clause
            $this->info("🧪 Method 3: Using where clause");
            try {
                $query = new Query('/ppp/active/remove');
                $query->where('name', $username);
                $response = $client->query($query)->read();
                $this->info("✅ Method 3 response: " . json_encode($response));
            } catch (\Exception $e) {
                $this->warn("⚠️ Method 3 failed: " . $e->getMessage());
            }
            
            sleep(2);
            
            // Method 4: Terminate command
            $this->info("🧪 Method 4: /interface/pppoe-server/terminate");
            try {
                $query = new Query('/interface/pppoe-server/terminate');
                $query->equal('name', $username);
                $response = $client->query($query)->read();
                $this->info("✅ Method 4 response: " . json_encode($response));
            } catch (\Exception $e) {
                $this->warn("⚠️ Method 4 failed: " . $e->getMessage());
            }
            
            $this->newLine();
            $this->info("🏁 All methods tested!");
            $this->info("💡 Check '/ppp active print' manually to see if any method worked");
            
        } catch (\Exception $e) {
            $this->error("❌ Connection error: " . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}
