<?php

namespace App\Http\Controllers;

use App\Models\nc_pratica;
use App\Models\nc_pratica_riga;
use App\Services\NcCsvImportService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BackofficeNonConformitaController extends Controller
{
    public function index(Request $request): View
    {
        $period = (string) $request->input('period', '');
        $allowed = ['', 'oggi', '7', '15', '30', 'custom'];
        if (! in_array($period, $allowed, true)) {
            $period = '';
        }
        $dataInizio = (string) $request->input('data_inizio', '');
        $dataFine = (string) $request->input('data_fine', '');
        $cliente = trim((string) $request->input('cliente', ''));
        $statoFiltro = (string) $request->input('stato_pratica', '');
        $allowedStato = ['', 'tutti', 'pagate', 'non_pagate', 'parziali'];
        if (! in_array($statoFiltro, $allowedStato, true)) {
            $statoFiltro = '';
        }
        $numeroPratica = trim((string) $request->input('numero_pratica', ''));

        $filtroErrors = [];
        if ($cliente !== '' && ! filter_var($cliente, FILTER_VALIDATE_EMAIL)) {
            $filtroErrors[] = 'Email cliente non valida per il filtro.';
        }
        if ($period === 'custom') {
            if ($dataInizio === '' || $dataFine === '') {
                $filtroErrors[] = 'Per il periodo personalizzato servono data inizio e data fine.';
            } else {
                try {
                    $d1 = Carbon::createFromFormat('Y-m-d', $dataInizio)->startOfDay();
                    $d2 = Carbon::createFromFormat('Y-m-d', $dataFine)->endOfDay();
                    if ($d1->gt($d2)) {
                        $filtroErrors[] = 'La data inizio non può essere successiva alla data fine.';
                    }
                } catch (\Throwable) {
                    $filtroErrors[] = 'Date non valide (formato AAAA-MM-GG).';
                }
            }
        }

        [$from, $to] = $filtroErrors === [] ? $this->intervalloNc($period, $dataInizio, $dataFine) : [null, null];

        $q = nc_pratica::query()->with(['user', 'righe.spedizione.ordine'])->orderByDesc('id');

        if ($from !== null && $to !== null) {
            $q->whereBetween('created_at', [$from, $to]);
        }
        if ($cliente !== '' && $filtroErrors === []) {
            $q->whereHas('user', fn ($qq) => $qq->where('email', $cliente));
        }
        if ($numeroPratica !== '') {
            $q->where('numero_pratica', 'like', '%'.$numeroPratica.'%');
        }
        if ($statoFiltro === 'pagate') {
            $q->where('stato', nc_pratica::STATO_CHIUSO);
        } elseif ($statoFiltro === 'non_pagate') {
            $q->where('stato', nc_pratica::STATO_APERTO)
                ->whereDoesntHave('righe', fn ($qq) => $qq->where('stato_riga', nc_pratica_riga::STATO_PAGATO));
        } elseif ($statoFiltro === 'parziali') {
            $q->where('stato', nc_pratica::STATO_APERTO)
                ->whereHas('righe', fn ($qq) => $qq->where('stato_riga', nc_pratica_riga::STATO_PAGATO));
        }

        $pratiche = $q->limit(500)->get();

        $pratichePerCliente = $pratiche
            ->groupBy('user_id')
            ->map(static function ($gruppo) {
                /** @var \Illuminate\Support\Collection<int, nc_pratica> $gruppo */
                $ordinato = $gruppo->sortByDesc('id')->values();

                return [
                    'user' => $ordinato->first()?->user,
                    'pratiche' => $ordinato,
                ];
            })
            ->values()
            ->sortBy(static function (array $blocco): string {
                return strtolower((string) ($blocco['user']?->email ?? 'zz'));
            })
            ->values();

        return view('backoffice.non-conformita', [
            'pratichePerCliente' => $pratichePerCliente,
            'filtroPeriod' => $period,
            'filtroDataInizio' => $dataInizio,
            'filtroDataFine' => $dataFine,
            'filtroCliente' => $cliente,
            'filtroStatoPratica' => $statoFiltro,
            'filtroNumeroPratica' => $numeroPratica,
            'filtroErrors' => $filtroErrors,
        ]);
    }

    public function importCsv(Request $request, NcCsvImportService $import): RedirectResponse
    {
        $request->validate([
            'file_csv' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $res = $import->importa($request->file('file_csv'), (int) $request->user()->id);

        $haDati = $res['pratiche'] > 0 || $res['righe'] > 0;
        $redir = redirect()->route('backoffice.nc.index');

        if (! $haDati) {
            return $redir
                ->with('nc_import_outcome', 'fallito')
                ->with(
                    'nc_import_warnings',
                    $res['errori'] !== [] ? $res['errori'] : ['Nessuna riga del file è stata importata.']
                );
        }

        if ($res['errori'] !== []) {
            return $redir
                ->with('nc_import_outcome', 'parziale')
                ->with('nc_import_pratiche', $res['pratiche'])
                ->with('nc_import_righe', $res['righe'])
                ->with('nc_import_warnings', $res['errori']);
        }

        return $redir
            ->with('nc_import_outcome', 'ok')
            ->with('nc_import_pratiche', $res['pratiche'])
            ->with('nc_import_righe', $res['righe']);
    }

    /**
     * @return array{0: ?Carbon, 1: ?Carbon}
     */
    private function intervalloNc(string $period, string $dataInizio, string $dataFine): array
    {
        $now = now();

        return match ($period) {
            'oggi' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            '7' => [$now->copy()->subDays(7)->startOfDay(), $now->copy()->endOfDay()],
            '15' => [$now->copy()->subDays(15)->startOfDay(), $now->copy()->endOfDay()],
            '30' => [$now->copy()->subDays(30)->startOfDay(), $now->copy()->endOfDay()],
            'custom' => [
                Carbon::createFromFormat('Y-m-d', $dataInizio)->startOfDay(),
                Carbon::createFromFormat('Y-m-d', $dataFine)->endOfDay(),
            ],
            default => [null, null],
        };
    }
}
