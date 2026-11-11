<?php

use App\Http\Middleware\CatatAkses;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\AuthenticateSession;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Di belakang ngrok, port forward, Cloudflare, atau reverse proxy hosting: percaya header X-Forwarded-*
        // supaya tautan dan aset dibuat dengan host dan https yang benar (bukan http://localhost).
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'role' => EnsureUserHasRole::class,
            'audit' => CatatAkses::class,
        ]);
        // AuthenticateSession: ganti password otomatis mengeluarkan sesi di perangkat lain (laptop yang lupa logout)
        $middleware->web(append: [AuthenticateSession::class, SecurityHeaders::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();