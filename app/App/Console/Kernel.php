<?php

namespace DDD\App\Console;

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
        AnalyzeAllDashboardsCommand::class,
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('admin:analyze-all-dashboards')->dailyAt('02:00'); // 2:00 AM UTC (https://www.timeanddate.com/worldclock/timezone/utc)
        $schedule->command('admin:analyze-all-dashboards')->dailyAt('17:00')->timezone('America/Denver');	; // https://www.timeanddate.com/worldclock/timezone/utc
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
