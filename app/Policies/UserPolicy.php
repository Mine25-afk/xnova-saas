<?php

namespace App\Policies;

/**
 * User no tiene scope de tenant (la autenticación debe encontrarlo antes de
 * conocer su empresa), por eso esta policy es la que impide administrar
 * usuarios de otra empresa.
 */
class UserPolicy extends TenantOwnedPolicy {}
