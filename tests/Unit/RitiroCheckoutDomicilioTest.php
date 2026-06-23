<?php

namespace Tests\Unit;

use App\Models\corriere;
use App\Support\PiattaformaCorriere;
use App\Support\RitiroCheckoutDomicilio;
use App\Support\SpedisciOnlineEamultiContratti;
use Tests\TestCase;

class RitiroCheckoutDomicilioTest extends TestCase
{
    public function test_sda_richiede_data_ritiro_anche_senza_colonna_pickup(): void
    {
        $c = new corriere(['pickup' => null]);
        $c->id = SpedisciOnlineEamultiContratti::CORRIERE_SDA_M;

        $this->assertTrue(RitiroCheckoutDomicilio::corriereRichiedeDataRitiro($c));
    }

    public function test_sendcloud_poste_domicilio_richiede_data_ritiro(): void
    {
        $c = new corriere([
            'id' => 99,
            'piattaforma' => PiattaformaCorriere::SENDCLOUD,
            'tariffa_interna' => false,
            'pickup' => 'Domicilio',
            'carrier_code' => 'poste_it_delivery',
            'codice_servizio' => 'poste_it_delivery:standard/kg=0-2',
        ]);

        $this->assertTrue(RitiroCheckoutDomicilio::ritiroADomicilio($c));
        $this->assertTrue(RitiroCheckoutDomicilio::corriereRichiedeDataRitiro($c));
    }

    public function test_sendcloud_inpost_indirizzo_non_richiede_data_ritiro(): void
    {
        $c = new corriere([
            'id' => 11,
            'piattaforma' => PiattaformaCorriere::SENDCLOUD,
            'tariffa_interna' => false,
            'pickup' => 'Locker o InPost Point',
            'carrier_code' => 'inpost_it',
            'codice_servizio' => 'inpost_it:addresstolocker/pickup,size=m',
        ]);

        $this->assertFalse(RitiroCheckoutDomicilio::ritiroADomicilio($c));
        $this->assertFalse(RitiroCheckoutDomicilio::corriereRichiedeDataRitiro($c));
    }

    public function test_sendcloud_punto_poste_non_richiede_data_ritiro(): void
    {
        $c = new corriere([
            'id' => 10,
            'piattaforma' => PiattaformaCorriere::SENDCLOUD,
            'tariffa_interna' => false,
            'pickup' => 'Punto Poste',
            'codice_servizio' => 'poste_it_delivery:standard_puntoposte/kg=0-2',
        ]);

        $this->assertFalse(RitiroCheckoutDomicilio::corriereRichiedeDataRitiro($c));
    }
}
