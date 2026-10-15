<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Xnova;

trait ResolvesCurrentXnova
{
    protected function currentXnovaId(): int
    {
        $userXnovaId = auth()->user()?->xnova_id;

        if ($userXnovaId) {
            return (int) $userXnovaId;
        }

        $fallbackXnovaId = Xnova::query()->value('id');

        if ($fallbackXnovaId) {
            return (int) $fallbackXnovaId;
        }

        abort(500, 'No existe ninguna xNova configurada.');
    }
}
