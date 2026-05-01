<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Previene queries N+1 en producción
        \Illuminate\Database\Eloquent\Model::preventLazyLoading(app()->isProduction());
    }
}
