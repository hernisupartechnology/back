<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * FormRequest: MovimientoStoreRequest
 *
 * Valida los datos para registrar un movimiento manual de inventario
 * en el Kardex (POST /api/movimientos).
 *
 * Casos de uso cubiertos:
 *   - 'entrada': Reabastecimiento por compra a proveedor.
 *   - 'salida' : Baja por daño, pérdida, o venta manual (si motivo contiene 'venta').
 */
class MovimientoStoreRequest extends FormRequest
{
    /**
     * Autorización: gestionada por middleware de roles (próximo sprint).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación para el registro del movimiento.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            // El producto debe existir y no estar eliminado lógicamente.
            'producto_id' => ['required', 'integer', 'exists:productos,id'],

            // Solo se aceptan los dos tipos definidos en la migración ENUM.
            'tipo'        => ['required', 'string', 'in:entrada,salida'],

            // La cantidad debe ser al menos 1 unidad física.
            'cantidad'    => ['required', 'integer', 'min:1'],

            // Motivo descriptivo para auditoría del Kardex.
            // Ejemplos: 'compra_proveedor', 'baja_daño', 'venta_factura_001'.
            'motivo'      => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * Mensajes de error personalizados en español.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'producto_id.required' => 'El producto es obligatorio.',
            'producto_id.exists'   => 'El producto no existe o fue descatalogado.',
            'tipo.required'        => 'El tipo de movimiento es obligatorio.',
            'tipo.in'              => 'El tipo de movimiento debe ser "entrada" o "salida".',
            'cantidad.required'    => 'La cantidad es obligatoria.',
            'cantidad.integer'     => 'La cantidad debe ser un número entero.',
            'cantidad.min'         => 'La cantidad mínima es 1 unidad.',
            'motivo.required'      => 'El motivo del movimiento es obligatorio.',
            'motivo.max'           => 'El motivo no puede superar los 255 caracteres.',
        ];
    }
}
