<?php

namespace App\Providers;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
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

        /**
         * Runs once per parallel worker process.
         * Perfect place to:
         *  - point the CTS connection at a unique database for this token
         *  - create that database if needed
         *  - rebuild CTS schema once per worker
         */
        ParallelTesting::setUpProcess(function (int $token): void {
            $this->setUpCtsDatabaseForToken($token);

            // Rebuild CTS schema for this worker (isolated DB => no deadlocks/races)
            Artisan::call('cts:migrate:fresh', ['--no-interaction' => true]);
        });

        /**
         * Runs when Laravel is setting up the "main" test database.
         * Keep this for your default connection migrations/seeds.
         */
        ParallelTesting::setUpTestDatabase(function (string $database, string $token): void {
            Artisan::call('migrate:fresh', ['--force' => true, '--no-interaction' => true]);
            Artisan::call('db:seed', ['--no-interaction' => true]);
        });
    }

    private function setUpCtsDatabaseForToken(int $token): void
    {
        // Base CTS database name from config (e.g. "cts_test")
        $base = (string) Config::get('database.connections.cts.database');

        // Make it per worker: cts_test_1, cts_test_2, ...
        $dbName = "{$base}_{$token}";

        // Update runtime config for this worker process
        Config::set('database.connections.cts.database', $dbName);

        // Reconnect so the new database name takes effect
        DB::purge('cts');

        // Create the DB (connect without selecting a DB)
        $this->createDatabaseIfMissing($dbName);

        DB::reconnect('cts');
    }

    private function createDatabaseIfMissing(string $dbName): void
    {
        $ctsConfig = Config::get('database.connections.cts');

        // Build a "server" connection using same creds but without database selected.
        $serverConfig = $ctsConfig;
        $serverConfig['database'] = null;

        Config::set('database.connections.cts_server', $serverConfig);

        DB::purge('cts_server');

        $conn = DB::connection('cts_server');

        // Works for MySQL/MariaDB.
        $conn->statement("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        DB::disconnect('cts_server');
    }
}
