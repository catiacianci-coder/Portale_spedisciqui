<?php

namespace App\Http\Controllers;

use App\Models\nc_pratica;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FinanziarioNonConformitaController extends Controller
{
    public function index(Request $request): View
    {
        $uid = (int) $request->user()->id;
        $pratiche = nc_pratica::query()
            ->where('user_id', $uid)
            ->with(['righe'])
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        return view('finanziario.non-conformita-index', [
            'pratiche' => $pratiche,
        ]);
    }

    public function show(Request $request, int $pratica): View
    {
        $uid = (int) $request->user()->id;
        $model = nc_pratica::query()
            ->where('user_id', $uid)
            ->with(['righe.spedizione.ordine', 'righe.spedizione.corriereRecord'])
            ->findOrFail($pratica);

        return view('finanziario.non-conformita-show', [
            'pratica' => $model,
        ]);
    }

    public function pdf(Request $request, int $pratica): BinaryFileResponse
    {
        $uid = (int) $request->user()->id;
        $model = nc_pratica::query()->where('user_id', $uid)->findOrFail($pratica);
        if ($model->pdf_path === null || $model->pdf_path === '') {
            abort(404);
        }
        $full = storage_path('app/'.$model->pdf_path);
        if (! File::isFile($full)) {
            abort(404);
        }

        $nome = ($model->numero_pratica !== null && $model->numero_pratica !== '')
            ? $model->numero_pratica.'.pdf'
            : 'pratica-'.$model->id.'.pdf';

        return response()->download($full, $nome);
    }
}
