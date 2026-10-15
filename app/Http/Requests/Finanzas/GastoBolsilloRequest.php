<?php

namespace App\Http\Requests\Finanzas;

class GastoBolsilloRequest extends MovimientoBolsilloRequest
{
    public function campoFijo(): string
    {
        return 'finanzas_gasto_fijo_id';
    }

    protected function tablaFijo(): string
    {
        return 'finanzas_gastos_fijos';
    }
}
