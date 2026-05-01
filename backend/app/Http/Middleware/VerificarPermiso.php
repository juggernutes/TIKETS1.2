<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerificarPermiso
{
    public function handle(Request $request, Closure $next, string $permiso): Response
    {
        $usuario = $request->user();

        if (!$usuario) {
            return response()->json(['message' => 'No autenticado.'], 401);
        }

        $usuario->loadMissing('rol.permisos');
        $claves = $usuario->rol?->permisos?->pluck('Clave') ?? collect();

        if (!$claves->contains($permiso)) {
            return response()->json(['message' => 'No tienes permiso para realizar esta accion.'], 403);
        }

        return $next($request);
    }
}
