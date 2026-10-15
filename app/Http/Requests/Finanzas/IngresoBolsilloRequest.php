<?php

namespace App\Http\Requests\Finanzas;

class IngresoBolsilloRequest extends MovimientoBolsilloRequest
{
    public function campoFijo(): string
    {
        return 'finanzas_ingreso_fijo_id';
    }

    protected function tablaFijo(): string
    {
        return 'finanzas_ingresos_fijos';
    }
}
