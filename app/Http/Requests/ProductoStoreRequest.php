<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * FormRequest: ProductoStoreRequest
 *
 * Valida y sanitiza los datos de entrada para la creación
 * de un nuevo producto en el catálogo (POST /api/productos).
 *
 * Si la validación falla, Laravel devuelve automáticamente
 * un 422 Unprocessable Entity con el detalle de los errores.
 */
class ProductoStoreRequest extends FormRequest
{
    /**
     * Autorización: la restricción real se aplica en el middleware
     * de roles (pendiente de integrar). Por ahora se permite todo.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación para la creación del producto.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            // SKU: código único de inventario. Se verifica contra la tabla
            // 'productos' ignorando los registros con SoftDelete (deleted_at IS NULL).
            'sku'          => ['required', 'string', 'max:100', 'unique:productos,sku'],

            'nombre'       => ['required', 'string', 'max:255'],

            // Descripción opcional: puede venir null o vacía.
            'descripcion'  => ['nullable', 'string'],

            // Los precios deben ser números positivos con hasta 2 decimales.
            // 'gt:0' rechaza valores de 0 o negativos.
            'precio_costo' => ['required', 'numeric', 'gt:0', 'max:9999999999.99'],
            'precio_venta' => ['required', 'numeric', 'gt:0', 'max:9999999999.99'],

            // El stock puede empezar en 0 (producto recién catalogado sin existencias).
            'stock_actual' => ['sometimes', 'integer', 'min:0'],

            // Si no se envía, el modelo usará el default de la migración (5).
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
            'sku.required'          => 'El código SKU es obligatorio.',
            'sku.unique'            => 'Este SKU ya existe en el catálogo. Use uno diferente.',
            'sku.max'               => 'El SKU no puede superar los 100 caracteres.',
            'nombre.required'       => 'El nombre del producto es obligatorio.',
            'descripcion.string'    => 'La descripción debe ser texto.',
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
