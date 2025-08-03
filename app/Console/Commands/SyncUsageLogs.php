<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\UsageLogService;
use App\Services\MikrotikService;

class SyncUsageLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mikrotik:sync-usage-logs 
                            {--cleanup : Also cleanup old logs}
                            {--days=90 : Days to keep when cleaning up}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync usage logs from MikroTik router';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Starting usage logs sync from MikroTik...');
        $this->newLine();

        try {
            $mikrotikService = new MikrotikService();
            $usageLogService = new UsageLogService($mikrotikService);

            // Sync usage logs
            $result = $usageLogService->syncFromMikrotik();

            if ($result['success']) {
                $this->info("✅ Successfully synced {$result['synced']} active connections");
                
                if (!empty($result['errors'])) {
                    $this->warn("⚠️  Some errors occurred:");
                    foreach ($result['errors'] as $error) {
                        $this->line("   • {$error}");
                    }
                }
            } else {
                $this->error("❌ Sync failed: {$result['message']}");
                return Command::FAILURE;
            }

            // Cleanup old logs if requested
            if ($this->option('cleanup')) {
                $days = (int) $this->option('days');
                $this->info("🧹 Cleaning up logs older than {$days} days...");
                
                $deleted = $usageLogService->cleanupOldLogs($days);
                $this->info("🗑️  Deleted {$deleted} old log entries");
            }

            $this->newLine();
            $this->info('✅ Usage logs sync completed successfully!');
            
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("❌ Failed to sync usage logs: " . $e->getMessage());
            $this->newLine();
            $this->error("Stack trace:");
            $this->line($e->getTraceAsString());
            
            return Command::FAILURE;
        }
    }
}
