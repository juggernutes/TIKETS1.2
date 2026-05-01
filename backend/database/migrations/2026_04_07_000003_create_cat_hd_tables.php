<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── SCHEMA: cat ──────────────────────────────────────────────────────
        Schema::create('cat.tipo_articulo', function (Blueprint $table) {
            $table->integer('IdTipoArticulo')->autoIncrement();
            $table->string('Nombre', 60)->unique();
            $table->string('Medida', 30)->nullable();
            $table->boolean('Activo')->default(true);
            $table->dateTimeTz('created_at', 0)->useCurrent();
            $table->dateTimeTz('updated_at', 0)->useCurrent();
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();

            $table->foreign('created_by')->references('ID_Usuario')->on('core.usuario')->nullOnDelete();
            $table->foreign('updated_by')->references('ID_Usuario')->on('core.usuario')->nullOnDelete();
        });

        Schema::create('cat.linea_articulo', function (Blueprint $table) {
            $table->integer('IdLineaArticulo')->autoIncrement();
            $table->string('Nombre', 60)->unique();
            $table->string('Descripcion', 200)->nullable();
            $table->boolean('Activo')->default(true);
            $table->dateTimeTz('created_at', 0)->useCurrent();
            $table->dateTimeTz('updated_at', 0)->useCurrent();
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();

            $table->foreign('created_by')->references('ID_Usuario')->on('core.usuario')->nullOnDelete();
            $table->foreign('updated_by')->references('ID_Usuario')->on('core.usuario')->nullOnDelete();
        });

        Schema::create('cat.grupo_articulo', function (Blueprint $table) {
            $table->integer('IdGrupoArticulo')->autoIncrement();
            $table->integer('IdLineaArticulo');
            $table->string('Nombre', 60);
            $table->string('Descripcion', 200)->nullable();
            $table->boolean('Activo')->default(true);
            $table->dateTimeTz('created_at', 0)->useCurrent();
            $table->dateTimeTz('updated_at', 0)->useCurrent();
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();

            $table->foreign('IdLineaArticulo')->references('IdLineaArticulo')->on('cat.linea_articulo');
            $table->foreign('created_by')->references('ID_Usuario')->on('core.usuario')->nullOnDelete();
            $table->foreign('updated_by')->references('ID_Usuario')->on('core.usuario')->nullOnDelete();
        });

        Schema::create('cat.articulo', function (Blueprint $table) {
            $table->integer('IdArticulo')->autoIncrement();
            $table->string('Nombre', 120);
            $table->string('NombreCorto', 40)->nullable();
            $table->integer('IdTipoArticulo');
            $table->decimal('Peso', 12, 3)->nullable();
            $table->boolean('Bloqueado')->default(false);
            $table->integer('IdGrupoArticulo');
            $table->boolean('Activo')->default(true);
            $table->dateTimeTz('created_at', 0)->useCurrent();
            $table->dateTimeTz('updated_at', 0)->useCurrent();
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();

            $table->foreign('IdTipoArticulo')->references('IdTipoArticulo')->on('cat.tipo_articulo');
            $table->foreign('IdGrupoArticulo')->references('IdGrupoArticulo')->on('cat.grupo_articulo');
            $table->foreign('created_by')->references('ID_Usuario')->on('core.usuario')->nullOnDelete();
            $table->foreign('updated_by')->references('ID_Usuario')->on('core.usuario')->nullOnDelete();
        });

        Schema::create('cat.sistema', function (Blueprint $table) {
            $table->integer('ID_Sistema')->autoIncrement();
            $table->integer('ID_Area');
            $table->string('Nombre', 100);
            $table->text('Descripcion')->nullable();
            $table->boolean('Activo')->default(true);

            $table->foreign('ID_Area')->references('ID_Area')->on('core.area');
        });

        // ── SCHEMA: hd ──────────────────────────────────────────────────────
        Schema::create('hd.tipo_error', function (Blueprint $table) {
            $table->integer('ID_TipoError')->autoIncrement();
            $table->string('Nombre', 60)->unique();
            $table->string('Descripcion', 200)->nullable();
            $table->boolean('Activo')->default(true);
        });

        Schema::create('hd.estatus', function (Blueprint $table) {
            $table->integer('ID_Estatus')->autoIncrement();
            $table->string('Nombre', 50)->unique();
            $table->integer('Orden')->default(0);
        });

        Schema::create('hd.error', function (Blueprint $table) {
            $table->integer('ID_Error')->autoIncrement();
            $table->text('Descripcion');
            $table->integer('Tipo'); // FK a hd.tipo_error
            $table->boolean('Activo')->default(true);

            $table->foreign('Tipo')->references('ID_TipoError')->on('hd.tipo_error');
        });

        Schema::create('hd.solucion', function (Blueprint $table) {
            $table->integer('ID_Solucion')->autoIncrement();
            $table->text('Descripcion');
            $table->boolean('Activo')->default(true);
        });

        Schema::create('hd.ticket', function (Blueprint $table) {
            $table->integer('ID_Ticket')->autoIncrement();
            $table->string('SerieFolio', 20)->nullable();
            $table->integer('Numero_Empleado');
            $table->integer('ID_Area_Origen');
            $table->integer('ID_Area_Responsable');
            $table->integer('ID_Sistema');
            $table->integer('ID_Error');
            $table->text('Descripcion');
            $table->dateTimeTz('FechaReporte', 0)->useCurrent();
            $table->dateTimeTz('FechaAsignacion', 0)->nullable();
            $table->dateTimeTz('FechaSolucion', 0)->nullable();
            $table->integer('ID_Solucion')->nullable();
            $table->text('DetalleSolucion')->nullable();
            $table->integer('ID_Soporte')->nullable();
            $table->integer('ID_Estatus');
            $table->integer('ID_Usuario_Cierra')->nullable();
            $table->boolean('Activo')->default(true);
            $table->dateTimeTz('FechaCreacion', 0)->useCurrent();
            $table->dateTimeTz('FechaModificacion', 0)->nullable();

            $table->foreign('Numero_Empleado')->references('Numero_Empleado')->on('core.empleado');
            $table->foreign('ID_Area_Origen')->references('ID_Area')->on('core.area');
            $table->foreign('ID_Area_Responsable')->references('ID_Area')->on('core.area');
            $table->foreign('ID_Sistema')->references('ID_Sistema')->on('cat.sistema');
            $table->foreign('ID_Error')->references('ID_Error')->on('hd.error');
            $table->foreign('ID_Solucion')->references('ID_Solucion')->on('hd.solucion')->nullOnDelete();
            $table->foreign('ID_Soporte')->references('ID_Usuario')->on('core.usuario')->nullOnDelete();
            $table->foreign('ID_Estatus')->references('ID_Estatus')->on('hd.estatus');
            $table->foreign('ID_Usuario_Cierra')->references('ID_Usuario')->on('core.usuario')->nullOnDelete();
        });

        Schema::create('hd.ticket_asignacion_area', function (Blueprint $table) {
            $table->integer('ID_Asignacion')->autoIncrement();
            $table->integer('ID_Ticket');
            $table->integer('ID_Area');
            $table->dateTimeTz('FechaAsignacion', 0)->useCurrent();
            $table->integer('ID_UsuarioAsigno')->nullable();
            $table->boolean('Activa')->default(true);

            $table->foreign('ID_Ticket')->references('ID_Ticket')->on('hd.ticket')->cascadeOnDelete();
            $table->foreign('ID_Area')->references('ID_Area')->on('core.area');
            $table->foreign('ID_UsuarioAsigno')->references('ID_Usuario')->on('core.usuario')->nullOnDelete();
        });

        Schema::create('hd.comentario', function (Blueprint $table) {
            $table->integer('ID_Comentario')->autoIncrement();
            $table->integer('ID_Ticket');
            $table->integer('ID_Usuario');
            $table->text('Mensaje');
            $table->boolean('EsInterno')->default(false);
            $table->dateTimeTz('Fecha', 0)->useCurrent();

            $table->foreign('ID_Ticket')->references('ID_Ticket')->on('hd.ticket')->cascadeOnDelete();
            $table->foreign('ID_Usuario')->references('ID_Usuario')->on('core.usuario');
        });

        Schema::create('hd.encuesta_ticket', function (Blueprint $table) {
            $table->integer('ID_Encuesta')->autoIncrement();
            $table->integer('ID_Ticket')->unique();
            $table->integer('Calificacion')->nullable();
            $table->text('Comentarios')->nullable();
            $table->dateTimeTz('Fecha', 0)->useCurrent();
            $table->integer('ID_Usuario')->nullable();

            $table->foreign('ID_Ticket')->references('ID_Ticket')->on('hd.ticket')->cascadeOnDelete();
            $table->foreign('ID_Usuario')->references('ID_Usuario')->on('core.usuario')->nullOnDelete();
        });

        // Índices hd
        DB::statement('CREATE INDEX IX_hd_tkt_Empleado    ON hd.ticket(Numero_Empleado)');
        DB::statement('CREATE INDEX IX_hd_tkt_Estatus     ON hd.ticket(ID_Estatus)');
        DB::statement('CREATE INDEX IX_hd_tkt_Area_Resp   ON hd.ticket(ID_Area_Responsable)');
        DB::statement('CREATE INDEX IX_hd_tkt_FechaReport ON hd.ticket(FechaReporte)');
        DB::statement('CREATE INDEX IX_hd_com_Ticket      ON hd.comentario(ID_Ticket)');
    }

    public function down(): void
    {
        $tables = [
            'hd.encuesta_ticket', 'hd.comentario', 'hd.ticket_asignacion_area',
            'hd.ticket', 'hd.solucion', 'hd.error', 'hd.estatus', 'hd.tipo_error',
            'cat.sistema', 'cat.articulo', 'cat.grupo_articulo',
            'cat.linea_articulo', 'cat.tipo_articulo',
        ];
        foreach ($tables as $t) {
            Schema::dropIfExists($t);
        }
    }
};
