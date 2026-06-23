<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Espone il token CSRF corrente nell'header di risposta (es. axios in registrazione).
 */
class AppendCsrfTokenHeader
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->hasSession()) {
            $response->headers->set('X-Csrf-Token', csrf_token());
        }

        return $response;
    }
}
