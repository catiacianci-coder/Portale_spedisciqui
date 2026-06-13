<?php

namespace App\Services\Sendcloud;

final class SendcloudLabelResult
{
    /**
     * @param  array<string, mixed>|null  $responseBody
     */
    public function __construct(
        public readonly bool $ok,
        public readonly string $message,
        public readonly ?int $httpStatus = null,
        public readonly ?string $shipmentId = null,
        public readonly ?string $tracking = null,
        public readonly ?array $responseBody = null,
    ) {}
}
