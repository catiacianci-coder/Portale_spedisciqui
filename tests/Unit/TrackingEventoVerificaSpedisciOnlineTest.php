<?php

namespace Tests\Unit;

use App\Support\TrackingEventoVerifica;
use PHPUnit\Framework\TestCase;

class TrackingEventoVerificaSpedisciOnlineTest extends TestCase
{
    public function test_eventi_da_lista_tracking_events(): void
    {
        $eventi = TrackingEventoVerifica::eventiDaResponseSpedisciOnline([
            'trackingEvents' => [
                [
                    'eventDescription' => 'In transito',
                    'eventDateTime' => '2026-06-20 10:00:00',
                ],
                [
                    'eventDescription' => 'Consegnato',
                    'eventDateTime' => '2026-06-21 15:30:00',
                ],
            ],
        ]);

        $this->assertCount(2, $eventi);
        $this->assertSame('In transito', $eventi[0]['status']);
        $this->assertSame('Consegnato', $eventi[1]['status']);
    }

    public function test_eventi_da_stato_singolo(): void
    {
        $eventi = TrackingEventoVerifica::eventiDaResponseSpedisciOnline([
            'status' => 'Preso in carico',
            'eventDateTime' => '2026-06-19 09:00:00',
        ]);

        $this->assertSame([
            ['status' => 'Preso in carico', 'data' => '2026-06-19 09:00:00'],
        ], $eventi);
    }
}
