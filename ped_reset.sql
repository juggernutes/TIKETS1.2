/* ============================================================
   PORTAL V2 — ped_reset.sql
   Limpia el schema ped por completo (FK constraints + tablas)
   y lo recrea desde cero.
   ============================================================ */

USE PortalV2;
GO

/* PASO 1: Eliminar TODAS las FK constraints del schema ped */
DECLARE @sql NVARCHAR(MAX) = N''
SELECT @sql = @sql +
    'ALTER TABLE ' + QUOTENAME(s.name) + '.' + QUOTENAME(t.name) +
    ' DROP CONSTRAINT ' + QUOTENAME(fk.name) + ';' + CHAR(13)
FROM sys.foreign_keys fk
JOIN sys.tables  t ON fk.parent_object_id = t.object_id
JOIN sys.schemas s ON t.schema_id = s.schema_id
WHERE s.name = 'ped'
IF LEN(@sql) > 0
BEGIN
    EXEC sp_executesql @sql
    PRINT 'FK constraints eliminadas.'
END
GO

/* PASO 2: Eliminar TODAS las tablas del schema ped */
DECLARE @sql NVARCHAR(MAX) = N''
SELECT @sql = @sql +
    'DROP TABLE ' + QUOTENAME(s.name) + '.' + QUOTENAME(t.name) + ';' + CHAR(13)
FROM sys.tables  t
JOIN sys.schemas s ON t.schema_id = s.schema_id
WHERE s.name = 'ped'
IF LEN(@sql) > 0
BEGIN
    EXEC sp_executesql @sql
    PRINT 'Tablas ped eliminadas.'
END
GO

/* PASO 3: Recrear tablas limpias (cada CREATE en su propio batch) */

CREATE TABLE ped.tipounidad (
    IdTipoUnidad  int         IDENTITY(1,1) NOT NULL PRIMARY KEY,
    Nombre        varchar(60) NOT NULL,
    Activo        bit         NOT NULL DEFAULT (1)
);
GO

SET IDENTITY_INSERT ped.tipounidad ON;
INSERT INTO ped.tipounidad (IdTipoUnidad, Nombre, Activo) VALUES
(1, 'CAMION',      1),
(2, 'CAMIONETA',   1),
(3, 'REFRIGERADO', 1),
(4, 'OTRO',        1);
SET IDENTITY_INSERT ped.tipounidad OFF;
GO

CREATE TABLE ped.unidadoperacional (
    IdUnidad      int         IDENTITY(1,1) NOT NULL PRIMARY KEY,
    IdTipoUnidad  int         NOT NULL REFERENCES ped.tipounidad(IdTipoUnidad),
    Nombre        varchar(80) NOT NULL,
    Placas        varchar(20) NULL,
    activo        bit         NOT NULL DEFAULT (1)
);
GO

CREATE TABLE ped.estado_pedido (
    IdEstadoPedido  int         IDENTITY(1,1) NOT NULL PRIMARY KEY,
    Nombre          varchar(50) NOT NULL,
    Orden           int         NOT NULL DEFAULT (99),
    Activo          bit         NOT NULL DEFAULT (1)
);
GO

SET IDENTITY_INSERT ped.estado_pedido ON;
INSERT INTO ped.estado_pedido (IdEstadoPedido, Nombre, Orden, Activo) VALUES
(1, 'PENDIENTE',  1, 1),
(2, 'EN PROCESO', 2, 1),
(3, 'DESPACHADO', 3, 1),
(4, 'ENTREGADO',  4, 1),
(5, 'CANCELADO',  5, 1);
SET IDENTITY_INSERT ped.estado_pedido OFF;
GO

CREATE TABLE ped.pedido (
    IdPedido          int            IDENTITY(1,1) NOT NULL PRIMARY KEY,
    Folio             varchar(30)    NOT NULL,
    Numero_Empleado   int            NULL REFERENCES core.empleado(Numero_Empleado),
    IdUnidad          int            NULL REFERENCES ped.unidadoperacional(IdUnidad),
    IdEstadoPedido    int            NOT NULL DEFAULT (1) REFERENCES ped.estado_pedido(IdEstadoPedido),
    Descripcion       nvarchar(2000) NULL,
    FechaPedido       datetime2      NOT NULL DEFAULT SYSDATETIME(),
    FechaEntrega      datetime2      NULL,
    Observaciones     nvarchar(1000) NULL,
    Activo            bit            NOT NULL DEFAULT (1),
    FechaCreacion     datetime2      NOT NULL DEFAULT SYSDATETIME(),
    FechaModificacion datetime2      NULL
);
GO

PRINT 'Schema ped recreado correctamente.';
GO
