<?php

namespace App\Http\Controllers\Com;

use App\Http\Controllers\Controller;
use App\Models\Com\BaseComisionSemanal;
use App\Models\Com\CorridaComision;
use App\Models\Com\CorridaComisionLog;
use App\Support\ComisionCalculoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * USUARIO ADMIN
 * Gestiona las corridas de comisión: crear, ver detalle y cambiar estatus.
 * El flujo normal es: BORRADOR → EN_PROCESO → CALCULADO → APROBADO → PAGADO
 */
class CorridaComisionController extends Controller
{
    private const TRANSICIONES = [
        'BORRADOR'   => ['EN_PROCESO', 'CANCELADO'],
        'EN_PROCESO' => ['CALCULADO', 'CANCELADO'],
        'CALCULADO'  => ['APROBADO', 'BORRADOR', 'CANCELADO'],
        'APROBADO'   => ['PAGADO', 'CANCELADO'],
        'PAGADO'     => [],
        'CANCELADO'  => [],
    ];

    public function index(Request $request): JsonResponse
    {
        $query = CorridaComision::with(['semana', 'usuarioCreo']);

        if ($request->filled('id_semana')) {
            $query->where('ID_Semana', $request->integer('id_semana'));
        }

        if ($request->filled('estatus')) {
            $query->where('Estatus', strtoupper($request->string('estatus')));
        }

        $perPage = min(100, max(1, $request->integer('per_page', 20)));

        return response()->json($query->orderByDesc('FechaCreacion')->paginate($perPage));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ID_Semana'      => 'required|integer|exists:com.semana,ID_Semana',
            'FechaInicio'    => 'nullable|date',
            'FechaFin'       => 'nullable|date|after_or_equal:FechaInicio',
            'Observaciones'  => 'nullable|string|max:500',
            'ArchivoOrigen'  => 'nullable|string|max:255',
            'ID_UsuarioCreo' => 'nullable|integer|exists:core.usuario,ID_Usuario',
        ]);

        $corrida = CorridaComision::create($data);
        $basesGeneradas = $this->generarBasesSemanales($corrida, $data['ID_UsuarioCreo'] ?? null);

        $this->registrarLog($corrida->ID_Corrida, null, 'BORRADOR', $data['ID_UsuarioCreo'] ?? null, $request->ip());

        return response()->json([
            'message'         => 'Corrida creada.',
            'bases_generadas' => $basesGeneradas,
            'data'            => $corrida->load('semana'),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $corrida = CorridaComision::with([
            'semana',
            'usuarioCreo',
            'bases.empleado',
            'bases.resultadosIndicador.indicador',
            'bases.resultadosDoc.subIndicador',
            'bases.ajustes',
            'bases.calculo',
            'log.usuario',
        ])->findOrFail($id);

        return response()->json($corrida);
    }

    public function cambiarEstatus(Request $request, int $id): JsonResponse
    {
        $corrida = CorridaComision::findOrFail($id);

        $data = $request->validate([
            'Estatus'        => 'required|string|in:BORRADOR,EN_PROCESO,CALCULADO,APROBADO,PAGADO,CANCELADO',
            'Comentario'     => 'nullable|string|max:500',
            'ID_Usuario'     => 'nullable|integer|exists:core.usuario,ID_Usuario',
        ]);

        $destino   = strtoupper($data['Estatus']);
        $permitidos = self::TRANSICIONES[$corrida->Estatus] ?? [];

        if (!in_array($destino, $permitidos, true)) {
            return response()->json([
                'message'   => "No se puede pasar de [{$corrida->Estatus}] a [{$destino}].",
                'permitidos'=> $permitidos,
            ], 422);
        }

        $anterior = $corrida->Estatus;
        $corrida->Estatus            = $destino;
        $corrida->ID_UsuarioModifico = $data['ID_Usuario'] ?? null;
        $corrida->save();

        $this->registrarLog($corrida->ID_Corrida, $anterior, $destino, $data['ID_Usuario'] ?? null, $request->ip(), $data['Comentario'] ?? null);

        return response()->json([
            'message' => "Corrida movida a [{$destino}].",
            'data'    => $corrida->fresh('semana'),
        ]);
    }

    /**
     * Dispara el motor de cálculo para toda la corrida.
     * Mueve la corrida a EN_PROCESO, calcula, y la deja en CALCULADO.
     */
    public function calcular(Request $request, int $id): JsonResponse
    {
        $corrida = CorridaComision::findOrFail($id);

        if (!in_array($corrida->Estatus, ['BORRADOR', 'EN_PROCESO'], true)) {
            return response()->json([
                'message' => "Solo se puede calcular desde BORRADOR o EN_PROCESO. Estado actual: {$corrida->Estatus}.",
            ], 422);
        }

        $data = $request->validate([
            'ID_Usuario' => 'nullable|integer|exists:core.usuario,ID_Usuario',
        ]);

        $idUsuario = $data['ID_Usuario'] ?? 0;

        // Mover a EN_PROCESO
        if ($corrida->Estatus === 'BORRADOR') {
            $this->registrarLog($corrida->ID_Corrida, 'BORRADOR', 'EN_PROCESO', $idUsuario, $request->ip());
            $corrida->Estatus = 'EN_PROCESO';
            $corrida->save();
        }

        $resultado = (new ComisionCalculoService())->calcularCorrida($corrida->ID_Corrida, $idUsuario);

        // Mover a CALCULADO si no hubo errores graves
        if (empty($resultado['errores'])) {
            $corrida->Estatus = 'CALCULADO';
            $corrida->save();
            $this->registrarLog($corrida->ID_Corrida, 'EN_PROCESO', 'CALCULADO', $idUsuario, $request->ip());
        }

        return response()->json([
            'message'    => empty($resultado['errores'])
                ? "Cálculo completado. {$resultado['procesados']} empleado(s) procesados."
                : "Cálculo con errores. Revisar detalle.",
            'procesados' => $resultado['procesados'],
            'errores'    => $resultado['errores'],
            'corrida'    => $corrida->fresh('semana'),
        ]);
    }

    private function registrarLog(int $idCorrida, ?string $anterior, string $nuevo, ?int $idUsuario, ?string $ip, ?string $comentario = null): void
    {
        CorridaComisionLog::create([
            'ID_Corrida'    => $idCorrida,
            'EstadoAnterior'=> $anterior,
            'EstadoNuevo'   => $nuevo,
            'Comentario'    => $comentario,
            'ID_Usuario'    => $idUsuario,
            'IP'            => $ip,
        ]);
    }

    private function generarBasesSemanales(CorridaComision $corrida, ?int $idUsuario): int
    {
        $asignaciones = DB::table('com.comision_base as cb')
            ->join('core.empleado as e', 'e.Numero_Empleado', '=', 'cb.Numero_Empleado')
            ->join('ped.unidadoperacional as u', 'u.IdUnidad', '=', 'cb.IdUnidad')
            ->leftJoin('ped.tipounidad as tu', 'tu.IdTipoUnidad', '=', 'u.IdTipoUnidad')
            ->leftJoin('core.sucursal as s', 's.ID_Sucursal', '=', 'u.IdSucursal')
            ->where('cb.ID_Semana', $corrida->ID_Semana)
            ->where('cb.Estatus', '<>', 'CANCELADO')
            ->select([
                'cb.Numero_Empleado',
                'e.Nombre as NombreEmpleado',
                'u.Nombre as Ruta',
                'tu.Nombre as TipoUnidad',
                's.Nombre as Sucursal',
            ])
            ->orderBy('s.Nombre')
            ->orderBy('u.Nombre')
            ->get();

        $generadas = 0;

        foreach ($asignaciones as $asignacion) {
            if (BaseComisionSemanal::where('ID_Corrida', $corrida->ID_Corrida)
                ->where('Numero_Empleado', $asignacion->Numero_Empleado)
                ->exists()) {
                continue;
            }

            [$puesto, $canal, $tce] = $this->clasificarTce($asignacion->TipoUnidad);

            BaseComisionSemanal::create([
                'ID_Corrida'      => $corrida->ID_Corrida,
                'Numero_Empleado' => $asignacion->Numero_Empleado,
                'Sucursal'        => $asignacion->Sucursal ?: 'SIN SUCURSAL',
                'NombreEmpleado'  => $asignacion->NombreEmpleado,
                'Ruta'            => $asignacion->Ruta,
                'Puesto'          => $puesto,
                'Canal'           => $canal,
                'TCE'             => $tce,
                'Activo'          => true,
                'ID_UsuarioCreo'  => $idUsuario,
            ]);

            $generadas++;
        }

        return $generadas;
    }

    private function clasificarTce(?string $tipoUnidad): array
    {
        $tipo = strtoupper($tipoUnidad ?? '');

        $puesto = str_contains($tipo, 'SUPERVISOR') ? 'SUPERVISOR' : 'VENDEDOR';
        $canal = str_contains($tipo, 'MODERNO') ? 'MODERNO' : 'TRADICIONAL';

        $tce = match ([$puesto, $canal]) {
            ['VENDEDOR', 'MODERNO'] => 'VM',
            ['SUPERVISOR', 'TRADICIONAL'] => 'XT',
            ['SUPERVISOR', 'MODERNO'] => 'XM',
            default => 'VT',
        };

        return [$puesto, $canal, $tce];
    }
}
