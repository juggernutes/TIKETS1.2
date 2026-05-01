<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            IF COL_LENGTH('hd.ticket', 'ID_Usuario_Reporta') IS NULL
                ALTER TABLE hd.ticket ADD ID_Usuario_Reporta int NULL
        ");

        DB::statement("
            IF COL_LENGTH('hd.ticket', 'ID_Usuario_Reporta') IS NOT NULL
               AND NOT EXISTS (SELECT 1 FROM sys.foreign_keys WHERE name = 'FK_hd_tkt_usuario_reporta')
                ALTER TABLE hd.ticket
                ADD CONSTRAINT FK_hd_tkt_usuario_reporta
                FOREIGN KEY (ID_Usuario_Reporta) REFERENCES core.usuario(ID_Usuario)
        ");

        DB::statement("
            IF OBJECT_ID('hd.ticket_estatus_log', 'U') IS NULL
            CREATE TABLE hd.ticket_estatus_log (
                ID_Log int IDENTITY(1,1) NOT NULL PRIMARY KEY,
                ID_Ticket int NOT NULL,
                ID_Estatus_Anterior int NULL,
                ID_Estatus_Nuevo int NOT NULL,
                ID_Usuario int NULL,
                Comentario varchar(max) NULL,
                Fecha datetime2(0) NOT NULL CONSTRAINT DF_hd_tel_Fecha DEFAULT (SYSDATETIME()),
                CONSTRAINT FK_hd_tel_ticket FOREIGN KEY (ID_Ticket) REFERENCES hd.ticket(ID_Ticket) ON DELETE CASCADE,
                CONSTRAINT FK_hd_tel_est_ant FOREIGN KEY (ID_Estatus_Anterior) REFERENCES hd.estatus(ID_Estatus),
                CONSTRAINT FK_hd_tel_est_nvo FOREIGN KEY (ID_Estatus_Nuevo) REFERENCES hd.estatus(ID_Estatus),
                CONSTRAINT FK_hd_tel_usuario FOREIGN KEY (ID_Usuario) REFERENCES core.usuario(ID_Usuario)
            )
        ");

        DB::statement("
            IF OBJECT_ID('hd.ticket_agente_log', 'U') IS NULL
            CREATE TABLE hd.ticket_agente_log (
                ID_Log int IDENTITY(1,1) NOT NULL PRIMARY KEY,
                ID_Ticket int NOT NULL,
                ID_Soporte_Anterior int NULL,
                ID_Soporte_Nuevo int NOT NULL,
                ID_UsuarioAsigno int NULL,
                Fecha datetime2(0) NOT NULL CONSTRAINT DF_hd_tal_Fecha DEFAULT (SYSDATETIME()),
                CONSTRAINT FK_hd_tal_ticket FOREIGN KEY (ID_Ticket) REFERENCES hd.ticket(ID_Ticket) ON DELETE CASCADE,
                CONSTRAINT FK_hd_tal_ant FOREIGN KEY (ID_Soporte_Anterior) REFERENCES core.usuario(ID_Usuario),
                CONSTRAINT FK_hd_tal_nvo FOREIGN KEY (ID_Soporte_Nuevo) REFERENCES core.usuario(ID_Usuario),
                CONSTRAINT FK_hd_tal_asigno FOREIGN KEY (ID_UsuarioAsigno) REFERENCES core.usuario(ID_Usuario)
            )
        ");

        DB::statement("
            IF OBJECT_ID('core.empleado_equipo', 'U') IS NULL
            CREATE TABLE core.empleado_equipo (
                ID_EmpleadoEquipo int IDENTITY(1,1) NOT NULL PRIMARY KEY,
                Numero_Empleado int NOT NULL,
                ID_Equipo int NOT NULL,
                FechaAsignacion datetime2(0) NOT NULL CONSTRAINT DF_core_ee_FechaAsignacion DEFAULT (SYSDATETIME()),
                FechaDevolucion datetime2(0) NULL,
                Observaciones varchar(500) NULL,
                Activo bit NOT NULL CONSTRAINT DF_core_ee_Activo DEFAULT (1),
                CONSTRAINT FK_core_ee_empleado FOREIGN KEY (Numero_Empleado) REFERENCES core.empleado(Numero_Empleado),
                CONSTRAINT FK_core_ee_equipo FOREIGN KEY (ID_Equipo) REFERENCES core.equipo(ID_Equipo)
            )
        ");

        DB::statement("
            IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'UQ_core_ee_Equipo_Activo' AND object_id = OBJECT_ID('core.empleado_equipo'))
                CREATE UNIQUE INDEX UQ_core_ee_Equipo_Activo ON core.empleado_equipo(ID_Equipo) WHERE Activo = 1
        ");
    }

    public function down(): void
    {
        DB::statement("IF EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'UQ_core_ee_Equipo_Activo' AND object_id = OBJECT_ID('core.empleado_equipo')) DROP INDEX UQ_core_ee_Equipo_Activo ON core.empleado_equipo");
        DB::statement("IF OBJECT_ID('core.empleado_equipo', 'U') IS NOT NULL DROP TABLE core.empleado_equipo");
        DB::statement("IF OBJECT_ID('hd.ticket_agente_log', 'U') IS NOT NULL DROP TABLE hd.ticket_agente_log");
        DB::statement("IF OBJECT_ID('hd.ticket_estatus_log', 'U') IS NOT NULL DROP TABLE hd.ticket_estatus_log");
        DB::statement("IF EXISTS (SELECT 1 FROM sys.foreign_keys WHERE name = 'FK_hd_tkt_usuario_reporta') ALTER TABLE hd.ticket DROP CONSTRAINT FK_hd_tkt_usuario_reporta");
        DB::statement("IF COL_LENGTH('hd.ticket', 'ID_Usuario_Reporta') IS NOT NULL ALTER TABLE hd.ticket DROP COLUMN ID_Usuario_Reporta");
    }
};
