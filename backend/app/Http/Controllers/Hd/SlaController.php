<?php

namespace App\Http\Controllers\Hd;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * USUARIO ADMIN / TI
 * Gestión de acuerdos de nivel de servicio para tickets.
 */
class SlaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = DB::table('hd.sla as s')
            ->leftJoin('core.area as a', 'a.ID_Area', '=', 's.ID_Area')
            ->select('s.*', 'a.Nombre as NombreArea')
            ->orderBy('s.Prioridad')
            ->orderBy('s.Nombre');

        if ($request->filled('prioridad')) {
            $query->where('s.Prioridad', strtoupper($request->string('prioridad')));
        }

        if ($request->filled('activo')) {
            $query->where('s.Activo', $request->boolean('activo'));
        }

        return response()->json($query->get());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'Nombre'          => 'required|string|max:100',
            'ID_Area'         => 'nullable|integer|exists:core.area,ID_Area',
            'Prioridad'       => 'required|string|in:CRITICA,ALTA,MEDIA,BAJA',
            'HorasRespuesta'  => 'required|integer|min:1',
            'HorasResolucion' => 'required|integer|min:1|gte:HorasRespuesta',
            'Activo'          => 'sometimes|boolean',
        ]);

        $id = DB::table('hd.sla')->insertGetId(array_merge($data, ['Activo' => $data['Activo'] ?? true]));

        return response()->json([
            'message' => 'SLA creado.',
            'data'    => DB::table('hd.sla')->find($id),
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $sla = DB::table('hd.sla')->find($id);
        if (!$sla) {
            return response()->json(['message' => 'SLA no encontrado.'], 404);
        }

        $data = $request->validate([
            'Nombre'          => 'sometimes|string|max:100',
            'ID_Area'         => 'nullable|integer|exists:core.area,ID_Area',
            'Prioridad'       => 'sometimes|string|in:CRITICA,ALTA,MEDIA,BAJA',
            'HorasRespuesta'  => 'sometimes|integer|min:1',
            'HorasResolucion' => 'sometimes|integer|min:1',
            'Activo'          => 'sometimes|boolean',
        ]);

        // Validar que HorasResolucion >= HorasRespuesta
        $respuesta  = $data['HorasRespuesta']  ?? $sla->HorasRespuesta;
        $resolucion = $data['HorasResolucion'] ?? $sla->HorasResolucion;

        if ($resolucion < $respuesta) {
            return response()->json([
                'message' => 'HorasResolucion debe ser >= HorasRespuesta.',
            ], 422);
        }

        DB::table('hd.sla')->where('ID_SLA', $id)->update($data);

        return response()->json([
            'message' => 'SLA actualizado.',
            'data'    => DB::table('hd.sla')->find($id),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $sla = DB::table('hd.sla')->find($id);
        if (!$sla) {
            return response()->json(['message' => 'SLA no encontrado.'], 404);
        }

        // Verificar si hay tickets que lo usan
        $enUso = DB::table('hd.ticket')->where('ID_SLA', $id)->exists();
        if ($enUso) {
            DB::table('hd.sla')->where('ID_SLA', $id)->update(['Activo' => false]);
            return response()->json(['message' => 'SLA desactivado (tiene tickets asociados).']);
        }

        DB::table('hd.sla')->where('ID_SLA', $id)->delete();

        return response()->json(['message' => 'SLA eliminado.']);
    }
}
