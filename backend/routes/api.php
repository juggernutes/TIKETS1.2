<?php

use App\Http\Controllers\Com\AjusteComisionController;
use App\Http\Controllers\Com\CalculoComisionController;
use App\Http\Controllers\Com\ComisionBaseController;
use App\Http\Controllers\Com\CorridaComisionController;
use App\Http\Controllers\Com\MetaMensualController;
use App\Http\Controllers\Com\ReglaComisionController;
use App\Http\Controllers\Com\ResultadoDocController;
use App\Http\Controllers\Com\ResultadoIndicadorController;
use App\Http\Controllers\Com\SemanaController;
use App\Http\Controllers\Core\AdjuntoController;
use App\Http\Controllers\Core\AuthController;
use App\Http\Controllers\Core\EmpleadoController;
use App\Http\Controllers\Core\NotificacionController;
use App\Http\Controllers\Core\ParametroController;
use App\Http\Controllers\Core\UsuarioController;
use App\Http\Controllers\Hd\ComentarioController;
use App\Http\Controllers\Hd\EncuestaController;
use App\Http\Controllers\Hd\SlaController;
use App\Http\Controllers\Hd\TicketController;
use App\Http\Controllers\Ped\PedidoController;
use App\Http\Controllers\Rh\CandidatoController;
use App\Http\Controllers\Rh\EntrevistaController;
use App\Http\Controllers\Rh\OfertaLaboralController;
use App\Http\Controllers\Rh\VacanteController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| PortalV2 API
|--------------------------------------------------------------------------
*/

Route::get('/health', fn () => response()->json([
    'status'    => 'ok',
    'service'   => 'PortalV2-backend',
    'timestamp' => now()->toIso8601String(),
]));

// ── AUTH ──────────────────────────────────────────────────────────────────
Route::prefix('auth')->group(function () {
    Route::post('/login',            [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout',           [AuthController::class, 'logout']);
        Route::get('/me',                [AuthController::class, 'me']);
        Route::post('/cambiar-password', [AuthController::class, 'cambiarPassword']);
    });
});

// ── CATÁLOGOS (GET público para facilitar dropdowns en UI) ────────────────
Route::prefix('catalogos')->group(function () {
    Route::get('/estatus-hd',           fn () => response()->json(DB::table('hd.estatus')->orderBy('Orden')->get()));
    Route::get('/tipo-error',           fn () => response()->json(DB::table('hd.tipo_error')->where('Activo', 1)->orderBy('Nombre')->get()));
    Route::get('/sistemas',             fn () => response()->json(DB::table('cat.sistema')->where('Activo', 1)->get()));
    Route::get('/areas',                fn () => response()->json(DB::table('core.area')->where('Activo', 1)->orderBy('Nombre')->get()));
    Route::get('/sucursales',           fn () => response()->json(DB::table('core.sucursal')->where('Activo', 1)->orderBy('Nombre')->get()));
    Route::get('/puestos',              fn () => response()->json(DB::table('core.puesto')->where('Activo', 1)->orderBy('Descripcion')->get()));
    Route::get('/fuentes-reclutamiento',fn () => response()->json(DB::table('rh.fuente_reclutamiento')->where('Activo', 1)->orderBy('Nombre')->get()));
    Route::get('/estatus-candidato',    fn () => response()->json(DB::table('rh.estatus_candidato')->where('Activo', 1)->orderBy('OrdenProceso')->get()));
    Route::get('/semanas',              fn () => response()->json(DB::table('com.semana')->orderByDesc('Anio')->orderByDesc('Semana')->select('ID_Semana','Semana','Anio', DB::raw("CONCAT('Sem ', Semana, '/', Anio) as label"))->get()));
    Route::get('/soluciones-hd',        fn () => response()->json(DB::table('hd.solucion')->where('Activo', 1)->get()));
    Route::get('/errores-hd',           fn () => response()->json(DB::table('hd.error as e')->join('hd.tipo_error as t','t.ID_TipoError','=','e.Tipo')->where('e.Activo',1)->select('e.ID_Error','e.Descripcion','t.Nombre as Tipo')->get()));
    Route::get('/proveedores',          fn () => response()->json(DB::table('core.proveedor')->where('Activo', 1)->orderBy('Nombre')->get()));
    Route::get('/indicadores',          fn () => response()->json(DB::table('com.indicador')->where('Activo', 1)->get()));
    Route::get('/sub-indicadores',      fn () => response()->json(DB::table('com.sub_indicador')->where('Activo', 1)->orderBy('ID_Indicador')->orderBy('Orden')->get()));
    Route::get('/articulos',            fn () => response()->json(DB::table('cat.articulo')->where('Activo', 1)->orderBy('Nombre')->get()));
    Route::get('/usuarios',             fn () => response()->json(
        DB::table('core.usuario as u')
            ->leftJoin('core.rol as r', 'r.ID_Rol', '=', 'u.ID_Rol')
            ->where('u.Activo', 1)
            ->orderBy('u.Nombre')
            ->select('u.ID_Usuario', 'u.Nombre', 'u.Email', 'u.ID_Area', 'r.Nombre as Rol')
            ->get()
    ));
    Route::get('/empleados',            function (\Illuminate\Http\Request $request) {
        $q = DB::table('core.empleado as e')
            ->leftJoin('core.area as a', 'a.ID_Area', '=', 'e.ID_Area')
            ->where('e.Activo', 1)
            ->select('e.Numero_Empleado', 'e.Nombre', 'e.Correo', 'e.Extension', 'e.ID_Area', 'a.Nombre as Area');
        if ($request->filled('q')) {
            $buscar = '%' . $request->string('q') . '%';
            $q->where(function ($sub) use ($buscar) {
                $sub->where('e.Nombre', 'like', $buscar)
                    ->orWhere('e.Correo', 'like', $buscar)
                    ->orWhere(DB::raw('CAST(e.Numero_Empleado AS varchar)'), 'like', $buscar);
            });
        }
        if ($request->filled('id_area')) {
            $q->where('e.ID_Area', $request->integer('id_area'));
        }
        $limite = $request->filled('id_area') && !$request->filled('q') ? 500 : 30;
        return response()->json($q->orderBy('e.Nombre')->limit($limite)->get());
    });
});

