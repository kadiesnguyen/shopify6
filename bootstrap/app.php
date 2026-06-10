<?php

use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureAdminApi;
use App\Http\Middleware\EnsureMember;
use App\Http\Middleware\EnsureMemberApi;
use App\Http\Middleware\SetLocale;
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
        \App\Console\Commands\MigrateFreshCommand::class,
        \App\Console\Commands\DatabaseUpgradeCommand::class,
        \App\Console\Commands\EnsureDatabaseReadyCommand::class,
    ])
    ->create();
