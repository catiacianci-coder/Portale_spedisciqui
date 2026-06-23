<?php

use App\Http\Middleware\AppendCsrfTokenHeader;
use App\Http\Middleware\EnsureBackofficeUser;
use App\Http\Middleware\IdrataCarrelloSessione;
use App\Http\Middleware\SharePageHelpContent;
use App\Services\ExceptionLogService;
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
        $middleware->alias([
            'backoffice' => EnsureBackofficeUser::class,
        ]);
        $middleware->web(append: [
            IdrataCarrelloSessione::class,
            SharePageHelpContent::class,
            AppendCsrfTokenHeader::class,
        ]);
        $middleware->validateCsrfTokens(except: [
            'stripe/webhook',
            'webhook/liccardi-tms',
        ]);
        $middleware->redirectTo(
            guests: '/login',
            users: function (Request $request) {
                // Se l'utente è verificato, mandalo alla Home (o dove voleva andare)
                if ($request->user()?->hasVerifiedEmail()) {
                    return session('url.intended', '/');
                }

                // Se deve ancora verificare, lascialo sulla pagina di avviso
                return route('verification.notice');
            }
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->report(function (Throwable $e): void {
            try {
                app(ExceptionLogService::class)->log($e);
            } catch (Throwable) {
                // Non bloccare la gestione dell'errore se il log fallisce.
            }
        });
    })->create();