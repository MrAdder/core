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
            $this->setUpMysqlDatabase($token);
            $this->setUpCtsDatabase($token);
        });
    }

    protected function setUpMysqlDatabase(int $token): void
    {
        $base = config('database.connections.mysql.database');
        $database = "{$base}_{$token}";

        // Requires that the current mysql connection points at a DB that exists.
        DB::statement("CREATE DATABASE IF NOT EXISTS `{$database}`");

        config(['database.connections.mysql.database' => $database]);
        DB::purge('mysql');
        DB::reconnect('mysql');
        Artisan::call('migrate:fresh');
        Artisan::call('db:seed', ['--force' => true]);
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

        // Build CTS schema in THIS worker DB (important!)
        Artisan::call('cts:migrate:fresh');
        Artisan::call('db:seed', ['--force' => true]);
    }
}
