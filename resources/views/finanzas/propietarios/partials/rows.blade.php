@forelse ($propietarios as $propietario)
    <tr>
        <td>
            <div class="list-table__name">{{ $propietario->nombre }}</div>
        </td>
        <td>{{ optional($propietario->created_at)->format('d/m/Y h:i A') ?? 'Sin fecha' }}</td>
        <td>
            <div class="list-actions">
                <a href="{{ route('finanzas.propietarios.show', $propietario) }}" class="list-action list-action--info">Detalles</a>
                <a href="{{ route('finanzas.propietarios.edit', $propietario) }}" class="list-action list-action--edit">Editar</a>
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="3" class="list-empty-row">No se encontraron propietarios.</td>
    </tr>
@endforelse
