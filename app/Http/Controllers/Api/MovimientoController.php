<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MovimientoStoreRequest;
use App\Http\Resources\MovimientoInventarioResource;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Services\MovimientoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Controller: MovimientoController
 *
 * Controlador delgado para el módulo del Kardex de inventario.
 * Delega toda la lógica de negocio al MovimientoService.
 */
class MovimientoController extends Controller
{
    public function __construct(
        private readonly MovimientoService $movimientoService
    ) {}

    /**
     * GET /api/movimientos
     *
     * Lista el Kardex completo de movimientos de inventario.
     * Paginado y con eager loading de producto y usuario responsable
     * para evitar N+1 queries en el listado.
     *
     * Filtros opcionales:
     *   ?producto_id=N  → Kardex de un producto específico.
     *   ?tipo=entrada   → Solo entradas o solo salidas.
     *   ?per_page=N     → Registros por página (máx. 100).
     *
     * @return AnonymousResourceCollection
     */
    public function index(): AnonymousResourceCollection
    {
        $perPage = min(request()->integer('per_page', 20), 100);

        $query = MovimientoInventario::with(['producto', 'usuario'])
            ->latest();

        // Filtro opcional por producto.
        if ($productoId = request()->integer('producto_id')) {
            $query->where('producto_id', $productoId);
        }

        // Filtro opcional por tipo de movimiento.
        if ($tipo = request()->string('tipo')->value()) {
            $query->where('tipo', $tipo);
        }

        return MovimientoInventarioResource::collection(
            $query->paginate($perPage)
        );
    }

    /**
     * POST /api/movimientos
     *
     * Registra un movimiento manual de inventario en el Kardex.
     * La lógica varía según el tipo y motivo:
     *   - 'entrada'          → Incrementa stock.
     *   - 'salida' + venta   → Stock ↓ + ingreso en flujo_caja.
     *   - 'salida' + baja    → Solo decrementa stock.
     *
     * @param  MovimientoStoreRequest $request
     * @return JsonResponse
     */
    public function store(MovimientoStoreRequest $request): JsonResponse
    {
        $producto = Producto::findOrFail($request->validated('producto_id'));
        $userId   = $request->user()?->id ?? 1; // Temporal hasta integrar JWT.

        $resultado = $this->movimientoService->registrar(
            producto: $producto,
            tipo:     $request->validated('tipo'),
            cantidad: $request->validated('cantidad'),
            userId:   $userId,
            motivo:   $request->validated('motivo'),
        );

        return response()->json([
            'message'    => 'Movimiento registrado exitosamente.',
            'movimiento' => new MovimientoInventarioResource(
                $resultado['movimiento']->load(['producto', 'usuario'])
            ),
            // flujo_caja es null en entradas y bajas no relacionadas a ventas.
            'flujo_caja_generado' => $resultado['flujo_caja'] !== null,
        ], 201);
    }
}
