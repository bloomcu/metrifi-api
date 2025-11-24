<?php

namespace DDD\App\Console;

use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Console\Scheduling\Schedule;
use DDD\Domain\Admin\Commands\SnapshotAllFunnelsCommand;
use DDD\Domain\Admin\Commands\SendAllOrganizationWeeklyAnalysisEmailCommand;
use DDD\Domain\Admin\Commands\AnalyzeAllDashboardsCommand;
use DDD\App\Console\Commands\EncryptConnectionTokens;
use DDD\App\Console\Commands\RefreshGoogleAnalyticsTokens;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        AnalyzeAllDashboardsCommand::class,
        SendAllOrganizationWeeklyAnalysisEmailCommand::class,
        SnapshotAllFunnelsCommand::class,
        EncryptConnectionTokens::class,
        RefreshGoogleAnalyticsTokens::class,
    ];

    /**
     * Define the application's command schedule.
     * 00:00 is midnight
     */
    protected function schedule(Schedule $schedule): void
    {
        // cd /home/forge/staging-api.metrifi && php artisan schedule:run
        // php /home/forge/staging-api.metrifi/artisan schedule:run

        $schedule->command('admin:snapshot-all-funnels')->dailyAt('04:00')->timezone('America/Denver'); // 4:00 am
        $schedule->command('admin:analyze-all-dashboards')->dailyAt('04:30')->timezone('America/Denver'); // 4:30 am

        if (app()->environment() === 'production') {
            $schedule->command('admin:send-all-organization-weekly-analysis-email')
                ->mondays()
                ->at('09:00')
                ->timezone('America/Denver')
                ->withoutOverlapping(120); // Prevent overlapping runs, lock for 120 minutes max
        }
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
