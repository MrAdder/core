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
            // 1) MAIN DB (mysql)
            $this->configureMysqlWorkerDatabase($token);

            // Build schema + seed ONCE per worker
            Artisan::call('migrate:fresh', ['--force' => true]);
            Artisan::call('db:seed', ['--force' => true]);

            // 2) CTS DB (secondary)
            $this->configureCtsWorkerDatabase($token);

            // Build CTS schema ONCE per worker DB
            Artisan::call('cts:migrate:fresh');
        });
    }

    protected function configureMysqlWorkerDatabase(int $token): void
    {
        $base = (string) config('database.connections.mysql.database');
        $database = "{$base}_{$token}";

        // Create worker DB
        DB::statement("CREATE DATABASE IF NOT EXISTS `{$database}`");

        // Switch connection to worker DB
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

        // Create CTS worker DB
        DB::connection('mysql')->statement("CREATE DATABASE IF NOT EXISTS `{$database}`");

        // Switch CTS connection to worker DB
        config(['database.connections.cts.database' => $database]);
        DB::purge('cts');
        DB::reconnect('cts');
    }
}
