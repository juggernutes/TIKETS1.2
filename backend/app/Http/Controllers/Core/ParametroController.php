<?php

namespace App\Http\Controllers\Core;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * USUARIO ADMIN
 * Lectura y actualización de parámetros de configuración del sistema.
 * Los parámetros se crean via migración; este controlador solo permite editarlos.
 */
class ParametroController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = DB::table('core.parametro')->orderBy('Modulo')->orderBy('Clave');

        if ($request->filled('modulo')) {
            $query->where('Modulo', strtolower($request->string('modulo')));
        }

        if ($request->filled('activo')) {
            $query->where('Activo', $request->boolean('activo'));
        }

        return response()->json($query->get());
    }

    public function show(string $clave): JsonResponse
    {
        $param = DB::table('core.parametro')->where('Clave', strtoupper($clave))->first();
        if (!$param) {
            return response()->json(['message' => "Parámetro [{$clave}] no encontrado."], 404);
        }

        return response()->json($param);
    }

    public function update(Request $request, string $clave): JsonResponse
    {
        $param = DB::table('core.parametro')->where('Clave', strtoupper($clave))->first();
        if (!$param) {
            return response()->json(['message' => "Parámetro [{$clave}] no encontrado."], 404);
        }

        $data = $request->validate([
            'Valor'          => 'required|string|max:500',
            'Descripcion'    => 'nullable|string|max:300',
            'Activo'         => 'sometimes|boolean',
            'ID_Usuario'     => 'nullable|integer|exists:core.usuario,ID_Usuario',
        ]);

        // Validar formato según TipoDato
        $valor = $data['Valor'];
        $error = $this->validarTipo($valor, $param->TipoDato);
        if ($error) {
            return response()->json(['message' => $error], 422);
        }

        DB::table('core.parametro')->where('Clave', strtoupper($clave))->update([
            'Valor'               => $valor,
            'Descripcion'         => $data['Descripcion'] ?? $param->Descripcion,
            'Activo'              => $data['Activo']      ?? $param->Activo,
            'FechaModificacion'   => now(),
            'ID_UsuarioModifico'  => $data['ID_Usuario']  ?? null,
        ]);

        return response()->json([
            'message' => "Parámetro [{$clave}] actualizado.",
            'data'    => DB::table('core.parametro')->where('Clave', strtoupper($clave))->first(),
        ]);
    }

    /**
     * Devuelve el valor ya casteado al tipo correcto.
     * Útil para que el frontend reciba el valor listo para usar.
     */
    public function valor(string $clave): JsonResponse
    {
        $param = DB::table('core.parametro')
            ->where('Clave', strtoupper($clave))
            ->where('Activo', 1)
            ->first();

        if (!$param) {
            return response()->json(['message' => "Parámetro [{$clave}] no encontrado o inactivo."], 404);
        }

        return response()->json([
            'clave' => $param->Clave,
            'valor' => $this->castValor($param->Valor, $param->TipoDato),
            'tipo'  => $param->TipoDato,
        ]);
    }

    private function validarTipo(string $valor, string $tipo): ?string
    {
        return match ($tipo) {
            'INT'     => is_numeric($valor) && floor((float)$valor) == (float)$valor ? null : "El parámetro espera un entero.",
            'DECIMAL' => is_numeric($valor) ? null : "El parámetro espera un número decimal.",
            'BOOL'    => in_array(strtolower($valor), ['true','false','1','0'], true) ? null : "El parámetro espera true/false o 1/0.",
            'JSON'    => json_decode($valor) !== null ? null : "El parámetro espera un JSON válido.",
            default   => null,
        };
    }

    private function castValor(string $valor, string $tipo): mixed
    {
        return match ($tipo) {
            'INT'     => (int) $valor,
            'DECIMAL' => (float) $valor,
            'BOOL'    => in_array(strtolower($valor), ['true', '1'], true),
            'JSON'    => json_decode($valor, true),
            default   => $valor,
        };
    }
}
