<?php

namespace App\Http\Controllers\Rh;

use App\Http\Controllers\Controller;
use App\Models\Core\Notificacion;
use App\Models\Rh\Entrevista;
use App\Enums\RhResultadoEntrevista;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EntrevistaController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ID_Candidato'           => 'required|integer|exists:rh.candidato,ID_Candidato',
            'ID_Vacante'             => 'required|integer|exists:rh.vacante,ID_Vacante',
            'ID_UsuarioEntrevistador'=> 'nullable|integer|exists:core.usuario,ID_Usuario',
            'TipoEntrevista'         => 'required|in:PRESENCIAL,VIRTUAL,TELEFONICA,TECNICA,RH,PANEL',
            'FechaEntrevista'        => 'required|date',
            'DuracionMinutos'        => 'nullable|integer|min:1',
            'Ubicacion'              => 'nullable|string|max:200',
            'Medio'                  => 'nullable|string|max:100',
        ]);

        $entrevista = Entrevista::create($data);

        // Notificar al entrevistador asignado
        if (!empty($data['ID_UsuarioEntrevistador'])) {
            $nombreCandidato = DB::table('rh.candidato')
                ->where('ID_Candidato', $data['ID_Candidato'])
                ->value('Nombre') ?? 'Candidato';

            $fecha = \Carbon\Carbon::parse($data['FechaEntrevista'])->format('d/m/Y H:i');

            Notificacion::enviar(
                $data['ID_UsuarioEntrevistador'],
                'RH',
                'RH',
                "Entrevista programada: {$nombreCandidato}",
                "Tipo: {$data['TipoEntrevista']} — Fecha: {$fecha}",
                $entrevista->ID_Entrevista,
            );
        }

        return response()->json([
            'message' => 'Entrevista programada.',
            'data'    => $entrevista->load(['candidato', 'entrevistador']),
        ], 201);
    }

    public function registrarResultado(Request $request, int $id): JsonResponse
    {
        $entrevista = Entrevista::findOrFail($id);

        $data = $request->validate([
            'Resultado'    => 'required|in:PENDIENTE,APROBADO,RECHAZADO,REPROGRAMAR,NO_ASISTIO',
            'Calificacion' => 'nullable|numeric|min:0|max:10',
            'Comentarios'  => 'nullable|string',
        ]);

        $entrevista->update($data);

        // Notificar al responsable de la vacante sobre el resultado
        $idResponsable = DB::table('rh.vacante')
            ->where('ID_Vacante', $entrevista->ID_Vacante)
            ->value('ID_UsuarioResponsable');

        if ($idResponsable) {
            $nombreCandidato = DB::table('rh.candidato')
                ->where('ID_Candidato', $entrevista->ID_Candidato)
                ->value('Nombre') ?? 'Candidato';

            Notificacion::enviar(
                $idResponsable,
                'RH',
                'RH',
                "Resultado de entrevista: {$nombreCandidato}",
                "Resultado: {$data['Resultado']}" . ($data['Calificacion'] !== null ? " — Calificación: {$data['Calificacion']}" : ''),
                $entrevista->ID_Entrevista,
            );
        }

        return response()->json([
            'message' => 'Resultado registrado.',
            'data'    => $entrevista->fresh()->load(['candidato']),
        ]);
    }

    public function agregarEvaluador(Request $request, int $id): JsonResponse
    {
        $entrevista = Entrevista::findOrFail($id);

        $data = $request->validate([
            'ID_Usuario'      => 'required|integer|exists:core.usuario,ID_Usuario',
            'Rol'             => 'nullable|string|max:50',
            'Calificacion'    => 'nullable|numeric|min:0|max:10',
            'Comentarios'     => 'nullable|string',
            'FechaEvaluacion' => 'nullable|date',
        ]);

        $evaluador = $entrevista->evaluadores()->updateOrCreate(
            ['ID_Usuario' => $data['ID_Usuario']],
            $data
        );

        return response()->json([
            'message' => 'Evaluador registrado.',
            'data'    => $evaluador,
        ], 201);
    }
}
