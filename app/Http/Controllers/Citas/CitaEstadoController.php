<?php

namespace App\Http\Controllers\Citas;

use App\Http\Controllers\Controller;
use App\Models\Cita;
use App\Repositories\Citas\CitaRepository;
use App\Services\Citas\CitaService;
use Illuminate\Http\JsonResponse;

/**
 * Transiciones de estado de una cita (terminar y cancelar).
 */
class CitaEstadoController extends Controller
{
    public function __construct(private readonly CitaService $citaService) {}

    public function terminar(Cita $cita): JsonResponse
    {
        $this->authorize('view', $cita);

        $this->citaService->terminar($cita);

        return response()->json([
            'message' => 'Cita marcada como terminada.',
            'cita' => $cita->fresh()?->load(CitaRepository::RELACIONES),
        ]);
    }

    public function cancelar(Cita $cita): JsonResponse
    {
        $this->authorize('view', $cita);

        $this->citaService->cancelar($cita);

        return response()->json([
            'message' => 'Cita cancelada correctamente.',
            'cita' => $cita->fresh()?->load(CitaRepository::RELACIONES),
        ]);
    }
}
