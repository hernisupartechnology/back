<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo: FlujoCaja
 *
 * Libro contable simplificado. Cada registro representa una
 * transacción de dinero: ingreso (venta) o egreso (gasto).
 * Es la fuente de verdad para los reportes financieros del Admin.
 *
 * @property int         $id
 * @property int         $user_id
 * @property int|null    $movimiento_inventario_id
 * @property string      $tipo                     ('ingreso' | 'egreso')
 * @property float       $monto
 * @property string      $concepto
 * @property \Carbon\Carbon $fecha_transaccion
 */
class FlujoCaja extends Model
{
    protected $table = 'flujo_caja';

    protected $fillable = [
        'user_id',
        'movimiento_inventario_id',
        'tipo',
        'monto',
        'concepto',
        'fecha_transaccion',
    ];

    protected $casts = [
        'monto'              => 'decimal:2',
        'fecha_transaccion'  => 'datetime',
    ];

    // -------------------------------------------------------
    // Relaciones
    // -------------------------------------------------------

    /**
     * El usuario que registró la transacción.
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * El movimiento de inventario que originó este registro (si existe).
     * Presente en ventas; null en egresos manuales.
     */
    public function movimientoInventario(): BelongsTo
    {
        return $this->belongsTo(MovimientoInventario::class, 'movimiento_inventario_id');
    }
}
