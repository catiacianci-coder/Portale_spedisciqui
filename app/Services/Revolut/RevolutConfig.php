<?php

namespace App\Services\Revolut;

use App\Services\ParametriApiConfig;

final class RevolutConfig
{
    public static function isConfigured(): bool
    {
        return self::accessToken() !== ''
            && self::accountId() !== ''
            && ! str_contains(self::accessToken(), 'replace_me');
    }

    public static function accessToken(): string
    {
        return ParametriApiConfig::revolutAccessToken();
    }

    public static function accountId(): string
    {
        return ParametriApiConfig::revolutAccountId();
    }

    public static function apiBase(): string
    {
        return ParametriApiConfig::revolutApiBase();
    }

    public static function timeout(): int
    {
        return ParametriApiConfig::revolutTimeout();
    }
}
