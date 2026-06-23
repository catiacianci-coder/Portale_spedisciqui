<?php

namespace Tests\Unit;

use App\Models\ordine;
use App\Models\User;
use App\Policies\OrdinePolicy;
use Mockery;
use PHPUnit\Framework\TestCase;

class OrdinePolicyCancelTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_cancel_consentito_solo_per_ordine_non_pagato(): void
    {
        $user = new User(['id' => 10]);
        $policy = new OrdinePolicy;

        $this->assertTrue($policy->cancel($user, $this->mockOrdine(10, ordine::STATO_NON_PAGATO)));
        $this->assertFalse($policy->cancel($user, $this->mockOrdine(10, ordine::STATO_PAGATO)));
        $this->assertFalse($policy->cancel($user, $this->mockOrdine(10, ordine::STATO_ANNULLATO)));
    }

    public function test_cancel_negato_per_ordine_di_altro_utente(): void
    {
        $policy = new OrdinePolicy;

        $this->assertFalse($policy->cancel(new User(['id' => 10]), $this->mockOrdine(99, ordine::STATO_NON_PAGATO)));
    }

    private function mockOrdine(int $userId, string $stato): ordine
    {
        $ordine = Mockery::mock(ordine::class)->makePartial();
        $ordine->user_id = $userId;
        $ordine->shouldReceive('getAttribute')->with('stato')->andReturn($stato);

        return $ordine;
    }
}
