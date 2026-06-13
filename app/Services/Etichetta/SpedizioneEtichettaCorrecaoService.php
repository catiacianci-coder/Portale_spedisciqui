<?php

namespace App\Services\Etichetta;

use App\Models\spedizione;
use App\Models\spedizione_servizio_aggiuntivi;
use App\Models\stato_spedizione;
use App\Models\tariffa_spedizione;
use App\Services\Tracking\SpedizioneTrackingService;
use App\Support\SpedizioneEtichettaStato;
use DomainException;
use Illuminate\Support\Facades\DB;

final class SpedizioneEtichettaCorrecaoService
{
    public function __construct(
        private readonly EtichettaAcquistoSingoloService $acquisto,
        private readonly SpedizioneTrackingService $tracking,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function datiPerModal(spedizione $s, int $userId): array
    {
        $this->assertCorrigivel($s, $userId);
        $this->tracking->assertEtichettaNonSpeditaPerCorrecao($s);

        return [
            'codice_interno' => (string) ($s->codice_interno ?? ''),
            'nome_d' => (string) ($s->nome_d ?? ''),
            'sobrenome_d' => (string) ($s->sobrenome_d ?? ''),
            'ragione_sociale_d' => (string) ($s->ragione_sociale_d ?? ''),
            'indirizzo_d' => (string) ($s->indirizzo_d ?? ''),
            'numero_d' => (string) ($s->numero_d ?? ''),
            'frazione_d' => (string) ($s->frazione_d ?? ''),
            'cap_d' => (string) ($s->cap_d ?? ''),
            'citta_d' => (string) ($s->citta_d ?? ''),
            'stato_d' => (string) ($s->stato_d ?? ''),
            'tel_d' => (string) ($s->tel_d ?? ''),
            'note_d' => (string) ($s->note_d ?? ''),
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function salvaEGeneraNuova(spedizione $vecchia, int $userId, array $input): spedizione
    {
        $this->assertCorrigivel($vecchia, $userId);

        $normalized = [
            'nome_d' => trim((string) ($input['nome_d'] ?? '')),
            'sobrenome_d' => trim((string) ($input['sobrenome_d'] ?? '')),
            'indirizzo_d' => trim((string) ($input['indirizzo_d'] ?? '')),
            'numero_d' => trim((string) ($input['numero_d'] ?? '')),
            'frazione_d' => trim((string) ($input['frazione_d'] ?? '')),
            'tel_d' => trim((string) ($input['tel_d'] ?? '')),
            'note_d' => trim((string) ($input['note_d'] ?? '')),
        ];

        $haRagioneSociale = trim((string) ($vecchia->ragione_sociale_d ?? '')) !== '';

        if (! $haRagioneSociale && ($normalized['nome_d'] === '' || $normalized['sobrenome_d'] === '')) {
            throw new DomainException('Nome e cognome destinatario sono obbligatori.');
        }

        if ($normalized['indirizzo_d'] === '' || $normalized['numero_d'] === '' || $normalized['tel_d'] === '') {
            throw new DomainException('Indirizzo, numero civico e telefono destinatario sono obbligatori.');
        }

        if (! $this->haDifferenza($vecchia, $normalized)) {
            throw new DomainException('Non ci sono modifiche rispetto ai dati attuali.');
        }

        $this->tracking->assertEtichettaNonSpeditaPerCorrecao($vecchia);

        $delete = $this->acquisto->eliminaSePresente($vecchia);
        if (! $delete['ok']) {
            throw new DomainException($delete['message']);
        }

        $nova = null;

        DB::transaction(function () use ($vecchia, $normalized, &$nova): void {
            $vecchia->refresh();

            if (SpedizioneEtichettaStato::haSuccessoreCompensazione($vecchia)) {
                throw new DomainException('Questa spedizione è già stata sostituita.');
            }

            $vecchia->forceFill([
                'compensata' => true,
                'spedizione_stato_id' => stato_spedizione::ANNULLATA,
            ])->save();

            $nova = $vecchia->replicate([
                'id',
                'codice_interno',
                'created_at',
                'updated_at',
                'compensata',
                'padre_comp',
                'padre_reso',
                'cancellata_il',
            ]);

            $nova->forceFill([
                'compensata' => false,
                'padre_comp' => $vecchia->id,
                'spedizione_stato_id' => stato_spedizione::PAGATA,
                'esiste_integrazione' => false,
                'tracking' => null,
                'etiqueta_pdf_path' => null,
                'id_shipment' => null,
                'ldverro' => false,
                'ldv_emessa_il' => null,
                'nome_d' => $normalized['nome_d'] !== '' ? $normalized['nome_d'] : null,
                'sobrenome_d' => $normalized['sobrenome_d'] !== '' ? $normalized['sobrenome_d'] : null,
                'indirizzo_d' => $normalized['indirizzo_d'],
                'numero_d' => $normalized['numero_d'],
                'frazione_d' => $normalized['frazione_d'] !== '' ? $normalized['frazione_d'] : null,
                'tel_d' => $normalized['tel_d'],
                'note_d' => $normalized['note_d'] !== '' ? $normalized['note_d'] : null,
            ])->save();

            $tariffa = tariffa_spedizione::query()->where('spedizione_id', $vecchia->id)->first();
            if ($tariffa !== null) {
                $tNuova = $tariffa->replicate(['id', 'spedizione_id', 'created_at', 'updated_at']);
                $tNuova->spedizione_id = $nova->id;
                $tNuova->save();
            }

            spedizione_servizio_aggiuntivi::query()
                ->where('id_spedizionis', $vecchia->id)
                ->get()
                ->each(function (spedizione_servizio_aggiuntivi $riga) use ($nova): void {
                    $copy = $riga->replicate(['id', 'id_spedizionis', 'created_at', 'updated_at']);
                    $copy->id_spedizionis = $nova->id;
                    $copy->save();
                });
        });

        if (! $nova instanceof spedizione) {
            throw new \RuntimeException('Impossibile creare la nuova spedizione.');
        }

        $nova = $nova->fresh();
        $outcome = $this->acquisto->genera($nova);

        if (! $outcome['ok']) {
            throw new DomainException($outcome['message']);
        }

        return $nova->fresh();
    }

    private function assertCorrigivel(spedizione $s, int $userId): void
    {
        if ((int) $s->user_id !== $userId) {
            throw new DomainException('Spedizione non trovata.');
        }

        if (! SpedizioneEtichettaStato::podeCorrigir($s)) {
            throw new DomainException(SpedizioneEtichettaStato::motivoCorrecaoDisabilitada($s));
        }
    }

    /**
     * @param  array{nome_d: string, sobrenome_d: string, indirizzo_d: string, numero_d: string, frazione_d: string, tel_d: string, note_d: string}  $normalized
     */
    private function haDifferenza(spedizione $s, array $normalized): bool
    {
        return trim((string) ($s->nome_d ?? '')) !== $normalized['nome_d']
            || trim((string) ($s->sobrenome_d ?? '')) !== $normalized['sobrenome_d']
            || trim((string) ($s->indirizzo_d ?? '')) !== $normalized['indirizzo_d']
            || trim((string) ($s->numero_d ?? '')) !== $normalized['numero_d']
            || trim((string) ($s->frazione_d ?? '')) !== $normalized['frazione_d']
            || trim((string) ($s->tel_d ?? '')) !== $normalized['tel_d']
            || trim((string) ($s->note_d ?? '')) !== $normalized['note_d'];
    }
}
