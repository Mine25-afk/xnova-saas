<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesCurrentXnova;
use App\Models\Puesto;
use App\Services\SuscripcionLimiteService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PuestoController extends Controller
{
    use ResolvesCurrentXnova;

    public function __construct(
        protected SuscripcionLimiteService $suscripcionLimiteService
    ) {
    }

    public function page(): View
    {
        return view('puestos.index');
    }

    public function create(): View
    {
        return view('puestos.form', [
            'puesto' => new Puesto([
                'active' => true,
            ]),
            'isEditing' => false,
        ]);
    }

    public function edit(Puesto $puesto): View
    {
        abort_if((int) $puesto->xnova_id !== $this->currentXnovaId(), 404);

        return view('puestos.form', [
            'puesto' => $puesto,
            'isEditing' => true,
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:50'],
        ]);

        $search = trim((string) ($validated['search'] ?? ''));
        $perPage = (int) ($validated['per_page'] ?? 10);

        $puestos = $this->puestosBaseQuery($search)
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'rows' => view('puestos.partials.rows', ['puestos' => $puestos])->render(),
            'cards' => view('puestos.partials.cards', ['puestos' => $puestos])->render(),
            'pagination' => view('puestos.partials.pagination', ['puestos' => $puestos])->render(),
            'meta' => [
                'current_page' => $puestos->currentPage(),
                'last_page' => $puestos->lastPage(),
                'per_page' => $puestos->perPage(),
                'total' => $puestos->total(),
                'from' => $puestos->firstItem(),
                'to' => $puestos->lastItem(),
            ],
        ]);
    }

    

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nombre' => [
                'required',
                'string',
                'max:255',
                'unique:puestos,nombre,NULL,id,xnova_id,'.$this->currentXnovaId(),
            ],
        ], [
            'nombre.required' => 'El nombre del puesto es obligatorio.',
            'nombre.unique' => 'Ya existe un puesto con ese nombre.',
        ]);

        $this->suscripcionLimiteService->asegurarCupoPruebaPuestosDisponible($this->currentXnovaId());

        $data['xnova_id'] = $this->currentXnovaId();
        $data['active'] = true;

        $puesto = Puesto::create($data);

        return response()->json([
            'message' => 'Puesto guardado correctamente.',
            'puesto' => $puesto,
        ], 201);
    }

    public function show(Puesto $puesto): JsonResponse
    {
        return response()->json($puesto);
    }

    public function update(Request $request, Puesto $puesto): JsonResponse
    {
        abort_if((int) $puesto->xnova_id !== $this->currentXnovaId(), 404);

        $data = $request->validate([
            'nombre' => ['sometimes', 'string', 'max:100', 'unique:puestos,nombre,'.$puesto->id.',id,xnova_id,'.$this->currentXnovaId()],
        ], [
            'nombre.required' => 'El nombre del puesto es obligatorio.',
            'nombre.unique' => 'Ya existe un puesto con ese nombre.',
        ]);

        $puesto->update($data);

        return response()->json([
            'message' => 'Puesto actualizado correctamente.',
            'puesto' => $puesto->fresh(),
        ]);
    }

    public function destroy(Puesto $puesto): JsonResponse
    {
        abort_if((int) $puesto->xnova_id !== $this->currentXnovaId(), 404);

        $puesto->update([
            'active' => false,
        ]);

        return response()->json([
            'message' => 'Puesto desactivado correctamente.',
            'puesto' => $puesto->fresh(),
        ]);
    }

    public function activate(Puesto $puesto): JsonResponse
    {
        abort_if((int) $puesto->xnova_id !== $this->currentXnovaId(), 404);

        $puesto->update([
            'active' => true,
        ]);

        return response()->json([
            'message' => 'Puesto activado correctamente.',
            'puesto' => $puesto->fresh(),
        ]);
    }
    private function puestosBaseQuery(string $search = ''): Builder
    {
        return Puesto::query()
            ->select(['id', 'nombre', 'active', 'created_at'])
            ->where('xnova_id', $this->currentXnovaId())
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where('nombre', 'like', "%{$search}%");
            });
    }
}
