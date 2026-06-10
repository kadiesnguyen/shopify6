<?php

namespace App\Providers;

use App\Console\Commands\DatabaseUpgradeCommand;
use App\Console\Commands\EnsureDatabaseReadyCommand;
use App\Console\Commands\MigrateFreshCommand;
use App\Support\Database\DatabaseBootstrap;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->commands([
            MigrateFreshCommand::class,
            DatabaseUpgradeCommand::class,
            EnsureDatabaseReadyCommand::class,
        ]);

        DatabaseBootstrap::ensureReady();

        RateLimiter::for('login', function (Request $request) {
            $login = (string) ($request->input('email') ?: $request->input('login'));

            return Limit::perMinute(5)->by($login.'|'.$request->ip());
        });
    }
}
