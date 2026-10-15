<?php

namespace App\Http\Requests\Finanzas;

class CuentaPorCobrarRequest extends SaldoPendienteRequest
{
    protected function campoContraparte(): string
    {
        return 'deudor';
    }
}
