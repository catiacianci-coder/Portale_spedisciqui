<?php

namespace App\Http\Controllers;

use App\Services\Stripe\StripeWebhookHandler;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Stripe\Exception\SignatureVerificationException;

class StripeWebhookController extends Controller
{
    public function __invoke(Request $request, StripeWebhookHandler $handler): Response
    {
        try {
            $handler->handleRawPayload(
                $request->getContent(),
                $request->header('Stripe-Signature'),
            );
        } catch (SignatureVerificationException|\UnexpectedValueException) {
            return response('Invalid signature', 400);
        } catch (\RuntimeException $e) {
            return response($e->getMessage(), 503);
        }

        return response('OK', 200);
    }
}
