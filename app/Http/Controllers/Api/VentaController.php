<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegistrarVentaRequest;
use App\Models\Producto;
use App\Services\VentaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

/**
 * Controller: VentaController
 *
 * Controlador delgado (Skinny Controller) para el módulo de ventas.
 * Delega toda la lógica de negocio al VentaService, limitándose a:
 *   1. Recibir y validar la petición HTTP.
 *   2. Llamar al servicio correspondiente.
 *   3. Formatear y devolver la respuesta JSON.
 */
class VentaController extends Controller
{
    /**
     * Inyección de dependencias por constructor.
     * Laravel resuelve VentaService automáticamente desde el IoC container.
     */
    public function __construct(
        private readonly VentaService $ventaService
    ) {}

    /**
     * POST /api/ventas
     *
     * Registra una venta de producto, actualizando el inventario
     * y creando automáticamente el ingreso en flujo de caja.
     *
     * @param  RegistrarVentaRequest $request  Datos validados de la petición.
     * @return JsonResponse
     */
    public function registrar(RegistrarVentaRequest $request): JsonResponse
    {
        // Recuperar el producto con los datos que necesita el servicio.
        // findOrFail devuelve 404 automáticamente si no existe.
        $producto = Producto::findOrFail($request->validated('producto_id'));

        // El ID del usuario autenticado como responsable del movimiento.
        // Cuando se integre JWT, este valor vendrá del token.
        $userId = $request->user()?->id ?? 1; // Temporal hasta integrar auth JWT.

        // Delegar al servicio toda la lógica de negocio atómica.
        $resultado = $this->ventaService->registrarVenta(
            producto: $producto,
            cantidad: $request->validated('cantidad'),
            userId:   $userId,
            concepto: $request->validated('concepto'),
        );

        return response()->json([
            'message'    => 'Venta registrada exitosamente.',
            'movimiento' => [
                'id'          => $resultado['movimiento']->id,
                'producto_id' => $resultado['movimiento']->producto_id,
                'tipo'        => $resultado['movimiento']->tipo,
                'cantidad'    => $resultado['movimiento']->cantidad,
                'motivo'      => $resultado['movimiento']->motivo,
                'created_at'  => $resultado['movimiento']->created_at,
            ],
            'flujo_caja' => [
                'id'                       => $resultado['flujo_caja']->id,
                'tipo'                     => $resultado['flujo_caja']->tipo,
                'monto'                    => $resultado['flujo_caja']->monto,
                'concepto'                 => $resultado['flujo_caja']->concepto,
                'movimiento_inventario_id' => $resultado['flujo_caja']->movimiento_inventario_id,
                'fecha_transaccion'        => $resultado['flujo_caja']->fecha_transaccion,
            ],
            'stock_restante' => $producto->fresh()->stock_actual,
        ], 201);
    }
}
