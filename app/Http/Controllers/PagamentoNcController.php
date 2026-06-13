<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class PagamentoNcController extends Controller
{
    /**
     * Pagamento non conformità (gateway / bonifico): pagina provvisoria.
     */
    public function show(Request $request): View
    {
        $rid = $request->query('riga_id');
        if (is_array($rid)) {
            $rigaIds = array_values(array_filter(array_map('intval', $rid)));
        } elseif ($rid !== null && $rid !== '') {
            $rigaIds = [(int) $rid];
        } else {
            $rigaIds = [];
        }

        return view('pagamento-nc', [
            'rigaIds' => $rigaIds,
            'praticaId' => $request->query('pratica'),
            'tutto' => $request->boolean('tutto'),
        ]);
    }
}
