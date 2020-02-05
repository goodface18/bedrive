<?php

namespace App\Console;

use App\Console\Commands\CleanDemoSite;
use App\Console\Commands\CreateDemoAccounts;
use App\Console\Commands\DeleteExpiredLinks;
use App\Console\Commands\EmptyTempDirectory;
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
        DeleteExpiredLinks::class,
        EmptyTempDirectory::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
         $schedule->command('links:delete_expired')->everyFiveMinutes();

         $schedule->command('tempDir:empty')->hourly();

         if (config('common.site.demo')) {
             $schedule->command('demoSite:clean')->daily();
         }
    }

    /**
     * Register the Closure based commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        if (config('common.site.demo')) {
            $this->registerCommand(app(CreateDemoAccounts::class));
            $this->registerCommand(app(CleanDemoSite::class));
        }

        //require base_path('routes/console.php');
    }
}
