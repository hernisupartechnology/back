<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReporteFinancieroRequest;
use App\Models\FlujoCaja;
use Illuminate\Http\JsonResponse;

/**
 * Controller: ReporteController
 *
 * Controlador para los reportes financieros del módulo de Administrador.
 * Solo los usuarios con rol 'admin' deben acceder a estos endpoints
 * (la restricción real se aplica via middleware de roles en el próximo sprint).
 */
class ReporteController extends Controller
{
    /**
     * GET /api/reportes/financiero?fecha_inicio=Y-m-d&fecha_fin=Y-m-d
     *
     * Retorna el balance financiero consolidado para el rango de fechas
     * especificado. Las consultas agregadas operan sobre el campo
     * indexado 'fecha_transaccion' para máxima eficiencia en producción.
     *
     * Estructura de respuesta garantizada:
     * {
     *   "total_ingresos": 1500000.00,
     *   "total_egresos":  400000.00,
     *   "utilidad_neta":  1100000.00,
     *   "rango": { "desde": "2026-06-01", "hasta": "2026-06-15" }
     * }
     *
     * @param  ReporteFinancieroRequest $request  Parámetros validados: fecha_inicio, fecha_fin.
     * @return JsonResponse
     */
    public function financiero(ReporteFinancieroRequest $request): JsonResponse
    {
        $fechaInicio = $request->validated('fecha_inicio');
        $fechaFin    = $request->validated('fecha_fin');

        // -------------------------------------------------------
        // Consultas agregadas sobre el campo indexado fecha_transaccion.
        //
        // whereDate() convierte el timestamp a fecha (Y-m-d) para que
        // los límites sean inclusivos en ambos extremos del rango,
        // independientemente de la hora de la transacción.
        //
        // Dos consultas separadas (una por tipo) en lugar de una con
        // CASE WHEN: MySQL optimiza mejor con índices en este patrón.
        // -------------------------------------------------------
        $totalIngresos = FlujoCaja::where('tipo', 'ingreso')
            ->whereDate('fecha_transaccion', '>=', $fechaInicio)
            ->whereDate('fecha_transaccion', '<=', $fechaFin)
            ->sum('monto');

        $totalEgresos = FlujoCaja::where('tipo', 'egreso')
            ->whereDate('fecha_transaccion', '>=', $fechaInicio)
            ->whereDate('fecha_transaccion', '<=', $fechaFin)
            ->sum('monto');

        // La utilidad neta puede ser negativa si los egresos superan los ingresos.
        // bcsub garantiza precisión exacta en la resta de decimales monetarios.
        $utilidadNeta = bcsub((string) $totalIngresos, (string) $totalEgresos, 2);

        return response()->json([
            'total_ingresos' => number_format((float) $totalIngresos, 2, '.', ''),
            'total_egresos'  => number_format((float) $totalEgresos,  2, '.', ''),
            'utilidad_neta'  => number_format((float) $utilidadNeta,  2, '.', ''),
            'rango'          => [
                'desde'  => $fechaInicio,
                'hasta'  => $fechaFin,
            ],
        ]);
    }
}
