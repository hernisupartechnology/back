<?php

use App\Http\Controllers\Api\VentaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - UparContable
|--------------------------------------------------------------------------
|
| Todas las rutas aquí se prefijan automáticamente con /api
| gracias a la configuración en bootstrap/app.php.
|
| Autenticación JWT: pendiente de integrar en el próximo sprint.
| Cuando esté lista, envolver estas rutas en:
|   Route::middleware('auth:api')->group(function () { ... });
|
*/

// -------------------------------------------------------
// Módulo: Ventas
// -------------------------------------------------------

Route::prefix('ventas')->name('ventas.')->group(function () {

    /**
     * POST /api/ventas
     * Registra una venta de producto.
     * Ejecuta en una sola transacción atómica:
     *   1. movimientos_inventario (tipo: salida)
     *   2. productos.stock_actual (decrement)
     *   3. flujo_caja (tipo: ingreso)
     */
    Route::post('/', [VentaController::class, 'registrar'])->name('registrar');
});
