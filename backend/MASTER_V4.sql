/* ============================================================
   PORTAL V2 — MASTER_V4.sql
   Script DELTA respecto a V3.
   Aplica solo los cambios faltantes; usa IF NOT EXISTS en todo
   para que sea idempotente (se puede correr más de una vez).

   Cambios incluidos:
     [V4-1] hd.tipo_error            — tabla nueva (catálogo)
     [V4-2] ped.estado_pedido.Orden  — columna nueva
     [V4-3] com.semana.Activo        — columna nueva
     [V4-4] rh.fuente_reclutamiento  — tabla nueva (catálogo)
     [V4-5] rh.entrevista_evaluador  — tabla nueva
     [V4-6] rh.oferta_laboral        — tabla nueva
============================================================ */

USE PortalV2;
GO

/* ============================================================
   [V4-1]  hd.tipo_error
   Catálogo de tipos de error para hd.[error].Tipo
============================================================ */
IF NOT EXISTS (
    SELECT 1 FROM sys.tables t
    JOIN sys.schemas s ON t.schema_id = s.schema_id
    WHERE s.name = 'hd' AND t.name = 'tipo_error'
)
BEGIN
    CREATE TABLE hd.tipo_error (
        ID_TipoError int         IDENTITY(1,1) NOT NULL,
        Nombre       varchar(50) NOT NULL,
        Activo       bit         NOT NULL CONSTRAINT DF_hd_te_Activo  DEFAULT (1),
        Descripcion  varchar(200) NULL,
        CONSTRAINT PK_hd_tipo_error        PRIMARY KEY (ID_TipoError),
        CONSTRAINT UQ_hd_tipo_error_Nombre UNIQUE      (Nombre)
    );
    PRINT '[V4-1] Tabla hd.tipo_error creada.';
END
ELSE
    PRINT '[V4-1] hd.tipo_error ya existe — omitido.';
GO

/* ============================================================
   [V4-2]  ped.estado_pedido — agregar columna Orden
============================================================ */
IF NOT EXISTS (
    SELECT 1 FROM sys.columns c
    JOIN sys.tables  t ON c.object_id = t.object_id
    JOIN sys.schemas s ON t.schema_id = s.schema_id
    WHERE s.name = 'ped' AND t.name = 'estado_pedido' AND c.name = 'Orden'
)
BEGIN
    ALTER TABLE ped.estado_pedido
        ADD Orden int NOT NULL CONSTRAINT DF_ped_ep_Orden DEFAULT (99);
    PRINT '[V4-2] Columna ped.estado_pedido.Orden agregada.';
END
ELSE
    PRINT '[V4-2] ped.estado_pedido.Orden ya existe — omitido.';
GO

/* ============================================================
   [V4-3]  com.semana — agregar columna Activo
============================================================ */
IF NOT EXISTS (
    SELECT 1 FROM sys.columns c
    JOIN sys.tables  t ON c.object_id = t.object_id
    JOIN sys.schemas s ON t.schema_id = s.schema_id
    WHERE s.name = 'com' AND t.name = 'semana' AND c.name = 'Activo'
)
BEGIN
    ALTER TABLE com.semana
        ADD Activo bit NOT NULL CONSTRAINT DF_com_semana_Activo DEFAULT (1);
    PRINT '[V4-3] Columna com.semana.Activo agregada.';
END
ELSE
    PRINT '[V4-3] com.semana.Activo ya existe — omitido.';
GO

/* ============================================================
   [V4-4]  rh.fuente_reclutamiento
   Catálogo de fuentes de reclutamiento
============================================================ */
IF NOT EXISTS (
    SELECT 1 FROM sys.tables t
    JOIN sys.schemas s ON t.schema_id = s.schema_id
    WHERE s.name = 'rh' AND t.name = 'fuente_reclutamiento'
)
BEGIN
    CREATE TABLE rh.fuente_reclutamiento (
        ID_Fuente   int         IDENTITY(1,1) NOT NULL,
        Nombre      varchar(80) NOT NULL,
        Activo      bit         NOT NULL CONSTRAINT DF_rh_fr_Activo DEFAULT (1),
        Descripcion varchar(200) NULL,
        CONSTRAINT PK_rh_fuente_reclutamiento PRIMARY KEY (ID_Fuente),
        CONSTRAINT UQ_rh_fuente_Nombre        UNIQUE      (Nombre)
    );
    PRINT '[V4-4] Tabla rh.fuente_reclutamiento creada.';
