<?php

namespace App\Http\Controllers\Citas;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class CitaReporteController extends Controller
{
    public function __invoke(): View
    {
        return view('citas.placeholder', [
            'title' => 'Reportes de citas',
            'description' => 'Pronto mostraremos aqui los reportes operativos de citas.',
        ]);
    }
}
