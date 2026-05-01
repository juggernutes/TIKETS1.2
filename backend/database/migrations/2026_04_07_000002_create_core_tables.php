<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('core.rol', function (Blueprint $table) {
            $table->integer('ID_Rol')->autoIncrement();
            $table->string('Nombre', 50)->unique();
            $table->boolean('Activo')->default(true);
        });

        Schema::create('core.permiso', function (Blueprint $table) {
            $table->integer('ID_Permiso')->autoIncrement();
            $table->string('Clave', 50)->unique();
            $table->string('Nombre', 100);
            $table->string('Modulo', 30);
            $table->string('Descripcion', 200)->nullable();
            $table->boolean('Activo')->default(true);
        });

        Schema::create('core.area', function (Blueprint $table) {
            $table->integer('ID_Area')->autoIncrement();
            $table->string('Nombre', 50)->unique();
            $table->string('Serie', 3)->unique();
            $table->boolean('Activo')->default(true);
        });

        Schema::create('core.puesto', function (Blueprint $table) {
            $table->integer('ID_Puesto')->autoIncrement();
            $table->string('Clave', 9)->unique();
            $table->string('Descripcion', 100);
            $table->integer('Nivel');
            $table->string('Categoria', 1);
            $table->string('Segmento', 3);
            $table->string('Responsabilidad', 1);
            $table->boolean('Activo')->default(true);
        });

        Schema::create('core.sucursal', function (Blueprint $table) {
            $table->integer('ID_Sucursal')->primary(); // PK desde ERP, sin autoincrement
            $table->string('Nombre', 100)->unique();
            $table->string('Ciudad', 100)->nullable();
            $table->boolean('Activo')->default(true);
        });

        Schema::create('core.empleado', function (Blueprint $table) {
            $table->integer('Numero_Empleado')->primary(); // PK desde ERP
            $table->string('Nombre', 100);
            $table->string('Correo', 100)->nullable();
            $table->string('Extension', 10)->nullable();
            $table->string('Telefono', 20)->nullable();
            $table->string('UsuarioAnyDesk', 255)->nullable();
            $table->string('ClaveAnyDesk_Enc', 512)->nullable(); // AES-256 en aplicación
            $table->integer('ID_Sucursal')->nullable();
            $table->integer('ID_Puesto')->nullable();
            $table->integer('ID_Area')->nullable();
            $table->boolean('Activo')->default(true);
            $table->dateTimeTz('FechaCreacion', 0)->useCurrent();
            $table->dateTimeTz('FechaModificacion', 0)->nullable();

            $table->foreign('ID_Sucursal')->references('ID_Sucursal')->on('core.sucursal')->nullOnDelete();
            $table->foreign('ID_Puesto')->references('ID_Puesto')->on('core.puesto')->nullOnDelete();
            $table->foreign('ID_Area')->references('ID_Area')->on('core.area')->nullOnDelete();
        });

        Schema::create('core.usuario', function (Blueprint $table) {
            $table->integer('ID_Usuario')->autoIncrement();
            $table->string('Nombre', 50);
            $table->string('Email', 100)->unique();
            $table->string('FotoURL', 255)->nullable();
            $table->integer('ID_Rol')->nullable();
            $table->integer('ID_Area')->nullable();
            $table->boolean('Activo')->default(true);
            $table->dateTimeTz('FechaCreacion', 0)->useCurrent();
            $table->dateTimeTz('FechaModificacion', 0)->nullable();

            $table->foreign('ID_Rol')->references('ID_Rol')->on('core.rol')->nullOnDelete();
            $table->foreign('ID_Area')->references('ID_Area')->on('core.area')->nullOnDelete();
        });

        Schema::create('core.rol_permiso', function (Blueprint $table) {
            $table->integer('ID_Rol');
            $table->integer('ID_Permiso');
            $table->boolean('Activo')->default(true);
            $table->dateTimeTz('FechaCreacion', 0)->useCurrent();
            $table->integer('AsignadoPor')->nullable();

            $table->primary(['ID_Rol', 'ID_Permiso']);
            $table->foreign('ID_Rol')->references('ID_Rol')->on('core.rol')->cascadeOnDelete();
            $table->foreign('ID_Permiso')->references('ID_Permiso')->on('core.permiso')->cascadeOnDelete();
            $table->foreign('AsignadoPor')->references('ID_Usuario')->on('core.usuario')->nullOnDelete();
        });

        Schema::create('core.login', function (Blueprint $table) {
            $table->integer('ID_Login')->autoIncrement();
            $table->string('Cuenta', 70)->unique();
            $table->string('PasswordHash', 512);
            $table->integer('IntentosFallidos')->default(0);
            $table->dateTimeTz('FechaUltimoIntento', 0)->nullable();
            $table->integer('ID_Usuario');
            $table->string('SesionID', 100)->nullable();
            $table->boolean('Activo')->default(true);
            $table->boolean('DebeCambiarPassword')->default(true);
            $table->dateTimeTz('UltimoCambioPassword', 0)->nullable();
            $table->dateTimeTz('FechaCreacion', 0)->useCurrent();
            $table->dateTimeTz('FechaModificacion', 0)->nullable();

            $table->foreign('ID_Usuario')->references('ID_Usuario')->on('core.usuario')->cascadeOnDelete();
        });

        Schema::create('core.password_resets', function (Blueprint $table) {
            $table->integer('ID')->autoIncrement();
            $table->integer('ID_Usuario');
            $table->string('Token', 64)->unique();
            $table->dateTimeTz('ExpiresAt', 0);
            $table->boolean('Used')->default(false);
            $table->dateTimeTz('CreatedAt', 0)->useCurrent();

            $table->foreign('ID_Usuario')->references('ID_Usuario')->on('core.usuario')->cascadeOnDelete();
        });

        Schema::create('core.tipo_equipo', function (Blueprint $table) {
            $table->integer('ID_TipoEquipo')->autoIncrement();
            $table->string('Nombre', 50)->unique();
            $table->string('Descripcion', 200)->nullable();
            $table->boolean('Activo')->default(true);
        });

        Schema::create('core.equipo', function (Blueprint $table) {
            $table->integer('ID_Equipo')->autoIncrement();
            $table->integer('ID_TipoEquipo');
            $table->string('Marca', 50)->nullable();
            $table->string('Modelo', 50)->nullable();
            $table->string('NumeroSerie', 100)->nullable();
            $table->string('IPDireccion', 45)->nullable();
            $table->string('MacDireccion', 17)->nullable();
            $table->string('NuActvoFijo', 30)->nullable();
            $table->string('SistemaOperativo', 50)->nullable();
            $table->text('Descripcion')->nullable();
            $table->date('FechaCompra')->nullable();
            $table->string('ClaveUsuarioWindows_Enc', 512)->nullable(); // AES-256 en aplicación
            $table->boolean('Activo')->default(true);
            $table->dateTimeTz('FechaCreacion', 0)->useCurrent();

            $table->foreign('ID_TipoEquipo')->references('ID_TipoEquipo')->on('core.tipo_equipo');
        });

        Schema::create('core.proveedor', function (Blueprint $table) {
            $table->integer('ID_Proveedor')->autoIncrement();
            $table->string('Nombre', 50);
            $table->string('Correo', 100);
            $table->string('Telefono', 20)->nullable();
            $table->integer('ID_Usuario')->nullable();
            $table->boolean('Activo')->default(true);
            $table->dateTimeTz('FechaCreacion', 0)->useCurrent();
            $table->dateTimeTz('FechaModificacion', 0)->nullable();

            $table->foreign('ID_Usuario')->references('ID_Usuario')->on('core.usuario')->nullOnDelete();
        });

        Schema::create('core.usuario_relacion', function (Blueprint $table) {
            $table->integer('ID_UsuarioRelacion')->autoIncrement();
            $table->integer('ID_Usuario')->unique();
            $table->integer('Numero_Empleado')->nullable()->unique();
            $table->integer('ID_Proveedor')->nullable()->unique();
            $table->boolean('Activo')->default(true);

            $table->foreign('ID_Usuario')->references('ID_Usuario')->on('core.usuario')->cascadeOnDelete();
            $table->foreign('Numero_Empleado')->references('Numero_Empleado')->on('core.empleado')->nullOnDelete();
            $table->foreign('ID_Proveedor')->references('ID_Proveedor')->on('core.proveedor')->nullOnDelete();
        });

        Schema::create('core.folio_area_diario', function (Blueprint $table) {
            $table->integer('ID_Area');
            $table->date('Fecha');
            $table->integer('Consecutivo')->default(0);

            $table->primary(['ID_Area', 'Fecha']);
            $table->foreign('ID_Area')->references('ID_Area')->on('core.area')->cascadeOnDelete();
        });

        // Índices adicionales
        DB::statement('CREATE UNIQUE INDEX UQ_core_emp_Correo ON core.empleado(Correo) WHERE Correo IS NOT NULL');
        DB::statement('CREATE INDEX IX_core_usuario_rol ON core.usuario(ID_Rol) WHERE ID_Rol IS NOT NULL');
        DB::statement('CREATE INDEX IX_core_pr_ID_Usuario ON core.password_resets(ID_Usuario)');
    }

    public function down(): void
    {
        $tables = [
            'core.folio_area_diario', 'core.usuario_relacion', 'core.proveedor',
            'core.equipo', 'core.tipo_equipo', 'core.password_resets',
            'core.login', 'core.rol_permiso', 'core.usuario',
            'core.empleado', 'core.sucursal', 'core.puesto',
            'core.area', 'core.permiso', 'core.rol',
        ];
        foreach ($tables as $t) {
            Schema::dropIfExists($t);
        }
    }
};
