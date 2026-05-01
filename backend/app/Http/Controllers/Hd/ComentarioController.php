<?php

namespace App\Http\Controllers\Hd;

use App\Http\Controllers\Controller;
use App\Models\Core\Notificacion;
use App\Models\Hd\Comentario;
use App\Models\Hd\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ComentarioController extends Controller
{
    public function index(int $ticketId): JsonResponse
    {
        $ticket = Ticket::findOrFail($ticketId);

        $comentarios = Comentario::with('usuario')
            ->where('ID_Ticket', $ticket->ID_Ticket)
            ->orderBy('Fecha')
            ->get();

        return response()->json($comentarios);
    }

    public function store(Request $request, int $ticketId): JsonResponse
    {
        $ticket = Ticket::findOrFail($ticketId);

        $data = $request->validate([
            'ID_Usuario' => 'required|integer|exists:core.usuario,ID_Usuario',
            'Mensaje'    => 'required|string|max:4000',
            'EsInterno'  => 'boolean',
        ]);

        $data['ID_Ticket'] = $ticket->ID_Ticket;
        $data['Fecha']     = now();

        $comentario = Comentario::create($data);

        $esInterno   = (bool) ($data['EsInterno'] ?? false);
        $idComentador = $data['ID_Usuario'];

        // Si el comentador es el agente → notificar al empleado (si tiene usuario)
        if ($ticket->ID_Soporte === $idComentador && !$esInterno) {
            $idUsuarioEmpleado = DB::table('core.usuario_relacion')
                ->where('Numero_Empleado', $ticket->Numero_Empleado)
                ->value('ID_Usuario');

            if ($idUsuarioEmpleado) {
                Notificacion::enviar(
                    $idUsuarioEmpleado,
                    'TICKET',
                    'HD',
                    "Nuevo comentario en tu ticket #{$ticket->SerieFolio}",
                    mb_strimwidth($data['Mensaje'], 0, 100, '…'),
                    $ticket->ID_Ticket,
                );
            }
        }

        // Si el comentador es el empleado (u otro) → notificar al agente asignado
        if ($ticket->ID_Soporte && $ticket->ID_Soporte !== $idComentador) {
            Notificacion::enviar(
                $ticket->ID_Soporte,
                'TICKET',
                'HD',
                "Nuevo comentario en el ticket #{$ticket->SerieFolio}",
                mb_strimwidth($data['Mensaje'], 0, 100, '…'),
                $ticket->ID_Ticket,
            );
        }

        return response()->json([
            'message' => 'Comentario agregado.',
            'data'    => $comentario->load('usuario'),
        ], 201);
    }
}
