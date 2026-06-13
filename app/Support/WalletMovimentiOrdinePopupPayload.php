<?php

namespace App\Support;

use App\Models\ordine;
use App\Models\spedizione;
use App\Services\OrdineTotaleIvatoService;
use App\Support\SpedizioneCampiPersistenza;

/**
 * Dati strutturati per la modale «dettaglio ordine» nella pagina movimenti wallet.
 */
final class WalletMovimentiOrdinePopupPayload
{
    /**
     * @return array{codice: string, spedizioni: list<array<string, mixed>>}|null
     */
    public static function fromOrdine(?ordine $ordine): ?array
    {
        if ($ordine === null) {
            return null;
        }

        $ordine->loadMissing([
            'spedizioni' => fn ($q) => $q->orderBy('id'),
            'spedizioni.corriereRecord',
            'spedizioni.tipoSpedizione',
            'spedizioni.spedizioneStato',
            'spedizioni.tariffaSpedizione',
            'spedizioni.serviziAggiuntiviRighe.corriereServizioAggiuntivo:id,testo_servizio',
            'metodoPagamentoOrdine',
        ]);

        $pagamentoWallet = $ordine->metodo_pagamento_ordinis_id
            && app(OrdineTotaleIvatoService::class)->metodoIsWallet((int) $ordine->metodo_pagamento_ordinis_id);

        return [
            'codice' => (string) $ordine->codice,
            'pagamento_wallet' => $pagamentoWallet,
            'spedizioni' => $ordine->spedizioni
                ->map(fn (spedizione $s) => self::spedizioneRiga($s))
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function spedizioneRiga(spedizione $s): array
    {
        $nomeCorriere = trim((string) ($s->corriere ?? ''));
        if ($nomeCorriere === '' && $s->corriereRecord) {
            $nomeCorriere = trim((string) ($s->corriereRecord->nome_visualizzato ?? ''))
                ?: trim((string) ($s->corriereRecord->nome_servizio ?? ''))
                ?: trim((string) ($s->corriereRecord->nome_corriere ?? ''));
        }

        $pacco = SpedizioneCampiPersistenza::paccoArray($s);
        $peso = self::floatOrNull($s->peso ?? $pacco['peso_kg'] ?? null);
        $h = self::floatOrNull($s->altezza ?? $pacco['altezza_cm'] ?? null);
        $w = self::floatOrNull($s->larghezza ?? $pacco['larghezza_cm'] ?? null);
        $d = self::floatOrNull($s->spessore ?? $pacco['spessore_cm'] ?? null);

        $fmtDim = ($h !== null && $w !== null && $d !== null)
            ? number_format((float) $h, 2, ',', '.')
                .' × '.number_format((float) $w, 2, ',', '.')
                .' × '.number_format((float) $d, 2, ',', '.').' cm'
            : null;

        $pesoStr = $peso !== null ? number_format((float) $peso, 2, ',', '.').' kg' : null;
        $dimensioniPesoParts = array_filter([$pesoStr, $fmtDim !== null ? 'Dimensioni: '.$fmtDim : null]);
        $dimensioniPeso = $dimensioniPesoParts !== [] ? implode(' · ', $dimensioniPesoParts) : null;

        $contenuto = self::contenutoPacco($pacco);

        $prezzo = SpedizioneCampiPersistenza::prezzoNettoDaOrdine($s);
        $costo = $prezzo !== null
            ? number_format((float) $prezzo, 2, ',', '.').' €'
            : null;

        $tipologia = $s->tipoSpedizione?->tipo_spedizione;
        $tipologia = $tipologia !== null && trim((string) $tipologia) !== '' ? trim((string) $tipologia) : null;
        $servizi = [];
        foreach ($s->serviziAggiuntiviRighe as $riga) {
            $lbl = $riga->denominazione_servizio
                ?? $riga->corriereServizioAggiuntivo?->testo_servizio;
            if ($lbl !== null && trim((string) $lbl) !== '') {
                $item = ['nome' => trim((string) $lbl)];
                $val = self::valoreUtenteServizio($riga);
                if ($val !== null) {
                    $item['valore'] = $val;
                }
                $servizi[] = $item;
            }
        }
        $servizi = array_values($servizi);
        $serviziLabel = $servizi !== []
            ? implode(', ', array_map(fn (array $sx) => isset($sx['valore']) ? ($sx['nome'].' ('.$sx['valore'].')') : $sx['nome'], $servizi))
            : null;

        $statoUi = RimborsoEtichettaUi::statoEtichettaUi($s);
        $importoIvato = RimborsoEtichettaUi::importoIvato($s);

        return [
            'id' => (int) $s->id,
            'codice_interno' => (string) ($s->codice_interno ?? ''),
            'stato_badge' => $statoUi['badge'],
            'stato_label' => $statoUi['testo'],
            'destinatario_tabella' => RimborsoEtichettaUi::nomeDestinatario($s),
            'servizio_tabella' => RimborsoEtichettaUi::nomeServizioVisualizzato($s),
            'tracking_tabella' => trim((string) ($s->tracking ?? '')),
            'importo_ivato' => $importoIvato,
            'importo_ivato_fmt' => $importoIvato !== null
                ? number_format($importoIvato, 2, ',', '.').' €'
                : null,
            'corriere_nome' => $nomeCorriere !== '' ? $nomeCorriere : null,
            'mittente' => self::bloccoIndirizzoConcat($s, 'mittente'),
            'destinatario' => self::bloccoIndirizzoConcat($s, 'destinatario'),
            'mittente_note' => self::bloccoIndirizzoNota($s, 'mittente'),
            'destinatario_note' => self::bloccoIndirizzoNota($s, 'destinatario'),
            'costo_totale' => $costo,
            'tracking' => self::stringOrNull($s->tracking),
            'tipologia' => $tipologia,
            'dimensioni_peso' => $dimensioniPeso,
            'servizi_aggiuntivi_items' => $servizi,
            'servizi_aggiuntivi' => $serviziLabel,
            'contenuto' => $contenuto,
        ];
    }

    /**
     * Valori mittente/destinatario in un’unica stringa, separati da uno spazio.
     */
    private static function bloccoIndirizzoConcat(spedizione $s, string $lato): ?string
    {
        $lines = self::bloccoIndirizzo($s, $lato);
        if ($lines === []) {
            return null;
        }
        $parts = array_map(function (array $row): string {
            $label = trim((string) ($row['label'] ?? ''));
            $value = trim((string) ($row['value'] ?? ''));
            if ($value === '') {
                return '';
            }
            if ($label !== '' && mb_strtolower($label) === 'note') {
                return '';
            }

            return $value;
        }, $lines);
        $parts = array_values(array_filter($parts, fn (string $x) => $x !== ''));

        return $parts !== [] ? implode(' ', $parts) : null;
    }

    private static function bloccoIndirizzoNota(spedizione $s, string $lato): ?string
    {
        $lines = self::bloccoIndirizzo($s, $lato);
        foreach ($lines as $row) {
            $label = mb_strtolower(trim((string) ($row['label'] ?? '')));
            $value = trim((string) ($row['value'] ?? ''));
            if ($label === 'note' && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    /**
     * @return list<array{label: string, value: string}>
     */
    private static function bloccoIndirizzo(spedizione $s, string $lato): array
    {
        $json = $lato === 'mittente'
            ? SpedizioneCampiPersistenza::mittenteArray($s)
            : SpedizioneCampiPersistenza::destinatarioArray($s);

        $lines = [];
        $seen = [];

        $add = function (string $label, mixed $val) use (&$lines, &$seen): void {
            if ($val === null || $val === '') {
                return;
            }
            $t = trim((string) $val);
            if ($t === '') {
                return;
            }
            $key = mb_strtolower($label);
            if (isset($seen[$key])) {
                return;
            }
            $seen[$key] = true;
            $lines[] = ['label' => $label, 'value' => $t];
        };

        if ($lato === 'mittente') {
            $add('Nome', $s->nome_o ?? self::jsonStr($json, 'nome'));
            $add('Cognome', $s->cognome_o ?? self::jsonStr($json, 'cognome'));
            $add('Ragione sociale', $s->ragione_sociale_o ?? self::jsonStr($json, 'ragione_sociale'));
            $add('Indirizzo', $s->indirizzo_o ?? self::jsonStrAny($json, ['indirizzo', 'via', 'street']));
            $add('Numero civico', $s->numero_o ?? self::jsonStrAny($json, ['numero', 'civico', 'street_number']));
            $add('CAP', $s->cap_o ?? self::jsonStr($json, 'cap'));
            $add('Città', $s->citta_o ?? self::jsonStrAny($json, ['comune', 'citta', 'city']));
            $add('Provincia', $s->stato_o ?? self::jsonStr($json, 'provincia'));
            $add('Nazione', self::jsonStr($json, 'nazione'));
            $add('Telefono', self::jsonStrAny($json, ['telefono', 'phone', 'tel']));
            $add('Email', self::jsonStrAny($json, ['email', 'mail']));
            $add('Note', self::jsonStrAny($json, ['note', 'note_ritiro', 'istruzioni']));
        } else {
            $nomeDest = $s->nome_d ?? self::jsonStr($json, 'nome');
            $cognomeDest = $s->sobrenome_d ?? self::jsonStr($json, 'cognome');
            $add('Nome', $nomeDest);
            $add('Cognome', $cognomeDest);
            $add('Ragione sociale', self::jsonStr($json, 'ragione_sociale'));
            $nomeDestinatario = self::jsonStr($json, 'nome_destinatario');
            $nomeCompleto = trim(trim((string) $nomeDest).' '.trim((string) $cognomeDest));
            if ($nomeDestinatario !== null && mb_strtolower($nomeDestinatario) !== mb_strtolower($nomeCompleto)) {
                $add('Nome destinatario', $nomeDestinatario);
            }
            $add('Indirizzo', $s->indirizzo_d ?? self::jsonStrAny($json, ['indirizzo', 'via', 'street']));
            $add('Numero civico', $s->numero_d ?? self::jsonStrAny($json, ['numero', 'civico', 'street_number']));
            $add('CAP', $s->cap_d ?? self::jsonStr($json, 'cap'));
            $add('Città', $s->citta_d ?? self::jsonStrAny($json, ['comune', 'citta', 'city']));
            $add('Provincia', $s->stato_d ?? self::jsonStr($json, 'provincia'));
            $add('Nazione', self::jsonStr($json, 'nazione'));
            $add('Telefono', self::jsonStrAny($json, ['telefono', 'phone', 'tel']));
            $add('Email', self::jsonStrAny($json, ['email', 'mail']));
            $add('Note', self::jsonStrAny($json, ['note', 'note_consegna', 'istruzioni']));
        }

        $reserved = [
            'nome', 'cognome', 'ragione_sociale', 'nome_destinatario', 'nome_cognome', 'indirizzo', 'via', 'street',
            'numero', 'civico', 'street_number', 'cap', 'comune', 'citta', 'city', 'provincia', 'nazione',
            'telefono', 'phone', 'tel', 'email', 'mail', 'note', 'note_ritiro', 'note_consegna', 'istruzioni',
            'first_name', 'last_name',
        ];

        foreach ($json as $key => $val) {
            if (! is_string($key) || in_array($key, $reserved, true)) {
                continue;
            }
            if (! is_scalar($val) || (string) $val === '') {
                continue;
            }
            $add(self::etichettaChiave($key), $val);
        }

        return $lines;
    }

    /**
     * @param  array<string, mixed>  $pacco
     */
    private static function contenutoPacco(array $pacco): ?string
    {
        $keys = [
            'contenuto', 'contenuto_collo', 'descrizione_contenuto', 'descrizione_collo',
            'descrizione', 'note_contenuto', 'merce', 'tipo_merce',
        ];
        foreach ($keys as $k) {
            $v = $pacco[$k] ?? null;
            if ($v !== null && $v !== '') {
                $t = trim((string) $v);

                return $t !== '' ? $t : null;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $json
     */
    private static function jsonStr(array $json, string $key): ?string
    {
        if (! array_key_exists($key, $json)) {
            return null;
        }
        $v = $json[$key];
        if ($v === null || $v === '') {
            return null;
        }
        $t = trim((string) $v);

        return $t !== '' ? $t : null;
    }

    /**
     * @param  array<string, mixed>  $json
     * @param  list<string>  $keys
     */
    private static function jsonStrAny(array $json, array $keys): ?string
    {
        foreach ($keys as $k) {
            $s = self::jsonStr($json, $k);
            if ($s !== null) {
                return $s;
            }
        }

        return null;
    }

    private static function etichettaChiave(string $key): string
    {
        $key = str_replace('_', ' ', $key);

        return mb_convert_case($key, MB_CASE_TITLE, 'UTF-8');
    }

    private static function stringOrNull(mixed $v): ?string
    {
        if ($v === null || $v === '') {
            return null;
        }
        $t = trim((string) $v);

        return $t !== '' ? $t : null;
    }

    private static function floatOrNull(mixed $v): ?float
    {
        if ($v === null || $v === '') {
            return null;
        }
        if (is_numeric($v)) {
            return (float) $v;
        }

        return null;
    }

    private static function valoreUtenteServizio(object $riga): ?string
    {
        $attrs = method_exists($riga, 'getAttributes') ? (array) $riga->getAttributes() : [];
        foreach (['valore_merce', 'valore_dichiarato', 'importo_dichiarato', 'valore'] as $k) {
            $raw = $attrs[$k] ?? null;
            if ($raw !== null && $raw !== '' && is_numeric($raw)) {
                return number_format((float) $raw, 2, ',', '.').' €';
            }
        }

        return null;
    }

}
