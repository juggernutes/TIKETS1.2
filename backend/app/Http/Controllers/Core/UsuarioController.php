<?php

namespace App\Http\Controllers\Core;

use App\Http\Controllers\Controller;
use App\Models\Core\Login;
use App\Models\Core\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UsuarioController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Usuario::with(['rol', 'area', 'login'])
            ->when($request->filled('buscar'), function ($q) use ($request) {
                $term = '%' . $request->buscar . '%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('Nombre', 'like', $term)
                        ->orWhere('Email', 'like', $term);
                });
            })
            ->when($request->filled('rol'), fn ($q) => $q->where('ID_Rol', $request->integer('rol')))
            ->when($request->filled('area'), fn ($q) => $q->where('ID_Area', $request->integer('area')))
            ->when($request->filled('activo'), fn ($q) => $q->where('Activo', (bool) $request->activo))
            ->orderBy('Nombre');

        $perPage = min(100, max(1, $request->integer('per_page', 20)));

        return response()->json($query->paginate($perPage)->through(fn (Usuario $u) => [
            'id_usuario'              => $u->ID_Usuario,
            'nombre'                  => $u->Nombre,
            'email'                   => $u->Email,
            'rol'                     => $u->rol?->Nombre,
            'area'                    => $u->area?->Nombre,
            'activo'                  => $u->Activo,
            'id_login'                => $u->login?->ID_Login,
            'cuenta'                  => $u->login?->Cuenta,
            'debe_cambiar_password'   => (bool) ($u->login?->DebeCambiarPassword ?? false),
            'ultimo_cambio_password'  => $u->login?->UltimoCambioPassword,
        ]));
    }

    public function resetPassword(Request $request, int $idUsuario): JsonResponse
    {
        $request->validate([
            'password_temporal' => 'nullable|string|min:8',
        ]);

        $usuario = Usuario::findOrFail($idUsuario);
        $login = Login::where('ID_Usuario', $usuario->ID_Usuario)->firstOrFail();

        $passwordTemporal = $request->string('password_temporal')->toString()
            ?: 'Rosarito#' . Str::upper(Str::random(4)) . random_int(10, 99);

        $login->PasswordHash = Hash::make($passwordTemporal);
        $login->DebeCambiarPassword = true;
        $login->IntentosFallidos = 0;
        $login->FechaUltimoIntento = null;
        $login->UltimoCambioPassword = now();
        $login->save();

        return response()->json([
            'message' => 'Contraseña temporal generada.',
            'data' => [
                'id_usuario' => $usuario->ID_Usuario,
                'cuenta' => $login->Cuenta,
                'password_temporal' => $passwordTemporal,
                'debe_cambiar_password' => true,
            ],
        ]);
    }
}
