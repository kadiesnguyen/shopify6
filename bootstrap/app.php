<?php

use App\Console\Commands\AutoCompleteShippedOrdersCommand;
use App\Console\Commands\DatabaseUpgradeCommand;
use App\Console\Commands\DbBackupCommand;
use App\Console\Commands\DbGuardCommand;
use App\Console\Commands\EnsureDatabaseReadyCommand;
use App\Console\Commands\MigrateFreshCommand;
use App\Console\Commands\SeedCommand;
use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureAdminApi;
use App\Http\Middleware\EnsureMember;
use App\Http\Middleware\EnsureMemberApi;
use App\Http\Middleware\SetLocale;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            Route::middleware('web')
                ->group(base_path('routes/member.php'));

            Route::middleware('web')
                ->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'locale' => SetLocale::class,
            'member' => EnsureMember::class,
            'admin' => EnsureAdmin::class,
            'member.api' => EnsureMemberApi::class,
            'admin.api' => EnsureAdminApi::class,
        ]);

        $middleware->web(append: [
            SetLocale::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })
    ->withCommands([
        MigrateFreshCommand::class,
        SeedCommand::class,
        DbBackupCommand::class,
        DbGuardCommand::class,
        DatabaseUpgradeCommand::class,
        EnsureDatabaseReadyCommand::class,
        AutoCompleteShippedOrdersCommand::class,
    ])
    ->withSchedule(function (Schedule $schedule): void {
        // Guard also lives in the command (hours <= 0 no-ops); skip schedule when disabled.
        if ((int) config('portal.order_auto_complete_hours', 0) > 0) {
            $schedule->command('orders:auto-complete-shipped')->everyFiveMinutes();
        }

        $schedule->command('queue:work --stop-when-empty --max-time=55 --tries=1 --timeout=30')
            ->everyMinute()
            ->withoutOverlapping(5);
    })
    ->create();
