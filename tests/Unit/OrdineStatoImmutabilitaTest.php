<?php

namespace Tests\Unit;

use App\Models\spedizione;
use App\Models\stato_spedizione;
use App\Support\SpedisciOnlineIntegrazione;
use PHPUnit\Framework\TestCase;

class OrdineStatoImmutabilitaTest extends TestCase
{
    public function test_attributi_annullamento_non_toccano_cr(): void
    {
        $source = file_get_contents(app_path('Support/OrdineDatiPagamento.php'));

        $this->assertIsString($source);
        $this->assertStringContainsString('attributiAnnullamento', $source);
        $this->assertStringContainsString('stato_ordine_id', $source);
        $this->assertStringContainsString('annullato_in', $source);
        $this->assertStringNotContainsString("'cr'", $source);
    }

    public function test_etichetta_cancellata_usa_stato_spedizione_non_stato_ordine(): void
    {
        $source = file_get_contents(app_path('Support/SpedisciOnlineIntegrazione.php'));

        $this->assertStringContainsString('stato_spedizione::ANNULLATA', $source);
        $this->assertStringContainsString('stato_spedizione::RIMBORSATA', $source);
        $this->assertStringNotContainsString('STATO_ANNULLATO', $source);

        $spedizione = new spedizione([
            'ldv_emessa_il' => new \DateTimeImmutable,
            'esiste_integrazione' => false,
            'spedizione_stato_id' => stato_spedizione::RIMBORSATA,
        ]);

        $this->assertTrue(SpedisciOnlineIntegrazione::etichettaCancellata($spedizione));

        $spedizione->spedizione_stato_id = stato_spedizione::PAGATA;
        $this->assertFalse(SpedisciOnlineIntegrazione::etichettaCancellata($spedizione));
    }
}
