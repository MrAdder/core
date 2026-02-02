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
        if (! $this->app->runningUnitTests()) {
            return;
        }

        ParallelTesting::setUpProcess(function (int $token) {
            $this->setUpMysqlDatabase($token);
            $this->setUpCtsDatabase($token);

            // Seed ONCE per worker (against the default/mysql DB)
            Artisan::call('db:seed', ['--force' => true]);
        });
    }

    protected function setUpMysqlDatabase(int $token): void
    {
        $base = config('database.connections.mysql.database');
        $database = "{$base}_{$token}";

        // Use mysql connection to create the DB (requires base db exists!)
        DB::connection('mysql')->statement("CREATE DATABASE IF NOT EXISTS `{$database}`");

        config(['database.connections.mysql.database' => $database]);

        DB::purge('mysql');
        DB::reconnect('mysql');

        // Explicit connection so we don't accidentally migrate the wrong DB
        Artisan::call('migrate:fresh', [
            '--database' => 'mysql',
            '--force' => true,
        ]);
    }

    protected function setUpCtsDatabase(int $token): void
    {
        if (! config()->has('database.connections.cts')) {
            return;
        }

        $base = config('database.connections.cts.database');
        $database = "{$base}_{$token}";

        DB::connection('mysql')->statement("CREATE DATABASE IF NOT EXISTS `{$database}`");

        config(['database.connections.cts.database' => $database]);

        DB::purge('cts');
        DB::reconnect('cts');

        // Run CTS migrations against CTS connection explicitly
        Artisan::call('cts:migrate:fresh', [
            '--database' => 'cts',
            '--force' => true,
        ]);
    }
}
