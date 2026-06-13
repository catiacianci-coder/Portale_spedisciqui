<?php

namespace Tests\Feature;

use App\Services\ParametriApiConfig;
use App\Support\ParametriApi;
use Tests\TestCase;

class LiccardiTmsWebhookTest extends TestCase
{
    private const URL = '/webhook/liccardi-tms';

    protected function setUp(): void
    {
        parent::setUp();

        ParametriApiConfig::setOverride(ParametriApi::LICCARDI_TMS_WEBHOOK_TOKEN, 'test-webhook-token-segreto');
        ParametriApiConfig::setOverride(ParametriApi::LICCARDI_TMS_WEBHOOK_HEADER, 'X-Liccardi-Webhook-Token');
        ParametriApiConfig::setOverride(ParametriApi::LICCARDI_TMS_COMPANY_ID, 'K91ADVSRL');
    }

    protected function tearDown(): void
    {
        ParametriApiConfig::clearOverrides();
        parent::tearDown();
    }

    public function test_get_endpoint_is_reachable(): void
    {
        $this->get(self::URL)
            ->assertOk()
            ->assertJson([
                'status' => 'ok',
                'webhook' => 'liccardi-tms',
            ]);
    }

    public function test_rejects_missing_token(): void
    {
        $this->postJson(self::URL, ['courierLdv' => '660000001201'])
            ->assertUnauthorized();
    }

    public function test_rejects_invalid_json_body(): void
    {
        $this->call(
            'POST',
            self::URL,
            [],
            [],
            [],
            [
                'HTTP_X-Liccardi-Webhook-Token' => 'test-webhook-token-segreto',
                'CONTENT_TYPE' => 'application/json',
            ],
            'not-json'
        )->assertStatus(400);
    }

    public function test_rejects_missing_identifiers(): void
    {
        $this->postJson(self::URL, ['customerCode' => 'K91ADVSRL'], [
            'X-Liccardi-Webhook-Token' => 'test-webhook-token-segreto',
        ])->assertStatus(400);
    }

    public function test_returns_service_unavailable_without_configured_token(): void
    {
        ParametriApiConfig::setOverride(ParametriApi::LICCARDI_TMS_WEBHOOK_TOKEN, '');

        $this->postJson(self::URL, ['courierLdv' => '660000001201'], [
            'X-Liccardi-Webhook-Token' => 'qualsiasi',
        ])->assertStatus(503);
    }
}
