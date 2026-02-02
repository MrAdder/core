<?php

namespace App\Providers;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\ParallelTesting;
use Illuminate\Support\ServiceProvider;

class ParallelTestingServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (! app()->runningUnitTests()) {
            return;
        }

        ParallelTesting::setUpProcess(function (int $token) {
            // 1) Prepare MySQL worker DB (default connection)
            $this->configureMysqlWorkerDatabase($token);

            // Run app migrations + seed once per worker DB
            Artisan::call('migrate:fresh', ['--force' => true]);
            Artisan::call('db:seed', ['--force' => true]);

            // 2) Prepare CTS worker DB (secondary connection), then migrate it
            $this->configureCtsWorkerDatabase($token);
            Artisan::call('cts:migrate:fresh');

            // If you truly need seeding in CTS DB too, prefer one of:
            // - a CTS-specific seeder command, OR
            // - seed using the cts connection explicitly (only if your seeders support it)
            //
            // Example if you have a CTS seeder:
            // Artisan::call('cts:seed', ['--force' => true]);
            //
            // Example if your normal seeders can target CTS:
            // Artisan::call('db:seed', ['--force' => true, '--database' => 'cts']);
        });
    }

    protected function configureMysqlWorkerDatabase(int $token): void
    {
        $base = (string) config('database.connections.mysql.database');
        $database = "{$base}_{$token}";

        // Create the worker DB using the current mysql connection.
        // Assumes the current connection user has CREATE DATABASE privileges.
        DB::statement("CREATE DATABASE IF NOT EXISTS `{$database}`");

        // Point the default mysql connection at the worker DB and reconnect
        config(['database.connections.mysql.database' => $database]);
        DB::purge('mysql');
        DB::reconnect('mysql');
    }

    protected function configureCtsWorkerDatabase(int $token): void
    {
        if (! config()->has('database.connections.cts')) {
            return;
        }

        $base = (string) config('database.connections.cts.database');
        $database = "{$base}_{$token}";

        // Create the CTS worker DB.
        // Use mysql connection to run CREATE DATABASE (same server/creds).
        DB::connection('mysql')->statement("CREATE DATABASE IF NOT EXISTS `{$database}`");

        // Point CTS connection at the worker DB and reconnect
        config(['database.connections.cts.database' => $database]);
        DB::purge('cts');
        DB::reconnect('cts');
    }
}
