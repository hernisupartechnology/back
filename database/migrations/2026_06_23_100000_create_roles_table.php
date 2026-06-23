<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración: Tabla 'roles'
 *
 * Define los niveles de acceso del sistema (admin / operador).
 * Esta tabla debe crearse ANTES que la modificación a 'users',
 * ya que 'users.role_id' depende de esta clave foránea.
 */
return new class extends Migration
{
    /**
     * Ejecuta la migración: crea la tabla de roles.
     */
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            // Clave primaria autoincremental estándar de Laravel.
            $table->id();

            // Identificador único de máquina para lógica de autorización.
            // Valores estrictos del MVP: 'admin' | 'operador'.
            $table->string('slug', 50)->unique();

            // Nombre legible para mostrar en interfaces de administración.
            $table->string('nombre', 100);

            $table->timestamps();
        });
    }

    /**
     * Revierte la migración: elimina la tabla de roles.
     * Nota: La FK en 'users' debe eliminarse ANTES de este drop
     * (ver migración: 2026_06_23_200000_add_role_id_to_users_table).
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
