<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            IF COL_LENGTH('hd.ticket', 'ID_Proveedor') IS NULL
                ALTER TABLE hd.ticket ADD ID_Proveedor int NULL
        ");

        DB::statement("
            IF COL_LENGTH('hd.ticket', 'FechaEnvioProveedor') IS NULL
                ALTER TABLE hd.ticket ADD FechaEnvioProveedor datetime2(0) NULL
        ");

        DB::statement("
            IF COL_LENGTH('hd.ticket', 'SeguimientoProveedor') IS NULL
                ALTER TABLE hd.ticket ADD SeguimientoProveedor nvarchar(max) NULL
        ");

        DB::statement("
            IF COL_LENGTH('hd.ticket', 'ID_Proveedor') IS NOT NULL
               AND OBJECT_ID('hd.FK_hd_tkt_proveedor', 'F') IS NULL
                ALTER TABLE hd.ticket ADD CONSTRAINT FK_hd_tkt_proveedor
                FOREIGN KEY (ID_Proveedor) REFERENCES core.proveedor(ID_Proveedor)
        ");
    }

    public function down(): void
    {
        DB::statement("IF OBJECT_ID('hd.FK_hd_tkt_proveedor', 'F') IS NOT NULL ALTER TABLE hd.ticket DROP CONSTRAINT FK_hd_tkt_proveedor");
        DB::statement("IF COL_LENGTH('hd.ticket', 'SeguimientoProveedor') IS NOT NULL ALTER TABLE hd.ticket DROP COLUMN SeguimientoProveedor");
        DB::statement("IF COL_LENGTH('hd.ticket', 'FechaEnvioProveedor') IS NOT NULL ALTER TABLE hd.ticket DROP COLUMN FechaEnvioProveedor");
        DB::statement("IF COL_LENGTH('hd.ticket', 'ID_Proveedor') IS NOT NULL ALTER TABLE hd.ticket DROP COLUMN ID_Proveedor");
    }
};
