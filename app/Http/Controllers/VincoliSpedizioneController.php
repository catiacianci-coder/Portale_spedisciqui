<?php

namespace App\Http\Controllers;

use App\Models\comune;
use App\Models\corriere;
use App\Models\italia_destino;
use App\Models\origine_destino;
use App\Models\origine_italia;
use App\Models\parametri_globali;
use App\Models\tariffa;
use App\Models\tipo_spedizone;
use App\Support\CorriereLogo;
use App\Support\MetodoPagamentoCodice;
use App\Support\PreventivoColonnePagamento;
use App\Services\RegolePricingService;
use App\Services\TariffaPrezzoBaseService;
use App\Services\UserImballaggiDefault;
use App\Services\UserMittenzeService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class VincoliSpedizioneController extends Controller
{
    /** Sconto % sul totale se pagamento con saldo/wallet (placeholder finché non arriva la regola da DB). */
    private const WALLET_DISCOUNT_PERCENT = 2.0;

    public function __construct(
        private readonly RegolePricingService $regolePricingService
    ) {}

    public function create(Request $request)
    {
        $tipi = tipo_spedizone::query()->orderBy('tipo_spedizione')->get();

        $userImballaggiJson = [];
        $capMittentePreferitoDefault = '';
        $idComuneMittentePreferitoDefault = '';
        $u = $request->user();
        if ($u && $u->hasVerifiedEmail()) {
            app(UserMittenzeService::class)->ensureForUser($u);
            $prefMitt = $u->mittenze()->where('is_preferito', true)->first();
            if ($prefMitt) {
                if ($prefMitt->id_comune) {
                    $comunePref = comune::query()->find($prefMitt->id_comune);
                    if ($comunePref) {
                        $capMittentePreferitoDefault = $this->formatCapComuneLabel($comunePref);
                        $idComuneMittentePreferitoDefault = (string) $prefMitt->id_comune;
                    } else {
                        $capMittentePreferitoDefault = $this->formatCapPadded((string) ($prefMitt->cap ?? ''));
                    }
                } else {
                    $capMittentePreferitoDefault = $this->formatCapPadded((string) ($prefMitt->cap ?? ''));
                }
            }
            app(UserImballaggiDefault::class)->ensureDefaults($u);
            $userImballaggiJson = $u->imballaggi()
                ->orderByDesc('is_preferito')
                ->orderBy('nome')
                ->get(['id', 'id_tipo_spediziones', 'nome', 'altezza', 'larghezza', 'spessore', 'peso', 'is_preferito'])
                ->map(fn ($row) => [
                    'id' => $row->id,
                    'id_tipo_spediziones' => (int) $row->id_tipo_spediziones,
                    'nome' => $row->nome,
                    'altezza' => (float) $row->altezza,
                    'larghezza' => (float) $row->larghezza,
                    'spessore' => (float) $row->spessore,
                    'peso' => (float) $row->peso,
                    'is_preferito' => (bool) $row->is_preferito,
                ])
                ->values()
                ->all();
        }

        $partnerCorrieri = corriere::query()
            ->where('attivo', true)
            ->where('ord_carosello', '>', 0)
            ->orderBy('ord_carosello')
            ->orderBy('id')
            ->get(['id', 'nome_corriere', 'nome_visualizzato'])
            ->map(function (corriere $c) {
                $nome = trim((string) $c->nome_visualizzato);
                if ($nome === '') {
                    $nome = (string) $c->nome_corriere;
                }

                return [
                    'id' => (int) $c->id,
                    'nome' => $nome,
                    'logo_url' => CorriereLogo::pubblico((int) $c->id),
                ];
            })
            ->values()
            ->all();

        return view('vincoli-spedizione', [
            'tipi' => $tipi,
            'risultati' => null,
            'input' => [],
            'partnerCorrieri' => $partnerCorrieri,
            'userImballaggiJson' => $userImballaggiJson,
            'capMittentePreferitoDefault' => $capMittentePreferitoDefault,
            'idComuneMittentePreferitoDefault' => $idComuneMittentePreferitoDefault,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_tipo_spediziones' => ['required', 'integer', 'exists:tipo_spediziones,id'],
            'ambito_spedizione' => ['required', 'in:nazionale,internazionale'],
            'cap_origine' => ['required', 'string', 'max:80'],
            'cap_destino' => ['required', 'string', 'max:80'],
            'id_comune_origine' => ['nullable', 'integer', 'exists:comuni,id'],
            'id_comune_destino' => ['nullable', 'integer', 'exists:comuni,id'],
            'altezza' => ['required', 'numeric', 'min:0.01'],
            'larghezza' => ['required', 'numeric', 'min:0.01'],
            'spessore' => ['required', 'numeric', 'min:0.01'],
            'peso' => ['required', 'numeric', 'min:0.01'],
        ]);

        $idComuneOrigine = $this->risolviComuneIdDaCapOSelezione(
            trim($validated['cap_origine']),
            $validated['id_comune_origine'] ?? null,
            'cap_origine'
        );

        $idComuneDestino = $this->risolviComuneIdDaCapOSelezione(
            trim($validated['cap_destino']),
            $validated['id_comune_destino'] ?? null,
            'cap_destino'
        );

        if ($idComuneOrigine instanceof \Illuminate\Http\RedirectResponse) {
            return $idComuneOrigine;
        }

        if ($idComuneDestino instanceof \Illuminate\Http\RedirectResponse) {
            return $idComuneDestino;
        }

        $idComuneOrigine = (int) $idComuneOrigine;
        $idComuneDestino = (int) $idComuneDestino;

        $dims = [
            (float) $validated['altezza'],
            (float) $validated['larghezza'],
            (float) $validated['spessore'],
        ];
        rsort($dims);
        [$latoMax, $latoMed, $latoMin] = $dims;
        // Nota: in `tariffas.max` il confronto è coerente con la somma dei tre lati del pacco (a+b+c),
        // non con il perimetro "fisico" 2*(a+b+c).
        $sommaLati = $latoMax + $latoMed + $latoMin;

        $peso = (float) $validated['peso'];
        $idTipoSpedizione = (int) $validated['id_tipo_spediziones'];

        $origineComune = comune::query()->find($idComuneOrigine);
        $destinoComune = comune::query()->find($idComuneDestino);

        $capOrigine = $this->formatCapPadded((string) ($origineComune->cap ?? ''));
        $capDestino = $this->formatCapPadded((string) ($destinoComune->cap ?? ''));

        $corrieri = corriere::query()
            ->where('attivo', true)
            ->orderBy('nome_corriere')
            ->get();

        $righe = [];

        foreach ($corrieri as $corriere) {
            $okTratta = $this->corriereCopreTratta($corriere, $idComuneOrigine, $idComuneDestino);
            $motivoTratta = $okTratta ? null : 'Non copre la tratta secondo tipo_o_d e tabelle collegate.';
            $usaTariffaInterna = (bool) ($corriere->tariffa_interna ?? true);

            $tariffaSelezionata = null;
            $motivoTariffa = null;
            $prezzoBase = null;
            $prezzoFinale = null;
            $prezzoWallet = null;
            $walletDiscountPct = null;
            $walletModifierPct = null;

            if ($okTratta && $usaTariffaInterna) {
                $tariffaSelezionata = $this->trovaTariffaCompatibile(
                    $corriere->id,
                    $idTipoSpedizione,
                    $peso,
                    $latoMax,
                    $latoMed,
                    $latoMin,
                    $sommaLati
                );

                if (! $tariffaSelezionata) {
                    $motivoTariffa = 'Nessuna tariffa compatibile con peso/dimensioni/perimetro.';
                } else {
                    $regioneOrigine = $origineComune ? (string) $origineComune->regione : null;
                    $regioneDestino = $destinoComune ? (string) $destinoComune->regione : null;
                    $base = TariffaPrezzoBaseService::prezzoBase(
                        $tariffaSelezionata,
                        $corriere,
                        $regioneOrigine,
                        $regioneDestino
                    );
                    $ricarico = $tariffaSelezionata->ricarico === null ? 0.0 : (float) $tariffaSelezionata->ricarico;
                    $sovrattassaDisagiato = $this->regolePricingService->calcolaSovrattassaDisagiato(
                        (int) $corriere->id,
                        $idComuneOrigine,
                        $idComuneDestino,
                        $peso
                    );

                    $prezzoBase = $base;
                    $prezzoFinale = ($base * (1 + ($ricarico / 100))) + $sovrattassaDisagiato;
                    $wMod = $this->walletPaymentModifier();
                    $pctWallet = $wMod['pct'];
                    $absWallet = $wMod['abs'];
                    $walletModifierPct = $pctWallet;
                    $prezzoWallet = round($prezzoFinale * (1 + ($pctWallet / 100)) + $absWallet, 2);
                    $walletDiscountPct = $pctWallet < 0 ? abs($pctWallet) : null;
                }
            } elseif ($okTratta && ! $usaTariffaInterna) {
                $motivoTariffa = 'Tariffa interna disattivata: prezzo da API piattaforma.';
            }

            $righe[] = [
                'corriere' => $corriere->toArray(),
                'ok_tratta' => $okTratta,
                'motivo_tratta' => $motivoTratta,
                'tariffa' => $tariffaSelezionata?->toArray(),
                'motivo_tariffa' => $motivoTariffa,
                'prezzo_base' => $prezzoBase,
                'prezzo_finale' => $prezzoFinale,
                'prezzo_wallet' => $prezzoWallet,
                'wallet_discount_pct' => $walletDiscountPct,
                'wallet_modifier_pct' => $walletModifierPct,
            ];
        }

        $tipoSpedizione = tipo_spedizone::query()->find($idTipoSpedizione);

        $payload = [
            'version' => 1,
            'created_at' => now()->toIso8601String(),
            'tipo_spedizione' => $tipoSpedizione ? $tipoSpedizione->toArray() : ['id' => $idTipoSpedizione],
            'origine' => $origineComune ? $origineComune->toArray() : null,
            'destino' => $destinoComune ? $destinoComune->toArray() : null,
            'input' => [
                'id_tipo_spediziones' => $idTipoSpedizione,
                'ambito_spedizione' => $validated['ambito_spedizione'],
                'cap_origine' => $capOrigine,
                'cap_destino' => $capDestino,
                'id_comune_origine' => $idComuneOrigine,
                'id_comune_destino' => $idComuneDestino,
                'altezza' => (float) $validated['altezza'],
                'larghezza' => (float) $validated['larghezza'],
                'spessore' => (float) $validated['spessore'],
                'peso' => (float) $validated['peso'],
            ],
            'misure' => [
                'lato_max' => $latoMax,
                'lato_med' => $latoMed,
                'lato_min' => $latoMin,
                'somma_lati' => $sommaLati,
                'perimetro_geometrico' => 2 * $sommaLati,
            ],
            'righe' => $righe,
        ];

        $request->session()->put('preventivo', $payload);

        return redirect()->route('preventivi');
    }

    public function suggestComuni(Request $request)
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:1', 'max:80'],
        ]);

        $q = trim($validated['q']);
        $isDigits = ctype_digit($q);

        $query = comune::query()->select(['id', 'cap', 'comune', 'provincia']);

        if ($isDigits) {
            if (strlen($q) === 5) {
                $cap = str_pad($q, 5, '0', STR_PAD_LEFT);
                $query->where('cap', $cap);
            } else {
                $query->where('cap', 'like', $q . '%');
            }
        } else {
            if (strlen($q) < 2) {
                return response()->json([]);
            }

            $query->where('comune', 'like', $q . '%');
        }

        $results = $query
            ->orderBy('comune')
            ->limit(25)
            ->get()
            ->map(function (comune $c) {
                return [
                    'id' => $c->id,
                    'cap' => str_pad((string) $c->cap, 5, '0', STR_PAD_LEFT),
                    'comune' => $c->comune,
                    'provincia' => $c->provincia,
                    'label' => str_pad((string) $c->cap, 5, '0', STR_PAD_LEFT) . ' — ' . $c->comune . ' (' . $c->provincia . ')',
                ];
            });

        return response()->json($results);
    }

    private function risolviComuneIdDaCapOSelezione(string $rawDisplay, ?int $idSelezionato, string $fieldKey): int|\Illuminate\Http\RedirectResponse
    {
        if ($idSelezionato) {
            $comune = comune::query()->find($idSelezionato);
            if (!$comune) {
                return back()->withErrors([$fieldKey => 'Comune selezionato non valido.'])->withInput();
            }

            return (int) $comune->id;
        }

        $capPadded = $this->estraiCapPadded($rawDisplay);
        if ($capPadded === null) {
            return back()->withErrors([$fieldKey => 'CAP non valido.'])->withInput();
        }

        $comuni = comune::query()->where('cap', $capPadded)->get();

        if ($comuni->count() === 1) {
            return (int) $comuni->first()->id;
        }

        $errors = [];
        $errors[$fieldKey] = $comuni->count() === 0
            ? 'CAP non trovato.'
            : 'CAP ambiguo: scegli un comune dall’autocomplete.';

        return back()->withErrors($errors)->withInput();
    }

    private function formatCapPadded(string $cap): string
    {
        return str_pad(trim($cap), 5, '0', STR_PAD_LEFT);
    }

    private function formatCapComuneLabel(comune $comune): string
    {
        return $this->formatCapPadded((string) $comune->cap)
            . ' — '
            . $comune->comune
            . ' ('
            . $comune->provincia
            . ')';
    }

    private function estraiCapPadded(string $raw): ?string
    {
        $trim = trim($raw);
        if ($trim === '') {
            return null;
        }

        if (ctype_digit($trim)) {
            return $this->formatCapPadded($trim);
        }

        if (preg_match('/^(\d{5})\b/u', $trim, $matches)) {
            return $matches[1];
        }

        if (preg_match('/^(\d+)/u', $trim, $matches) && strlen($matches[1]) <= 5) {
            return $this->formatCapPadded($matches[1]);
        }

        return null;
    }

    private function corriereCopreTratta(corriere $corriere, int $idComuneOrigine, int $idComuneDestino): bool
    {
        return match ($corriere->tipo_o_d) {
            'italia_italia' => true,
            'origine_italias' => origine_italia::query()
                ->where('id_corriere', $corriere->id)
                ->where('id_comune', $idComuneOrigine)
                ->exists(),
            'italia_destinos' => italia_destino::query()
                ->where('id_corriere', $corriere->id)
                ->where('id_comune', $idComuneDestino)
                ->exists(),
            'origine_destinos' => origine_destino::query()
                ->where('id_corriere', $corriere->id)
                ->where('id_comune_origine', $idComuneOrigine)
                ->where('id_comune_destino', $idComuneDestino)
                ->exists(),
            default => false,
        };
    }

    private function trovaTariffaCompatibile(
        int $idCorriere,
        int $idTipoSpedizione,
        float $peso,
        float $latoMax,
        float $latoMed,
        float $latoMin,
        float $sommaLati
    ): ?tariffa {
        $oggi = Carbon::today();

        $candidati = tariffa::query()
            ->where('id_corrieres', $idCorriere)
            ->where('id_tipo_spediziones', $idTipoSpedizione)
            ->where(function ($q) use ($peso) {
                $q->whereNull('peso_da')->orWhere('peso_da', '<=', $peso);
            })
            ->where(function ($q) use ($peso) {
                $q->whereNull('peso_a')->orWhere('peso_a', '>=', $peso);
            })
            ->where(function ($q) use ($oggi) {
                $q->whereNull('data_sospensione')->orWhereDate('data_sospensione', '>', $oggi);
            })
            ->orderBy('tariffa')
            ->get();

        foreach ($candidati as $t) {
            if (!$this->tariffaRispettaDimensioni($t, $latoMax, $latoMed, $latoMin, $sommaLati, $peso)) {
                continue;
            }

            return $t;
        }

        return null;
    }

    private function tariffaRispettaDimensioni(
        tariffa $t,
        float $latoMax,
        float $latoMed,
        float $latoMin,
        float $sommaLati,
        float $peso
    ): bool {
        // Vincolo richiesto: usare solo lato_max.
        // Se il valore tariffa e' <= 10 lo interpretiamo come metri (es. 1.5 -> 150 cm).
        // Se e' > 10 lo interpretiamo come centimetri.
        $latoMaxTariffaCm = $this->normalizzaLatoMaxInCm($t->lato_max);
        if ($latoMaxTariffaCm !== null && $latoMax > $latoMaxTariffaCm) {
            return false;
        }

        if ($t->max !== null && $sommaLati > (float) $t->max) {
            return false;
        }

        if ($t->peso_max_collo !== null && $peso > (float) $t->peso_max_collo) {
            return false;
        }

        return true;
    }

    private function normalizzaLatoMaxInCm(mixed $valore): ?float
    {
        if ($valore === null || $valore === '') {
            return null;
        }

        $v = (float) $valore;
        if ($v <= 0) {
            return null;
        }

        return $v <= 10 ? $v * 100 : $v;
    }

    /**
     * Sconto/commissione % wallet sugli ordini (metodo_pagamento_ordinis.commissioni).
     *
     * @return array{pct: float, abs: float}
     */
    private function walletPaymentModifier(): array
    {
        $pct = PreventivoColonnePagamento::commissioniPctMetodo(MetodoPagamentoCodice::WALLET);

        return ['pct' => $pct, 'abs' => 0.0];
    }
}
