<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Catálogos del módulo PED:
 *   ped.capacidaduv     — categorías de capacidad de unidades
 *   ped.tipounidad      — tipos de unidad operacional
 *   ped.estado_pedido   — estados del flujo de un pedido
 *   ped.unidadoperacional — rutas, supervisores y almacenes demo
 */
class PedCatalogSeeder extends Seeder
{
    public function run(): void
    {
        // ped.capacidaduv
        DB::table('ped.capacidaduv')->insert([
            ['Nombre' => 'RUTA PEQUEÑA',  'Descripcion' => 'Hasta 50 clientes',         'CapacidadMinima' => 1,  'CapacidadMaxima' => 50,  'activo' => 1],
            ['Nombre' => 'RUTA MEDIANA',  'Descripcion' => 'Entre 51 y 100 clientes',   'CapacidadMinima' => 51, 'CapacidadMaxima' => 100, 'activo' => 1],
            ['Nombre' => 'RUTA GRANDE',   'Descripcion' => 'Más de 100 clientes',       'CapacidadMinima' => 101,'CapacidadMaxima' => 999, 'activo' => 1],
            ['Nombre' => 'ALMACEN',       'Descripcion' => 'Almacén, sin límite rutas', 'CapacidadMinima' => null,'CapacidadMaxima' => null,'activo' => 1],
        ]);

        // ped.tipounidad
        DB::table('ped.tipounidad')->insert([
            ['Nombre' => 'VENDEDOR',    'Descripcion' => 'Ruta de vendedor de campo',         'activo' => 1],
            ['Nombre' => 'SUPERVISOR',  'Descripcion' => 'Zona de supervisor',                'activo' => 1],
            ['Nombre' => 'ALMACEN',     'Descripcion' => 'Almacén / centro de distribución',  'activo' => 1],
        ]);

        // ped.estado_pedido
        DB::table('ped.estado_pedido')->insert([
            ['Nombre' => 'PENDIENTE',    'Descripcion' => 'Pedido capturado, pendiente de autorización', 'activo' => 1],
            ['Nombre' => 'AUTORIZADO',   'Descripcion' => 'Aprobado por supervisor',                     'activo' => 1],
            ['Nombre' => 'EN_PROCESO',   'Descripcion' => 'En preparación en almacén',                   'activo' => 1],
            ['Nombre' => 'SURTIDO',      'Descripcion' => 'Mercancía entregada a la ruta',               'activo' => 1],
            ['Nombre' => 'RECHAZADO',    'Descripcion' => 'Rechazado por el supervisor o almacén',       'activo' => 1],
            ['Nombre' => 'CANCELADO',    'Descripcion' => 'Cancelado antes del surtido',                 'activo' => 1],
        ]);

        // ped.unidadoperacional — requiere usuarios existentes
        $idAdmin = DB::table('core.login')->where('Cuenta', 'admin')->value('ID_Usuario')
                ?? DB::table('core.usuario')->first()?->ID_Usuario;

        if (!$idAdmin) {
            $this->command?->warn('PedCatalogSeeder: no se encontró usuario admin, omitiendo unidades operacionales.');
            return;
        }

        $idTipoVen  = DB::table('ped.tipounidad')->where('Nombre', 'VENDEDOR')->value('IdTipoUnidad');
        $idTipoSup  = DB::table('ped.tipounidad')->where('Nombre', 'SUPERVISOR')->value('IdTipoUnidad');
        $idTipoAlm  = DB::table('ped.tipounidad')->where('Nombre', 'ALMACEN')->value('IdTipoUnidad');
        $capMed     = DB::table('ped.capacidaduv')->where('Nombre', 'RUTA MEDIANA')->value('IdCapacidadUV');
        $capAlm     = DB::table('ped.capacidaduv')->where('Nombre', 'ALMACEN')->value('IdCapacidadUV');

        $idSucTj = DB::table('core.sucursal')->where('Nombre', 'TIJUANA')->value('ID_Sucursal');
        $idSucEns= DB::table('core.sucursal')->where('Nombre', 'ENSENADA')->value('ID_Sucursal');

        // Crear almacenes primero (para asignar como destino en rutas)
        $idAlmTj = DB::table('ped.unidadoperacional')->insertGetId([
            'IdTipoUnidad'  => $idTipoAlm,
            'IdUsuario'     => $idAdmin,
            'IdSucursal'    => $idSucTj,
            'IdCapacidadUV' => $capAlm,
            'Nombre'        => 'ALMACEN TIJUANA',
            'Descripcion'   => 'Almacén central Tijuana',
            'activo'        => 1,
        ]);

        $idAlmEns = DB::table('ped.unidadoperacional')->insertGetId([
            'IdTipoUnidad'  => $idTipoAlm,
            'IdUsuario'     => $idAdmin,
            'IdSucursal'    => $idSucEns,
            'IdCapacidadUV' => $capAlm,
            'Nombre'        => 'ALMACEN ENSENADA',
            'Descripcion'   => 'Almacén Ensenada',
            'activo'        => 1,
        ]);

        // Supervisor Tijuana
        $idSupTj = DB::table('ped.unidadoperacional')->insertGetId([
            'IdTipoUnidad'  => $idTipoSup,
            'IdUsuario'     => $idAdmin,
            'IdSucursal'    => $idSucTj,
            'IdCapacidadUV' => $capAlm,
            'Nombre'        => 'ZONA TJ-NORTE',
            'Descripcion'   => 'Zona supervisada TJ Norte',
            'activo'        => 1,
        ]);

        // Rutas vendedor Tijuana
        DB::table('ped.unidadoperacional')->insert([
            [
                'IdTipoUnidad'  => $idTipoVen,
                'IdUsuario'     => $idAdmin,
                'IdSupervisor'  => $idSupTj,
                'IdSucursal'    => $idSucTj,
                'IdCapacidadUV' => $capMed,
                'Nombre'        => 'RUTA TJ-01',
                'Descripcion'   => 'Ruta tradicional Tijuana 01',
                'activo'        => 1,
            ],
            [
                'IdTipoUnidad'  => $idTipoVen,
                'IdUsuario'     => $idAdmin,
                'IdSupervisor'  => $idSupTj,
                'IdSucursal'    => $idSucTj,
                'IdCapacidadUV' => $capMed,
                'Nombre'        => 'RUTA TJ-02',
                'Descripcion'   => 'Ruta moderna Tijuana 01',
                'activo'        => 1,
            ],
        ]);

        $this->command?->info('PedCatalogSeeder: catálogos PED cargados.');
    }
}
