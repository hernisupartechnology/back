<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración: Modificación de la tabla 'users' nativa de Laravel.
 *
 * Se añade la columna 'role_id' como clave foránea hacia 'roles'.
 * Se usa Schema::table() —NO Schema::create()— para no destruir
 * la tabla existente ni sus datos.
 *
 * IMPORTANTE: Esta migración depende de que la tabla 'roles'
 * ya exista (migración: 2026_06_23_100000_create_roles_table).
 */
return new class extends Migration
{
    /**
     * Ejecuta la alteración: añade role_id a la tabla users.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Se coloca 'after id' para mantener un orden lógico
            // en la estructura de la tabla (id → role → datos personales).
            $table->unsignedBigInteger('role_id')
                  ->nullable()          // Nullable para no romper usuarios pre-existentes durante la migración.
                  ->after('id');

            // ON DELETE RESTRICT: impide eliminar un rol que tenga
            // usuarios asignados, protegiendo la integridad referencial.
            $table->foreign('role_id')
                  ->references('id')
                  ->on('roles')
                  ->restrictOnDelete();
        });
    }

    /**
     * Revierte la alteración: elimina la FK y la columna role_id.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Primero se elimina la restricción de FK, luego la columna.
            $table->dropForeign(['role_id']);
            $table->dropColumn('role_id');
        });
    }
};
