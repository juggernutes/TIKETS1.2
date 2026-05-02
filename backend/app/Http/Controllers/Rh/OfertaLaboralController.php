<?php

namespace App\Http\Controllers\Rh;

use App\Http\Controllers\Controller;
use App\Models\Core\Notificacion;
use App\Models\Rh\OfertaLaboral;
use App\Enums\RhEstatusOferta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OfertaLaboralController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ID_Candidato'    => 'required|integer',
            'ID_Vacante'      => 'required|integer',
            'SalarioOfertado' => 'required|numeric|min:0',
            'FechaVencimiento'=> 'nullable|date|after:today',
            'FechaIngreso'    => 'nullable|date',
        ]);

        $this->validarExiste($data, 'ID_Candidato', 'rh.candidato', 'ID_Candidato', 'El candidato seleccionado no existe.');
        $this->validarExiste($data, 'ID_Vacante', 'rh.vacante', 'ID_Vacante', 'La vacante seleccionada no existe.');

        $oferta = OfertaLaboral::create($data);

        // Notificar al responsable de la vacante que la oferta fue enviada
        $vacante = DB::table('rh.vacante')
            ->where('ID_Vacante', $data['ID_Vacante'])
            ->first(['Titulo', 'ID_UsuarioResponsable']);

        if ($vacante?->ID_UsuarioResponsable) {
            $nombreCandidato = DB::table('rh.candidato')
                ->where('ID_Candidato', $data['ID_Candidato'])
                ->value('Nombre') ?? 'Candidato';

            Notificacion::enviar(
                $vacante->ID_UsuarioResponsable,
                'VACANTE',
                'RH',
                "Oferta enviada a: {$nombreCandidato}",
                "Vacante: {$vacante->Titulo} — Salario: $" . number_format($data['SalarioOfertado'], 2),
                $oferta->ID_Oferta,
            );
        }

        return response()->json([
            'message' => 'Oferta enviada.',
            'data'    => $oferta->load(['candidato', 'vacante']),
        ], 201);
    }

    public function responder(Request $request, int $id): JsonResponse
    {
        $oferta = OfertaLaboral::findOrFail($id);

        $data = $request->validate([
            'Estatus'         => 'required|in:ACEPTADA,RECHAZADA,NEGOCIACION',
            'MotivoRechazo'   => 'nullable|string|max:300',
            'Contrapropuesta' => 'nullable|numeric|min:0',
            'FechaIngreso'    => 'nullable|date',
        ]);

        $oferta->FechaRespuesta = now();
        $oferta->fill($data)->save();

        // Notificar al responsable de la vacante sobre la respuesta
        $idResponsable = DB::table('rh.vacante')
            ->where('ID_Vacante', $oferta->ID_Vacante)
            ->value('ID_UsuarioResponsable');

        if ($idResponsable) {
            $nombreCandidato = DB::table('rh.candidato')
                ->where('ID_Candidato', $oferta->ID_Candidato)
                ->value('Nombre') ?? 'Candidato';

            $etiquetas = ['ACEPTADA' => 'aceptó', 'RECHAZADA' => 'rechazó', 'NEGOCIACION' => 'envió contrapropuesta en'];
            $accion    = $etiquetas[$data['Estatus']] ?? $data['Estatus'];

            Notificacion::enviar(
                $idResponsable,
                'VACANTE',
                'RH',
                "{$nombreCandidato} {$accion} la oferta laboral",
                $data['MotivoRechazo'] ?? null,
                $oferta->ID_Oferta,
            );
        }

        return response()->json([
            'message' => 'Respuesta registrada.',
            'data'    => $oferta->fresh(),
        ]);
    }

    private function validarExiste(array $data, string $campo, string $tabla, string $columna, string $mensaje): void
    {
        if (!array_key_exists($campo, $data) || $data[$campo] === null || $data[$campo] === '') {
            return;
        }

        $existe = DB::table($tabla)->where($columna, $data[$campo])->exists();

        if (!$existe) {
            throw ValidationException::withMessages([$campo => [$mensaje]]);
        }
    }
}
