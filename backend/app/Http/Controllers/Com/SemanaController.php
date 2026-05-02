<?php

namespace App\Http\Controllers\Com;

use App\Http\Controllers\Controller;
use App\Models\Com\MetaMesPortada;
use App\Models\Com\MetaSemanal;
use App\Models\Com\Semana;
use App\Rules\SqlServerExists;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * USUARIO ADMIN
 * Gestión de semanas del ciclo de comisiones.
 * Cada semana pertenece a uno o dos meses (si cruza mes).
 */
class SemanaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Semana::with(['metaMesInicio', 'metaMesFinal']);

        if ($request->filled('anio')) {
            $query->where('Anio', $request->integer('anio'));
        }

        if ($request->filled('activo')) {
            $query->where('Activo', $request->boolean('activo'));
        }

        return response()->json(
            $query->orderByDesc('Anio')->orderByDesc('Semana')->get()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'Anio'              => 'required|integer|min:2020|max:2099',
            'Semana'            => 'required|integer|min:1|max:53',
            'FechaInicio'       => 'required|date',
            'FechaFin'          => 'required|date|after_or_equal:FechaInicio',
            'ID_MetaMesInicio'  => ['required', 'integer', new SqlServerExists('com.meta_mes_portada', 'ID_MetaMes')],
            'ID_MetaMesFinal'   => ['nullable', 'integer', new SqlServerExists('com.meta_mes_portada', 'ID_MetaMes')],
            'DiasMesInicio'     => 'required|integer|min:1|max:7',
            'DiasMesFinal'      => 'nullable|integer|min:0|max:6',
            'ID_UsuarioCreo'    => ['nullable', 'integer', new SqlServerExists('core.usuario', 'ID_Usuario')],
        ]);

        // Validar que DiasMesInicio + DiasMesFinal == días de la semana
        $diasInicio = $data['DiasMesInicio'];
        $diasFinal  = $data['DiasMesFinal'] ?? 0;

        if (($diasInicio + $diasFinal) > 7) {
            return response()->json([
                'message' => 'DiasMesInicio + DiasMesFinal no puede exceder 7.',
            ], 422);
        }

        // Unicidad Anio+Semana
        if (Semana::where('Anio', $data['Anio'])->where('Semana', $data['Semana'])->exists()) {
            return response()->json([
                'message' => "Ya existe la semana {$data['Semana']} del año {$data['Anio']}.",
            ], 422);
        }

        $semana = Semana::create($data);

        return response()->json([
            'message' => 'Semana creada.',
            'data'    => $semana->load(['metaMesInicio', 'metaMesFinal']),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $semana = Semana::with([
            'metaMesInicio',
            'metaMesFinal',
            'metasSemanal',
            'corridas.log',
        ])->findOrFail($id);

        return response()->json($semana);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $semana = Semana::findOrFail($id);

        // No editar semanas que ya tienen corridas activas
        $tieneCorrida = DB::table('com.corrida_comision')
            ->where('ID_Semana', $id)
            ->whereNotIn('Estatus', ['CANCELADO'])
            ->exists();

        if ($tieneCorrida) {
            return response()->json([
                'message' => 'No se puede modificar una semana que ya tiene corridas activas.',
            ], 422);
        }

        $data = $request->validate([
            'FechaInicio'      => 'sometimes|date',
            'FechaFin'         => 'sometimes|date|after_or_equal:FechaInicio',
            'ID_MetaMesInicio' => ['sometimes', 'integer', new SqlServerExists('com.meta_mes_portada', 'ID_MetaMes')],
            'ID_MetaMesFinal'  => ['nullable', 'integer', new SqlServerExists('com.meta_mes_portada', 'ID_MetaMes')],
            'DiasMesInicio'    => 'sometimes|integer|min:1|max:7',
            'DiasMesFinal'     => 'nullable|integer|min:0|max:6',
            'Activo'           => 'sometimes|boolean',
        ]);

        $semana->fill($data)->save();

        return response()->json([
            'message' => 'Semana actualizada.',
            'data'    => $semana->fresh(['metaMesInicio', 'metaMesFinal']),
        ]);
    }

    /**
     * Resumen de una semana: corridas, metas semanales y bases de empleados.
     */
    public function resumen(int $id): JsonResponse
    {
        $semana = Semana::with(['metaMesInicio', 'metaMesFinal'])->findOrFail($id);

        $corridas = DB::table('com.corrida_comision')
            ->where('ID_Semana', $id)
            ->select(['ID_Corrida', 'Estatus', 'FechaCreacion'])
            ->get();

        $metas = DB::table('com.meta_semanal')
            ->where('ID_Semana', $id)
            ->get();

        $totalEmpleados = DB::table('com.base_comision_semanal as b')
            ->join('com.corrida_comision as c', 'c.ID_Corrida', '=', 'b.ID_Corrida')
            ->where('c.ID_Semana', $id)
            ->where('b.Activo', 1)
            ->count();

        return response()->json([
            'semana'          => $semana,
            'corridas'        => $corridas,
            'metas_semanales' => $metas,
            'total_empleados' => $totalEmpleados,
        ]);
    }
}
