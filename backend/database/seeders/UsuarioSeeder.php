<?php

namespace Database\Seeders;

use App\Models\Core\Area;
use App\Models\Core\Empleado;
use App\Models\Core\Rol;
use App\Models\Core\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Usuarios demo del sistema con sus credenciales de acceso.
 *
 * Cuentas creadas:
 *   admin         / Admin1234!   → ADMIN
 *   gerente.tj    / Demo1234!    → GERENTE (Tijuana)
 *   gerente.ens   / Demo1234!    → GERENTE (Ensenada)
 *   generalista   / Demo1234!    → GENERALISTA
 *   cxc           / Demo1234!    → CXC
 *   soporte.hd    / Demo1234!    → SOPORTE HD
 *   empleado.demo / Demo1234!    → EMPLEADO (sin rol especial)
 */
class UsuarioSeeder extends Seeder
{
    public function run(): void
    {
        $roles = Rol::pluck('ID_Rol', 'Nombre');
        $areas = Area::pluck('ID_Area', 'Nombre');

        $demoPassword = Hash::make('Demo1234!');
        $adminPassword = Hash::make('Admin1234!');

        $usuarios = [
            [
                'Nombre'  => 'ADMINISTRADOR',
                'Email'   => 'admin@empresa.com',
                'ID_Rol'  => $roles['ADMINISTRADOR'] ?? null,
                'ID_Area' => $areas['TI']             ?? null,
                'Activo'  => true,
                'cuenta'  => 'admin',
                'pass'    => $adminPassword,
                'debe_cambiar' => false,
            ],
            [
                'Nombre'  => 'GERENTE TIJUANA',
                'Email'   => 'gerente.tj@empresa.com',
                'ID_Rol'  => $roles['GERENTE']        ?? null,
                'ID_Area' => $areas['VENTAS']          ?? null,
                'Activo'  => true,
                'cuenta'  => 'gerente.tj',
                'pass'    => $demoPassword,
                'debe_cambiar' => false,
            ],
            [
                'Nombre'  => 'GERENTE ENSENADA',
                'Email'   => 'gerente.ens@empresa.com',
                'ID_Rol'  => $roles['GERENTE']        ?? null,
                'ID_Area' => $areas['VENTAS']          ?? null,
                'Activo'  => true,
                'cuenta'  => 'gerente.ens',
                'pass'    => $demoPassword,
                'debe_cambiar' => false,
            ],
            [
                'Nombre'  => 'GENERALISTA RH',
                'Email'   => 'generalista@empresa.com',
                'ID_Rol'  => $roles['GENERALISTA']    ?? null,
                'ID_Area' => $areas['RECURSOS HUMANOS'] ?? null,
                'Activo'  => true,
                'cuenta'  => 'generalista',
                'pass'    => $demoPassword,
                'debe_cambiar' => false,
            ],
            [
                'Nombre'  => 'ANALISTA CXC',
                'Email'   => 'cxc@empresa.com',
                'ID_Rol'  => $roles['CXC']            ?? null,
                'ID_Area' => $areas['CONTABILIDAD']   ?? null,
                'Activo'  => true,
                'cuenta'  => 'cxc',
                'pass'    => $demoPassword,
                'debe_cambiar' => false,
            ],
            [
                'Nombre'  => 'SOPORTE HELP DESK',
                'Email'   => 'soporte.hd@empresa.com',
                'ID_Rol'  => $roles['SOPORTE HD']     ?? null,
                'ID_Area' => $areas['TI']              ?? null,
                'Activo'  => true,
                'cuenta'  => 'soporte.hd',
                'pass'    => $demoPassword,
                'debe_cambiar' => false,
            ],
            [
                'Nombre'  => 'EMPLEADO DEMO',
                'Email'   => 'empleado.demo@empresa.com',
                'ID_Rol'  => $roles['EMPLEADO']       ?? null,
                'ID_Area' => $areas['VENTAS']          ?? null,
                'Activo'  => true,
                'cuenta'  => 'empleado.demo',
                'pass'    => $demoPassword,
                'debe_cambiar' => true,
            ],
        ];

        foreach ($usuarios as $u) {
            $cuenta        = $u['cuenta'];
            $pass          = $u['pass'];
            $debeCambiar   = $u['debe_cambiar'];

            unset($u['cuenta'], $u['pass'], $u['debe_cambiar']);

            $usuario = Usuario::updateOrCreate(['Email' => $u['Email']], $u);

            DB::table('core.login')->updateOrInsert(
                ['Cuenta' => $cuenta],
                [
                    'PasswordHash'         => $pass,
                    'ID_Usuario'           => $usuario->ID_Usuario,
                    'Activo'               => true,
                    'DebeCambiarPassword'  => $debeCambiar,
                    'IntentosFallidos'     => 0,
                ]
            );
        }

        // Vincular empleados demo a sus usuarios via core.usuario_relacion
        $vinculaciones = [
            ['cuenta' => 'gerente.tj',  'numero_empleado' => 10001],
            ['cuenta' => 'gerente.ens', 'numero_empleado' => 10011],
            ['cuenta' => 'generalista', 'numero_empleado' => 10099],
            ['cuenta' => 'cxc',         'numero_empleado' => 10098],
            ['cuenta' => 'admin',       'numero_empleado' => 10097],
        ];

        foreach ($vinculaciones as $v) {
            $idUsuario = DB::table('core.login')
                ->where('Cuenta', $v['cuenta'])
                ->value('ID_Usuario');

            if ($idUsuario && Empleado::find($v['numero_empleado'])) {
                DB::table('core.usuario_relacion')->updateOrInsert(
                    ['ID_Usuario' => $idUsuario],
                    ['Numero_Empleado' => $v['numero_empleado']]
                );
            }
        }

        $this->command?->info('UsuarioSeeder: ' . count($usuarios) . ' usuarios creados.');
        $this->command?->table(
            ['Cuenta', 'Password', 'Rol'],
            array_map(fn ($u) => [
                $u['cuenta'],
                $u['debe_cambiar'] ? 'Demo1234! (debe cambiar)' : ($u['cuenta'] === 'admin' ? 'Admin1234!' : 'Demo1234!'),
                '-',
            ], $usuarios)
        );
    }
}
