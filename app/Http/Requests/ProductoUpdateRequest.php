<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * FormRequest: ProductoUpdateRequest
 *
 * Valida los datos de entrada para la actualización de un producto
 * existente (PUT /api/productos/{id}).
 *
 * Diferencia clave con ProductoStoreRequest:
 * - Todos los campos usan 'sometimes' para permitir actualizaciones parciales.
 * - La regla unique del SKU ignora el registro actual del producto
 *   (usando Rule::unique()->ignore()) para no rechazar el propio SKU.
 */
class ProductoUpdateRequest extends FormRequest
{
    /**
     * Autorización: gestionada por middleware de roles (próximo sprint).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación para la actualización del producto.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        // El ID del producto viene del segmento de ruta {id}.
        // Se usa para excluir el propio registro de la validación unique del SKU.
        $productoId = $this->route('id');

        return [
            // 'sometimes' significa: "solo valida si el campo está presente en la petición".
            // Esto permite actualizaciones parciales sin enviar todos los campos.
            'sku'    => [
                'sometimes',
                'required',
                'string',
                'max:100',
                // Ignorar el propio producto para no rechazar el mismo SKU al actualizar.
                Rule::unique('productos', 'sku')->ignore($productoId)->whereNull('deleted_at'),
            ],

            'nombre'       => ['sometimes', 'required', 'string', 'max:255'],
            'descripcion'  => ['sometimes', 'nullable', 'string'],
            'precio_costo' => ['sometimes', 'required', 'numeric', 'gt:0', 'max:9999999999.99'],
            'precio_venta' => ['sometimes', 'required', 'numeric', 'gt:0', 'max:9999999999.99'],
            'stock_actual' => ['sometimes', 'integer', 'min:0'],
            'stock_minimo' => ['sometimes', 'integer', 'min:0'],
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
            'sku.unique'            => 'Este SKU ya está en uso por otro producto.',
            'sku.max'               => 'El SKU no puede superar los 100 caracteres.',
            'nombre.required'       => 'El nombre del producto es obligatorio.',
            'precio_costo.required' => 'El precio de costo es obligatorio.',
            'precio_costo.numeric'  => 'El precio de costo debe ser un número.',
            'precio_costo.gt'       => 'El precio de costo debe ser mayor a cero.',
            'precio_venta.required' => 'El precio de venta es obligatorio.',
            'precio_venta.numeric'  => 'El precio de venta debe ser un número.',
            'precio_venta.gt'       => 'El precio de venta debe ser mayor a cero.',
            'stock_actual.integer'  => 'El stock actual debe ser un número entero.',
            'stock_actual.min'      => 'El stock actual no puede ser negativo.',
            'stock_minimo.integer'  => 'El stock mínimo debe ser un número entero.',
            'stock_minimo.min'      => 'El stock mínimo no puede ser negativo.',
        ];
    }
}
