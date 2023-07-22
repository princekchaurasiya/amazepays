<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Console\Commands\GenerateBearerToken;


class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Add the schedule to run the GenerateBearerToken command every day at 1:00 AM
        $schedule->command(GenerateBearerToken::class)->weekly()->mondays()->at('12:00');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */

     
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');
    }

    /**
     * Get the Artisan commands provided by your application.
     *
     * @return array
     */
    protected function getCommands()
    {
        return array_merge(
            parent::getCommands(),
            [
                // Add the GenerateBearerToken command to the commands array
                GenerateBearerToken::class,
            ]
        );
    }
}

