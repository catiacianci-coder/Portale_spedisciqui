<?php

namespace App\Http\Controllers;

use App\Services\Liccardi\LiccardiTmsWebhookHandler;
use App\Services\Liccardi\LiccardiTmsWebhookRejected;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LiccardiTmsWebhookController extends Controller
{
    public function __invoke(Request $request, LiccardiTmsWebhookHandler $handler): Response
    {
        if ($request->isMethod('GET')) {
            return response()->json([
                'status' => 'ok',
                'webhook' => 'liccardi-tms',
                'method' => 'POST',
            ], 200);
        }

        if (! $request->isMethod('POST')) {
            return response('Method Not Allowed', 405);
        }

        try {
            $handler->handle($request);
        } catch (LiccardiTmsWebhookRejected $e) {
            return response($e->getMessage(), $e->httpStatus);
        }

        return response('OK', 200);
    }
}
