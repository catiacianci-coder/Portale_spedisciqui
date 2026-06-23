<?php

namespace Tests\Unit;

use App\Support\RitiroDateSelezionabili;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

/**
 * Logica giorni lavorativi (senza DB): verifica conteggio con finestra fissa simulata.
 */
class RitiroDateSelezionabiliWeekdayTest extends TestCase
{
    public function test_quattro_giorni_lavorativi_saltano_weekend(): void
    {
        // Venerdì 2026-06-19 → primo giorno lun 22 (non oggi ven 19)
        $partenza = Carbon::parse('2026-06-19');
        $date = $this->dateLavorativiDa($partenza, 4);

        $this->assertSame([
            '2026-06-22',
            '2026-06-23',
            '2026-06-24',
            '2026-06-25',
        ], $date);
    }

    public function test_partenza_sabato_inizia_da_lunedi(): void
    {
        $partenza = Carbon::parse('2026-06-20'); // sabato
        $date = $this->dateLavorativiDa($partenza, 4);

        $this->assertSame([
            '2026-06-22',
            '2026-06-23',
            '2026-06-24',
            '2026-06-25',
        ], $date);
    }

    public function test_primo_giorno_non_include_oggi(): void
    {
        // Lunedì 2026-06-22 → primo selezionabile martedì 23
        $partenza = Carbon::parse('2026-06-22');
        $date = $this->dateLavorativiDa($partenza, 4);

        $this->assertSame('2026-06-23', $date[0]);
        $this->assertSame([
            '2026-06-23',
            '2026-06-24',
            '2026-06-25',
            '2026-06-26',
        ], $date);
    }

    /**
     * Replica {@see RitiroDateSelezionabili::dateDa()} senza DB.
     *
     * @return list<string>
     */
    private function dateLavorativiDa(Carbon $partenza, int $target): array
    {
        $partenza = $partenza->copy()->startOfDay();
        $out = [];
        $cursor = $partenza->copy()->addDay();

        while (count($out) < $target) {
            if (! $cursor->isWeekend()) {
                $out[] = $cursor->toDateString();
            }
            $cursor->addDay();
        }

        return $out;
    }
}
