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
        '\App\Console\Commands\CheckSubscription',
        '\App\Console\Commands\CheckPostJobRequest',
        \App\Console\Commands\OptimizeImages::class,
        \App\Console\Commands\UpdateEliteTechnicians::class,
        \App\Console\Commands\ReleaseScheduledEscrow::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();
        if(default_earning_type() === 'subscription'){
            $schedule->command('check:subscription')->daily();
        }

        $schedule->command('check:postjobrequest')->daily();
        $schedule->command('elite:update')->daily();
        $schedule->command('sand:release-escrow')->dailyAt('02:00');
        $schedule->command('sand:reconcile')->dailyAt('03:00');
        $schedule->command('sand:retry-failed')->everyHour();

    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
