<?php

use App\Http\Middleware\CatatAktivitasPengguna;
use App\Http\Middleware\EnsureAdmin;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Percayai proxy seperti Cloudflare Tunnel agar Laravel
        // dapat mengenali request HTTPS dengan benar.
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'admin' => EnsureAdmin::class,
        ]);

        // Aktivitas dicatat untuk seluruh request web, bukan hanya halaman
        // ber-middleware "auth", supaya kunjungan ke halaman apa pun ikut
        // terhitung selama penggunanya sudah login.
        $middleware->appendToGroup('web', CatatAktivitasPengguna::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();