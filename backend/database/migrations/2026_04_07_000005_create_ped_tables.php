<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ped.capacidaduv', function (Blueprint $table) {
            $table->integer('IdCapacidadUV')->autoIncrement();
            $table->string('Nombre', 80)->unique();
            $table->string('Descripcion', 200)->nullable();
            $table->integer('CapacidadMaxima')->nullable();
            $table->integer('CapacidadMinima')->nullable();
            $table->boolean('activo')->default(true);
            $table->dateTimeTz('created_at', 0)->useCurrent();
            $table->dateTimeTz('updated_at', 0)->useCurrent();
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();

            $table->foreign('created_by')->references('ID_Usuario')->on('core.usuario')->nullOnDelete();
            $table->foreign('updated_by')->references('ID_Usuario')->on('core.usuario')->nullOnDelete();
        });

        Schema::create('ped.tipounidad', function (Blueprint $table) {
            $table->integer('IdTipoUnidad')->autoIncrement();
            $table->string('Nombre', 80)->unique();
            $table->string('Descripcion', 200)->nullable();
            $table->boolean('activo')->default(true);
            $table->dateTimeTz('created_at', 0)->useCurrent();
            $table->dateTimeTz('updated_at', 0)->useCurrent();
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();

            $table->foreign('created_by')->references('ID_Usuario')->on('core.usuario')->nullOnDelete();
            $table->foreign('updated_by')->references('ID_Usuario')->on('core.usuario')->nullOnDelete();
        });

        Schema::create('ped.unidadoperacional', function (Blueprint $table) {
            $table->integer('IdUnidad')->autoIncrement();
            $table->integer('IdTipoUnidad');
            $table->integer('IdUsuario');
            $table->integer('IdSupervisor')->nullable();
            $table->integer('IdSucursal')->nullable();
            $table->integer('IdCapacidadUV');
            $table->string('Nombre', 100);
            $table->string('Descripcion', 200)->nullable();
            $table->boolean('activo')->default(true);
            $table->dateTimeTz('created_at', 0)->useCurrent();
            $table->dateTimeTz('updated_at', 0)->useCurrent();
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();

            $table->foreign('IdTipoUnidad')->references('IdTipoUnidad')->on('ped.tipounidad');
            $table->foreign('IdUsuario')->references('ID_Usuario')->on('core.usuario');
            $table->foreign('IdSupervisor')->references('IdUnidad')->on('ped.unidadoperacional')->nullOnDelete();
            $table->foreign('IdSucursal')->references('ID_Sucursal')->on('core.sucursal')->nullOnDelete();
            $table->foreign('IdCapacidadUV')->references('IdCapacidadUV')->on('ped.capacidaduv');
            $table->foreign('created_by')->references('ID_Usuario')->on('core.usuario')->nullOnDelete();
            $table->foreign('updated_by')->references('ID_Usuario')->on('core.usuario')->nullOnDelete();
        });

        Schema::create('ped.estado_pedido', function (Blueprint $table) {
            $table->integer('IdEstado')->autoIncrement();
            $table->string('Nombre', 60)->unique();
            $table->string('Descripcion', 200)->nullable();
            $table->boolean('activo')->default(true);
            $table->dateTimeTz('created_at', 0)->useCurrent();
            $table->dateTimeTz('updated_at', 0)->useCurrent();
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();

            $table->foreign('created_by')->references('ID_Usuario')->on('core.usuario')->nullOnDelete();
            $table->foreign('updated_by')->references('ID_Usuario')->on('core.usuario')->nullOnDelete();
        });

        Schema::create('ped.pedidos', function (Blueprint $table) {
            $table->integer('IdPedido')->autoIncrement();
            $table->string('FolioPedido', 40)->nullable();
            $table->integer('IdEstado');
            $table->integer('IdUnidadPedido');
            $table->integer('IdSupervisor');
            $table->integer('IdAlmacen');
            $table->integer('Registros')->nullable();
            $table->string('Dia', 2)->nullable();
            $table->tinyInteger('Semana')->unsigned()->nullable();
            $table->integer('ID_Semana')->nullable();
            $table->decimal('PedVolPed', 12, 3)->nullable();
            $table->decimal('PedVolApr', 12, 3)->nullable();
            $table->decimal('PedVolSur', 12, 3)->nullable();
            $table->dateTimeTz('FechaPedido', 0)->nullable();
            $table->dateTimeTz('FechaAutorizacion', 0)->nullable();
            $table->dateTimeTz('FechaSurtido', 0)->nullable();
            $table->string('ObserVen', 200)->nullable();
            $table->string('ObserSup', 200)->nullable();
            $table->string('ObserAlm', 200)->nullable();
            $table->boolean('activo')->default(true);
            $table->dateTimeTz('created_at', 0)->useCurrent();
            $table->dateTimeTz('updated_at', 0)->useCurrent();
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();

            $table->foreign('IdEstado')->references('IdEstado')->on('ped.estado_pedido');
            $table->foreign('IdUnidadPedido')->references('IdUnidad')->on('ped.unidadoperacional');
            $table->foreign('IdSupervisor')->references('IdUnidad')->on('ped.unidadoperacional');
            $table->foreign('IdAlmacen')->references('IdUnidad')->on('ped.unidadoperacional');
            $table->foreign('ID_Semana')->references('ID_Semana')->on('com.semana')->nullOnDelete();
            $table->foreign('created_by')->references('ID_Usuario')->on('core.usuario')->nullOnDelete();
            $table->foreign('updated_by')->references('ID_Usuario')->on('core.usuario')->nullOnDelete();
        });

        Schema::create('ped.pedido_detalle', function (Blueprint $table) {
            $table->integer('IdPedido');
            $table->integer('IdArticulo');
            $table->smallInteger('Registro');
            $table->integer('CanPzPed');
            $table->decimal('VolPed', 12, 3);
            $table->integer('CanPzApr')->nullable();
            $table->decimal('VolApr', 12, 3)->nullable();
            $table->integer('CanPzSur')->nullable();
            $table->decimal('VolSur', 12, 3)->nullable();
            $table->boolean('activo')->default(true);
            $table->dateTimeTz('created_at', 0)->useCurrent();
            $table->dateTimeTz('updated_at', 0)->useCurrent();
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();

            $table->primary(['IdPedido', 'Registro', 'IdArticulo']);
            $table->foreign('IdPedido')->references('IdPedido')->on('ped.pedidos')->cascadeOnDelete();
            $table->foreign('IdArticulo')->references('IdArticulo')->on('cat.articulo');
            $table->foreign('created_by')->references('ID_Usuario')->on('core.usuario')->nullOnDelete();
            $table->foreign('updated_by')->references('ID_Usuario')->on('core.usuario')->nullOnDelete();
        });

        Schema::create('ped.pedido_estado_log', function (Blueprint $table) {
            $table->bigInteger('IdLog')->autoIncrement();
            $table->integer('IdPedido');
            $table->integer('IdEstado');
            $table->integer('CambioPor')->nullable();
            $table->string('Notas', 500)->nullable();
            $table->dateTimeTz('created_at', 0)->useCurrent();

            $table->foreign('IdPedido')->references('IdPedido')->on('ped.pedidos')->cascadeOnDelete();
            $table->foreign('IdEstado')->references('IdEstado')->on('ped.estado_pedido');
            $table->foreign('CambioPor')->references('ID_Usuario')->on('core.usuario')->nullOnDelete();
        });

        // Índices ped
        DB::statement('CREATE UNIQUE INDEX UQ_ped_ped_Folio ON ped.pedidos(FolioPedido) WHERE FolioPedido IS NOT NULL');
        DB::statement('CREATE INDEX IX_ped_ped_IdEstado    ON ped.pedidos(IdEstado)');
        DB::statement('CREATE INDEX IX_ped_ped_IdUnidad    ON ped.pedidos(IdUnidadPedido)');
        DB::statement('CREATE INDEX IX_ped_ped_FechaPedido ON ped.pedidos(FechaPedido)');
        DB::statement('CREATE INDEX IX_ped_log_IdPedido    ON ped.pedido_estado_log(IdPedido)');
        DB::statement('CREATE INDEX IX_ped_det_IdArticulo  ON ped.pedido_detalle(IdArticulo)');
    }

    public function down(): void
    {
        $tables = [
            'ped.pedido_estado_log', 'ped.pedido_detalle', 'ped.pedidos',
            'ped.estado_pedido', 'ped.unidadoperacional',
            'ped.tipounidad', 'ped.capacidaduv',
        ];
        foreach ($tables as $t) {
            Schema::dropIfExists($t);
        }
    }
};
