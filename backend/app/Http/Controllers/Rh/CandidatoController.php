<?php

namespace App\Http\Controllers\Rh;

use App\Http\Controllers\Controller;
use App\Models\Core\Notificacion;
use App\Models\Rh\Candidato;
use App\Models\Rh\Vacante;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CandidatoController extends Controller
{
    private const CV_MAX_MB = 10;
    private const CV_EXTENSIONS = ['pdf', 'doc', 'docx'];

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

    public function postularPublico(Request $request, int $idVacante): JsonResponse
    {
        $vacante = Vacante::where('Activo', true)
            ->where('Estatus', 'ABIERTA')
            ->findOrFail($idVacante);

        $data = $request->validate([
            'Nombre'                => 'required|string|max:100',
            'ApellidoPaterno'       => 'required|string|max:100',
            'ApellidoMaterno'       => 'nullable|string|max:100',
            'Correo'                => 'required|email|max:150',
            'Telefono'              => 'required|string|max:20',
            'TelefonoAlterno'       => 'nullable|string|max:20',
            'Escolaridad'           => 'nullable|string|max:100',
            'Profesion'             => 'nullable|string|max:100',
            'ExperienciaResumen'    => 'nullable|string|max:4000',
            'LinkedIn_URL'          => 'nullable|url|max:500',
            'PretensionSalarial'    => 'nullable|numeric|min:0',
            'Observaciones'         => 'nullable|string|max:2000',
            'cv'                    => 'nullable|file|mimes:pdf,doc,docx|max:' . (self::CV_MAX_MB * 1024),
        ]);

        unset($data['cv']);

        $candidato = DB::transaction(function () use ($request, $data, $vacante) {
            $candidato = Candidato::create([
                ...$data,
                'ID_Vacante' => $vacante->ID_Vacante,
                'ID_EstatusCandidato' => $this->idEstatusNuevo(),
                'Fuente' => 'PORTAL_PUBLICO',
                'FechaPostulacion' => now(),
                'Activo' => true,
            ]);

            if ($request->hasFile('cv')) {
                $candidato->CV_URL = $this->guardarCv($request, $candidato);
                $candidato->save();
            }

            return $candidato;
        });

        if ($vacante->ID_UsuarioResponsable) {
            Notificacion::enviar(
                (int) $vacante->ID_UsuarioResponsable,
                'RH',
                'RH',
                "Nuevo postulante para {$vacante->Titulo}",
                trim("{$candidato->Nombre} {$candidato->ApellidoPaterno}") . ' se postulo desde el portal publico.',
                $candidato->ID_Candidato,
            );
        }

        return response()->json([
            'message' => 'Postulacion recibida.',
            'data' => [
                'id_candidato' => $candidato->ID_Candidato,
                'folio_vacante' => $vacante->Folio,
                'vacante' => $vacante->Titulo,
            ],
        ], 201);
    }

    public function descargarCv(int $id)
    {
        $adjunto = DB::table('core.adjunto')
            ->where('Modulo', 'rh')
            ->where('Entidad', 'candidato_cv')
            ->where('ID_Referencia', $id)
            ->where('Activo', 1)
            ->orderByDesc('FechaCreacion')
            ->first();

        if (!$adjunto || !$adjunto->RutaArchivo || !Storage::disk('local')->exists($adjunto->RutaArchivo)) {
            return response()->json(['message' => 'CV no disponible.'], 404);
        }

        return Storage::disk('local')->download($adjunto->RutaArchivo, $adjunto->NombreArchivo);
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

    private function idEstatusNuevo(): int
    {
        $id = DB::table('rh.estatus_candidato')
            ->where('Activo', 1)
            ->where('Nombre', 'NUEVO')
            ->value('ID_EstatusCandidato');

        return (int) ($id ?: DB::table('rh.estatus_candidato')
            ->where('Activo', 1)
            ->orderBy('OrdenProceso')
            ->value('ID_EstatusCandidato'));
    }

    private function guardarCv(Request $request, Candidato $candidato): string
    {
        $file = $request->file('cv');
        $extension = strtolower($file->getClientOriginalExtension());

        if (!in_array($extension, self::CV_EXTENSIONS, true)) {
            abort(422, 'El CV debe ser PDF, DOC o DOCX.');
        }

        $nombreOriginal = $file->getClientOriginalName();
        $nombreBase = Str::slug(pathinfo($nombreOriginal, PATHINFO_FILENAME)) ?: 'cv';
        $nombreFisico = now()->format('YmdHis') . '_' . Str::uuid() . '_' . $nombreBase . '.' . $extension;
        $carpeta = "rh/candidato_cv/{$candidato->ID_Candidato}";
        $ruta = $file->storeAs($carpeta, $nombreFisico, 'local');

        DB::table('core.adjunto')->insert([
            'Modulo' => 'rh',
            'Entidad' => 'candidato_cv',
            'ID_Referencia' => $candidato->ID_Candidato,
            'NombreArchivo' => $nombreOriginal,
            'RutaArchivo' => $ruta,
            'Extension' => $extension,
            'TamanoBytes' => $file->getSize(),
            'MimeType' => $file->getMimeType(),
            'Descripcion' => 'CV cargado por postulante publico',
            'Activo' => 1,
            'ID_UsuarioCreo' => null,
        ]);

        return "/api/rh/candidatos/{$candidato->ID_Candidato}/cv";
    }
}
