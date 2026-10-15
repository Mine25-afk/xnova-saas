<?php

namespace App\Policies;

use App\Models\Cita;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;

class CitaPolicy extends TenantOwnedPolicy
{
    /**
     * Solo las citas reservadas o autorizadas pueden editarse.
     */
    public function update(User $user, Model $model): Response
    {
        $acceso = $this->mismaEmpresa($user, $model);

        if ($acceso->denied() || ! $model instanceof Cita) {
            return $acceso;
        }

        return $model->estaEditable()
            ? Response::allow()
            : Response::deny('Solo las citas reservadas o autorizadas pueden editarse.');
    }
}
