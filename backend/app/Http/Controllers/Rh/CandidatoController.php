<?php

namespace App\Http\Controllers\Rh;

use App\Http\Controllers\Controller;
use App\Models\Core\Notificacion;
use App\Models\Rh\Candidato;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CandidatoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Candidato::with(['vacante', 'estatus'])
            ->where('Activo', true);

        if ($request->filled('id_vacante')) {
            $query->where('ID_Vacante', $request->integer('id_vacante'));
        }

        if ($request->filled('id_estatus')) {
            $query->where('ID_EstatusCandidato', $request->integer('id_estatus'));
        }

        if ($request->filled('busqueda')) {
            $q = $request->string('busqueda');
            $query->where(function ($sub) use ($q) {
                $sub->where('Nombre', 'like', "%{$q}%")
                    ->orWhere('ApellidoPaterno', 'like', "%{$q}%")
                    ->orWhere('Correo', 'like', "%{$q}%");
            });
        }

        $perPage = min(100, max(1, $request->integer('per_page', 20)));

        return response()->json($query->orderByDesc('FechaCreacion')->paginate($perPage));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ID_Vacante'            => 'required|integer|exists:rh.vacante,ID_Vacante',
            'ID_EstatusCandidato'   => 'required|integer|exists:rh.estatus_candidato,ID_EstatusCandidato',
            'Nombre'                => 'required|string|max:100',
            'ApellidoPaterno'       => 'nullable|string|max:100',
            'ApellidoMaterno'       => 'nullable|string|max:100',
            'Correo'                => 'nullable|email|max:150',
            'Telefono'              => 'nullable|string|max:20',
            'FechaNacimiento'       => 'nullable|date',
            'Genero'                => 'nullable|string|in:FEMENINO,MASCULINO,NO_BINARIO,OTRO,PREFIERO_NO_DECIR',
            'RFC'                   => 'nullable|string|max:13',
            'CURP'                  => 'nullable|string|max:18',
            'Escolaridad'           => 'nullable|string|max:100',
            'Profesion'             => 'nullable|string|max:100',
            'ExperienciaResumen'    => 'nullable|string',
            'CV_URL'                => 'nullable|url|max:500',
            'Fuente'                => 'nullable|string|max:50',
            'PretensionSalarial'    => 'nullable|numeric|min:0',
            'Observaciones'         => 'nullable|string',
        ]);

        $candidato = Candidato::create($data);

        return response()->json([
            'message' => 'Candidato registrado.',
            'data'    => $candidato->load(['vacante', 'estatus']),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $candidato = Candidato::with([
            'vacante', 'estatus',
            'entrevistas.entrevistador',
            'ofertas',
        ])->findOrFail($id);

        return response()->json($candidato);
    }

    public function cambiarEstatus(Request $request, int $id): JsonResponse
    {
        $candidato = Candidato::findOrFail($id);

        $data = $request->validate([
            'ID_EstatusCandidato' => 'required|integer|exists:rh.estatus_candidato,ID_EstatusCandidato',
            'Comentario'          => 'nullable|string|max:500',
            'ID_Usuario'          => 'nullable|integer|exists:core.usuario,ID_Usuario',
        ]);

        $estatusAnterior = $candidato->ID_EstatusCandidato;
        $candidato->ID_EstatusCandidato = $data['ID_EstatusCandidato'];
        $candidato->save();

        // Notificar al responsable de la vacante sobre el cambio de estatus
        $idResponsable = DB::table('rh.vacante')
            ->where('ID_Vacante', $candidato->ID_Vacante)
            ->value('ID_UsuarioResponsable');

        if ($idResponsable) {
            $nombreEstatus = DB::table('rh.estatus_candidato')
                ->where('ID_EstatusCandidato', $data['ID_EstatusCandidato'])
                ->value('Nombre') ?? $data['ID_EstatusCandidato'];

            $nombreCandidato = trim("{$candidato->Nombre} {$candidato->ApellidoPaterno}");

            Notificacion::enviar(
                $idResponsable,
                'RH',
                'RH',
                "Candidato {$nombreCandidato} → {$nombreEstatus}",
                $data['Comentario'] ?? null,
                $candidato->ID_Candidato,
            );
        }

        DB::table('rh.candidato_log')->insert([
            'ID_Candidato'       => $candidato->ID_Candidato,
            'ID_EstatusAnterior' => $estatusAnterior,
            'ID_EstatusNuevo'    => $data['ID_EstatusCandidato'],
            'Comentario'         => $data['Comentario'] ?? null,
            'ID_Usuario'         => $data['ID_Usuario'] ?? null,
            'Fecha'              => now(),
        ]);

        return response()->json([
            'message' => 'Estatus actualizado.',
            'data'    => $candidato->load('estatus'),
        ]);
    }
}
