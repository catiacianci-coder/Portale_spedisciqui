<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class BackofficeUtilitiesController extends Controller
{
    public function index(): View
    {
        return view('backoffice.utilities.index');
    }
}
