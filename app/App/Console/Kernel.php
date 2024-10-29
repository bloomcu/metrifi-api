<?php

namespace DDD\App\Console;

use DDD\App\Console\Commands\SyncRecommendationOrgs;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Console\Scheduling\Schedule;
use DDD\Domain\Admin\Commands\AnalyzeAllDashboardsCommand;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        SyncRecommendationOrgs::class,
        AnalyzeAllDashboardsCommand::class,
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // cd /home/forge/staging-api.metrifi && php artisan schedule:run
        // php /home/forge/staging-api.metrifi/artisan schedule:run
        $schedule->command('admin:analyze-all-dashboards')->dailyAt('02:00')->timezone('America/Denver'); // 00:00 is midnight
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
