<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\ParallelTesting;

class ParallelTestingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if (! app()->runningUnitTests()) {
            return;
        }

        ParallelTesting::setUpProcess(function (int $token) {
            $this->setUpMysqlDatabase($token);
            $this->setUpCtsDatabase($token); // optional
        });
    }

    protected function setUpMysqlDatabase(int $token): void
    {
        $base = config('database.connections.mysql.database');
        $database = "{$base}_{$token}";

        DB::statement("CREATE DATABASE IF NOT EXISTS `{$database}`");

        config([
            'database.connections.mysql.database' => $database,
        ]);
    }

    protected function setUpCtsDatabase(int $token): void
    {
        if (! config('database.connections.cts')) {
            return;
        }

        $base = config('database.connections.cts.database');
        $database = "{$base}_{$token}";

        DB::connection('mysql')->statement(
            "CREATE DATABASE IF NOT EXISTS `{$database}`"
        );

        config([
            'database.connections.cts.database' => $database,
        ]);
    }
}
