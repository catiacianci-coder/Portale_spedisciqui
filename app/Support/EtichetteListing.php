<?php

namespace App\Support;

use App\Models\spedizione;
use App\Support\EtichettaSpedizioneAccess as EtichettaAccess;
use App\Support\SpedizioneEtichettaStato;

/**
 * Dati per elenco etichette cliente e popup dettaglio spedizione.
 */
final class EtichetteListing
{
    /**
     * @return array{
     *     codice_interno: string,
     *     ordine_codice: string,
     *     data_pagamento_fmt: string,
     *     email: string,
     *     servizio: string,
     *     tracking: string,
     *     stato_label: string,
     *     importo_ivato_fmt: string,
     *     metodo_pagamento: string,
     *     mittente: array{nome: string, indirizzo: string, telefono: string},
     *     destinatario: array{nome: string, indirizzo: string, telefono: string},
     *     colli: string,
     *     valore_merce: ?array{tipo: string, label: string, importo_fmt: string},
     *     etichetta_url: ?string,
     *     etichetta_disponibile: bool,
     *     etichetta_pendente: bool,
     *     pode_corrigir: bool,
     *     motivo_correcao: string,
     *     correcao_url: ?string,
     *     retry_url: ?string,
     *     dettaglio_url: string
     * }
     */
    public static function dettaglioPayload(spedizione $s): array
    {
        $s->loadMissing([
            'ordine.user',
            'ordine.metodoPagamentoOrdine',
            'corriereRecord',
            'tipoSpedizione',
            'spedizioneStato',
            'serviziAggiuntiviRighe.corriereServizioAggiuntivo',
        ]);

        $ord = $s->ordine;
        $importoIvato = $s->prezzoClienteIvato();
        $ldvCancellata = EtichettaAccess::etichettaCancellata($s);
        $ldvStampabile = ! $ldvCancellata && (
            SpedisciOnlineIntegrazione::etichettaStampabile($s)
            || trim((string) $s->etiqueta_pdf_path) !== ''
            || trim((string) $s->id_shipment) !== ''
        );

        $metodo = trim((string) ($ord?->metodoPagamentoOrdine?->descrizione ?? ''));
        $podeCorrigir = SpedizioneEtichettaStato::podeCorrigir($s);
        $pendente = SpedizioneEtichettaStato::etichettaPendente($s);

        return [
            'codice_interno' => (string) ($s->codice_interno ?? ''),
            'ordine_codice' => (string) ($ord?->codice ?? ''),
            'data_pagamento_fmt' => $ord?->data_pagamento?->format('d/m/Y H:i') ?? '—',
            'email' => (string) ($ord?->user?->email ?? ''),
            'servizio' => self::nomeServizio($s),
            'tracking' => trim((string) ($s->tracking ?? '')),
            'stato_label' => (string) ($s->spedizioneStato?->denominazione_stato ?? '—'),
            'importo_ivato_fmt' => $importoIvato !== null
                ? number_format($importoIvato, 2, ',', '.').' €'
                : '—',
            'metodo_pagamento' => $metodo !== '' ? $metodo : '—',
            'mittente' => self::persona($s, true),
            'destinatario' => self::persona($s, false),
            'colli' => self::rigaColli($s),
            'valore_merce' => self::valoreMerceServizi($s),
            'etichetta_url' => $ldvStampabile ? route('spedizioni.etichetta', $s) : null,
            'etichetta_disponibile' => $ldvStampabile,
            'etichetta_pendente' => $pendente,
            'pode_corrigir' => $podeCorrigir,
            'motivo_correcao' => SpedizioneEtichettaStato::motivoCorrecaoDisabilitada($s),
            'correcao_url' => $podeCorrigir ? route('etichette.spedizione.correcao', $s) : null,
            'retry_url' => $pendente ? route('etichette.spedizione.retry', $s) : null,
            'dettaglio_url' => route('etichette.spedizione.dettaglio', $s),
        ];
    }

    public static function nomeServizio(spedizione $s): string
    {
        $s->loadMissing('corriereRecord');

        return trim((string) (
            $s->service_description
            ?? $s->corriere
            ?? $s->corriereRecord?->nome_visualizzato
            ?? $s->corriereRecord?->nome_corriere
            ?? ''
        ));
    }

