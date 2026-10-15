<?php

namespace App\Http\Requests\Finanzas;

class DeudaRequest extends SaldoPendienteRequest
{
    protected function campoContraparte(): string
    {
        return 'acreedor';
    }
}
