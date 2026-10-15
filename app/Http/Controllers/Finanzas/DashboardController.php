<?php

namespace App\Http\Controllers\Finanzas;

use App\Services\Finanzas\DashboardFinanzasService;
use Illuminate\View\View;

class DashboardController extends BaseFinanzasController
{
    public function __invoke(DashboardFinanzasService $dashboard): View
    {
        return view('finanzas.dashboard', $dashboard->resumen());
    }
}
