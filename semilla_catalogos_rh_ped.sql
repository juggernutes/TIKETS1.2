/* ============================================================
   PORTAL V2 — semilla_catalogos_rh_ped.sql
   Crea tablas faltantes:
     - rh.fuente_reclutamiento  (tabla faltante, usada por el catálogo)
     - rh.estatus_candidato     (verifica/inserta si vacía)
     - ped schema               (tablas de pedidos)
   ============================================================ */

USE PortalV2;
GO

/* ============================================================
   1. rh.fuente_reclutamiento  (faltaba en master_v3)
   ============================================================ */
IF NOT EXISTS (
    SELECT 1 FROM sys.tables t
    JOIN sys.schemas s ON t.schema_id = s.schema_id
    WHERE s.name = 'rh' AND t.name = 'fuente_reclutamiento'
)
BEGIN
    CREATE TABLE rh.fuente_reclutamiento (
        ID_Fuente  int         IDENTITY(1,1) NOT NULL PRIMARY KEY,
        Nombre     varchar(80) NOT NULL,
        Activo     bit         NOT NULL CONSTRAINT DF_rh_fr_Activo DEFAULT (1),
        CONSTRAINT UQ_rh_fuente_Nombre UNIQUE (Nombre)
    );
    PRINT 'Tabla rh.fuente_reclutamiento creada.';
END
ELSE
    PRINT 'Tabla rh.fuente_reclutamiento ya existe.';
GO

-- Insertar sólo las que no existan
MERGE rh.fuente_reclutamiento AS tgt
USING (VALUES
  ('OCC Mundial'),
  ('Indeed'),
  ('LinkedIn'),
  ('Referido interno'),
  ('Bolsa de trabajo IMSS'),
  ('Agencia de colocación'),
  ('Feria de empleo'),
  ('Periódico / Anuncio'),
  ('Cartera interna'),
  ('Otro')
) AS src (Nombre)
ON tgt.Nombre = src.Nombre
WHEN NOT MATCHED THEN
  INSERT (Nombre, Activo) VALUES (src.Nombre, 1);
GO

/* ============================================================
   2. rh.estatus_candidato — verifica columna OrdenProceso
      (el catálogo la usa para ordenar)
   ============================================================ */
IF NOT EXISTS (
    SELECT 1 FROM sys.columns c
    JOIN sys.tables  t ON c.object_id = t.object_id
    JOIN sys.schemas s ON t.schema_id = s.schema_id
    WHERE s.name = 'rh' AND t.name = 'estatus_candidato' AND c.name = 'OrdenProceso'
)
BEGIN
    ALTER TABLE rh.estatus_candidato ADD OrdenProceso int NOT NULL DEFAULT (99);
    PRINT 'Columna OrdenProceso agregada a rh.estatus_candidato.';
END
GO

-- Si la tabla está vacía, insertar valores base
IF NOT EXISTS (SELECT 1 FROM rh.estatus_candidato)
BEGIN
    SET IDENTITY_INSERT rh.estatus_candidato ON;
    INSERT INTO rh.estatus_candidato (ID_EstatusCandidato, Nombre, OrdenProceso, Activo) VALUES
    (1, 'EN REVISIÓN',    1, 1),
    (2, 'CONTACTADO',     2, 1),
    (3, 'ENTREVISTADO',   3, 1),
    (4, 'EN EVALUACIÓN',  4, 1),
    (5, 'ACEPTADO',       5, 1),
    (6, 'RECHAZADO',      6, 1),
    (7, 'CONTRATADO',     7, 1),
    (8, 'DESISTIÓ',       8, 1);
    SET IDENTITY_INSERT rh.estatus_candidato OFF;
    PRINT 'Estatus candidato insertados.';
END
GO

/* ============================================================
   3. Schema ped — tablas de pedidos (completamente ausentes)
   ============================================================ */

-- Crear schema si no existe
IF NOT EXISTS (SELECT 1 FROM sys.schemas WHERE name = 'ped')
BEGIN
    EXEC('CREATE SCHEMA ped');
    PRINT 'Schema ped creado.';
END
GO

