<?php

namespace App\Support;

use App\Models\ordine;
use App\Models\spedizione;
use App\Models\stato_spedizione;

/** Stato etichetta PDF / azioni cliente (retry, correzione). */
final class SpedizioneEtichettaStato
{
    public static function consegnaADomicilio(spedizione $s): bool
    {
        if ((int) ($s->to_service_point ?? 0) > 0) {
            return false;
        }

        if (trim((string) ($s->nome_punto ?? '')) !== '') {
            return false;
        }

        if (trim((string) ($s->to_post_number ?? '')) !== '') {
            return false;
        }

        return true;
    }

    public static function temEtichettaPronta(spedizione $s): bool
    {
        if ((bool) $s->compensata || $s->padre_comp !== null) {
            return false;
        }

        if (EtichettaSpedizioneAccess::etichettaCancellata($s)) {
            return false;
        }

        return EtichettaSpedizioneAccess::percorsoAssoluto($s) !== null
            || SpedisciOnlineIntegrazione::etichettaStampabile($s);
    }

    public static function etichettaPendente(spedizione $s): bool
    {
        if (! self::spedizionePagataAttiva($s)) {
            return false;
        }

        if ((bool) $s->compensata || $s->padre_comp !== null) {
            return false;
        }

        return ! self::temEtichettaPronta($s);
    }

    public static function haSuccessoreCompensazione(spedizione $s): bool
    {
        return spedizione::query()->where('padre_comp', $s->id)->exists();
    }

    public static function haEtichettaEsistente(spedizione $s): bool
    {
        if (EtichettaSpedizioneAccess::etichettaCancellata($s)) {
            return false;
        }

        if (SpedisciOnlineIntegrazione::etichettaStampabile($s)) {
            return true;
        }

        if (EtichettaSpedizioneAccess::percorsoAssoluto($s) !== null) {
            return true;
        }

        if (trim((string) ($s->etiqueta_pdf_path ?? '')) !== ''
            || trim((string) ($s->id_shipment ?? '')) !== '') {
            return true;
        }

        $s->loadMissing('corriereRecord');
        $corriere = $s->corriereRecord;

        if ($corriere && PiattaformaCorriere::corriereUsaAcquistoSendcloud($corriere)) {
            return SendcloudIntegrazione::shipmentId($s) !== null
                || ((bool) $s->esiste_integrazione && SendcloudIntegrazione::tracking($s) !== null);
        }

        if ($corriere && PiattaformaCorriere::corriereUsaAcquistoLiccardiTms($corriere)) {
            return LiccardiTmsIntegrazione::courierLdv($s) !== null
                || LiccardiTmsIntegrazione::spedizioneId($s) !== null;
        }

        return false;
    }

    public static function podeCorrigir(spedizione $s): bool
    {
        if ((bool) $s->compensata || $s->padre_comp !== null) {
            return false;
        }

        if (self::haSuccessoreCompensazione($s)) {
            return false;
        }

        $s->loadMissing('rimborso');
        if ($s->rimborso !== null) {
            return false;
        }

        if (! self::consegnaADomicilio($s)) {
            return false;
        }

        if (! self::haEtichettaEsistente($s)) {
            return false;
        }

        return self::corrierePermetteCorrecao($s);
    }

    public static function corrierePermetteCorrecao(spedizione $s): bool
    {
        $s->loadMissing('corriereRecord');
        $corriere = $s->corriereRecord;
        if (! $corriere) {
            return false;
        }

        return (bool) ($corriere->trackingsn ?? false);
    }

    public static function motivoCorrecaoDisabilitada(spedizione $s): string
    {
        if ((bool) $s->compensata) {
            return 'Etichetta sostituita: non è più modificabile.';
        }

        if ($s->padre_comp !== null) {
            return 'Questa spedizione ha già sostituito un\'altra etichetta.';
        }

        if (self::haSuccessoreCompensazione($s)) {
            return 'Esiste già una nuova etichetta che sostituisce questa spedizione.';
        }

        $s->loadMissing('rimborso');
        if ($s->rimborso !== null) {
            return 'Non è possibile correggere una spedizione con rimborso in corso.';
        }

        if (! self::consegnaADomicilio($s)) {
            return 'La correzione dati non è disponibile per consegne in punto ritiro, ufficio o locker.';
        }

        if (! self::haEtichettaEsistente($s)) {
            return 'L\'etichetta non è ancora stata generata: correzione non disponibile.';
        }

        if (! self::corrierePermetteCorrecao($s)) {
            return 'La correzione dati è disponibile solo per corrieri con tracking automatico (API).';
        }

        return 'Correzione non disponibile.';
    }

    /** Usato solo per retry etichetta pendente, non per la correzione dati. */
    private static function spedizionePagataAttiva(spedizione $s): bool
    {
        $statoId = (int) $s->spedizione_stato_id;

        if (in_array($statoId, [stato_spedizione::NON_PAGATA, stato_spedizione::ANNULLATA, stato_spedizione::RIMBORSATA], true)) {
            return false;
        }

        $s->loadMissing('ordine');

        return $s->ordine !== null && $s->ordine->haStato(ordine::STATO_PAGATO);
    }
}
