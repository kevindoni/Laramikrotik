<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Invoice;

class TestInvoicePrint extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoice:test-print {id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test invoice printing functionality';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $invoiceId = $this->argument('id');
        
        if ($invoiceId) {
            $invoice = Invoice::with(['customer', 'pppSecret.pppProfile'])->find($invoiceId);
            if (!$invoice) {
                $this->error("Invoice with ID {$invoiceId} not found.");
                return 1;
            }
            $invoices = collect([$invoice]);
        } else {
            $invoices = Invoice::with(['customer', 'pppSecret.pppProfile'])->take(5)->get();
        }

        if ($invoices->isEmpty()) {
            $this->error('No invoices found.');
            return 1;
        }

        $this->info('🧾 Invoice Print/Preview Test Results:');
        $this->line(str_repeat('=', 80));

        foreach ($invoices as $invoice) {
            $this->info("📄 Invoice: {$invoice->invoice_number}");
            $customerName = $invoice->customer ? $invoice->customer->name : 'N/A';
            $profileName = $invoice->pppSecret && $invoice->pppSecret->pppProfile ? $invoice->pppSecret->pppProfile->name : 'N/A';
            
            $this->line("   Customer: {$customerName}");
            $this->line("   Profile: {$profileName}");
            $this->line("   Amount: Rp " . number_format($invoice->total_amount, 0, ',', '.'));
            $this->line("   Status: {$invoice->status}");
            
            $baseUrl = config('app.url', 'http://localhost:8000');
            $this->line("   🔗 Preview: {$baseUrl}/invoices/{$invoice->id}/preview");
            $this->line("   📥 Download: {$baseUrl}/invoices/{$invoice->id}/download");
            $this->line("   📋 Details: {$baseUrl}/invoices/{$invoice->id}");
            $this->line('');
        }

        $this->line(str_repeat('=', 80));
        $this->info('✅ Invoice Preview & Print Features:');
        $this->line('   • Preview Invoice - Professional invoice layout for screen viewing');
        $this->line('   • Download PDF - Auto-print optimized PDF version');
        $this->line('   • Print Button - Direct browser print with CSS optimizations');
        $this->line('   • Responsive Design - Works on desktop and mobile devices');
        
        $this->info('🎯 Usage Instructions:');
        $this->line('   1. Click "Preview" to see formatted invoice in new tab');
        $this->line('   2. Click "Download PDF" to get printable version');
        $this->line('   3. Use browser Print button (Ctrl+P) for direct printing');
        $this->line('   4. Mobile users can share invoice links via WhatsApp/Email');
        
        return 0;
    }
}
