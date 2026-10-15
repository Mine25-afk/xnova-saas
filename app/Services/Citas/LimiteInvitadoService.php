<?php

namespace App\Services\Citas;

use App\Exceptions\BusinessRuleException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Un invitado (sin Google) puede reservar una vez al día por empresa, tanto
 * por sesión como por IP, para frenar reservas basura.
 */
final class LimiteInvitadoService
{
    public function asegurarDisponible(Request $request, int $xnovaId): void
    {
        if ((bool) $request->session()->get($this->claveSesion($xnovaId), false)) {
            throw BusinessRuleException::enCampo('auth_mode', 'Como invitado ya realizaste una reservacion hoy. Intenta nuevamente manana o usa Google.');
        }

        if (Cache::has($this->claveIp($xnovaId, (string) $request->ip()))) {
            throw BusinessRuleException::enCampo('auth_mode', 'Como invitado ya existe una reservacion para hoy desde esta conexion. Intenta manana o usa Google.');
        }
    }

    public function registrarUso(Request $request, int $xnovaId): void
    {
        $request->session()->put($this->claveSesion($xnovaId), true);

        $segundosHastaManana = max(60, (int) now()->diffInSeconds(now()->endOfDay()));

        Cache::put($this->claveIp($xnovaId, (string) $request->ip()), true, $segundosHastaManana);
    }

    private function claveSesion(int $xnovaId): string
    {
        return 'guest_reservation.'.$xnovaId.'.'.now()->toDateString();
    }

    private function claveIp(int $xnovaId, string $ip): string
    {
        return 'guest-reservation:'.$xnovaId.':'.now()->toDateString().':'.md5($ip);
    }
}
