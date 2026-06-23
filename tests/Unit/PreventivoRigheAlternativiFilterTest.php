<?php

namespace Tests\Unit;

use App\Support\PreventivoRigheAlternativiFilter;
use PHPUnit\Framework\TestCase;

class PreventivoRigheAlternativiFilterTest extends TestCase
{
    public function test_gls_prezzi_uguali_mostra_solo_standard(): void
    {
        $spedisci = [
            5 => ['quote' => ['price_amount' => 8.77]],
            13 => ['quote' => ['price_amount' => 8.77]],
        ];

        $nascosti = PreventivoRigheAlternativiFilter::corriereIdsDaNascondere([], $spedisci);

        $this->assertSame([13], $nascosti);
    }

    public function test_gls_light_piu_economico_nasconde_standard(): void
    {
        $spedisci = [
            5 => ['quote' => ['price_amount' => 8.77]],
            13 => ['quote' => ['price_amount' => 4.63]],
        ];

        $nascosti = PreventivoRigheAlternativiFilter::corriereIdsDaNascondere([], $spedisci);

        $this->assertSame([5], $nascosti);
    }

    public function test_inpost_mostra_solo_il_minimo(): void
    {
        $sendcloud = [
            11 => ['quote' => ['price_amount' => 5.50]],
            12 => ['quote' => ['price_amount' => 6.20]],
        ];

        $nascosti = PreventivoRigheAlternativiFilter::corriereIdsDaNascondere($sendcloud, []);

        $this->assertSame([12], $nascosti);
    }
}
