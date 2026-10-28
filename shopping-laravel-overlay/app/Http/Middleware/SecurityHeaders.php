<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Mesmos headers de segurança do after_request do Flask. */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->add([
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'geolocation=(), camera=(), microphone=()',
        ]);

        // Com APP_DEBUG=true a página de erro do Laravel usa scripts/estilos inline; a CSP a deixaria em branco.
        if (! config('app.debug')) {
            $response->headers->set('Content-Security-Policy',
                "default-src 'self'; img-src 'self' data:; style-src 'self' https://fonts.googleapis.com; "
                ."font-src https://fonts.gstatic.com; script-src 'self'; base-uri 'self'; "
                ."form-action 'self'; frame-ancestors 'none'");
        }

        if (app()->isProduction()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