-- ped.tipounidad
IF NOT EXISTS (SELECT 1 FROM sys.tables t JOIN sys.schemas s ON t.schema_id=s.schema_id WHERE s.name='ped' AND t.name='tipounidad')
BEGIN
    CREATE TABLE ped.tipounidad (
        IdTipoUnidad  int          IDENTITY(1,1) NOT NULL PRIMARY KEY,
        Nombre        varchar(60)  NOT NULL,
        Activo        bit          NOT NULL DEFAULT (1)
    );
    SET IDENTITY_INSERT ped.tipounidad ON;
    INSERT INTO ped.tipounidad (IdTipoUnidad, Nombre, Activo) VALUES
    (1, 'CAMION',      1),
    (2, 'CAMIONETA',   1),
    (3, 'REFRIGERADO', 1),
    (4, 'OTRO',        1);
    SET IDENTITY_INSERT ped.tipounidad OFF;
    PRINT 'Tabla ped.tipounidad creada.';
END
GO

-- ped.unidadoperacional
IF NOT EXISTS (SELECT 1 FROM sys.tables t JOIN sys.schemas s ON t.schema_id=s.schema_id WHERE s.name='ped' AND t.name='unidadoperacional')
BEGIN
    CREATE TABLE ped.unidadoperacional (
        IdUnidad      int          IDENTITY(1,1) NOT NULL PRIMARY KEY,
        IdTipoUnidad  int          NOT NULL REFERENCES ped.tipounidad(IdTipoUnidad),
        Nombre        varchar(80)  NOT NULL,
        Placas        varchar(20)  NULL,
        activo        bit          NOT NULL DEFAULT (1)
    );
    PRINT 'Tabla ped.unidadoperacional creada.';
END
GO

-- ped.estado_pedido
IF NOT EXISTS (SELECT 1 FROM sys.tables t JOIN sys.schemas s ON t.schema_id=s.schema_id WHERE s.name='ped' AND t.name='estado_pedido')
BEGIN
    CREATE TABLE ped.estado_pedido (
        IdEstadoPedido  int          IDENTITY(1,1) NOT NULL PRIMARY KEY,
        Nombre          varchar(50)  NOT NULL,
        Orden           int          NOT NULL DEFAULT (99),
        Activo          bit          NOT NULL DEFAULT (1)
    );
    SET IDENTITY_INSERT ped.estado_pedido ON;
    INSERT INTO ped.estado_pedido (IdEstadoPedido, Nombre, Orden, Activo) VALUES
    (1, 'PENDIENTE',    1, 1),
    (2, 'EN PROCESO',   2, 1),
    (3, 'DESPACHADO',   3, 1),
    (4, 'ENTREGADO',    4, 1),
    (5, 'CANCELADO',    5, 1);
    SET IDENTITY_INSERT ped.estado_pedido OFF;
    PRINT 'Tabla ped.estado_pedido creada.';
END
GO

-- ped.pedido
IF NOT EXISTS (SELECT 1 FROM sys.tables t JOIN sys.schemas s ON t.schema_id=s.schema_id WHERE s.name='ped' AND t.name='pedido')
BEGIN
    CREATE TABLE ped.pedido (
        IdPedido         int           IDENTITY(1,1) NOT NULL PRIMARY KEY,
        Folio            varchar(30)   NOT NULL,
        Numero_Empleado  int           NULL REFERENCES core.empleado(Numero_Empleado),
        IdUnidad         int           NULL REFERENCES ped.unidadoperacional(IdUnidad),
        IdEstadoPedido   int           NOT NULL REFERENCES ped.estado_pedido(IdEstadoPedido) DEFAULT (1),
        Descripcion      nvarchar(2000) NULL,
        FechaPedido      datetime2     NOT NULL DEFAULT SYSDATETIME(),
        FechaEntrega     datetime2     NULL,
        Observaciones    nvarchar(1000) NULL,
        Activo           bit           NOT NULL DEFAULT (1),
        FechaCreacion    datetime2     NOT NULL DEFAULT SYSDATETIME(),
        FechaModificacion datetime2    NULL
    );
    PRINT 'Tabla ped.pedido creada.';
END
GO

PRINT 'Catálogos RH y schema PED listos.';
GO
