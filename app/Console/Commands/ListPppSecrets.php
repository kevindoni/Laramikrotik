<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MikrotikService;

class ListPppSecrets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ppp:list-secrets {search?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List PPP secrets from MikroTik router';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $search = $this->argument('search');
        
        $this->info("📋 Listing PPP Secrets from MikroTik...");
        
        try {
            $mikrotikService = new MikrotikService();
            $mikrotikService->connect();
            
            $this->info("🔌 Connected to MikroTik successfully");
            
            // Get all secrets
            $secrets = $mikrotikService->getPppSecrets();
            
            if (empty($secrets)) {
                $this->warn("⚠️ No PPP secrets found or timeout occurred");
                return 0;
            }
            
            // Filter by search if provided
            if ($search) {
                $secrets = array_filter($secrets, function($secret) use ($search) {
                    return stripos($secret['name'], $search) !== false;
                });
                $this->info("🔍 Filtered results for: {$search}");
            }
            
            $this->info("📊 Found " . count($secrets) . " PPP secret(s):");
            $this->line("");
            
            $headers = ['#', 'Name', 'Service', 'Profile', 'MikroTik ID'];
            $rows = [];
            
            foreach ($secrets as $index => $secret) {
                $rows[] = [
                    $index + 1,
                    $secret['name'] ?? 'N/A',
                    $secret['service'] ?? 'N/A',
                    $secret['profile'] ?? 'N/A',
                    $secret['.id'] ?? 'N/A',
                ];
            }
            
            $this->table($headers, $rows);
            
        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}
