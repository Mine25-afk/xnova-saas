<?php

namespace App\Http\Controllers\Citas;

use App\Http\Controllers\Controller;
use App\Http\Requests\Citas\HuecosLibresRequest;
use App\Services\Citas\AgendaService;
use Illuminate\Http\JsonResponse;

class HuecosLibresController extends Controller
{
    public function __invoke(HuecosLibresRequest $request, AgendaService $agenda): JsonResponse
    {
        return response()->json($agenda->huecosLibres(
            (string) $request->validated('colaborador_dni'),
            $request->integer('sucursal_Id'),
            (string) $request->validated('fecha'),
            $request->duracion(),
        ));
    }
}
