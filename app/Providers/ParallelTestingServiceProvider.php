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
            // IMPORTANT: switch connections to per-worker DBs first
            $this->switchMysqlToWorkerDatabase($token);
            $this->switchCtsToWorkerDatabase($token);

            // Build schema once per worker (NOT per test)
            Artisan::call('migrate:fresh', ['--force' => true]);

            // Seed once per worker so permissions exist for all tests
            Artisan::call('db:seed', ['--force' => true]);

            // Build CTS schema once per worker
            Artisan::call('cts:migrate:fresh');
        });
    }

    private function switchMysqlToWorkerDatabase(int $token): void
    {
        $base = (string) config('database.connections.mysql.database');
        $workerDb = "{$base}_{$token}";

        DB::statement("CREATE DATABASE IF NOT EXISTS `{$workerDb}`");

        config(['database.connections.mysql.database' => $workerDb]);
        DB::purge('mysql');
        DB::reconnect('mysql');
    }

    private function switchCtsToWorkerDatabase(int $token): void
    {
        if (! config()->has('database.connections.cts')) {
            return;
        }

        $base = (string) config('database.connections.cts.database');
        $workerDb = "{$base}_{$token}";

        // create CTS DB using mysql connection (it exists + has perms)
        DB::connection('mysql')->statement("CREATE DATABASE IF NOT EXISTS `{$workerDb}`");

        config(['database.connections.cts.database' => $workerDb]);
        DB::purge('cts');
        DB::reconnect('cts');
    }
}
