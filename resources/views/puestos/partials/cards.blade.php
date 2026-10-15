@forelse ($puestos as $puesto)
    <article
        class="list-card-item js-puesto-card"
        data-id="{{ $puesto->id }}"
        data-name="{{ $puesto->nombre }}"
        data-active="{{ $puesto->active ? 1 : 0 }}"
        data-url="{{ $puesto->active ? route('puestos.deactivate', $puesto) : route('puestos.activate', $puesto) }}"
        data-method="{{ $puesto->active ? 'DELETE' : 'PATCH' }}"
        data-edit-url="{{ route('puestos.edit', $puesto) }}"
    >
        <div class="list-card-item__title">{{ $puesto->nombre }}</div>

        <div class="list-card-item__footer">
            <span class="list-status {{ $puesto->active ? 'is-active' : 'is-inactive' }}">
                {{ $puesto->active ? 'Activo' : 'Inactivo' }}
            </span>

            <span class="list-card-item__hint">Acciones</span>
        </div>
    </article>
@empty
    <div class="list-empty">No se encontraron puestos.</div>
@endforelse
