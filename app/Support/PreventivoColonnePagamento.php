<?php

namespace App\Support;

use App\Models\metodo_pagamento_ordine;

/**
 * Colonne prezzo in pagina preventivi: raggruppate per commissione metodo pagamento ordine.
 */
final class PreventivoColonnePagamento
{
    /**
     * @return array<int, array{
     *     key: string,
     *     titolo: string,
     *     titolo_righe: array<int, string>,
     *     commissioni_pct: float,
     *     metodi: array<int, array{id: int, nome: string}>
     * }>
     */
    public static function colonneAttive(): array
    {
        $metodi = metodo_pagamento_ordine::query()
            ->where('abilitato', true)
            ->orderBy('id')
            ->get();

        /** @var array<string, array{commissioni_pct: float, metodi: array<int, array{id: int, nome: string}>}> $gruppi */
        $gruppi = [];

        foreach ($metodi as $m) {
            $pct = round((float) $m->commissioni, 4);
            $key = (string) $pct;

            if (! isset($gruppi[$key])) {
                $gruppi[$key] = [
                    'commissioni_pct' => $pct,
                    'metodi' => [],
                ];
            }

            $gruppi[$key]['metodi'][] = [
                'id' => (int) $m->id,
                'nome' => trim((string) $m->metodo_pagamento),
                'codice' => trim((string) $m->codice),
            ];
        }

        $colonne = [];
        foreach ($gruppi as $key => $g) {
            $righe = self::titoloRighe($g['metodi'], $g['commissioni_pct']);

            $colonne[] = [
                'key' => $key,
                'titolo' => implode(' / ', $righe),
                'titolo_righe' => $righe,
                'commissioni_pct' => $g['commissioni_pct'],
                'metodi' => $g['metodi'],
            ];
        }

        usort(
            $colonne,
            fn (array $a, array $b): int => $b['commissioni_pct'] <=> $a['commissioni_pct'],
        );

        return $colonne;
    }

    public static function prezzoPerColonna(float $prezzoBase, float $commissioniPct): float
    {
        return round($prezzoBase * (1 + ($commissioniPct / 100)), 2);
    }

    /**
     * @param  array<int, array{id: int, nome: string, codice: string}>  $metodi
     * @return array<int, string>
     */
    private static function titoloRighe(array $metodi, float $commissioniPct): array
    {
        if ($metodi === []) {
            return ['—'];
        }

        if ($commissioniPct < 0) {
            $nome = trim((string) ($metodi[0]['nome'] ?? 'Wallet'));

            return [$nome.' (Scontato)'];
        }

        $codici = array_map(
            fn (array $m): string => strtolower(trim((string) ($m['codice'] ?? ''))),
            $metodi,
        );

        if (in_array('carta', $codici, true) && in_array('bonifico', $codici, true)) {
            return ['Carte/Bonifico'];
        }

        $nomi = array_values(array_filter(array_column($metodi, 'nome')));

        return $nomi !== [] ? $nomi : ['—'];
    }
}
