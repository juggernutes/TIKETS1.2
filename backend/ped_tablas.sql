/* ============================================================
   PORTAL V2 — ped_tablas.sql
   Crea tablas del schema ped separando CREATE e INSERT en
   batches distintos (GO) para evitar el error de resolución
   de columnas de SQL Server.
   ============================================================ */

USE PortalV2;
GO

/* 1. ped.tipounidad */
IF NOT EXISTS (
    SELECT 1 FROM sys.tables t JOIN sys.schemas s ON t.schema_id=s.schema_id
    WHERE s.name='ped' AND t.name='tipounidad'
)
    CREATE TABLE ped.tipounidad (
        IdTipoUnidad  int         IDENTITY(1,1) NOT NULL PRIMARY KEY,
        Nombre        varchar(60) NOT NULL,
        Activo        bit         NOT NULL DEFAULT (1)
    );
GO

SET IDENTITY_INSERT ped.tipounidad ON;
INSERT INTO ped.tipounidad (IdTipoUnidad, Nombre, Activo)
SELECT v.* FROM (VALUES
  (1, 'CAMION',      1),
  (2, 'CAMIONETA',   1),
  (3, 'REFRIGERADO', 1),
  (4, 'OTRO',        1)
) AS v (IdTipoUnidad, Nombre, Activo)
WHERE NOT EXISTS (SELECT 1 FROM ped.tipounidad WHERE IdTipoUnidad = v.IdTipoUnidad);
SET IDENTITY_INSERT ped.tipounidad OFF;
GO

/* 2. ped.unidadoperacional */
IF NOT EXISTS (
    SELECT 1 FROM sys.tables t JOIN sys.schemas s ON t.schema_id=s.schema_id
    WHERE s.name='ped' AND t.name='unidadoperacional'
)
    CREATE TABLE ped.unidadoperacional (
        IdUnidad      int         IDENTITY(1,1) NOT NULL PRIMARY KEY,
        IdTipoUnidad  int         NOT NULL REFERENCES ped.tipounidad(IdTipoUnidad),
        Nombre        varchar(80) NOT NULL,
        Placas        varchar(20) NULL,
        activo        bit         NOT NULL DEFAULT (1)
    );
GO

/* 3. ped.estado_pedido */
IF NOT EXISTS (
    SELECT 1 FROM sys.tables t JOIN sys.schemas s ON t.schema_id=s.schema_id
    WHERE s.name='ped' AND t.name='estado_pedido'
)
    CREATE TABLE ped.estado_pedido (
        IdEstadoPedido  int         IDENTITY(1,1) NOT NULL PRIMARY KEY,
        Nombre          varchar(50) NOT NULL,
        Orden           int         NOT NULL DEFAULT (99),
        Activo          bit         NOT NULL DEFAULT (1)
    );
GO

SET IDENTITY_INSERT ped.estado_pedido ON;
INSERT INTO ped.estado_pedido (IdEstadoPedido, Nombre, Orden, Activo)
SELECT v.* FROM (VALUES
  (1, 'PENDIENTE',  1, 1),
  (2, 'EN PROCESO', 2, 1),
  (3, 'DESPACHADO', 3, 1),
  (4, 'ENTREGADO',  4, 1),
  (5, 'CANCELADO',  5, 1)
) AS v (IdEstadoPedido, Nombre, Orden, Activo)
WHERE NOT EXISTS (SELECT 1 FROM ped.estado_pedido WHERE IdEstadoPedido = v.IdEstadoPedido);
SET IDENTITY_INSERT ped.estado_pedido OFF;
GO

/* 4. ped.pedido */
IF NOT EXISTS (
    SELECT 1 FROM sys.tables t JOIN sys.schemas s ON t.schema_id=s.schema_id
    WHERE s.name='ped' AND t.name='pedido'
)
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

PRINT 'Schema ped listo.';
GO
