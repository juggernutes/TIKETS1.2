<?php

namespace App\Http\Controllers\Com;

use App\Http\Controllers\Controller;
use App\Models\Com\BaseComisionSemanal;
use App\Models\Com\Indicador;
use App\Models\Com\ResultadoIndicador;
use App\Rules\SqlServerExists;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Dos roles usan este controller:
 *
 *  ÁREA CxC  → captura DF1 (Devolución F1) y DAU (Devolución autoservicio)
 *              Envía el monto total de devoluciones; el sistema calcula el descuento.
 *
 *  USUARIO ADMIN → carga VOL, EFE, EFI, COB (ventas + cobertura).
 *                  Soporta carga individual o masiva (bulk).
 */
class ResultadoIndicadorController extends Controller
{
    // Indicadores que captura CxC
    private const INDICADORES_CXC = ['DF1', 'DAU'];

    // Indicadores que carga el admin
    private const INDICADORES_ADMIN = ['VOL', 'EFE', 'EFI', 'COB', 'NSE'];

    public function index(Request $request): JsonResponse
    {
        $query = ResultadoIndicador::with(['base.empleado', 'indicador', 'subIndicador']);

        if ($request->filled('id_base')) {
            $query->where('ID_Base', $request->integer('id_base'));
        }

        if ($request->filled('id_corrida')) {
            $query->whereHas('base', fn ($q) => $q->where('ID_Corrida', $request->integer('id_corrida')));
        }

        if ($request->filled('clave_indicador')) {
            $query->whereHas('indicador', fn ($q) => $q->where('Clave', strtoupper($request->string('clave_indicador'))));
        }

        return response()->json($query->get());
    }

    /**
     * CxC: captura DF1 o DAU para un empleado (ID_Base).
     * Solo recibe ValorReal (monto devuelto); el descuento lo calcula el motor de comisiones.
     */
    public function storeDevolucion(Request $request, int $idBase): JsonResponse
    {
        BaseComisionSemanal::findOrFail($idBase);

        $data = $request->validate([
            'clave_indicador' => 'required|string|in:DF1,DAU',
            'ValorReal'       => 'required|numeric|min:0',
            'Observaciones'   => 'nullable|string|max:300',
            'ID_UsuarioCreo'  => ['nullable', 'integer', new SqlServerExists('core.usuario', 'ID_Usuario')],
        ]);

        $indicador = Indicador::where('Clave', $data['clave_indicador'])->firstOrFail();

        $resultado = ResultadoIndicador::updateOrCreate(
            ['ID_Base' => $idBase, 'ID_Indicador' => $indicador->ID_Indicador, 'ID_SubIndicador' => null],
            [
                'ValorReal'     => $data['ValorReal'],
                'Observaciones' => $data['Observaciones'] ?? null,
                'ID_UsuarioCreo'=> $data['ID_UsuarioCreo'] ?? null,
            ]
        );

        return response()->json([
            'message' => "Devolución {$data['clave_indicador']} registrada.",
            'data'    => $resultado->load('indicador'),
        ], $resultado->wasRecentlyCreated ? 201 : 200);
    }

