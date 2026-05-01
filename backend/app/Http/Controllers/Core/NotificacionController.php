<?php

namespace App\Http\Controllers\Core;

use App\Http\Controllers\Controller;
use App\Models\Core\Notificacion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificacionController extends Controller
{
    /**
     * Lista las notificaciones del usuario autenticado.
     * Filtros: ?leida=0|1  ?modulo=HD  ?per_page=20
     */
    public function index(Request $request): JsonResponse
    {
        $idUsuario = $request->user()->ID_Usuario;

        $query = Notificacion::where('ID_Usuario', $idUsuario)
            ->orderByDesc('FechaCreacion');

        if ($request->has('leida')) {
            $query->where('Leida', (bool) $request->integer('leida'));
        }

        if ($request->filled('modulo')) {
            $query->where('Modulo', strtoupper($request->string('modulo')));
        }

        $perPage = min(100, max(1, $request->integer('per_page', 20)));

        $paginado = $query->paginate($perPage);

        return response()->json([
            'data'         => $paginado->items(),
            'total'        => $paginado->total(),
            'no_leidas'    => Notificacion::where('ID_Usuario', $idUsuario)->where('Leida', false)->count(),
            'current_page' => $paginado->currentPage(),
            'last_page'    => $paginado->lastPage(),
        ]);
    }

    /**
     * Marca una notificación como leída.
     */
    public function marcarLeida(Request $request, int $id): JsonResponse
    {
        $notificacion = Notificacion::findOrFail($id);

        if (!$notificacion->Leida) {
            $notificacion->Leida      = true;
            $notificacion->FechaLeida = now();
            $notificacion->save();
        }

        return response()->json(['message' => 'Notificación marcada como leída.']);
    }

    /**
     * Marca todas las notificaciones no leídas del usuario como leídas.
     */
    public function marcarTodasLeidas(Request $request): JsonResponse
    {
        $idUsuario = $request->user()->ID_Usuario;

        $count = Notificacion::where('ID_Usuario', $idUsuario)
            ->where('Leida', false)
            ->update([
                'Leida'      => true,
                'FechaLeida' => now(),
            ]);

        return response()->json([
            'message'   => 'Notificaciones marcadas como leídas.',
            'actualizadas' => $count,
        ]);
    }

    /**
     * Elimina una notificación.
     */
    public function destroy(int $id): JsonResponse
    {
        Notificacion::findOrFail($id)->delete();

        return response()->json(['message' => 'Notificación eliminada.']);
    }
}
