<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware: CheckRole
 *
 * Valida que el usuario autenticado tenga al menos uno de los roles
 * permitidos para acceder al recurso solicitado.
 *
 * Uso en rutas (registrado como alias 'role' en bootstrap/app.php):
 *   Route::middleware('role:admin')             → Solo Admin.
 *   Route::middleware('role:admin,operador')    → Admin o Operador.
 *
 * Flujo de validación:
 *   1. Verificar que el usuario esté autenticado (Sanctum ya lo garantiza
 *      cuando se usa después de 'auth:sanctum', pero se verifica por seguridad).
 *   2. Cargar el rol del usuario si no está en memoria.
 *   3. Comparar el slug del rol contra los roles permitidos.
 *   4. Si no coincide → 403 Forbidden con mensaje estructurado en JSON.
 */
class CheckRole
{
    /**
     * Intercepta la petición y verifica el rol del usuario.
     *
     * @param  Request  $request   Petición HTTP entrante.
     * @param  Closure  $next      Siguiente eslabón en la cadena de middleware.
     * @param  string[] ...$roles  Uno o más slugs de roles permitidos.
     * @return Response
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        // Capa extra de seguridad: verificar autenticación.
        // En rutas protegidas con 'auth:sanctum' esto no debería ocurrir,
        // pero previene errores si el middleware se usa fuera de ese contexto.
        if (! $user) {
            return response()->json([
                'message' => 'No autenticado. Por favor, inicia sesión.',
            ], 401);
        }

        // Cargar el rol del usuario desde la BD solo si aún no está en memoria.
        // loadMissing() evita una segunda query si ya fue eager-loaded antes.
        $user->loadMissing('role');

        // Verificar si el slug del rol del usuario está en la lista de permitidos.
        // in_array() con comprobación estricta (===) para evitar falsas coincidencias.
        if (! $user->role || ! in_array($user->role->slug, $roles, strict: true)) {
            return response()->json([
                'message' => 'No tienes permisos para acceder a este recurso.',
                'rol_requerido' => $roles,
            ], 403);
        }

        // El usuario tiene un rol permitido → continuar con la petición.
        return $next($request);
    }
}
