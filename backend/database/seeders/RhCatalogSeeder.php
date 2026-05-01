<?php

namespace Database\Seeders;

use App\Models\Core\Empleado;
use App\Models\Core\Puesto;
use Illuminate\Database\Seeder;

/**
 * Empleados demo para pruebas.
 * Los puestos base ya existen en CoreCatalogSeeder (DIR-01-01, GER-02-01, SUP-03-01, VEN-04-01/02, GEN-05-01, ANA-05-02).
 * Este seeder solo crea los registros de empleados referencándolos.
 */
class RhCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $puestos = Puesto::pluck('ID_Puesto', 'Clave');

        // IDs fijos definidos en CoreCatalogSeeder (1=MATRIZ/TJ, 2=ENSENADA, 3=MEXICALI)
        $tj  = 1;
        $ens = 2;
        $mxl = 3;

        $pGer = $puestos['GER-02-01'] ?? null;
        $pSup = $puestos['SUP-03-01'] ?? null;
        $pVT  = $puestos['VEN-04-01'] ?? null;
        $pVM  = $puestos['VEN-04-02'] ?? null;
        $pGen = $puestos['GEN-05-01'] ?? null;
        $pAnaSis = $puestos['ANA-05-02'] ?? null;
        $pAnaRh  = $puestos['ANA-05-01'] ?? null;

        $empleados = [
            // ── Gerentes de sucursal ──────────────────────────────────────
            ['Numero_Empleado' => 10001, 'Nombre' => 'GERENTE TIJUANA DEMO',       'Correo' => 'gerente.tj@empresa.com',   'ID_Sucursal' => $tj,  'ID_Puesto' => $pGer],
            ['Numero_Empleado' => 10011, 'Nombre' => 'GERENTE ENSENADA DEMO',      'Correo' => 'gerente.ens@empresa.com',  'ID_Sucursal' => $ens, 'ID_Puesto' => $pGer],
            ['Numero_Empleado' => 10021, 'Nombre' => 'GERENTE MEXICALI DEMO',      'Correo' => 'gerente.mxl@empresa.com',  'ID_Sucursal' => $mxl, 'ID_Puesto' => $pGer],

            // ── Supervisores ──────────────────────────────────────────────
            ['Numero_Empleado' => 10004, 'Nombre' => 'SUPERVISOR TRADICIONAL TJ',  'Correo' => 'sup.tj@empresa.com',       'ID_Sucursal' => $tj,  'ID_Puesto' => $pSup],
            ['Numero_Empleado' => 10014, 'Nombre' => 'SUPERVISOR MODERNO ENS',     'Correo' => 'sup.ens@empresa.com',      'ID_Sucursal' => $ens, 'ID_Puesto' => $pSup],

            // ── Vendedores tradicionales ──────────────────────────────────
            ['Numero_Empleado' => 10002, 'Nombre' => 'VENDEDOR TRAD TJ-01',        'Correo' => 'vt.tj01@empresa.com',      'ID_Sucursal' => $tj,  'ID_Puesto' => $pVT],
            ['Numero_Empleado' => 10003, 'Nombre' => 'VENDEDOR TRAD TJ-02',        'Correo' => 'vt.tj02@empresa.com',      'ID_Sucursal' => $tj,  'ID_Puesto' => $pVT],
            ['Numero_Empleado' => 10012, 'Nombre' => 'VENDEDOR TRAD ENS-01',       'Correo' => 'vt.ens01@empresa.com',     'ID_Sucursal' => $ens, 'ID_Puesto' => $pVT],
            ['Numero_Empleado' => 10022, 'Nombre' => 'VENDEDOR TRAD MXL-01',       'Correo' => 'vt.mxl01@empresa.com',     'ID_Sucursal' => $mxl, 'ID_Puesto' => $pVT],

            // ── Vendedores modernos ───────────────────────────────────────
            ['Numero_Empleado' => 10005, 'Nombre' => 'VENDEDOR MOD TJ-01',         'Correo' => 'vm.tj01@empresa.com',      'ID_Sucursal' => $tj,  'ID_Puesto' => $pVM],
            ['Numero_Empleado' => 10015, 'Nombre' => 'VENDEDOR MOD ENS-01',        'Correo' => 'vm.ens01@empresa.com',     'ID_Sucursal' => $ens, 'ID_Puesto' => $pVM],

            // ── Staff corporativo ─────────────────────────────────────────
            ['Numero_Empleado' => 10099, 'Nombre' => 'GENERALISTA RH CORP',        'Correo' => 'generalista@empresa.com',  'ID_Sucursal' => $tj,  'ID_Puesto' => $pGen],
            ['Numero_Empleado' => 10098, 'Nombre' => 'ANALISTA CXC CORP',          'Correo' => 'cxc@empresa.com',          'ID_Sucursal' => $tj,  'ID_Puesto' => $pAnaRh],
            ['Numero_Empleado' => 10097, 'Nombre' => 'ADMINISTRADOR SISTEMA',      'Correo' => 'admin@empresa.com',        'ID_Sucursal' => $tj,  'ID_Puesto' => $pAnaSis],
        ];

        foreach ($empleados as $e) {
            Empleado::updateOrCreate(
                ['Numero_Empleado' => $e['Numero_Empleado']],
                array_merge($e, ['Activo' => true])
            );
        }

        $this->command?->info('RhCatalogSeeder: ' . count($empleados) . ' empleados demo cargados.');
    }
}
