<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Generate monthly invoices daily at 6 AM
        $schedule->command('invoices:generate-monthly')
                 ->dailyAt('06:00')
                 ->withoutOverlapping()
                 ->appendOutputTo(storage_path('logs/invoice-generation.log'));
        
        // Process overdue invoices every hour at 5 minutes past the hour
        $schedule->command('invoices:process-overdue')
                 ->hourly()
                 ->at(5)
                 ->withoutOverlapping()
                 ->appendOutputTo(storage_path('logs/overdue-processing.log'));
        
        // Auto-block overdue users every hour at 10 minutes past the hour
        $schedule->command('users:auto-block-overdue')
                 ->hourly()
                 ->at(10)
                 ->withoutOverlapping()
                 ->appendOutputTo(storage_path('logs/auto-block.log'));
        
        // Sync PPP profiles and secrets with MikroTik daily at 2 AM
        $schedule->command('mikrotik:sync')
                 ->dailyAt('02:00')
                 ->withoutOverlapping()
                 ->appendOutputTo(storage_path('logs/mikrotik-sync.log'));
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
