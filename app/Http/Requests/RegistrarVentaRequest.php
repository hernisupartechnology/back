<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * FormRequest: RegistrarVentaRequest
 *
 * Valida y sanitiza los datos de entrada para el endpoint
 * de registro de venta antes de que lleguen al controlador.
 * Si la validación falla, Laravel devuelve automáticamente un 422.
 */
class RegistrarVentaRequest extends FormRequest
{
    /**
     * Define si el usuario está autorizado para hacer esta petición.
     * La autorización real se gestiona en el middleware de roles (próximo sprint).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación de entrada.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            // El producto debe existir en la tabla y NO estar eliminado (SoftDeletes).
            'producto_id' => ['required', 'integer', 'exists:productos,id'],

            // La cantidad debe ser al menos 1 unidad.
            'cantidad'    => ['required', 'integer', 'min:1'],

            // Descripción libre de la venta para el Kardex y el libro contable.
            'concepto'    => ['required', 'string', 'max:255'],
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
            'producto_id.required' => 'El campo producto es obligatorio.',
            'producto_id.exists'   => 'El producto seleccionado no existe o fue descatalogado.',
            'cantidad.required'    => 'La cantidad es obligatoria.',
            'cantidad.integer'     => 'La cantidad debe ser un número entero.',
            'cantidad.min'         => 'La cantidad mínima de venta es 1 unidad.',
            'concepto.required'    => 'El concepto de la venta es obligatorio.',
            'concepto.max'         => 'El concepto no puede superar los 255 caracteres.',
        ];
    }
}
