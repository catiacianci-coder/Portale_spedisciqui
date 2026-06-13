<?php

namespace App\Services\Ordine;

use App\Models\ordine;
use App\Models\spedizione;
use App\Models\stato_spedizione;
use App\Services\OrdineTotaleIvatoService;
use App\Support\LiccardiVolumeSconto;
use App\Support\OrdineTotaliPagamento;
use App\Support\RigaCarrelloOrdine;
use App\Support\TariffaSpedizioneDaRiga;

/**
 * Ricalcola righe ordine, sconto volume Liccardi e tariffe_spediziones dopo modifica spedizioni.
 */
class OrdinePrezziSincronizzazioneService
{
    public function __construct(
        private readonly OrdineTotaleIvatoService $totaleSvc,
    ) {}

    public function dopoRimozioneSpedizioni(ordine $ordine): void
    {
        if ($ordine->haStato(ordine::STATO_PAGATO)) {
            return;
        }

        $ordine->refresh();
        $ordine->loadMissing([
            'spedizioni' => fn ($q) => $q->with(['corriereRecord', 'tariffaSpedizione'])->orderBy('id'),
        ]);

        $righeAttive = $this->righeAttiveDaOrdine($ordine);
        $esitoSconto = LiccardiVolumeSconto::applicaAlCarrello($righeAttive);

        $dettaglio = is_array($ordine->dettaglio_json) ? $ordine->dettaglio_json : [];
        $dettaglio['righe'] = array_values($esitoSconto['items']);
        $dettaglio['liccardi_volume_sconto'] = [
            'applicato' => $esitoSconto['applicato'],
            'righe_liccardi' => $esitoSconto['righe_liccardi'],
            'sconto_totale' => $esitoSconto['sconto_totale'],
        ];

        $ordine->dettaglio_json = $dettaglio;
        OrdineTotaliPagamento::applicaSuOrdine(
            $ordine,
            $esitoSconto['items'],
            $this->totaleSvc->aliquotaIvaPerOrdine($ordine),
        );
        $ordine->save();

        $this->aggiornaTariffeSpedizioni($ordine, $esitoSconto['items']);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function righeAttiveDaOrdine(ordine $ordine): array
    {
        $righeOld = $ordine->dettaglio_json['righe'] ?? [];
        if (! is_array($righeOld)) {
            $righeOld = [];
        }

        $righeByCarrello = [];
        foreach ($righeOld as $r) {
            if (! is_array($r)) {
                continue;
            }
            $r = RigaCarrelloOrdine::normalizza($r);
            $key = trim((string) ($r['id'] ?? ''));
            if ($key !== '') {
                $righeByCarrello[$key] = $r;
            }
        }

        $righeAttive = [];
        foreach ($ordine->spedizioni as $sp) {
            if ((int) $sp->spedizione_stato_id === stato_spedizione::ANNULLATA) {
                continue;
            }

            $key = trim((string) ($sp->carrello_id ?? ''));
            if ($key !== '' && isset($righeByCarrello[$key])) {
                $righeAttive[] = $righeByCarrello[$key];

                continue;
            }

            // Fallback: stessa posizione nell'ordine di creazione
            $idx = $ordine->spedizioni->search(fn (spedizione $x) => (int) $x->id === (int) $sp->id);
            if ($idx !== false && isset($righeOld[$idx]) && is_array($righeOld[$idx])) {
                $righeAttive[] = RigaCarrelloOrdine::normalizza($righeOld[$idx]);
            }
        }

        return $righeAttive;
    }

    /**
     * @param  array<int, array<string, mixed>>  $righe
     */
    private function aggiornaTariffeSpedizioni(ordine $ordine, array $righe): void
    {
        $aliquotaIva = $this->totaleSvc->aliquotaIvaPerOrdine($ordine);

        $righeByCarrello = [];
        foreach ($righe as $r) {
            if (! is_array($r)) {
                continue;
            }
            $key = trim((string) ($r['id'] ?? ''));
            if ($key !== '') {
                $righeByCarrello[$key] = $r;
            }
        }

        foreach ($ordine->spedizioni as $sp) {
            if ((int) $sp->spedizione_stato_id === stato_spedizione::ANNULLATA) {
                continue;
            }

            $key = trim((string) ($sp->carrello_id ?? ''));
            $riga = $key !== '' ? ($righeByCarrello[$key] ?? null) : null;
            if (! is_array($riga)) {
                continue;
            }

            $tariffa = $sp->tariffaSpedizione;
            if ($tariffa === null) {
                continue;
            }

            $servizi = is_array($riga['servizi_selezionati'] ?? null) ? $riga['servizi_selezionati'] : [];
            $costoFornitore = (float) ($tariffa->costo_trasporto ?? $riga['prezzo_base_trasporto_iva_esc'] ?? 0);

            $tariffa->update(
                TariffaSpedizioneDaRiga::attributiDaRigaCarrello(
                    $riga,
                    $sp,
                    $costoFornitore,
                    $servizi,
                    $sp->corriereRecord,
                    $aliquotaIva,
                    0,
                ),
            );
        }
    }
}
