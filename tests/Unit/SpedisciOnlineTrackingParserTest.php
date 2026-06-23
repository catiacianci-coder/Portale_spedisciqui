<?php

namespace Tests\Unit;

use App\Support\SpedisciOnlineTrackingParser;
use App\Support\TrackingEventoVerifica;
use PHPUnit\Framework\TestCase;

class SpedisciOnlineTrackingParserTest extends TestCase
{
    public function test_tracking_dettaglio_documentato(): void
    {
        $ultimo = SpedisciOnlineTrackingParser::ultimoEvento([
            'TrackingDettaglio' => [
                [
                    'Data' => '22/09/2016 15:22',
                    'Stato' => 'CONSEGNATA',
                    'Luogo' => 'Cembra',
                ],
                [
                    'Data' => '19/09/2016 17:42',
                    'Stato' => 'Spedizione generata. In attesa di ritiro.',
                    'Luogo' => 'MACERATA CAMPANIA',
                ],
            ],
        ]);

        $this->assertSame('CONSEGNATA', SpedisciOnlineTrackingParser::etichettaCliente($ultimo['stato'], $ultimo['luogo']));
        $this->assertNotNull($ultimo['evento_at']);
    }

    public function test_ignora_status_numerico_root(): void
    {
        $ultimo = SpedisciOnlineTrackingParser::ultimoEvento([
            'status' => 0,
            'TrackingDettaglio' => [
                [
                    'Data' => '20/06/2026 10:00',
                    'Stato' => 'Partita dalla sede mittente',
                    'Luogo' => 'Milano',
                ],
            ],
        ]);

        $this->assertSame('Partita dalla sede mittente', SpedisciOnlineTrackingParser::etichettaCliente($ultimo['stato'], $ultimo['luogo']));
    }

    public function test_eventi_per_rimborso_da_tracking_dettaglio(): void
    {
        $eventi = TrackingEventoVerifica::eventiDaResponseSpedisciOnline([
            'TrackingDettaglio' => [
                [
                    'Data' => '20/06/2026 10:00',
                    'Stato' => 'In transito',
                    'Luogo' => 'Roma',
                ],
            ],
        ]);

        $this->assertSame([
            ['status' => 'In transito', 'data' => '20/06/2026 10:00'],
        ], $eventi);
    }

    public function test_formato_generico_event_description(): void
    {
        $ultimo = SpedisciOnlineTrackingParser::ultimoEvento([
            'trackingEvents' => [
                [
                    'eventDescription' => 'Preso in carico',
                    'eventDateTime' => '2026-06-20 10:00:00',
                ],
            ],
        ]);

        $this->assertSame('Preso in carico', $ultimo['stato']);
    }
}
