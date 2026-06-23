<?php

namespace App\Services\Sendcloud;

final class SendcloudPickupResult
{
    /**
     * @param  array<string, mixed>|null  $payload
     * @param  array<string, mixed>|null  $responseBody
     */
    public function __construct(
        public readonly bool $ok,
        public readonly string $message,
        public readonly ?int $httpStatus = null,
        public readonly ?string $pickupId = null,
        public readonly ?array $payload = null,
        public readonly ?array $responseBody = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toCheckoutTrace(): array
    {
        return [
            'ok' => $this->ok,
            'message' => $this->message,
            'http_status' => $this->httpStatus,
            'pickup_id' => $this->pickupId,
            'payload' => $this->payload,
            'response' => $this->responseBody,
            'provider' => 'sendcloud',
            'endpoint' => 'POST /pickups',
        ];
    }
}
