<?php

namespace App\Services\Liccardi;

final class LiccardiTmsLabelResult
{
    /**
     * @param  array<string, mixed>|null  $responseBody
     */
    public function __construct(
        public readonly bool $ok,
        public readonly string $message,
        public readonly ?int $httpStatus = null,
        public readonly ?int $spedizioneId = null,
        public readonly ?string $tracking = null,
        public readonly ?array $responseBody = null,
    ) {}
}
