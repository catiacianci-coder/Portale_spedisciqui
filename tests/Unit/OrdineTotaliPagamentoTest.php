<?php

namespace Tests\Unit;

use App\Models\ordine;
use App\Support\OrdineTotaliPagamento;
use PHPUnit\Framework\TestCase;

class OrdineTotaliPagamentoTest extends TestCase
{
    public function test_breakdown_salvato_usa_totali_salvati_su_ordine(): void
    {
        $ordine = new ordine([
            'total_pagamento' => 122.0,
            'total_pagamento_wallet' => 119.56,
            'dettaglio_json' => [
                'righe' => [
                    [
                        'netto_iva_esc' => 100.0,
                        'netto_wallet_iva_esc' => 98.0,
                    ],
                ],
            ],
        ]);

        $standard = OrdineTotaliPagamento::breakdownSalvato($ordine, false);
        $this->assertSame(122.0, $standard['totale']);
        $this->assertSame(100.0, $standard['imponibile']);
        $this->assertSame(22.0, $standard['iva']);

        $wallet = OrdineTotaliPagamento::breakdownSalvato($ordine, true);
        $this->assertSame(119.56, $wallet['totale']);
        $this->assertSame(98.0, $wallet['imponibile']);
        $this->assertSame(21.56, $wallet['iva']);
    }
}
