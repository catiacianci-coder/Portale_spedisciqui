<?php

namespace App\Http\Controllers;

use App\Models\spedizione;
use App\Models\stato_spedizione;
use App\Support\FiltriTabella;
use Illuminate\Http\Request;

class SpedizioniClienteController extends Controller
{
    public function index(Request $request)
    {
        $uid = (int) $request->user()->id;
        $perPage = FiltriTabella::perPage($request);
        $periodo = FiltriTabella::periodoDaRequest($request, '30');
        $codice = trim((string) $request->input('codice', ''));
        $tracking = trim((string) $request->input('tracking', ''));
        $numeroOrdine = trim((string) $request->input('numero_ordine', ''));

        $with = [
            'ordine',
            'corriereRecord',
            'tipoSpedizione',
            'tariffaSpedizione',
            'spedizioneStato',
            'rimborso',
            'serviziAggiuntiviRighe.corriereServizioAggiuntivo:id,testo_servizio',
        ];

        $query = spedizione::query()
            ->where('user_id', $uid)
            ->where('spedizione_stato_id', '!=', stato_spedizione::NON_PAGATA)
            ->whereHas('ordine', fn ($q) => $q->conPagamentoRegistrato())
            ->with($with)
            ->orderByDesc('id');

        if ($periodo['errors'] === []) {
            FiltriTabella::applicaFiltroCreatedAt($query, $periodo['from'], $periodo['to']);
        }

        FiltriTabella::filtraSpedizioniCliente($query, $codice, $tracking, $numeroOrdine);

        $spedizioni = $query->paginate($perPage)->withQueryString();

        return view('spedizioni.index', [
            'spedizioni' => $spedizioni,
            'perPage' => $perPage,
            'filtroPeriod' => $periodo['period'],
            'filtroDataDa' => $periodo['data_da'],
            'filtroDataA' => $periodo['data_a'],
            'filtroCodice' => $codice,
            'filtroTracking' => $tracking,
            'filtroNumeroOrdine' => $numeroOrdine,
            'filtroErrors' => $periodo['errors'],
            'queryParams' => FiltriTabella::parametriQuery($request),
        ]);
    }
}