    /**
     * Admin: carga individual de un indicador (VOL/EFE/EFI/COB/NSE) para un empleado.
     */
    public function store(Request $request, int $idBase): JsonResponse
    {
        BaseComisionSemanal::findOrFail($idBase);

        $data = $request->validate([
            'clave_indicador'        => 'required|string|in:VOL,EFE,EFI,COB,NSE',
            'ID_SubIndicador'        => ['nullable', 'integer', new SqlServerExists('com.sub_indicador', 'ID_SubIndicador')],
            'ValorReal'              => 'required|numeric|min:0',
            'Meta'                   => 'nullable|numeric|min:0',
            'ClientesActivos'        => 'nullable|integer|min:0',
            'ClientesVisitados'      => 'nullable|integer|min:0',
            'ClientesConCompra'      => 'nullable|integer|min:0',
            'PorcentajeCumplimiento' => 'nullable|numeric|min:0',
            'Observaciones'          => 'nullable|string|max:300',
            'ID_UsuarioCreo'         => ['nullable', 'integer', new SqlServerExists('core.usuario', 'ID_Usuario')],
        ]);

        $indicador = Indicador::where('Clave', $data['clave_indicador'])->firstOrFail();

        $resultado = ResultadoIndicador::updateOrCreate(
            [
                'ID_Base'        => $idBase,
                'ID_Indicador'   => $indicador->ID_Indicador,
                'ID_SubIndicador'=> $data['ID_SubIndicador'] ?? null,
            ],
            [
                'ValorReal'              => $data['ValorReal'],
                'Meta'                   => $data['Meta'] ?? null,
                'ClientesActivos'        => $data['ClientesActivos'] ?? null,
                'ClientesVisitados'      => $data['ClientesVisitados'] ?? null,
                'ClientesConCompra'      => $data['ClientesConCompra'] ?? null,
                'PorcentajeCumplimiento' => $data['PorcentajeCumplimiento'] ?? null,
                'Observaciones'          => $data['Observaciones'] ?? null,
                'ID_UsuarioCreo'         => $data['ID_UsuarioCreo'] ?? null,
            ]
        );

        return response()->json([
            'message' => 'Resultado guardado.',
            'data'    => $resultado->load('indicador', 'subIndicador'),
        ], $resultado->wasRecentlyCreated ? 201 : 200);
    }

    /**
     * Admin: carga masiva de ventas/cobertura para una corrida completa.
     * Recibe un array de registros: [{id_base, clave_indicador, ValorReal, ...}]
     */
    public function storeBulk(Request $request): JsonResponse
    {
        $request->validate([
            'registros'                          => 'required|array|min:1',
            'registros.*.ID_Base'                => ['required', 'integer', new SqlServerExists('com.base_comision_semanal', 'ID_Base')],
            'registros.*.clave_indicador'        => 'required|string|in:VOL,EFE,EFI,COB,NSE,DF1,DAU',
            'registros.*.ID_SubIndicador'        => ['nullable', 'integer', new SqlServerExists('com.sub_indicador', 'ID_SubIndicador')],
            'registros.*.ValorReal'              => 'required|numeric|min:0',
            'registros.*.Meta'                   => 'nullable|numeric|min:0',
            'registros.*.ClientesActivos'        => 'nullable|integer|min:0',
            'registros.*.ClientesVisitados'      => 'nullable|integer|min:0',
            'registros.*.ClientesConCompra'      => 'nullable|integer|min:0',
            'registros.*.PorcentajeCumplimiento' => 'nullable|numeric|min:0',
            'ID_UsuarioCreo'                     => ['nullable', 'integer', new SqlServerExists('core.usuario', 'ID_Usuario')],
        ]);

        $idUsuario    = $request->integer('ID_UsuarioCreo');
        $creados      = 0;
        $actualizados = 0;

        // Pre-cargar indicadores en un map para no hacer N queries
        $indicadoresMap = Indicador::pluck('ID_Indicador', 'Clave');

        foreach ($request->input('registros') as $item) {
            $idIndicador = $indicadoresMap[$item['clave_indicador']] ?? null;
            if (!$idIndicador) continue;

            $resultado = ResultadoIndicador::updateOrCreate(
                [
                    'ID_Base'        => $item['ID_Base'],
                    'ID_Indicador'   => $idIndicador,
                    'ID_SubIndicador'=> $item['ID_SubIndicador'] ?? null,
                ],
                [
                    'ValorReal'              => $item['ValorReal'],
                    'Meta'                   => $item['Meta'] ?? null,
                    'ClientesActivos'        => $item['ClientesActivos'] ?? null,
                    'ClientesVisitados'      => $item['ClientesVisitados'] ?? null,
                    'ClientesConCompra'      => $item['ClientesConCompra'] ?? null,
                    'PorcentajeCumplimiento' => $item['PorcentajeCumplimiento'] ?? null,
                    'ID_UsuarioCreo'         => $idUsuario ?: null,
                ]
            );

            $resultado->wasRecentlyCreated ? $creados++ : $actualizados++;
        }

        return response()->json([
            'message'      => 'Carga masiva completada.',
            'creados'      => $creados,
            'actualizados' => $actualizados,
        ]);
    }
}
