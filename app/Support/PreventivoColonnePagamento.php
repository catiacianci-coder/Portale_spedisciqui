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

    public static function commissioniPctMetodo(string $codice): float
    {
        $m = metodo_pagamento_ordine::query()
            ->where('abilitato', true)
            ->where('codice', $codice)
            ->first();

        return $m !== null ? (float) $m->commissioni : 0.0;
    }

    public static function prezzoTrasportoPerMetodo(float $prezzoBase, string $codice): float
    {
        return self::prezzoPerColonna(round(max(0, $prezzoBase), 2), self::commissioniPctMetodo($codice));
    }

    /**
     * Allinea i prezzi trasporto riga carrello/ordine alle colonne preventivo (Bonifico / Wallet).
     *
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    public static function applicaPrezziTrasportoSuRiga(array $item, float $baseTrasporto): array
    {
        $base = round(max(0, $baseTrasporto), 2);
        $extra = round((float) ($item['extra_servizi_iva_esc'] ?? 0), 2);

        $item['trasporto_base_iva_esc'] = $base;
        $item['trasporto_iva_esc'] = self::prezzoTrasportoPerMetodo($base, MetodoPagamentoCodice::BONIFICO);
        $item['trasporto_wallet_iva_esc'] = self::prezzoTrasportoPerMetodo($base, MetodoPagamentoCodice::WALLET);
        $item['netto_iva_esc'] = round($item['trasporto_iva_esc'] + $extra, 2);
        $item['netto_wallet_iva_esc'] = round($item['trasporto_wallet_iva_esc'] + $extra, 2);

        return $item;
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
