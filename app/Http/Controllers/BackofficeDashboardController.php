<?php

namespace App\Http\Controllers;

use App\Models\parametri_globali;
use App\Support\BackofficeHub;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BackofficeDashboardController extends Controller
{
    public const SESSION_DB_PANEL_UNLOCKED = 'backoffice_db_panel_unlocked';

    public function index(): View
    {
        return view('backoffice.index', [
            'sections' => BackofficeHub::sectionsWithItems(),
            'dbSectionUnlocked' => $this->dbSectionUnlocked(),
        ]);
    }

    public function unlockDbPanel(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'string', 'max:255'],
        ], [
            'password.required' => 'Inserisci la password.',
        ]);

        $expected = parametri_globali::valoreTesto(parametri_globali::DENOM_PASSWORD_PANNELLO_DB_BO);
        if ($expected === '' || ! hash_equals($expected, $validated['password'])) {
            return redirect()
                ->route('backoffice.index')
                ->withErrors(['db_panel_password' => 'Password non corretta.'])
                ->with('db_panel_unlock_attempt', true);
        }

        $request->session()->put(self::SESSION_DB_PANEL_UNLOCKED, true);

        return redirect()
            ->route('backoffice.index')
            ->with('ok', 'Sezione Database e Documenti sbloccata.');
    }

    public function lockDbPanel(Request $request): RedirectResponse
    {
        $request->session()->forget(self::SESSION_DB_PANEL_UNLOCKED);

        return redirect()
            ->route('backoffice.index')
            ->with('ok', 'Sezione Database e Documenti chiusa.');
    }

    private function dbSectionUnlocked(): bool
    {
        return (bool) session(self::SESSION_DB_PANEL_UNLOCKED, false);
    }
}
