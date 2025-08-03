<?php

namespace App\Console\Commands;

use App\Models\PppSecret;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\MikrotikService;
use Illuminate\Console\Command;
use Exception;

class RestoreBlockedUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:restore-blocked {--simulate-payment : Simulate payment verification} {--force : Force restore without payment}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restore blocked users to their original profiles';

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
        $simulatePayment = $this->option('simulate-payment');
        $force = $this->option('force');
        
        $this->info('🔍 Finding blocked users with original profiles...');
        
        // Get blocked users who have original profile
        $blockedUsers = PppSecret::with(['customer', 'pppProfile', 'originalPppProfile'])
            ->whereNotNull('original_ppp_profile_id')
            ->whereHas('pppProfile', function($query) {
                $query->where('name', 'Blokir');
            })
            ->get();
            
        if ($blockedUsers->isEmpty()) {
            $this->info('✅ No blocked users found with original profiles to restore.');
            return 0;
        }
        
        $this->info("📊 Found {$blockedUsers->count()} blocked users:");
        $this->table(['Username', 'Customer', 'Current Profile', 'Original Profile'], 
            $blockedUsers->map(function($user) {
                return [
                    $user->username,
                    $user->customer ? $user->customer->name : 'No Customer',
                    $user->pppProfile ? $user->pppProfile->name : 'None',
                    $user->originalPppProfile ? $user->originalPppProfile->name : 'None'
                ];
            })->toArray()
        );
        
        if ($simulatePayment) {
            $this->info("\n💳 Simulating payment verification process...");
            $this->simulatePayments($blockedUsers);
        } elseif ($force) {
            $this->info("\n🔄 Force restoring profiles...");
            $this->restoreProfiles($blockedUsers);
        } else {
            $this->info("\nOptions:");
            $this->line("  --simulate-payment : Create payments and verify them (full workflow)");
            $this->line("  --force           : Direct restore without payment (for testing)");
            return 0;
        }
        
        return 0;
    }
    
    /**
     * Simulate payment verification workflow
     */
    private function simulatePayments($blockedUsers)
    {
        $restored = 0;
        $errors = 0;
        
        foreach ($blockedUsers as $user) {
            try {
                $this->line("\n🔄 Processing {$user->username}...");
                
                // Find unpaid invoice for this user
                $invoice = Invoice::where('ppp_secret_id', $user->id)
                    ->where('status', 'unpaid')
                    ->latest()
                    ->first();
                    
                if (!$invoice) {
                    $this->warn("  ⚠️  No unpaid invoice found for {$user->username}");
                    continue;
                }
                
                $this->line("  📄 Found invoice: {$invoice->invoice_number} (Rp " . number_format($invoice->total_amount, 0, ',', '.') . ")");
                
                // Create payment
                $payment = Payment::create([
                    'invoice_id' => $invoice->id,
                    'customer_id' => $user->customer_id,
                    'payment_number' => 'PAY-' . date('YmdHis') . '-' . $user->id,
                    'amount' => $invoice->total_amount,
                    'payment_date' => now(),
                    'payment_method' => 'bank_transfer',
                    'status' => 'pending',
                    'reference_number' => 'SIM-' . strtoupper($user->username) . '-' . time(),
                ]);
                
                $this->line("  💳 Created payment: {$payment->payment_number}");
                
                // Verify payment (simulate admin verification)
                $payment->verify(1); // Using user ID 1 for simulation
                $invoice->updateStatus();
                
                $this->line("  ✅ Payment verified, invoice status: {$invoice->fresh()->status}");
                
                // Restore original profile via MikroTik service
                if ($invoice->fresh()->status === 'paid' && $user->original_ppp_profile_id) {
                    $this->mikrotikService->connect();
                    $this->mikrotikService->restoreOriginalProfile($user);
                    
                    $user->refresh();
                    $this->info("  🎉 Profile restored: {$user->username} -> {$user->pppProfile->name}");
                    $restored++;
                } else {
                    $this->error("  ❌ Failed to restore profile for {$user->username}");
                    $errors++;
                }
                
            } catch (Exception $e) {
                $this->error("  ❌ Error processing {$user->username}: {$e->getMessage()}");
                $errors++;
            }
        }
        
        $this->newLine();
        $this->info("📊 Simulation Summary:");
        $this->info("✅ Restored: {$restored} users");
        if ($errors > 0) {
            $this->error("❌ Errors: {$errors} users");
        }
    }
    
    /**
     * Direct profile restore (force mode)
     */
    private function restoreProfiles($blockedUsers)
    {
        $restored = 0;
        $errors = 0;
        
        foreach ($blockedUsers as $user) {
            try {
                $this->line("🔄 Restoring {$user->username}...");
                
                $this->mikrotikService->connect();
                $this->mikrotikService->restoreOriginalProfile($user);
                
                $user->refresh();
                $this->info("  ✅ {$user->username} -> {$user->pppProfile->name}");
                $restored++;
                
            } catch (Exception $e) {
                $this->error("  ❌ Failed to restore {$user->username}: {$e->getMessage()}");
                $errors++;
            }
        }
        
        $this->newLine();
        $this->info("📊 Force Restore Summary:");
        $this->info("✅ Restored: {$restored} users");
        if ($errors > 0) {
            $this->error("❌ Errors: {$errors} users");
        }
    }
}
