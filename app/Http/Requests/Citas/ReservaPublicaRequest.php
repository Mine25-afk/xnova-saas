<?php

namespace App\Http\Requests\Citas;

use App\DTOs\Citas\ReservaPublicaData;
use App\Http\Requests\TenantFormRequest;
use Illuminate\Validation\Rule;

/**
 * La empresa la fija el middleware ResolvePublicTenant a partir del uid público.
 */
class ReservaPublicaRequest extends TenantFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $google = ReservaPublicaData::MODO_GOOGLE;

        return [
            'auth_mode' => ['required', Rule::in([ReservaPublicaData::MODO_GOOGLE, ReservaPublicaData::MODO_INVITADO])],
            'google_email' => ["required_if:auth_mode,{$google}", 'nullable', 'string', 'email', 'max:150'],
            'google_name' => ["required_if:auth_mode,{$google}", 'nullable', 'string', 'max:150'],
            'producto_id' => ['required', 'integer', $this->existeEnEmpresa('productos')->where('active', true)],
            'colaborador_dni' => ['required', $this->existeEnEmpresa('colaboradores', 'dni')],
            'fecha' => ['required', 'date', 'after_or_equal:'.now()->toDateString()],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'hora_salida' => ['required', 'date_format:H:i', 'after:hora_inicio'],
            'comentario' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'auth_mode.required' => 'Selecciona Google o invitado para continuar.',
            'google_email.required_if' => 'Debes proporcionar tu correo de Google.',
            'google_email.email' => 'El correo de Google no es valido.',
            'google_name.required_if' => 'Debes proporcionar tu nombre para Google.',
            'producto_id.required' => 'Debes seleccionar un producto.',
            'colaborador_dni.required' => 'Debes seleccionar un colaborador.',
            'fecha.required' => 'La fecha es obligatoria.',
            'fecha.after_or_equal' => 'La fecha no puede ser menor a la fecha actual.',
            'hora_inicio.required' => 'La hora de inicio es obligatoria.',
            'hora_salida.required' => 'La hora de salida es obligatoria.',
            'hora_salida.after' => 'La hora de salida debe ser posterior a la hora de inicio.',
        ];
    }

    public function toDto(): ReservaPublicaData
    {
        $texto = fn (string $campo): ?string => is_string($this->validated($campo)) ? $this->validated($campo) : null;

        return new ReservaPublicaData(
            modo: (string) $this->validated('auth_mode'),
            productoId: $this->integer('producto_id'),
            colaboradorDni: (string) $this->validated('colaborador_dni'),
            fecha: (string) $this->validated('fecha'),
            horaInicio: (string) $this->validated('hora_inicio'),
            horaSalida: (string) $this->validated('hora_salida'),
            comentario: $texto('comentario'),
            googleEmail: $texto('google_email'),
            googleNombre: $texto('google_name'),
        );
    }
}
