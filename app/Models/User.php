<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Modelo: User
 *
 * Extiende el Authenticatable nativo de Laravel.
 * Vinculado a un Rol para el control de acceso (RBAC).
 *
 * @property int         $id
 * @property int|null    $role_id
 * @property string      $name
 * @property string      $email
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'role_id',
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    // -------------------------------------------------------
    // Relaciones
    // -------------------------------------------------------

    /**
     * El rol asignado al usuario (Admin / Operador).
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    /**
     * Movimientos de inventario registrados por este usuario.
     */
    public function movimientosInventario(): HasMany
    {
        return $this->hasMany(MovimientoInventario::class, 'user_id');
    }

    /**
     * Registros de flujo de caja creados por este usuario.
     */
    public function flujoCaja(): HasMany
    {
        return $this->hasMany(FlujoCaja::class, 'user_id');
    }
}
