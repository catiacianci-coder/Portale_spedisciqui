<?php

namespace App\Services\Liccardi;

use App\Models\corriere;
use App\Models\spedizione;
use App\Support\SpedizioneCampiPersistenza;

/**
 * Mappa una spedizione portale verso l'input usato da {@see LiccardiTmsPayloadBuilder}.
 */
final class LiccardiTmsSpedizioneMapper
{
    /**
     * @return array<string, mixed>
     */
    public function buildInput(spedizione $spedizione, corriere $corriere): array
    {
        $spedizione->loadMissing('serviziAggiuntiviRighe');

        $mitt = SpedizioneCampiPersistenza::mittenteArray($spedizione);
        $dest = SpedizioneCampiPersistenza::destinatarioArray($spedizione);
        $pacco = SpedizioneCampiPersistenza::paccoArray($spedizione);

        [$viaOrigine, $civicoOrigine] = $this->viaECivico(
            $spedizione->indirizzo_o ?? ($mitt['via'] ?? $mitt['indirizzo'] ?? null),
            $spedizione->numero_o ?? ($mitt['numero'] ?? null),
        );
        [$viaDestino, $civicoDestino] = $this->viaECivico(
            $spedizione->indirizzo_d ?? ($dest['via'] ?? $dest['indirizzo'] ?? null),
            $spedizione->numero_d ?? ($dest['numero'] ?? null),
        );

        $mittNome = trim((string) (($mitt['nome'] ?? '').' '.($mitt['cognome'] ?? '')));
        $destNome = trim((string) (($dest['nome'] ?? '').' '.($dest['cognome'] ?? '')));
        $mittAzienda = trim((string) ($mitt['denominazione_impresa'] ?? $mitt['ragione_sociale'] ?? ''));
        if ($mittAzienda === '') {
            $mittAzienda = $mittNome !== '' ? $mittNome : 'Mittente';
        }

        $destAzienda = trim((string) ($dest['denominazione_impresa'] ?? $dest['ragione_sociale'] ?? ''));

        $codiceServizio = trim((string) ($corriere->codice_servizio ?? ''));
        if ($codiceServizio === '') {
            $codiceServizio = trim((string) ($corriere->istat ?? ''));
        }
        if ($codiceServizio === '') {
            $codiceServizio = trim((string) ($spedizione->codice_servizio ?? 'E'));
        }
        if ($codiceServizio === '') {
            $codiceServizio = 'E';
        }

        $servizi = $this->estraiValoriServizi($spedizione);

        $note = trim((string) ($mitt['note'] ?? ''));
        if ($note === '') {
            $note = trim((string) ($dest['note'] ?? ''));
        }

        $riferimento = trim((string) ($spedizione->codice_interno ?? ''));
        if ($riferimento === '') {
            $riferimento = 'SPQ_'.$spedizione->id;
        }

        return [
            'codice_servizio' => $codiceServizio,
            'cap_origine' => (string) ($mitt['cap'] ?? $spedizione->cap_o ?? ''),
            'citta_origine' => (string) ($mitt['comune'] ?? $spedizione->citta_o ?? ''),
            'pv_origine' => strtoupper(trim((string) ($mitt['provincia'] ?? $spedizione->stato_o ?? ''))),
            'via_origine' => $viaOrigine,
            'civico_origine' => $civicoOrigine !== '' ? $civicoOrigine : '1',
            'cap_destino' => (string) ($dest['cap'] ?? $spedizione->cap_d ?? ''),
            'citta_destino' => (string) ($dest['comune'] ?? $spedizione->citta_d ?? ''),
            'pv_destino' => strtoupper(trim((string) ($dest['provincia'] ?? $spedizione->stato_d ?? ''))),
            'via_destino' => $viaDestino,
            'civico_destino' => $civicoDestino !== '' ? $civicoDestino : '1',
            'peso' => (float) ($pacco['peso_kg'] ?? $spedizione->peso ?? 1),
            'altezza' => (float) ($pacco['altezza_cm'] ?? $spedizione->altezza ?? 15),
            'larghezza' => (float) ($pacco['larghezza_cm'] ?? $spedizione->larghezza ?? 20),
            'spessore' => (float) ($pacco['spessore_cm'] ?? $spedizione->spessore ?? 30),
            'num_colli' => 1,
            'mittente_azienda' => $mittAzienda,
            'destinatario_azienda' => $destAzienda,
            'destinatario_nome' => $destNome !== '' ? $destNome : 'Destinatario',
            'contrassegno' => $servizi['contrassegno'],
            'contrassegno_mode' => 0,
            'assicurazione' => $servizi['assicurazione'],
            'note_spedizione' => $note,
            'riferimento_cliente' => $riferimento,
            'auto_close' => '1',
            'generate_ritiro' => '1',
        ];
    }

    /**
     * @return array{contrassegno: float, assicurazione: float}
     */
    private function estraiValoriServizi(spedizione $spedizione): array
    {
        $contrassegno = 0.0;
        $assicurazione = 0.0;

        foreach ($spedizione->serviziAggiuntiviRighe as $riga) {
            $testo = mb_strtolower(trim((string) ($riga->testo_servizio ?? '')));
            $valore = max(0.0, (float) ($riga->valore_merce ?? 0));
            if ($testo === 'contrassegno') {
                $contrassegno = $valore;
            } elseif ($testo === 'assicurazione') {
                $assicurazione = $valore;
            }
        }

        return [
            'contrassegno' => $contrassegno,
            'assicurazione' => $assicurazione,
        ];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function viaECivico(mixed $indirizzo, mixed $numero): array
    {
        $via = trim((string) $indirizzo);
        $civico = trim((string) $numero);

        if ($civico !== '') {
            if ($via !== '' && preg_match('/\s+'.preg_quote($civico, '/').'$/u', $via)) {
                $via = trim((string) preg_replace('/\s+'.preg_quote($civico, '/').'$/u', '', $via));
            }

            return [$via, $civico];
        }

        if ($via === '') {
            return ['', ''];
        }

        if (preg_match('/^(.*)\s+(\d+[A-Za-z]?)$/u', $via, $m)) {
            return [trim($m[1]), trim($m[2])];
        }

        return [$via, ''];
    }
}
