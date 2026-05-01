<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Catálogo de artículos para el módulo PED y metas COM.
 * Árbol: TipoArticulo → LineaArticulo → GrupoArticulo → Articulo
 */
class CatArticuloSeeder extends Seeder
{
    public function run(): void
    {
        // ── cat.tipo_articulo ────────────────────────────────────────────────
        DB::table('cat.tipo_articulo')->insert([
            ['Nombre' => 'PIEZA',     'Medida' => 'PZ',  'Activo' => 1],
            ['Nombre' => 'CAJA',      'Medida' => 'CJ',  'Activo' => 1],
            ['Nombre' => 'KILOGRAMO', 'Medida' => 'KG',  'Activo' => 1],
            ['Nombre' => 'PAQUETE',   'Medida' => 'PQ',  'Activo' => 1],
        ]);

        $tipoPZ = DB::table('cat.tipo_articulo')->where('Nombre', 'PIEZA')->value('IdTipoArticulo');
        $tipoCJ = DB::table('cat.tipo_articulo')->where('Nombre', 'CAJA')->value('IdTipoArticulo');
        $tipoKG = DB::table('cat.tipo_articulo')->where('Nombre', 'KILOGRAMO')->value('IdTipoArticulo');
        $tipoPQ = DB::table('cat.tipo_articulo')->where('Nombre', 'PAQUETE')->value('IdTipoArticulo');

        // ── cat.linea_articulo ───────────────────────────────────────────────
        DB::table('cat.linea_articulo')->insert([
            ['Nombre' => 'EMBUTIDOS',     'Descripcion' => 'Salchichas, salchicha cocida',    'Activo' => 1],
            ['Nombre' => 'CARNES FRIAS',  'Descripcion' => 'Jamón, bolonia, lomo',            'Activo' => 1],
            ['Nombre' => 'QUESOS',        'Descripcion' => 'Queso amarillo, manchego',        'Activo' => 1],
            ['Nombre' => 'MANTEQUILLA',   'Descripcion' => 'Mantequilla y margarina',         'Activo' => 1],
            ['Nombre' => 'CHORIZO',       'Descripcion' => 'Chorizo español y picante',       'Activo' => 1],
            ['Nombre' => 'TOCINO',        'Descripcion' => 'Tocino ahumado y natural',        'Activo' => 1],
        ]);

        $lineas = DB::table('cat.linea_articulo')->pluck('IdLineaArticulo', 'Nombre');

        // ── cat.grupo_articulo ───────────────────────────────────────────────
        $grupos = [
            ['IdLineaArticulo' => $lineas['EMBUTIDOS'],   'Nombre' => 'SALCHICHAS',      'Activo' => 1],
            ['IdLineaArticulo' => $lineas['EMBUTIDOS'],   'Nombre' => 'MORTADELA',       'Activo' => 1],
            ['IdLineaArticulo' => $lineas['CARNES FRIAS'],'Nombre' => 'JAMON',           'Activo' => 1],
            ['IdLineaArticulo' => $lineas['CARNES FRIAS'],'Nombre' => 'BOLONIA',         'Activo' => 1],
            ['IdLineaArticulo' => $lineas['CARNES FRIAS'],'Nombre' => 'LOMO',            'Activo' => 1],
            ['IdLineaArticulo' => $lineas['QUESOS'],      'Nombre' => 'QUESO AMARILLO',  'Activo' => 1],
            ['IdLineaArticulo' => $lineas['QUESOS'],      'Nombre' => 'MANCHEGO',        'Activo' => 1],
            ['IdLineaArticulo' => $lineas['MANTEQUILLA'], 'Nombre' => 'MANTEQUILLA',     'Activo' => 1],
            ['IdLineaArticulo' => $lineas['CHORIZO'],     'Nombre' => 'CHORIZO',         'Activo' => 1],
            ['IdLineaArticulo' => $lineas['TOCINO'],      'Nombre' => 'TOCINO',          'Activo' => 1],
        ];

        DB::table('cat.grupo_articulo')->insert($grupos);

        $grupoIds = DB::table('cat.grupo_articulo')->pluck('IdGrupoArticulo', 'Nombre');

        // ── cat.articulo ─────────────────────────────────────────────────────
        DB::table('cat.articulo')->insert([
            // Salchichas
            ['Nombre' => 'SALCHICHA VIENA 250G',      'NombreCorto' => 'SALV250',   'IdTipoArticulo' => $tipoPQ, 'Peso' => 0.250, 'IdGrupoArticulo' => $grupoIds['SALCHICHAS'],     'Activo' => 1],
            ['Nombre' => 'SALCHICHA CHEDDAR 500G',    'NombreCorto' => 'SALCH500',  'IdTipoArticulo' => $tipoPQ, 'Peso' => 0.500, 'IdGrupoArticulo' => $grupoIds['SALCHICHAS'],     'Activo' => 1],
            ['Nombre' => 'SALCHICHA CAJA X12',        'NombreCorto' => 'SALCJ12',   'IdTipoArticulo' => $tipoCJ, 'Peso' => 3.000, 'IdGrupoArticulo' => $grupoIds['SALCHICHAS'],     'Activo' => 1],
            // Mortadela
            ['Nombre' => 'MORTADELA CLASICA 400G',    'NombreCorto' => 'MORTC400',  'IdTipoArticulo' => $tipoPQ, 'Peso' => 0.400, 'IdGrupoArticulo' => $grupoIds['MORTADELA'],      'Activo' => 1],
            // Jamón
            ['Nombre' => 'JAMON COCIDO 200G',         'NombreCorto' => 'JAMC200',   'IdTipoArticulo' => $tipoPQ, 'Peso' => 0.200, 'IdGrupoArticulo' => $grupoIds['JAMON'],          'Activo' => 1],
            ['Nombre' => 'JAMON SERRANO 100G',        'NombreCorto' => 'JAMS100',   'IdTipoArticulo' => $tipoPQ, 'Peso' => 0.100, 'IdGrupoArticulo' => $grupoIds['JAMON'],          'Activo' => 1],
            // Bolonia
            ['Nombre' => 'BOLONIA REGULAR 500G',      'NombreCorto' => 'BOLR500',   'IdTipoArticulo' => $tipoPQ, 'Peso' => 0.500, 'IdGrupoArticulo' => $grupoIds['BOLONIA'],        'Activo' => 1],
            // Lomo
            ['Nombre' => 'LOMO DE CERDO 300G',        'NombreCorto' => 'LOMC300',   'IdTipoArticulo' => $tipoPQ, 'Peso' => 0.300, 'IdGrupoArticulo' => $grupoIds['LOMO'],           'Activo' => 1],
            // Quesos
            ['Nombre' => 'QUESO AMARILLO LAMINADO',   'NombreCorto' => 'QALAM',     'IdTipoArticulo' => $tipoPQ, 'Peso' => 0.200, 'IdGrupoArticulo' => $grupoIds['QUESO AMARILLO'], 'Activo' => 1],
            ['Nombre' => 'MANCHEGO GRANEL KG',        'NombreCorto' => 'MANKG',     'IdTipoArticulo' => $tipoKG, 'Peso' => 1.000, 'IdGrupoArticulo' => $grupoIds['MANCHEGO'],       'Activo' => 1],
            // Mantequilla
            ['Nombre' => 'MANTEQUILLA 90G',           'NombreCorto' => 'MANT90',    'IdTipoArticulo' => $tipoPZ, 'Peso' => 0.090, 'IdGrupoArticulo' => $grupoIds['MANTEQUILLA'],    'Activo' => 1],
            ['Nombre' => 'MANTEQUILLA CAJA X24',      'NombreCorto' => 'MANTCJ24',  'IdTipoArticulo' => $tipoCJ, 'Peso' => 2.160, 'IdGrupoArticulo' => $grupoIds['MANTEQUILLA'],    'Activo' => 1],
            // Chorizo
            ['Nombre' => 'CHORIZO ESPAÑOL 200G',      'NombreCorto' => 'CHORE200',  'IdTipoArticulo' => $tipoPQ, 'Peso' => 0.200, 'IdGrupoArticulo' => $grupoIds['CHORIZO'],        'Activo' => 1],
            // Tocino
            ['Nombre' => 'TOCINO AHUMADO 200G',       'NombreCorto' => 'TOCA200',   'IdTipoArticulo' => $tipoPQ, 'Peso' => 0.200, 'IdGrupoArticulo' => $grupoIds['TOCINO'],         'Activo' => 1],
            ['Nombre' => 'TOCINO NATURAL 500G',       'NombreCorto' => 'TOCN500',   'IdTipoArticulo' => $tipoPQ, 'Peso' => 0.500, 'IdGrupoArticulo' => $grupoIds['TOCINO'],         'Activo' => 1],
        ]);

        $this->command?->info('CatArticuloSeeder: catálogo de artículos cargado.');
    }
}
