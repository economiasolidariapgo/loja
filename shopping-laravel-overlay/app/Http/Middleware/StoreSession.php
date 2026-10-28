<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Junta dois comportamentos do Flask:
 *  - user_loader: loja desativada vale como deslogada;
 *  - force_change: com senha temporária, só deixa acessar "trocar senha" e "sair".
 */
class StoreSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $store = Auth::user();

        if ($store && ! $store->is_active) {
            Auth::logout();
            $store = null;
        }

        // /trocar-senha por caminho porque o GET e o POST compartilham a URL (só o GET tem nome)
        if ($store && $store->must_change_pw && ! $request->is('trocar-senha') && ! $request->routeIs('sair')) {
            return redirect()->route('trocar_senha');
        }

        return $next($request);
    }
}
