<?php

namespace Tests\Unit;

use App\Support\LiccardiVolumeSconto;
use PHPUnit\Framework\TestCase;

class LiccardiVolumeScontoTest extends TestCase
{
    public function test_trasporto_scontato_non_va_sotto_zero(): void
    {
        $this->assertSame(7.0, LiccardiVolumeSconto::trasportoScontato(10.0));
        $this->assertSame(0.0, LiccardiVolumeSconto::trasportoScontato(2.5));
    }

    public function test_sconto_applicabile_solo_da_dieci_righe(): void
    {
        $this->assertFalse(LiccardiVolumeSconto::scontoApplicabile(9));
        $this->assertTrue(LiccardiVolumeSconto::scontoApplicabile(10));
        $this->assertTrue(LiccardiVolumeSconto::scontoApplicabile(15));
    }

    public function test_trasporto_pieno_ripristina_da_originale_o_sconto(): void
    {
        $this->assertSame(15.40, LiccardiVolumeSconto::trasportoPieno([
            'trasporto_base_iva_esc' => 12.40,
            'trasporto_base_iva_esc_originale' => 15.40,
        ]));
        $this->assertSame(15.40, LiccardiVolumeSconto::trasportoPieno([
            'trasporto_base_iva_esc' => 12.40,
            'liccardi_volume_sconto_eur' => 3.0,
        ]));
        $this->assertSame(15.40, LiccardiVolumeSconto::trasportoPieno([
            'trasporto_iva_esc' => 12.40,
            'trasporto_iva_esc_originale' => 15.40,
        ]));
    }
}
