@forelse ($productos as $producto)
    <article class="list-card-item">
        <div class="product-cell">
            @if ($producto->imagen_url)
                <img src="{{ $producto->imagen_url }}" alt="{{ $producto->nombre }}" class="product-thumb">
            @else
                <div class="product-thumb product-thumb-placeholder">
                    {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($producto->nombre, 0, 1)) }}
                </div>
            @endif

            <div>
                <div class="list-card-item__title">{{ $producto->nombre }}</div>
                <div class="list-card-item__meta">Precio: {{ number_format((float) $producto->precio, 2) }}</div>
            </div>
        </div>

        <div class="list-card-item__copy">
            {{ $producto->descripcion ?: 'Sin descripcion' }}
        </div>

        <div class="list-card-item__footer">
            <a href="{{ route('inventario.productos.edit', $producto) }}" class="list-action list-action--edit">Editar</a>
            <span class="product-status {{ $producto->active ? 'is-active' : 'is-inactive' }}">
                {{ $producto->active ? 'Activo' : 'Inactivo' }}
            </span>
        </div>
    </article>
@empty
    <div class="list-empty">No se encontraron productos.</div>
@endforelse
