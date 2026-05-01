<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CoreCatalogSeeder::class,   // 1. roles, áreas, sucursales, puestos
            CatalogoSeeder::class,      // 2. hd.estatus/tipo_error/error/solucion/sla, rh.fuentes, com.indicadores — necesita cat.sistema → áreas ya existen
            RhCatalogSeeder::class,     // 3. empleados demo — necesita puestos y sucursales
            UsuarioSeeder::class,       // 4. usuarios + logins — necesita roles, áreas, empleados
            CatArticuloSeeder::class,   // 5. cat.tipo_articulo/linea/grupo/articulo — independiente
            PedCatalogSeeder::class,    // 6. ped.capacidaduv/tipounidad/estado_pedido/unidadoperacional — necesita usuarios y sucursales
        ]);
    }
}
