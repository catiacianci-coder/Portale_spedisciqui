<?php

namespace App\Http\Controllers;

use App\Models\ordine;
use App\Models\spedizione;
use App\Models\tipo_spedizone;
use App\Models\User;
use App\Services\Etichetta\BackofficeSpedizioneEtichettaService;
use App\Support\EtichetteListing;
use Carbon\Carbon;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BackofficeSpedizioniController extends Controller
{
    /**
     * @var list<string>
     */
    private const REMESSA_EDITABLE_FIELD_KEYS = [
        'nome_o', 'cognome_o', 'ragione_sociale_o', 'cap_o', 'citta_o', 'indirizzo_o', 'numero_o', 'frazione_o', 'stato_o', 'tel_o', 'email_o', 'note_o',
        'nome_d', 'sobrenome_d', 'ragione_sociale_d', 'cap_d', 'citta_d', 'indirizzo_d', 'numero_d', 'frazione_d', 'stato_d', 'tel_d', 'email_d', 'note_d',
        'tipo_id', 'codice_servizio', 'service_description', 'corriere', 'id_shipment',
        'altezza', 'larghezza', 'spessore', 'peso',
    ];

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
                    'ordine:id,stato_ordine_id,cr,updated_at',
                    'ordine.statoOrdine:id,codice,denominazione',
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
                if ($idOrdine !== null) {
                    $query->where('ordine_id', $idOrdine);
                } else {
                    $query->whereRaw('0 = 1');
                }
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

    public function dettaglio(spedizione $spedizione): View
    {
        $spedizione->loadMissing(['ordine.user', 'user']);

        return view('backoffice.spedizioni.partials.dettaglio-remessa-modal', [
            's' => $spedizione,
            'd' => EtichetteListing::dettaglioPayloadBackoffice($spedizione),
        ]);
    }

    public function opcoes(spedizione $spedizione, BackofficeSpedizioneEtichettaService $svc): View
    {
        try {
            $svc->assertModificabile($spedizione);
        } catch (DomainException $e) {
            abort(403, $e->getMessage());
        }

        $spedizione->loadMissing(['ordine.user', 'user', 'corriereRecord']);
        $dettaglio = EtichetteListing::dettaglioPayloadBackoffice($spedizione);

        return view('backoffice.spedizioni.partials.opzioni-remessa-modal', [
            's' => $spedizione,
            'servicoNome' => EtichetteListing::nomeServizio($spedizione),
            'fieldLabels' => self::remessaFieldLabelsIt(),
            'detalheUrl' => route('backoffice.spedizioni.dettaglio', $spedizione),
            'retryUrl' => $dettaglio['retry_url_bo'],
        ]);
    }

    public function update(Request $request, spedizione $spedizione, BackofficeSpedizioneEtichettaService $svc): RedirectResponse
    {
        try {
            $svc->assertModificabile($spedizione);
        } catch (DomainException $e) {
            return redirect()
                ->back(fallback: route('backoffice.spedizioni.index'))
                ->with('error', $e->getMessage());
        }

        $rules = [];
        foreach (self::REMESSA_EDITABLE_FIELD_KEYS as $key) {
            $rules[$key] = match ($key) {
                'tipo_id', 'id_shipment' => 'nullable|integer|min:0',
                'altezza', 'larghezza', 'spessore', 'peso' => 'nullable|numeric|min:0',
                default => 'nullable|string|max:512',
            };
        }

        $data = $request->validate($rules);

        foreach (self::REMESSA_EDITABLE_FIELD_KEYS as $key) {
            if (! array_key_exists($key, $data)) {
                continue;
            }
            $val = $data[$key];
            if ($val === '' || $val === null) {
                $spedizione->{$key} = null;

                continue;
            }
            if (in_array($key, ['tipo_id', 'id_shipment'], true)) {
                $intVal = (int) $val;
                $spedizione->{$key} = $intVal > 0 ? $intVal : null;

                continue;
            }
            if (in_array($key, ['altezza', 'larghezza', 'spessore', 'peso'], true)) {
                $spedizione->{$key} = $val;

                continue;
            }
            $spedizione->{$key} = $val;
        }

        $spedizione->save();

        return redirect()
            ->back(fallback: route('backoffice.spedizioni.index'))
            ->with('ok', 'Dati spedizione #'.$spedizione->id.' aggiornati.');
    }

    public function manual(Request $request, spedizione $spedizione, BackofficeSpedizioneEtichettaService $svc): RedirectResponse
    {
        $request->validate([
            'codigo_rastreio' => 'nullable|string|max:80',
            'arquivo_etiqueta' => 'nullable|file|mimes:pdf|max:10240',
        ]);

        try {
            $outcome = $svc->salvaManuale(
                $spedizione,
                $request->input('codigo_rastreio'),
                $request->file('arquivo_etiqueta'),
            );
        } catch (DomainException $e) {
            return redirect()
                ->back(fallback: route('backoffice.spedizioni.index'))
                ->with('error', $e->getMessage());
        }

        $tipo = $outcome['ok'] ? 'ok' : 'error';

        return redirect()
            ->back(fallback: route('backoffice.spedizioni.index'))
            ->with($tipo, $outcome['message'])
            ->withInput($outcome['ok'] ? [] : $request->except('arquivo_etiqueta'));
    }

    public function retry(spedizione $spedizione, BackofficeSpedizioneEtichettaService $svc): RedirectResponse
    {
        try {
            $outcome = $svc->retry($spedizione);
        } catch (DomainException $e) {
            return redirect()
                ->back(fallback: route('backoffice.spedizioni.index'))
                ->with('error', $e->getMessage());
        }

        $tipo = $outcome['ok'] ? 'ok' : 'error';

        return redirect()
            ->back(fallback: route('backoffice.spedizioni.index'))
            ->with($tipo, $outcome['message']);
    }

    /**
     * @return array<string, string>
     */
    private static function remessaFieldLabelsIt(): array
    {
        return [
            'nome_o' => 'Mittente — nome',
            'cognome_o' => 'Mittente — cognome',
            'ragione_sociale_o' => 'Mittente — ragione sociale',
            'cap_o' => 'Mittente — CAP',
            'citta_o' => 'Mittente — città',
            'indirizzo_o' => 'Mittente — indirizzo',
            'numero_o' => 'Mittente — numero civico',
            'frazione_o' => 'Mittente — frazione',
            'stato_o' => 'Mittente — provincia',
            'tel_o' => 'Mittente — telefono',
            'email_o' => 'Mittente — e-mail',
            'note_o' => 'Mittente — note',
            'nome_d' => 'Destinatario — nome',
            'sobrenome_d' => 'Destinatario — cognome',
            'ragione_sociale_d' => 'Destinatario — ragione sociale',
            'cap_d' => 'Destinatario — CAP',
            'citta_d' => 'Destinatario — città',
            'indirizzo_d' => 'Destinatario — indirizzo',
            'numero_d' => 'Destinatario — numero civico',
            'frazione_d' => 'Destinatario — frazione',
            'stato_d' => 'Destinatario — provincia',
            'tel_d' => 'Destinatario — telefono',
            'email_d' => 'Destinatario — e-mail',
            'note_d' => 'Destinatario — note',
            'tipo_id' => 'Tipo spedizione (ID)',
            'codice_servizio' => 'Codice servizio',
            'service_description' => 'Descrizione servizio',
            'corriere' => 'Corriere',
            'id_shipment' => 'ID shipment (interno)',
            'altezza' => 'Altezza (cm)',
            'larghezza' => 'Larghezza (cm)',
            'spessore' => 'Spessore (cm)',
            'peso' => 'Peso (kg)',
        ];
    }
}
