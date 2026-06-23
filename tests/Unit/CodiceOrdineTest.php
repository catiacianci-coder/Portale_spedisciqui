<?php

namespace Tests\Unit;

use App\Support\CodiceOrdine;
use PHPUnit\Framework\TestCase;

class CodiceOrdineTest extends TestCase
{
    public function test_format_returns_numeric_id(): void
    {
        $this->assertSame('27', CodiceOrdine::format(27));
    }

    public function test_id_da_riferimento_accetta_solo_cifre(): void
    {
        $this->assertSame(27, CodiceOrdine::idDaRiferimento('27'));
        $this->assertNull(CodiceOrdine::idDaRiferimento(''));
        $this->assertNull(CodiceOrdine::idDaRiferimento('abc'));
    }

    public function test_id_da_riferimento_accetta_prefisso_o_legacy(): void
    {
        $this->assertSame(27, CodiceOrdine::idDaRiferimento('O27'));
        $this->assertSame(27, CodiceOrdine::idDaRiferimento('o27'));
    }
}
