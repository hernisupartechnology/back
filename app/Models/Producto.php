<?php

namespace App\Models;

use Database\Factories\ProductoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelo: Producto
 *
 * Representa el catálogo maestro de inventario.
 * Utiliza SoftDeletes para preservar el historial contable
 * cuando un producto es descatalogado.
 *
 * @property int         $id
 * @property string      $sku
 * @property string      $nombre
 * @property string|null $descripcion
 * @property float       $precio_costo
 * @property float       $precio_venta
 * @property int         $stock_actual
 * @property int         $stock_minimo
 */
class Producto extends Model
{
    /** @use HasFactory<ProductoFactory> */
    use HasFactory, SoftDeletes;


    protected $table = 'productos';

    protected $fillable = [
        'sku',
        'nombre',
        'descripcion',
        'precio_costo',
        'precio_venta',
        'stock_actual',
        'stock_minimo',
    ];

    protected $casts = [
        'precio_costo' => 'decimal:2',
        'precio_venta' => 'decimal:2',
        'stock_actual' => 'integer',
        'stock_minimo' => 'integer',
    ];

    // -------------------------------------------------------
    // Relaciones
    // -------------------------------------------------------

    /**
     * Un producto puede tener muchos movimientos de inventario.
     * Se incluyen los soft-deleted para mantener el historial completo.
     */
    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoInventario::class, 'producto_id');
    }

    // -------------------------------------------------------
    // Scopes de consulta
    // -------------------------------------------------------

    /**
     * Scope: productos con stock en o por debajo del mínimo.
     * Usado por el endpoint de alertas de reabastecimiento.
     */
    public function scopeBajoStock($query)
    {
        return $query->whereColumn('stock_actual', '<=', 'stock_minimo');
    }
}
