<?php

declare(strict_types=1);

use App\Support\MonitoringErrorReporter;
use App\Http\Middleware\ExamGuard;
use App\Http\Middleware\AdminAuthenticate;
use App\Http\Middleware\EnsureUserRole;
use App\Http\Middleware\ParticipantAuthenticate;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'exam.guard' => ExamGuard::class,
            'admin.auth' => AdminAuthenticate::class,
            'role' => EnsureUserRole::class,
            'participant.auth' => ParticipantAuthenticate::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->report(function (\Throwable $exception): void {
            app(MonitoringErrorReporter::class)->report($exception);
        });
    })
    ->create();
