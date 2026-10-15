<?php

namespace App\Tenancy\Exceptions;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Intento de escribir un registro en una empresa distinta a la activa.
 */
final class TenantMismatchException extends AccessDeniedHttpException
{
    public function __construct(string $modelo)
    {
        parent::__construct("No se puede asignar {$modelo} a otra empresa.");
    }
}
