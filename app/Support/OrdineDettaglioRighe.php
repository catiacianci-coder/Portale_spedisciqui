<?php

namespace App\Support;

use App\Models\ordine;
use App\Support\SpedizioneCampiPersistenza;

/**
 * Righe ordine (da dettaglio_json) arricchite con snapshot DB delle spedizioni, per le card operative cliente/BO.
 */
class OrdineDettaglioRighe
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function righePerCards(ordine $ordine): array
    {
        $ordine->loadMissing([
            'spedizioni' => fn ($q) => $q->with(['corriereRecord', 'tipoSpedizione'])->orderBy('id'),
        ]);

        $righe = $ordine->dettaglio_json['righe'] ?? [];
        if (! is_array($righe)) {
            $righe = [];
        }

        $spedizioni = $ordine->spedizioni;
        foreach ($righe as $i => &$r) {
            if (! is_array($r)) {
                continue;
            }
            $r = RigaCarrelloOrdine::normalizza($r);
            $sp = $spedizioni->get($i);
            if (! $sp) {
                continue;
            }
            $r['codice_interno_spedizione'] = (string) ($sp->codice_interno ?? '');
            $r['corriere_nome_visualizzato'] = trim((string) ($sp->corriere ?? $sp->corriereRecord?->nome_visualizzato ?? $sp->corriereRecord?->nome_corriere ?? ($r['corriere_nome_visualizzato'] ?? $r['corriere_nome'] ?? '')));
            $r['tipo_spedizione_nome'] = trim((string) ($sp->tipoSpedizione?->tipo_spedizione ?? ($r['tipo_spedizione_nome'] ?? '')));
            $smitt = SpedizioneCampiPersistenza::mittenteArray($sp);
            if ($smitt !== []) {
                $r['indirizzi'] = $r['indirizzi'] ?? [];
                $part = is_array($r['indirizzi']['partenza'] ?? null) ? $r['indirizzi']['partenza'] : [];
                foreach ($smitt as $k => $v) {
                    if (! isset($part[$k]) || trim((string) $part[$k]) === '') {
                        if ($v !== null && $v !== '') {
                            $part[$k] = $v;
                        }
                    }
                }
                $r['indirizzi']['partenza'] = $part;
            }
            $sdest = SpedizioneCampiPersistenza::destinatarioArray($sp);
            if ($sdest !== []) {
                $r['indirizzi'] = $r['indirizzi'] ?? [];
                $dest = is_array($r['indirizzi']['destinazione'] ?? null) ? $r['indirizzi']['destinazione'] : [];
                foreach ($sdest as $k => $v) {
                    if (! isset($dest[$k]) || trim((string) $dest[$k]) === '') {
                        if ($v !== null && $v !== '') {
                            $dest[$k] = $v;
                        }
                    }
                }
                $r['indirizzi']['destinazione'] = $dest;
            }
            $pj = SpedizioneCampiPersistenza::paccoArray($sp);
            if ($pj !== []) {
                $base = is_array($r['dati_pacco'] ?? null) ? $r['dati_pacco'] : [];
                foreach (['peso_kg', 'altezza_cm', 'larghezza_cm', 'spessore_cm'] as $k) {
                    $cur = $base[$k] ?? null;
                    if (($cur === null || $cur === '' || ! is_numeric($cur)) && array_key_exists($k, $pj) && $pj[$k] !== null && $pj[$k] !== '') {
                        $base[$k] = is_numeric($pj[$k]) ? (float) $pj[$k] : $pj[$k];
                    }
                }
                $r['dati_pacco'] = $base;
            }
        }
        unset($r);

        return $righe;
    }
}
