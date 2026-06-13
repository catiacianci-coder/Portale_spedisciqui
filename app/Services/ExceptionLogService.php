<?php

namespace App\Services;

use App\Models\log_errore_applicativo;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class ExceptionLogService
{
    public function log(Throwable $e, ?Request $request = null): void
    {
        if (! $this->deveRegistrare($e)) {
            return;
        }

        $request = $request ?? request();
        if ($request === null) {
            return;
        }

        if ($request->routeIs('backoffice.errori.*')) {
            return;
        }

        $status = $this->httpStatus($e);
        if ($status < 500) {
            return;
        }

        $messaggio = $e->getMessage();
        if ($messaggio === '') {
            $messaggio = class_basename($e);
        }

        log_errore_applicativo::query()->create([
            'user_id' => $request->user()?->id,
            'http_status' => $status,
            'exception_class' => $e::class,
            'messaggio' => mb_substr($messaggio, 0, 65000),
            'url' => mb_substr($request->fullUrl(), 0, 2048),
            'metodo' => $request->method(),
            'ip' => $request->ip(),
            'trace' => mb_substr($e->getTraceAsString(), 0, 65000),
            'created_at' => now(),
        ]);
    }

    private function deveRegistrare(Throwable $e): bool
    {
        if ($e instanceof ValidationException
            || $e instanceof AuthenticationException
            || $e instanceof TokenMismatchException) {
            return false;
        }

        return true;
    }

    private function httpStatus(Throwable $e): int
    {
        if ($e instanceof HttpException) {
            return $e->getStatusCode();
        }

        return 500;
    }
}
