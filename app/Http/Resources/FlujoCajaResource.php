<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API Resource: FlujoCajaResource
 *
 * Transforma el modelo FlujoCaja en un JSON limpio y tipado
 * para el historial contable del Frontend.
 *
 * El campo 'monto' se formatea con 2 decimales fijos para garantizar
 * consistencia numérica en el Frontend (evita '1500000' vs '1500000.00').
 *
 * @mixin \App\Models\FlujoCaja
 */
class FlujoCajaResource extends JsonResource
{
    /**
     * Transforma el recurso en un arreglo JSON.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'      => $this->id,

            // Clasificación visual para el Frontend (badge verde/rojo).
            'tipo'    => $this->tipo,   // 'ingreso' | 'egreso'

            // Monto formateado con 2 decimales fijos para consistencia en JS.
            'monto'   => number_format((float) $this->monto, 2, '.', ''),

            'concepto' => $this->concepto,

            // Fecha real de la transacción (puede diferir de created_at).
            'fecha_transaccion' => $this->fecha_transaccion?->toIso8601String(),

            // Vínculo al movimiento de inventario de origen (si existe).
            // null en egresos manuales que no provienen de una venta.
            'movimiento_inventario_id' => $this->movimiento_inventario_id,

            // Datos del responsable del registro.
            'registrado_por' => $this->whenLoaded('usuario', fn () => [
                'id'     => $this->usuario->id,
                'nombre' => $this->usuario->name,
            ]),

            'creado_en' => $this->created_at?->toIso8601String(),
        ];
    }
}
