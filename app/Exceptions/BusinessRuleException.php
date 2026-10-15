<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Violación de una regla de negocio (saldo insuficiente, cita fuera de horario...).
 *
 * Los servicios la lanzan sin saber si los llamó un formulario o una petición
 * AJAX; aquí se decide la respuesta: JSON 422 o redirect con errores.
 */
class BusinessRuleException extends RuntimeException
{
    /**
     * @param  array<string, list<string>>  $errors
     * @param  array<string, mixed>  $extra
     */
    public function __construct(
        string $message,
        private readonly array $errors = [],
        private readonly array $extra = [],
        private readonly int $status = 422,
    ) {
        parent::__construct($message);
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    public static function enCampo(string $campo, string $mensaje, array $extra = []): self
    {
        return new self($mensaje, [$campo => [$mensaje]], $extra);
    }

    /**
     * @param  list<string>  $campos
     * @param  array<string, mixed>  $extra
     */
    public static function enCampos(array $campos, string $mensaje, array $extra = []): self
    {
        $errors = [];

        foreach ($campos as $campo) {
            $errors[$campo] = [$mensaje];
        }

        return new self($mensaje, $errors, $extra);
    }

    /**
     * @return array<string, list<string>>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * @return array<string, mixed>
     */
    public function extra(): array
    {
        return $this->extra;
    }

    public function status(): int
    {
        return $this->status;
    }

    public function render(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            $payload = ['message' => $this->getMessage()];

            if ($this->errors !== []) {
                $payload['errors'] = $this->errors;
            }

            return response()->json(array_merge($payload, $this->extra), $this->status);
        }

        $errors = $this->errors !== [] ? $this->errors : ['general' => [$this->getMessage()]];

        return back()->withInput()->withErrors($errors);
    }
}
