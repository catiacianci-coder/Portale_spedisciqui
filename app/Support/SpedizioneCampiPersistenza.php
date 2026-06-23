<?php

namespace App\Support;

use App\Models\corriere;
use App\Models\spedizione;
use App\Models\stato_spedizione;
use App\Models\tariffa;

/**
 * Mappa righe carrello / snapshot indirizzi verso colonne tabella spedizionis (schema CSV).
 */
final class SpedizioneCampiPersistenza
{
    /**
     * @param  array<string, mixed>  $it  Riga carrello normalizzata
     * @return array<string, mixed>
     */
    public static function attributiDaRigaCarrello(
        array $it,
        int $userId,
        int $ordineId,
        ?corriere $crow = null,
        ?tariffa $trow = null,
    ): array {
        $ind = is_array($it['indirizzi'] ?? null) ? $it['indirizzi'] : [];
        $mittenteRaw = is_array($ind['partenza'] ?? null) ? $ind['partenza'] : [];
        $destinatarioRaw = is_array($ind['destinazione'] ?? null) ? $ind['destinazione'] : [];
        if ($crow && PuntoConsegnaSessione::consegnaRichiedePunto($crow->consegna)) {
            $destinatarioRaw = PuntoConsegnaSessione::destinazioneConIndirizzoPunto($destinatarioRaw);
        }
        $mittente = IndirizzoSpedizioneSnapshot::mittentePerDatabase($mittenteRaw, $ind);
        $destinatario = IndirizzoSpedizioneSnapshot::destinatarioPerDatabase($destinatarioRaw);
        $pacco = RigaCarrelloOrdine::paccoPerSpedizione($it);

        $cid = isset($it['corriere_id']) ? (int) $it['corriere_id'] : 0;
        $nomeCorriere = trim((string) ($it['corriere_nome_visualizzato'] ?? $it['corriere_nome'] ?? ''));
        if ($nomeCorriere === '' && $crow) {
            $nomeCorriere = trim((string) ($crow->nome_visualizzato ?? $crow->nome_corriere ?? ''));
        }

        $padreReso = isset($it['reso_source_spedizione_id']) && (int) $it['reso_source_spedizione_id'] > 0
            ? (int) $it['reso_source_spedizione_id']
            : null;

        $tipoId = $trow?->id_tipo_spediziones;
        if ($tipoId === null) {
            $prevInput = is_array($it['preventivo_input'] ?? null) ? $it['preventivo_input'] : [];
            $idTipo = (int) ($prevInput['id_tipo_spediziones'] ?? 0);
            $tipoId = $idTipo > 0 ? $idTipo : null;
        }

        return array_merge(
            self::mittenteDestinatarioPacco($mittente, $destinatario, $pacco),
            self::campiPuntoDestinatario($destinatarioRaw),
            [
                'user_id' => $userId,
                'ordine_id' => $ordineId,
                'spedizione_stato_id' => stato_spedizione::NON_PAGATA,
                'carrello_id' => isset($it['id']) ? (string) $it['id'] : null,
                'tipo_id' => $tipoId,
                'id_codice_servizio' => $cid > 0 ? $cid : null,
                'codice_servizio' => $crow?->codice_servizio,
                'service_description' => $crow ? trim((string) ($crow->nome_servizio ?? '')) : null,
                'corriere' => $nomeCorriere !== '' ? $nomeCorriere : null,
                'reso' => (bool) ($it['is_reso'] ?? false),
                'padre_reso' => $padreReso,
                'codice_reso' => null,
                'esiste_integrazione' => false,
                'tracking' => null,
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $mittente
     * @param  array<string, mixed>  $destinatario
     * @param  array<string, mixed>  $pacco
     * @return array<string, mixed>
     */
    public static function mittenteDestinatarioPacco(array $mittente, array $destinatario, array $pacco): array
    {
        $viaO = trim((string) ($mittente['via'] ?? ''));
        $numO = trim((string) ($mittente['numero'] ?? ''));
        $indirizzoO = IndirizzoViaCivico::perColonnaDatabase(
            $viaO,
            $numO,
            trim((string) ($mittente['indirizzo'] ?? '')),
        );

        $viaD = trim((string) ($destinatario['via'] ?? ''));
        $numD = trim((string) ($destinatario['numero'] ?? ''));
        $indirizzoD = IndirizzoViaCivico::perColonnaDatabase(
            $viaD,
            $numD,
            trim((string) ($destinatario['indirizzo'] ?? '')),
        );

        return [
            'nome_o' => self::strOrNull($mittente['nome'] ?? null),
            'cognome_o' => self::strOrNull($mittente['cognome'] ?? null),
            'ragione_sociale_o' => self::strOrNull($mittente['denominazione_impresa'] ?? $mittente['ragione_sociale'] ?? null),
            'cap_o' => self::strOrNull($mittente['cap'] ?? null),
            'citta_o' => self::strOrNull($mittente['comune'] ?? $mittente['citta'] ?? null),
            'indirizzo_o' => $indirizzoO !== '' ? $indirizzoO : null,
            'numero_o' => $numO !== '' ? $numO : null,
            'frazione_o' => self::nazioneItalia($mittente),
            'stato_o' => self::strOrNull($mittente['provincia'] ?? null),
            'tel_o' => self::strOrNull($mittente['telefono'] ?? null),
            'email_o' => self::strOrNull($mittente['email'] ?? null),
            'note_o' => self::strOrNull($mittente['note'] ?? null),

            'nome_d' => self::strOrNull($destinatario['nome'] ?? null),
            'sobrenome_d' => self::strOrNull($destinatario['cognome'] ?? null),
            'ragione_sociale_d' => self::strOrNull($destinatario['ragione_sociale'] ?? null),
            'cap_d' => self::strOrNull($destinatario['cap'] ?? null),
            'citta_d' => self::strOrNull($destinatario['comune'] ?? $destinatario['citta'] ?? null),
            'indirizzo_d' => $indirizzoD !== '' ? $indirizzoD : null,
            'numero_d' => $numD !== '' ? $numD : null,
            'frazione_d' => self::nazioneItalia($destinatario),
            'stato_d' => self::strOrNull($destinatario['provincia'] ?? null),
            'tel_d' => self::strOrNull($destinatario['telefono'] ?? null),
            'email_d' => self::strOrNull($destinatario['email'] ?? null),
            'note_d' => self::strOrNull($destinatario['note'] ?? null),

            'altezza' => self::numOrNull($pacco['altezza_cm'] ?? $pacco['altezza'] ?? null),
            'larghezza' => self::numOrNull($pacco['larghezza_cm'] ?? $pacco['larghezza'] ?? null),
            'spessore' => self::numOrNull($pacco['spessore_cm'] ?? $pacco['spessore'] ?? null),
            'peso' => self::numOrNull($pacco['peso_kg'] ?? $pacco['peso'] ?? null),
        ];
    }

    /**
     * @param  array<string, mixed>  $destinatarioRaw
     * @return array<string, mixed>
     */
    private static function campiPuntoDestinatario(array $destinatarioRaw): array
    {
        $servicePointId = (int) ($destinatarioRaw['to_service_point'] ?? 0);

        return array_filter([
            'to_service_point' => $servicePointId > 0 ? $servicePointId : null,
            'nome_punto' => self::strOrNull($destinatarioRaw['nome_punto'] ?? null),
            'to_post_number' => self::strOrNull($destinatarioRaw['to_post_number'] ?? null),
        ], static fn ($v) => $v !== null);
    }

    /**
     * @return array<string, mixed>  Struttura compatibile con letture legacy (nome, cap, comune, …)
     */
    public static function mittenteArray(spedizione $s): array
    {
        return self::indirizzoArray($s, 'o');
    }

    /**
     * @return array<string, mixed>
     */
    public static function destinatarioArray(spedizione $s): array
    {
        return self::indirizzoArray($s, 'd');
    }

    /**
     * @return array<string, mixed>
     */
    public static function paccoArray(spedizione $s): array
    {
        return array_filter([
            'peso_kg' => $s->peso,
            'altezza_cm' => $s->altezza,
            'larghezza_cm' => $s->larghezza,
            'spessore_cm' => $s->spessore,
        ], fn ($v) => $v !== null && $v !== '');
    }

    /**
     * @return array<string, mixed>
     */
    private static function indirizzoArray(spedizione $s, string $lato): array
    {
        if ($lato === 'o') {
            return array_filter([
                'nome' => $s->nome_o,
                'cognome' => $s->cognome_o,
                'ragione_sociale' => $s->ragione_sociale_o,
                'denominazione_impresa' => $s->ragione_sociale_o,
                'cap' => $s->cap_o,
                'comune' => $s->citta_o,
                'indirizzo' => $s->indirizzo_o,
                'numero' => $s->numero_o,
                'frazione' => $s->frazione_o,
                'nazione' => $s->frazione_o,
                'provincia' => $s->stato_o,
                'telefono' => $s->tel_o,
                'email' => $s->email_o,
                'note' => $s->note_o,
            ], fn ($v) => $v !== null && trim((string) $v) !== '');
        }

        return array_filter([
            'nome' => $s->nome_d,
            'cognome' => $s->sobrenome_d,
            'ragione_sociale' => $s->ragione_sociale_d,
            'denominazione_impresa' => $s->ragione_sociale_d,
            'cap' => $s->cap_d,
            'comune' => $s->citta_d,
            'indirizzo' => $s->indirizzo_d,
            'numero' => $s->numero_d,
            'frazione' => $s->frazione_d,
            'nazione' => $s->frazione_d,
            'provincia' => $s->stato_d,
            'telefono' => $s->tel_d,
            'email' => $s->email_d,
            'note' => $s->note_d,
        ], fn ($v) => $v !== null && trim((string) $v) !== '');
    }

    public static function prezzoNettoDaOrdine(spedizione $s): ?float
    {
        $s->loadMissing(['tariffaSpedizione', 'ordine']);
        if ($s->tariffaSpedizione && $s->tariffaSpedizione->totale_spedizione !== null) {
            return round((float) $s->tariffaSpedizione->totale_spedizione, 2);
        }

        $s->loadMissing('ordine');
        $ordine = $s->ordine;
        if ($ordine === null) {
            return null;
        }

        $righe = $ordine->dettaglio_json['righe'] ?? [];
        if (! is_array($righe)) {
            return null;
        }

        $carrelloId = trim((string) ($s->carrello_id ?? ''));
        foreach ($righe as $r) {
            if (! is_array($r)) {
                continue;
            }
            $r = RigaCarrelloOrdine::normalizza($r);
            if ($carrelloId !== '' && (string) ($r['id'] ?? '') === $carrelloId) {
                return isset($r['netto_iva_esc']) ? round((float) $r['netto_iva_esc'], 2) : null;
            }
        }

        $index = $s->ordine?->spedizioni?->search(fn (spedizione $x) => (int) $x->id === (int) $s->id);
        if ($index !== false && isset($righe[$index]) && is_array($righe[$index])) {
            $r = RigaCarrelloOrdine::normalizza($righe[$index]);

            return isset($r['netto_iva_esc']) ? round((float) $r['netto_iva_esc'], 2) : null;
        }

        return null;
    }

    /** Importo ivato pagato dal cliente: ordine pagato → solo pag_effettivo_sp; altrimenti stima pre-pagamento. */
    public static function prezzoClienteIvatoDaOrdine(spedizione $s): ?float
    {
        $s->loadMissing(['tariffaSpedizione', 'ordine.metodoPagamentoOrdine']);

        if ($s->ordine?->haStato(\App\Models\ordine::STATO_PAGATO)) {
            return self::pagEffettivoSp($s);
        }

        if ($s->tariffaSpedizione !== null && $s->tariffaSpedizione->cliente_ivato !== null) {
            $ivato = round((float) $s->tariffaSpedizione->cliente_ivato, 2);

            return $ivato > 0 ? $ivato : null;
        }

        $netto = self::prezzoNettoDaOrdine($s);
        if ($netto === null || $netto <= 0) {
            return null;
        }

        $commissioni = $s->ordine?->metodoPagamentoOrdine
            ? (float) $s->ordine->metodoPagamentoOrdine->commissioni
            : 0.0;

        $ivato = TariffaSpedizioneClienteIvato::calcolaDaNetto(
            $netto,
            TariffaSpedizioneClienteIvato::aliquotaIva($s->ordine),
            $commissioni,
        );

        return $ivato > 0 ? $ivato : null;
    }

    /** Importo ivato effettivamente pagato per la spedizione (solo ordini pagati). */
    public static function pagEffettivoSp(spedizione $s): ?float
    {
        $s->loadMissing(['tariffaSpedizione', 'ordine']);

        if (! $s->ordine?->haStato(\App\Models\ordine::STATO_PAGATO)) {
            return null;
        }

        $pagato = $s->tariffaSpedizione?->pag_effettivo_sp;
        if ($pagato === null) {
            return null;
        }

        $ivato = round((float) $pagato, 2);

        return $ivato > 0 ? $ivato : null;
    }

    public static function prezzoNettoWalletDaOrdine(spedizione $s): ?float
    {
        $s->loadMissing(['tariffaSpedizione', 'ordine']);
        if ($s->tariffaSpedizione && $s->tariffaSpedizione->totale_spedizione_wallet !== null) {
            return round((float) $s->tariffaSpedizione->totale_spedizione_wallet, 2);
        }

        $s->loadMissing('ordine');
        $ordine = $s->ordine;
        if ($ordine === null) {
            return null;
        }

        $righe = $ordine->dettaglio_json['righe'] ?? [];
        if (! is_array($righe)) {
            return null;
        }

        $carrelloId = trim((string) ($s->carrello_id ?? ''));
        foreach ($righe as $r) {
            if (! is_array($r)) {
                continue;
            }
            $r = RigaCarrelloOrdine::normalizza($r);
            if ($carrelloId !== '' && (string) ($r['id'] ?? '') === $carrelloId) {
                return isset($r['netto_wallet_iva_esc'])
                    ? round((float) $r['netto_wallet_iva_esc'], 2)
                    : (isset($r['netto_iva_esc']) ? round((float) $r['netto_iva_esc'], 2) : null);
            }
        }

        $index = $s->ordine?->spedizioni?->search(fn (spedizione $x) => (int) $x->id === (int) $s->id);
        if ($index !== false && isset($righe[$index]) && is_array($righe[$index])) {
            $r = RigaCarrelloOrdine::normalizza($righe[$index]);

            return isset($r['netto_wallet_iva_esc'])
                ? round((float) $r['netto_wallet_iva_esc'], 2)
                : (isset($r['netto_iva_esc']) ? round((float) $r['netto_iva_esc'], 2) : null);
        }

        return null;
    }

    /** Importo ivato Wallet (cliente_ivato_wallet) per ordini non pagati. */
    public static function prezzoClienteIvatoWalletDaOrdine(spedizione $s): ?float
    {
        $s->loadMissing(['tariffaSpedizione', 'ordine']);

        if ($s->tariffaSpedizione !== null && $s->tariffaSpedizione->cliente_ivato_wallet !== null) {
            $ivato = round((float) $s->tariffaSpedizione->cliente_ivato_wallet, 2);

            return $ivato > 0 ? $ivato : null;
        }

        $netto = self::prezzoNettoWalletDaOrdine($s);
        if ($netto === null || $netto <= 0) {
            return null;
        }

        $ivato = TariffaSpedizioneClienteIvato::calcolaDaNetto(
            $netto,
            TariffaSpedizioneClienteIvato::aliquotaIva($s->ordine),
            0,
        );

        return $ivato > 0 ? $ivato : null;
    }

    /**
     * Colonna frazione_* = nazione (Stato paese). Per spedizioni Italia→Italia è sempre Italia.
     *
     * @param  array<string, mixed>  $indirizzo
     */
    private static function nazioneItalia(array $indirizzo): string
    {
        $nazione = self::strOrNull($indirizzo['nazione'] ?? $indirizzo['paese'] ?? null);

        return $nazione ?? 'Italia';
    }

    private static function strOrNull(mixed $v): ?string
    {
        if ($v === null) {
            return null;
        }
        $t = trim((string) $v);

        return $t !== '' ? $t : null;
    }

    private static function numOrNull(mixed $v): ?float
    {
        if ($v === null || $v === '') {
            return null;
        }
        if (is_numeric($v)) {
            return (float) $v;
        }

        return null;
    }
}
