<?php

namespace App\Http\Controllers\Com;

use App\Enums\ComEstatusComision;
use App\Http\Controllers\Controller;
use App\Models\Com\CalculoComision;
use App\Models\Core\Notificacion;
use App\Rules\SqlServerExists;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CalculoComisionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = CalculoComision::with(['base.empleado', 'base.corrida.semana']);

        if ($request->filled('id_corrida')) {
            $query->whereHas('base', fn ($q) => $q->where('ID_Corrida', $request->integer('id_corrida')));
        }

        if ($request->filled('numero_empleado')) {
            $query->whereHas('base', fn ($q) => $q->where('Numero_Empleado', $request->integer('numero_empleado')));
        }

        if ($request->filled('estatus')) {
            $query->where('Estatus', strtoupper($request->string('estatus')));
        }

        if ($request->filled('tce')) {
            $query->whereHas('base', fn ($q) => $q->where('TCE', strtoupper($request->string('tce'))));
        }

        if ($request->filled('canal')) {
            $query->whereHas('base', fn ($q) => $q->where('Canal', strtoupper($request->string('canal'))));
        }

        if ($request->filled('puesto')) {
            $query->whereHas('base', fn ($q) => $q->where('Puesto', strtoupper($request->string('puesto'))));
        }

        $perPage = min(200, max(1, $request->integer('per_page', 50)));

        return response()->json($query->orderByDesc('FechaCalculo')->paginate($perPage));
    }

    public function show(int $id): JsonResponse
    {
        $calculo = CalculoComision::with([
            'base.empleado',
            'base.corrida.semana',
            'base.resultadosIndicador.indicador',
            'base.resultadosDoc.subIndicador',
            'base.ajustes',
            'calculadoPor',
            'aprobadoPor',
        ])->findOrFail($id);

        return response()->json($calculo);
    }

    public function cambiarEstatus(Request $request, int $id): JsonResponse
    {
        $calculo = CalculoComision::with('base.empleado')->findOrFail($id);

        $data = $request->validate([
            'estatus'      => 'required|string',
            'ID_Usuario'   => ['nullable', 'integer', new SqlServerExists('core.usuario', 'ID_Usuario')],
            'Observaciones'=> 'nullable|string|max:500',
        ]);

        $destino = ComEstatusComision::tryFrom(strtoupper($data['estatus']));

        if (!$destino) {
            return response()->json([
                'message'          => 'Estatus inválido.',
                'valores_permitidos' => array_column(ComEstatusComision::cases(), 'value'),
            ], 422);
        }

        $actual = ComEstatusComision::tryFrom($calculo->Estatus) ?? ComEstatusComision::CALCULADO;

        if (!$actual->puedeTransicionarA($destino)) {
            return response()->json([
                'message'   => "No se puede pasar de [{$actual->label()}] a [{$destino->label()}].",
                'actual'    => $actual->value,
                'permitidos'=> array_column($actual->transicionesPermitidas(), 'value'),
            ], 422);
        }

        $calculo->Estatus = $destino->value;

        if ($destino === ComEstatusComision::APROBADO) {
            $calculo->Aprobado        = true;
            $calculo->FechaAprobacion = now();
            $calculo->AprobadoPor     = $data['ID_Usuario'] ?? null;
        }

        if (isset($data['Observaciones'])) {
            $calculo->Observaciones = $data['Observaciones'];
        }

        $calculo->save();

        // Notificar al empleado
        $idUsuarioEmpleado = DB::table('core.usuario_relacion')
            ->where('Numero_Empleado', $calculo->base->Numero_Empleado)
            ->value('ID_Usuario');

        if ($idUsuarioEmpleado) {
            $mensajes = [
                ComEstatusComision::APROBADO  => 'Tu comisión fue aprobada',
                ComEstatusComision::RECHAZADO => 'Tu comisión fue rechazada',
                ComEstatusComision::PAGADO    => 'Tu comisión fue pagada',
                ComEstatusComision::CANCELADO => 'Tu comisión fue cancelada',
            ];

            if (isset($mensajes[$destino])) {
                $semana = DB::table('com.semana as s')
                    ->join('com.corrida_comision as c', 'c.ID_Semana', '=', 's.ID_Semana')
                    ->join('com.base_comision_semanal as b', 'b.ID_Corrida', '=', 'c.ID_Corrida')
                    ->where('b.ID_Base', $calculo->ID_Base)
                    ->first(['s.Semana', 's.Anio']);

                $detalle = $semana
                    ? "Semana {$semana->Semana}/{$semana->Anio} — $" . number_format($calculo->MontoFinal, 2)
                    : null;

                Notificacion::enviar(
                    $idUsuarioEmpleado,
                    'COMISION',
                    'COM',
                    $mensajes[$destino],
                    $detalle,
                    $calculo->ID_Calculo,
                );
            }
        }

        return response()->json([
            'message' => "Comisión movida a [{$destino->label()}].",
            'data'    => $calculo->fresh(),
        ]);
    }

    /** Alias semántico para el gerente */
    public function aprobar(Request $request, int $id): JsonResponse
    {
        $request->merge(['estatus' => ComEstatusComision::APROBADO->value]);
        return $this->cambiarEstatus($request, $id);
    }

    public function resumenCorrida(int $idCorrida): JsonResponse
    {
        $corrida = DB::table('com.corrida_comision as cc')
            ->join('com.semana as s', 's.ID_Semana', '=', 'cc.ID_Semana')
            ->where('cc.ID_Corrida', $idCorrida)
            ->first(['cc.ID_Corrida', 'cc.Estatus', 's.Semana', 's.Anio', 's.FechaInicio', 's.FechaFin']);

        if (!$corrida) {
            return response()->json(['message' => 'Corrida no encontrada.'], 404);
        }

        $detalle = DB::table('com.calculo_comision as cal')
            ->join('com.base_comision_semanal as b', 'b.ID_Base', '=', 'cal.ID_Base')
            ->join('core.empleado as e', 'e.Numero_Empleado', '=', 'b.Numero_Empleado')
            ->where('b.ID_Corrida', $idCorrida)
            ->select([
                'b.Numero_Empleado',
                'e.Nombre',
                'b.Ruta',
                'b.Puesto',
                'b.Canal',
                'b.TCE',
                'cal.MontoBruto',
                'cal.TotalDescuentos',
                'cal.TotalAgregados',
                'cal.MontoFinal',
                'cal.Estatus',
                'cal.Aprobado',
            ])
            ->orderBy('e.Nombre')
            ->get();

        return response()->json([
            'corrida' => $corrida,
            'totales' => [
                'bruto'       => $detalle->sum('MontoBruto'),
                'descuentos'  => $detalle->sum('TotalDescuentos'),
                'agregados'   => $detalle->sum('TotalAgregados'),
                'neto'        => $detalle->sum('MontoFinal'),
                'empleados'   => $detalle->count(),
                'aprobados'   => $detalle->where('Aprobado', true)->count(),
            ],
            'detalle' => $detalle,
        ]);
    }
}
