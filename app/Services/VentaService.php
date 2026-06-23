<?php

namespace App\Services;

use App\Models\FlujoCaja;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Service: VentaService
 *
 * Encapsula la lógica de negocio completa para registrar una venta.
 * Garantiza la consistencia total de los datos usando una transacción
 * atómica de base de datos: si cualquiera de los tres pasos falla,
 * ningún cambio persiste (rollback automático).
 *
 * Pasos que ejecuta en orden dentro de la transacción:
 *   1. Valida que haya stock suficiente para cubrir la venta.
 *   2. Registra el movimiento de inventario como tipo = 'salida'.
 *   3. Decrementa el stock_actual del producto.
 *   4. Crea el registro en flujo_caja como tipo = 'ingreso',
 *      vinculado al movimiento creado en el paso 2.
 */
class VentaService
{
    /**
     * Registra una venta de forma atómica.
     *
     * @param  Producto $producto   El producto que se está vendiendo.
     * @param  int      $cantidad   Unidades vendidas (debe ser > 0).
     * @param  int      $userId     ID del usuario que realiza el registro.
     * @param  string   $concepto   Descripción de la venta (ej: 'Venta Factura #001').
     * @return array{
     *   movimiento: MovimientoInventario,
     *   flujo_caja: FlujoCaja
     * }
     *
     * @throws ValidationException  Si el stock es insuficiente.
     * @throws \Throwable           Si ocurre cualquier error de base de datos.
     */
    public function registrarVenta(
        Producto $producto,
        int $cantidad,
        int $userId,
        string $concepto
    ): array {
        // -------------------------------------------------------
        // Pre-validación ANTES de abrir la transacción.
        // Esto evita abrir una transacción innecesaria si el stock
        // ya es insuficiente desde el inicio.
        // -------------------------------------------------------
        $this->validarStockSuficiente($producto, $cantidad);

        // -------------------------------------------------------
        // Transacción atómica: los 3 pasos se ejecutan como uno.
        // Si cualquiera lanza una excepción → rollback automático.
        // -------------------------------------------------------
        $resultado = DB::transaction(function () use ($producto, $cantidad, $userId, $concepto) {

            // ---------------------------------------------------
            // PASO 1: Bloquear la fila del producto para evitar
            // condiciones de carrera (race conditions) si dos
            // ventas del mismo producto se procesan en paralelo.
            // lockForUpdate() mantiene el lock hasta el commit.
            // ---------------------------------------------------
            $producto = Producto::lockForUpdate()->findOrFail($producto->id);

            // Re-validar stock dentro de la transacción con el dato
            // actualizado y bloqueado para evitar overselling.
            $this->validarStockSuficiente($producto, $cantidad);

            // ---------------------------------------------------
            // PASO 2: Registrar el movimiento de inventario (Kardex).
            // El motivo incluye el concepto para trazabilidad cruzada.
            // ---------------------------------------------------
            $movimiento = MovimientoInventario::create([
                'producto_id' => $producto->id,
                'user_id'     => $userId,
                'tipo'        => 'salida',
                'cantidad'    => $cantidad,
                'motivo'      => $concepto,
            ]);

            // ---------------------------------------------------
            // PASO 3: Decrementar el stock del producto.
            // Se usa decrement() para una operación atómica a nivel
            // de SQL (UPDATE productos SET stock_actual = stock_actual - X)
            // en lugar de un save() que podría sobrescribir cambios concurrentes.
            // ---------------------------------------------------
            $producto->decrement('stock_actual', $cantidad);

            // ---------------------------------------------------
            // PASO 4: Crear el registro contable en flujo_caja.
            // El monto usa el precio_venta del momento de la venta
            // para que el historial refleje el precio real cobrado,
            // incluso si el precio cambia en el futuro.
            // ---------------------------------------------------
            $monto = bcmul((string) $producto->precio_venta, (string) $cantidad, 2);

            $flujoCaja = FlujoCaja::create([
                'user_id'                  => $userId,
                'movimiento_inventario_id' => $movimiento->id,
                'tipo'                     => 'ingreso',
                'monto'                    => $monto,
                'concepto'                 => $concepto,
                'fecha_transaccion'        => now(),
            ]);

            return [
                'movimiento' => $movimiento,
                'flujo_caja' => $flujoCaja,
            ];
        });

        return $resultado;
    }

    // -------------------------------------------------------
    // Métodos privados de soporte
    // -------------------------------------------------------

    /**
     * Verifica que el producto tenga stock suficiente para la venta.
     * Lanza ValidationException para que el controlador devuelva
     * automáticamente un 422 con el mensaje de error estructurado.
     *
     * @throws ValidationException
     */
    private function validarStockSuficiente(Producto $producto, int $cantidad): void
    {
        if ($producto->stock_actual < $cantidad) {
            throw ValidationException::withMessages([
                'cantidad' => [
                    "Stock insuficiente para '{$producto->nombre}'. "
                    . "Disponible: {$producto->stock_actual}, solicitado: {$cantidad}."
                ],
            ]);
        }
    }
}