END
ELSE
    PRINT '[V4-4] rh.fuente_reclutamiento ya existe — omitido.';
GO

/* ============================================================
   [V4-5]  rh.entrevista_evaluador
   Evaluadores adicionales por entrevista (panel)
============================================================ */
IF NOT EXISTS (
    SELECT 1 FROM sys.tables t
    JOIN sys.schemas s ON t.schema_id = s.schema_id
    WHERE s.name = 'rh' AND t.name = 'entrevista_evaluador'
)
BEGIN
    CREATE TABLE rh.entrevista_evaluador (
        ID_EvalEntrevista int           IDENTITY(1,1) NOT NULL,
        ID_Entrevista     int           NOT NULL,
        ID_Usuario        int           NOT NULL,
        Rol               varchar(50)   NULL,
        Calificacion      decimal(5,2)  NULL,
        Comentarios       varchar(max)  NULL,
        FechaEvaluacion   datetime2(0)  NULL,
        CONSTRAINT PK_rh_ee  PRIMARY KEY (ID_EvalEntrevista),
        CONSTRAINT UQ_rh_ee  UNIQUE      (ID_Entrevista, ID_Usuario),
        CONSTRAINT FK_rh_ee_entrevista FOREIGN KEY (ID_Entrevista) REFERENCES rh.entrevista(ID_Entrevista),
        CONSTRAINT FK_rh_ee_usuario    FOREIGN KEY (ID_Usuario)    REFERENCES core.usuario(ID_Usuario),
        CONSTRAINT CHK_rh_ee_Cal       CHECK (Calificacion IS NULL OR (Calificacion >= 0 AND Calificacion <= 10))
    );
    PRINT '[V4-5] Tabla rh.entrevista_evaluador creada.';
END
ELSE
    PRINT '[V4-5] rh.entrevista_evaluador ya existe — omitido.';
GO

/* ============================================================
   [V4-6]  rh.oferta_laboral
   Ofertas económicas enviadas a candidatos
============================================================ */
IF NOT EXISTS (
    SELECT 1 FROM sys.tables t
    JOIN sys.schemas s ON t.schema_id = s.schema_id
    WHERE s.name = 'rh' AND t.name = 'oferta_laboral'
)
BEGIN
    CREATE TABLE rh.oferta_laboral (
        ID_Oferta         int           IDENTITY(1,1) NOT NULL,
        ID_Candidato      int           NOT NULL,
        ID_Vacante        int           NOT NULL,
        SalarioOfertado   decimal(12,2) NOT NULL,
        FechaOferta       datetime2(0)  NOT NULL CONSTRAINT DF_rh_ol_FO     DEFAULT (SYSDATETIME()),
        FechaVencimiento  datetime2(0)  NULL,
        FechaRespuesta    datetime2(0)  NULL,
        Estatus           varchar(20)   NOT NULL CONSTRAINT DF_rh_ol_Est    DEFAULT ('ENVIADA'),
        MotivoRechazo     varchar(300)  NULL,
        Contrapropuesta   decimal(12,2) NULL,
        FechaIngreso      date          NULL,
        Activo            bit           NOT NULL CONSTRAINT DF_rh_ol_Activo DEFAULT (1),
        FechaCreacion     datetime2(0)  NOT NULL CONSTRAINT DF_rh_ol_FC     DEFAULT (SYSDATETIME()),
        FechaModificacion datetime2(0)  NULL,
        CONSTRAINT PK_rh_oferta_laboral  PRIMARY KEY (ID_Oferta),
        CONSTRAINT FK_rh_ol_candidato    FOREIGN KEY (ID_Candidato) REFERENCES rh.candidato(ID_Candidato),
        CONSTRAINT FK_rh_ol_vacante      FOREIGN KEY (ID_Vacante)   REFERENCES rh.vacante(ID_Vacante),
        CONSTRAINT CHK_rh_ol_Estatus     CHECK (Estatus IN ('ENVIADA','ACEPTADA','RECHAZADA','NEGOCIACION')),
        CONSTRAINT CHK_rh_ol_Salario     CHECK (SalarioOfertado >= 0),
        CONSTRAINT CHK_rh_ol_Contra      CHECK (Contrapropuesta IS NULL OR Contrapropuesta >= 0)
    );
    PRINT '[V4-6] Tabla rh.oferta_laboral creada.';
