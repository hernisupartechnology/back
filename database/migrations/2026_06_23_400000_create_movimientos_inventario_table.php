<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración: Tabla 'movimientos_inventario'
 *
 * Kardex / historial de auditoría del inventario físico.
 * Cada registro representa un cambio en el stock:
 *   - 'entrada': incrementa stock_actual del producto.
 *   - 'salida' : decrementa stock_actual del producto.
 *
 * DEPENDENCIAS:
 *   - productos (2026_06_23_300000_create_productos_table)
 *   - users     (nativa + alterada en 2026_06_23_200000)
 */
return new class extends Migration
{
    /**
     * Ejecuta la migración: crea la tabla de movimientos de inventario.
     */
    public function up(): void
    {
        Schema::create('movimientos_inventario', function (Blueprint $table) {
            // Clave primaria autoincremental.
            $table->id();

            // Producto afectado por el movimiento.
            // ON DELETE RESTRICT: protege el historial si se intenta
            // borrar físicamente un producto (los SoftDeletes lo previenen,
            // pero esta FK es la última línea de defensa).
            $table->unsignedBigInteger('producto_id');
            $table->foreign('producto_id')
                  ->references('id')
                  ->on('productos')
                  ->restrictOnDelete();

            // Usuario responsable de registrar el movimiento (trazabilidad).
            // ON DELETE RESTRICT: no se puede eliminar un usuario con
            // movimientos registrados.
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->restrictOnDelete();

            // Tipo de movimiento: 'entrada' (compra/ajuste positivo)
            // o 'salida' (venta/baja/daño).
            $table->enum('tipo', ['entrada', 'salida']);

            // Unidades involucradas en el movimiento. Siempre positivo;
            // la dirección la define el campo 'tipo'.
            $table->integer('cantidad');

            // Descripción del origen del movimiento para auditoría.
            // Ejemplos: 'compra_proveedor', 'venta_factura_001', 'baja_daño'.
            $table->string('motivo', 255);

            $table->timestamps();
        });
    }

    /**
     * Revierte la migración: elimina la tabla de movimientos de inventario.
     */
    public function down(): void
    {
        Schema::dropIfExists('movimientos_inventario');
    }
};
