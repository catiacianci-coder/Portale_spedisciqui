<?php

namespace App\Http\Controllers;

use App\Models\comune;
use App\Models\spedizione;
use App\Models\tariffa;
use App\Support\RigaCarrelloOrdine;
use App\Support\SpedizioneCampiPersistenza;
use App\Models\tipo_spedizone;
use App\Services\TariffaPrezzoBaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ResiController extends Controller
{
    public function index()
    {
        return view('resi.index', [
            'spedizioneFound' => null,
            'searchInput' => ['tracking' => '', 'codice_interno' => ''],
        ]);
    }

    public function search(Request $request)
    {
        $validated = $request->validate([
            'tracking' => ['nullable', 'string', 'max:128', 'required_without:codice_interno'],
            'codice_interno' => ['nullable', 'string', 'max:40', 'required_without:tracking'],
        ]);

        $tracking = trim((string) ($validated['tracking'] ?? ''));
        $codiceInterno = trim((string) ($validated['codice_interno'] ?? ''));

        $uid = (int) $request->user()->id;
        $q = spedizione::query()
            ->where('user_id', $uid)
            ->with(['corriereRecord', 'tipoSpedizione', 'ordine'])
            ->orderByDesc('id');

        if ($codiceInterno !== '') {
            $q->whereRaw('UPPER(codice_interno) = ?', [mb_strtoupper($codiceInterno)]);
        } else {
            $q->where('tracking', 'like', '%'.$tracking.'%');
        }

        $sped = $q->first();
        if (! $sped) {
            return back()->withErrors(['resi' => 'Spedizione non trovata con i dati inseriti.'])->withInput();
        }

        return view('resi.index', [
            'spedizioneFound' => $sped,
            'searchInput' => [
                'tracking' => $tracking,
                'codice_interno' => $codiceInterno,
            ],
        ]);
    }

    public function creaLetteraVettura(Request $request, spedizione $spedizione): RedirectResponse
    {
        $this->authorize('view', $spedizione);

        $spedizione->loadMissing(['corriereRecord', 'tipoSpedizione', 'ordine']);
        if (! $spedizione->corriereRecord) {
            return redirect()->route('resi.index')->withErrors(['resi' => 'Dati corriere mancanti sulla spedizione selezionata.']);
        }

        $payload = $this->buildPreventivoResoPayload($spedizione);
        $request->session()->put('preventivo', $payload);

        return redirect()->route('spedizione.indirizzi', ['corriere' => (int) $spedizione->id_codice_servizio]);
    }

    /** @return array<string, mixed> */
    private function buildPreventivoResoPayload(spedizione $s): array
    {
        $mittOrig = $this->addressFromSpedizione($s, true);
        $destOrig = $this->addressFromSpedizione($s, false);

        $partenza = $destOrig;
        $destinazione = $mittOrig;
        $partenza['note'] = '';
        $destinazione['note'] = '';

        $capOrig = $this->normalizeCap((string) ($partenza['cap'] ?? ''));
        $capDest = $this->normalizeCap((string) ($destinazione['cap'] ?? ''));

        $comuneOrig = $this->findComuneByCapCittaProvincia($capOrig, (string) ($partenza['comune'] ?? ''), (string) ($partenza['provincia'] ?? ''));
        $comuneDest = $this->findComuneByCapCittaProvincia($capDest, (string) ($destinazione['comune'] ?? ''), (string) ($destinazione['provincia'] ?? ''));

        $peso = (float) ($s->peso ?? 0);
        $altezza = (float) ($s->altezza ?? 0);
        $larghezza = (float) ($s->larghezza ?? 0);
        $spessore = (float) ($s->spessore ?? 0);

        $misure = [$altezza, $larghezza, $spessore];
        rsort($misure, SORT_NUMERIC);
        $latoMax = (float) ($misure[0] ?? 0);
        $latoMed = (float) ($misure[1] ?? 0);
        $latoMin = (float) ($misure[2] ?? 0);
        $sommaLati = $latoMax + $latoMed + $latoMin;

        $tipoSpedId = (int) ($s->tipo_id ?? 0);
        $tipoSped = $tipoSpedId > 0 ? tipo_spedizone::query()->find($tipoSpedId) : $s->tipoSpedizione;

        $trow = $this->tariffaDaOrdine($s);
        $prezzoTrasporto = (float) (SpedizioneCampiPersistenza::prezzoNettoDaOrdine($s) ?? 0);

        $baseTrasporto = $s->corriereRecord && $trow
            ? TariffaPrezzoBaseService::prezzoBase(
                $trow,
                $s->corriereRecord,
                $comuneOrig ? (string) $comuneOrig->regione : null,
                $comuneDest ? (string) $comuneDest->regione : null
            )
            : ($trow?->tariffa !== null ? (float) $trow->tariffa : $prezzoTrasporto);

        return [
            'version' => 1,
            'created_at' => now()->toIso8601String(),
            'reso' => true,
            'reso_source_spedizione_id' => (int) $s->id,
            'tipo_spedizione' => $tipoSped ? $tipoSped->toArray() : ['id' => $tipoSpedId > 0 ? $tipoSpedId : null],
            'origine' => $comuneOrig ? $comuneOrig->toArray() : [
                'id' => null,
                'cap' => $capOrig,
                'comune' => (string) ($partenza['comune'] ?? ''),
                'provincia' => strtoupper(substr((string) ($partenza['provincia'] ?? ''), 0, 2)),
            ],
            'destino' => $comuneDest ? $comuneDest->toArray() : [
                'id' => null,
                'cap' => $capDest,
                'comune' => (string) ($destinazione['comune'] ?? ''),
                'provincia' => strtoupper(substr((string) ($destinazione['provincia'] ?? ''), 0, 2)),
            ],
            'input' => [
                'id_tipo_spediziones' => $tipoSpedId > 0 ? $tipoSpedId : null,
                'ambito_spedizione' => 'nazionale',
                'cap_origine' => $capOrig,
                'cap_destino' => $capDest,
                'id_comune_origine' => $comuneOrig?->id,
                'id_comune_destino' => $comuneDest?->id,
                'altezza' => $altezza,
                'larghezza' => $larghezza,
                'spessore' => $spessore,
                'peso' => $peso,
            ],
            'misure' => [
                'lato_max' => $latoMax,
                'lato_med' => $latoMed,
                'lato_min' => $latoMin,
                'somma_lati' => $sommaLati,
                'perimetro_geometrico' => 2 * $sommaLati,
            ],
            'righe' => [[
                'corriere' => $s->corriereRecord->toArray(),
                'ok_tratta' => true,
                'motivo_tratta' => null,
                'tariffa' => $trow ? $trow->toArray() : [],
                'motivo_tariffa' => null,
                'prezzo_base' => $baseTrasporto,
                'prezzo_finale' => $prezzoTrasporto,
                'prezzo_wallet' => null,
                'wallet_discount_pct' => null,
                'wallet_modifier_pct' => null,
            ]],
            'indirizzi' => [
                'corriere_id' => (int) $s->id_codice_servizio,
                'mittente_rubrica_id' => null,
                'partenza' => $partenza,
                'destinazione' => $destinazione,
                'updated_at' => now()->toIso8601String(),
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function tariffaDaOrdine(spedizione $s): ?tariffa
    {
        $s->loadMissing('ordine');
        $righe = $s->ordine?->dettaglio_json['righe'] ?? [];
        if (! is_array($righe)) {
            return null;
        }
        $carrelloId = trim((string) ($s->carrello_id ?? ''));
        foreach ($righe as $r) {
            if (! is_array($r)) {
                continue;
            }
            $r = RigaCarrelloOrdine::normalizza($r);
            if ($carrelloId !== '' && (string) ($r['id'] ?? '') !== $carrelloId) {
                continue;
            }
            $tid = (int) ($r['id_tariffas'] ?? 0);

            return $tid > 0 ? tariffa::query()->find($tid) : null;
        }

        return null;
    }

    private function addressFromSpedizione(spedizione $s, bool $mittente): array
    {
        $json = $mittente
            ? SpedizioneCampiPersistenza::mittenteArray($s)
            : SpedizioneCampiPersistenza::destinatarioArray($s);

        $nome = trim((string) ($json['nome'] ?? ''));
        $cognome = trim((string) ($json['cognome'] ?? ''));
        $via = trim((string) ($json['via'] ?? $json['indirizzo'] ?? ''));
        $numero = trim((string) ($json['numero'] ?? ''));
        $cap = $this->normalizeCap((string) ($json['cap'] ?? ''));
        $comune = trim((string) ($json['comune'] ?? ''));
        $prov = strtoupper(substr(trim((string) ($json['provincia'] ?? '')), 0, 2));

        return [
            'nome' => $nome,
            'cognome' => $cognome,
            'cap' => $cap,
            'comune' => $comune,
            'provincia' => $prov,
            'via' => $via,
            'numero' => $numero,
            'indirizzo' => trim($via.($via !== '' && $numero !== '' ? ' ' : '').$numero),
            'telefono' => trim((string) ($json['telefono'] ?? '')),
            'email' => trim((string) ($json['email'] ?? '')),
            'denominazione_impresa' => trim((string) ($json['denominazione_impresa'] ?? $json['denominazione_ragione_sociale'] ?? '')),
            'note' => '',
            'nome_destinatario' => trim($nome.' '.$cognome),
        ];
    }

    private function normalizeCap(string $raw): string
    {
        return str_pad(preg_replace('/\D/', '', $raw), 5, '0', STR_PAD_LEFT);
    }

    private function findComuneByCapCittaProvincia(string $capNorm, string $citta, string $provincia): ?comune
    {
        if (strlen($capNorm) !== 5) {
            return null;
        }
        $cittaNorm = mb_strtolower(trim($citta));
        $prov = strtoupper(substr(trim($provincia), 0, 2));
        if ($cittaNorm === '' || strlen($prov) !== 2) {
            return null;
        }

        return comune::query()
            ->whereRaw('UPPER(LEFT(TRIM(provincia), 2)) = ?', [$prov])
            ->get()
            ->first(function ($c) use ($capNorm, $cittaNorm) {
                $capDb = str_pad(preg_replace('/\D/', '', (string) $c->cap), 5, '0', STR_PAD_LEFT);

                return $capDb === $capNorm && mb_strtolower(trim((string) $c->comune)) === $cittaNorm;
            });
    }
}
