<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;

/**
 * Autorización base para datos de una empresa: el registro debe ser de la
 * empresa del usuario. Si no lo es se responde 404 para no revelar que existe.
 *
 * El scope global ya impide cargar registros ajenos; esta policy es la
 * segunda línea de defensa (y la única para modelos sin scope, como User).
 */
abstract class TenantOwnedPolicy
{
    public function view(User $user, Model $model): Response
    {
        return $this->mismaEmpresa($user, $model);
    }

    public function update(User $user, Model $model): Response
    {
        return $this->mismaEmpresa($user, $model);
    }

    public function delete(User $user, Model $model): Response
    {
        return $this->mismaEmpresa($user, $model);
    }

    protected function mismaEmpresa(User $user, Model $model): Response
    {
        $xnovaId = $model->getAttribute('xnova_id');

        return $user->perteneceA($xnovaId !== null ? (int) $xnovaId : null)
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}
