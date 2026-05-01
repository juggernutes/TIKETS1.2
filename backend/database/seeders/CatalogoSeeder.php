<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CatalogoSeeder extends Seeder
{
    public function run(): void
    {
        // cat.sistema (sistemas que se pueden reportar en HD)
        $idAreaTi = DB::table('core.area')->where('Nombre', 'TI')->value('ID_Area')
                 ?? DB::table('core.area')->first()?->ID_Area;

        DB::table('cat.sistema')->insert([
            ['ID_Area' => $idAreaTi, 'Nombre' => 'PortalV2',          'Descripcion' => 'Portal interno v2',              'Activo' => 1],
            ['ID_Area' => $idAreaTi, 'Nombre' => 'ERP',               'Descripcion' => 'Sistema ERP principal',          'Activo' => 1],
            ['ID_Area' => $idAreaTi, 'Nombre' => 'Correo',            'Descripcion' => 'Correo corporativo',             'Activo' => 1],
            ['ID_Area' => $idAreaTi, 'Nombre' => 'Impresoras',        'Descripcion' => 'Impresoras y periféricos',       'Activo' => 1],
            ['ID_Area' => $idAreaTi, 'Nombre' => 'Red / VPN',         'Descripcion' => 'Conectividad y acceso remoto',   'Activo' => 1],
            ['ID_Area' => $idAreaTi, 'Nombre' => 'Computadora',       'Descripcion' => 'Equipo de cómputo',              'Activo' => 1],
            ['ID_Area' => $idAreaTi, 'Nombre' => 'Módulo Comisiones', 'Descripcion' => 'Módulo COM del portal',          'Activo' => 1],
            ['ID_Area' => $idAreaTi, 'Nombre' => 'Módulo Pedidos',    'Descripcion' => 'Módulo PED del portal',          'Activo' => 1],
            ['ID_Area' => $idAreaTi, 'Nombre' => 'Otro',              'Descripcion' => 'Sistema no catalogado',          'Activo' => 1],
        ]);

        // hd.tipo_error
        DB::table('hd.tipo_error')->insert([
            ['Nombre' => 'Error de sistema',    'Descripcion' => 'Fallo en aplicación o módulo', 'Activo' => 1],
            ['Nombre' => 'Error de usuario',    'Descripcion' => 'Operación incorrecta del usuario', 'Activo' => 1],
            ['Nombre' => 'Error de red',        'Descripcion' => 'Conectividad o VPN', 'Activo' => 1],
            ['Nombre' => 'Error de hardware',   'Descripcion' => 'Equipo físico dañado', 'Activo' => 1],
            ['Nombre' => 'Error de datos',      'Descripcion' => 'Datos incorrectos o corruptos', 'Activo' => 1],
            ['Nombre' => 'Solicitud de acceso', 'Descripcion' => 'Alta de usuario o permiso', 'Activo' => 1],
            ['Nombre' => 'Instalación',         'Descripcion' => 'Instalación o configuración de software', 'Activo' => 1],
            ['Nombre' => 'Consulta',            'Descripcion' => 'Pregunta o duda funcional', 'Activo' => 1],
            ['Nombre' => 'Otro',                'Descripcion' => 'No clasificado', 'Activo' => 1],
        ]);

        // hd.estatus (en orden de flujo)
        DB::table('hd.estatus')->insert([
            ['Nombre' => 'Abierto',      'Orden' => 1],
            ['Nombre' => 'En proceso',   'Orden' => 2],
            ['Nombre' => 'Asignado',     'Orden' => 3],
            ['Nombre' => 'En espera',    'Orden' => 4],
            ['Nombre' => 'Resuelto',     'Orden' => 5],
            ['Nombre' => 'Cerrado',      'Orden' => 6],
            ['Nombre' => 'Cancelado',    'Orden' => 7],
        ]);

        // hd.error (errores frecuentes por tipo)
        $tipos = DB::table('hd.tipo_error')->pluck('ID_TipoError', 'Nombre');

        $errores = [
            ['desc' => 'No puedo iniciar sesión',                    'tipo' => 'Error de sistema'],
            ['desc' => 'La pantalla no carga / carga lenta',         'tipo' => 'Error de sistema'],
            ['desc' => 'Error al guardar o actualizar datos',        'tipo' => 'Error de sistema'],
            ['desc' => 'No tengo acceso al módulo',                  'tipo' => 'Solicitud de acceso'],
            ['desc' => 'Necesito alta de usuario',                   'tipo' => 'Solicitud de acceso'],
            ['desc' => 'Sin conexión a internet / VPN',              'tipo' => 'Error de red'],
            ['desc' => 'La impresora no imprime',                    'tipo' => 'Error de hardware'],
            ['desc' => 'Computadora no enciende / lenta',            'tipo' => 'Error de hardware'],
            ['desc' => 'Datos incorrectos en el sistema',            'tipo' => 'Error de datos'],
            ['desc' => 'Necesito instalar un programa',              'tipo' => 'Instalación'],
            ['desc' => 'Duda sobre cómo usar el sistema',            'tipo' => 'Consulta'],
            ['desc' => 'Error al capturar comisión',                 'tipo' => 'Error de usuario'],
            ['desc' => 'Error al generar pedido',                    'tipo' => 'Error de sistema'],
            ['desc' => 'Otro problema no listado',                   'tipo' => 'Otro'],
        ];

        foreach ($errores as $e) {
            $idTipo = $tipos[$e['tipo']] ?? $tipos->first();
            DB::table('hd.error')->insert(['Descripcion' => $e['desc'], 'Tipo' => $idTipo, 'Activo' => 1]);
        }

        // hd.solucion
        DB::table('hd.solucion')->insert([
            ['Descripcion' => 'Reinicio de sesión / caché del navegador',      'Activo' => 1],
            ['Descripcion' => 'Restablecimiento de contraseña',                'Activo' => 1],
            ['Descripcion' => 'Alta/baja de permisos en el sistema',           'Activo' => 1],
            ['Descripcion' => 'Reinstalación de controladores o software',     'Activo' => 1],
            ['Descripcion' => 'Sustitución o configuración de hardware',       'Activo' => 1],
            ['Descripcion' => 'Corrección de datos en base de datos',          'Activo' => 1],
            ['Descripcion' => 'Configuración de red / VPN',                    'Activo' => 1],
            ['Descripcion' => 'Capacitación / guía al usuario',                'Activo' => 1],
            ['Descripcion' => 'Escalado a proveedor externo',                  'Activo' => 1],
            ['Descripcion' => 'Solución no aplicable / cerrado sin acción',    'Activo' => 1],
        ]);

        // hd.sla (niveles de servicio por defecto)
        DB::table('hd.sla')->insert([
            ['Nombre' => 'Crítico global',   'ID_Area' => null, 'Prioridad' => 'CRITICA', 'HorasRespuesta' => 1,  'HorasResolucion' => 4,  'Activo' => 1],
            ['Nombre' => 'Alto global',      'ID_Area' => null, 'Prioridad' => 'ALTA',    'HorasRespuesta' => 2,  'HorasResolucion' => 8,  'Activo' => 1],
            ['Nombre' => 'Media global',     'ID_Area' => null, 'Prioridad' => 'MEDIA',   'HorasRespuesta' => 4,  'HorasResolucion' => 24, 'Activo' => 1],
            ['Nombre' => 'Baja global',      'ID_Area' => null, 'Prioridad' => 'BAJA',    'HorasRespuesta' => 8,  'HorasResolucion' => 48, 'Activo' => 1],
        ]);

        // rh.fuente_reclutamiento
        DB::table('rh.fuente_reclutamiento')->insert([
            ['Nombre' => 'LinkedIn',         'Descripcion' => 'Red profesional LinkedIn', 'Activo' => 1],
            ['Nombre' => 'OCC Mundial',      'Descripcion' => 'Portal OCC Mundial', 'Activo' => 1],
            ['Nombre' => 'Indeed',           'Descripcion' => 'Portal Indeed', 'Activo' => 1],
            ['Nombre' => 'Referido interno', 'Descripcion' => 'Referencia de empleado', 'Activo' => 1],
            ['Nombre' => 'Bolsa de trabajo', 'Descripcion' => 'Bolsa universitaria', 'Activo' => 1],
            ['Nombre' => 'Facebook',         'Descripcion' => 'Publicación en redes sociales', 'Activo' => 1],
            ['Nombre' => 'Agencia externa',  'Descripcion' => 'Agencia de reclutamiento', 'Activo' => 1],
            ['Nombre' => 'Candidatura espontánea', 'Descripcion' => 'CV enviado directamente', 'Activo' => 1],
            ['Nombre' => 'Otro',             'Descripcion' => 'Otro canal no especificado', 'Activo' => 1],
        ]);

        // rh.estatus_candidato
        DB::table('rh.estatus_candidato')->insert([
            ['Nombre' => 'NUEVO',          'Descripcion' => 'Candidato recién registrado',     'OrdenProceso' => 1, 'Activo' => 1],
            ['Nombre' => 'EN_REVISION',    'Descripcion' => 'CV en revisión por RH',           'OrdenProceso' => 2, 'Activo' => 1],
            ['Nombre' => 'CITADO',         'Descripcion' => 'Citado a entrevista',             'OrdenProceso' => 3, 'Activo' => 1],
            ['Nombre' => 'ENTREVISTADO',   'Descripcion' => 'Entrevistado, pendiente resultado','OrdenProceso' => 4, 'Activo' => 1],
            ['Nombre' => 'SELECCIONADO',   'Descripcion' => 'Candidato seleccionado',          'OrdenProceso' => 5, 'Activo' => 1],
            ['Nombre' => 'OFERTA_ENVIADA', 'Descripcion' => 'Oferta laboral enviada',          'OrdenProceso' => 6, 'Activo' => 1],
            ['Nombre' => 'CONTRATADO',     'Descripcion' => 'Candidato contratado',            'OrdenProceso' => 7, 'Activo' => 1],
            ['Nombre' => 'RECHAZADO',      'Descripcion' => 'No pasó el proceso',              'OrdenProceso' => 8, 'Activo' => 1],
            ['Nombre' => 'DESCARTADO',     'Descripcion' => 'Descartado por RH',               'OrdenProceso' => 9, 'Activo' => 1],
        ]);

        // com.indicador
        DB::table('com.indicador')->insert([
            ['Clave' => 'VOL', 'Nombre' => 'Volumen de venta',        'Categoria' => 'VENTAS',      'OrdenResumen' => 1, 'Activo' => 1],
            ['Clave' => 'COB', 'Nombre' => 'Cobertura de categoría',  'Categoria' => 'VENTAS',      'OrdenResumen' => 2, 'Activo' => 1],
            ['Clave' => 'EFE', 'Nombre' => 'Efectividad',             'Categoria' => 'VENTAS',      'OrdenResumen' => 3, 'Activo' => 1],
            ['Clave' => 'EFI', 'Nombre' => 'Eficiencia',              'Categoria' => 'VENTAS',      'OrdenResumen' => 4, 'Activo' => 1],
            ['Clave' => 'DF1', 'Nombre' => 'Devolución F1',           'Categoria' => 'CALIDAD',     'OrdenResumen' => 5, 'Activo' => 1],
            ['Clave' => 'DAU', 'Nombre' => 'Devolución autoservicio', 'Categoria' => 'CALIDAD',     'OrdenResumen' => 6, 'Activo' => 1],
            ['Clave' => 'NSE', 'Nombre' => 'NSE nivel de servicio',   'Categoria' => 'CALIDAD',     'OrdenResumen' => 7, 'Activo' => 1],
            ['Clave' => 'DOC', 'Nombre' => 'Documentos y checklist',  'Categoria' => 'DOCUMENTOS',  'OrdenResumen' => 8, 'Activo' => 1],
        ]);

        // com.sub_indicador — VOL: líneas de producto
        $idVol = DB::table('com.indicador')->where('Clave', 'VOL')->value('ID_Indicador');
        DB::table('com.sub_indicador')->insert([
            ['ID_Indicador' => $idVol, 'Clave' => 'EMB', 'Nombre' => 'Embutidos',    'Orden' => 1, 'Activo' => 1],
            ['ID_Indicador' => $idVol, 'Clave' => 'CF',  'Nombre' => 'Carnes frías', 'Orden' => 2, 'Activo' => 1],
            ['ID_Indicador' => $idVol, 'Clave' => 'QSO', 'Nombre' => 'Queso',        'Orden' => 3, 'Activo' => 1],
            ['ID_Indicador' => $idVol, 'Clave' => 'MTK', 'Nombre' => 'Mantequilla',  'Orden' => 4, 'Activo' => 1],
        ]);

        // com.sub_indicador — COB: categorías de cobertura
        $idCob = DB::table('com.indicador')->where('Clave', 'COB')->value('ID_Indicador');
        DB::table('com.sub_indicador')->insert([
            ['ID_Indicador' => $idCob, 'Clave' => 'BOL',  'Nombre' => 'Bolonia',     'Orden' => 1, 'Activo' => 1],
            ['ID_Indicador' => $idCob, 'Clave' => 'CHOR', 'Nombre' => 'Chorizo',     'Orden' => 2, 'Activo' => 1],
            ['ID_Indicador' => $idCob, 'Clave' => 'JAM',  'Nombre' => 'Jamón',       'Orden' => 3, 'Activo' => 1],
            ['ID_Indicador' => $idCob, 'Clave' => 'LOM',  'Nombre' => 'Lomo',        'Orden' => 4, 'Activo' => 1],
            ['ID_Indicador' => $idCob, 'Clave' => 'MTK',  'Nombre' => 'Mantequilla', 'Orden' => 5, 'Activo' => 1],
            ['ID_Indicador' => $idCob, 'Clave' => 'QSO',  'Nombre' => 'Queso',       'Orden' => 6, 'Activo' => 1],
            ['ID_Indicador' => $idCob, 'Clave' => 'SAL',  'Nombre' => 'Salchicha',   'Orden' => 7, 'Activo' => 1],
            ['ID_Indicador' => $idCob, 'Clave' => 'TOC',  'Nombre' => 'Tocino',      'Orden' => 8, 'Activo' => 1],
        ]);

        // com.sub_indicador — DOC: conceptos del checklist (Gerentes de Sucursal)
        $idDoc = DB::table('com.indicador')->where('Clave', 'DOC')->value('ID_Indicador');
        DB::table('com.sub_indicador')->insert([
            ['ID_Indicador' => $idDoc, 'Clave' => 'CHK', 'Nombre' => 'Check list de unidades',                   'Orden' => 1, 'Activo' => 1],
            ['ID_Indicador' => $idDoc, 'Clave' => 'SPH', 'Nombre' => 'Smartphone / Impresora / Licencia',        'Orden' => 2, 'Activo' => 1],
            ['ID_Indicador' => $idDoc, 'Clave' => 'ACO', 'Nombre' => 'Formato de acompañamiento supervisor',     'Orden' => 3, 'Activo' => 1],
            ['ID_Indicador' => $idDoc, 'Clave' => 'LIQ', 'Nombre' => 'Liquidación perfecta',                    'Orden' => 4, 'Activo' => 1],
            ['ID_Indicador' => $idDoc, 'Clave' => 'MES', 'Nombre' => 'Mesa de control',                         'Orden' => 5, 'Activo' => 1],
            ['ID_Indicador' => $idDoc, 'Clave' => 'REP', 'Nombre' => 'Reporte de liquidación mayor $600',       'Orden' => 6, 'Activo' => 1],
            ['ID_Indicador' => $idDoc, 'Clave' => 'PRO', 'Nombre' => 'Check list promotoría',                   'Orden' => 7, 'Activo' => 1],
            ['ID_Indicador' => $idDoc, 'Clave' => 'MER', 'Nombre' => 'Mercadeo tiendas',                        'Orden' => 8, 'Activo' => 1],
        ]);
    }
}
