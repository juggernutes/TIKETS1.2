<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── core.sesion ──────────────────────────────────────────────────────
        Schema::create('core.sesion', function (Blueprint $table) {
            $table->uuid('ID_Sesion')->primary()->default(DB::raw('NEWID()'));
            $table->integer('ID_Usuario');
            $table->string('Token', 512)->unique();
            $table->string('IPOrigen', 45)->nullable();
            $table->string('UserAgent', 500)->nullable();
            $table->string('Dispositivo', 100)->nullable();
            $table->dateTimeTz('FechaCreacion', 0)->useCurrent();
            $table->dateTimeTz('FechaExpiracion', 0);
            $table->dateTimeTz('FechaUltimoUso', 0)->nullable();
            $table->boolean('Revocada')->default(false);
            $table->string('MotivoRevocacion', 100)->nullable();

            $table->foreign('ID_Usuario')->references('ID_Usuario')->on('core.usuario')->cascadeOnDelete();
        });

        DB::statement('ALTER TABLE core.sesion ADD CONSTRAINT CHK_core_ses_Expiracion CHECK (FechaExpiracion > FechaCreacion)');
        DB::statement('CREATE INDEX IX_core_ses_Usuario ON core.sesion(ID_Usuario)');
        DB::statement("CREATE INDEX IX_core_ses_Token   ON core.sesion(Token) WHERE Revocada = 0");
        DB::statement("CREATE INDEX IX_core_ses_Expira  ON core.sesion(FechaExpiracion) WHERE Revocada = 0");

        // ── core.auditoria ───────────────────────────────────────────────────
        Schema::create('core.auditoria', function (Blueprint $table) {
            $table->bigInteger('ID_Auditoria')->autoIncrement();
            $table->string('Schema_Tabla', 100);
            $table->string('ID_Registro', 100);
            $table->string('Accion', 10);
            $table->string('CampoModificado', 100)->nullable();
            $table->nText('ValorAnterior')->nullable();
            $table->nText('ValorNuevo')->nullable();
            $table->integer('ID_Usuario')->nullable();
            $table->dateTimeTz('FechaHora', 0)->useCurrent();
            $table->string('IP', 45)->nullable();
            $table->string('Aplicacion', 50)->nullable();
        });

        DB::statement("ALTER TABLE core.auditoria ADD CONSTRAINT CHK_core_aud_Accion CHECK (Accion IN ('INSERT','UPDATE','DELETE'))");
        DB::statement('CREATE INDEX IX_core_aud_Tabla   ON core.auditoria(Schema_Tabla, ID_Registro)');
        DB::statement('CREATE INDEX IX_core_aud_Usuario ON core.auditoria(ID_Usuario)');
        DB::statement('CREATE INDEX IX_core_aud_Fecha   ON core.auditoria(FechaHora)');

        // ── core.parametro ───────────────────────────────────────────────────
        Schema::create('core.parametro', function (Blueprint $table) {
            $table->integer('ID_Parametro')->autoIncrement();
            $table->string('Clave', 100)->unique();
            $table->string('Valor', 500);
            $table->string('TipoDato', 20);
            $table->string('Modulo', 30);
            $table->string('Descripcion', 300)->nullable();
            $table->boolean('Activo')->default(true);
            $table->dateTimeTz('FechaCreacion', 0)->useCurrent();
            $table->dateTimeTz('FechaModificacion', 0)->nullable();
            $table->integer('ID_UsuarioModifico')->nullable();

            $table->foreign('ID_UsuarioModifico')->references('ID_Usuario')->on('core.usuario')->nullOnDelete();
        });

        DB::statement("ALTER TABLE core.parametro ADD CONSTRAINT CHK_core_par_TipoDato CHECK (TipoDato IN ('INT','BOOL','STRING','DECIMAL','JSON'))");

        // Parámetros semilla
        DB::table('core.parametro')->insert([
            ['Clave' => 'LOGIN_MAX_INTENTOS',      'Valor' => '5',    'TipoDato' => 'INT',     'Modulo' => 'core', 'Descripcion' => 'Intentos fallidos antes de bloquear cuenta'],
            ['Clave' => 'LOGIN_BLOQUEO_MINUTOS',   'Valor' => '15',   'TipoDato' => 'INT',     'Modulo' => 'core', 'Descripcion' => 'Minutos de bloqueo tras exceder intentos'],
            ['Clave' => 'SESION_EXPIRACION_HORAS', 'Valor' => '8',    'TipoDato' => 'INT',     'Modulo' => 'core', 'Descripcion' => 'Horas de vigencia de una sesión activa'],
            ['Clave' => 'TOKEN_RESET_MINUTOS',     'Valor' => '30',   'TipoDato' => 'INT',     'Modulo' => 'core', 'Descripcion' => 'Minutos de vigencia del token de reset'],
            ['Clave' => 'HD_SLA_CRITICO_HORAS',    'Valor' => '4',    'TipoDato' => 'INT',     'Modulo' => 'hd',   'Descripcion' => 'SLA en horas para tickets críticos'],
            ['Clave' => 'HD_SLA_NORMAL_HORAS',     'Valor' => '24',   'TipoDato' => 'INT',     'Modulo' => 'hd',   'Descripcion' => 'SLA en horas para tickets normales'],
            ['Clave' => 'COM_PORCENTAJE_MIN_PAGO', 'Valor' => '0.90', 'TipoDato' => 'DECIMAL', 'Modulo' => 'com',  'Descripcion' => 'Porcentaje mínimo de cumplimiento para pagar'],
            ['Clave' => 'RH_DIAS_VIGENCIA_VACANTE','Valor' => '90',   'TipoDato' => 'INT',     'Modulo' => 'rh',   'Descripcion' => 'Días máximos que una vacante puede estar abierta'],
        ]);

        // ── hd.sla ───────────────────────────────────────────────────────────
        Schema::create('hd.sla', function (Blueprint $table) {
            $table->integer('ID_SLA')->autoIncrement();
            $table->string('Nombre', 100);
            $table->integer('ID_Area')->nullable();
            $table->string('Prioridad', 20);
            $table->integer('HorasRespuesta');
            $table->integer('HorasResolucion');
            $table->boolean('Activo')->default(true);

            $table->foreign('ID_Area')->references('ID_Area')->on('core.area')->nullOnDelete();
        });

        DB::statement("ALTER TABLE hd.sla ADD CONSTRAINT CHK_hd_sla_Prioridad CHECK (Prioridad IN ('CRITICA','ALTA','MEDIA','BAJA'))");
        DB::statement('ALTER TABLE hd.sla ADD CONSTRAINT CHK_hd_sla_Horas     CHECK (HorasRespuesta > 0 AND HorasResolucion >= HorasRespuesta)');

        // Agregar columnas SLA al ticket
        DB::statement("ALTER TABLE hd.ticket ADD Prioridad  varchar(20) NOT NULL CONSTRAINT DF_hd_tkt_Prior DEFAULT ('MEDIA')");
        DB::statement('ALTER TABLE hd.ticket ADD ID_SLA      int NULL');
        DB::statement('ALTER TABLE hd.ticket ADD FechaLimite datetime2(0) NULL');
        DB::statement("ALTER TABLE hd.ticket ADD FueraDeSLA  bit NOT NULL CONSTRAINT DF_hd_tkt_FSLA DEFAULT (0)");
        DB::statement("ALTER TABLE hd.ticket ADD CONSTRAINT CHK_hd_tkt_Prioridad CHECK (Prioridad IN ('CRITICA','ALTA','MEDIA','BAJA'))");
        DB::statement('ALTER TABLE hd.ticket ADD CONSTRAINT FK_hd_tkt_sla FOREIGN KEY (ID_SLA) REFERENCES hd.sla(ID_SLA)');

        // ── core.adjunto ─────────────────────────────────────────────────────
        Schema::create('core.adjunto', function (Blueprint $table) {
            $table->integer('ID_Adjunto')->autoIncrement();
            $table->string('Modulo', 30);
            $table->string('Entidad', 50);
            $table->integer('ID_Referencia');
            $table->string('NombreArchivo', 255);
            $table->string('RutaArchivo', 500)->nullable();
            $table->string('Extension', 10)->nullable();
            $table->bigInteger('TamanoBytes')->nullable();
            $table->string('MimeType', 100)->nullable();
            $table->string('Descripcion', 200)->nullable();
            $table->boolean('Activo')->default(true);
            $table->dateTimeTz('FechaCreacion', 0)->useCurrent();
            $table->integer('ID_UsuarioCreo')->nullable();

            $table->foreign('ID_UsuarioCreo')->references('ID_Usuario')->on('core.usuario')->nullOnDelete();
        });

        DB::statement("ALTER TABLE core.adjunto ADD CONSTRAINT CHK_core_adj_Modulo CHECK (Modulo IN ('hd','rh','ped','com','core'))");
        DB::statement('ALTER TABLE core.adjunto ADD CONSTRAINT CHK_core_adj_Tamano CHECK (TamanoBytes IS NULL OR TamanoBytes >= 0)');
        DB::statement('CREATE INDEX IX_core_adj_Referencia ON core.adjunto(Modulo, Entidad, ID_Referencia)');

        // ── core.dia_no_laboral ───────────────────────────────────────────────
        Schema::create('core.dia_no_laboral', function (Blueprint $table) {
            $table->integer('ID_DiaNL')->autoIncrement();
            $table->date('Fecha');
            $table->string('Descripcion', 100);
            $table->integer('ID_Sucursal')->nullable();
            $table->string('Tipo', 20);
            $table->boolean('Activo')->default(true);

            $table->unique(['Fecha', 'ID_Sucursal']);
            $table->foreign('ID_Sucursal')->references('ID_Sucursal')->on('core.sucursal')->nullOnDelete();
        });

        DB::statement("ALTER TABLE core.dia_no_laboral ADD CONSTRAINT CHK_core_dnl_Tipo CHECK (Tipo IN ('FESTIVO','PUENTE','CIERRE','OTRO'))");
        DB::statement('CREATE INDEX IX_core_dnl_Fecha ON core.dia_no_laboral(Fecha)');
    }

    public function down(): void
    {
        // Revertir columnas SLA del ticket
        DB::statement('ALTER TABLE hd.ticket DROP CONSTRAINT IF EXISTS FK_hd_tkt_sla');
        DB::statement('ALTER TABLE hd.ticket DROP CONSTRAINT IF EXISTS CHK_hd_tkt_Prioridad');
        DB::statement('ALTER TABLE hd.ticket DROP CONSTRAINT IF EXISTS DF_hd_tkt_Prior');
        DB::statement('ALTER TABLE hd.ticket DROP CONSTRAINT IF EXISTS DF_hd_tkt_FSLA');
        DB::statement('ALTER TABLE hd.ticket DROP COLUMN IF EXISTS Prioridad, ID_SLA, FechaLimite, FueraDeSLA');

        $tables = [
            'core.dia_no_laboral', 'core.adjunto', 'hd.sla',
            'core.parametro', 'core.auditoria', 'core.sesion',
        ];
        foreach ($tables as $t) {
            Schema::dropIfExists($t);
        }
    }
};
