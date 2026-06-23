<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración: Tabla 'productos'
 *
 * Catálogo maestro del inventario. Centraliza costos y precios
 * para el cálculo automático de utilidad neta en los reportes
 * del Administrador. Utiliza SoftDeletes para preservar el
 * historial contable y de movimientos al descatalogar un producto.
 */
return new class extends Migration
{
    /**
     * Ejecuta la migración: crea la tabla de productos.
     */
    public function up(): void
    {
        Schema::create('productos', function (Blueprint $table) {
            // Clave primaria autoincremental.
            $table->id();

            // Código único de inventario. Indexado para búsquedas rápidas
            // por SKU en el punto de venta y en el Kardex.
            $table->string('sku', 100)->unique();

            // Nombre descriptivo del producto para mostrar en listas y facturas.
            $table->string('nombre', 255);

            // Descripción detallada opcional (especificaciones técnicas, etc.).
            $table->text('descripcion')->nullable();

            // Precio de adquisición. Usado por el Admin para calcular:
            // Ganancia = (precio_venta - precio_costo) * unidades_vendidas
            $table->decimal('precio_costo', 12, 2);

            // Precio de venta al público. Genera el 'ingreso' en flujo_caja.
            $table->decimal('precio_venta', 12, 2);

            // Cantidad actual en almacén. Se actualiza automáticamente
            // mediante los Observers/Eventos de movimientos_inventario.
            $table->integer('stock_actual')->default(0);

            // Umbral mínimo para el endpoint de alertas de reabastecimiento.
            // Alerta: WHERE stock_actual <= stock_minimo
            $table->integer('stock_minimo')->default(5);

            $table->timestamps();

            // SoftDeletes: al "eliminar" un producto se rellena deleted_at.
            // Esto garantiza que las FK en movimientos_inventario y flujo_caja
            // no queden huérfanas y el historial permanezca íntegro.
            $table->softDeletes();
        });
    }

    /**
     * Revierte la migración: elimina la tabla de productos.
     */
    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};
