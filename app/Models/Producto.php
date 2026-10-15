<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Producto extends Model
{
    protected $table = 'productos';

    protected $fillable = [
        'nombre',
        'descripcion',
        'precio',
        'active',
        'imagen_path',
        'xnova_id',
    ];

    protected function casts(): array
    {
        return [
            'precio' => 'decimal:2',
            'active' => 'boolean',
        ];
    }

    public function getImagenUrlAttribute(): ?string
    {
        if (! filled($this->imagen_path)) {
            return null;
        }

        return Storage::disk('public')->url($this->imagen_path);
    }

    public function xnova(): BelongsTo
    {
        return $this->belongsTo(Xnova::class, 'xnova_id');
    }

    public function citas(): HasMany
    {
        return $this->hasMany(Cita::class, 'producto_id');
    }
}
