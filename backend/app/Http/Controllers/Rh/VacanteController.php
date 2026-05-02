<?php

namespace App\Http\Controllers\Rh;

use App\Http\Controllers\Controller;
use App\Models\Core\Notificacion;
use App\Models\Rh\Vacante;
use App\Enums\RhEstatusVacante;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VacanteController extends Controller
{
    public function publicIndex(Request $request): JsonResponse
    {
        $query = Vacante::with(['area', 'puesto', 'sucursal'])
            ->where('Activo', true)
            ->where('Estatus', RhEstatusVacante::ABIERTA->value);

        if ($request->filled('id_area')) {
            $query->where('ID_Area', $request->integer('id_area'));
        }

        if ($request->filled('id_sucursal')) {
            $query->where('ID_Sucursal', $request->integer('id_sucursal'));
        }

        $perPage = min(50, max(1, $request->integer('per_page', 20)));

        return response()->json($query->orderByDesc('FechaPublicacion')->paginate($perPage));
    }

    public function publicShow(int $id): JsonResponse
    {
        $vacante = Vacante::with(['area', 'puesto', 'sucursal'])
            ->where('Activo', true)
            ->where('Estatus', RhEstatusVacante::ABIERTA->value)
            ->findOrFail($id);

        return response()->json($vacante);
    }

    public function index(Request $request): JsonResponse
    {
        $query = Vacante::with(['area', 'puesto', 'sucursal', 'usuarioResponsable'])
            ->where('Activo', true);

        if ($request->filled('estatus')) {
            $query->where('Estatus', $request->string('estatus')->upper());
        }

        if ($request->filled('id_area')) {
            $query->where('ID_Area', $request->integer('id_area'));
        }

        if ($request->filled('id_sucursal')) {
            $query->where('ID_Sucursal', $request->integer('id_sucursal'));
        }

        $perPage = min(100, max(1, $request->integer('per_page', 20)));

        return response()->json($query->orderByDesc('FechaCreacion')->paginate($perPage));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'Titulo'                 => 'required|string|max:100',
            'Descripcion'            => 'nullable|string',
            'Perfil'                 => 'nullable|string',
            'Requisitos'             => 'nullable|string',
            'SalarioMin'             => 'nullable|numeric|min:0',
            'SalarioMax'             => 'nullable|numeric|gte:SalarioMin',
            'NumeroPosiciones'       => 'integer|min:1',
            'ID_Area'                => 'nullable|integer',
            'ID_Puesto'              => 'nullable|integer',
            'ID_Sucursal'            => 'nullable|integer',
            'ID_UsuarioSolicita'     => 'nullable|integer',
            'ID_UsuarioResponsable'  => 'nullable|integer',
            'DetonanteTipo'          => 'nullable|string|in:BAJA_EMPLEADO,CREACION_PUESTO,NUEVA_POSICION',
            'DetonanteEmpleadoNumero'=> 'nullable|integer',
            'DetonantePuestoNombre'  => 'nullable|string|max:150',
            'DetonanteComentario'    => 'nullable|string|max:500',
        ]);

        $this->validarCatalogos($data);

        $vacante = Vacante::create($data);

        return response()->json([
            'message' => 'Vacante creada.',
            'data'    => $vacante->load(['area', 'puesto', 'sucursal']),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $vacante = Vacante::with([
            'area', 'puesto', 'sucursal',
            'usuarioSolicita', 'usuarioResponsable',
            'candidatos.estatus',
        ])->findOrFail($id);

        return response()->json($vacante);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $vacante = Vacante::findOrFail($id);

        $data = $request->validate([
            'Titulo'                => 'sometimes|string|max:100',
            'Descripcion'           => 'nullable|string',
            'Perfil'                => 'nullable|string',
            'Requisitos'            => 'nullable|string',
            'SalarioMin'            => 'nullable|numeric|min:0',
            'SalarioMax'            => 'nullable|numeric',
            'NumeroPosiciones'      => 'sometimes|integer|min:1',
            'ID_Area'               => 'nullable|integer',
            'ID_Puesto'             => 'nullable|integer',
            'ID_Sucursal'           => 'nullable|integer',
            'ID_UsuarioSolicita'    => 'nullable|integer',
            'ID_UsuarioResponsable' => 'nullable|integer',
            'DetonanteTipo'         => 'nullable|string|in:BAJA_EMPLEADO,CREACION_PUESTO,NUEVA_POSICION',
            'DetonanteEmpleadoNumero'=> 'nullable|integer',
            'DetonantePuestoNombre' => 'nullable|string|max:150',
            'DetonanteComentario'   => 'nullable|string|max:500',
        ]);

        $this->validarCatalogos($data);

        $vacante->update($data);

        return response()->json([
            'message' => 'Vacante actualizada.',
            'data'    => $vacante->fresh(),
        ]);
    }

    public function cambiarEstatus(Request $request, int $id): JsonResponse
    {
        $vacante = Vacante::findOrFail($id);

        $data = $request->validate([
            'Estatus'    => 'required|string|in:ABIERTA,PAUSADA,CERRADA,CANCELADA',
            'Comentario' => 'nullable|string|max:500',
        ]);

        $estatusAnterior = $vacante->Estatus instanceof RhEstatusVacante
            ? $vacante->Estatus->value
            : $vacante->Estatus;

        $vacante->Estatus = $data['Estatus'];
        if ($data['Estatus'] !== 'ABIERTA') {
            $vacante->FechaCierre = now();
        }
        $vacante->save();

        // Notificar al responsable y al solicitante (si son distintos)
        $destinatarios = array_filter(array_unique([
            $vacante->ID_UsuarioResponsable,
            $vacante->ID_UsuarioSolicita,
        ]));

        if (!empty($destinatarios)) {
            $etiquetas = ['PAUSADA' => 'pausada', 'CERRADA' => 'cerrada', 'CANCELADA' => 'cancelada', 'ABIERTA' => 'abierta'];
            $accion    = $etiquetas[$data['Estatus']] ?? strtolower($data['Estatus']);

            Notificacion::enviarAVarios(
                array_values($destinatarios),
                'VACANTE',
                'RH',
                "Vacante \"{$vacante->Titulo}\" fue {$accion}",
                $data['Comentario'] ?? null,
                $vacante->ID_Vacante,
            );
        }

        // Log del cambio
        DB::table('rh.vacante_log')->insert([
            'ID_Vacante'     => $vacante->ID_Vacante,
            'EstadoAnterior' => $estatusAnterior,
            'EstadoNuevo'    => $data['Estatus'],
            'Comentario'     => $data['Comentario'] ?? null,
            'Fecha'          => now(),
        ]);

        return response()->json([
            'message' => 'Estatus actualizado.',
            'data'    => $vacante->fresh(),
        ]);
    }

    private function validarCatalogos(array $data): void
    {
        $this->validarExiste($data, 'ID_Area', 'core.area', 'ID_Area', 'El area seleccionada no existe.');
        $this->validarExiste($data, 'ID_Puesto', 'core.puesto', 'ID_Puesto', 'El puesto seleccionado no existe.');
        $this->validarExiste($data, 'ID_Sucursal', 'core.sucursal', 'ID_Sucursal', 'La sucursal seleccionada no existe.');
        $this->validarExiste($data, 'ID_UsuarioSolicita', 'core.usuario', 'ID_Usuario', 'El usuario solicitante no existe.');
        $this->validarExiste($data, 'ID_UsuarioResponsable', 'core.usuario', 'ID_Usuario', 'El usuario responsable no existe.');
        $this->validarExiste($data, 'DetonanteEmpleadoNumero', 'core.empleado', 'Numero_Empleado', 'El empleado detonante no existe.');
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