// ── RUTAS PROTEGIDAS ──────────────────────────────────────────────────────
// RH publico: bolsa de trabajo y postulaciones externas
Route::prefix('public/rh')->group(function () {
    Route::get('/vacantes', [VacanteController::class, 'publicIndex']);
    Route::get('/vacantes/{id}', [VacanteController::class, 'publicShow']);
    Route::post('/vacantes/{id}/postulaciones', [CandidatoController::class, 'postularPublico']);
});

Route::middleware('auth:sanctum')->group(function () {

    // ── CORE ─────────────────────────────────────────────────────────────
    Route::prefix('core')->group(function () {
        Route::get('/empleados',                    [EmpleadoController::class, 'index']);
        Route::get('/empleados/{numeroEmpleado}',   [EmpleadoController::class, 'show']);
        Route::get('/empleados/{numeroEmpleado}/equipos', [EmpleadoController::class, 'equipos']);

        Route::get('/usuarios',                     [UsuarioController::class, 'index']);
        Route::post('/usuarios/{idUsuario}/reset-password', [UsuarioController::class, 'resetPassword']);
    });

    // ── HELP DESK ────────────────────────────────────────────────────────
    Route::prefix('hd')->group(function () {
        Route::get('/tickets',                           [TicketController::class, 'index']);
        Route::post('/tickets',                          [TicketController::class, 'store']);
        Route::get('/tickets/{id}',                      [TicketController::class, 'show']);
        Route::patch('/tickets/{id}/estatus',            [TicketController::class, 'actualizarEstatus']);
        Route::patch('/tickets/{id}/agente',             [TicketController::class, 'asignarAgente']);
        Route::patch('/tickets/{id}/proveedor',          [TicketController::class, 'enviarProveedor']);
        Route::get('/tickets/{ticketId}/comentarios',    [ComentarioController::class, 'index']);
        Route::post('/tickets/{ticketId}/comentarios',   [ComentarioController::class, 'store']);
        Route::post('/tickets/{id}/encuesta',            [EncuestaController::class, 'store']);
        Route::get('/tickets/{id}/encuesta',             [EncuestaController::class, 'show']);
        Route::get('/encuestas/resumen',                 [EncuestaController::class, 'resumen']);

        Route::get('/sla',                               [SlaController::class, 'index']);
        Route::post('/sla',                              [SlaController::class, 'store']);
        Route::patch('/sla/{id}',                        [SlaController::class, 'update']);
        Route::delete('/sla/{id}',                       [SlaController::class, 'destroy']);
    });

    // ── RH ───────────────────────────────────────────────────────────────
    Route::prefix('rh')->group(function () {
        Route::get('/vacantes',                       [VacanteController::class, 'index']);
        Route::post('/vacantes',                      [VacanteController::class, 'store']);
        Route::get('/vacantes/{id}',                  [VacanteController::class, 'show']);
        Route::patch('/vacantes/{id}',                [VacanteController::class, 'update']);
        Route::patch('/vacantes/{id}/estatus',        [VacanteController::class, 'cambiarEstatus']);

        Route::get('/candidatos',                     [CandidatoController::class, 'index']);
        Route::post('/candidatos',                    [CandidatoController::class, 'store']);
        Route::get('/candidatos/{id}',                [CandidatoController::class, 'show']);
        Route::get('/candidatos/{id}/cv',             [CandidatoController::class, 'descargarCv']);
        Route::patch('/candidatos/{id}/estatus',      [CandidatoController::class, 'cambiarEstatus']);

        Route::post('/entrevistas',                   [EntrevistaController::class, 'store']);
        Route::patch('/entrevistas/{id}/resultado',   [EntrevistaController::class, 'registrarResultado']);
        Route::post('/entrevistas/{id}/evaluadores',  [EntrevistaController::class, 'agregarEvaluador']);

        Route::post('/ofertas',                       [OfertaLaboralController::class, 'store']);
        Route::patch('/ofertas/{id}/respuesta',       [OfertaLaboralController::class, 'responder']);
    });

    // ── PEDIDOS ──────────────────────────────────────────────────────────
    Route::prefix('ped')->group(function () {
        Route::get('/mi-unidad',                      [PedidoController::class, 'miUnidad'])->middleware('permiso:ped.pedidos.crear');
        Route::get('/pedidos/por-autorizar',          [PedidoController::class, 'porAutorizar'])->middleware('permiso:ped.pedidos.ver_por_autorizar');
        Route::get('/pedidos/por-surtir',             [PedidoController::class, 'porSurtir'])->middleware('permiso:ped.pedidos.ver_por_surtir');
        Route::get('/pedidos',                        [PedidoController::class, 'index'])->middleware('permiso:ped.pedidos.ver');
        Route::post('/pedidos',                       [PedidoController::class, 'store'])->middleware('permiso:ped.pedidos.crear');
        Route::get('/pedidos/{id}',                   [PedidoController::class, 'show'])->middleware('permiso:ped.pedidos.ver');
        Route::post('/pedidos/{id}/autorizar',        [PedidoController::class, 'autorizar'])->middleware('permiso:ped.pedidos.autorizar');
        Route::post('/pedidos/{id}/surtir',           [PedidoController::class, 'surtir'])->middleware('permiso:ped.pedidos.surtir');
        Route::post('/pedidos/{id}/cancelar',         [PedidoController::class, 'cancelar'])->middleware('permiso:ped.pedidos.cancelar');
        Route::patch('/pedidos/{id}/estado',          [PedidoController::class, 'cambiarEstado'])->middleware('permiso:ped.unidades.admin');

        Route::get('/unidades', fn () => response()->json(
            DB::table('ped.unidadoperacional as u')
                ->join('ped.tipounidad as t', 't.IdTipoUnidad', '=', 'u.IdTipoUnidad')
                ->where('u.activo', 1)
                ->select('u.IdUnidad', 'u.Nombre', 'u.IdTipoUnidad', 't.Nombre as TipoNombre')
                ->orderBy('t.Nombre')->orderBy('u.Nombre')
                ->get()
        ))->middleware('permiso:ped.unidades.admin');
        Route::get('/estados', fn () => response()->json(DB::table('ped.estado_pedido')->orderBy('Orden')->get()))->middleware('permiso:ped.catalogos.ver');
    });

    // ── COMISIONES ───────────────────────────────────────────────────────
    Route::prefix('com')->group(function () {

        // Reglas de comisión (Admin)
        Route::get('/reglas',                                    [ReglaComisionController::class, 'index']);
        Route::post('/reglas',                                   [ReglaComisionController::class, 'store']);
        Route::get('/reglas/{id}',                               [ReglaComisionController::class, 'show']);
        Route::patch('/reglas/{id}',                             [ReglaComisionController::class, 'update']);
        Route::delete('/reglas/{id}',                            [ReglaComisionController::class, 'destroy']);
        Route::post('/reglas/bulk',                              [ReglaComisionController::class, 'storeBulk']);

        // Corridas (Admin)
        Route::get('/corridas',                                  [CorridaComisionController::class, 'index']);
        Route::post('/corridas',                                 [CorridaComisionController::class, 'store']);
        Route::get('/corridas/{id}',                             [CorridaComisionController::class, 'show']);
        Route::patch('/corridas/{id}/estatus',                   [CorridaComisionController::class, 'cambiarEstatus']);
        Route::post('/corridas/{id}/calcular',                   [CorridaComisionController::class, 'calcular']);

        // Metas mensuales (Gerentes de Sucursal)
        Route::get('/metas/portadas',                            [MetaMensualController::class, 'indexPortadas']);
        Route::post('/metas/portadas',                           [MetaMensualController::class, 'storePortada']);
        Route::get('/metas/portadas/{id}',                       [MetaMensualController::class, 'showPortada']);
        Route::patch('/metas/portadas/{id}',                     [MetaMensualController::class, 'updatePortada']);
        Route::post('/metas/portadas/{id}/contenido',            [MetaMensualController::class, 'storeMetas']);
        Route::post('/metas/portadas/{id}/calcular-semanales',   [MetaMensualController::class, 'calcularMetasSemanales']);

        // Asignación empleado-ruta (Generalistas)
        Route::get('/base-empleados',                            [ComisionBaseController::class, 'index']);
        Route::get('/base-calculo',                              [ComisionBaseController::class, 'baseCalculo']);
        Route::post('/base-empleados',                           [ComisionBaseController::class, 'store']);
        Route::post('/base-empleados/bulk',                      [ComisionBaseController::class, 'storeBulk']);
        Route::delete('/base-empleados/{id}',                    [ComisionBaseController::class, 'destroy']);

        // Resultados DOC (Gerentes de Sucursal — checklist)
        Route::get('/resultado-doc',                             [ResultadoDocController::class, 'index']);
        Route::post('/resultado-doc/base/{idBase}',              [ResultadoDocController::class, 'storePorBase']);

        // Resultados indicadores: ventas/cobertura (Admin) y devoluciones CxC (DF1/DAU)
        Route::get('/resultado-indicador',                       [ResultadoIndicadorController::class, 'index']);
        Route::post('/resultado-indicador/base/{idBase}',        [ResultadoIndicadorController::class, 'store']);
        Route::post('/resultado-indicador/base/{idBase}/dev',    [ResultadoIndicadorController::class, 'storeDevolucion']);
        Route::post('/resultado-indicador/bulk',                 [ResultadoIndicadorController::class, 'storeBulk']);

        // Ajustes manuales SR1/SR2/DESCUENTO/AGREGADO (CxC + Admin)
        Route::get('/ajustes',                                   [AjusteComisionController::class, 'index']);
        Route::post('/ajustes',                                  [AjusteComisionController::class, 'store']);
        Route::post('/ajustes/base/{idBase}',                   [AjusteComisionController::class, 'storePorBase']);
        Route::delete('/ajustes/{id}',                          [AjusteComisionController::class, 'destroy']);

        // Semanas (Admin)
        Route::get('/semanas',                                   [SemanaController::class, 'index']);
        Route::post('/semanas',                                  [SemanaController::class, 'store']);
        Route::get('/semanas/{id}',                              [SemanaController::class, 'show']);
        Route::patch('/semanas/{id}',                            [SemanaController::class, 'update']);
        Route::get('/semanas/{id}/resumen',                      [SemanaController::class, 'resumen']);

        // Cálculo final
        Route::get('/calculos',                                  [CalculoComisionController::class, 'index']);
        Route::get('/calculos/{id}',                             [CalculoComisionController::class, 'show']);
        Route::patch('/calculos/{id}/estatus',                   [CalculoComisionController::class, 'cambiarEstatus']);
        Route::patch('/calculos/{id}/aprobar',                   [CalculoComisionController::class, 'aprobar']);
        Route::get('/corridas/{idCorrida}/resumen',              [CalculoComisionController::class, 'resumenCorrida']);
    });

    // ── ADJUNTOS (cross-módulo) ──────────────────────────────────────────
    Route::prefix('adjuntos')->group(function () {
        Route::get('/',              [AdjuntoController::class, 'index']);
        Route::post('/',             [AdjuntoController::class, 'store']);
        Route::delete('/{id}',       [AdjuntoController::class, 'destroy']);
        Route::get('/{id}/download', [AdjuntoController::class, 'download']);
    });

    // ── PARÁMETROS ───────────────────────────────────────────────────────
    Route::prefix('parametros')->group(function () {
        Route::get('/',              [ParametroController::class, 'index']);
        Route::get('/{clave}',       [ParametroController::class, 'show']);
        Route::patch('/{clave}',     [ParametroController::class, 'update']);
        Route::get('/{clave}/valor', [ParametroController::class, 'valor']);
    });

    // ── NOTIFICACIONES ───────────────────────────────────────────────────
    Route::prefix('notificaciones')->group(function () {
        Route::get('/',                      [NotificacionController::class, 'index']);
        Route::patch('/{id}/leida',          [NotificacionController::class, 'marcarLeida']);
        Route::post('/marcar-todas-leidas',  [NotificacionController::class, 'marcarTodasLeidas']);
        Route::delete('/{id}',               [NotificacionController::class, 'destroy']);
    });

}); // fin middleware auth:sanctum
