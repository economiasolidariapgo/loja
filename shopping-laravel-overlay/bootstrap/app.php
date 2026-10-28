<?php

use App\Http\Controllers\PublicController;
use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\StoreSession;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        // Rotas FORA do grupo "web": sem sessão/cookies, então não consultam o banco só para abrir sessão.
        then: function () {
            Route::get('/saude', [PublicController::class, 'saude'])->name('saude');   // monitor de uptime
            Route::get('/midia/{name}', [PublicController::class, 'midia'])            // imagens (dezenas por página)
                ->where('name', '[0-9a-f]{32}')
                ->middleware(SecurityHeaders::class)
                ->name('midia');
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Atrás de proxy/HTTPS (Render, Nginx, Cloudflare...). Se souber o IP do proxy, troque '*' por ele.
        $middleware->trustProxies(at: '*');

        // Não aparar espaços das senhas (o TrimStrings do Laravel já ignora "password", mas não "password2").
        $middleware->trimStrings(except: ['password', 'password2']);

        // Visitante em página protegida vai para /entrar
        $middleware->redirectGuestsTo(fn () => route('entrar'));

        $middleware->alias(['admin' => EnsureAdmin::class]);

        $middleware->web(append: [StoreSession::class, SecurityHeaders::class]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();
