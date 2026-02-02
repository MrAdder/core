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

        ParallelTesting::setUpTestDatabase(function (string $database, string $token): void {
            Artisan::call('migrate:fresh', ['--force' => true, '--no-interaction' => true]);
            Artisan::call('db:seed', ['--no-interaction' => true]);
            Artisan::call('cts:migrate:fresh', ['--force' => true, '--no-interaction' => true]);
        });
    }
}
