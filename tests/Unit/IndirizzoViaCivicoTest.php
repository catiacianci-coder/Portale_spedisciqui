<?php

namespace Tests\Unit;

use App\Support\IndirizzoViaCivico;
use PHPUnit\Framework\TestCase;

class IndirizzoViaCivicoTest extends TestCase
{
    public function test_locker_inpost_rimuove_civico_e_suffix_apt(): void
    {
        [$via, $civico] = IndirizzoViaCivico::perSendcloud(
            'Viale Umberto Maddalena 53 apt. 0',
            '53',
        );

        $this->assertSame('Viale Umberto Maddalena', $via);
        $this->assertSame('53', $civico);
    }

    public function test_via_esplicita_con_civico_separato(): void
    {
        [$via, $civico] = IndirizzoViaCivico::perSendcloud(
            'Via Roma 12',
            '12',
            'Via Roma',
        );

        $this->assertSame('Via Roma', $via);
        $this->assertSame('12', $civico);
    }

    public function test_colonna_database_preferisce_via(): void
    {
        $this->assertSame(
            'Via Roma',
            IndirizzoViaCivico::perColonnaDatabase('Via Roma', '12', 'Via Roma 12'),
        );
    }
}
