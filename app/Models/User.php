<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Usuario del sistema. No usa el scope de tenant porque la autenticación debe
 * poder encontrarlo antes de saber a qué empresa pertenece; el aislamiento se
 * hace con UserPolicy y filtros explícitos por xnova_id.
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'google_id',
        'email_verified_at',
        'xnova_id',
        'rol_id',
        'sucursal_Id',
        'colaborador_dni',
        'active',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Xnova, $this>
     */
    public function xnova(): BelongsTo
    {
        return $this->belongsTo(Xnova::class, 'xnova_id');
    }

    /**
     * @return BelongsTo<Rol, $this>
     */
    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class, 'rol_id');
    }

    /**
     * @return BelongsTo<Sucursal, $this>
     */
    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_Id');
    }

    /**
     * @return BelongsTo<Colaborador, $this>
     */
    public function colaborador(): BelongsTo
    {
        return $this->belongsTo(Colaborador::class, 'colaborador_dni', 'dni');
    }

    /**
     * @return HasMany<UserLog, $this>
     */
    public function logs(): HasMany
    {
        return $this->hasMany(UserLog::class, 'User_Id');
    }

    public function perteneceA(?int $xnovaId): bool
    {
        return $xnovaId !== null && (int) $this->xnova_id === $xnovaId;
    }
}
