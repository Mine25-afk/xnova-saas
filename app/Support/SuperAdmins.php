<?php

namespace App\Support;

use App\Models\User;

/**
 * Punto único para resolver si un usuario es superadministrador, según la
 * lista de correos en config/superadmin.php.
 */
class SuperAdmins
{
    /**
     * Correos autorizados, normalizados a minúsculas.
     *
     * @return array<int, string>
     */
    public static function emails(): array
    {
        return array_map('strtolower', (array) config('superadmin.emails', []));
    }

    public static function has(?User $user): bool
    {
        if (! $user?->email) {
            return false;
        }

        return in_array(strtolower($user->email), self::emails(), true);
    }
}
