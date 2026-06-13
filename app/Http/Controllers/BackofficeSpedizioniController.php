<?php

namespace App\Http\Controllers;

use App\Models\ordine;
use App\Models\spedizione;
use App\Models\tipo_spedizone;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class BackofficeSpedizioniController extends Controller
{
    public function index(Request $request)
    {
        $haRicerca = (string) $request->input('cerca', '') === '1';
        $codiceInvio = trim((string) $request->input('codice_invio', ''));
        $tracking = trim((string) $request->input('tracking', ''));
        $numeroOrdine = trim((string) $request->input('numero_ordine', ''));
        $utente = trim((string) $request->input('utente', ''));
        $mittenteNome = trim((string) $request->input('mittente_nome', ''));
        $mittenteCognome = trim((string) $request->input('mittente_cognome', ''));
        $mittenteImpresa = trim((string) $request->input('mittente_impresa', ''));
        $mittenteIndirizzo = trim((string) $request->input('mittente_indirizzo', ''));
        $mittenteCap = trim((string) $request->input('mittente_cap', ''));
        $mittenteCitta = trim((string) $request->input('mittente_citta', ''));
        $destinatarioNome = trim((string) $request->input('destinatario_nome', ''));
        $destinatarioCognome = trim((string) $request->input('destinatario_cognome', ''));
        $destinatarioImpresa = trim((string) $request->input('destinatario_impresa', ''));
        $destinatarioIndirizzo = trim((string) $request->input('destinatario_indirizzo', ''));
        $destinatarioCap = trim((string) $request->input('destinatario_cap', ''));
        $destinatarioCitta = trim((string) $request->input('destinatario_citta', ''));
        $corriereNome = trim((string) $request->input('corriere_nome', ''));
        $tipoSpedizione = (string) $request->input('tipo_spedizione', '');
        $servizio = (string) $request->input('servizio', '');
        $pagata = (string) $request->input('pagata', '');
        $period = (string) $request->input('period', '30');
        $dataDa = (string) $request->input('data_da', '');
        $dataA = (string) $request->input('data_a', '');
        $perPage = \App\Support\FiltriTabella::perPage($request);

        $allowedPeriod = ['oggi', '7', '15', '30', 'custom'];
        if (! in_array($period, $allowedPeriod, true)) {
            $period = '30';
        }

        $allowedPagata = ['', 'si', 'no'];
        if (! in_array($pagata, $allowedPagata, true)) {
            $pagata = '';
        }

        $allowedServizio = ['', 'assicurata', 'contrassegno'];
        if (! in_array($servizio, $allowedServizio, true)) {
            $servizio = '';
        }

        $filtroErrors = [];
        if ($period === 'custom') {
            if ($dataDa === '' || $dataA === '') {
                $filtroErrors[] = 'Per il periodo personalizzato servono entrambe le date (da/a).';
            } else {
                try {
                    $d1 = Carbon::createFromFormat('Y-m-d', $dataDa)->startOfDay();
                    $d2 = Carbon::createFromFormat('Y-m-d', $dataA)->endOfDay();
                    if ($d1->gt($d2)) {
                        $filtroErrors[] = 'La data "da" non può essere successiva alla data "a".';
                    }
                } catch (\Throwable) {
                    $filtroErrors[] = 'Date non valide nel periodo personalizzato.';
                }
            }
        }

        [$from, $to] = ($haRicerca && $filtroErrors === []) ? $this->intervalloFiltroSpedizioni($period, $dataDa, $dataA) : [null, null];

        if ($haRicerca) {
            $query = spedizione::query()
                ->with([
                    'user:id,email',
                    'ordine:id,codice,stato,updated_at',
                    'corriereRecord:id,nome_visualizzato,nome_corriere,piattaforma,tariffa_interna,codice_servizio',
                    'tipoSpedizione:id,tipo_spedizione',
                    'serviziAggiuntiviRighe.corriereServizioAggiuntivo:id,testo_servizio',
                    'spedizioneStato:id,denominazione_stato',
                ]);

            if ($codiceInvio !== '') {
                $query->where('codice_interno', 'like', '%'.$codiceInvio.'%');
            }

            if ($tracking !== '') {
                $query->where('tracking', 'like', '%'.$tracking.'%');
            }

            if ($numeroOrdine !== '') {
                $idOrdine = \App\Support\CodiceOrdine::idDaRiferimento($numeroOrdine);
                $query->whereHas('ordine', function ($q) use ($idOrdine): void {
                    if ($idOrdine !== null) {
                        $q->where('id', $idOrdine);
                    }
                });
            }
            if ($utente !== '') {
                $query->whereHas('user', fn ($q) => $q->where('email', 'like', '%'.$utente.'%'));
            }

            if ($mittenteNome !== '') {
                $query->where('nome_o', 'like', '%'.$mittenteNome.'%');
            }
            if ($mittenteCognome !== '') {
                $query->where('cognome_o', 'like', '%'.$mittenteCognome.'%');
            }
            if ($mittenteIndirizzo !== '') {
                $query->where('indirizzo_o', 'like', '%'.$mittenteIndirizzo.'%');
            }
            if ($mittenteCap !== '') {
                $query->where('cap_o', 'like', $mittenteCap.'%');
            }
            if ($mittenteCitta !== '') {
                $query->where('citta_o', 'like', '%'.$mittenteCitta.'%');
            }
            if ($mittenteImpresa !== '') {
                $query->where('ragione_sociale_o', 'like', '%'.$mittenteImpresa.'%');
            }

            if ($destinatarioNome !== '') {
                $query->where('nome_d', 'like', '%'.$destinatarioNome.'%');
            }
            if ($destinatarioCognome !== '') {
                $query->where('sobrenome_d', 'like', '%'.$destinatarioCognome.'%');
            }
            if ($destinatarioIndirizzo !== '') {
                $query->where('indirizzo_d', 'like', '%'.$destinatarioIndirizzo.'%');
            }
            if ($destinatarioCap !== '') {
                $query->where('cap_d', 'like', $destinatarioCap.'%');
            }
            if ($destinatarioCitta !== '') {
                $query->where('citta_d', 'like', '%'.$destinatarioCitta.'%');
            }
            if ($destinatarioImpresa !== '') {
                $query->where('ragione_sociale_d', 'like', '%'.$destinatarioImpresa.'%');
            }
            if ($corriereNome !== '') {
                $query->where(function ($q) use ($corriereNome): void {
                    $q->where('corriere', 'like', '%'.$corriereNome.'%')
                        ->orWhereHas('corriereRecord', fn ($c) => $c->where('nome_corriere', 'like', '%'.$corriereNome.'%'));
                });
            }

            if ($tipoSpedizione !== '') {
                $query->where('tipo_id', (int) $tipoSpedizione);
            }

            if ($servizio !== '') {
                $needle = $servizio === 'assicurata' ? '%assicur%' : '%contrassegno%';
                $query->whereHas('serviziAggiuntiviRighe', function ($q) use ($needle): void {
                    $q->where('testo_servizio', 'like', $needle)
                        ->orWhereHas('corriereServizioAggiuntivo', fn ($c) => $c->where('testo_servizio', 'like', $needle));
                });
            }

            if ($pagata === 'si') {
                $query->whereHas('ordine', fn ($q) => $q->conStatoCodice(ordine::STATO_PAGATO));
            } elseif ($pagata === 'no') {
                $query->whereHas('ordine', fn ($q) => $q->whereIn('stato_ordine_id', [
                    ordine::statoId(ordine::STATO_NON_PAGATO),
                    ordine::statoId(ordine::STATO_ANNULLATO),
                ]));
            }

            if ($from !== null && $to !== null) {
                $query->whereBetween('created_at', [$from, $to]);
            }

            $spedizioni = $query
                ->orderByDesc('id')
                ->paginate($perPage)
                ->withQueryString();
        } else {
            $spedizioni = new \Illuminate\Pagination\LengthAwarePaginator(
                collect(),
                0,
                $perPage,
                1,
                ['path' => $request->url(), 'query' => $request->query()]
            );
        }

        $tipiSpedizione = tipo_spedizone::query()
            ->orderBy('tipo_spedizione')
            ->get(['id', 'tipo_spedizione']);

        $corrieriNomi = \App\Models\corriere::query()
            ->select('nome_corriere')
            ->whereNotNull('nome_corriere')
            ->where('nome_corriere', '!=', '')
            ->distinct()
            ->orderBy('nome_corriere')
            ->pluck('nome_corriere');

        $utentiEmails = User::query()
            ->select('email')
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->distinct()
            ->orderBy('email')
            ->limit(1500)
            ->pluck('email');

        return view('backoffice.spedizioni', [
            'spedizioni' => $spedizioni,
            'tipiSpedizione' => $tipiSpedizione,
            'corrieriNomi' => $corrieriNomi,
            'utentiEmails' => $utentiEmails,
            'filtroCodiceInvio' => $codiceInvio,
            'filtroTracking' => $tracking,
            'filtroNumeroOrdine' => $numeroOrdine,
            'filtroUtente' => $utente,
            'filtroMittenteNome' => $mittenteNome,
            'filtroMittenteCognome' => $mittenteCognome,
            'filtroMittenteImpresa' => $mittenteImpresa,
            'filtroMittenteIndirizzo' => $mittenteIndirizzo,
            'filtroMittenteCap' => $mittenteCap,
            'filtroMittenteCitta' => $mittenteCitta,
            'filtroDestinatarioNome' => $destinatarioNome,
            'filtroDestinatarioCognome' => $destinatarioCognome,
            'filtroDestinatarioImpresa' => $destinatarioImpresa,
            'filtroDestinatarioIndirizzo' => $destinatarioIndirizzo,
            'filtroDestinatarioCap' => $destinatarioCap,
            'filtroDestinatarioCitta' => $destinatarioCitta,
            'filtroCorriereNome' => $corriereNome,
            'filtroTipoSpedizione' => $tipoSpedizione,
            'filtroServizio' => $servizio,
            'filtroPagata' => $pagata,
            'filtroPeriod' => $period,
            'filtroDataDa' => $dataDa,
            'filtroDataA' => $dataA,
            'perPage' => $perPage,
            'filtroErrors' => $filtroErrors,
            'haRicerca' => $haRicerca,
            'queryParams' => \App\Support\FiltriTabella::parametriQuery($request, ['page']),
        ]);
    }

    /**
     * @return array{0: ?Carbon, 1: ?Carbon}
     */
    private function intervalloFiltroSpedizioni(string $period, string $dataDa, string $dataA): array
    {
        $now = now();

        return match ($period) {
            'oggi' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            '7' => [$now->copy()->subDays(7)->startOfDay(), $now->copy()->endOfDay()],
            '15' => [$now->copy()->subDays(15)->startOfDay(), $now->copy()->endOfDay()],
            '30' => [$now->copy()->subDays(30)->startOfDay(), $now->copy()->endOfDay()],
            'custom' => [
                Carbon::createFromFormat('Y-m-d', $dataDa)->startOfDay(),
                Carbon::createFromFormat('Y-m-d', $dataA)->endOfDay(),
            ],
            default => [null, null],
        };
    }
}
