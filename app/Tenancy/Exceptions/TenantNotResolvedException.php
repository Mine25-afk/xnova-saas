<?php

namespace App\Tenancy\Exceptions;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * No hay una empresa activa para el usuario o proceso actual: se responde 403.
 */
final class TenantNotResolvedException extends AccessDeniedHttpException
{
    public function __construct(string $message = 'No hay una empresa activa para esta sesión.')
    {
        parent::__construct($message);
    }
}
