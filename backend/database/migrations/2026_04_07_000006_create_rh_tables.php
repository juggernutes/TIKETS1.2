<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rh.fuente_reclutamiento', function (Blueprint $table) {
            $table->integer('ID_Fuente')->autoIncrement();
            $table->string('Nombre', 80)->unique();
            $table->string('Descripcion', 200)->nullable();
            $table->boolean('Activo')->default(true);
        });

        Schema::create('rh.estatus_candidato', function (Blueprint $table) {
            $table->integer('ID_EstatusCandidato')->autoIncrement();
            $table->string('Nombre', 50)->unique();
            $table->string('Descripcion', 200)->nullable();
            $table->integer('OrdenProceso')->nullable();
            $table->boolean('Activo')->default(true);
            $table->dateTimeTz('FechaCreacion', 0)->useCurrent();
            $table->dateTimeTz('FechaActualizacion', 0)->nullable();
        });

        Schema::create('rh.vacante', function (Blueprint $table) {
            $table->integer('ID_Vacante')->autoIncrement();
            $table->string('Folio', 20)->nullable();
            $table->string('Titulo', 100);
            $table->text('Descripcion')->nullable();
            $table->text('Perfil')->nullable();
            $table->text('Requisitos')->nullable();
            $table->decimal('SalarioMin', 12, 2)->nullable();
            $table->decimal('SalarioMax', 12, 2)->nullable();
            $table->integer('NumeroPosiciones')->default(1);
            $table->dateTimeTz('FechaPublicacion', 0)->useCurrent();
            $table->dateTimeTz('FechaCierre', 0)->nullable();
            $table->integer('ID_Area')->nullable();
            $table->integer('ID_Puesto')->nullable();
            $table->integer('ID_Sucursal')->nullable();
            $table->integer('ID_UsuarioSolicita')->nullable();
            $table->integer('ID_UsuarioResponsable')->nullable();
            $table->string('Estatus', 20)->default('ABIERTA');
            $table->boolean('Activo')->default(true);
            $table->dateTimeTz('FechaCreacion', 0)->useCurrent();
            $table->dateTimeTz('FechaModificacion', 0)->nullable();

            $table->foreign('ID_Area')->references('ID_Area')->on('core.area')->nullOnDelete();
            $table->foreign('ID_Puesto')->references('ID_Puesto')->on('core.puesto')->nullOnDelete();
            $table->foreign('ID_Sucursal')->references('ID_Sucursal')->on('core.sucursal')->nullOnDelete();
            $table->foreign('ID_UsuarioSolicita')->references('ID_Usuario')->on('core.usuario')->nullOnDelete();
            $table->foreign('ID_UsuarioResponsable')->references('ID_Usuario')->on('core.usuario')->nullOnDelete();
        });

        DB::statement('CREATE UNIQUE INDEX UQ_rh_vac_Folio ON rh.vacante(Folio) WHERE Folio IS NOT NULL');

        Schema::create('rh.vacante_log', function (Blueprint $table) {
            $table->bigInteger('ID_Log')->autoIncrement();
            $table->integer('ID_Vacante');
            $table->string('EstadoAnterior', 20)->nullable();
            $table->string('EstadoNuevo', 20);
            $table->string('Comentario', 500)->nullable();
            $table->integer('ID_Usuario')->nullable();
            $table->dateTimeTz('Fecha', 0)->useCurrent();

            $table->foreign('ID_Vacante')->references('ID_Vacante')->on('rh.vacante')->cascadeOnDelete();
            $table->foreign('ID_Usuario')->references('ID_Usuario')->on('core.usuario')->nullOnDelete();
        });

        Schema::create('rh.candidato', function (Blueprint $table) {
            $table->integer('ID_Candidato')->autoIncrement();
            $table->integer('ID_Vacante');
            $table->integer('ID_EstatusCandidato');
            $table->string('Nombre', 100);
            $table->string('ApellidoPaterno', 100)->nullable();
            $table->string('ApellidoMaterno', 100)->nullable();
            $table->string('Correo', 150)->nullable();
            $table->string('Telefono', 20)->nullable();
            $table->string('TelefonoAlterno', 20)->nullable();
            $table->date('FechaNacimiento')->nullable();
            $table->string('Genero', 20)->nullable();
            $table->string('RFC', 13)->nullable();
            $table->string('CURP', 18)->nullable();
            $table->string('Escolaridad', 100)->nullable();
            $table->string('Profesion', 100)->nullable();
            $table->text('ExperienciaResumen')->nullable();
            $table->string('CV_URL', 500)->nullable();
            $table->string('LinkedIn_URL', 500)->nullable();
            $table->string('Fuente', 50)->nullable();
            $table->decimal('PretensionSalarial', 12, 2)->nullable();
            $table->dateTimeTz('FechaPostulacion', 0)->useCurrent();
            $table->text('Observaciones')->nullable();
            $table->boolean('Activo')->default(true);
            $table->dateTimeTz('FechaCreacion', 0)->useCurrent();
            $table->dateTimeTz('FechaModificacion', 0)->nullable();

            $table->foreign('ID_Vacante')->references('ID_Vacante')->on('rh.vacante')->cascadeOnDelete();
            $table->foreign('ID_EstatusCandidato')->references('ID_EstatusCandidato')->on('rh.estatus_candidato');
        });

        DB::statement('CREATE UNIQUE INDEX UQ_rh_can_Vacante_Correo ON rh.candidato(ID_Vacante, Correo) WHERE Correo IS NOT NULL');

        Schema::create('rh.candidato_log', function (Blueprint $table) {
            $table->bigInteger('ID_Log')->autoIncrement();
            $table->integer('ID_Candidato');
            $table->integer('ID_EstatusAnterior')->nullable();
            $table->integer('ID_EstatusNuevo');
            $table->string('Comentario', 500)->nullable();
            $table->integer('ID_Usuario')->nullable();
            $table->dateTimeTz('Fecha', 0)->useCurrent();

            $table->foreign('ID_Candidato')->references('ID_Candidato')->on('rh.candidato')->cascadeOnDelete();
            $table->foreign('ID_EstatusAnterior')->references('ID_EstatusCandidato')->on('rh.estatus_candidato')->nullOnDelete();
            $table->foreign('ID_EstatusNuevo')->references('ID_EstatusCandidato')->on('rh.estatus_candidato');
            $table->foreign('ID_Usuario')->references('ID_Usuario')->on('core.usuario')->nullOnDelete();
        });

        Schema::create('rh.entrevista', function (Blueprint $table) {
            $table->integer('ID_Entrevista')->autoIncrement();
            $table->integer('ID_Candidato');
            $table->integer('ID_Vacante');
            $table->integer('ID_UsuarioEntrevistador')->nullable();
            $table->string('TipoEntrevista', 20)->default('PRESENCIAL');
            $table->dateTimeTz('FechaEntrevista', 0);
            $table->integer('DuracionMinutos')->nullable();
            $table->string('Ubicacion', 200)->nullable();
            $table->string('Medio', 100)->nullable();
            $table->string('Resultado', 20)->nullable();
            $table->decimal('Calificacion', 5, 2)->nullable();
            $table->text('Comentarios')->nullable();
            $table->boolean('Activo')->default(true);
            $table->dateTimeTz('FechaCreacion', 0)->useCurrent();
            $table->dateTimeTz('FechaModificacion', 0)->nullable();

            $table->foreign('ID_Candidato')->references('ID_Candidato')->on('rh.candidato')->cascadeOnDelete();
            $table->foreign('ID_Vacante')->references('ID_Vacante')->on('rh.vacante');
            $table->foreign('ID_UsuarioEntrevistador')->references('ID_Usuario')->on('core.usuario')->nullOnDelete();
        });

        Schema::create('rh.entrevista_evaluador', function (Blueprint $table) {
            $table->integer('ID_EvalEntrevista')->autoIncrement();
            $table->integer('ID_Entrevista');
            $table->integer('ID_Usuario');
            $table->string('Rol', 50)->nullable();
            $table->decimal('Calificacion', 5, 2)->nullable();
            $table->text('Comentarios')->nullable();
            $table->dateTimeTz('FechaEvaluacion', 0)->nullable();

            $table->unique(['ID_Entrevista', 'ID_Usuario']);
            $table->foreign('ID_Entrevista')->references('ID_Entrevista')->on('rh.entrevista')->cascadeOnDelete();
            $table->foreign('ID_Usuario')->references('ID_Usuario')->on('core.usuario');
        });

        Schema::create('rh.oferta_laboral', function (Blueprint $table) {
            $table->integer('ID_Oferta')->autoIncrement();
            $table->integer('ID_Candidato');
            $table->integer('ID_Vacante');
            $table->decimal('SalarioOfertado', 12, 2);
            $table->dateTimeTz('FechaOferta', 0)->useCurrent();
            $table->dateTimeTz('FechaVencimiento', 0)->nullable();
            $table->dateTimeTz('FechaRespuesta', 0)->nullable();
            $table->string('Estatus', 20)->default('ENVIADA');
            $table->string('MotivoRechazo', 300)->nullable();
            $table->decimal('Contrapropuesta', 12, 2)->nullable();
            $table->date('FechaIngreso')->nullable();
            $table->boolean('Activo')->default(true);
            $table->dateTimeTz('FechaCreacion', 0)->useCurrent();
            $table->dateTimeTz('FechaModificacion', 0)->nullable();

            $table->foreign('ID_Candidato')->references('ID_Candidato')->on('rh.candidato')->cascadeOnDelete();
            $table->foreign('ID_Vacante')->references('ID_Vacante')->on('rh.vacante');
        });

        Schema::create('rh.documento_candidato', function (Blueprint $table) {
            $table->integer('ID_DocumentoCandidato')->autoIncrement();
            $table->integer('ID_Candidato');
            $table->integer('ID_Vacante');
            $table->string('TipoDocumento', 50);
            $table->string('NombreArchivo', 255);
            $table->string('RutaArchivo', 500)->nullable();
            $table->string('Extension', 10)->nullable();
            $table->bigInteger('TamanoBytes')->nullable();
            $table->string('Observaciones', 300)->nullable();
            $table->dateTimeTz('FechaCarga', 0)->useCurrent();
            $table->boolean('Activo')->default(true);
            $table->dateTimeTz('FechaCreacion', 0)->useCurrent();
            $table->dateTimeTz('FechaModificacion', 0)->nullable();
            $table->integer('ID_UsuarioCreo')->nullable();

            $table->foreign('ID_Candidato')->references('ID_Candidato')->on('rh.candidato')->cascadeOnDelete();
            $table->foreign('ID_Vacante')->references('ID_Vacante')->on('rh.vacante');
            $table->foreign('ID_UsuarioCreo')->references('ID_Usuario')->on('core.usuario')->nullOnDelete();
        });

        // Índices rh
        DB::statement('CREATE INDEX IX_rh_vac_Estatus   ON rh.vacante(Estatus)');
        DB::statement('CREATE INDEX IX_rh_vac_Area      ON rh.vacante(ID_Area)    WHERE ID_Area    IS NOT NULL');
        DB::statement('CREATE INDEX IX_rh_vac_Sucursal  ON rh.vacante(ID_Sucursal) WHERE ID_Sucursal IS NOT NULL');
        DB::statement('CREATE INDEX IX_rh_can_Vacante   ON rh.candidato(ID_Vacante)');
        DB::statement('CREATE INDEX IX_rh_can_Estatus   ON rh.candidato(ID_EstatusCandidato)');
        DB::statement('CREATE INDEX IX_rh_ent_Candidato ON rh.entrevista(ID_Candidato)');
        DB::statement('CREATE INDEX IX_rh_ent_Vacante   ON rh.entrevista(ID_Vacante)');
    }

    public function down(): void
    {
        $tables = [
            'rh.documento_candidato', 'rh.oferta_laboral',
            'rh.entrevista_evaluador', 'rh.entrevista',
            'rh.candidato_log', 'rh.candidato', 'rh.vacante_log',
            'rh.vacante', 'rh.estatus_candidato', 'rh.fuente_reclutamiento',
        ];
        foreach ($tables as $t) {
            Schema::dropIfExists($t);
        }
    }
};
