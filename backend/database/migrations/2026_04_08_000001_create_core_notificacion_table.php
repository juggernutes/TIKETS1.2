<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('core.notificacion', function (Blueprint $table) {
            $table->integer('ID_Notificacion')->autoIncrement();
            $table->integer('ID_Usuario');
            $table->string('Tipo', 50);
            $table->string('Modulo', 30);
            $table->integer('ID_Referencia')->nullable();
            $table->string('Titulo', 200);
            $table->text('Mensaje')->nullable();
            $table->boolean('Leida')->default(false);
            $table->dateTimeTz('FechaLeida', 0)->nullable();
            $table->dateTimeTz('FechaCreacion', 0)->useCurrent();
            $table->dateTimeTz('FechaExpiracion', 0)->nullable();

            $table->foreign('ID_Usuario')->references('ID_Usuario')->on('core.usuario')->cascadeOnDelete();
        });

        DB::statement("ALTER TABLE core.notificacion ADD CONSTRAINT CHK_core_noti_Tipo
            CHECK (Tipo IN ('TICKET','COMISION','VACANTE','PEDIDO','SISTEMA','RH'))");

        DB::statement('CREATE INDEX IX_core_noti_Usuario_Leida ON core.notificacion(ID_Usuario, Leida)');
        DB::statement('CREATE INDEX IX_core_noti_Fecha         ON core.notificacion(FechaCreacion)');
    }

    public function down(): void
    {
        Schema::dropIfExists('core.notificacion');
    }
};
