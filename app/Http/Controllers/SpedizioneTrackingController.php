<?php

namespace App\Http\Controllers;

use App\Models\spedizione;
use App\Services\Tracking\SpedizioneTrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SpedizioneTrackingController extends Controller
{
    public function showCliente(
        Request $request,
        spedizione $spedizione,
        SpedizioneTrackingService $trackingService,
    ): JsonResponse {
        $this->authorize('view', $spedizione);

        return response()->json($trackingService->consulta($spedizione));
    }

    public function showBackoffice(
        spedizione $spedizione,
        SpedizioneTrackingService $trackingService,
    ): JsonResponse {
        return response()->json($trackingService->consulta($spedizione));
    }
}
