<?php

namespace Database\Seeders;

use App\Models\Producto;
use Illuminate\Database\Seeder;

/**
 * DatabaseSeeder — Orquestador principal del MVP UparContable.
 *
 * Ejecuta los seeders en el orden jerárquico correcto respetando
 * las dependencias de claves foráneas entre tablas:
 *
 *   1. RoleSeeder     → Crea los roles 'admin' y 'operador'.
 *                       (Requerido antes que users por la FK role_id)
 *
 *   2. UserSeeder     → Crea los usuarios fijos de prueba con roles asignados.
 *                       (Requerido antes de cualquier movimiento o venta)
 *
 *   3. ProductoFactory → Genera 20 productos del catálogo con distribución
 *                        realista de stock para pruebas del MVP.
 *
 * Uso:
 *   php artisan db:seed               (solo seeders)
 *   php artisan migrate --seed        (migrar y sembrar en un solo paso)
 *   php artisan migrate:fresh --seed  (borrar todo y empezar de cero)
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Ejecuta todos los seeders del MVP en orden jerárquico.
     */
    public function run(): void
    {
        // -------------------------------------------------------
        // PASO 1: Roles (sin dependencias externas)
        // -------------------------------------------------------
        $this->call(RoleSeeder::class);

        // -------------------------------------------------------
        // PASO 2: Usuarios fijos de prueba (depende de roles)
        // -------------------------------------------------------
        $this->call(UserSeeder::class);

        // -------------------------------------------------------
        // PASO 3: Catálogo de productos (sin dependencias de FK,
        // pero se siembra al final para que el sistema esté listo
        // para pruebas de ventas y movimientos de inmediato).
        //
        // Se generan 20 productos con la distribución de stock:
        //   ~10 con stock saludable  (sin alerta)
        //   ~5  con stock bajo       (alerta activa)
        //   ~5  con stock agotado    (alerta crítica)
        // -------------------------------------------------------
        Producto::factory(20)->create();

        $this->command->info('');
        $this->command->info('🚀 Base de datos sembrada exitosamente.');
        $this->command->info('   Roles: admin, operador');
        $this->command->info('   Usuarios de prueba: admin@uparcontable.com / operador@uparcontable.com');
        $this->command->info('   Productos generados: 20');
    }
}
