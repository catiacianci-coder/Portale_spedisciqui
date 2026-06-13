<?php

namespace App\Services\SpedisciOnline;

final class SpedisciOnlineLabelResult
{
    /**
     * @param  array<string, mixed>|null  $responseBody
     */
    public function __construct(
        public readonly bool $ok,
        public readonly string $message,
        public readonly ?int $httpStatus = null,
        public readonly ?string $tracking = null,
        public readonly ?array $responseBody = null,
    ) {}
}
