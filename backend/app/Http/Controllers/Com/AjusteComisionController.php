<?php

namespace App\Http\Controllers\Com;

use App\Http\Controllers\Controller;
use App\Models\Com\AjusteComision;
use App\Models\Com\BaseComisionSemanal;
use App\Rules\SqlServerExists;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * USUARIO CxC (DF1/DAU) + ADMIN
 * Captura y gestión de ajustes manuales sobre una base de comisión semanal.
 * Tipos: DESCUENTO, AGREGADO, SR1 (sin ruta 1 semana), SR2 (sin ruta 2+ semanas)
 */
class AjusteComisionController extends Controller
{
    private const TIPOS_PERMITIDOS = ['DESCUENTO', 'AGREGADO', 'SR1', 'SR2'];

    public function index(Request $request): JsonResponse
    {
        $query = AjusteComision::with(['base.empleado', 'usuarioCreo']);

        if ($request->filled('id_base')) {
            $query->where('ID_Base', $request->integer('id_base'));
        }

        if ($request->filled('id_corrida')) {
            $query->whereHas('base', fn ($q) => $q->where('ID_Corrida', $request->integer('id_corrida')));
        }

        if ($request->filled('numero_empleado')) {
            $query->whereHas('base', fn ($q) => $q->where('Numero_Empleado', $request->integer('numero_empleado')));
        }

        if ($request->filled('tipo')) {
            $query->where('TipoAjuste', strtoupper($request->string('tipo')));
        }

        return response()->json($query->orderByDesc('FechaCreacion')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ID_Base'       => ['required', 'integer', new SqlServerExists('com.base_comision_semanal', 'ID_Base')],
            'TipoAjuste'    => 'required|string|in:DESCUENTO,AGREGADO,SR1,SR2',
            'DiasDescuento' => 'nullable|integer|min:1|max:7',
            'Monto'         => 'nullable|numeric|min:0',
            'Motivo'        => 'nullable|string|max:300',
            'ID_UsuarioCreo'=> ['nullable', 'integer', new SqlServerExists('core.usuario', 'ID_Usuario')],
        ]);

        // SR1/SR2 requieren DiasDescuento; DESCUENTO/AGREGADO requieren Monto
        if (in_array($data['TipoAjuste'], ['SR1', 'SR2'], true) && empty($data['DiasDescuento'])) {
            return response()->json([
                'message' => 'DiasDescuento es requerido para ajustes de tipo SR1 y SR2.',
            ], 422);
        }

        if (in_array($data['TipoAjuste'], ['DESCUENTO', 'AGREGADO'], true) && !isset($data['Monto'])) {
            return response()->json([
                'message' => 'Monto es requerido para ajustes de tipo DESCUENTO y AGREGADO.',
            ], 422);
        }

        $base = BaseComisionSemanal::findOrFail($data['ID_Base']);

        // Validar que la corrida no esté en un estado final
        $corrida = $base->corrida;
        if (in_array($corrida->Estatus, ['APROBADO', 'PAGADO', 'CANCELADO'], true)) {
            return response()->json([
                'message' => "No se pueden agregar ajustes a una corrida en estatus [{$corrida->Estatus}].",
            ], 422);
        }

        $ajuste = AjusteComision::create($data);

        return response()->json([
            'message' => 'Ajuste registrado.',
            'data'    => $ajuste->load('base.empleado'),
        ], 201);
    }

    public function storePorBase(Request $request, int $idBase): JsonResponse
    {
        $base = BaseComisionSemanal::with('corrida')->findOrFail($idBase);

        if (in_array($base->corrida->Estatus, ['APROBADO', 'PAGADO', 'CANCELADO'], true)) {
            return response()->json([
                'message' => "No se pueden agregar ajustes a una corrida en estatus [{$base->corrida->Estatus}].",
            ], 422);
        }

        $request->validate([
            'ajustes'               => 'required|array|min:1',
            'ajustes.*.TipoAjuste'  => 'required|string|in:DESCUENTO,AGREGADO,SR1,SR2',
            'ajustes.*.DiasDescuento'=> 'nullable|integer|min:1|max:7',
            'ajustes.*.Monto'       => 'nullable|numeric|min:0',
            'ajustes.*.Motivo'      => 'nullable|string|max:300',
            'ID_UsuarioCreo'        => ['nullable', 'integer', new SqlServerExists('core.usuario', 'ID_Usuario')],
        ]);

        $idUsuario = $request->integer('ID_UsuarioCreo') ?: null;
        $creados   = [];
        $errores   = [];

        foreach ($request->input('ajustes') as $i => $item) {
            $tipo = strtoupper($item['TipoAjuste']);

            if (in_array($tipo, ['SR1', 'SR2'], true) && empty($item['DiasDescuento'])) {
                $errores[] = "Fila {$i}: DiasDescuento requerido para {$tipo}.";
                continue;
            }

            if (in_array($tipo, ['DESCUENTO', 'AGREGADO'], true) && !isset($item['Monto'])) {
                $errores[] = "Fila {$i}: Monto requerido para {$tipo}.";
                continue;
            }

            $creados[] = AjusteComision::create([
                'ID_Base'        => $idBase,
                'TipoAjuste'     => $tipo,
                'DiasDescuento'  => $item['DiasDescuento'] ?? null,
                'Monto'          => $item['Monto'] ?? null,
                'Motivo'         => $item['Motivo'] ?? null,
                'ID_UsuarioCreo' => $idUsuario,
            ]);
        }

        return response()->json([
            'message'  => count($creados) . ' ajuste(s) registrados.',
            'creados'  => count($creados),
            'errores'  => $errores,
        ], empty($errores) ? 201 : 207);
    }

    public function destroy(int $id): JsonResponse
    {
        $ajuste = AjusteComision::findOrFail($id);

        $corrida = $ajuste->base->corrida;
        if (in_array($corrida->Estatus, ['APROBADO', 'PAGADO', 'CANCELADO'], true)) {
            return response()->json([
                'message' => "No se puede eliminar un ajuste de una corrida en estatus [{$corrida->Estatus}].",
            ], 422);
        }

        $ajuste->delete();

        return response()->json(['message' => 'Ajuste eliminado.']);
    }
}
