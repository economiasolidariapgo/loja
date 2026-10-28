<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Limites de tentativas (Flask-Limiter). Aplicados só nos POST, por IP.
        RateLimiter::for('cadastro', fn (Request $r) => Limit::perHour(8)->by($r->ip()));
        RateLimiter::for('entrar', fn (Request $r) => [
            Limit::perMinute(10)->by($r->ip()),
            Limit::perHour(60)->by($r->ip()),
        ]);
        RateLimiter::for('recuperar', fn (Request $r) => Limit::perHour(5)->by($r->ip()));
        RateLimiter::for('redefinir', fn (Request $r) => Limit::perHour(10)->by($r->ip()));

        // Antigo context_processor `inject()`
        View::share([
            'categories' => config('shopping.categories'),
            'days' => config('shopping.days'),
            'siteWa' => config('shopping.site_whatsapp'),
        ]);
    }
}
