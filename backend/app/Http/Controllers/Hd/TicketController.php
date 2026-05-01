<?php

namespace App\Http\Controllers\Hd;

use App\Http\Controllers\Controller;
use App\Models\Core\Notificacion;
use App\Models\Hd\Ticket;
use App\Models\Hd\TicketAgenteLog;
use App\Models\Hd\TicketAsignacionArea;
use App\Models\Hd\TicketEstatusLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TicketController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Ticket::with(['empleado', 'estatus', 'soporte', 'areaResponsable', 'sistema', 'proveedor'])
            ->where('Activo', true);

        if ($request->filled('id_estatus')) {
            $query->where('ID_Estatus', $request->integer('id_estatus'));
        }

        if ($request->filled('id_area_responsable')) {
            $query->where('ID_Area_Responsable', $request->integer('id_area_responsable'));
        }

        if ($request->filled('numero_empleado')) {
            $query->where('Numero_Empleado', $request->integer('numero_empleado'));
        }

        if ($request->filled('id_soporte')) {
            $query->where('ID_Soporte', $request->integer('id_soporte'));
        }

        if ($request->filled('vista')) {
            $usuario = $request->user();
            $vista = $request->string('vista')->toString();

            if ($vista === 'reportados' && $usuario) {
                $query->where('ID_Usuario_Reporta', $usuario->ID_Usuario);
            }

            if ($vista === 'asignados' && $usuario) {
                $query->where('ID_Soporte', $usuario->ID_Usuario);
            }

            if ($vista === 'mi_area' && $usuario?->ID_Area) {
                $query->where('ID_Area_Responsable', $usuario->ID_Area);
            }

            if ($vista === 'cerrados') {
                $query->whereHas('estatus', fn ($q) => $q->whereIn('Nombre', ['Resuelto', 'Cerrado', 'Cancelado']));
            }
        }

        if ($request->filled('fecha_desde')) {
            $query->where('FechaReporte', '>=', $request->string('fecha_desde'));
        }

        if ($request->filled('fecha_hasta')) {
            $query->where('FechaReporte', '<=', $request->string('fecha_hasta'));
        }

        $perPage = min(100, max(1, $request->integer('per_page', 20)));

        return response()->json($query->orderByDesc('FechaReporte')->paginate($perPage));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'Numero_Empleado'     => 'required|integer',
            'ID_Area_Origen'      => 'nullable|integer',
            'ID_Area_Responsable' => 'nullable|integer',
            'ID_Sistema'          => 'required|integer',
            'ID_Error'            => 'nullable|integer',
            'ID_Usuario_Reporta'  => 'nullable|integer',
            'Descripcion'         => 'required|string|max:4000',
            'Prioridad'           => 'nullable|string|in:CRITICA,ALTA,MEDIA,BAJA',
            'ID_SLA'              => 'nullable|integer',
        ]);
        $this->validarExiste('Numero_Empleado', 'core.empleado', 'Numero_Empleado', $data['Numero_Empleado']);
        $this->validarExiste('ID_Sistema', 'cat.sistema', 'ID_Sistema', $data['ID_Sistema']);
        $this->validarExisteOpcional('ID_Area_Origen', 'core.area', 'ID_Area', $data['ID_Area_Origen'] ?? null);
        $this->validarExisteOpcional('ID_Error', 'hd.error', 'ID_Error', $data['ID_Error'] ?? null);
        $this->validarExisteOpcional('ID_Usuario_Reporta', 'core.usuario', 'ID_Usuario', $data['ID_Usuario_Reporta'] ?? null);
        $this->validarExisteOpcional('ID_SLA', 'hd.sla', 'ID_SLA', $data['ID_SLA'] ?? null);

        $usuario = $request->user();
        $data['ID_Usuario_Reporta'] = $data['ID_Usuario_Reporta'] ?? $usuario?->ID_Usuario;
        $data['ID_Area_Origen'] = $this->idAreaOrigenTicket($data['Numero_Empleado'], $usuario?->ID_Area, $data['ID_Area_Origen'] ?? null);
        if (!$data['ID_Area_Origen']) {
            throw ValidationException::withMessages([
                'ID_Area_Origen' => ['No se pudo determinar el area de origen del empleado que levanto el ticket.'],
            ]);
        }
        $data['ID_Area_Responsable'] = $this->idAreaTecnologias()
            ?? DB::table('cat.sistema')->where('ID_Sistema', $data['ID_Sistema'])->value('ID_Area')
            ?? $data['ID_Area_Origen'];
        $data['ID_Error'] = $data['ID_Error']
            ?? DB::table('hd.error')->where('Activo', 1)->where('Descripcion', 'Otro problema no listado')->value('ID_Error')
            ?? DB::table('hd.error')->where('Activo', 1)->value('ID_Error');
        $data['Prioridad'] = $data['Prioridad'] ?? 'MEDIA';

        $ticket = DB::transaction(function () use ($data, $usuario) {
            $estatusAbierto = $this->idEstatusPorNombre('Abierto')
                ?? $this->idEstatusPorNombre('Nuevo');
            $data['ID_Estatus'] = $estatusAbierto;
            $data['SerieFolio'] = $this->generarFolio($data['ID_Area_Responsable']);

            // Asignar SLA automático si no se especificó
            if (empty($data['ID_SLA'])) {
                $prioridad = $data['Prioridad'] ?? 'MEDIA';
                $sla = DB::table('hd.sla')
                    ->where('Prioridad', $prioridad)
                    ->where('Activo', 1)
                    ->whereNull('ID_Area')
                    ->first();
                if ($sla) {
                    $data['ID_SLA']      = $sla->ID_SLA;
                    $data['FechaLimite'] = now()->addHours((int) $sla->HorasResolucion);
                }
            }

            $ticket = Ticket::create($data);

            TicketEstatusLog::create([
                'ID_Ticket' => $ticket->ID_Ticket,
                'ID_Estatus_Anterior' => null,
                'ID_Estatus_Nuevo' => $estatusAbierto,
                'ID_Usuario' => $usuario?->ID_Usuario,
                'Comentario' => 'Ticket creado.',
            ]);

            TicketAsignacionArea::create([
                'ID_Ticket' => $ticket->ID_Ticket,
                'ID_Area' => $ticket->ID_Area_Responsable,
                'ID_UsuarioAsigno' => $usuario?->ID_Usuario,
                'Activa' => true,
            ]);

            return $ticket;
        });

        // Notificar a todos los usuarios del área responsable
        $idsAgentes = DB::table('core.usuario')
            ->where('ID_Area', $data['ID_Area_Responsable'])
            ->where('Activo', true)
            ->pluck('ID_Usuario')
            ->toArray();

        if (!empty($idsAgentes)) {
            $nombreEmpleado = DB::table('core.empleado')
                ->where('Numero_Empleado', $data['Numero_Empleado'])
                ->value('Nombre') ?? 'Empleado';

            Notificacion::enviarAVarios(
                $idsAgentes,
                'TICKET',
                'HD',
                "Nuevo ticket #{$ticket->SerieFolio}",
                "{$nombreEmpleado}: " . mb_strimwidth($data['Descripcion'], 0, 100, '…'),
                $ticket->ID_Ticket,
            );
        }

        return response()->json([
            'message' => 'Ticket creado.',
            'data'    => $ticket->load(['empleado', 'estatus', 'sistema', 'error', 'areaOrigen', 'areaResponsable']),
        ], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $ticket = Ticket::with([
            'empleado', 'estatus', 'soporte', 'proveedor', 'areaOrigen',
            'areaResponsable', 'sistema', 'error', 'solucion',
            'reporta', 'comentarios.usuario', 'encuesta',
            'estatusLogs.usuario', 'agenteLogs.usuarioAsigno',
        ])->findOrFail($id);

        if ($this->marcarEnProcesoSiAplica($ticket, $request)) {
            $ticket = Ticket::with([
                'empleado', 'estatus', 'soporte', 'proveedor', 'areaOrigen',
                'areaResponsable', 'sistema', 'error', 'solucion',
                'reporta', 'comentarios.usuario', 'encuesta',
                'estatusLogs.usuario', 'agenteLogs.usuarioAsigno',
            ])->findOrFail($id);
        }

        return response()->json($ticket);
    }

    public function actualizarEstatus(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'ID_Estatus'      => 'required|integer',
            'ID_Soporte'      => 'nullable|integer',
            'ID_Area_Responsable' => 'nullable|integer',
            'ID_Error'        => 'nullable|integer',
            'ID_Solucion'     => 'nullable|integer',
            'DetalleSolucion' => 'nullable|string|max:4000',
            'Prioridad'       => 'nullable|string|in:CRITICA,ALTA,MEDIA,BAJA',
        ]);
        $this->validarExiste('ID_Estatus', 'hd.estatus', 'ID_Estatus', $data['ID_Estatus']);
        $this->validarExisteOpcional('ID_Soporte', 'core.usuario', 'ID_Usuario', $data['ID_Soporte'] ?? null);
        $this->validarExisteOpcional('ID_Area_Responsable', 'core.area', 'ID_Area', $data['ID_Area_Responsable'] ?? null);
        $this->validarExisteOpcional('ID_Error', 'hd.error', 'ID_Error', $data['ID_Error'] ?? null);
        $this->validarExisteOpcional('ID_Solucion', 'hd.solucion', 'ID_Solucion', $data['ID_Solucion'] ?? null);

        $ticket = Ticket::findOrFail($id);
        $estatusAnterior = $ticket->ID_Estatus;
        $areaAnterior = $ticket->ID_Area_Responsable;
        $ticket->fill($data);

        $estatus = DB::table('hd.estatus')->where('ID_Estatus', $data['ID_Estatus'])->first(['Nombre']);
        $nombreEstatus = $estatus?->Nombre ?? '';

        if (strcasecmp($nombreEstatus, 'Resuelto') === 0) {
            $ticket->FechaSolucion = now();
        }
        if (strcasecmp($nombreEstatus, 'Cerrado') === 0) {
            $ticket->ID_Usuario_Cierra = $request->user()?->ID_Usuario;
        }
        if (!$ticket->FechaAsignacion && $ticket->ID_Soporte) {
            $ticket->FechaAsignacion = now();
        }

        DB::transaction(function () use ($ticket, $estatusAnterior, $areaAnterior, $data, $request) {
            $ticket->save();

            if ($estatusAnterior !== $ticket->ID_Estatus) {
                TicketEstatusLog::create([
                    'ID_Ticket' => $ticket->ID_Ticket,
                    'ID_Estatus_Anterior' => $estatusAnterior,
                    'ID_Estatus_Nuevo' => $ticket->ID_Estatus,
                    'ID_Usuario' => $request->user()?->ID_Usuario,
                    'Comentario' => $data['DetalleSolucion'] ?? null,
                ]);
            }

            if (!empty($data['ID_Area_Responsable']) && $areaAnterior !== $ticket->ID_Area_Responsable) {
                TicketAsignacionArea::where('ID_Ticket', $ticket->ID_Ticket)->update(['Activa' => false]);
                TicketAsignacionArea::create([
                    'ID_Ticket' => $ticket->ID_Ticket,
                    'ID_Area' => $ticket->ID_Area_Responsable,
                    'ID_UsuarioAsigno' => $request->user()?->ID_Usuario,
                    'Activa' => true,
                ]);
            }
        });

        // Notificar al empleado que reportó (si tiene usuario asociado)
        $idUsuarioEmpleado = DB::table('core.usuario_relacion')
            ->where('Numero_Empleado', $ticket->Numero_Empleado)
            ->value('ID_Usuario');

        if ($idUsuarioEmpleado) {
            Notificacion::enviar(
                $idUsuarioEmpleado,
                'TICKET',
                'HD',
                "Tu ticket #{$ticket->SerieFolio} cambió a: {$nombreEstatus}",
                null,
                $ticket->ID_Ticket,
            );
        }

        // Notificar al agente si fue asignado en este cambio
        if (!empty($data['ID_Soporte'])) {
            Notificacion::enviar(
                $data['ID_Soporte'],
                'TICKET',
                'HD',
                "Se te asignó el ticket #{$ticket->SerieFolio}",
                mb_strimwidth($ticket->Descripcion, 0, 100, '…'),
                $ticket->ID_Ticket,
            );
        }

        return response()->json([
            'message' => 'Estatus actualizado.',
            'data'    => $ticket->load('estatus', 'soporte', 'areaResponsable', 'error', 'solucion'),
        ]);
    }

    public function enviarProveedor(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'ID_Proveedor' => 'required|integer',
            'SeguimientoProveedor' => 'nullable|string|max:4000',
        ]);
        $this->validarExiste('ID_Proveedor', 'core.proveedor', 'ID_Proveedor', $data['ID_Proveedor']);

        $ticket = Ticket::findOrFail($id);
        $estatusAnterior = $ticket->ID_Estatus;
        $estatusEspera = DB::table('hd.estatus')->where('Nombre', 'En espera')->value('ID_Estatus');

        DB::transaction(function () use ($ticket, $data, $estatusAnterior, $estatusEspera, $request) {
            $ticket->ID_Proveedor = $data['ID_Proveedor'];
            $ticket->FechaEnvioProveedor = now();
            $ticket->SeguimientoProveedor = $data['SeguimientoProveedor'] ?? null;
            if ($estatusEspera) {
                $ticket->ID_Estatus = $estatusEspera;
            }
            $ticket->save();

            if ($estatusEspera && $estatusAnterior !== $ticket->ID_Estatus) {
                TicketEstatusLog::create([
                    'ID_Ticket' => $ticket->ID_Ticket,
                    'ID_Estatus_Anterior' => $estatusAnterior,
                    'ID_Estatus_Nuevo' => $ticket->ID_Estatus,
                    'ID_Usuario' => $request->user()?->ID_Usuario,
                    'Comentario' => $data['SeguimientoProveedor'] ?? 'Ticket enviado a proveedor.',
                ]);
            }
        });

        $proveedor = DB::table('core.proveedor')
            ->where('ID_Proveedor', $data['ID_Proveedor'])
            ->first(['Nombre', 'ID_Usuario']);

        if ($proveedor?->ID_Usuario) {
            Notificacion::enviar(
                $proveedor->ID_Usuario,
                'TICKET',
                'HD',
                "Ticket #{$ticket->SerieFolio} enviado a proveedor",
                mb_strimwidth($ticket->Descripcion, 0, 100, '…'),
                $ticket->ID_Ticket,
            );
        }

        return response()->json([
            'message' => "Ticket enviado a proveedor {$proveedor?->Nombre}.",
            'data' => $ticket->load('proveedor', 'estatus'),
        ]);
    }

    public function asignarAgente(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'ID_Soporte' => 'required|integer',
        ]);
        $this->validarExiste('ID_Soporte', 'core.usuario', 'ID_Usuario', $data['ID_Soporte']);

        $ticket = Ticket::findOrFail($id);
        $soporteAnterior = $ticket->ID_Soporte;
        $estatusAnterior = $ticket->ID_Estatus;
        $estatusAsignado = $this->idEstatusPorNombre('Asignado');
        $ticket->ID_Soporte      = $data['ID_Soporte'];
        $ticket->FechaAsignacion = $ticket->FechaAsignacion ?? now();
        if ($estatusAsignado) {
            $ticket->ID_Estatus = $estatusAsignado;
        }

        DB::transaction(function () use ($ticket, $soporteAnterior, $estatusAnterior, $estatusAsignado, $data, $request) {
            $ticket->save();

            if ($soporteAnterior !== $data['ID_Soporte']) {
                TicketAgenteLog::create([
                    'ID_Ticket' => $ticket->ID_Ticket,
                    'ID_Soporte_Anterior' => $soporteAnterior,
                    'ID_Soporte_Nuevo' => $data['ID_Soporte'],
                    'ID_UsuarioAsigno' => $request->user()?->ID_Usuario,
                ]);
            }

            if ($estatusAsignado && $estatusAnterior !== $ticket->ID_Estatus) {
                TicketEstatusLog::create([
                    'ID_Ticket' => $ticket->ID_Ticket,
                    'ID_Estatus_Anterior' => $estatusAnterior,
                    'ID_Estatus_Nuevo' => $ticket->ID_Estatus,
                    'ID_Usuario' => $request->user()?->ID_Usuario,
                    'Comentario' => 'Ticket asignado a soporte.',
                ]);
            }
        });

        // Notificar al agente asignado
        Notificacion::enviar(
            $data['ID_Soporte'],
            'TICKET',
            'HD',
            "Se te asignó el ticket #{$ticket->SerieFolio}",
            mb_strimwidth($ticket->Descripcion, 0, 100, '…'),
            $ticket->ID_Ticket,
        );

        return response()->json([
            'message' => 'Agente asignado.',
            'data'    => $ticket->load('soporte', 'estatus'),
        ]);
    }

    private function generarFolio(int $idArea): string
    {
        $serie = DB::table('core.area')->where('ID_Area', $idArea)->value('Serie') ?? 'GEN';
        $fecha = now()->format('Ymd');

        $consecutivo = DB::table('core.folio_area_diario')
            ->where('ID_Area', $idArea)
            ->where('Fecha', now()->toDateString())
            ->value('Consecutivo');

        if ($consecutivo === null) {
            DB::table('core.folio_area_diario')->insert([
                'ID_Area'     => $idArea,
                'Fecha'       => now()->toDateString(),
                'Consecutivo' => 1,
            ]);
            $consecutivo = 1;
        } else {
            DB::table('core.folio_area_diario')
                ->where('ID_Area', $idArea)
                ->where('Fecha', now()->toDateString())
                ->increment('Consecutivo');
            $consecutivo++;
        }

        return $serie . '-' . $fecha . '-' . str_pad($consecutivo, 4, '0', STR_PAD_LEFT);
    }

    private function validarExisteOpcional(string $campo, string $tabla, string $columna, mixed $valor): void
    {
        if ($valor === null || $valor === '') {
            return;
        }

        $this->validarExiste($campo, $tabla, $columna, $valor);
    }

    private function validarExiste(string $campo, string $tabla, string $columna, mixed $valor): void
    {
        if (!DB::table($tabla)->where($columna, $valor)->exists()) {
            throw ValidationException::withMessages([
                $campo => ["El valor seleccionado en {$campo} no es valido."],
            ]);
        }
    }

    private function idEstatusPorNombre(string $nombre): ?int
    {
        $id = DB::table('hd.estatus')->where('Nombre', $nombre)->value('ID_Estatus');

        return $id === null ? null : (int) $id;
    }

    private function idAreaOrigenTicket(int $numeroEmpleado, ?int $idAreaUsuario, ?int $idAreaSolicitud): ?int
    {
        $idAreaEmpleado = DB::table('core.empleado')
            ->where('Numero_Empleado', $numeroEmpleado)
            ->value('ID_Area');

        return $idAreaEmpleado !== null
            ? (int) $idAreaEmpleado
            : ($idAreaUsuario ?? $idAreaSolicitud);
    }

    private function idAreaTecnologias(): ?int
    {
        $id = DB::table('core.area')
            ->whereIn(DB::raw('UPPER(Nombre)'), ['TECNOLOGIAS', 'TECNOLOGIA', 'TECNOLOGÍAS', 'SISTEMAS', 'TI'])
            ->orderByRaw("CASE WHEN UPPER(Nombre) IN ('TECNOLOGIAS', 'TECNOLOGÍAS') THEN 0 ELSE 1 END")
            ->value('ID_Area');

        return $id === null ? null : (int) $id;
    }

    private function marcarEnProcesoSiAplica(Ticket $ticket, Request $request): bool
    {
        $usuario = $request->user();
        $estatusActual = strtoupper((string) ($ticket->estatus?->Nombre ?? ''));
        if (!$usuario || !in_array($estatusActual, ['ABIERTO', 'NUEVO'], true)) {
            return false;
        }

        $rol = strtoupper((string) ($usuario->rol?->Nombre ?? ''));
        $esSoporte = in_array($rol, ['ADMIN', 'ADMINISTRADOR', 'SOPORTE_HD', 'SOPORTE HD'], true)
            || ((int) $usuario->ID_Area === (int) $ticket->ID_Area_Responsable);

        if (!$esSoporte) {
            return false;
        }

        $estatusEnProceso = $this->idEstatusPorNombre('En proceso');
        if (!$estatusEnProceso || (int) $ticket->ID_Estatus === $estatusEnProceso) {
            return false;
        }

        $estatusAnterior = $ticket->ID_Estatus;

        DB::transaction(function () use ($ticket, $estatusAnterior, $estatusEnProceso, $usuario) {
            $ticket->ID_Estatus = $estatusEnProceso;
            $ticket->save();

            TicketEstatusLog::create([
                'ID_Ticket' => $ticket->ID_Ticket,
                'ID_Estatus_Anterior' => $estatusAnterior,
                'ID_Estatus_Nuevo' => $estatusEnProceso,
                'ID_Usuario' => $usuario->ID_Usuario,
                'Comentario' => 'Ticket tomado en proceso por Help Desk.',
            ]);
        });

        return true;
    }
}
