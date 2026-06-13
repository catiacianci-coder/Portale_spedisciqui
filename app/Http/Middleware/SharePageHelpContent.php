<?php

namespace App\Http\Middleware;

use App\Models\PageHelpContent;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class SharePageHelpContent
{
    public function handle(Request $request, Closure $next): Response
    {
        $pageKey = PageHelpContent::pageKeyForRoute($request->route()?->getName());
        $pageHelp = $pageKey !== null
            ? PageHelpContent::forPublicPage($pageKey)
            : null;

        View::share('pageHelp', $pageHelp);

        return $next($request);
    }
}
