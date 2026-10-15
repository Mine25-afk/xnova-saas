<?php

namespace App\Http\Controllers\Citas;

use App\Http\Controllers\Controller;
use App\Http\Middleware\ResolvePublicTenant;
use App\Http\Requests\Citas\ReservaPublicaRequest;
use App\Models\Xnova;
use App\Services\Citas\LimiteInvitadoService;
use App\Services\Citas\ReservaPublicaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Formulario público de reserva. La empresa la resuelve ResolvePublicTenant
 * a partir del uid de la URL.
 */
class ReservaPublicaController extends Controller
{
    public function __construct(
        private readonly ReservaPublicaService $reservas,
        private readonly LimiteInvitadoService $limiteInvitado,
    ) {}

    public function show(Request $request): View
    {
        return view('citas.public-reservation', $this->reservas->datosFormulario($this->empresa($request)) + [
            'googleClientId' => (string) config('services.google.client_id'),
        ]);
    }

    public function store(ReservaPublicaRequest $request): RedirectResponse
    {
        $empresa = $this->empresa($request);
        $data = $request->toDto();

        if ($data->esInvitado()) {
            $this->limiteInvitado->asegurarDisponible($request, (int) $empresa->id);
        }

        $cita = $this->reservas->reservar($empresa, $data);

        if ($data->esInvitado()) {
            $this->limiteInvitado->registrarUso($request, (int) $empresa->id);
        }

        return redirect()
            ->route('reservaciones.public.form', $empresa->reservacion_uid)
            ->with('success', 'Reservacion enviada correctamente. Numero de cita: #'.$cita->id.'.');
    }

    private function empresa(Request $request): Xnova
    {
        $empresa = $request->attributes->get(ResolvePublicTenant::ATRIBUTO);

        abort_unless($empresa instanceof Xnova, 404);

        return $empresa;
    }
}
