<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API Resource: ProductoResource
 *
 * Transforma el modelo Producto en un JSON limpio y predecible
 * para el Frontend en CoreUI. Centraliza el formato de salida en
 * un solo lugar, por lo que si la BD cambia, solo se modifica aquí.
 *
 * Características del recurso:
 * - Los precios se devuelven como string con 2 decimales fijos para
 *   evitar problemas de representación de punto flotante en JS.
 * - Se añade el campo calculado 'en_alerta' para que el Frontend
 *   pueda pintar indicadores visuales sin lógica adicional.
 * - El campo 'margen_ganancia_pct' solo se expone cuando el modelo
 *   lo tiene cargado (evitar cálculos innecesarios en listados grandes).
 *
 * @mixin \App\Models\Producto
 */
class ProductoResource extends JsonResource
{
    /**
     * Transforma el recurso en un arreglo JSON.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            // --- Identificación ---
            'id'          => $this->id,
            'sku'         => $this->sku,

            // --- Información del producto ---
            'nombre'      => $this->nombre,
            'descripcion' => $this->descripcion,

            // --- Precios (string con 2 decimales para precisión en JS) ---
            'precio_costo' => number_format((float) $this->precio_costo, 2, '.', ''),
            'precio_venta' => number_format((float) $this->precio_venta, 2, '.', ''),

            // --- Campo calculado: margen de ganancia porcentual ---
            // Útil para que el Admin vea la rentabilidad por producto.
            // Solo se calcula si precio_costo > 0 para evitar división por cero.
            'margen_ganancia_pct' => $this->precio_costo > 0
                ? number_format(
                    (((float) $this->precio_venta - (float) $this->precio_costo) / (float) $this->precio_costo) * 100,
                    2, '.', ''
                )
                : '0.00',

            // --- Control de stock ---
            'stock_actual' => $this->stock_actual,
            'stock_minimo' => $this->stock_minimo,

            // --- Indicador de alerta calculado ---
            // El Frontend usa este booleano para pintar badges visuales
            // (rojo = agotado, amarillo = bajo) sin lógica adicional en JS.
            'en_alerta'     => $this->stock_actual <= $this->stock_minimo,
            'stock_agotado' => $this->stock_actual === 0,

            // --- Metadatos temporales ---
            'creado_en'      => $this->created_at?->toIso8601String(),
            'actualizado_en' => $this->updated_at?->toIso8601String(),
        ];
    }
}
