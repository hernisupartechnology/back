<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * FormRequest: ReporteFinancieroRequest
 *
 * Valida los parámetros de consulta para el reporte financiero
 * (GET /api/reportes/financiero?fecha_inicio=...&fecha_fin=...).
 *
 * El rango de fechas es obligatorio para evitar consultas sin filtro
 * que podrían retornar millones de filas en producción.
 */
class ReporteFinancieroRequest extends FormRequest
{
    /**
     * Autorización: solo el Admin puede ver reportes financieros completos.
     * La restricción real se aplica via middleware de roles (próximo sprint).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación para los filtros del reporte.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            // Ambas fechas son obligatorias para acotar el reporte.
            // Formato: 'Y-m-d' (ej: 2026-06-01).
            'fecha_inicio' => ['required', 'date', 'date_format:Y-m-d'],

            // fecha_fin debe ser igual o posterior a fecha_inicio.
            'fecha_fin'    => ['required', 'date', 'date_format:Y-m-d', 'gte:fecha_inicio'],
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
            'fecha_inicio.required'    => 'La fecha de inicio del reporte es obligatoria.',
            'fecha_inicio.date'        => 'La fecha de inicio no tiene un formato válido.',
            'fecha_inicio.date_format' => 'La fecha de inicio debe tener el formato AAAA-MM-DD.',
            'fecha_fin.required'       => 'La fecha de fin del reporte es obligatoria.',
            'fecha_fin.date'           => 'La fecha de fin no tiene un formato válido.',
            'fecha_fin.date_format'    => 'La fecha de fin debe tener el formato AAAA-MM-DD.',
            'fecha_fin.gte'            => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
        ];
    }
}
