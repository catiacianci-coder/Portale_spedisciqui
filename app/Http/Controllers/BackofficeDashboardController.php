<?php

namespace App\Http\Controllers;

use App\Support\BackofficeHub;
use Illuminate\View\View;

class BackofficeDashboardController extends Controller
{
    public function index(): View
    {
        return view('backoffice.index', [
            'items' => BackofficeHub::items(),
        ]);
    }
}
