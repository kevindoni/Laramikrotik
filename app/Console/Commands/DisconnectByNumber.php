<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MikrotikService;
use RouterOS\Query;

class DisconnectByNumber extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ppp:disconnect-number {number} {--confirm=false}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Disconnect PPP user by their number in the active list';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $number = $this->argument('number');
        $autoConfirm = $this->option('confirm') === 'true';
        
        $this->info("🔍 Disconnecting PPP user at position #{$number}");
        $this->newLine();
        
        if (!$autoConfirm && !$this->confirm("Are you sure you want to disconnect user at position #{$number}?")) {
            $this->info("🚫 Cancelled by user");
            return 0;
        }
        
        try {
            $mikrotikService = new MikrotikService();
            $mikrotikService->connect();
            $client = $mikrotikService->getClient();
            
            $this->info("✅ Connected to MikroTik successfully");
            
            // Method 1: Try removing by number directly
            $this->info("🔌 Method 1: Disconnect by number...");
            try {
                $query = new Query('/ppp/active/remove');
                $query->equal('numbers', $number);
                $response = $client->query($query)->read();
                
                $this->info("📤 Response: " . json_encode($response));
                $this->info("✅ Disconnect command sent successfully!");
                
            } catch (\Exception $e) {
                $this->warn("⚠️ Method 1 failed: " . $e->getMessage());
                
                // Method 2: Try alternative approach
                $this->info("🔌 Method 2: Alternative approach...");
                try {
                    $query = new Query('/ppp/active/remove');
                    $query->equal('.id', '*' . dechex($number));
                    $response = $client->query($query)->read();
                    
                    $this->info("📤 Response: " . json_encode($response));
                    $this->info("✅ Alternative method sent successfully!");
                    
                } catch (\Exception $e2) {
                    $this->error("❌ Both methods failed:");
                    $this->error("   Method 1: " . $e->getMessage());
                    $this->error("   Method 2: " . $e2->getMessage());
                    return 1;
                }
            }
            
            $this->newLine();
            $this->info("⏳ Waiting 5 seconds for disconnection to take effect...");
            sleep(5);
            
            $this->info("✅ Disconnect process completed!");
            $this->info("💡 Please check manually with '/ppp active print' to verify");
            
        } catch (\Exception $e) {
            $this->error("❌ Connection error: " . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}
