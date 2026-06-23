<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\EgresoStoreRequest;
use App\Http\Resources\FlujoCajaResource;
use App\Models\FlujoCaja;
use App\Services\CajaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Controller: CajaController
 *
 * Controlador delgado para el módulo de flujo de caja.
 * Gestiona el historial contable y el registro manual de egresos.
 */
class CajaController extends Controller
{
    public function __construct(
        private readonly CajaService $cajaService
    ) {}

    /**
     * GET /api/caja
     *
     * Lista el historial completo del flujo de caja (ingresos y egresos),
     * ordenado por fecha_transaccion descendente (más reciente primero).
     *
     * Filtros opcionales:
     *   ?tipo=ingreso      → Solo ingresos o solo egresos.
     *   ?fecha_inicio=Y-m-d → Rango de fechas (inclusive).
     *   ?fecha_fin=Y-m-d
     *   ?per_page=N        → Registros por página (máx. 100).
     *
     * @return AnonymousResourceCollection
     */
    public function index(): AnonymousResourceCollection
    {
        $perPage = min(request()->integer('per_page', 20), 100);

        $query = FlujoCaja::with('usuario')
            ->orderByDesc('fecha_transaccion');

        // Filtro por tipo de transacción.
        if ($tipo = request()->string('tipo')->value()) {
            $query->where('tipo', $tipo);
        }

        // Filtro por rango de fechas sobre el campo indexado fecha_transaccion.
        if ($fechaInicio = request()->string('fecha_inicio')->value()) {
            $query->whereDate('fecha_transaccion', '>=', $fechaInicio);
        }

        if ($fechaFin = request()->string('fecha_fin')->value()) {
            $query->whereDate('fecha_transaccion', '<=', $fechaFin);
        }

        return FlujoCajaResource::collection(
            $query->paginate($perPage)
        );
    }

    /**
     * POST /api/caja/egreso
     *
     * Registra un egreso manual directo en el flujo de caja.
     * Cubre gastos no vinculados a movimientos de inventario:
     * arriendos, servicios públicos, nómina, pagos a proveedores.
     *
     * @param  EgresoStoreRequest $request
     * @return JsonResponse
     */
    public function egreso(EgresoStoreRequest $request): JsonResponse
    {
        $userId = $request->user()?->id ?? 1; // Temporal hasta integrar JWT.

        $registro = $this->cajaService->registrarEgreso(
            userId:           $userId,
            monto:            $request->validated('monto'),
            concepto:         $request->validated('concepto'),
            fechaTransaccion: $request->validated('fecha_transaccion'),
        );

        return (new FlujoCajaResource($registro->load('usuario')))
            ->response()
            ->setStatusCode(201);
    }
}
