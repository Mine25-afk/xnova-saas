<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\UsaEmpresaActual;
use App\Models\Producto;
use App\Services\ProductImageProcessor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\JsonResponse;

class ProductoController extends Controller
{
    use UsaEmpresaActual;

    public function __construct(
        protected ProductImageProcessor $productImageProcessor
    ) {
    }

    public function index(): View
    {
        return view('productos.index');
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

        $productos = $this->productosBaseQuery($search)
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'rows' => view('productos.partials.rows', ['productos' => $productos])->render(),
            'cards' => view('productos.partials.cards', ['productos' => $productos])->render(),
            'pagination' => view('productos.partials.pagination', ['productos' => $productos])->render(),
            'meta' => [
                'current_page' => $productos->currentPage(),
                'last_page' => $productos->lastPage(),
                'per_page' => $productos->perPage(),
                'total' => $productos->total(),
                'from' => $productos->firstItem(),
                'to' => $productos->lastItem(),
            ],
        ]);
    }

    public function create(): View
    {
        return view('productos.form', [
            'producto' => new Producto(),
            'isEditing' => false,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validatePayload($request);
        $data['xnova_id'] = $this->empresaId();
        $storedImagePath = null;

        if ($request->hasFile('imagen')) {
            $storedImagePath = $this->productImageProcessor->store($request->file('imagen'), $data['xnova_id']);
            $data['imagen_path'] = $storedImagePath;
        }

        try {
            $producto = Producto::query()->create($data);
        } catch (\Throwable $exception) {
            $this->productImageProcessor->delete($storedImagePath);

            throw $exception;
        }

        return response()->json([
            'message' => 'Producto guardado correctamente.',
            'producto' => $producto->fresh(),
        ], 201);
    }

    public function edit(Producto $producto): View
    {
        $this->asegurarProductoActual($producto);

        return view('productos.form', [
            'producto' => $producto,
            'isEditing' => true,
        ]);
    }

    public function update(Request $request, Producto $producto): JsonResponse
    {
        $this->asegurarProductoActual($producto);

        $data = $this->validatePayload($request, $producto);
        $storedImagePath = null;
        $imageToDelete = null;

        if ($request->hasFile('imagen')) {
            $storedImagePath = $this->productImageProcessor->store($request->file('imagen'), $this->empresaId());
            $data['imagen_path'] = $storedImagePath;
            $imageToDelete = $producto->imagen_path;
        } elseif ($request->boolean('remove_image')) {
            $data['imagen_path'] = null;
            $imageToDelete = $producto->imagen_path;
        }

        try {
            $producto->update($data);
        } catch (\Throwable $exception) {
            $this->productImageProcessor->delete($storedImagePath);

            throw $exception;
        }

        if ($imageToDelete && $imageToDelete !== ($data['imagen_path'] ?? null)) {
            $this->productImageProcessor->delete($imageToDelete);
        }

        return response()->json([
            'message' => 'Producto actualizado correctamente.',
            'producto' => $producto->fresh(),
        ]);
    }

    protected function validatePayload(Request $request, ?Producto $producto = null): array
    {
        return $request->validate([
            'nombre' => [
                'required',
                'string',
                'max:120',
                'unique:productos,nombre,'.($producto?->id ?? 'NULL').',id,xnova_id,'.$this->empresaId(),
            ],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'precio' => ['required', 'numeric', 'min:0'],
            'active' => ['nullable', 'boolean'],
            'imagen' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_image' => ['nullable', 'boolean'],
        ], [
            'nombre.required' => 'El nombre del producto es obligatorio.',
            'nombre.unique' => 'Ya existe un producto con ese nombre.',
            'precio.required' => 'El precio es obligatorio.',
            'precio.numeric' => 'El precio debe ser numerico.',
            'precio.min' => 'El precio no puede ser negativo.',
            'imagen.image' => 'Debes subir una imagen valida.',
            'imagen.mimes' => 'Solo se permiten imagenes JPG, PNG o WebP.',
            'imagen.max' => 'La imagen no puede superar los 5 MB.',
        ]);
    }

    protected function productosBaseQuery(string $search = ''): Builder
    {
        return Producto::query()
            ->select(['id', 'nombre', 'descripcion', 'precio', 'active', 'imagen_path', 'created_at'])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $subQuery) use ($search): void {
                    $subQuery
                        ->where('nombre', 'like', "%{$search}%")
                        ->orWhere('descripcion', 'like', "%{$search}%");
                });
            });
    }

    protected function asegurarProductoActual(Producto $producto): void
    {
        $this->authorize('view', $producto);
    }
}
