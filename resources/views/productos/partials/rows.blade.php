@forelse ($productos as $producto)
    <tr>
        <td>
            <div class="product-cell">
                @if ($producto->imagen_url)
                    <img src="{{ $producto->imagen_url }}" alt="{{ $producto->nombre }}" class="product-thumb">
                @else
                    <div class="product-thumb product-thumb-placeholder">
                        {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($producto->nombre, 0, 1)) }}
                    </div>
                @endif

                <div class="product-meta">
                    <div class="list-table__name">{{ $producto->nombre }}</div>
                    <div class="product-image-note">{{ $producto->imagen_url ? 'Imagen optimizada en WebP' : 'Sin imagen' }}</div>
                </div>
            </div>
        </td>
        <td>{{ $producto->descripcion ?: 'Sin descripcion' }}</td>
        <td>
            <span class="product-status {{ $producto->active ? 'is-active' : 'is-inactive' }}">
                {{ $producto->active ? 'Activo' : 'Inactivo' }}
            </span>
        </td>
        <td class="product-price">{{ number_format((float) $producto->precio, 2) }}</td>
        <td>
            <div class="list-actions">
                <a href="{{ route('inventario.productos.edit', $producto) }}" class="list-action list-action--edit">Editar</a>
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="5" class="list-empty-row">No se encontraron productos.</td>
    </tr>
@endforelse
