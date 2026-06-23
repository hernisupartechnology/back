<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CajaController;
use App\Http\Controllers\Api\MovimientoController;
use App\Http\Controllers\Api\ProductoController;
use App\Http\Controllers\Api\ReporteController;
use App\Http\Controllers\Api\VentaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - UparContable MVP
|--------------------------------------------------------------------------
|
| Prefijo automático: /api  (configurado en bootstrap/app.php)
|
| Estructura de acceso:
|
|   ┌─ PÚBLICAS ─────────────────────────────────────────────────────────┐
|   │  POST /api/login                  Obtener Bearer token             │
|   └────────────────────────────────────────────────────────────────────┘
|   ┌─ PROTEGIDAS (auth:sanctum) ────────────────────────────────────────┐
|   │                                                                    │
|   │  ┌─ COMPARTIDO (admin + operador) ──────────────────────────────┐  │
|   │  │  GET  /api/productos             Catálogo paginado           │  │
|   │  │  GET  /api/productos/{id}        Detalle del producto        │  │
|   │  │  GET  /api/productos/alertas     Alertas de bajo stock       │  │
|   │  │  POST /api/movimientos           Registrar movimiento manual  │  │
|   │  │  GET  /api/caja                  Historial flujo de caja     │  │
|   │  │  GET  /api/me                    Perfil del usuario actual   │  │
|   │  └─────────────────────────────────────────────────────────────┘  │
|   │                                                                    │
|   │  ┌─ SOLO ADMIN ────────────────────────────────────────────────┐  │
|   │  │  POST   /api/productos           Crear producto              │  │
|   │  │  PUT    /api/productos/{id}      Editar producto             │  │
|   │  │  DELETE /api/productos/{id}      Eliminar (SoftDelete)       │  │
|   │  │  POST   /api/caja/egreso         Egreso manual directo       │  │
|   │  │  GET    /api/reportes/financiero Balance y utilidades netas  │  │
|   │  │  POST   /api/logout              Cerrar sesión               │  │
|   │  └─────────────────────────────────────────────────────────────┘  │
|   └────────────────────────────────────────────────────────────────────┘
*/

// ═══════════════════════════════════════════════════════════════════════
// RUTAS PÚBLICAS — Sin autenticación requerida.
// ═══════════════════════════════════════════════════════════════════════

/**
 * POST /api/login
 * Autentica al usuario y retorna el Bearer token + datos con slug de rol.
 * Es el único endpoint abierto del sistema.
 */
Route::post('/login', [AuthController::class, 'login'])->name('auth.login');

// ═══════════════════════════════════════════════════════════════════════
// RUTAS PROTEGIDAS — Requieren Bearer token válido de Sanctum.
// ═══════════════════════════════════════════════════════════════════════

Route::middleware('auth:sanctum')->group(function () {

    // ---------------------------------------------------------------
    // Sesión del usuario autenticado
    // ---------------------------------------------------------------

    /**
     * GET /api/me
     * Perfil del usuario autenticado con su rol.
     * Útil para que el Frontend refresque los datos sin re-login.
     */
    Route::get('/me', [AuthController::class, 'me'])->name('auth.me');

    /**
     * POST /api/logout
     * Revoca el token activo. El Frontend elimina el token localmente.
     */
    Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');

    // ═══════════════════════════════════════════════════════════════
    // GRUPO COMPARTIDO: Admin y Operador
    // Acciones de consulta y operaciones diarias del punto de venta.
    // ═══════════════════════════════════════════════════════════════

    Route::middleware('role:admin,operador')->group(function () {

        // -----------------------------------------------------------
        // Módulo: Inventario — Lectura y alertas (ambos roles)
        //
        // ORDEN CRÍTICO: /alertas ANTES del apiResource para evitar
        // que Laravel interprete "alertas" como el parámetro {id}.
        // -----------------------------------------------------------

        Route::prefix('productos')->name('productos.')->group(function () {
            /**
             * GET /api/productos/alertas
             * Productos con stock_actual <= stock_minimo.
             */
            Route::get('/alertas', [ProductoController::class, 'alertas'])->name('alertas');
        });

        /**
         * GET /api/productos        → index  (catálogo paginado)
         * GET /api/productos/{id}   → show   (detalle del producto)
         */
        Route::apiResource('productos', ProductoController::class)
            ->parameters(['productos' => 'id'])
            ->only(['index', 'show']);

        // -----------------------------------------------------------
        // Módulo: Kardex — Movimientos (ambos roles)
        // -----------------------------------------------------------

        Route::prefix('movimientos')->name('movimientos.')->group(function () {

            /**
             * GET /api/movimientos
             * Kardex paginado. Filtros: ?producto_id, ?tipo, ?per_page
             */
            Route::get('/', [MovimientoController::class, 'index'])->name('index');

            /**
             * POST /api/movimientos
             * Registrar entrada, venta o baja de inventario.
             * Si tipo=salida y motivo=venta* → genera ingreso en flujo_caja.
             */
            Route::post('/', [MovimientoController::class, 'store'])->name('store');
        });

        // -----------------------------------------------------------
        // Módulo: Caja — Historial de transacciones (ambos roles)
        // -----------------------------------------------------------

        /**
         * GET /api/caja
         * Historial de flujo de caja paginado.
         * Filtros: ?tipo, ?fecha_inicio, ?fecha_fin, ?per_page
         */
        Route::get('/caja', [CajaController::class, 'index'])->name('caja.index');
    });

    // ═══════════════════════════════════════════════════════════════
    // GRUPO RESTRINGIDO: Solo Admin
    // Gestión del catálogo, egresos masivos y reportes financieros.
    // ═══════════════════════════════════════════════════════════════

    Route::middleware('role:admin')->group(function () {

        // -----------------------------------------------------------
        // Módulo: Inventario — Escritura (solo Admin)
        // -----------------------------------------------------------

        /**
         * POST   /api/productos        → store   (crear producto)
         * PUT    /api/productos/{id}   → update  (editar producto)
         * DELETE /api/productos/{id}   → destroy (borrado lógico)
         */
        Route::apiResource('productos', ProductoController::class)
            ->parameters(['productos' => 'id'])
            ->only(['store', 'update', 'destroy']);

        // -----------------------------------------------------------
        // Módulo: Caja — Egresos manuales (solo Admin)
        // -----------------------------------------------------------

        /**
         * POST /api/caja/egreso
         * Registrar egreso directo: arriendos, servicios, nómina, etc.
         */
        Route::post('/caja/egreso', [CajaController::class, 'egreso'])->name('caja.egreso');

        // -----------------------------------------------------------
        // Módulo: Reportes Financieros (solo Admin)
        // -----------------------------------------------------------

        /**
         * GET /api/reportes/financiero?fecha_inicio=Y-m-d&fecha_fin=Y-m-d
         * Balance consolidado: total_ingresos, total_egresos, utilidad_neta.
         */
        Route::get('/reportes/financiero', [ReporteController::class, 'financiero'])
            ->name('reportes.financiero');

        // -----------------------------------------------------------
        // Módulo: Ventas — acceso directo (solo Admin en este endpoint)
        // Los operadores registran ventas via POST /api/movimientos.
        // -----------------------------------------------------------

        /**
         * POST /api/ventas
         * Venta atómica directa: movimiento + stock ↓ + ingreso en caja.
         */
        Route::post('/ventas', [VentaController::class, 'registrar'])->name('ventas.registrar');
    });
});
