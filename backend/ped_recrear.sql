/* ============================================================
   PORTAL V2 — ped_recrear.sql
   Recrea el schema ped desde cero.
   Borra en orden inverso de FK, luego crea en orden correcto.
   ============================================================ */

USE PortalV2;
GO

/* --- DROP en orden inverso de FK --- */
IF OBJECT_ID('ped.pedido',              'U') IS NOT NULL DROP TABLE ped.pedido;
IF OBJECT_ID('ped.unidadoperacional',   'U') IS NOT NULL DROP TABLE ped.unidadoperacional;
IF OBJECT_ID('ped.estado_pedido',       'U') IS NOT NULL DROP TABLE ped.estado_pedido;
IF OBJECT_ID('ped.tipounidad',          'U') IS NOT NULL DROP TABLE ped.tipounidad;
GO

/* --- CREATE ped.tipounidad --- */
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

/* --- CREATE ped.estado_pedido --- */
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

/* --- CREATE ped.unidadoperacional --- */
CREATE TABLE ped.unidadoperacional (
    IdUnidad      int         IDENTITY(1,1) NOT NULL PRIMARY KEY,
    IdTipoUnidad  int         NOT NULL REFERENCES ped.tipounidad(IdTipoUnidad),
    Nombre        varchar(80) NOT NULL,
    Placas        varchar(20) NULL,
    activo        bit         NOT NULL DEFAULT (1)
);
GO

/* --- CREATE ped.pedido --- */
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
