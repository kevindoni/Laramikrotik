<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\PppProfile;
use App\Models\PppSecret;
use App\Services\MikrotikService;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;

class ProcessOverdueInvoices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:process-overdue {--dry-run : Show what would be done without actually doing it}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process overdue invoices and move PPP secrets to Blokir profile';

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
        $dryRun = $this->option('dry-run');
        $now = Carbon::now();
        
        $this->info("Processing overdue invoices at: {$now->format('Y-m-d H:i:s')}");
        if ($dryRun) {
            $this->warn("DRY RUN MODE - No actual changes will be made");
        }
        
        // Get Blokir profile
        $blokirProfile = PppProfile::where('name', 'Blokir')->first();
        if (!$blokirProfile) {
            $this->error("Blokir profile not found! Please create it first.");
            return 1;
        }
        
        // Find overdue invoices with active PPP secrets
        $overdueInvoices = Invoice::with(['pppSecret', 'pppSecret.pppProfile'])
            ->where('status', 'unpaid')
            ->where('due_date', '<', $now)
            ->whereHas('pppSecret', function ($query) {
                $query->where('is_active', true)
                      ->where('ppp_profile_id', '!=', 
                          PppProfile::where('name', 'Blokir')->value('id')
                      );
            })
            ->get();
        
        if ($overdueInvoices->isEmpty()) {
            $this->info("No overdue invoices found with active PPP secrets to process.");
            return 0;
        }
        
        $this->info("Found {$overdueInvoices->count()} overdue invoices to process:");
        
        $processed = 0;
        $errors = 0;
        
        try {
            if (!$dryRun) {
                $this->mikrotikService->connect();
            }
            
            foreach ($overdueInvoices as $invoice) {
                $secret = $invoice->pppSecret;
                $currentProfile = $secret->pppProfile;
                $overdueDays = $now->diffInDays($invoice->due_date);
                
                $this->line("Processing: {$secret->username}");
                $this->line("  Invoice: #{$invoice->invoice_number}");
                $this->line("  Due Date: {$invoice->due_date->format('Y-m-d H:i')}");
                $this->line("  Overdue: {$overdueDays} days");
                $this->line("  Current Profile: {$currentProfile->name}");
                
                if ($dryRun) {
                    $this->warn("  [DRY RUN] Would move to Blokir profile");
                } else {
                    try {
                        // Save original profile before blocking (if not already saved)
                        if (!$secret->original_ppp_profile_id) {
                            $secret->original_ppp_profile_id = $secret->ppp_profile_id;
                        }
                        
                        // Update profile in database
                        $secret->ppp_profile_id = $blokirProfile->id;
                        $secret->save();
                        
                        // Update profile in MikroTik
                        $this->mikrotikService->updatePppSecretProfile($secret, $blokirProfile);
                        
                        $this->info("  ✅ Moved to Blokir profile");
                        $processed++;
                        
                    } catch (Exception $e) {
                        $this->error("  ❌ Failed to move {$secret->username}: {$e->getMessage()}");
                        $errors++;
                    }
                }
                
                $this->line("  ---");
            }
            
        } catch (Exception $e) {
            $this->error("Failed to connect to MikroTik: {$e->getMessage()}");
            return 1;
        }
        
        $this->newLine();
        $this->info("=== Summary ===");
        if ($dryRun) {
            $this->info("Would process: {$overdueInvoices->count()} overdue invoices");
        } else {
            $this->info("Processed: {$processed} PPP secrets moved to Blokir");
            if ($errors > 0) {
                $this->error("Errors: {$errors} failed to process");
            }
        }
        
        return 0;
    }
}
