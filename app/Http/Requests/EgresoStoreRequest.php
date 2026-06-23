<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * FormRequest: EgresoStoreRequest
 *
 * Valida los datos para registrar un egreso manual en el flujo de caja
 * (POST /api/caja/egreso). Cubre gastos directos que no están vinculados
 * a un movimiento de inventario: arriendos, servicios, nómina, etc.
 */
class EgresoStoreRequest extends FormRequest
{
    /**
     * Autorización: gestionada por middleware de roles (próximo sprint).
     * Solo el rol 'admin' debería poder registrar egresos manuales.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación para el registro del egreso.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            // El monto del egreso debe ser un valor positivo mayor a cero.
            'monto'   => ['required', 'numeric', 'gt:0', 'max:9999999999.99'],

            // Descripción clara del gasto para el libro contable.
            // Ejemplos: 'Pago arriendo local', 'Factura servicios públicos'.
            'concepto' => ['required', 'string', 'max:255'],

            // Fecha real de la transacción. Si no se envía, se usa now().
            // Formato aceptado: 'Y-m-d' o 'Y-m-d H:i:s'.
            'fecha_transaccion' => ['sometimes', 'date'],
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
            'monto.required'          => 'El monto del egreso es obligatorio.',
            'monto.numeric'           => 'El monto debe ser un número.',
            'monto.gt'                => 'El monto debe ser mayor a cero.',
            'concepto.required'       => 'El concepto del egreso es obligatorio.',
            'concepto.max'            => 'El concepto no puede superar los 255 caracteres.',
            'fecha_transaccion.date'  => 'La fecha de transacción no tiene un formato válido.',
        ];
    }
}
