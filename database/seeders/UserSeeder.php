<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder: UserSeeder
 *
 * Crea los dos usuarios fijos de prueba del MVP con contraseñas conocidas
 * para que el equipo pueda autenticarse inmediatamente al levantar el entorno.
 *
 * Credenciales de prueba:
 * ┌─────────────────────────────┬──────────────┬────────────┐
 * │ Email                       │ Contraseña   │ Rol        │
 * ├─────────────────────────────┼──────────────┼────────────┤
 * │ admin@uparcontable.com      │ Admin@1234   │ admin      │
 * │ operador@uparcontable.com   │ Oper@1234    │ operador   │
 * └─────────────────────────────┴──────────────┴────────────┘
 *
 * IMPORTANTE: Ejecutar RoleSeeder antes de este seeder (ver DatabaseSeeder).
 */
class UserSeeder extends Seeder
{
    /**
     * Crea los usuarios fijos de prueba del MVP.
     */
    public function run(): void
    {
        // Recuperar los roles previamente sembrados por RoleSeeder.
        // firstOrFail lanzará una excepción clara si el RoleSeeder no corrió antes,
        // en lugar de un error críptico de FK constraint.
        $roleAdmin    = Role::where('slug', 'admin')->firstOrFail();
        $roleOperador = Role::where('slug', 'operador')->firstOrFail();

        // -------------------------------------------------------
        // Usuario Administrador
        // Tiene acceso completo: reportes financieros, márgenes de
        // ganancia, gestión de usuarios y toda la configuración.
        // -------------------------------------------------------
        User::updateOrCreate(
            ['email' => 'admin@uparcontable.com'],
            [
                'role_id'           => $roleAdmin->id,
                'name'              => 'Administrador UparContable',
                'password'          => Hash::make('Admin@1234'),
                'email_verified_at' => now(),
            ]
        );

        // -------------------------------------------------------
        // Usuario Operador
        // Acceso restringido: puede ver catálogo, registrar
        // movimientos de stock y generar ventas. Bloqueado de
        // ver totales financieros y márgenes de ganancia.
        // -------------------------------------------------------
        User::updateOrCreate(
            ['email' => 'operador@uparcontable.com'],
            [
                'role_id'           => $roleOperador->id,
                'name'              => 'Operador de Prueba',
                'password'          => Hash::make('Oper@1234'),
                'email_verified_at' => now(),
            ]
        );

        $this->command->info('✅ UserSeeder: usuarios admin y operador creados correctamente.');
        $this->command->table(
            ['Email', 'Contraseña', 'Rol'],
            [
                ['admin@uparcontable.com',    'Admin@1234', 'Administrador'],
                ['operador@uparcontable.com', 'Oper@1234',  'Operador'],
            ]
        );
    }
}
