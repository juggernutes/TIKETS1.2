<?php

namespace App\Http\Controllers\Com;

use App\Http\Controllers\Controller;
use App\Models\Com\BaseComisionSemanal;
use App\Models\Com\ComisionBase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * GENERALISTAS
 * Capturan la relación empleado ↔ unidad/ruta por semana.
 * Cada registro define quién trabaja en qué ruta esa semana y con qué perfil (TCE/Canal/Puesto).
 */
class ComisionBaseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = ComisionBase::with(['semana', 'unidad', 'empleado', 'usuarioCreo']);

        if ($request->filled('id_semana')) {
            $query->where('ID_Semana', $request->integer('id_semana'));
        }

        if ($request->filled('id_unidad')) {
            $query->where('IdUnidad', $request->integer('id_unidad'));
        }

        if ($request->filled('numero_empleado')) {
            $query->where('Numero_Empleado', $request->integer('numero_empleado'));
        }

        if ($request->filled('estatus')) {
            $query->where('Estatus', strtoupper($request->string('estatus')));
        }

        $perPage = min(200, max(1, $request->integer('per_page', 50)));

        return response()->json($query->paginate($perPage));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ID_Semana'       => 'required|integer|exists:com.semana,ID_Semana',
            'IdUnidad'        => 'required|integer|exists:ped.unidadoperacional,IdUnidad',
            'Numero_Empleado' => 'required|integer|exists:core.empleado,Numero_Empleado',
            'Observaciones'   => 'nullable|string|max:500',
            'ID_UsuarioCreo'  => 'nullable|integer|exists:core.usuario,ID_Usuario',
        ]);

        $base = ComisionBase::create($data);

        return response()->json([
            'message' => 'Asignación registrada.',
            'data'    => $base->load(['semana', 'unidad', 'empleado']),
        ], 201);
    }

    public function baseCalculo(Request $request): JsonResponse
    {
        $query = BaseComisionSemanal::with([
            'corrida.semana',
            'empleado',
            'resultadosIndicador.indicador',
            'resultadosDoc.subIndicador',
        ])->withCount(['resultadosIndicador', 'resultadosDoc']);

        if ($request->filled('id_corrida')) {
            $query->where('ID_Corrida', $request->integer('id_corrida'));
        }

        if ($request->filled('id_semana')) {
            $query->whereHas('corrida', function ($q) use ($request) {
                $q->where('ID_Semana', $request->integer('id_semana'));
            });
        }

        if ($request->filled('canal')) {
            $query->where('Canal', strtoupper($request->string('canal')));
        }

        if ($request->filled('tce')) {
            $query->where('TCE', strtoupper($request->string('tce')));
        }

        if ($request->filled('numero_empleado')) {
            $query->where('Numero_Empleado', $request->integer('numero_empleado'));
        }

        $perPage = min(200, max(1, $request->integer('per_page', 50)));

        return response()->json(
            $query->orderBy('Sucursal')
                ->orderBy('Ruta')
                ->orderBy('NombreEmpleado')
                ->paginate($perPage)
        );
    }

    /**
     * Carga masiva: array de asignaciones para una semana.
     * Hace upsert por (ID_Semana, IdUnidad, Numero_Empleado).
     */
    public function storeBulk(Request $request): JsonResponse
    {
        $request->validate([
            'ID_Semana'         => 'required|integer|exists:com.semana,ID_Semana',
            'ID_UsuarioCreo'    => 'nullable|integer|exists:core.usuario,ID_Usuario',
            'asignaciones'      => 'required|array|min:1',
            'asignaciones.*.IdUnidad'        => 'required|integer|exists:ped.unidadoperacional,IdUnidad',
            'asignaciones.*.Numero_Empleado' => 'required|integer|exists:core.empleado,Numero_Empleado',
            'asignaciones.*.Observaciones'   => 'nullable|string|max:500',
        ]);

        $idSemana    = $request->integer('ID_Semana');
        $idUsuario   = $request->integer('ID_UsuarioCreo');
        $creados     = 0;
        $actualizados = 0;

        foreach ($request->input('asignaciones') as $item) {
            $existe = ComisionBase::where('ID_Semana', $idSemana)
                ->where('IdUnidad', $item['IdUnidad'])
                ->where('Numero_Empleado', $item['Numero_Empleado'])
                ->first();

            if ($existe) {
                $existe->update(['Observaciones' => $item['Observaciones'] ?? $existe->Observaciones]);
                $actualizados++;
            } else {
                ComisionBase::create([
                    'ID_Semana'       => $idSemana,
                    'IdUnidad'        => $item['IdUnidad'],
                    'Numero_Empleado' => $item['Numero_Empleado'],
                    'Observaciones'   => $item['Observaciones'] ?? null,
                    'ID_UsuarioCreo'  => $idUsuario ?: null,
                ]);
                $creados++;
            }
        }

        return response()->json([
            'message'      => 'Carga masiva completada.',
            'creados'      => $creados,
            'actualizados' => $actualizados,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        ComisionBase::findOrFail($id)->delete();

        return response()->json(['message' => 'Asignación eliminada.']);
    }
}