    /**
     * @return array{nome: string, indirizzo: string, telefono: string}
     */
    public static function persona(spedizione $s, bool $mittente): array
    {
        if ($mittente) {
            $nome = trim((string) ($s->ragione_sociale_o ?: trim((string) (($s->nome_o ?? '').' '.($s->cognome_o ?? '')))));
            $via = trim(implode(' ', array_filter([
                trim((string) ($s->indirizzo_o ?? '')),
                trim((string) ($s->numero_o ?? '')),
            ])));
            $riga2 = trim(implode(' — ', array_filter([
                trim((string) ($s->frazione_o ?? '')),
                trim(implode(' / ', array_filter([
                    trim((string) ($s->citta_o ?? '')),
                    trim((string) ($s->stato_o ?? '')),
                ]))),
                trim((string) ($s->cap_o ?? '')),
            ])));
            $tel = trim((string) ($s->tel_o ?? ''));

            return [
                'nome' => $nome !== '' ? $nome : '—',
                'indirizzo' => trim($via.($riga2 !== '' ? "\n".$riga2 : '')),
                'telefono' => $tel !== '' ? $tel : '—',
            ];
        }

        $nome = trim((string) ($s->ragione_sociale_d ?: trim((string) (($s->nome_d ?? '').' '.($s->sobrenome_d ?? '')))));
        $via = trim(implode(' ', array_filter([
            trim((string) ($s->indirizzo_d ?? '')),
            trim((string) ($s->numero_d ?? '')),
        ])));
        $riga2 = trim(implode(' — ', array_filter([
            trim((string) ($s->frazione_d ?? '')),
            trim(implode(' / ', array_filter([
                trim((string) ($s->citta_d ?? '')),
                trim((string) ($s->stato_d ?? '')),
            ]))),
            trim((string) ($s->cap_d ?? '')),
        ])));
        $tel = trim((string) ($s->tel_d ?? ''));

        return [
            'nome' => $nome !== '' ? $nome : '—',
            'indirizzo' => trim($via.($riga2 !== '' ? "\n".$riga2 : '')),
            'telefono' => $tel !== '' ? $tel : '—',
        ];
    }

    public static function destinatarioTabella(spedizione $s): string
    {
        $p = self::persona($s, false);

        return $p['nome'] !== '—' ? $p['nome'] : '—';
    }

    /**
     * @return list<string>
     */
    public static function destinatarioIndirizzoRigheTabella(spedizione $s): array
    {
        $via = trim(implode(' ', array_filter([
            trim((string) ($s->indirizzo_d ?? '')),
            trim((string) ($s->numero_d ?? '')),
        ])));
        $nazione = trim((string) ($s->frazione_d ?? ''));
        if ($nazione === '') {
            $nazione = 'Italia';
        }
        $localita = trim(implode(' / ', array_filter([
            trim((string) ($s->citta_d ?? '')),
            trim((string) ($s->stato_d ?? '')),
        ])));
        $cap = trim((string) ($s->cap_d ?? ''));
        $rigaLocalita = trim(implode(' — ', array_filter([$localita, $cap])));

        return array_values(array_filter([$via, $nazione, $rigaLocalita], static fn (string $l): bool => $l !== ''));
    }

    public static function destinatarioIndirizzoTabella(spedizione $s): string
    {
        return implode("\n", self::destinatarioIndirizzoRigheTabella($s));
    }

    public static function rigaColli(spedizione $s): string
    {
        $alt = (float) ($s->altezza ?? 0);
        $lar = (float) ($s->larghezza ?? 0);
        $spe = (float) ($s->spessore ?? 0);
        $peso = (float) ($s->peso ?? 0);
        $tipo = trim((string) ($s->tipoSpedizione?->tipo_spedizione ?? ''));

        $dim = ($alt > 0 && $lar > 0 && $spe > 0)
            ? sprintf('%.1f × %.1f × %.1f cm', $lar, $alt, $spe)
            : null;
        $pesoTxt = $peso > 0 ? sprintf('%.2f Kg', $peso) : null;

        $parts = array_filter([
            $tipo !== '' ? $tipo : null,
            $dim,
            $pesoTxt,
        ]);

        return count($parts) > 0 ? implode(' — ', $parts) : '—';
    }

    /**
     * @return ?array{tipo: string, label: string, importo_fmt: string}
     */
    public static function valoreMerceServizi(spedizione $s): ?array
    {
        $s->loadMissing('serviziAggiuntiviRighe.corriereServizioAggiuntivo');

        foreach ($s->serviziAggiuntiviRighe as $riga) {
            $testo = mb_strtolower(trim((string) (
                $riga->denominazione_servizio
                ?? $riga->corriereServizioAggiuntivo?->testo_servizio
                ?? ''
            )));
            if ($testo === '') {
                continue;
            }

            $isContrassegno = str_contains($testo, 'contrassegno');
            $isAssicurazione = str_contains($testo, 'assicur');
            if (! $isContrassegno && ! $isAssicurazione) {
                continue;
            }

            $valore = isset($riga->valore_merce) ? (float) $riga->valore_merce : 0.0;
            if ($valore <= 0) {
                continue;
            }

            return [
                'tipo' => $isContrassegno ? 'contrassegno' : 'assicurazione',
                'label' => $isContrassegno ? 'Importo contrassegno' : 'Valore assicurato',
                'importo_fmt' => number_format($valore, 2, ',', '.').' €',
            ];
        }

        return null;
    }
}
