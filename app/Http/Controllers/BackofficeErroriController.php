<?php

namespace App\Http\Controllers;

use App\Models\log_errore_applicativo;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BackofficeErroriController extends Controller
{
    public function index(Request $request): View
    {
        $errori = log_errore_applicativo::query()
            ->with('user:id,email')
            ->orderByDesc('id')
            ->limit(200)
            ->get();

        return view('backoffice.errori', [
            'errori' => $errori,
        ]);
    }

    public function show(log_errore_applicativo $log_errore_applicativo): View
    {
        $log_errore_applicativo->load('user:id,email');

        return view('backoffice.errori-show', [
            'errore' => $log_errore_applicativo,
        ]);
    }
}
