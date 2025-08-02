<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PppSecret;
use App\Services\MikrotikService;

class TestPppDelete extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ppp:test-delete {username?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test PPP secret delete functionality with MikroTik sync';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $username = $this->argument('username');
        
        if (!$username) {
            $username = $this->ask('Enter username to test delete');
        }
        
        $this->info("🔍 Testing delete functionality for username: {$username}");
        
        // Find the PPP secret
        $pppSecret = PppSecret::where('username', $username)->first();
        
        if (!$pppSecret) {
            $this->error("❌ PPP Secret with username '{$username}' not found!");
            return 1;
        }
        
        $this->info("✅ PPP Secret found: {$pppSecret->username}");
        $this->info("📋 Current MikroTik ID: " . ($pppSecret->mikrotik_id ?: 'Not set'));
        
        // Ask for confirmation
        if (!$this->confirm("⚠️ Are you sure you want to test delete this secret? This will actually delete it!")) {
            $this->info("🚫 Delete cancelled by user");
            return 0;
        }
        
        $syncWithMikrotik = $this->confirm("🔄 Delete from MikroTik router as well?", true);
        
        try {
            $mikrotikService = new MikrotikService();
            
            if ($syncWithMikrotik) {
                $this->info("🔌 Connecting to MikroTik...");
                $mikrotikService->connect();
                
                $this->info("🗑️ Deleting from MikroTik router...");
                $mikrotikService->deletePppSecret($pppSecret);
                $this->info("✅ Successfully deleted from MikroTik");
            }
            
            $this->info("🗄️ Deleting from database...");
            $pppSecret->delete();
            $this->info("✅ Successfully deleted from database");
            
            $message = "🎉 PPP Secret '{$username}' deleted successfully";
            if ($syncWithMikrotik) {
                $message .= " from both database and MikroTik";
            } else {
                $message .= " from database only";
            }
            
            $this->info($message);
            
        } catch (\Exception $e) {
            $this->error("❌ Error during delete: " . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}
