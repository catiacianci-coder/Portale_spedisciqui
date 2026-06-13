<?php

namespace App\Http\Controllers;

use App\Models\comune;
use App\Models\destinatario;
use App\Models\mittenza;
use App\Models\User;
use App\Services\Preventivo\PreventivoRigaPrezzoService;
use App\Services\UserMittenzeService;
use App\Support\IndirizzoSpedizioneSnapshot;
use App\Support\PreventivoPrezziEsposti;
use App\Support\PreventivoRigaSelezionabile;
use App\Support\PuntoConsegnaSessione;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class IndirizziSpedizioneController extends Controller
{
    public function __construct(
        private UserMittenzeService $capService,
    ) {}

    public function show(Request $request)
    {
        $preventivo = $request->session()->get('preventivo');
        $corriereId = (int) $request->query('corriere', 0);

        if (! $preventivo || $corriereId < 1) {
            return redirect()
                ->route('preventivi')
                ->withErrors(['indirizzi' => 'Nessun preventivo attivo o corriere non valido.']);
        }

        $riga = PreventivoRigaSelezionabile::trovaRiga($preventivo, $corriereId);
        if (! $riga) {
            abort(404);
        }

        $prezzoSvc = app(PreventivoRigaPrezzoService::class);
        $esitoPrezzo = $prezzoSvc->aggiornaSessione($preventivo, $corriereId);
        if (! ($esitoPrezzo['ok'] ?? false)) {
            return redirect()
                ->route('preventivi')
                ->withErrors(['indirizzi' => $esitoPrezzo['error'] ?? 'Impossibile confermare i prezzi del corriere selezionato.']);
        }
        PreventivoPrezziEsposti::aggiornaDaRiga($preventivo, $corriereId);
        $request->session()->put('preventivo', $preventivo);
        $riga = PreventivoRigaSelezionabile::trovaRiga($preventivo, $corriereId);
        if (! $riga) {
            abort(404);
        }

        $orig = $preventivo['origine'] ?? [];
        $dst = $preventivo['destino'] ?? [];
        $input = $preventivo['input'] ?? [];

        $capPartenza = $input['cap_origine'] ?? ($orig['cap'] ?? '');
        $capArrivo = $input['cap_destino'] ?? ($dst['cap'] ?? '');

        $ind = $preventivo['indirizzi'] ?? [];
        if (! is_array($ind)) {
            $ind = [];
        }
        $part = is_array($ind['partenza'] ?? null) ? $ind['partenza'] : [];
        $dest = is_array($ind['destinazione'] ?? null) ? $ind['destinazione'] : [];

        $destinatariRubrica = [];
        $u = $request->user();
        $isImpresaUtenteIndirizzi = $u && $u->hasVerifiedEmail() && (string) ($u->tipo_utente ?? 'privato') !== 'privato';
        $capNormPartenza = $this->capService->normalizzaCap((string) $capPartenza);
        $capNormArrivo = $this->capService->normalizzaCap((string) $capArrivo);
        $mittentiPerCap = $this->mittenzeUtentePerCapPartenza($u, $capNormPartenza);
        $mittentiRubrica = $mittentiPerCap->map(static fn (mittenza $m) => [
            'id' => (int) $m->id,
            'nome' => (string) ($m->nome ?? ''),
            'cognome' => (string) ($m->cognome ?? ''),
            'denominazione_ragione_sociale' => (string) ($m->denominazione_ragione_sociale ?? ''),
            'telefono' => (string) ($m->telefono ?? ''),
            'email' => (string) ($m->email ?? ''),
            'indirizzo' => (string) ($m->indirizzo ?? ''),
            'civico' => (string) ($m->civico ?? ''),
            'is_preferito' => (bool) $m->is_preferito,
        ])->values()->all();

        $selectedRubricaId = (int) old('mittente_rubrica_id', (string) ($ind['mittente_rubrica_id'] ?? 0));
        $mSorg = ($selectedRubricaId > 0) ? $mittentiPerCap->firstWhere('id', $selectedRubricaId) : null;

        if ($u && $u->hasVerifiedEmail()) {
            $this->capService->ensureForUser($u);
            $destinatariRubrica = $this->destinatariUtentePerCapDestino($u, $capNormArrivo)
                ->map(static fn (destinatario $d) => [
                    'id' => (int) $d->id,
                    'nome' => (string) ($d->nome ?? ''),
                    'cognome' => (string) ($d->cognome ?? ''),
                    'denominazione_ragione_sociale' => (string) ($d->denominazione_ragione_sociale ?? ''),
                    'telefono' => (string) ($d->telefono ?? ''),
                    'email' => (string) ($d->email ?? ''),
                    'indirizzo' => (string) ($d->indirizzo ?? ''),
                    'civico' => (string) ($d->civico ?? ''),
                ])
                ->values()
                ->all();
        }

        $destNome = trim((string) ($dest['nome'] ?? ''));
        $destCognome = trim((string) ($dest['cognome'] ?? ''));
        if ($destNome === '' && $destCognome === '') {
            $leg = trim((string) ($dest['nome_destinatario'] ?? ''));
            if ($leg !== '') {
                $destNome = $leg;
            }
        }

        $fallbackNome = '';
        $fallbackCognome = '';
        if ($mSorg) {
            $fallbackNome = trim((string) ($mSorg->nome ?? ''));
            $fallbackCognome = trim((string) ($mSorg->cognome ?? ''));
            if ($fallbackNome === '' && trim((string) ($mSorg->denominazione_ragione_sociale ?? '')) !== '') {
                $fallbackNome = trim((string) $mSorg->denominazione_ragione_sociale);
            }
        }

        $partNome = trim((string) ($part['nome'] ?? ''));
        $partCognome = trim((string) ($part['cognome'] ?? ''));
        $partVia = trim((string) ($part['via'] ?? ''));
        $partNumero = trim((string) ($part['numero'] ?? ''));
        $partTel = trim((string) ($part['telefono'] ?? ''));
        $partEmail = trim((string) ($part['email'] ?? ''));

        if ($mSorg) {
            $defTel = $partTel !== '' ? $partTel : trim((string) ($mSorg->telefono ?? ''));
            $defEmail = $partEmail !== '' ? $partEmail : trim((string) ($mSorg->email ?? ''));
        } else {
            $defTel = $partTel;
            $defEmail = $partEmail;
        }

        $savedDenomDest = trim((string) ($dest['denominazione_impresa'] ?? $dest['denominazione_ragione_sociale'] ?? ''));
        $prefDenomMittente = ($isImpresaUtenteIndirizzi && $mSorg)
            ? trim((string) ($mSorg->denominazione_ragione_sociale ?? ''))
            : '';
        $defaultDenomDest = $isImpresaUtenteIndirizzi
            ? ($savedDenomDest !== '' ? $savedDenomDest : $prefDenomMittente)
            : '';

        $mittenteDenominazioneChiuso = $mSorg && trim((string) ($mSorg->denominazione_ragione_sociale ?? '')) === '';
        $savedDenomMitt = trim((string) ($part['denominazione_impresa'] ?? $part['denominazione_ragione_sociale'] ?? ''));
        $prefDenomMittRubrica = $mSorg ? trim((string) ($mSorg->denominazione_ragione_sociale ?? '')) : '';
        $defaultDenomMitt = $mittenteDenominazioneChiuso
            ? ''
            : ($savedDenomMitt !== '' ? $savedDenomMitt : $prefDenomMittRubrica);

        $values = [
            'denominazione_mittente' => $mittenteDenominazioneChiuso
                ? ''
                : old('denominazione_mittente', $defaultDenomMitt),
            'nome_mittente' => old('nome_mittente', $partNome !== '' ? $partNome : $fallbackNome),
            'cognome_mittente' => old('cognome_mittente', $partCognome !== '' ? $partCognome : $fallbackCognome),
            'via_partenza' => old('via_partenza', $partVia !== '' ? $partVia : ($mSorg ? trim((string) ($mSorg->indirizzo ?? '')) : '')),
            'numero_partenza' => old('numero_partenza', $partNumero !== '' ? $partNumero : ($mSorg ? trim((string) ($mSorg->civico ?? '')) : '')),
            'note_partenza' => old('note_partenza', (string) ($part['note'] ?? '')),
            'telefono_mittente' => old('telefono_mittente', $defTel),
            'email_mittente' => old('email_mittente', $defEmail),
            'denominazione_destinatario' => old('denominazione_destinatario', $defaultDenomDest),
            'nome_destinatario' => old('nome_destinatario', $destNome),
            'cognome_destinatario' => old('cognome_destinatario', $destCognome),
            'telefono_destinatario' => old('telefono_destinatario', trim((string) ($dest['telefono'] ?? ''))),
            'email_destinatario' => old('email_destinatario', trim((string) ($dest['email'] ?? ''))),
            'via_destinazione' => old('via_destinazione', (string) ($dest['via'] ?? '')),
            'numero_destinazione' => old('numero_destinazione', (string) ($dest['numero'] ?? '')),
            'note_destinazione' => old('note_destinazione', (string) ($dest['note'] ?? '')),
        ];
        $isReso = ! empty($preventivo['reso']);
        $notePartenzaPlaceholder = ! empty($preventivo['reso'])
            ? 'Puoi inserire qui eventuali codici.'
            : 'Istruzioni per il corriere al ritiro';

        $consegnaMode = trim((string) ($riga['corriere']['consegna'] ?? ''));
        $consegnaPunto = ! $isReso && PuntoConsegnaSessione::richiestoPerRiga($riga);

        return view('indirizzi-spedizione', [
            'preventivo' => $preventivo,
            'corriereId' => $corriereId,
            'riga' => $riga,
            'capPartenza' => $capPartenza,
            'capArrivo' => $capArrivo,
            'cittaPartenza' => $orig['comune'] ?? '—',
            'pvPartenza' => $orig['provincia'] ?? '—',
            'cittaArrivo' => $dst['comune'] ?? '—',
            'pvArrivo' => $dst['provincia'] ?? '—',
            'values' => $values,
            'destinatariRubrica' => $destinatariRubrica,
            'mittenteDenominazioneChiuso' => (bool) $mittenteDenominazioneChiuso,
            'mittentiRubrica' => $mittentiRubrica,
            'mittenteRubricaIdSelezionato' => $selectedRubricaId,
            'notePartenzaPlaceholder' => $notePartenzaPlaceholder,
            'isReso' => $isReso,
            'consegnaPunto' => $consegnaPunto,
            'consegnaMode' => $consegnaMode,
        ]);
    }

    public function store(Request $request)
    {
        $preventivo = $request->session()->get('preventivo');
        $corriereId = (int) $request->input('corriere_id', 0);

        if (! $preventivo || $corriereId < 1) {
            return redirect()
                ->route('preventivi')
                ->withErrors(['indirizzi' => 'Sessione preventivo non valida.']);
        }

        $riga = PreventivoRigaSelezionabile::trovaRiga($preventivo, $corriereId);
        if (! $riga) {
            abort(404);
        }

        $ridIn = $request->input('mittente_rubrica_id');
        $request->merge([
            'mittente_rubrica_id' => $ridIn !== null && $ridIn !== '' ? (int) $ridIn : null,
        ]);

        $didIn = $request->input('destinatario_rubrica_id');
        $request->merge([
            'destinatario_rubrica_id' => $didIn !== null && $didIn !== '' ? (int) $didIn : null,
        ]);

        $consegnaPunto = PuntoConsegnaSessione::richiestoPerRiga($riga);

        $rules = [
            'corriere_id' => ['required', 'integer'],
            'denominazione_mittente' => ['nullable', 'string', 'max:255'],
            'nome_mittente' => ['required', 'string', 'max:120'],
            'cognome_mittente' => ['required', 'string', 'max:120'],
            'via_partenza' => ['required', 'string', 'max:160'],
            'numero_partenza' => ['required', 'string', 'max:32'],
            'note_partenza' => ['nullable', 'string', 'max:1000'],
            'telefono_mittente' => ['required', 'string', 'max:40'],
            'email_mittente' => ['required', 'email', 'max:160'],
            'nome_destinatario' => ['required', 'string', 'max:120'],
            'cognome_destinatario' => ['required', 'string', 'max:120'],
            'telefono_destinatario' => ['required', 'string', 'max:40'],
            'email_destinatario' => ['required', 'email', 'max:160'],
            'salva_destinatario_rubrica' => ['sometimes', 'boolean'],
            'salva_mittente_rubrica' => ['sometimes', 'boolean'],
            'mittente_rubrica_id' => ['nullable', 'integer'],
            'destinatario_rubrica_id' => ['nullable', 'integer'],
        ];

        if (! $consegnaPunto) {
            $rules['denominazione_destinatario'] = ['nullable', 'string', 'max:255'];
            $rules['via_destinazione'] = ['required', 'string', 'max:160'];
            $rules['numero_destinazione'] = ['required', 'string', 'max:32'];
            $rules['note_destinazione'] = ['nullable', 'string', 'max:1000'];
        }

        $validated = $request->validate($rules);

        $orig = $preventivo['origine'] ?? [];
        $dst = $preventivo['destino'] ?? [];
        $input = $preventivo['input'] ?? [];

        $indirizzoPartenza = IndirizzoSpedizioneSnapshot::componeIndirizzo(
            $validated['via_partenza'],
            $validated['numero_partenza']
        );
        $viaDest = $consegnaPunto ? '' : trim((string) ($validated['via_destinazione'] ?? ''));
        $numeroDest = $consegnaPunto ? '' : trim((string) ($validated['numero_destinazione'] ?? ''));
        $indirizzoDest = IndirizzoSpedizioneSnapshot::componeIndirizzo($viaDest, $numeroDest);

        $user = $request->user();
        $isImpresaUtenteIndirizzi = $user && $user->hasVerifiedEmail() && (string) ($user->tipo_utente ?? 'privato') !== 'privato';
        $denomDestNorm = trim((string) ($validated['denominazione_destinatario'] ?? ''));

        $capNormPartenzaStore = $this->capService->normalizzaCap((string) ($input['cap_origine'] ?? ($orig['cap'] ?? '')));
        $mittentiCapStore = $this->mittenzeUtentePerCapPartenza($user, $capNormPartenzaStore);
        $ridStore = (int) ($validated['mittente_rubrica_id'] ?? 0);
        $mRow = ($ridStore > 0) ? $mittentiCapStore->firstWhere('id', $ridStore) : null;
        $mittenteDenominazioneChiuso = $mRow && trim((string) ($mRow->denominazione_ragione_sociale ?? '')) === '';
        $denomMittNorm = '';
        if (! $mittenteDenominazioneChiuso) {
            $denomMittNorm = trim((string) ($validated['denominazione_mittente'] ?? ''));
        }

        $mittRubricaIdSalva = $mRow ? (int) $mRow->id : null;

        $preventivo['indirizzi'] = [
            'corriere_id' => $corriereId,
            'mittente_rubrica_id' => $mittRubricaIdSalva,
            'partenza' => array_merge([
                'nome' => $validated['nome_mittente'],
                'cognome' => $validated['cognome_mittente'],
                'cap' => $input['cap_origine'] ?? ($orig['cap'] ?? ''),
                'comune' => $orig['comune'] ?? '',
                'provincia' => $orig['provincia'] ?? '',
                'via' => $validated['via_partenza'],
                'numero' => $validated['numero_partenza'],
                'indirizzo' => $indirizzoPartenza,
                'telefono' => $validated['telefono_mittente'],
                'email' => $validated['email_mittente'],
                'note' => $validated['note_partenza'] ?? '',
            ], $denomMittNorm !== '' ? ['denominazione_impresa' => $denomMittNorm] : []),
            'destinazione' => array_merge([
                'nome' => $validated['nome_destinatario'],
                'cognome' => $validated['cognome_destinatario'],
                'cap' => $input['cap_destino'] ?? ($dst['cap'] ?? ''),
                'comune' => $dst['comune'] ?? '',
                'provincia' => $dst['provincia'] ?? '',
                'via' => $viaDest,
                'numero' => $numeroDest,
                'indirizzo' => $indirizzoDest,
                'telefono' => trim($validated['telefono_destinatario']),
                'email' => mb_strtolower(trim($validated['email_destinatario'])),
                'note' => $consegnaPunto ? '' : ($validated['note_destinazione'] ?? ''),
                'nome_destinatario' => trim($validated['nome_destinatario'].' '.$validated['cognome_destinatario']),
            ], $denomDestNorm !== '' && ! $consegnaPunto ? ['denominazione_impresa' => $denomDestNorm] : [], $consegnaPunto ? [
                'consegna_a_punto' => true,
            ] : []),
            'updated_at' => now()->toIso8601String(),
        ];

        PreventivoPrezziEsposti::aggiornaDaRiga($preventivo, $corriereId);
        $request->session()->put('preventivo', $preventivo);

        $didStore = (int) ($validated['destinatario_rubrica_id'] ?? 0);

        if ($request->boolean('salva_mittente_rubrica') && $user && $user->hasVerifiedEmail() && $ridStore === 0) {
            $idComuneOrig = $this->resolveComuneIdForPreventivoOrigine($preventivo);
            if ($idComuneOrig !== null) {
                $capRawOrig = $input['cap_origine'] ?? ($orig['cap'] ?? '');
                $capNormOrig = $this->capService->normalizzaCap((string) $capRawOrig);
                $tipo = (string) ($user->tipo_utente ?? 'privato');
                $user->mittenze()->create([
                    'nome' => trim($validated['nome_mittente']),
                    'cognome' => trim($validated['cognome_mittente']),
                    'telefono' => trim($validated['telefono_mittente']),
                    'email' => mb_strtolower(trim($validated['email_mittente'])),
                    'indirizzo' => trim($validated['via_partenza']),
                    'civico' => trim($validated['numero_partenza']),
                    'cap' => strlen($capNormOrig) === 5 ? $capNormOrig : null,
                    'citta' => trim((string) ($orig['comune'] ?? '')),
                    'provincia' => strtoupper(substr(trim((string) ($orig['provincia'] ?? '')), 0, 2)),
                    'id_comune' => $idComuneOrig,
                    'denominazione_ragione_sociale' => $tipo !== 'privato' && $denomMittNorm !== ''
                        ? $denomMittNorm
                        : null,
                    'is_preferito' => false,
                    'is_fatturazione' => false,
                ]);
            }
        }

        if (! $consegnaPunto && $request->boolean('salva_destinatario_rubrica') && $user && $user->hasVerifiedEmail() && $didStore === 0) {
            $idComune = $this->resolveComuneIdForPreventivoDestino($preventivo);
            if ($idComune !== null) {
                $capRaw = $input['cap_destino'] ?? ($dst['cap'] ?? '');
                $capNorm = $this->capService->normalizzaCap((string) $capRaw);
                $payload = [
                    'nome' => trim($validated['nome_destinatario']),
                    'cognome' => trim($validated['cognome_destinatario']),
                    'telefono' => trim($validated['telefono_destinatario']),
                    'email' => mb_strtolower(trim($validated['email_destinatario'])),
                    'indirizzo' => $viaDest,
                    'civico' => $numeroDest,
                    'cap' => strlen($capNorm) === 5 ? $capNorm : null,
                    'citta' => trim((string) ($dst['comune'] ?? '')),
                    'provincia' => strtoupper(substr(trim((string) ($dst['provincia'] ?? '')), 0, 2)),
                    'id_comune' => $idComune,
                    'denominazione_ragione_sociale' => $denomDestNorm !== '' ? $denomDestNorm : null,
                ];
                $user->destinatari()->create($payload);
            }
        }

        return redirect()->route('checkout.show', ['corriere' => $corriereId]);
    }

    /**
     * Allinea CAP, comune e provincia della partenza del preventivo a una riga comuni.
     */
    private function resolveComuneIdForPreventivoOrigine(array $preventivo): ?int
    {
        $orig = $preventivo['origine'] ?? [];
        $input = $preventivo['input'] ?? [];
        $capRaw = $input['cap_origine'] ?? ($orig['cap'] ?? '');
        $capNorm = $this->capService->normalizzaCap((string) $capRaw);
        if (strlen($capNorm) !== 5) {
            return null;
        }

        $cittaNorm = mb_strtolower(trim((string) ($orig['comune'] ?? '')));
        if ($cittaNorm === '') {
            return null;
        }

        $prov = strtoupper(substr(trim((string) ($orig['provincia'] ?? '')), 0, 2));
        if (strlen($prov) !== 2) {
            return null;
        }

        $hit = comune::query()
            ->whereRaw('UPPER(LEFT(TRIM(provincia), 2)) = ?', [$prov])
            ->get()
            ->first(function ($c) use ($capNorm, $cittaNorm) {
                $cDb = str_pad(preg_replace('/\D/', '', (string) $c->cap), 5, '0', STR_PAD_LEFT);

                return $cDb === $capNorm && mb_strtolower(trim((string) $c->comune)) === $cittaNorm;
            });

        return $hit ? (int) $hit->id : null;
    }

    /**
     * Allinea CAP, comune e provincia del destino del preventivo a una riga della tabella comuni (come in rubrica mittenti/destinatari).
     */
    private function resolveComuneIdForPreventivoDestino(array $preventivo): ?int
    {
        $dst = $preventivo['destino'] ?? [];
        $input = $preventivo['input'] ?? [];
        $capRaw = $input['cap_destino'] ?? ($dst['cap'] ?? '');
        $capNorm = $this->capService->normalizzaCap((string) $capRaw);
        if (strlen($capNorm) !== 5) {
            return null;
        }

        $cittaNorm = mb_strtolower(trim((string) ($dst['comune'] ?? '')));
        if ($cittaNorm === '') {
            return null;
        }

        $prov = strtoupper(substr(trim((string) ($dst['provincia'] ?? '')), 0, 2));
        if (strlen($prov) !== 2) {
            return null;
        }

        $hit = comune::query()
            ->whereRaw('UPPER(LEFT(TRIM(provincia), 2)) = ?', [$prov])
            ->get()
            ->first(function ($c) use ($capNorm, $cittaNorm) {
                $cDb = str_pad(preg_replace('/\D/', '', (string) $c->cap), 5, '0', STR_PAD_LEFT);

                return $cDb === $capNorm && mb_strtolower(trim((string) $c->comune)) === $cittaNorm;
            });

        return $hit ? (int) $hit->id : null;
    }

    /**
     * Mittenti in rubrica il cui CAP coincide con la partenza del preventivo (5 cifre normalizzate).
     *
     * @return Collection<int, mittenza>
     */
    private function mittenzeUtentePerCapPartenza(?User $user, string $capNormPartenza): Collection
    {
        if (! $user || ! $user->hasVerifiedEmail() || strlen($capNormPartenza) !== 5) {
            return collect();
        }

        $this->capService->ensureForUser($user);

        return $user->mittenze()
            ->get()
            ->filter(function (mittenza $m) use ($capNormPartenza) {
                return $this->capService->normalizzaCap((string) ($m->cap ?? '')) === $capNormPartenza;
            })
            ->sort(function (mittenza $a, mittenza $b): int {
                $pa = (bool) $a->is_preferito;
                $pb = (bool) $b->is_preferito;
                if ($pa !== $pb) {
                    return $pb <=> $pa;
                }

                return strcasecmp((string) ($a->cognome ?? ''), (string) ($b->cognome ?? ''));
            })
            ->values();
    }

    /**
     * Destinatari in rubrica il cui CAP coincide con la destinazione del preventivo (5 cifre normalizzate).
     *
     * @return Collection<int, destinatario>
     */
    private function destinatariUtentePerCapDestino(?User $user, string $capNormArrivo): Collection
    {
        if (! $user || ! $user->hasVerifiedEmail() || strlen($capNormArrivo) !== 5) {
            return collect();
        }

        return $user->destinatari()
            ->orderBy('cognome')
            ->orderBy('nome')
            ->orderBy('id')
            ->get()
            ->filter(function (destinatario $d) use ($capNormArrivo) {
                return $this->capService->normalizzaCap((string) ($d->cap ?? '')) === $capNormArrivo;
            })
            ->values();
    }

}
