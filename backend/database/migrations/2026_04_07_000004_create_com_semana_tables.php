<?php
// IMPORTANTE: com.semana debe crearse ANTES que ped.pedidos
// porque ped.pedidos tiene FK a com.semana

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── com.meta_mes_portada ─────────────────────────────────────────────
        Schema::create('com.meta_mes_portada', function (Blueprint $table) {
            $table->integer('ID_MetaMes')->autoIncrement();
            $table->integer('Anio');
            $table->integer('Mes');
            $table->string('Nombre', 15);
            $table->integer('DiasHabiles');
            $table->dateTimeTz('FechaCreacion', 0)->useCurrent();
            $table->dateTimeTz('FechaModificacion', 0)->nullable();
            $table->integer('ID_UsuarioCreo')->nullable();
            $table->integer('ID_UsuarioModifico')->nullable();

            $table->unique(['Anio', 'Mes']);
            $table->foreign('ID_UsuarioCreo')->references('ID_Usuario')->on('core.usuario')->nullOnDelete();
            $table->foreign('ID_UsuarioModifico')->references('ID_Usuario')->on('core.usuario')->nullOnDelete();
        });

        DB::statement('ALTER TABLE com.meta_mes_portada ADD CONSTRAINT CHK_com_mmp_Mes         CHECK (Mes BETWEEN 1 AND 12)');
        DB::statement('ALTER TABLE com.meta_mes_portada ADD CONSTRAINT CHK_com_mmp_DiasHabiles CHECK (DiasHabiles BETWEEN 1 AND 31)');

        // ── com.meta_mensual_contenido ───────────────────────────────────────
        Schema::create('com.meta_mensual_contenido', function (Blueprint $table) {
            $table->integer('ID_Meta')->autoIncrement();
            $table->integer('ID_MetaMes');
            $table->integer('IdUnidad');
            $table->integer('IdLineaArticulo');
            $table->decimal('Meta', 18, 4);
            $table->decimal('Porcentaje', 18, 4);
            $table->decimal('Mezcla', 18, 4);
            $table->dateTimeTz('FechaCreacion', 0)->useCurrent();
            $table->dateTimeTz('FechaModificacion', 0)->nullable();
            $table->integer('ID_UsuarioCreo')->nullable();
            $table->integer('ID_UsuarioModifico')->nullable();

            $table->unique(['ID_MetaMes', 'IdUnidad', 'IdLineaArticulo']);
            $table->foreign('ID_MetaMes')->references('ID_MetaMes')->on('com.meta_mes_portada');
            $table->foreign('IdUnidad')->references('IdUnidad')->on('ped.unidadoperacional');
            $table->foreign('IdLineaArticulo')->references('IdLineaArticulo')->on('cat.linea_articulo');
            $table->foreign('ID_UsuarioCreo')->references('ID_Usuario')->on('core.usuario')->nullOnDelete();
            $table->foreign('ID_UsuarioModifico')->references('ID_Usuario')->on('core.usuario')->nullOnDelete();
        });

        DB::statement('ALTER TABLE com.meta_mensual_contenido ADD CONSTRAINT CHK_com_mmc_Meta CHECK (Meta >= 0)');

        // ── com.semana ───────────────────────────────────────────────────────
        Schema::create('com.semana', function (Blueprint $table) {
            $table->integer('ID_Semana')->autoIncrement();
            $table->integer('Anio');
            $table->integer('Semana');
            $table->date('FechaInicio');
            $table->date('FechaFin');
            $table->integer('ID_MetaMesInicio');
            $table->integer('ID_MetaMesFinal');
            $table->integer('DiasMesInicio');
            $table->integer('DiasMesFinal');
            $table->dateTimeTz('FechaCreacion', 0)->useCurrent();
            $table->integer('ID_UsuarioCreo')->nullable();

            $table->unique(['Anio', 'Semana']);
            $table->foreign('ID_MetaMesInicio')->references('ID_MetaMes')->on('com.meta_mes_portada');
            $table->foreign('ID_MetaMesFinal')->references('ID_MetaMes')->on('com.meta_mes_portada');
            $table->foreign('ID_UsuarioCreo')->references('ID_Usuario')->on('core.usuario')->nullOnDelete();
        });

        DB::statement('ALTER TABLE com.semana ADD CONSTRAINT CHK_com_semana_num      CHECK (Semana BETWEEN 1 AND 53)');
        DB::statement('ALTER TABLE com.semana ADD CONSTRAINT CHK_com_semana_anio     CHECK (Anio >= 2000)');
        DB::statement('ALTER TABLE com.semana ADD CONSTRAINT CHK_com_semana_fechas   CHECK (FechaFin >= FechaInicio)');
        DB::statement('ALTER TABLE com.semana ADD CONSTRAINT CHK_com_semana_dias_ini CHECK (DiasMesInicio BETWEEN 0 AND 7)');
        DB::statement('ALTER TABLE com.semana ADD CONSTRAINT CHK_com_semana_dias_fin CHECK (DiasMesFinal  BETWEEN 0 AND 7)');
        DB::statement('ALTER TABLE com.semana ADD CONSTRAINT CHK_com_semana_dias_tot CHECK ((DiasMesInicio + DiasMesFinal) BETWEEN 1 AND 7)');

        // ── com.meta_semanal ─────────────────────────────────────────────────
        Schema::create('com.meta_semanal', function (Blueprint $table) {
            $table->integer('ID_MetaSemanal')->autoIncrement();
            $table->integer('ID_Semana');
            $table->integer('IdUnidad');
            $table->integer('IdLineaArticulo');
            $table->decimal('MetaSemanal', 18, 4);
            $table->dateTimeTz('FechaCalculo', 0)->useCurrent();

            $table->unique(['ID_Semana', 'IdUnidad', 'IdLineaArticulo']);
            $table->foreign('ID_Semana')->references('ID_Semana')->on('com.semana');
            $table->foreign('IdUnidad')->references('IdUnidad')->on('ped.unidadoperacional');
            $table->foreign('IdLineaArticulo')->references('IdLineaArticulo')->on('cat.linea_articulo');
        });

        DB::statement('ALTER TABLE com.meta_semanal ADD CONSTRAINT CHK_com_ms_meta CHECK (MetaSemanal >= 0)');

        // ── Índices ──────────────────────────────────────────────────────────
        DB::statement('CREATE INDEX IX_com_mmc_ID_MetaMes     ON com.meta_mensual_contenido(ID_MetaMes)');
        DB::statement('CREATE INDEX IX_com_mmc_IdUnidad       ON com.meta_mensual_contenido(IdUnidad)');
        DB::statement('CREATE INDEX IX_com_mmc_IdLinea        ON com.meta_mensual_contenido(IdLineaArticulo)');
        DB::statement('CREATE INDEX IX_com_semana_Anio_Semana ON com.semana(Anio, Semana)');
        DB::statement('CREATE INDEX IX_com_semana_mes_inicio  ON com.semana(ID_MetaMesInicio)');
        DB::statement('CREATE INDEX IX_com_semana_mes_final   ON com.semana(ID_MetaMesFinal)');
        DB::statement('CREATE INDEX IX_com_ms_ID_Semana       ON com.meta_semanal(ID_Semana)');
        DB::statement('CREATE INDEX IX_com_ms_IdUnidad        ON com.meta_semanal(IdUnidad)');
        DB::statement('CREATE INDEX IX_com_ms_IdLinea         ON com.meta_semanal(IdLineaArticulo)');
    }

    public function down(): void
    {
        Schema::dropIfExists('com.meta_semanal');
        Schema::dropIfExists('com.semana');
        Schema::dropIfExists('com.meta_mensual_contenido');
        Schema::dropIfExists('com.meta_mes_portada');
    }
};
