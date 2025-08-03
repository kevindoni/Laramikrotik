<?php

namespace App\Console\Commands;

use App\Models\PppSecret;
use App\Models\PppProfile;
use App\Models\Notification;
use App\Services\MikrotikService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Exception;

class AutoBlockOverdueUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:auto-block-overdue {--dry-run : Show what would be blocked without actually blocking}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically block users whose due date has passed';

    protected $mikrotikService;

    /**
     * Create a new command instance.
     */
    public function __construct(MikrotikService $mikrotikService)
    {
        parent::__construct();
        $this->mikrotikService = $mikrotikService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Checking for overdue users...');
        
        try {
            // Get current date/time
            $now = Carbon::now();
            $this->info("⏰ Current time: {$now->format('Y-m-d H:i:s')}");

            // Find users whose due_date has passed (including today's 23:59)
            $overdueUsers = PppSecret::with(['customer', 'pppProfile'])
                ->whereNotNull('due_date')
                ->where('due_date', '<', $now)
                ->where('is_active', true)
                ->get();

            $this->info("📊 Found {$overdueUsers->count()} overdue users");

            // Get Blokir profile
            $blokirProfile = PppProfile::where('name', 'Blokir')->first();
            
            if (!$blokirProfile) {
                $this->error('❌ Profile "Blokir" not found. Please create this profile first.');
                return 1;
            }

            // Filter users that are not already blocked
            $usersToBlock = $overdueUsers->filter(function ($user) use ($blokirProfile) {
                return $user->ppp_profile_id !== $blokirProfile->id;
            });

            $this->info("🎯 Users that need to be blocked: {$usersToBlock->count()}");

            if ($usersToBlock->isEmpty()) {
                $this->info('✅ No users need to be blocked. All overdue users are already blocked.');
                return 0;
            }

            // Show users that will be blocked
            $this->table(
                ['Username', 'Customer', 'Current Profile', 'Due Date', 'Days Overdue'],
                $usersToBlock->map(function ($user) use ($now) {
                    $customerName = $user->customer ? $user->customer->name : 'N/A';
                    $profileName = $user->pppProfile ? $user->pppProfile->name : 'N/A';
                    return [
                        $user->username,
                        $customerName,
                        $profileName,
                        $user->due_date->format('Y-m-d H:i'),
                        $now->diffInDays($user->due_date) . ' days'
                    ];
                })
            );

            if ($this->option('dry-run')) {
                $this->warn('🧪 DRY RUN: No users were actually blocked.');
                return 0;
            }

            // Confirm before proceeding
            if (!$this->confirm('Do you want to proceed with blocking these users?')) {
                $this->info('❌ Operation cancelled.');
                return 0;
            }

            // Connect to MikroTik
            $this->info('🔌 Connecting to MikroTik...');
            $this->mikrotikService->connect();

            $blockedCount = 0;
            $errors = [];

            foreach ($usersToBlock as $user) {
                try {
                    $oldProfile = $user->pppProfile;
                    
                    // Save original profile before blocking (if not already saved)
                    if (!$user->original_ppp_profile_id) {
                        $user->original_ppp_profile_id = $user->ppp_profile_id;
                    }
                    
                    // Update profile in database
                    $user->ppp_profile_id = $blokirProfile->id;
                    $user->save();
                    
                    // Update profile in MikroTik
                    $this->updateProfileInMikrotik($user, $blokirProfile);
                    
                    $customerName = $user->customer ? $user->customer->name : 'N/A';
                    $this->info("✅ Blocked: {$user->username} ({$customerName})");
                    $blockedCount++;

                    // Create notification
                    $customerName = $user->customer ? $user->customer->name : 'N/A';
                    Notification::create([
                        'type' => 'user_blocked',
                        'title' => 'User Diblokir Otomatis',
                        'message' => "User {$user->username} ({$customerName}) telah diblokir otomatis karena melewati due date {$user->due_date->format('d M Y H:i')}",
                        'data' => [
                            'username' => $user->username,
                            'customer_name' => $customerName,
                            'old_profile' => $oldProfile->name,
                            'new_profile' => $blokirProfile->name,
                            'due_date' => $user->due_date->format('Y-m-d H:i:s'),
                            'blocked_at' => now()->format('Y-m-d H:i:s')
                        ],
                        'icon' => 'fas fa-user-slash',
                        'color' => 'warning',
                    ]);

                } catch (Exception $e) {
                    $this->error("❌ Failed to block {$user->username}: {$e->getMessage()}");
                    $errors[] = "Failed to block {$user->username}: {$e->getMessage()}";
                }
            }

            $this->info("🎉 Auto-block completed!");
            $this->info("✅ Successfully blocked: {$blockedCount} users");
            
            if (!empty($errors)) {
                $this->warn("⚠️  Errors: " . count($errors));
                foreach ($errors as $error) {
                    $this->error("   - {$error}");
                }
            }

            // Create summary notification
            if ($blockedCount > 0) {
                Notification::create([
                    'type' => 'auto_block_summary',
                    'title' => 'Auto-Block Summary',
                    'message' => "Auto-block selesai. {$blockedCount} user telah diblokir karena melewati due date",
                    'data' => [
                        'blocked_count' => $blockedCount,
                        'error_count' => count($errors),
                        'execution_time' => now()->format('Y-m-d H:i:s')
                    ],
                    'icon' => 'fas fa-robot',
                    'color' => 'info',
                ]);
            }

            return 0;

        } catch (Exception $e) {
            $this->error("❌ Command failed: {$e->getMessage()}");
            return 1;
        }
    }

    /**
     * Update profile in MikroTik router
     */
    private function updateProfileInMikrotik($secret, $profile)
    {
        try {
            // Get existing PPP secret from MikroTik
            $secrets = $this->mikrotikService->getAllPppSecrets();
            $mikrotikSecret = collect($secrets)->firstWhere('name', $secret->username);
            
            if ($mikrotikSecret) {
                // Update the profile
                $this->mikrotikService->updatePppSecret($mikrotikSecret['.id'], [
                    'profile' => $profile->name
                ]);
                
                // Disconnect active session to force profile change
                $this->mikrotikService->disconnectPppSession($secret->username);
            }
        } catch (Exception $e) {
            $this->warn("⚠️  Failed to update MikroTik for {$secret->username}: {$e->getMessage()}");
            // Don't throw - database was updated successfully
        }
    }
}
