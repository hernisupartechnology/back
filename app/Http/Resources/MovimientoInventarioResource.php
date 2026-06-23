<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API Resource: MovimientoInventarioResource
 *
 * Transforma el modelo MovimientoInventario en un JSON limpio
 * para el Kardex del Frontend. Incluye datos del producto relacionado
 * cuando están cargados (eager loading) para evitar N+1.
 *
 * @mixin \App\Models\MovimientoInventario
 */
class MovimientoInventarioResource extends JsonResource
{
    /**
     * Transforma el recurso en un arreglo JSON.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,

            // Datos del producto: incluidos solo si fueron cargados con eager loading.
            // whenLoaded() evita hacer queries adicionales si la relación no se precargó.
            'producto'   => $this->whenLoaded('producto', fn () => [
                'id'     => $this->producto->id,
                'sku'    => $this->producto->sku,
                'nombre' => $this->producto->nombre,
            ]),

            // Datos del responsable del movimiento.
            'responsable' => $this->whenLoaded('usuario', fn () => [
                'id'     => $this->usuario->id,
                'nombre' => $this->usuario->name,
            ]),

            // Clasificación del movimiento para el Kardex.
            'tipo'        => $this->tipo,   // 'entrada' | 'salida'
            'cantidad'    => $this->cantidad,
            'motivo'      => $this->motivo,

            // Metadatos temporales en formato ISO 8601 para parseo fácil en JS.
            'registrado_en' => $this->created_at?->toIso8601String(),
        ];
    }
}
