<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\PppSecret;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateMonthlyInvoices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:generate-monthly {--date= : Specific date to check (YYYY-MM-DD)} {--force : Force generation even if invoices exist}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate monthly invoices based on profile billing cycles';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $date = $this->option('date') ? Carbon::parse($this->option('date')) : Carbon::now();
        $force = $this->option('force');
        
        $this->info("Generating monthly invoices for date: {$date->format('Y-m-d')}");
        $this->info("Current day of month: {$date->day}");
        
        // Get all active PPP secrets with profiles that have billing cycles
        $secrets = PppSecret::with(['customer', 'pppProfile'])
            ->where('is_active', true)
            ->whereHas('pppProfile', function ($query) use ($date) {
                $query->where('billing_cycle_day', $date->day)
                      ->whereNotNull('billing_cycle_day')
                      ->where('price', '>', 0);
            })
            ->get();
        
        if ($secrets->isEmpty()) {
            $this->info("No PPP secrets found with billing cycle for day {$date->day}");
            return 0;
        }
        
        $this->info("Found {$secrets->count()} secrets to process:");
        
        $generated = 0;
        $skipped = 0;
        $errors = 0;
        
        foreach ($secrets as $secret) {
            $profile = $secret->pppProfile;
            
            $this->line("Processing: {$secret->username} (Profile: {$profile->name}, Cycle Day: {$profile->billing_cycle_day})");
            
            // Check if invoice already exists for this month
            $existingInvoice = Invoice::where('ppp_secret_id', $secret->id)
                ->whereYear('invoice_date', $date->year)
                ->whereMonth('invoice_date', $date->month)
                ->first();
            
            if ($existingInvoice && !$force) {
                $this->warn("  ⚠️  Invoice already exists for {$secret->username} in {$date->format('M Y')} (Invoice #: {$existingInvoice->invoice_number})");
                $skipped++;
                continue;
            }
            
            try {
                // Calculate invoice dates
                $invoiceDate = $date->copy();
                $dueDate = $date->copy()->addDays(1)->endOfDay(); // Due next day at 23:59
                
                // Calculate period (previous month for current billing)
                $periodStart = $date->copy()->subMonth()->startOfMonth();
                $periodEnd = $date->copy()->subMonth()->endOfMonth();
                
                $invoice = new Invoice([
                    'customer_id' => $secret->customer_id,
                    'ppp_secret_id' => $secret->id,
                    'invoice_date' => $invoiceDate,
                    'due_date' => $dueDate,
                    'amount' => $profile->price,
                    'tax' => 0, // You can modify this as needed
                    'total_amount' => $profile->price,
                    'status' => 'unpaid',
                    'notes' => "Monthly billing for {$secret->username} - Period: {$periodStart->format('M d')} to {$periodEnd->format('M d, Y')}",
                ]);
                
                $invoice->invoice_number = Invoice::generateInvoiceNumber();
                $invoice->save();
                
                $this->info("  ✅ Generated invoice #{$invoice->invoice_number} for {$secret->username} - Rp " . number_format($profile->price, 0, ',', '.'));
                $generated++;
                
            } catch (\Exception $e) {
                $this->error("  ❌ Failed to generate invoice for {$secret->username}: {$e->getMessage()}");
                $errors++;
            }
        }
        
        $this->newLine();
        $this->info("=== Summary ===");
        $this->info("Generated: {$generated} invoices");
        if ($skipped > 0) {
            $this->warn("Skipped: {$skipped} invoices (already exist)");
        }
        if ($errors > 0) {
            $this->error("Errors: {$errors} invoices");
        }
        
        return 0;
    }
}
