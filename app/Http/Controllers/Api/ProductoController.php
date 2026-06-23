<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductoStoreRequest;
use App\Http\Requests\ProductoUpdateRequest;
use App\Http\Resources\ProductoResource;
use App\Models\Producto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Controller: ProductoController
 *
 * Controlador delgado (Skinny Controller) para el módulo de inventario.
 * Maneja el CRUD completo del catálogo de productos más el endpoint
 * especial de alertas de reabastecimiento.
 *
 * Toda transformación de salida pasa por ProductoResource para
 * garantizar un contrato JSON estable con el Frontend en CoreUI.
 */
class ProductoController extends Controller
{
    /**
     * GET /api/productos
     *
     * Retorna el catálogo completo de productos (sin eliminados)
     * paginado de 15 en 15 registros. El Frontend puede cambiar
     * el tamaño de página con el parámetro ?per_page=N.
     *
     * @return AnonymousResourceCollection
     */
    public function index(): AnonymousResourceCollection
    {
        $perPage = request()->integer('per_page', 15);

        // Limitar per_page para evitar consultas abusivas.
        $perPage = min($perPage, 100);

        $productos = Producto::query()
            ->orderBy('nombre')
            ->paginate($perPage);

        return ProductoResource::collection($productos);
    }

    /**
     * POST /api/productos
     *
     * Crea un nuevo producto en el catálogo.
     * La validación de campos y unicidad del SKU corre en ProductoStoreRequest
     * antes de llegar a este método.
     *
     * @param  ProductoStoreRequest $request
     * @return JsonResponse
     */
    public function store(ProductoStoreRequest $request): JsonResponse
    {
        $producto = Producto::create($request->validated());

        return (new ProductoResource($producto))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * GET /api/productos/{id}
     *
     * Retorna el detalle de un producto específico.
     * Devuelve 404 automáticamente si el producto no existe
     * o fue eliminado lógicamente (SoftDelete).
     *
     * @param  int $id
     * @return ProductoResource
     */
    public function show(int $id): ProductoResource
    {
        $producto = Producto::findOrFail($id);

        return new ProductoResource($producto);
    }

    /**
     * PUT /api/productos/{id}
     *
     * Actualiza los datos de un producto existente.
     * Soporta actualizaciones parciales (solo los campos enviados
     * serán validados y actualizados, gracias a 'sometimes' en el FormRequest).
     *
     * @param  ProductoUpdateRequest $request
     * @param  int                   $id
     * @return ProductoResource
     */
    public function update(ProductoUpdateRequest $request, int $id): ProductoResource
    {
        $producto = Producto::findOrFail($id);
        $producto->update($request->validated());

        return new ProductoResource($producto);
    }

    /**
     * DELETE /api/productos/{id}
     *
     * Realiza un borrado lógico (SoftDelete) del producto.
     * El registro permanece en la BD con deleted_at relleno,
     * preservando el historial contable y de movimientos intacto.
     *
     * Retorna 204 No Content: éxito sin cuerpo de respuesta.
     *
     * @param  int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $producto = Producto::findOrFail($id);
        $producto->delete(); // Activa SoftDelete: rellena deleted_at.

        return response()->json(null, 204);
    }

    /**
     * GET /api/productos/alertas
     *
     * Endpoint especial de reabastecimiento.
     * Retorna los productos cuyo stock_actual está en o por debajo
     * del stock_minimo definido, sin paginación, para mostrar
     * todas las alertas activas de una sola vez en el dashboard.
     *
     * Se usa whereRaw para una comparación columna-a-columna eficiente
     * que no puede expresarse con los métodos convencionales de Eloquent.
     *
     * @return AnonymousResourceCollection
     */
    public function alertas(): AnonymousResourceCollection
    {
        $productos = Producto::whereRaw('stock_actual <= stock_minimo')
            ->orderBy('stock_actual') // Los más críticos (menor stock) primero.
            ->get();

        return ProductoResource::collection($productos);
    }
}
