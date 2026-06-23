<?php

namespace App\Services;

use App\Models\FlujoCaja;
use Illuminate\Support\Facades\DB;

/**
 * Service: CajaService
 *
 * Encapsula la lógica de registro manual en el flujo de caja.
 * Cubre únicamente egresos directos que no provienen de movimientos
 * de inventario: arriendos, servicios públicos, nómina, etc.
 *
 * Los ingresos en flujo_caja siempre se generan automáticamente
 * desde el VentaService para garantizar la trazabilidad cruzada.
 */
class CajaService
{
    /**
     * Registra un egreso manual en el flujo de caja.
     *
     * @param  int    $userId            ID del usuario que registra el gasto.
     * @param  float  $monto             Valor del egreso (siempre positivo).
     * @param  string $concepto          Descripción del gasto para el libro contable.
     * @param  string|null $fechaTransaccion  Fecha real del gasto (default: now()).
     * @return FlujoCaja
     *
     * @throws \Throwable  Si ocurre cualquier error de base de datos.
     */
    public function registrarEgreso(
        int $userId,
        float $monto,
        string $concepto,
        ?string $fechaTransaccion = null
    ): FlujoCaja {
        return DB::transaction(function () use ($userId, $monto, $concepto, $fechaTransaccion) {

            return FlujoCaja::create([
                'user_id'                  => $userId,
                'movimiento_inventario_id' => null,     // No está vinculado a un movimiento.
                'tipo'                     => 'egreso',
                'monto'                    => $monto,
                'concepto'                 => $concepto,
                // Si el Frontend no envía fecha_transaccion, se usa el momento actual.
                'fecha_transaccion'        => $fechaTransaccion ?? now(),
            ]);
        });
    }
}
