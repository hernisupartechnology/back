<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeder: RoleSeeder
 *
 * Inserta los dos roles estrictos del MVP en la tabla 'roles'.
 * Se usa updateOrInsert() para que el seeder sea idempotente:
 * se puede ejecutar múltiples veces sin duplicar registros,
 * lo cual es esencial durante el desarrollo iterativo.
 *
 * IMPORTANTE: Este seeder debe ejecutarse PRIMERO, antes que
 * UserSeeder, ya que los usuarios dependen de esta tabla (FK).
 */
class RoleSeeder extends Seeder
{
    /**
     * Inserta o actualiza los roles del sistema.
     */
    public function run(): void
    {
        $roles = [
            [
                // Acceso total: productos, movimientos, ventas y reportes financieros.
                'slug'   => 'admin',
                'nombre' => 'Administrador',
            ],
            [
                // Acceso restringido: catálogo, stock y ventas. Bloqueado de financieros.
                'slug'   => 'operador',
                'nombre' => 'Operador',
            ],
        ];

        foreach ($roles as $rol) {
            DB::table('roles')->updateOrInsert(
                // Condición de búsqueda: el slug es el identificador único de máquina.
                ['slug' => $rol['slug']],
                // Datos a insertar o actualizar si ya existe.
                array_merge($rol, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        $this->command->info('✅ RoleSeeder: roles "admin" y "operador" insertados correctamente.');
    }
}
