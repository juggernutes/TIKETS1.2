<?php

namespace App\Http\Controllers\Hd;

use App\Http\Controllers\Controller;
use App\Models\Hd\EncuestaTicket;
use App\Models\Hd\Ticket;
use App\Models\Hd\TicketEstatusLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Encuestas de satisfacción post-cierre de ticket.
 * Solo el empleado que reportó puede calificar, y solo una vez por ticket.
 */
class EncuestaController extends Controller
{
    public function store(Request $request, int $idTicket): JsonResponse
    {
        $ticket = Ticket::findOrFail($idTicket);

        // Solo tickets resueltos/cerrados
        $nombreEstatus = strtoupper((string) ($ticket->estatus?->Nombre ?? ''));
        if (!in_array($nombreEstatus, ['RESUELTO', 'CERRADO'], true)) {
            return response()->json([
                'message' => 'Solo se puede calificar un ticket resuelto o cerrado.',
            ], 422);
        }

        // Una sola encuesta por ticket
        if (EncuestaTicket::where('ID_Ticket', $idTicket)->exists()) {
            return response()->json([
                'message' => 'Este ticket ya tiene una encuesta registrada.',
            ], 422);
        }

        $data = $request->validate([
            'Calificacion' => 'required|integer|min:1|max:5',
            'Comentarios'  => 'nullable|string|max:1000',
            'ID_Usuario'   => 'nullable|integer',
        ]);
        if (!empty($data['ID_Usuario']) && !DB::table('core.usuario')->where('ID_Usuario', $data['ID_Usuario'])->exists()) {
            throw ValidationException::withMessages([
                'ID_Usuario' => ['El usuario indicado no existe.'],
            ]);
        }

        $encuesta = DB::transaction(function () use ($ticket, $idTicket, $data, $request) {
            $encuesta = EncuestaTicket::create(array_merge($data, ['ID_Ticket' => $idTicket]));

            if (strcasecmp((string) ($ticket->estatus?->Nombre ?? ''), 'Cerrado') !== 0) {
                $estatusAnterior = $ticket->ID_Estatus;
                $estatusCerrado = DB::table('hd.estatus')->where('Nombre', 'Cerrado')->value('ID_Estatus');

                if ($estatusCerrado) {
                    $ticket->ID_Estatus = (int) $estatusCerrado;
                    $ticket->ID_Usuario_Cierra = $data['ID_Usuario'] ?? $request->user()?->ID_Usuario;
                    $ticket->save();

                    TicketEstatusLog::create([
                        'ID_Ticket' => $ticket->ID_Ticket,
                        'ID_Estatus_Anterior' => $estatusAnterior,
                        'ID_Estatus_Nuevo' => $ticket->ID_Estatus,
                        'ID_Usuario' => $ticket->ID_Usuario_Cierra,
                        'Comentario' => 'Ticket cerrado por encuesta de satisfacción.',
                    ]);
                }
            }

            return $encuesta;
        });

        return response()->json([
            'message' => 'Encuesta registrada. Ticket cerrado.',
            'data'    => $encuesta,
        ], 201);
    }

    public function show(int $idTicket): JsonResponse
    {
        $encuesta = EncuestaTicket::where('ID_Ticket', $idTicket)->firstOrFail();

        return response()->json($encuesta);
    }

    public function resumen(Request $request): JsonResponse
    {
        $query = \Illuminate\Support\Facades\DB::table('hd.encuesta_ticket as e')
            ->join('hd.ticket as t', 't.ID_Ticket', '=', 'e.ID_Ticket');

        if ($request->filled('fecha_desde')) {
            $query->where('e.Fecha', '>=', $request->string('fecha_desde'));
        }

        if ($request->filled('fecha_hasta')) {
            $query->where('e.Fecha', '<=', $request->string('fecha_hasta'));
        }

        if ($request->filled('id_area')) {
            $query->where('t.ID_Area_Responsable', $request->integer('id_area'));
        }

        $stats = $query->selectRaw('
            COUNT(*)                                    AS total,
            AVG(CAST(e.Calificacion AS FLOAT))          AS promedio,
            SUM(CASE WHEN e.Calificacion = 5 THEN 1 ELSE 0 END) AS calificacion_5,
            SUM(CASE WHEN e.Calificacion = 4 THEN 1 ELSE 0 END) AS calificacion_4,
            SUM(CASE WHEN e.Calificacion = 3 THEN 1 ELSE 0 END) AS calificacion_3,
            SUM(CASE WHEN e.Calificacion = 2 THEN 1 ELSE 0 END) AS calificacion_2,
            SUM(CASE WHEN e.Calificacion = 1 THEN 1 ELSE 0 END) AS calificacion_1
        ')->first();

        return response()->json($stats);
    }
}
