<?php

namespace App\Services\Liccardi;

final class LiccardiTmsWebhookRejected extends \Exception
{
    public function __construct(string $message, public readonly int $httpStatus = 400)
    {
        parent::__construct($message);
    }
}
