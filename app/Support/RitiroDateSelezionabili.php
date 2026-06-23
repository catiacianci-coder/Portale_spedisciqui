<?php

namespace App\Support;

use App\Models\parametri_globali;
use Illuminate\Support\Carbon;

final class RitiroDateSelezionabili
{
    public static function giorniFinestra(): int
    {
        return max(1, parametri_globali::giorniRitiro());
    }

    /**
     * @return list<string> Date Y-m-d (solo lun–ven), fino a {@see giorniFinestra()} giorni lavorativi
     *                                                                 dal giorno successivo a {@see $partenza}.
     */
    public static function dateDa(Carbon $partenza): array
    {
        $partenza = $partenza->copy()->startOfDay();
        $target = self::giorniFinestra();
        $out = [];
        $cursor = $partenza->copy()->addDay();

        while (count($out) < $target) {
            if (! self::isWeekend($cursor)) {
                $out[] = $cursor->toDateString();
            }
            $cursor->addDay();
        }

        return $out;
    }

    public static function isValida(string $data, ?Carbon $partenza = null): bool
    {
        $data = trim($data);
        if ($data === '') {
            return false;
        }

        try {
            $target = Carbon::parse($data)->startOfDay();
        } catch (\Throwable) {
            return false;
        }

        if (self::isWeekend($target)) {
            return false;
        }

        $partenza = ($partenza ?? now())->copy()->startOfDay();

        return in_array($target->toDateString(), self::dateDa($partenza), true);
    }

    public static function messaggioErrore(): string
    {
        $n = self::giorniFinestra();

        return "Seleziona una data ritiro tra i prossimi {$n} giorni lavorativi (lun–ven, dal giorno successivo).";
    }

    /** Primo giorno selezionabile (Y-m-d): giorno lavorativo successivo a {@see $partenza}. */
    public static function primoGiornoValido(?Carbon $partenza = null): string
    {
        $date = self::dateDa($partenza ?? now());

        return $date[0] ?? self::prossimoGiornoLavorativo($partenza ?? now())->toDateString();
    }

    /**
     * Data da usare al pagamento: mantiene la scelta se ancora valida,
     * altrimenti il primo giorno utile a partire dal pagamento.
     */
    public static function dataEffettivaAlPagamento(?string $dataSelezionata, ?Carbon $pagamento = null): string
    {
        $dataSelezionata = trim((string) $dataSelezionata);
        $pagamento = ($pagamento ?? now())->copy()->startOfDay();

        if ($dataSelezionata !== '' && self::isValida($dataSelezionata, $pagamento)) {
            return $dataSelezionata;
        }

        return self::primoGiornoValido($pagamento);
    }

    private static function isWeekend(Carbon $date): bool
    {
        return $date->isWeekend();
    }

    private static function prossimoGiornoLavorativo(Carbon $date): Carbon
    {
        $cursor = $date->copy()->startOfDay()->addDay();
        while (self::isWeekend($cursor)) {
            $cursor->addDay();
        }

        return $cursor;
    }
}
