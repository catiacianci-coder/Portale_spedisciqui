<?php

namespace App\Support;

use App\Models\ordine;
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
     *     ordine_id: string,
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
    public static function dettaglioPayload(
        spedizione $s,
        string $etichettaRoute = 'spedizioni.etichetta',
        string $dettaglioRoute = 'etichette.spedizione.dettaglio',
        string $correcaoRoute = 'etichette.spedizione.correcao',
        string $retryRoute = 'etichette.spedizione.retry',
    ): array {
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
        $ldvStampabile = ! $ldvCancellata && SpedizioneEtichettaStato::haEtichettaEsistente($s);

        $metodo = trim((string) ($ord?->metodoPagamentoOrdine?->descrizione ?? ''));
        $podeCorrigir = SpedizioneEtichettaStato::podeCorrigir($s);
        $pendente = SpedizioneEtichettaStato::etichettaPendente($s);

        return [
            'codice_interno' => (string) ($s->codice_interno ?? ''),
            'ordine_id' => $ord?->id ? (string) (int) $ord->id : '',
            'data_pagamento_fmt' => $ord?->data_pagamento?->format('d/m/Y H:i') ?? '—',
            'email' => (string) ($ord?->user?->email ?? ''),
            'servizio' => self::nomeServizio($s),
            'tracking' => trim((string) ($s->tracking ?? '')),
            'stato_label' => (string) ($s->spedizioneStato?->denominazione_stato ?? '—'),
            'importo_ivato_fmt' => $importoIvato !== null
                ? \App\Support\ImportoEuro::format($importoIvato)
                : '—',
            'metodo_pagamento' => $metodo !== '' ? $metodo : '—',
            'mittente' => self::persona($s, true),
            'destinatario' => self::persona($s, false),
            'colli' => self::rigaColli($s),
            'valore_merce' => self::valoreMerceServizi($s),
            'etichetta_url' => $ldvStampabile ? route($etichettaRoute, $s) : null,
            'etichetta_disponibile' => $ldvStampabile,
            'etichetta_pendente' => $pendente,
            'pode_corrigir' => $podeCorrigir,
            'motivo_correcao' => SpedizioneEtichettaStato::motivoCorrecaoDisabilitada($s),
            'correcao_url' => $podeCorrigir ? route($correcaoRoute, $s) : null,
            'retry_url' => $pendente ? route($retryRoute, $s) : null,
            'dettaglio_url' => route($dettaglioRoute, $s),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function dettaglioPayloadBackoffice(spedizione $s): array
    {
        $payload = self::dettaglioPayload(
            $s,
            etichettaRoute: 'backoffice.spedizioni.etichetta',
            dettaglioRoute: 'backoffice.spedizioni.dettaglio',
        );

        $s->loadMissing(['ordine', 'user']);
        $editabile = self::spedizioneEditabileBackoffice($s);
        $errore = self::erroreGenerazioneEtichetta($s);

        return array_merge($payload, [
            'context' => 'backoffice',
            'user_id' => (int) ($s->user_id ?? 0),
            'editabile_bo' => $editabile,
            'manual_url' => $editabile ? route('backoffice.spedizioni.manual', $s) : null,
            'opcoes_url' => $editabile ? route('backoffice.spedizioni.opcoes', $s) : null,
            'retry_url_bo' => $editabile && ($payload['etichetta_pendente'] || (bool) $s->ldverro)
                ? route('backoffice.spedizioni.retry', $s)
                : null,
            'pdf_url' => $payload['etichetta_url'],
            'etichetta_erro' => $errore,
            'etichetta_erro_titolo' => 'Errore nella generazione dell\'etichetta',
            'rastro_status' => trim((string) ($s->tracking_status ?? '')),
        ]);
    }

    public static function spedizioneEditabileBackoffice(spedizione $s): bool
    {
        $s->loadMissing('ordine');

        if ($s->ordine === null || ! $s->ordine->haStato(ordine::STATO_PAGATO)) {
            return false;
        }

        if ((bool) $s->compensata || $s->padre_comp !== null) {
            return false;
        }

        return true;
    }

    public static function erroreGenerazioneEtichetta(spedizione $s): ?string
    {
        if (! (bool) $s->ldverro) {
            return null;
        }

        $s->loadMissing('corriereRecord');
        $corriere = $s->corriereRecord;

        if ($corriere && PiattaformaCorriere::corriereUsaAcquistoSendcloud($corriere)) {
            $msg = SendcloudIntegrazione::tracciaApiAnnounce($s)['error'];
            if ($msg !== null && $msg !== '') {
                return $msg;
            }
        }

        if ($corriere && PiattaformaCorriere::corriereUsaAcquistoLiccardiTms($corriere)) {
            $data = LiccardiTmsIntegrazione::decode($s);
            $msg = trim((string) ($data['last_error'] ?? ''));
            if ($msg !== '') {
                return $msg;
            }
        }

        return 'Generazione etichetta non riuscita. Controlla i dati della spedizione o carica manualmente tracking e PDF.';
    }

    public static function nomeServizio(spedizione $s): string
    {
        return SpedizioneServizioTabella::nomeVisualizzato($s);
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
        return SpedizioneIndirizzoTabella::destinatarioRighe($s);
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
                'importo_fmt' => \App\Support\ImportoEuro::format($valore),
            ];
        }

        return null;
    }
}
