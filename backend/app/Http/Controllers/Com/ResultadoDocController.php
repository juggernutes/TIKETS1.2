<?php

namespace App\Http\Controllers\Com;

use App\Http\Controllers\Controller;
use App\Models\Com\BaseComisionSemanal;
use App\Models\Com\ResultadoDoc;
use App\Models\Com\SubIndicador;
use App\Rules\SqlServerExists;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * GERENTES DE SUCURSAL
 * Capturan el checklist DOC semanal por cada empleado (base).
 * 8 conceptos: CHK, SPH, ACO, LIQ, MES, REP, PRO, MER.
 */
class ResultadoDocController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = ResultadoDoc::with(['base.empleado', 'subIndicador']);

        if ($request->filled('id_base')) {
            $query->where('ID_Base', $request->integer('id_base'));
        }

        if ($request->filled('id_corrida')) {
            $query->whereHas('base', fn ($q) => $q->where('ID_Corrida', $request->integer('id_corrida')));
        }

        return response()->json($query->get());
    }

    /**
     * Captura el checklist completo de un empleado (ID_Base).
     * Recibe los 8 conceptos DOC y hace upsert de cada uno.
     */
    public function storePorBase(Request $request, int $idBase): JsonResponse
    {
        BaseComisionSemanal::findOrFail($idBase);

        $request->validate([
            'conceptos'                      => 'required|array|min:1',
            'conceptos.*.ID_SubIndicador'    => ['required', 'integer', new SqlServerExists('com.sub_indicador', 'ID_SubIndicador')],
            'conceptos.*.Cumplido'           => 'required|boolean',
            'conceptos.*.MontoConcepto'      => 'nullable|numeric|min:0',
            'conceptos.*.Observaciones'      => 'nullable|string|max:200',
            'ID_UsuarioCreo'                 => ['nullable', 'integer', new SqlServerExists('core.usuario', 'ID_Usuario')],
        ]);

        $idUsuario    = $request->integer('ID_UsuarioCreo');
        $actualizados = 0;
        $creados      = 0;
        $resultados   = [];

        foreach ($request->input('conceptos') as $item) {
            $regla = DB::table('com.regla_comision as rc')
                ->join('com.sub_indicador as si', 'rc.ID_SubIndicador', '=', 'si.ID_SubIndicador')
                ->join('com.indicador as i', 'i.ID_Indicador', '=', 'rc.ID_Indicador')
                ->join('com.base_comision_semanal as b', function ($j) use ($idBase) {
                    $j->on('rc.TCE', '=', 'b.TCE')
                      ->where('b.ID_Base', $idBase);
                })
                ->where('si.ID_SubIndicador', $item['ID_SubIndicador'])
                ->where('i.Clave', 'DOC')
                ->where('rc.Activo', true)
                ->value('rc.Monto');

            $monto  = $item['Cumplido'] ? ($regla ?? $item['MontoConcepto'] ?? 0) : 0;
            $alcance = $item['Cumplido'] ? $monto : 0;

            $resultado = ResultadoDoc::updateOrCreate(
                ['ID_Base' => $idBase, 'ID_SubIndicador' => $item['ID_SubIndicador']],
                [
                    'Cumplido'      => $item['Cumplido'],
                    'MontoConcepto' => $monto,
                    'AlcancePesos'  => $alcance,
                    'Observaciones' => $item['Observaciones'] ?? null,
                    'ID_UsuarioCreo'=> $idUsuario ?: null,
                ]
            );

            $resultado->wasRecentlyCreated ? $creados++ : $actualizados++;
            $resultados[] = $resultado;
        }

        $totalAlcance = collect($resultados)->sum('AlcancePesos');

        return response()->json([
            'message'       => 'Checklist DOC guardado.',
            'creados'       => $creados,
            'actualizados'  => $actualizados,
            'total_alcance' => $totalAlcance,
            'data'          => $resultados,
        ]);
    }
}
