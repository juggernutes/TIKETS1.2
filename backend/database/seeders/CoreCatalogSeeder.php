<?php

namespace Database\Seeders;

use App\Models\Core\Area;
use App\Models\Core\Permiso;
use App\Models\Core\Puesto;
use App\Models\Core\Rol;
use App\Models\Core\Sucursal;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CoreCatalogSeeder extends Seeder
{
    public function run(): void
    {
        // ── Roles del sistema ────────────────────────────────────────────────
        $roles = [
            'ADMIN',
            'GERENTE_SUCURSAL',
            'GENERALISTA',
            'CXC',
            'SOPORTE_HD',
            'RH',
            'VENDEDOR',
            'SUPERVISOR',
            'ALMACEN',
        ];

        foreach ($roles as $nombre) {
            Rol::firstOrCreate(['Nombre' => $nombre], ['Activo' => true]);
        }

        $permisosPedidos = [
            ['Clave' => 'ped.pedidos.ver', 'Nombre' => 'Ver pedidos', 'Modulo' => 'PED', 'Descripcion' => 'Acceso al modulo de pedidos.'],
            ['Clave' => 'ped.pedidos.crear', 'Nombre' => 'Crear pedidos', 'Modulo' => 'PED', 'Descripcion' => 'Captura pedidos desde unidad operacional.'],
            ['Clave' => 'ped.pedidos.ver_propios', 'Nombre' => 'Ver pedidos propios', 'Modulo' => 'PED', 'Descripcion' => 'Consulta pedidos de la unidad propia.'],
            ['Clave' => 'ped.pedidos.ver_por_autorizar', 'Nombre' => 'Ver pedidos por autorizar', 'Modulo' => 'PED', 'Descripcion' => 'Consulta bandeja de pedidos capturados.'],
            ['Clave' => 'ped.pedidos.autorizar', 'Nombre' => 'Autorizar pedidos', 'Modulo' => 'PED', 'Descripcion' => 'Autoriza pedidos dentro de su alcance.'],
            ['Clave' => 'ped.pedidos.ver_por_surtir', 'Nombre' => 'Ver pedidos por surtir', 'Modulo' => 'PED', 'Descripcion' => 'Consulta bandeja de pedidos autorizados.'],
            ['Clave' => 'ped.pedidos.surtir', 'Nombre' => 'Surtir pedidos', 'Modulo' => 'PED', 'Descripcion' => 'Surtir pedidos autorizados dentro de su alcance.'],
            ['Clave' => 'ped.pedidos.cancelar', 'Nombre' => 'Cancelar pedidos', 'Modulo' => 'PED', 'Descripcion' => 'Cancela pedidos dentro de su alcance.'],
            ['Clave' => 'ped.pedidos.csv', 'Nombre' => 'Exportar CSV pedidos', 'Modulo' => 'PED', 'Descripcion' => 'Genera CSV de surtido.'],
            ['Clave' => 'ped.catalogos.ver', 'Nombre' => 'Ver catalogos pedidos', 'Modulo' => 'PED', 'Descripcion' => 'Consulta catalogos de pedidos.'],
            ['Clave' => 'ped.unidades.admin', 'Nombre' => 'Administrar unidades', 'Modulo' => 'PED', 'Descripcion' => 'Administra unidades operacionales y cambios administrativos.'],
        ];

        foreach ($permisosPedidos as $data) {
            Permiso::firstOrCreate(
                ['Clave' => $data['Clave']],
                array_merge($data, ['Activo' => true])
            );
        }

        $permisosPorRol = [
            'ADMIN' => array_column($permisosPedidos, 'Clave'),
            'VENDEDOR' => ['ped.pedidos.ver', 'ped.pedidos.crear', 'ped.pedidos.ver_propios', 'ped.catalogos.ver'],
            'SUPERVISOR' => ['ped.pedidos.ver', 'ped.pedidos.ver_por_autorizar', 'ped.pedidos.autorizar', 'ped.pedidos.cancelar', 'ped.catalogos.ver'],
            'GERENTE_SUCURSAL' => ['ped.pedidos.ver', 'ped.pedidos.ver_por_autorizar', 'ped.pedidos.autorizar', 'ped.pedidos.cancelar', 'ped.catalogos.ver'],
            'ALMACEN' => ['ped.pedidos.ver', 'ped.pedidos.ver_por_surtir', 'ped.pedidos.surtir', 'ped.pedidos.csv', 'ped.catalogos.ver'],
        ];

        foreach ($permisosPorRol as $rolNombre => $claves) {
            $rol = Rol::where('Nombre', $rolNombre)->first();
            if (!$rol) {
                continue;
            }

            $idsPermisos = Permiso::whereIn('Clave', $claves)->pluck('ID_Permiso');
            foreach ($idsPermisos as $idPermiso) {
                DB::table('core.rol_permiso')->updateOrInsert(
                    ['ID_Rol' => $rol->ID_Rol, 'ID_Permiso' => $idPermiso],
                    ['Activo' => true, 'FechaCreacion' => now()]
                );
            }
        }

        // ── Áreas (Serie = prefijo de folio 3 chars) ─────────────────────────
        $areas = [
            ['Nombre' => 'SISTEMAS',            'Serie' => 'SIS'],
            ['Nombre' => 'RECURSOS HUMANOS',     'Serie' => 'RRH'],
            ['Nombre' => 'CONTABILIDAD',         'Serie' => 'CON'],
            ['Nombre' => 'CUENTAS POR COBRAR',   'Serie' => 'CXC'],
            ['Nombre' => 'VENTAS',               'Serie' => 'VEN'],
            ['Nombre' => 'LOGISTICA',            'Serie' => 'LOG'],
            ['Nombre' => 'DIRECCION',            'Serie' => 'DIR'],
            ['Nombre' => 'GENERAL',              'Serie' => 'GEN'],
        ];

        foreach ($areas as $data) {
            Area::firstOrCreate(
                ['Nombre' => $data['Nombre']],
                ['Serie' => $data['Serie'], 'Activo' => true]
            );
        }

        // ── Sucursales (ID desde ERP — ajustar según catálogo real) ──────────
        $sucursales = [
            ['ID_Sucursal' => 1,  'Nombre' => 'MATRIZ',    'Ciudad' => 'TIJUANA'],
            ['ID_Sucursal' => 2,  'Nombre' => 'SUCURSAL 2', 'Ciudad' => 'ENSENADA'],
            ['ID_Sucursal' => 3,  'Nombre' => 'SUCURSAL 3', 'Ciudad' => 'MEXICALI'],
        ];

        foreach ($sucursales as $data) {
            Sucursal::firstOrCreate(
                ['ID_Sucursal' => $data['ID_Sucursal']],
                ['Nombre' => $data['Nombre'], 'Ciudad' => $data['Ciudad'], 'Activo' => true]
            );
        }

        // ── Puestos (Clave 9 chars, Nivel, Categoria, Segmento, Responsabilidad) ──
        $puestos = [
            ['Clave' => 'DIR-01-01', 'Descripcion' => 'DIRECTOR DE OPERACIONES',  'Nivel' => 1, 'Categoria' => 'D', 'Segmento' => 'ADM', 'Responsabilidad' => 'A'],
            ['Clave' => 'GER-02-01', 'Descripcion' => 'GERENTE DE SUCURSAL',      'Nivel' => 2, 'Categoria' => 'G', 'Segmento' => 'ADM', 'Responsabilidad' => 'A'],
            ['Clave' => 'SUP-03-01', 'Descripcion' => 'SUPERVISOR DE VENTAS',     'Nivel' => 3, 'Categoria' => 'S', 'Segmento' => 'VEN', 'Responsabilidad' => 'M'],
            ['Clave' => 'VEN-04-01', 'Descripcion' => 'VENDEDOR TRADICIONAL',     'Nivel' => 4, 'Categoria' => 'V', 'Segmento' => 'VEN', 'Responsabilidad' => 'B'],
            ['Clave' => 'VEN-04-02', 'Descripcion' => 'VENDEDOR MODERNO',         'Nivel' => 4, 'Categoria' => 'V', 'Segmento' => 'VEN', 'Responsabilidad' => 'B'],
            ['Clave' => 'ANA-05-01', 'Descripcion' => 'ANALISTA RH',              'Nivel' => 5, 'Categoria' => 'A', 'Segmento' => 'RRH', 'Responsabilidad' => 'B'],
            ['Clave' => 'ANA-05-02', 'Descripcion' => 'ANALISTA SISTEMAS',        'Nivel' => 5, 'Categoria' => 'A', 'Segmento' => 'SIS', 'Responsabilidad' => 'B'],
            ['Clave' => 'GEN-05-01', 'Descripcion' => 'GENERALISTA RH',           'Nivel' => 5, 'Categoria' => 'A', 'Segmento' => 'RRH', 'Responsabilidad' => 'B'],
            ['Clave' => 'AUX-06-01', 'Descripcion' => 'AUXILIAR ADMINISTRATIVO',  'Nivel' => 6, 'Categoria' => 'X', 'Segmento' => 'ADM', 'Responsabilidad' => 'B'],
        ];

        foreach ($puestos as $data) {
            Puesto::firstOrCreate(
                ['Clave' => $data['Clave']],
                array_merge($data, ['Activo' => true])
            );
        }
    }
}
