<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── com.indicador ────────────────────────────────────────────────────
        Schema::create('com.indicador', function (Blueprint $table) {
            $table->integer('ID_Indicador')->autoIncrement();
            $table->string('Clave', 20)->unique();
            $table->string('Nombre', 100);
            $table->string('Categoria', 30);
            $table->integer('OrdenResumen')->default(0);
            $table->boolean('Activo')->default(true);
        });

        DB::statement("ALTER TABLE com.indicador ADD CONSTRAINT CHK_com_ind_Clave  CHECK (LEN(LTRIM(RTRIM(Clave))) > 0)");
        DB::statement("ALTER TABLE com.indicador ADD CONSTRAINT CHK_com_ind_Categ  CHECK (Categoria IN ('VENTAS','CALIDAD','DOCUMENTOS'))");

        // ── com.sub_indicador ────────────────────────────────────────────────
        Schema::create('com.sub_indicador', function (Blueprint $table) {
            $table->integer('ID_SubIndicador')->autoIncrement();
            $table->integer('ID_Indicador');
            $table->string('Clave', 20);
            $table->string('Nombre', 100);
            $table->integer('Orden')->default(0);
            $table->boolean('Activo')->default(true);

            $table->unique(['ID_Indicador', 'Clave']);
            $table->foreign('ID_Indicador')->references('ID_Indicador')->on('com.indicador')->cascadeOnDelete();
        });

        DB::statement("ALTER TABLE com.sub_indicador ADD CONSTRAINT CHK_com_si_Clave CHECK (LEN(LTRIM(RTRIM(Clave))) > 0)");

        // ── com.regla_comision ───────────────────────────────────────────────
        Schema::create('com.regla_comision', function (Blueprint $table) {
            $table->integer('ID_Regla')->autoIncrement();
            $table->integer('ID_Indicador');
            $table->integer('ID_SubIndicador')->nullable();
            $table->string('Puesto', 30)->nullable();
            $table->string('Canal', 30)->nullable();
            $table->string('TCE', 5)->nullable();
            $table->decimal('PorcentajeMinimo', 10, 6)->default(0);
            $table->decimal('PorcentajeMaximo', 10, 6)->default(9.9999);
            $table->decimal('Monto', 18, 2);
            $table->decimal('Factor', 10, 6)->nullable();
            $table->boolean('Activo')->default(true);
            $table->dateTimeTz('FechaCreacion', 0)->useCurrent();
            $table->dateTimeTz('FechaModificacion', 0)->useCurrent();
            $table->integer('ID_UsuarioCreo')->nullable();

            $table->foreign('ID_Indicador')->references('ID_Indicador')->on('com.indicador');
            $table->foreign('ID_SubIndicador')->references('ID_SubIndicador')->on('com.sub_indicador')->nullOnDelete();
            $table->foreign('ID_UsuarioCreo')->references('ID_Usuario')->on('core.usuario')->nullOnDelete();
        });

        DB::statement("ALTER TABLE com.regla_comision ADD CONSTRAINT CHK_com_rc_Puesto  CHECK (Puesto IS NULL OR Puesto IN ('VENDEDOR','SUPERVISOR'))");
        DB::statement("ALTER TABLE com.regla_comision ADD CONSTRAINT CHK_com_rc_Canal   CHECK (Canal  IS NULL OR Canal  IN ('TRADICIONAL','MODERNO'))");
        DB::statement("ALTER TABLE com.regla_comision ADD CONSTRAINT CHK_com_rc_TCE     CHECK (TCE    IS NULL OR TCE    IN ('VT','VM','XT','XM'))");
        DB::statement('ALTER TABLE com.regla_comision ADD CONSTRAINT CHK_com_rc_Rango   CHECK (PorcentajeMinimo >= 0 AND PorcentajeMaximo >= PorcentajeMinimo)');
        DB::statement('ALTER TABLE com.regla_comision ADD CONSTRAINT CHK_com_rc_Monto   CHECK (Monto >= 0)');
        DB::statement('ALTER TABLE com.regla_comision ADD CONSTRAINT CHK_com_rc_Factor  CHECK (Factor IS NULL OR Factor >= 0)');

        // ── com.regla_comision_historial ─────────────────────────────────────
        Schema::create('com.regla_comision_historial', function (Blueprint $table) {
            $table->integer('ID_Historial')->autoIncrement();
            $table->integer('ID_Regla');
            $table->decimal('PorcentajeMinimo', 10, 6);
            $table->decimal('PorcentajeMaximo', 10, 6);
            $table->decimal('Monto', 18, 2);
            $table->decimal('Factor', 10, 6)->nullable();
            $table->boolean('Activo');
            $table->string('Accion', 10);
            $table->integer('ID_Usuario')->nullable();
            $table->dateTimeTz('Fecha', 0)->useCurrent();

            $table->foreign('ID_Regla')->references('ID_Regla')->on('com.regla_comision');
            $table->foreign('ID_Usuario')->references('ID_Usuario')->on('core.usuario')->nullOnDelete();
        });

        DB::statement("ALTER TABLE com.regla_comision_historial ADD CONSTRAINT CHK_com_rch_Accion CHECK (Accion IN ('INSERT','UPDATE','DELETE'))");

        // ── com.corrida_comision ─────────────────────────────────────────────
        Schema::create('com.corrida_comision', function (Blueprint $table) {
            $table->integer('ID_Corrida')->autoIncrement();
            $table->integer('ID_Semana');
            $table->date('FechaInicio')->nullable();
            $table->date('FechaFin')->nullable();
            $table->string('Estatus', 20)->default('BORRADOR');
            $table->string('ArchivoOrigen', 255)->nullable();
            $table->string('Observaciones', 500)->nullable();
            $table->dateTimeTz('FechaCreacion', 0)->useCurrent();
            $table->dateTimeTz('FechaModificacion', 0)->useCurrent();
            $table->integer('ID_UsuarioCreo')->nullable();
            $table->integer('ID_UsuarioModifico')->nullable();

            $table->foreign('ID_Semana')->references('ID_Semana')->on('com.semana');
            $table->foreign('ID_UsuarioCreo')->references('ID_Usuario')->on('core.usuario')->nullOnDelete();
            $table->foreign('ID_UsuarioModifico')->references('ID_Usuario')->on('core.usuario')->nullOnDelete();
        });

        DB::statement("ALTER TABLE com.corrida_comision ADD CONSTRAINT CHK_com_cc_Estatus CHECK (Estatus IN ('BORRADOR','EN_PROCESO','CALCULADO','APROBADO','PAGADO','CANCELADO'))");
        DB::statement('ALTER TABLE com.corrida_comision ADD CONSTRAINT CHK_com_cc_Fechas  CHECK (FechaInicio IS NULL OR FechaFin IS NULL OR FechaFin >= FechaInicio)');

        // ── com.corrida_comision_log ─────────────────────────────────────────
        Schema::create('com.corrida_comision_log', function (Blueprint $table) {
            $table->bigInteger('ID_Log')->autoIncrement();
            $table->integer('ID_Corrida');
            $table->string('EstadoAnterior', 20)->nullable();
            $table->string('EstadoNuevo', 20);
            $table->string('Comentario', 500)->nullable();
            $table->integer('ID_Usuario')->nullable();
            $table->dateTimeTz('Fecha', 0)->useCurrent();
            $table->string('IP', 45)->nullable();

            $table->foreign('ID_Corrida')->references('ID_Corrida')->on('com.corrida_comision');
            $table->foreign('ID_Usuario')->references('ID_Usuario')->on('core.usuario')->nullOnDelete();
        });

        DB::statement("ALTER TABLE com.corrida_comision_log ADD CONSTRAINT CHK_com_ccl_EstadoNuevo CHECK (EstadoNuevo IN ('BORRADOR','EN_PROCESO','CALCULADO','APROBADO','PAGADO','CANCELADO'))");

        // ── com.comision_base ────────────────────────────────────────────────
        // Cabecera empleado-unidad-semana: capturada por las Generalistas
        Schema::create('com.comision_base', function (Blueprint $table) {
            $table->integer('ID_Comision')->autoIncrement();
            $table->integer('ID_Semana');
            $table->integer('IdUnidad');
            $table->integer('Numero_Empleado');
            $table->string('Estatus', 20)->default('BORRADOR');
            $table->string('ArchivoOrigen', 255)->nullable();
            $table->string('Observaciones', 500)->nullable();
            $table->dateTimeTz('FechaCreacion', 0)->useCurrent();
            $table->dateTimeTz('FechaModificacion', 0)->useCurrent();
            $table->integer('ID_UsuarioCreo')->nullable();
            $table->integer('ID_UsuarioModifico')->nullable();

            $table->unique(['ID_Semana', 'IdUnidad', 'Numero_Empleado']);
            $table->foreign('ID_Semana')->references('ID_Semana')->on('com.semana');
            $table->foreign('IdUnidad')->references('IdUnidad')->on('ped.unidadoperacional');
            $table->foreign('Numero_Empleado')->references('Numero_Empleado')->on('core.empleado');
            $table->foreign('ID_UsuarioCreo')->references('ID_Usuario')->on('core.usuario')->nullOnDelete();
            $table->foreign('ID_UsuarioModifico')->references('ID_Usuario')->on('core.usuario')->nullOnDelete();
        });

        DB::statement("ALTER TABLE com.comision_base ADD CONSTRAINT CHK_com_cb_Estatus CHECK (Estatus IN ('BORRADOR','EN_PROCESO','CALCULADO','APROBADO','PAGADO','CANCELADO'))");

        // ── com.base_comision_semanal ────────────────────────────────────────
        // Detalle calculado por corrida: empleado con su TCE, puesto, canal
        Schema::create('com.base_comision_semanal', function (Blueprint $table) {
            $table->integer('ID_Base')->autoIncrement();
            $table->integer('ID_Corrida');
            $table->integer('Numero_Empleado');
            $table->string('Sucursal', 100);
            $table->string('NombreEmpleado', 150);
            $table->string('Ruta', 30)->nullable();
            $table->string('Puesto', 30)->nullable();
            $table->string('Canal', 30)->nullable();
            $table->string('TCE', 5)->nullable();
            $table->boolean('Activo')->default(true);
            $table->dateTimeTz('FechaCreacion', 0)->useCurrent();
            $table->integer('ID_UsuarioCreo')->nullable();

            $table->unique(['ID_Corrida', 'Numero_Empleado']);
            $table->foreign('ID_Corrida')->references('ID_Corrida')->on('com.corrida_comision');
            $table->foreign('Numero_Empleado')->references('Numero_Empleado')->on('core.empleado');
            $table->foreign('ID_UsuarioCreo')->references('ID_Usuario')->on('core.usuario')->nullOnDelete();
        });

        DB::statement("ALTER TABLE com.base_comision_semanal ADD CONSTRAINT CHK_com_bcs_Puesto CHECK (Puesto IS NULL OR Puesto IN ('VENDEDOR','SUPERVISOR'))");
        DB::statement("ALTER TABLE com.base_comision_semanal ADD CONSTRAINT CHK_com_bcs_Canal  CHECK (Canal  IS NULL OR Canal  IN ('TRADICIONAL','MODERNO'))");
        DB::statement("ALTER TABLE com.base_comision_semanal ADD CONSTRAINT CHK_com_bcs_TCE    CHECK (TCE    IS NULL OR TCE    IN ('VT','VM','XT','XM'))");

        // ── com.resultado_indicador ──────────────────────────────────────────
        // Cargado por el usuario (VOL/EFE/EFI/COB) y por CxC (DF1/DAU)
        Schema::create('com.resultado_indicador', function (Blueprint $table) {
            $table->integer('ID_Resultado')->autoIncrement();
            $table->integer('ID_Base');
            $table->integer('ID_Indicador');
            $table->integer('ID_SubIndicador')->nullable();
            $table->decimal('ValorReal', 18, 6)->nullable();
            $table->decimal('Meta', 18, 6)->nullable();
            $table->integer('ClientesActivos')->nullable();
            $table->integer('ClientesVisitados')->nullable();
            $table->integer('ClientesConCompra')->nullable();
            $table->decimal('PorcentajeCumplimiento', 10, 6)->nullable();
            $table->decimal('MontoCalculado', 18, 2)->nullable();
            $table->string('Observaciones', 300)->nullable();
            $table->dateTimeTz('FechaCreacion', 0)->useCurrent();
            $table->integer('ID_UsuarioCreo')->nullable();

            $table->unique(['ID_Base', 'ID_Indicador', 'ID_SubIndicador']);
            $table->foreign('ID_Base')->references('ID_Base')->on('com.base_comision_semanal')->cascadeOnDelete();
            $table->foreign('ID_Indicador')->references('ID_Indicador')->on('com.indicador');
            $table->foreign('ID_SubIndicador')->references('ID_SubIndicador')->on('com.sub_indicador')->nullOnDelete();
            $table->foreign('ID_UsuarioCreo')->references('ID_Usuario')->on('core.usuario')->nullOnDelete();
        });

        DB::statement('ALTER TABLE com.resultado_indicador ADD CONSTRAINT CHK_com_ri_Pct        CHECK (PorcentajeCumplimiento IS NULL OR PorcentajeCumplimiento >= 0)');
        DB::statement('ALTER TABLE com.resultado_indicador ADD CONSTRAINT CHK_com_ri_Monto      CHECK (MontoCalculado IS NULL OR MontoCalculado >= 0)');
        DB::statement('ALTER TABLE com.resultado_indicador ADD CONSTRAINT CHK_com_ri_Clientes_A CHECK (ClientesActivos   IS NULL OR ClientesActivos   >= 0)');
        DB::statement('ALTER TABLE com.resultado_indicador ADD CONSTRAINT CHK_com_ri_Clientes_V CHECK (ClientesVisitados IS NULL OR ClientesVisitados >= 0)');
        DB::statement('ALTER TABLE com.resultado_indicador ADD CONSTRAINT CHK_com_ri_Clientes_C CHECK (ClientesConCompra IS NULL OR ClientesConCompra >= 0)');

        // ── com.resultado_doc ────────────────────────────────────────────────
        // Capturado por Gerentes de Sucursal: checklist DOC semanal
        Schema::create('com.resultado_doc', function (Blueprint $table) {
            $table->integer('ID_ResultadoDoc')->autoIncrement();
            $table->integer('ID_Base');
            $table->integer('ID_SubIndicador');
            $table->boolean('Cumplido')->nullable();
            $table->decimal('MontoConcepto', 18, 2)->nullable();
            $table->decimal('AlcancePesos', 18, 2)->nullable();
            $table->string('Observaciones', 200)->nullable();
            $table->dateTimeTz('FechaCreacion', 0)->useCurrent();
            $table->integer('ID_UsuarioCreo')->nullable();

            $table->unique(['ID_Base', 'ID_SubIndicador']);
            $table->foreign('ID_Base')->references('ID_Base')->on('com.base_comision_semanal')->cascadeOnDelete();
            $table->foreign('ID_SubIndicador')->references('ID_SubIndicador')->on('com.sub_indicador');
            $table->foreign('ID_UsuarioCreo')->references('ID_Usuario')->on('core.usuario')->nullOnDelete();
        });

        DB::statement('ALTER TABLE com.resultado_doc ADD CONSTRAINT CHK_com_rdoc_Monto CHECK (MontoConcepto IS NULL OR MontoConcepto >= 0)');
        DB::statement('ALTER TABLE com.resultado_doc ADD CONSTRAINT CHK_com_rdoc_Alc   CHECK (AlcancePesos  IS NULL OR AlcancePesos  >= 0)');

        // ── com.ajuste_comision ──────────────────────────────────────────────
        // DESCUENTO/AGREGADO/SR1/SR2 — CxC captura DF1 y DAU como DESCUENTO
        Schema::create('com.ajuste_comision', function (Blueprint $table) {
            $table->integer('ID_Ajuste')->autoIncrement();
            $table->integer('ID_Base');
            $table->string('TipoAjuste', 20);
            $table->integer('DiasDescuento')->nullable();
            $table->decimal('Monto', 18, 2);
            $table->string('Motivo', 300)->nullable();
            $table->dateTimeTz('FechaCreacion', 0)->useCurrent();
            $table->integer('ID_UsuarioCreo')->nullable();

            $table->foreign('ID_Base')->references('ID_Base')->on('com.base_comision_semanal')->cascadeOnDelete();
            $table->foreign('ID_UsuarioCreo')->references('ID_Usuario')->on('core.usuario')->nullOnDelete();
        });

        DB::statement("ALTER TABLE com.ajuste_comision ADD CONSTRAINT CHK_com_aj_Tipo CHECK (TipoAjuste IN ('DESCUENTO','AGREGADO','SR1','SR2'))");
        DB::statement('ALTER TABLE com.ajuste_comision ADD CONSTRAINT CHK_com_aj_Dias  CHECK (DiasDescuento IS NULL OR DiasDescuento >= 0)');
        DB::statement('ALTER TABLE com.ajuste_comision ADD CONSTRAINT CHK_com_aj_Monto CHECK (Monto >= 0)');

        // ── com.calculo_comision ─────────────────────────────────────────────
        // Una fila por empleado (ID_Base único)
        Schema::create('com.calculo_comision', function (Blueprint $table) {
            $table->integer('ID_Calculo')->autoIncrement();
            $table->integer('ID_Base')->unique();
            $table->decimal('MontoBruto', 18, 2)->nullable();
            $table->decimal('TotalDescuentos', 18, 2)->default(0);
            $table->decimal('TotalAgregados', 18, 2)->default(0);
            $table->decimal('MontoFinal', 18, 2)->default(0);
            $table->string('Estatus', 20)->default('CALCULADO');
            $table->dateTimeTz('FechaCalculo', 0)->useCurrent();
            $table->integer('CalculadoPor')->nullable();
            $table->boolean('Aprobado')->default(false);
            $table->dateTimeTz('FechaAprobacion', 0)->nullable();
            $table->integer('AprobadoPor')->nullable();
            $table->string('Observaciones', 500)->nullable();

            $table->foreign('ID_Base')->references('ID_Base')->on('com.base_comision_semanal')->cascadeOnDelete();
            $table->foreign('CalculadoPor')->references('ID_Usuario')->on('core.usuario')->nullOnDelete();
            $table->foreign('AprobadoPor')->references('ID_Usuario')->on('core.usuario')->nullOnDelete();
        });

        DB::statement("ALTER TABLE com.calculo_comision ADD CONSTRAINT CHK_com_cal_Estatus CHECK (Estatus IN ('CALCULADO','APROBADO','PAGADO','RECHAZADO','CANCELADO'))");
        DB::statement('ALTER TABLE com.calculo_comision ADD CONSTRAINT CHK_com_cal_Bruto   CHECK (MontoBruto IS NULL OR MontoBruto >= 0)');
        DB::statement('ALTER TABLE com.calculo_comision ADD CONSTRAINT CHK_com_cal_Desc    CHECK (TotalDescuentos >= 0)');
        DB::statement('ALTER TABLE com.calculo_comision ADD CONSTRAINT CHK_com_cal_Agr     CHECK (TotalAgregados  >= 0)');
        DB::statement('ALTER TABLE com.calculo_comision ADD CONSTRAINT CHK_com_cal_Final   CHECK (MontoFinal >= 0)');

        // ── com.acumulado_empleado ───────────────────────────────────────────
        Schema::create('com.acumulado_empleado', function (Blueprint $table) {
            $table->integer('ID_Acumulado')->autoIncrement();
            $table->integer('Numero_Empleado');
            $table->integer('Anio');
            $table->decimal('TotalComisiones', 18, 2)->default(0);
            $table->decimal('TotalDescuentos', 18, 2)->default(0);
            $table->decimal('TotalPagado', 18, 2)->default(0);
            $table->dateTimeTz('FechaUltimaActualizacion', 0)->useCurrent();

            $table->unique(['Numero_Empleado', 'Anio']);
            $table->foreign('Numero_Empleado')->references('Numero_Empleado')->on('core.empleado');
        });

        // ── Índices ──────────────────────────────────────────────────────────
        DB::statement('CREATE INDEX IX_com_si_ID_Indicador    ON com.sub_indicador(ID_Indicador)');
        DB::statement('CREATE INDEX IX_com_rc_Indicador_TCE   ON com.regla_comision(ID_Indicador, TCE, Activo, PorcentajeMinimo, PorcentajeMaximo) INCLUDE (Monto, Factor)');
        DB::statement('CREATE INDEX IX_com_rch_ID_Regla       ON com.regla_comision_historial(ID_Regla)');
        DB::statement('CREATE INDEX IX_com_cc_ID_Semana       ON com.corrida_comision(ID_Semana)');
        DB::statement('CREATE INDEX IX_com_cc_Estatus         ON com.corrida_comision(Estatus)');
        DB::statement('CREATE INDEX IX_com_ccl_ID_Corrida     ON com.corrida_comision_log(ID_Corrida)');
        DB::statement('CREATE INDEX IX_com_cb_ID_Semana       ON com.comision_base(ID_Semana)');
        DB::statement('CREATE INDEX IX_com_cb_IdUnidad        ON com.comision_base(IdUnidad)');
        DB::statement('CREATE INDEX IX_com_cb_Empleado        ON com.comision_base(Numero_Empleado)');
        DB::statement('CREATE INDEX IX_com_bcs_ID_Corrida     ON com.base_comision_semanal(ID_Corrida)');
        DB::statement('CREATE INDEX IX_com_bcs_Empleado       ON com.base_comision_semanal(Numero_Empleado)');
        DB::statement('CREATE INDEX IX_com_bcs_TCE            ON com.base_comision_semanal(TCE)');
        DB::statement('CREATE INDEX IX_com_ri_ID_Base         ON com.resultado_indicador(ID_Base)');
        DB::statement('CREATE INDEX IX_com_ri_ID_Indicador    ON com.resultado_indicador(ID_Indicador)');
        DB::statement('CREATE INDEX IX_com_ri_ID_SubIndicador ON com.resultado_indicador(ID_SubIndicador)');
        DB::statement('CREATE INDEX IX_com_rdoc_ID_Base       ON com.resultado_doc(ID_Base)');
        DB::statement('CREATE INDEX IX_com_rdoc_ID_SubInd     ON com.resultado_doc(ID_SubIndicador)');
        DB::statement('CREATE INDEX IX_com_aj_ID_Base         ON com.ajuste_comision(ID_Base, TipoAjuste)');
        DB::statement('CREATE INDEX IX_com_cal_ID_Base        ON com.calculo_comision(ID_Base)');
        DB::statement('CREATE INDEX IX_com_cal_Estatus        ON com.calculo_comision(Estatus)');
        DB::statement('CREATE INDEX IX_com_ace_Empleado       ON com.acumulado_empleado(Numero_Empleado)');
        DB::statement('CREATE INDEX IX_com_ace_Anio           ON com.acumulado_empleado(Anio)');
    }

    public function down(): void
    {
        $tables = [
            'com.acumulado_empleado',
            'com.calculo_comision',
            'com.ajuste_comision',
            'com.resultado_doc',
            'com.resultado_indicador',
            'com.base_comision_semanal',
            'com.comision_base',
            'com.corrida_comision_log',
            'com.corrida_comision',
            'com.regla_comision_historial',
            'com.regla_comision',
            'com.sub_indicador',
            'com.indicador',
        ];
        foreach ($tables as $t) {
            Schema::dropIfExists($t);
        }
    }
};
