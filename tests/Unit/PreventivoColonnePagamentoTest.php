<?php

namespace Tests\Unit;

use App\Support\PreventivoColonnePagamento;
use PHPUnit\Framework\TestCase;

class PreventivoColonnePagamentoTest extends TestCase
{
    public function test_prezzo_per_colonna_applica_commissione(): void
    {
        $this->assertSame(98.0, PreventivoColonnePagamento::prezzoPerColonna(100.0, -2.0));
        $this->assertSame(100.0, PreventivoColonnePagamento::prezzoPerColonna(100.0, 0.0));
    }
}
