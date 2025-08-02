<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MikrotikService;
use RouterOS\Query;

class QuickDisconnect extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ppp:quick-disconnect {username}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Quickly disconnect a user without status checks';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $username = $this->argument('username');
        
        $this->info("⚡ Quick disconnect for user: {$username}");
        
        if (!$this->confirm("Are you sure you want to disconnect '{$username}'?")) {
            $this->info("🚫 Cancelled by user");
            return 0;
        }
        
        try {
            $mikrotikService = new MikrotikService();
            $mikrotikService->connect();
            
            $this->info("🔌 Sending direct disconnect command...");
            
            // Get the client directly and send disconnect command
            $client = $mikrotikService->getClient();
            
            // Try direct disconnect by name
            $query = new Query('/ppp/active/remove');
            $query->equal('name', $username);
            $response = $client->query($query)->read();
            
            $this->info("✅ Disconnect command sent successfully!");
            $this->info("🎯 Direct method used - no status verification needed");
            $this->info("💡 User should be disconnected within seconds");
            
            $this->newLine();
            $this->info("📋 To verify disconnection:");
            $this->info("   • Check manually on MikroTik: /ppp active print");
            $this->info("   • Wait 30-60 seconds for user device to notice");
            $this->info("   • User may auto-reconnect if enabled");
            
        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            
            if (strpos($e->getMessage(), 'no such item') !== false) {
                $this->info("💡 This usually means the user was already disconnected");
            } elseif (strpos($e->getMessage(), 'timeout') !== false) {
                $this->warn("⏳ Command may have been sent but timed out during confirmation");
            }
            
            return 1;
        }
        
        return 0;
    }
}
