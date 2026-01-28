<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Robust tea scraping scheduler
        // Daily scraping at 2 AM (off-peak hours) with longer delays
        $schedule->command('scrape:robust-tea --delay=5')
                ->daily()
                ->at('02:00')
                ->description('Daily tea data scraping with 5s delays');
        
        // Weekly fresh scraping on Sundays at 3 AM
        $schedule->command('scrape:robust-tea --force --delay=10')
                ->weekly()
                ->sundays()
                ->at('03:00')
                ->description('Weekly fresh tea data scraping');
        
        // Hourly cache check (no scraping, just checks cache status)
        $schedule->command('scrape:cache-status')
                ->hourly()
                ->description('Check scraping cache status');
        
        // Monthly cache cleanup on 1st at 4 AM
        $schedule->command('scrape:cache-clear --expired')
                ->monthlyOn(1, '04:00')
                ->description('Monthly expired cache cleanup');
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
