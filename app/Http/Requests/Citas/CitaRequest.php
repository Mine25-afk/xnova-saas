<?php

namespace App\Http\Requests\Citas;

use App\DTOs\Citas\CitaData;
use App\Http\Requests\TenantFormRequest;
use App\Models\SocioNegocio;
use Carbon\Carbon;

class CitaRequest extends TenantFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'cliente_dni' => [
                'required',
                $this->existeEnEmpresa('socio_negocios', 'dni')
                    ->where('tipo', SocioNegocio::TIPO_CLIENTE)
                    ->where('active', true),
            ],
            'colaborador_dni' => ['required', $this->existeEnEmpresa('colaboradores', 'dni')],
            'sucursal_Id' => ['required', 'integer', $this->existeEnEmpresa('sucursales')],
            'producto_id' => ['nullable', 'integer', $this->existeEnEmpresa('productos')],
            'fecha' => ['required', 'date', 'after_or_equal:'.now()->toDateString()],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'hora_salida' => ['required', 'date_format:H:i', 'after:hora_inicio'],
            'descripcion' => ['required', 'string', 'max:255'],
            'comentario' => ['nullable', 'string', 'max:500'],
            'confirmar_fuera_horario' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'cliente_dni.required' => 'Debes seleccionar un cliente.',
            'cliente_dni.exists' => 'El cliente seleccionado no es valido.',
            'colaborador_dni.required' => 'Debes seleccionar un empleado.',
            'colaborador_dni.exists' => 'El empleado seleccionado no es valido.',
            'sucursal_Id.required' => 'Debes seleccionar una sucursal.',
            'sucursal_Id.exists' => 'La sucursal seleccionada no es valida.',
            'producto_id.exists' => 'El producto seleccionado no es valido.',
            'fecha.required' => 'La fecha es obligatoria.',
            'fecha.after_or_equal' => 'La fecha no puede ser menor a '.Carbon::now()->format('d/m/Y').'.',
            'hora_inicio.required' => 'La hora de inicio es obligatoria.',
            'hora_salida.required' => 'La hora de salida es obligatoria.',
            'hora_salida.after' => 'La hora de salida debe ser posterior a la hora de inicio.',
            'descripcion.required' => 'La descripcion es obligatoria.',
        ];
    }

    public function toDto(): CitaData
    {
        $comentario = $this->validated('comentario');

        return new CitaData(
            clienteDni: (string) $this->validated('cliente_dni'),
            colaboradorDni: (string) $this->validated('colaborador_dni'),
            sucursalId: $this->integer('sucursal_Id'),
            fecha: (string) $this->validated('fecha'),
            horaInicio: (string) $this->validated('hora_inicio'),
            horaSalida: (string) $this->validated('hora_salida'),
            descripcion: (string) $this->validated('descripcion'),
            productoId: $this->enteroOpcional('producto_id'),
            comentario: is_string($comentario) ? $comentario : null,
            confirmarFueraHorario: $this->boolean('confirmar_fuera_horario'),
        );
    }
}
