<?php
namespace App\Http\Controllers\Auth;
use App\Http\Controllers\Controller;
use App\Services\TurnstileVerifier;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Anagrafica;
use App\Models\UserStatus;
use App\Support\CarrelloUtente;
use App\Services\UserMittenzeService;
use App\Rules\PasswordPortale;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Auth\Events\Registered;

class RegisteredUserController extends Controller
{
    public function store(Request $request)
    {
        if (TurnstileVerifier::isConfigured()) {
            $request->validate([
                'cf-turnstile-response' => ['required', 'string'],
            ], [
                'cf-turnstile-response.required' => 'Completa la verifica di sicurezza prima di continuare.',
            ]);

            if (! app(TurnstileVerifier::class)->verify(
                $request->input('cf-turnstile-response'),
                $request->ip()
            )) {
                return redirect()->back()
                    ->withErrors(['cf-turnstile-response' => 'Verifica di sicurezza non riuscita. Riprova.'])
                    ->withInput($request->except(['password', 'password_confirmation']));
            }
        }

        $validated = $request->validate([
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', new PasswordPortale()],
            'password_confirmation' => ['required', 'same:password'],
            'tipo_utente' => ['required', 'in:privato,ditta,societa,professionista'],
        ], [
            'password_confirmation.same' => 'Le due password non coincidono.',
        ]);

        if (! session()->has('url_provenienza') || ! str_contains(url()->previous(), 'registrati')) {
            session(['url_provenienza' => url()->previous()]);
        }

        $user = User::create([
            'email' => $validated['email'],
            'password' => $validated['password'],
            'tipo_utente' => $validated['tipo_utente'],
            'is_account_disabled' => true,
            'postagem_bloqueado_pelo_bo' => false,
        ]);

        session([
            'registering_user_id' => $user->id,
            'temp_user_data' => ['tipo_utente' => $validated['tipo_utente']],
        ]);

        return redirect()->back()->with(['current_step' => 2]);
    }

    public function checkFiscale(Request $request)
    {
        $request->validate([
            'codice_fiscale' => ['required', 'string', 'max:32'],
        ]);

        $cf = strtoupper(trim((string) $request->codice_fiscale));
        $userId = session('registering_user_id');
        $userData = session('temp_user_data');

        if (! $userId) {
            return response()->json(['error' => 'Sessione scaduta o account non trovato. Ricomincia dalla registrazione.'], 422);
        }

        $user = User::find($userId);
        if (! $user) {
            return response()->json(['error' => 'Utente non trovato.'], 422);
        }

        $tipo = $user->tipo_utente;
        $datiRecuperati = null;

        if ($tipo !== 'privato') {
            $esistente = Anagrafica::where('codice_fiscale', $cf)
                ->whereHas('user', function ($q) use ($tipo) {
                    $q->where('tipo_utente', $tipo);
                })->first();

            if ($esistente) {
                $datiRecuperati = $esistente->toArray();
            }
        }

        return DB::transaction(function () use ($user, $tipo, $cf, $datiRecuperati) {
            $anagrafica = Anagrafica::query()->where('user_id', $user->id)->attiva()->first()
                ?? Anagrafica::query()->where('user_id', $user->id)->orderByDesc('id')->first()
                ?? new Anagrafica(['user_id' => $user->id]);

            $anagrafica->codice_fiscale = $cf;
            $anagrafica->attivo = true;

            if ($datiRecuperati) {
                $anagrafica->fill(collect($datiRecuperati)->only([
                    'denominazione_ragione_sociale',
                    'partita_iva',
                    'indirizzo',
                    'civico',
                    'cap',
                    'citta',
                    'provincia',
                    'telefono',
                    'pec',
                    'codice_sdi',
                ])->toArray());
            }
            $anagrafica->save();

            Anagrafica::query()
                ->where('user_id', $user->id)
                ->where('id', '!=', $anagrafica->id)
                ->update(['attivo' => false]);

            UserStatus::updateOrCreate(
                ['id_utente' => $user->id],
                ['id_status' => 1, 'data_definizione' => now()]
            );

            session(['registering_user_id' => $user->id]);

            return response()->json([
                'status' => 'success',
                'tipo_utente' => $tipo,
                'dati' => $anagrafica,
            ]);
        });
    }

    public function updateAnagrafica(Request $request)
    {
        $userId = session('registering_user_id') ?? Auth::id();
        $user = User::find($userId);

        if (! $user) {
            return redirect()->route('register')->withErrors(['session' => 'Sessione scaduta']);
        }

        $rules = [
            'nome' => ['required', 'string', 'max:255'],
            'cognome' => ['required', 'string', 'max:255'],
            'indirizzo' => ['required', 'string', 'max:255'],
            'civico' => ['required', 'string', 'max:10'],
            'cap' => ['required', 'string', 'size:5'],
            'citta' => ['required', 'string', 'max:255'],
            'provincia' => ['required', 'string', 'size:2'],
            'telefono' => ['required', 'string', 'max:20'],
        ];

        if ($user->tipo_utente !== 'privato') {
            $rules['partita_iva'] = ['required', 'string', 'size:11'];
            $rules['denominazione_ragione_sociale'] = ['required', 'string', 'max:255'];
        }

        $request->validate($rules);

        return DB::transaction(function () use ($user, $request) {
            $latest = Anagrafica::query()->where('user_id', $user->id)->attiva()->first();
            if (! $latest) {
                return redirect()->route('register')->withErrors(['session' => 'Anagrafica non inizializzata. Ricomincia dalla verifica del codice fiscale.']);
            }
            $latest->update($request->only([
                'nome', 'cognome', 'indirizzo', 'civico', 'cap', 'citta', 'provincia',
                'partita_iva', 'denominazione_ragione_sociale', 'telefono', 'pec', 'codice_sdi',
            ]));

            app(UserMittenzeService::class)->ensureForUser($user->fresh(['anagrafica']));

            UserStatus::where('id_utente', $user->id)->update([
                'id_status' => 2,
                'data_definizione' => now(),
            ]);

            $carrelloBackup = $request->session()->get('carrello');
            $preventivoBackup = $request->session()->get('preventivo');

            Auth::login($user);
            $request->session()->regenerate();

            if ($preventivoBackup !== null) {
                $request->session()->put('preventivo', $preventivoBackup);
            }
            if (is_array($carrelloBackup) && ! empty($carrelloBackup['items'])) {
                $request->session()->put('carrello', $carrelloBackup);
                CarrelloUtente::salvaDaSessione($request);
            }

            event(new Registered($user));

            $urlRitorno = session('url_provenienza', route('home'));
            session(['url.intended' => $urlRitorno]);

            session()->forget(['temp_user_data', 'registering_user_id', 'url_provenienza']);

            return redirect()->route('verification.notice');
        });
    }
}