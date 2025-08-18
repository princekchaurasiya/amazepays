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

    protected $commands = [GenerateBearerToken::class];

    protected function schedule(Schedule $schedule)
    {


        $schedule->command('generate:bearerToken')->weekly()->mondays()->at('01:00');



        $schedule->command('fetch:categoryData')->monthlyOn(4, '02:30');
        // $schedule->command('fetch:categoryData')->everyMinute();

        $schedule->command('fetch:productList')->monthlyOn(4, '02:35');
        // $schedule->command('fetch:productList')->everyMinute();


        $schedule->command('fetch:productData')->monthlyOn(4, '02:40');
        // $schedule->command('fetch:productData')->everyMinute();

        $schedule->command('sync:stores')->dailyAt('01:00'); // adjust time as needed
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */

    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');
    }

    /**
     * Get the Artisan commands provided by your application.
     *
     * @return array
     */
    protected function getCommands()
    {
        return array_merge(parent::getCommands(), [
            // Add the GenerateBearerToken command to the commands array
            GenerateBearerToken::class,
        ]);
    }
}
