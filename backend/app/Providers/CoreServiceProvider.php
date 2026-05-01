<?php

namespace App\Providers;

use App\Models\Area;
use App\Models\Empleado;
use App\Models\Puesto;
use App\Models\Rol;
use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Support\ServiceProvider;

class CoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $coreModels = [
            'empleado' => Empleado::class,
            'puesto' => Puesto::class,
            'sucursal' => Sucursal::class,
            'area' => Area::class,
            'user' => User::class,
            'rol' => Rol::class,
        ];

        $this->app->instance('core.models', $coreModels);
    }

    public function boot(): void
    {
        //
    }
}
