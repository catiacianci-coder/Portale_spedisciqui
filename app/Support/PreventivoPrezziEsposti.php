<?php

namespace App\Support;

/**
 * Prezzi mostrati in preventivi (una voce per colonna metodo pagamento).
 */
final class PreventivoPrezziEsposti
{
    /**
     * @return list<array{key: string, titolo: string, commissioni_pct: float, prezzo: float}>
     */
    public static function colonneDaPrezzoTrasporto(float $prezzoTrasporto): array
    {
        $out = [];
        foreach (PreventivoColonnePagamento::colonneAttive() as $col) {
            $out[] = [
                'key' => (string) ($col['key'] ?? ''),
                'titolo' => (string) ($col['titolo'] ?? ''),
                'commissioni_pct' => (float) ($col['commissioni_pct'] ?? 0),
                'prezzo' => PreventivoColonnePagamento::prezzoPerColonna(
                    $prezzoTrasporto,
                    (float) ($col['commissioni_pct'] ?? 0),
                ),
            ];
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $preventivo
     */
    public static function salvaInPreventivo(array &$preventivo, int $corriereId, float $prezzoTrasporto): void
    {
        $preventivo['prezzi_esposti'] = [
            'corriere_id' => $corriereId,
            'prezzo_trasporto_base' => round($prezzoTrasporto, 2),
            'colonne' => self::colonneDaPrezzoTrasporto($prezzoTrasporto),
            'aggiornato_il' => now()->toIso8601String(),
        ];
    }

    /**
     * @param  array<string, mixed>  $preventivo
     */
    public static function aggiornaDaRiga(array &$preventivo, int $corriereId): void
    {
        $riga = PreventivoRigaSelezionabile::trovaRiga($preventivo, $corriereId);
        if (! $riga) {
            return;
        }

        $prezzoTrasporto = (float) ($riga['prezzo_finale'] ?? 0);
        self::salvaInPreventivo($preventivo, $corriereId, $prezzoTrasporto);
    }
}
