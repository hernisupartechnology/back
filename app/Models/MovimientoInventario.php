<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Modelo: MovimientoInventario
 *
 * Representa el Kardex: cada fila es un cambio físico en el almacén.
 * El tipo 'salida' generado por una venta disparará automáticamente
 * un registro en flujo_caja a través de VentaService.
 *
 * @property int         $id
 * @property int         $producto_id
 * @property int         $user_id
 * @property string      $tipo        ('entrada' | 'salida')
 * @property int         $cantidad
 * @property string      $motivo
 */
class MovimientoInventario extends Model
{
    protected $table = 'movimientos_inventario';

    protected $fillable = [
        'producto_id',
        'user_id',
        'tipo',
        'cantidad',
        'motivo',
    ];

    protected $casts = [
        'cantidad' => 'integer',
    ];

    // -------------------------------------------------------
    // Relaciones
    // -------------------------------------------------------

    /**
     * El producto al que pertenece este movimiento.
     * withTrashed() permite acceder al producto aunque esté descatalogado.
     */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id')->withTrashed();
    }

    /**
     * El usuario responsable de registrar el movimiento.
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Registro contable generado automáticamente por este movimiento
     * cuando el tipo es 'salida' (venta).
     */
    public function flujoCaja(): HasOne
    {
        return $this->hasOne(FlujoCaja::class, 'movimiento_inventario_id');
    }
}
