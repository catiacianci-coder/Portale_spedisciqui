<?php

namespace Tests\Unit;

use App\Support\LiccardiPremiumPricing;
use PHPUnit\Framework\TestCase;

class LiccardiPremiumPricingTest extends TestCase
{
    public function test_costo_trasporto_base_sottrae_tre_e_dividi_per_due(): void
    {
        $this->assertSame(8.5, LiccardiPremiumPricing::costoTrasportoBase(20.0));
        $this->assertSame(0.0, LiccardiPremiumPricing::costoTrasportoBase(2.0));
    }
}
