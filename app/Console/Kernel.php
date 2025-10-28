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
        // Archiver les comptes ÉPARGNE bloqués dont la période de blocage est expirée (toutes les heures)
        $schedule->job(\App\Jobs\ArchiveExpiredBlockedAccountsJob::class)
            ->hourly()
            ->name('archive-expired-blocked-epargne-accounts')
            ->withoutOverlapping()
            ->runInBackground();

        // Restaurer les comptes bloqués dont la période de blocage est expirée (toutes les heures)
        $schedule->job(new \App\Jobs\RestoreExpiredBlockedAccountsJob)
            ->hourly()
            ->name('restore-expired-blocked-accounts')
            ->withoutOverlapping()
            ->runInBackground();
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
