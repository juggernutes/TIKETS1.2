<?php

namespace App\Http\Controllers\Ped;

use App\Http\Controllers\Controller;
use App\Models\Core\Notificacion;
use App\Models\Ped\Pedido;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PedidoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = $this->basePedidoQuery();
        $this->aplicarAlcanceConsulta($query, $request);

        if ($request->filled('id_estado')) {
            $val = $request->input('id_estado');
            if (is_numeric($val)) {
                $query->where('IdEstado', (int) $val);
            } else {
                $idEstado = $this->idEstadoPorNombre((string) $val);
                $idEstado ? $query->where('IdEstado', $idEstado) : $query->whereRaw('1 = 0');
            }
        }

        if ($request->filled('folio')) {
            $query->where('FolioPedido', 'like', '%' . $request->string('folio') . '%');
        }

        if ($request->filled('id_unidad')) {
            $query->where('IdUnidadPedido', $request->integer('id_unidad'));
        }

        $semanaParam = $request->input('ID_Semana') ?? $request->input('id_semana');
        if ($semanaParam) {
            $query->where('ID_Semana', (int) $semanaParam);
        }

        if ($request->filled('dia')) {
            $query->where('Dia', $request->string('dia'));
        }

        if ($request->filled('fecha_desde')) {
            $query->where('FechaPedido', '>=', $request->string('fecha_desde'));
        }

        if ($request->filled('fecha_hasta')) {
            $query->where('FechaPedido', '<=', $request->string('fecha_hasta'));
        }

        $perPage = min(200, max(1, $request->integer('per_page', 50)));

        return response()->json($query->orderByDesc('FechaPedido')->paginate($perPage));
    }

    public function miUnidad(Request $request): JsonResponse
    {
        $unidad = $this->unidadDelUsuario($this->idUsuarioActual($request));

        if (!$unidad) {
            return response()->json(['message' => 'El usuario no tiene unidad operacional asignada.'], 404);
        }

        return response()->json($unidad);
    }

    public function porAutorizar(Request $request): JsonResponse
    {
        $query = $this->basePedidoQuery()
            ->where('IdEstado', $this->idEstadoCapturado());

        $this->aplicarAlcanceAutoriza($query, $request);

        return response()->json($query->orderByDesc('FechaPedido')->paginate($this->perPage($request)));
    }

    public function porSurtir(Request $request): JsonResponse
    {
        $query = $this->basePedidoQuery()
            ->where('IdEstado', $this->idEstadoAutorizado());

        $this->aplicarAlcanceSurtido($query, $request);

        return response()->json($query->orderByDesc('FechaPedido')->paginate($this->perPage($request)));
    }

    public function store(Request $request): JsonResponse
    {
        $unidad = $this->unidadDelUsuario($this->idUsuarioActual($request));
        $esAdmin = $this->esAdmin($request);

        $rules = [
            'ObserVen'       => 'nullable|string|max:200',
            'detalles'       => 'required|array|min:1',
            'detalles.*.IdArticulo' => 'required|integer',
            'detalles.*.CanPzPed'  => 'required|integer|min:1',
            'detalles.*.VolPed'    => 'nullable|numeric|min:0',
        ];

        if (!$unidad && $esAdmin) {
            $rules += [
                'IdUnidadPedido' => 'required|integer',
                'IdSupervisor'   => 'required|integer',
                'IdAlmacen'      => 'required|integer',
            ];
        }

        $data = $request->validate($rules);
        $this->validarArticulosEnDetalles($data['detalles']);

        if (!$unidad && !$esAdmin) {
            return response()->json(['message' => 'El usuario no tiene unidad operacional asignada.'], 422);
        }

        if (!$unidad && $esAdmin) {
            $this->validarIdExistente('ped.unidadoperacional', 'IdUnidad', (int) $data['IdUnidadPedido'], 'IdUnidadPedido', 'Unidad de pedido no valida.');
            $this->validarIdExistente('ped.unidadoperacional', 'IdUnidad', (int) $data['IdSupervisor'], 'IdSupervisor', 'Supervisor no valido.');
            $this->validarIdExistente('ped.unidadoperacional', 'IdUnidad', (int) $data['IdAlmacen'], 'IdAlmacen', 'Almacen no valido.');
        }

        $detalles = collect($data['detalles'])
            ->map(function (array $det) {
                $peso = DB::table('cat.articulo')->where('IdArticulo', $det['IdArticulo'])->value('Peso');
                $cantidad = (int) $det['CanPzPed'];
                $volumen = array_key_exists('VolPed', $det) && $det['VolPed'] !== null
                    ? (float) $det['VolPed']
                    : round($cantidad * (float) ($peso ?? 0), 3);

                return [
                    'IdArticulo' => (int) $det['IdArticulo'],
                    'CanPzPed'   => $cantidad,
                    'VolPed'     => $volumen,
                ];
            })
            ->values();

        $volumenPedido = round($detalles->sum('VolPed'), 3);
        if ($unidad && $unidad->CapacidadMaxima !== null && $volumenPedido > ((float) $unidad->CapacidadMaxima + 0.001)) {
            return response()->json([
                'message' => 'El pedido excede la capacidad maxima de la unidad.',
            ], 422);
        }

        $pedido = DB::transaction(function () use ($data, $detalles, $unidad, $request, $volumenPedido) {
            $idUsuario = $this->idUsuarioActual($request);
            $idUnidadPedido = $unidad?->IdUnidad ?? (int) $data['IdUnidadPedido'];
            $idSupervisor = $unidad?->ID_SUPERVISOR_UO ?? (int) $data['IdSupervisor'];
            $idAlmacen = $unidad?->ID_ALMACEN_UO ?? (int) $data['IdAlmacen'];
            $fechaPedido = now();

            $pedido = Pedido::create([
                'FolioPedido'    => $this->generarFolio($idUnidadPedido),
                'IdEstado'       => $this->idEstadoCapturado(),
                'IdUnidadPedido' => $idUnidadPedido,
                'IdSupervisor'   => $idSupervisor,
                'IdAlmacen'      => $idAlmacen,
                'Registros'      => $detalles->count(),
                'Dia'            => $this->diaActual(),
                'Semana'         => (int) $fechaPedido->format('W'),
                'FechaPedido'    => $fechaPedido,
                'PedVolPed'      => $volumenPedido,
                'ObserVen'       => $data['ObserVen'] ?? null,
                'activo'         => true,
            ]);

            foreach ($detalles as $i => $det) {
                $pedido->detalles()->create(array_merge($det, ['Registro' => $i + 1]));
            }

            $this->registrarLog($pedido, $pedido->IdEstado, $idUsuario, 'Pedido capturado por vendedor.');

            return $pedido;
        });

        $this->notificarPedidoCapturado($pedido);

        return response()->json([
            'message' => 'Pedido capturado.',
            'data'    => $pedido->load(['estado', 'unidadPedido', 'detalles.articulo']),
        ], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $pedido = Pedido::with([
            'estado', 'unidadPedido', 'supervisor', 'almacen',
            'semana', 'detalles.articulo', 'estadoLog.estado',
        ])->findOrFail($id);

        $this->validarAlcanceConsulta($pedido, $request);

        return response()->json($pedido);
    }

    public function autorizar(Request $request, int $id): JsonResponse
    {
        $pedido = Pedido::with('detalles')->findOrFail($id);
        if ((int) $pedido->IdEstado !== $this->idEstadoCapturado()) {
            return response()->json(['message' => 'Solo se pueden autorizar pedidos capturados.'], 422);
        }

        $this->validarAlcanceAutoriza($pedido, $request);

        $data = $request->validate([
            'detalles' => 'nullable|array',
            'detalles.*.IdArticulo' => 'required_with:detalles|integer',
            'detalles.*.CanPzApr' => 'required_with:detalles|integer|min:0',
            'detalles.*.VolApr' => 'nullable|numeric|min:0',
            'ObserSup' => 'nullable|string|max:200',
        ]);

        if (!empty($data['detalles'])) {
            $this->validarArticulosEnDetalles($data['detalles']);
        }

        DB::transaction(function () use ($pedido, $data, $request) {
            $this->actualizarDetallesEtapa($pedido, $data['detalles'] ?? null, 'Apr');

            $pedido->refresh()->load('detalles');
            $pedido->PedVolApr = round($pedido->detalles->sum(fn ($d) => (float) ($d->VolApr ?? 0)), 3);
            $pedido->FechaAutorizacion = now();
            $pedido->ObserSup = $data['ObserSup'] ?? 'Aprobado por supervisor';
            $pedido->IdEstado = $this->idEstadoAutorizado();
            $pedido->save();

            $this->registrarLog($pedido, $pedido->IdEstado, $this->idUsuarioActual($request), $pedido->ObserSup);
        });

        $this->notificarAlmacen($pedido);

        return response()->json([
            'message' => 'Pedido autorizado.',
            'data' => $pedido->fresh()->load(['estado', 'detalles.articulo']),
        ]);
    }

    public function surtir(Request $request, int $id): JsonResponse
    {
        $pedido = Pedido::with('detalles')->findOrFail($id);
        if ((int) $pedido->IdEstado !== $this->idEstadoAutorizado()) {
            return response()->json(['message' => 'Solo se pueden surtir pedidos autorizados.'], 422);
        }

        $this->validarAlcanceSurtido($pedido, $request);

        $data = $request->validate([
            'detalles' => 'nullable|array',
            'detalles.*.IdArticulo' => 'required_with:detalles|integer',
            'detalles.*.CanPzSur' => 'required_with:detalles|integer|min:0',
            'detalles.*.VolSur' => 'nullable|numeric|min:0',
            'ObserAlm' => 'nullable|string|max:200',
        ]);

        if (!empty($data['detalles'])) {
            $this->validarArticulosEnDetalles($data['detalles']);
        }

        DB::transaction(function () use ($pedido, $data, $request) {
            $this->actualizarDetallesEtapa($pedido, $data['detalles'] ?? null, 'Sur');

            $pedido->refresh()->load('detalles');
            $pedido->PedVolSur = round($pedido->detalles->sum(fn ($d) => (float) ($d->VolSur ?? 0)), 3);
            $pedido->FechaSurtido = now();
            $pedido->ObserAlm = $data['ObserAlm'] ?? null;
            $pedido->IdEstado = $this->idEstadoSurtido();
            $pedido->save();

            $this->registrarLog($pedido, $pedido->IdEstado, $this->idUsuarioActual($request), 'Pedido surtido por almacen.');
        });

        $this->notificarSolicitante($pedido, 'Pedido surtido', "Tu pedido {$pedido->FolioPedido} fue surtido.");

        return response()->json([
            'message' => 'Pedido surtido.',
            'data' => $pedido->fresh()->load(['estado', 'detalles.articulo']),
        ]);
    }

    public function cancelar(Request $request, int $id): JsonResponse
    {
        $pedido = Pedido::findOrFail($id);
        $this->validarAlcanceAutoriza($pedido, $request);

        $data = $request->validate(['Notas' => 'nullable|string|max:500']);

        $pedido->IdEstado = $this->idEstadoCancelado();
        $pedido->activo = false;
        $pedido->save();

        $this->registrarLog($pedido, $pedido->IdEstado, $this->idUsuarioActual($request), $data['Notas'] ?? 'Pedido cancelado.');
        $this->notificarSolicitante($pedido, 'Pedido cancelado', $data['Notas'] ?? "Tu pedido {$pedido->FolioPedido} fue cancelado.");

        return response()->json([
            'message' => 'Pedido cancelado.',
            'data' => $pedido->load('estado'),
        ]);
    }

    public function cambiarEstado(Request $request, int $id): JsonResponse
    {
        $pedido = Pedido::findOrFail($id);

        $data = $request->validate([
            'IdEstado'  => 'nullable|integer',
            'Estado'    => 'nullable|string',
            'Notas'     => 'nullable|string|max:500',
            'Comentario'=> 'nullable|string|max:500',
            'CambioPor' => 'nullable|integer',
        ]);

        if (!empty($data['IdEstado'])) {
            $idEstado = $data['IdEstado'];
            $this->validarIdExistente('ped.estado_pedido', 'IdEstado', (int) $idEstado, 'IdEstado', 'Estado no valido.');
            $nombreEstado = DB::table('ped.estado_pedido')->where('IdEstado', $idEstado)->value('Nombre') ?? '';
        } else {
            $nombreEstado = $data['Estado'] ?? '';
            $idEstado = $this->idEstadoPorNombre($nombreEstado);
            if (!$idEstado) {
                return response()->json(['message' => 'Estado no valido.'], 422);
            }
        }

        if (!empty($data['CambioPor'])) {
            $this->validarIdExistente('core.usuario', 'ID_Usuario', (int) $data['CambioPor'], 'CambioPor', 'Usuario de cambio no valido.');
        }

        $pedido->IdEstado = $idEstado;
        $data['Notas'] = $data['Notas'] ?? $data['Comentario'] ?? null;
        if (str_contains($this->normalizar((string) $nombreEstado), 'AUTORIZ')) {
            $pedido->FechaAutorizacion = now();
        } elseif (str_contains($this->normalizar((string) $nombreEstado), 'SURTID')) {
            $pedido->FechaSurtido = now();
        }

        $pedido->save();
        $this->registrarLog($pedido, $idEstado, $data['CambioPor'] ?? $this->idUsuarioActual($request), $data['Notas']);
        $this->notificarSolicitante($pedido, "Pedido actualizado", $data['Notas'] ?? "Tu pedido {$pedido->FolioPedido} cambio de estado.");

        return response()->json([
            'message' => 'Estado actualizado.',
            'data'    => $pedido->load('estado'),
        ]);
    }

    private function basePedidoQuery()
    {
        return Pedido::with(['estado', 'unidadPedido', 'supervisor', 'almacen', 'semana'])
            ->where('activo', true);
    }

    private function perPage(Request $request): int
    {
        return min(200, max(1, $request->integer('per_page', 50)));
    }

    private function idUsuarioActual(Request $request): ?int
    {
        return $request->user()?->ID_Usuario
            ?? $request->user()?->id
            ?? ($request->header('X-Usuario-ID') ? (int) $request->header('X-Usuario-ID') : null);
    }

    private function unidadDelUsuario(?int $idUsuario): ?object
    {
        if (!$idUsuario) {
            return null;
        }

        return DB::table('ped.unidadoperacional as u')
            ->join('ped.tipounidad as t', 't.IdTipoUnidad', '=', 'u.IdTipoUnidad')
            ->leftJoin('ped.capacidaduv as c', 'c.IdCapacidadUV', '=', 'u.IdCapacidadUV')
            ->leftJoin('ped.unidadoperacional as s', 's.IdUnidad', '=', 'u.IdSupervisor')
            ->leftJoin('ped.unidadoperacional as a', function ($join) {
                $join->on('a.IdSucursal', '=', 'u.IdSucursal')
                    ->where('a.activo', 1)
                    ->whereExists(function ($q) {
                        $q->selectRaw('1')
                            ->from('ped.tipounidad as ta')
                            ->whereColumn('ta.IdTipoUnidad', 'a.IdTipoUnidad')
                            ->whereRaw("UPPER(ta.Nombre) LIKE '%ALMACEN%'");
                    });
            })
            ->where('u.IdUsuario', $idUsuario)
            ->where('u.activo', 1)
            ->select(
                'u.IdUnidad',
                'u.Nombre',
                'u.IdUsuario',
                'u.IdSupervisor as ID_SUPERVISOR_UO',
                'u.IdSucursal',
                'u.IdCapacidadUV as ID_CAPUV',
                't.Nombre as TipoNombre',
                'c.Nombre as CapacidadNombre',
                'c.CapacidadMaxima',
                'c.CapacidadMinima',
                's.Nombre as Supervisor',
                'a.IdUnidad as ID_ALMACEN_UO',
                'a.Nombre as Almacen',
            )
            ->orderByRaw("
                CASE
                    WHEN a.IdSupervisor = COALESCE(s.IdSupervisor, u.IdSupervisor) THEN 0
                    WHEN a.IdUsuario IS NOT NULL THEN 1
                    ELSE 2
                END
            ")
            ->first();
    }

    private function aplicarAlcanceAutoriza($query, Request $request): void
    {
        if ($this->esAdmin($request)) {
            return;
        }

        $unidad = $this->unidadDelUsuario($this->idUsuarioActual($request));
        if (!$unidad) {
            $query->whereRaw('1 = 0');
            return;
        }

        $tipo = $this->normalizar($unidad->TipoNombre);
        if (str_contains($tipo, 'GERENTE')) {
            $query->whereHas('unidadPedido', fn ($q) => $q->where('IdSucursal', $unidad->IdSucursal));
            return;
        }

        $query->where('IdSupervisor', $unidad->IdUnidad);
    }

    private function aplicarAlcanceSurtido($query, Request $request): void
    {
        if ($this->esAdmin($request)) {
            return;
        }

        $unidad = $this->unidadDelUsuario($this->idUsuarioActual($request));
        if (!$unidad) {
            $query->whereRaw('1 = 0');
            return;
        }

        $query->where('IdAlmacen', $unidad->IdUnidad);
    }

    private function aplicarAlcanceConsulta($query, Request $request): void
    {
        if ($this->esAdmin($request)) {
            return;
        }

        $unidad = $this->unidadDelUsuario($this->idUsuarioActual($request));
        if (!$unidad) {
            $query->whereRaw('1 = 0');
            return;
        }

        $tipo = $this->normalizar($unidad->TipoNombre);
        if (str_contains($tipo, 'GERENTE')) {
            $query->whereHas('unidadPedido', fn ($q) => $q->where('IdSucursal', $unidad->IdSucursal));
            return;
        }

        if (str_contains($tipo, 'ALMACEN')) {
            $query->where('IdAlmacen', $unidad->IdUnidad);
            return;
        }

        if (str_contains($tipo, 'SUPERVISOR')) {
            $query->where('IdSupervisor', $unidad->IdUnidad);
            return;
        }

        $query->where('IdUnidadPedido', $unidad->IdUnidad);
    }

    private function validarAlcanceConsulta(Pedido $pedido, Request $request): void
    {
        if ($this->esAdmin($request)) {
            return;
        }

        $unidad = $this->unidadDelUsuario($this->idUsuarioActual($request));
        abort_if(!$unidad, 403, 'Sin unidad operacional.');

        $tipo = $this->normalizar($unidad->TipoNombre);
        if (str_contains($tipo, 'GERENTE')) {
            $idSucursalPedido = DB::table('ped.unidadoperacional')->where('IdUnidad', $pedido->IdUnidadPedido)->value('IdSucursal');
            abort_if((int) $idSucursalPedido !== (int) $unidad->IdSucursal, 403, 'No puedes ver pedidos de otra sucursal.');
            return;
        }

        if (str_contains($tipo, 'ALMACEN')) {
            abort_if((int) $pedido->IdAlmacen !== (int) $unidad->IdUnidad, 403, 'No puedes ver pedidos de otro almacen.');
            return;
        }

        if (str_contains($tipo, 'SUPERVISOR')) {
            abort_if((int) $pedido->IdSupervisor !== (int) $unidad->IdUnidad, 403, 'No puedes ver pedidos de otra supervision.');
            return;
        }

        abort_if((int) $pedido->IdUnidadPedido !== (int) $unidad->IdUnidad, 403, 'No puedes ver pedidos de otra unidad.');
    }

    private function validarAlcanceAutoriza(Pedido $pedido, Request $request): void
    {
        if ($this->esAdmin($request)) {
            return;
        }

        $unidad = $this->unidadDelUsuario($this->idUsuarioActual($request));
        abort_if(!$unidad, 403, 'Sin unidad operacional.');

        $tipo = $this->normalizar($unidad->TipoNombre);
        if (str_contains($tipo, 'GERENTE')) {
            $idSucursalPedido = DB::table('ped.unidadoperacional')->where('IdUnidad', $pedido->IdUnidadPedido)->value('IdSucursal');
            abort_if((int) $idSucursalPedido !== (int) $unidad->IdSucursal, 403, 'No puedes autorizar pedidos de otra sucursal.');
            return;
        }

        abort_if((int) $pedido->IdSupervisor !== (int) $unidad->IdUnidad, 403, 'No puedes autorizar pedidos de otra supervision.');
    }

    private function validarAlcanceSurtido(Pedido $pedido, Request $request): void
    {
        if ($this->esAdmin($request)) {
            return;
        }

        $unidad = $this->unidadDelUsuario($this->idUsuarioActual($request));
        abort_if(!$unidad || (int) $pedido->IdAlmacen !== (int) $unidad->IdUnidad, 403, 'No puedes surtir pedidos de otro almacen.');
    }

    private function actualizarDetallesEtapa(Pedido $pedido, ?array $detalles, string $etapa): void
    {
        $campoCantidad = "CanPz{$etapa}";
        $campoVolumen = "Vol{$etapa}";

        if (!$detalles) {
            foreach ($pedido->detalles as $detalle) {
                $detalle->{$campoCantidad} = $detalle->{$etapa === 'Apr' ? 'CanPzPed' : 'CanPzApr'} ?? $detalle->CanPzPed;
                $detalle->{$campoVolumen} = $detalle->{$etapa === 'Apr' ? 'VolPed' : 'VolApr'} ?? $detalle->VolPed;
                $detalle->save();
            }
            return;
        }

        $porArticulo = collect($detalles)->keyBy('IdArticulo');
        foreach ($pedido->detalles as $detalle) {
            $entrada = $porArticulo->get($detalle->IdArticulo);
            if (!$entrada) {
                continue;
            }

            $detalle->{$campoCantidad} = (int) $entrada[$campoCantidad];
            $detalle->{$campoVolumen} = array_key_exists($campoVolumen, $entrada) && $entrada[$campoVolumen] !== null
                ? (float) $entrada[$campoVolumen]
                : $this->volumenPorCantidad((int) $detalle->IdArticulo, (int) $entrada[$campoCantidad]);
            $detalle->save();
        }
    }

    private function volumenPorCantidad(int $idArticulo, int $cantidad): float
    {
        $peso = DB::table('cat.articulo')->where('IdArticulo', $idArticulo)->value('Peso');
        return round($cantidad * (float) ($peso ?? 0), 3);
    }

    private function validarArticulosEnDetalles(array $detalles): void
    {
        $ids = collect($detalles)
            ->pluck('IdArticulo')
            ->filter(fn ($id) => $id !== null)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return;
        }

        $existentes = DB::table('cat.articulo')
            ->whereIn('IdArticulo', $ids)
            ->pluck('IdArticulo')
            ->map(fn ($id) => (int) $id);

        $faltantes = $ids->diff($existentes)->values();
        if ($faltantes->isNotEmpty()) {
            throw ValidationException::withMessages([
                'detalles' => ['Articulo no valido: ' . $faltantes->implode(', ')],
            ]);
        }
    }

    private function validarIdExistente(string $tabla, string $columna, int $id, string $campo, string $mensaje): void
    {
        $existe = DB::table($tabla)->where($columna, $id)->exists();
        if (!$existe) {
            throw ValidationException::withMessages([
                $campo => [$mensaje],
            ]);
        }
    }

    private function registrarLog(Pedido $pedido, int $idEstado, ?int $idUsuario, ?string $notas = null): void
    {
        DB::table('ped.pedido_estado_log')->insert([
            'IdPedido'   => $pedido->IdPedido,
            'IdEstado'   => $idEstado,
            'CambioPor'  => $idUsuario,
            'Notas'      => $notas,
            'created_at' => now(),
        ]);
    }

    private function generarFolio(int $idUnidad): string
    {
        $prefijo = DB::table('ped.unidadoperacional as u')
            ->leftJoin('core.sucursal as s', 's.ID_Sucursal', '=', 'u.IdSucursal')
            ->where('u.IdUnidad', $idUnidad)
            ->value('s.Nombre');

        $prefijo = substr(preg_replace('/[^A-Za-z0-9]/', '', (string) $prefijo), 0, 3) ?: 'GEN';
        $fecha = now()->format('Ymd');
        $inicio = now()->startOfDay();
        $fin = now()->endOfDay();
        $consecutivo = Pedido::whereBetween('FechaPedido', [$inicio, $fin])->count() + 1;

        return strtoupper($prefijo) . '-PED-' . $fecha . '-' . str_pad((string) $consecutivo, 4, '0', STR_PAD_LEFT);
    }

    private function diaActual(): string
    {
        return [
            1 => 'lu',
            2 => 'ma',
            3 => 'mi',
            4 => 'ju',
            5 => 'vi',
            6 => 'sa',
            7 => 'sa',
        ][(int) now()->format('N')];
    }

    private function idEstadoCapturado(): int
    {
        return $this->idEstadoPorNombre('CAPTURADO')
            ?? $this->idEstadoPorNombre('PENDIENTE')
            ?? 1;
    }

    private function idEstadoAutorizado(): int
    {
        return $this->idEstadoPorNombre('AUTORIZADO') ?? 2;
    }

    private function idEstadoSurtido(): int
    {
        return $this->idEstadoPorNombre('SURTIDO') ?? 3;
    }

    private function idEstadoCancelado(): int
    {
        return $this->idEstadoPorNombre('CANCELADO') ?? 4;
    }

    private function idEstadoPorNombre(string $nombre): ?int
    {
        $normalizado = $this->normalizar($nombre);

        return DB::table('ped.estado_pedido')
            ->whereRaw('UPPER(Nombre) = ?', [$normalizado])
            ->orWhereRaw('UPPER(Nombre) = ?', [str_replace('_', ' ', $normalizado)])
            ->value('IdEstado');
    }

    private function esAdmin(Request $request): bool
    {
        $rol = $this->normalizar((string) ($request->user()?->rol?->Nombre ?? ''));
        return str_contains($rol, 'ADMIN');
    }

    private function normalizar(?string $valor): string
    {
        $valor = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', (string) $valor) ?: (string) $valor;
        return strtoupper(trim($valor));
    }

    private function notificarPedidoCapturado(Pedido $pedido): void
    {
        $ids = DB::table('ped.unidadoperacional')
            ->whereIn('IdUnidad', [$pedido->IdSupervisor])
            ->whereNotNull('IdUsuario')
            ->pluck('IdUsuario')
            ->unique()
            ->toArray();

        if ($ids) {
            Notificacion::enviarAVarios(
                $ids,
                'PEDIDO',
                'PED',
                "Pedido por autorizar: {$pedido->FolioPedido}",
                'Nuevo pedido capturado por ventas.',
                $pedido->IdPedido,
            );
        }
    }

    private function notificarAlmacen(Pedido $pedido): void
    {
        $idUsuario = DB::table('ped.unidadoperacional')->where('IdUnidad', $pedido->IdAlmacen)->value('IdUsuario');
        if ($idUsuario) {
            Notificacion::enviar(
                (int) $idUsuario,
                'PEDIDO',
                'PED',
                "Pedido autorizado: {$pedido->FolioPedido}",
                'Pedido listo para surtir.',
                $pedido->IdPedido,
            );
        }
    }

    private function notificarSolicitante(Pedido $pedido, string $titulo, ?string $mensaje = null): void
    {
        $idUsuario = DB::table('ped.unidadoperacional')->where('IdUnidad', $pedido->IdUnidadPedido)->value('IdUsuario');
        if ($idUsuario) {
            Notificacion::enviar((int) $idUsuario, 'PEDIDO', 'PED', $titulo, $mensaje, $pedido->IdPedido);
        }
    }
}
