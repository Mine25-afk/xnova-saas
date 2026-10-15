@forelse ($puestos as $puesto)
    <tr>
        <td>
            <div class="list-table__name">{{ $puesto->nombre }}</div>
        </td>
        <td>
            <span class="list-status {{ $puesto->active ? 'is-active' : 'is-inactive' }}">
                {{ $puesto->active ? 'Activo' : 'Inactivo' }}
            </span>
        </td>
        <td>
            <div class="list-actions">
                <a href="{{ route('puestos.edit', $puesto) }}" class="list-action list-action--edit">Editar</a>

                <button
                    type="button"
                    class="list-action {{ $puesto->active ? 'list-action--danger' : 'list-action--success' }} js-toggle-status"
                    data-id="{{ $puesto->id }}"
                    data-name="{{ $puesto->nombre }}"
                    data-active="{{ $puesto->active ? 1 : 0 }}"
                    data-url="{{ $puesto->active ? route('puestos.deactivate', $puesto) : route('puestos.activate', $puesto) }}"
                    data-method="{{ $puesto->active ? 'DELETE' : 'PATCH' }}"
                    data-edit-url="{{ route('puestos.edit', $puesto) }}"
                >
                    {{ $puesto->active ? 'Desactivar' : 'Activar' }}
                </button>
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="3" class="list-empty-row">No se encontraron puestos.</td>
    </tr>
@endforelse
