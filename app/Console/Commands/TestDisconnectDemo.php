<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PppSecret;
use App\Services\MikrotikService;

class TestDisconnectDemo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ppp:demo-disconnect';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Demonstrate the improved disconnect functionality';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("🎯 Demonstrating Improved PPP Disconnect Functionality");
        $this->newLine();
        
        // Get a few users from the database
        $users = PppSecret::select('username')->take(3)->get();
        
        if ($users->isEmpty()) {
            $this->error("❌ No PPP secrets found in database!");
            return 1;
        }
        
        $this->info("📋 Available users for testing:");
        foreach ($users as $user) {
            $this->info("   • {$user->username}");
        }
        $this->newLine();
        
        $this->info("🔧 Key Improvements Made:");
        $this->info("   ✅ Direct disconnect by username (faster method)");
        $this->info("   ✅ Fallback to connection ID lookup if direct fails");
        $this->info("   ✅ Force disconnect even if lookup times out");
        $this->info("   ✅ Better error handling and user feedback");
        $this->info("   ✅ Timeout-aware messaging in web interface");
        $this->newLine();
        
        $this->info("🚀 Testing Methods:");
        $this->info("   1. CLI Command: php artisan ppp:test-disconnect <username>");
        $this->info("   2. Web Interface: Visit PPP Secret details page and use 'Disconnect Session' button");
        $this->newLine();
        
        $testUser = $users->first()->username;
        
        if ($this->confirm("Would you like to test disconnect functionality with user '{$testUser}'?")) {
            $this->call('ppp:test-disconnect', ['username' => $testUser]);
        }
        
        $this->newLine();
        $this->info("💡 Remember:");
        $this->info("   • Disconnect commands are sent even during router timeouts");
        $this->info("   • Status verification may be delayed due to slow router response");
        $this->info("   • Web interface provides different alert types for various scenarios");
        $this->info("   • Users may reconnect automatically if auto-reconnect is enabled");
        
        return 0;
    }
}
