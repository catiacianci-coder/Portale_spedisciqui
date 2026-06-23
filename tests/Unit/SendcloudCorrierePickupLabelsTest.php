<?php

namespace Tests\Unit;

use App\Support\SendcloudCorrierePickupLabels;
use Tests\TestCase;

class SendcloudCorrierePickupLabelsTest extends TestCase
{
    public function test_poste_domicilio_express(): void
    {
        $labels = SendcloudCorrierePickupLabels::fromCorriere(
            'poste_it_delivery:express/kg=0-2',
            'poste_it_delivery',
        );

        $this->assertSame('Domicilio', $labels['pickup']);
        $this->assertSame('Domicilio', $labels['consegna']);
    }

    public function test_poste_domicilio_ufficio_postale(): void
    {
        $labels = SendcloudCorrierePickupLabels::fromCorriere(
            'poste_it_delivery:standard_postoffice/kg=0-2',
            'poste_it_delivery',
        );

        $this->assertSame('Domicilio', $labels['pickup']);
        $this->assertSame('Ufficio Postale', $labels['consegna']);
    }

    public function test_poste_shop2shop_dropoff(): void
    {
        $labels = SendcloudCorrierePickupLabels::fromCorriere(
            'poste_it_delivery:shop2shop_puntotopunto/kg=0-2,dropoff,service_point',
            'poste_it_delivery',
        );

        $this->assertSame('Punto Poste', $labels['pickup']);
        $this->assertSame('Punto Poste', $labels['consegna']);
    }

    public function test_inpost_address_to_locker(): void
    {
        $labels = SendcloudCorrierePickupLabels::fromCorriere(
            'inpost_it:addresstolocker/pickup,size=m',
            'inpost_it',
        );

        $this->assertSame('Locker o InPost Point', $labels['pickup']);
        $this->assertSame('Locker o InPost Point', $labels['consegna']);
    }

    public function test_inpost_locker_to_locker(): void
    {
        $labels = SendcloudCorrierePickupLabels::fromCorriere(
            'inpost_it:lockertolocker/kg=0-25,dropoff,size=m',
            'inpost_it',
        );

        $this->assertSame('Locker o InPost Point', $labels['pickup']);
        $this->assertSame('Locker o InPost Point', $labels['consegna']);
    }
}
