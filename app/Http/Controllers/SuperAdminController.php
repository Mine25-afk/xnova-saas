<?php

namespace App\Http\Controllers;

use App\Models\Xnova;
use Illuminate\View\View;

class SuperAdminController extends Controller
{
    /**
     * Listado de todas las empresas (xnovas) con su estado de suscripción,
     * paquete y módulos. Solo lectura (v1).
     */
    public function empresas(): View
    {
        $xnovas = Xnova::query()
            ->with([
                'tipo:id,tipo',
                'tipo.modulos:id,nombre,codigo',
                'owner:id,name,email',
                'xnovaPaquetes' => fn ($q) => $q->orderByDesc('activo')->orderByDesc('id')->with(['paquete', 'suscripciones' => fn ($s) => $s->orderByDesc('id')]),
            ])
            ->orderBy('nombre')
            ->get();

        $empresas = $xnovas->map(fn (Xnova $xnova) => $this->resumen($xnova))->values();

        $stats = [
            'total' => $empresas->count(),
            'en_prueba' => $empresas->where('estado_codigo', 'trial')->count(),
            'vencidas' => $empresas->where('estado_codigo', 'vencido')->count(),
            'activas' => $empresas->where('estado_codigo', 'activo')->count(),
            'inactivas' => $empresas->where('activa', false)->count(),
        ];

        return view('superadmin.empresas.index', [
            'empresas' => $empresas,
            'stats' => $stats,
        ]);
    }

    /**
     * Detalle de una empresa: dueño, suscripción, paquete y módulos.
     */
    public function showEmpresa(Xnova $xnova): View
    {
        $xnova->load([
            'tipo:id,tipo',
            'tipo.modulos:id,nombre,codigo',
            'owner:id,name,email',
            'xnovaPaquetes' => fn ($q) => $q->orderByDesc('activo')->orderByDesc('id')->with(['paquete', 'suscripciones' => fn ($s) => $s->orderByDesc('id')]),
        ]);

        return view('superadmin.empresas.show', [
            'empresa' => $this->resumen($xnova),
        ]);
    }

    /**
     * Arma un resumen homogéneo del estado de una empresa.
     *
     * @return array<string, mixed>
     */
    protected function resumen(Xnova $xnova): array
    {
        // Paquete vigente: el activo más reciente, o el último registrado.
        $xp = $xnova->xnovaPaquetes->firstWhere('activo', true) ?? $xnova->xnovaPaquetes->first();
        $suscripcion = $xp?->suscripciones->first();

        $esTrial = $suscripcion
            && (($suscripcion->proveedor === 'trial') || in_array($suscripcion->estado, ['trial', 'trialing'], true));

        // Fecha de fin: la del periodo de la suscripción o la del contrato (trial).
        $fechaFin = $suscripcion?->periodo_fin ?? $xp?->fecha_fin;

        $diasRestantes = null;
        $vencido = false;
        if ($fechaFin) {
            $diasRestantes = (int) round(now()->startOfDay()->diffInDays($fechaFin->copy()->startOfDay(), false));
            $vencido = $diasRestantes < 0;
        }

        // Estado normalizado.
        if (! $xp) {
            $estadoCodigo = 'sin_paquete';
            $estadoLabel = 'Sin paquete';
        } elseif ($esTrial) {
            $estadoCodigo = $vencido ? 'vencido' : 'trial';
            $estadoLabel = $vencido ? 'Prueba vencida' : 'En prueba';
        } elseif ($vencido) {
            $estadoCodigo = 'vencido';
            $estadoLabel = 'Vencido';
        } else {
            $estadoCodigo = 'activo';
            $estadoLabel = 'Activo';
        }

        $modulos = $xnova->tipo?->modulos
            ?->map(fn ($m) => ['nombre' => $m->nombre, 'codigo' => $m->codigo])
            ->values()
            ->all() ?? [];

        return [
            'id' => $xnova->id,
            'nombre' => $xnova->nombre,
            'tipo' => $xnova->tipo?->tipo,
            'activa' => (bool) $xnova->active,
            'creada' => $xnova->created_at,
            'owner_nombre' => $xnova->owner?->name,
            'owner_email' => $xnova->owner?->email,
            'paquete' => $xp?->paquete?->nombre,
            'precio_mensual' => $xp ? (float) $xp->precio_mensual : null,
            'moneda' => $xp?->paquete?->moneda ?? $suscripcion?->moneda ?? 'USD',
            'estado_codigo' => $estadoCodigo,
            'estado_label' => $estadoLabel,
            'proveedor' => $suscripcion?->proveedor,
            'fecha_fin' => $fechaFin,
            'dias_restantes' => $diasRestantes,
            'usuarios_totales' => $suscripcion?->usuarios_totales,
            'modulos' => $modulos,
            'modulos_count' => count($modulos),
        ];
    }
}
