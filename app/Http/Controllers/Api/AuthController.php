<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Controller: AuthController
 *
 * Gestiona el ciclo de vida de la autenticación mediante
 * Laravel Sanctum (Personal Access Tokens).
 *
 * Flujo de autenticación:
 *   1. POST /api/login  → Valida credenciales → devuelve Bearer token + datos del usuario.
 *   2. POST /api/logout → Revoca el token activo del usuario.
 *   3. GET  /api/me     → Retorna el perfil del usuario autenticado.
 */
class AuthController extends Controller
{
    /**
     * POST /api/login
     *
     * Autentica al usuario con email y password.
     * Si las credenciales son correctas, genera un Personal Access Token
     * de Sanctum y lo retorna junto con los datos básicos del usuario,
     * incluyendo el slug de su rol para que el Frontend (CoreUI)
     * pueda determinar qué vistas y acciones mostrar u ocultar.
     *
     * @param  Request $request
     * @return JsonResponse
     *
     * @throws ValidationException  Si las credenciales son inválidas.
     */
    public function login(Request $request): JsonResponse
    {
        // Validación de presencia de campos obligatorios.
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required'    => 'El email es obligatorio.',
            'email.email'       => 'El email no tiene un formato válido.',
            'password.required' => 'La contraseña es obligatoria.',
        ]);

        // Auth::attempt() verifica las credenciales contra la BD.
        // Si falla, NO lanza excepción por sí solo: debemos hacerlo manualmente.
        if (! Auth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales proporcionadas no son correctas.'],
            ]);
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Cargar la relación 'role' en una sola query para obtener el slug.
        // Esto evita una segunda query al acceder a $user->role->slug.
        $user->loadMissing('role');

        // Revocar todos los tokens anteriores del usuario para evitar
        // sesiones múltiples activas simultáneamente (política de un token activo).
        // Comentar esta línea si se necesita soporte multi-dispositivo.
        $user->tokens()->delete();

        // Generar el nuevo Personal Access Token de Sanctum.
        // 'auth_token' es el nombre descriptivo del token (visible en la tabla).
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'user'         => [
                'id'     => $user->id,
                'name'   => $user->name,
                'email'  => $user->email,
                // El slug del rol es indispensable para el RBAC del Frontend.
                // CoreUI lo usa para mostrar/ocultar secciones financieras y de admin.
                'rol'    => $user->role?->slug,
            ],
        ]);
    }

    /**
     * POST /api/logout
     *
     * Revoca el token de acceso actual del usuario autenticado.
     * El Frontend debe eliminar el token del localStorage/sessionStorage
     * y redirigir al login.
     *
     * @param  Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        // currentAccessToken() retorna el token que autenticó la petición actual.
        // delete() lo elimina de la tabla personal_access_tokens → token invalidado.
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Sesión cerrada exitosamente.',
        ]);
    }

    /**
     * GET /api/me
     *
     * Retorna el perfil del usuario autenticado con su rol.
     * Útil para que el Frontend refresque los datos del usuario
     * sin necesidad de hacer un nuevo login.
     *
     * @param  Request $request
     * @return JsonResponse
     */
    public function me(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user()->loadMissing('role');

        return response()->json([
            'user' => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'rol'   => $user->role?->slug,
            ],
        ]);
    }
}
