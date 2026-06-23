<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modelo: Role
 *
 * Define los niveles de acceso del sistema MVP.
 * Valores de slug garantizados por el RoleSeeder: 'admin' | 'operador'.
 *
 * @property int    $id
 * @property string $slug    Identificador de máquina para la lógica de autorización.
 * @property string $nombre  Nombre legible para la UI de administración.
 */
class Role extends Model
{
    protected $table = 'roles';

    protected $fillable = [
        'slug',
        'nombre',
    ];

    // -------------------------------------------------------
    // Relaciones
    // -------------------------------------------------------

    /**
     * Usuarios que tienen asignado este rol.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'role_id');
    }

    // -------------------------------------------------------
    // Helpers de conveniencia
    // -------------------------------------------------------

    /**
     * Verifica si el rol es Administrador.
     * Útil para condicionales de autorización en políticas y middleware.
     */
    public function esAdmin(): bool
    {
        return $this->slug === 'admin';
    }

    /**
     * Verifica si el rol es Operador.
     */
    public function esOperador(): bool
    {
        return $this->slug === 'operador';
    }
}
