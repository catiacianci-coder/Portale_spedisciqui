<?php

namespace App\Http\Middleware;

use App\Support\CarrelloUtente;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IdrataCarrelloSessione
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()) {
            CarrelloUtente::idrataSessione($request);
        }

        return $next($request);
    }
}
