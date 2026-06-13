<?php

namespace Tests\Unit;

use App\Models\corriere;
use App\Models\spedizione;
use App\Models\spedizione_servizio_aggiuntivi;
use App\Services\Liccardi\LiccardiTmsSpedizioneMapper;
use Illuminate\Support\Collection;
use Tests\TestCase;

class LiccardiTmsSpedizioneMapperTest extends TestCase
{
    public function test_mappa_servizi_assicurazione_e_contrassegno(): void
    {
        $corriere = new corriere([
            'codice_servizio' => 'E',
            'piattaforma' => 'liccardi_tms',
            'tariffa_interna' => false,
        ]);
        $corriere->id = 7;

        $spedizione = new spedizione([
            'nome_o' => 'Mario',
            'cognome_o' => 'Rossi',
            'cap_o' => '00187',
            'citta_o' => 'Roma',
            'stato_o' => 'RM',
            'indirizzo_o' => 'Via Condotti',
            'numero_o' => '5',
            'nome_d' => 'Luigi',
            'sobrenome_d' => 'Verdi',
            'cap_d' => '20121',
            'citta_d' => 'Milano',
            'stato_d' => 'MI',
            'indirizzo_d' => 'Corso Venezia',
            'numero_d' => '1',
            'peso' => 6.1,
            'altezza' => 30,
            'larghezza' => 25,
            'spessore' => 40,
            'codice_interno' => 'SPQ123',
        ]);
        $spedizione->id = 99;
        $spedizione->setRelation('serviziAggiuntiviRighe', new Collection([
            new spedizione_servizio_aggiuntivi([
                'testo_servizio' => 'assicurazione',
                'valore_merce' => 500.0,
            ]),
            new spedizione_servizio_aggiuntivi([
                'testo_servizio' => 'contrassegno',
                'valore_merce' => 100.0,
            ]),
        ]));

        $input = app(LiccardiTmsSpedizioneMapper::class)->buildInput($spedizione, $corriere);

        $this->assertSame('E', $input['codice_servizio']);
        $this->assertSame(500.0, $input['assicurazione']);
        $this->assertSame(100.0, $input['contrassegno']);
        $this->assertSame('SPQ123', $input['riferimento_cliente']);
        $this->assertSame('Via Condotti', $input['via_origine']);
        $this->assertSame('5', $input['civico_origine']);
    }
}
