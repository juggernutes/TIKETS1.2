<?php

namespace App\Http\Controllers\Core;

use App\Http\Controllers\Controller;
use App\Models\Core\Login;
use App\Models\Core\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'cuenta'   => 'required|string|max:70',
            'password' => 'required|string',
        ]);

        $login = Login::with('usuario.rol.permisos')
            ->where('Cuenta', $data['cuenta'])
            ->where('Activo', true)
            ->first();

        if (!$login || !Hash::check($data['password'], $login->PasswordHash)) {
            if ($login) {
                $login->increment('IntentosFallidos');
                $login->FechaUltimoIntento = now();
                $login->save();
            }

            return response()->json(['message' => 'Credenciales incorrectas.'], 401);
        }

        // Reset contador de intentos
        $login->IntentosFallidos = 0;
        $login->FechaUltimoIntento = now();
        $login->save();

        $usuario = $login->usuario;

        // Usar token simple (sin Sanctum) o Sanctum si está instalado
        $token = null;
        if (method_exists($usuario, 'createToken')) {
            $token = $usuario->createToken('api')->plainTextToken;
        } else {
            $token = base64_encode($usuario->ID_Usuario . '|' . now()->timestamp . '|' . $login->PasswordHash);
        }

        return response()->json([
            'token'                 => $token,
            'debe_cambiar_password' => (bool) $login->DebeCambiarPassword,
            'usuario'               => $this->usuarioPayload($usuario),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        if (method_exists($request->user(), 'currentAccessToken')) {
            $request->user()->currentAccessToken()->delete();
        }

        return response()->json(['message' => 'Sesión cerrada.']);
    }

    public function me(Request $request): JsonResponse
    {
        $usuario = $request->user() ?? Usuario::find($request->header('X-Usuario-ID'));

        if (!$usuario) {
            return response()->json(['message' => 'No autenticado.'], 401);
        }

        $usuario->load('rol.permisos', 'area', 'login');

        return response()->json(array_merge(
            $this->usuarioPayload($usuario),
            [
                'area'                  => $usuario->area?->Nombre,
                'debe_cambiar_password' => (bool) ($usuario->login?->DebeCambiarPassword ?? false),
            ]
        ));
    }

    public function cambiarPassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'password_actual' => 'required|string',
            'password_nuevo'  => 'required|string|min:8|confirmed',
        ]);

        $usuario = $request->user();
        $login   = Login::where('ID_Usuario', $usuario->ID_Usuario)->firstOrFail();

        if (!Hash::check($data['password_actual'], $login->PasswordHash)) {
            return response()->json(['message' => 'Contraseña actual incorrecta.'], 422);
        }

        $login->PasswordHash          = Hash::make($data['password_nuevo']);
        $login->DebeCambiarPassword   = false;
        $login->UltimoCambioPassword  = now();
        $login->save();

        return response()->json(['message' => 'Contraseña actualizada.']);
    }

    private function usuarioPayload(Usuario $usuario): array
    {
        $permisos = $usuario->rol?->permisos
            ?->pluck('Clave')
            ->filter()
            ->values()
            ->all() ?? [];

        return [
            'id'       => $usuario->ID_Usuario,
            'nombre'   => $usuario->Nombre,
            'email'    => $usuario->Email,
            'rol'      => $usuario->rol?->Nombre,
            'id_area'  => $usuario->ID_Area,
            'permisos' => $permisos,
        ];
    }
}
