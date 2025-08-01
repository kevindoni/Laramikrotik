<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MikrotikService;
use App\Models\MikrotikSetting;
use Exception;

class SyncMikrotikData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mikrotik:sync
                          {--profiles : Sync only PPP profiles}
                          {--secrets : Sync only PPP secrets}
                          {--stats : Show sync statistics only}
                          {--force : Force sync even if recently synced}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync data from MikroTik router (profiles, secrets, etc.)';

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
        $activeSetting = MikrotikSetting::getActive();
        
        if (!$activeSetting) {
            $this->error('❌ No active MikroTik setting found!');
            $this->info('💡 Please configure a MikroTik connection first.');
            return 1;
        }

        $this->info("🔗 Connecting to MikroTik: {$activeSetting->name} ({$activeSetting->host})");

        try {
            // Test connection
            $this->mikrotikService->connect();
            $identity = $this->mikrotikService->getSystemIdentity();
            $this->info("✅ Connected to MikroTik: {$identity}");

            // Show statistics only
            if ($this->option('stats')) {
                $this->showStatistics();
                return 0;
            }

            // Check if recently synced (unless forced)
            if (!$this->option('force') && $activeSetting->last_connected_at && 
                $activeSetting->last_connected_at->diffInMinutes(now()) < 5) {
                
                if (!$this->confirm('Data was synced recently. Continue anyway?')) {
                    $this->info('Sync cancelled.');
                    return 0;
                }
            }

            $this->info('');
            $this->info('🔄 Starting synchronization...');

            // Sync specific components or all
            if ($this->option('profiles')) {
                $this->syncProfiles();
            } elseif ($this->option('secrets')) {
                $this->syncSecrets();
            } else {
                $this->syncAll();
            }

            $this->info('');
            $this->info('✅ Synchronization completed successfully!');
            
            // Show final statistics
            $this->showStatistics();

        } catch (Exception $e) {
            $this->error('❌ Sync failed: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    /**
     * Sync only PPP profiles.
     */
    protected function syncProfiles()
    {
        $this->info('📋 Syncing PPP Profiles...');
        
        $results = $this->mikrotikService->syncPppProfiles();
        
        $this->info("   ✅ Synced: {$results['synced']} profiles");
        $this->info("   ⏭️  Skipped: {$results['skipped']} profiles");
        $this->info("   📊 Total: {$results['total']} profiles found");
    }

    /**
     * Sync only PPP secrets.
     */
    protected function syncSecrets()
    {
        $this->info('🔐 Syncing PPP Secrets...');
        
        $results = $this->mikrotikService->syncPppSecrets();
        
        $this->info("   ✅ Synced: {$results['synced']} secrets");
        $this->info("   ⏭️  Skipped: {$results['skipped']} secrets");
        $this->info("   📊 Total: {$results['total']} secrets found");
    }

    /**
     * Sync all data.
     */
    protected function syncAll()
    {
        $results = $this->mikrotikService->syncAllFromMikrotik();
        
        $this->info('📋 PPP Profiles:');
        $this->info("   ✅ Synced: {$results['profiles']['synced']}");
        $this->info("   ⏭️  Skipped: {$results['profiles']['skipped']}");
        $this->info("   📊 Total: {$results['profiles']['total']}");
        
        $this->info('');
        $this->info('🔐 PPP Secrets:');
        $this->info("   ✅ Synced: {$results['secrets']['synced']}");
        $this->info("   ⏭️  Skipped: {$results['secrets']['skipped']}");
        $this->info("   📊 Total: {$results['secrets']['total']}");
    }

    /**
     * Show sync statistics.
     */
    protected function showStatistics()
    {
        $stats = $this->mikrotikService->getSyncStatistics();
        
        $this->info('');
        $this->info('📊 SYNCHRONIZATION STATISTICS');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        
        $this->table(
            ['Source', 'Profiles', 'Secrets', 'Others'],
            [
                [
                    'MikroTik Router',
                    $stats['mikrotik']['profiles'],
                    $stats['mikrotik']['secrets'],
                    $stats['mikrotik']['active_connections'] . ' active connections'
                ],
                [
                    'Local Database',
                    $stats['database']['profiles'],
                    $stats['database']['secrets'],
                    $stats['database']['customers'] . ' customers'
                ]
            ]
        );

        if ($stats['last_sync']) {
            $this->info("🕒 Last sync: {$stats['last_sync']->diffForHumans()}");
        }

        $this->info('');
        $this->info('💡 Use --profiles or --secrets to sync specific data only');
        $this->info('💡 Use --force to bypass recent sync check');
    }
}
