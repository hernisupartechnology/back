<?php

namespace App\Services;

use App\Models\FlujoCaja;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Service: MovimientoService
 *
 * Orquesta el registro de todos los movimientos manuales de inventario.
 * Delega al VentaService cuando el movimiento corresponde a una venta,
 * para no duplicar la lógica atómica ya implementada.
 *
 * Tipos de movimiento gestionados:
 * ┌─────────────────┬─────────────────────────────────────────────────────┐
 * │ Caso            │ Comportamiento                                      │
 * ├─────────────────┼─────────────────────────────────────────────────────┤
 * │ entrada (*)     │ Incrementa stock. No genera flujo_caja.             │
 * │ salida + venta  │ Delega a VentaService (stock ↓ + ingreso en caja)  │
 * │ salida + baja   │ Solo decrementa stock. No genera flujo_caja.        │
 * └─────────────────┴─────────────────────────────────────────────────────┘
 *
 * (*) Si en el futuro se necesita registrar el costo de la compra como
 *     egreso, se puede extender este método sin romper la interfaz.
 */
class MovimientoService
{
    /**
     * Inyección del VentaService para reutilizar su lógica atómica
     * cuando el movimiento de salida corresponde a una venta.
     */
    public function __construct(
        private readonly VentaService $ventaService
    ) {}

    /**
     * Registra un movimiento de inventario de forma atómica.
     *
     * @param  Producto $producto   Producto afectado por el movimiento.
     * @param  string   $tipo       'entrada' | 'salida'
     * @param  int      $cantidad   Unidades físicas del movimiento.
     * @param  int      $userId     ID del usuario responsable.
     * @param  string   $motivo     Descripción del origen (ej: 'compra_proveedor').
     * @return array{
     *   movimiento: MovimientoInventario,
     *   flujo_caja: FlujoCaja|null
     * }
     *
     * @throws ValidationException  Si el stock es insuficiente en una salida.
     * @throws \Throwable           Si ocurre cualquier error de base de datos.
     */
    public function registrar(
        Producto $producto,
        string $tipo,
        int $cantidad,
        int $userId,
        string $motivo
    ): array {
        return match ($tipo) {
            'entrada' => $this->procesarEntrada($producto, $cantidad, $userId, $motivo),
            'salida'  => $this->procesarSalida($producto, $cantidad, $userId, $motivo),
        };
    }

    // -------------------------------------------------------
    // Métodos privados por caso de uso
    // -------------------------------------------------------

    /**
     * Procesa una entrada de inventario (reabastecimiento).
     *
     * Incrementa el stock_actual del producto en una transacción atómica.
     * No genera registro en flujo_caja (el costo de la compra se registra
     * como egreso manual por separado si el Admin lo requiere).
     */
    private function procesarEntrada(
        Producto $producto,
        int $cantidad,
        int $userId,
        string $motivo
    ): array {
        $movimiento = DB::transaction(function () use ($producto, $cantidad, $userId, $motivo) {

            // Bloquear la fila para evitar race conditions si dos
            // reabastecimientos del mismo producto corren en paralelo.
            $producto = Producto::lockForUpdate()->findOrFail($producto->id);

            // Registrar la entrada en el Kardex.
            $movimiento = MovimientoInventario::create([
                'producto_id' => $producto->id,
                'user_id'     => $userId,
                'tipo'        => 'entrada',
                'cantidad'    => $cantidad,
                'motivo'      => $motivo,
            ]);

            // Incrementar stock atómicamente a nivel SQL para evitar
            // sobrescrituras concurrentes (increment es thread-safe).
            $producto->increment('stock_actual', $cantidad);

            return $movimiento;
        });

        return [
            'movimiento' => $movimiento,
            'flujo_caja' => null,   // Entradas no generan registro contable automático.
        ];
    }

    /**
     * Procesa una salida de inventario.
     *
     * Si el motivo comienza con 'venta' (ej: 'venta_factura_001'),
     * delega al VentaService para ejecutar la transacción atómica completa
     * (stock ↓ + ingreso en flujo_caja). En caso contrario (baja por daño,
     * pérdida, etc.), solo registra el movimiento y decrementa el stock.
     */
    private function procesarSalida(
        Producto $producto,
        int $cantidad,
        int $userId,
        string $motivo
    ): array {
        // Detectar si la salida es una venta para disparar el flujo_caja.
        // La convención es que el motivo empiece con 'venta' (case-insensitive).
        if (str_starts_with(strtolower($motivo), 'venta')) {
            // Reutilizar la lógica atómica del VentaService:
            // stock ↓ + movimiento + ingreso en flujo_caja.
            return $this->ventaService->registrarVenta(
                producto: $producto,
                cantidad: $cantidad,
                userId:   $userId,
                concepto: $motivo,
            );
        }

        // Para bajas por daño, pérdida u otros: solo movimiento + stock ↓.
        $movimiento = DB::transaction(function () use ($producto, $cantidad, $userId, $motivo) {

            $producto = Producto::lockForUpdate()->findOrFail($producto->id);

            // Validar stock antes de registrar la baja.
            if ($producto->stock_actual < $cantidad) {
                throw ValidationException::withMessages([
                    'cantidad' => [
                        "Stock insuficiente para '{$producto->nombre}'. "
                        . "Disponible: {$producto->stock_actual}, solicitado: {$cantidad}."
                    ],
                ]);
            }

            $movimiento = MovimientoInventario::create([
                'producto_id' => $producto->id,
                'user_id'     => $userId,
                'tipo'        => 'salida',
                'cantidad'    => $cantidad,
                'motivo'      => $motivo,
            ]);

            // Decrement atómico a nivel SQL: evita sobrescrituras concurrentes.
            $producto->decrement('stock_actual', $cantidad);

            return $movimiento;
        });

        return [
            'movimiento' => $movimiento,
            'flujo_caja' => null,   // Bajas no generan ingreso contable.
        ];
    }
}