END
ELSE
    PRINT '[V4-6] rh.oferta_laboral ya existe — omitido.';
GO

/* ============================================================
   TRIGGER rh.oferta_laboral — FechaModificacion automática
============================================================ */
IF OBJECT_ID('rh.TR_oferta_laboral_FM', 'TR') IS NOT NULL DROP TRIGGER rh.TR_oferta_laboral_FM;
GO
CREATE TRIGGER rh.TR_oferta_laboral_FM
ON rh.oferta_laboral AFTER UPDATE
AS BEGIN
    SET NOCOUNT ON; IF TRIGGER_NESTLEVEL() > 1 RETURN;
    UPDATE o SET FechaModificacion = SYSDATETIME()
    FROM rh.oferta_laboral o INNER JOIN inserted i ON o.ID_Oferta = i.ID_Oferta;
END;
GO

/* ============================================================
   ÍNDICES de las tablas nuevas
============================================================ */
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_rh_ee_ID_Entrevista' AND object_id = OBJECT_ID('rh.entrevista_evaluador'))
    CREATE INDEX IX_rh_ee_ID_Entrevista ON rh.entrevista_evaluador(ID_Entrevista);
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_rh_ol_ID_Candidato' AND object_id = OBJECT_ID('rh.oferta_laboral'))
    CREATE INDEX IX_rh_ol_ID_Candidato ON rh.oferta_laboral(ID_Candidato);
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_rh_ol_ID_Vacante' AND object_id = OBJECT_ID('rh.oferta_laboral'))
    CREATE INDEX IX_rh_ol_ID_Vacante   ON rh.oferta_laboral(ID_Vacante);
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_rh_ol_Estatus' AND object_id = OBJECT_ID('rh.oferta_laboral'))
    CREATE INDEX IX_rh_ol_Estatus      ON rh.oferta_laboral(Estatus);
GO

/* ============================================================
   VERIFICACIÓN FINAL
   Muestra las tablas aplicadas y columnas nuevas
============================================================ */
SELECT
    s.name  AS Esquema,
    t.name  AS Tabla,
    'OK'    AS Estado
FROM sys.tables t
JOIN sys.schemas s ON t.schema_id = s.schema_id
WHERE (s.name = 'hd'  AND t.name = 'tipo_error')
   OR (s.name = 'rh'  AND t.name IN ('fuente_reclutamiento','entrevista_evaluador','oferta_laboral'))
ORDER BY s.name, t.name;

SELECT
    s.name  AS Esquema,
    t.name  AS Tabla,
    c.name  AS Columna,
    'OK'    AS Estado
FROM sys.columns c
JOIN sys.tables  t ON c.object_id = t.object_id
JOIN sys.schemas s ON t.schema_id = s.schema_id
WHERE (s.name = 'ped' AND t.name = 'estado_pedido' AND c.name = 'Orden')
   OR (s.name = 'com' AND t.name = 'semana'         AND c.name = 'Activo')
ORDER BY s.name, t.name;
GO

PRINT '============================================================';
PRINT 'MASTER_V4 completado.';
PRINT '============================================================';
GO
