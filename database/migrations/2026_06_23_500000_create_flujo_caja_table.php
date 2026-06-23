<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración: Tabla 'flujo_caja'
 *
 * Libro contable simplificado para el seguimiento del flujo de efectivo.
 * Registra tanto ingresos automáticos (generados por ventas) como
 * egresos manuales (gastos operativos). Es la fuente de verdad para
 * los reportes financieros del Administrador:
 *   Balance = SUM(ingresos) - SUM(egresos) en un rango de fechas.
 *
 * DEPENDENCIAS:
 *   - users                  (nativa + alterada en 2026_06_23_200000)
 *   - movimientos_inventario (2026_06_23_400000_create_movimientos_inventario_table)
 */
return new class extends Migration
{
    /**
     * Ejecuta la migración: crea la tabla de flujo de caja.
     */
    public function up(): void
    {
        Schema::create('flujo_caja', function (Blueprint $table) {
            // Clave primaria autoincremental.
            $table->id();

            // Usuario que registró la transacción (trazabilidad contable).
            // ON DELETE RESTRICT: protege el historial financiero.
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->restrictOnDelete();

            // Vínculo opcional al movimiento de inventario de origen.
            // Nullable: los egresos manuales no tienen movimiento asociado.
            // ON DELETE RESTRICT: si existe el vínculo, no se puede borrar
            // el movimiento de inventario sin antes desligar este registro.
            $table->unsignedBigInteger('movimiento_inventario_id')->nullable();
            $table->foreign('movimiento_inventario_id')
                  ->references('id')
                  ->on('movimientos_inventario')
                  ->restrictOnDelete();

            // 'ingreso': dinero que entra (ventas, cobros).
            // 'egreso' : dinero que sale (compras, gastos operativos).
            $table->enum('tipo', ['ingreso', 'egreso']);

            // Monto de la transacción. Siempre positivo;
            // la dirección la define el campo 'tipo'.
            $table->decimal('monto', 12, 2);

            // Descripción legible de la transacción para el reporte.
            // Ejemplos: 'Venta de producto SKU-1020', 'Pago de servicios'.
            $table->string('concepto', 255);

            // Fecha real de la transacción (puede diferir de created_at
            // si el registro se carga con retraso). Indexado para optimizar
            // las consultas de balance por rango de fechas.
            $table->timestamp('fecha_transaccion')->index();

            $table->timestamps();
        });
    }

    /**
     * Revierte la migración: elimina la tabla de flujo de caja.
     */
    public function down(): void
    {
        Schema::dropIfExists('flujo_caja');
    }
};
