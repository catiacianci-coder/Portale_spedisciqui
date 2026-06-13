<?php

namespace Tests\Unit;

use App\Services\Sendcloud\SendcloudShippingOptionsService;
use PHPUnit\Framework\TestCase;

class SendcloudAssicurazionePayloadTest extends TestCase
{
    public function test_shipping_options_usa_importo_numerico(): void
    {
        $service = new SendcloudShippingOptionsService(new \App\Services\Sendcloud\SendcloudClient);
        $payload = $service->buildNationalPayload([
            'cap_origine' => '81100',
            'cap_destino' => '80144',
            'valore_assicurazione' => 500,
        ]);

        $insured = $payload['parcels'][0]['additional_insured_price'] ?? null;
        $this->assertIsFloat($insured);
        $this->assertSame(500.0, $insured);
    }

    public function test_shipments_usa_oggetto_prezzo(): void
    {
        $price = SendcloudShippingOptionsService::additionalInsuredPriceForShipment(500);
        $this->assertIsArray($price);
        $this->assertSame('500.00', $price['value']);
        $this->assertSame('EUR', $price['currency']);
    }
}
