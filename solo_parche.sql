USE PortalV2;
GO

/* ============================================================
   SOLO PARCHE
   Aplicar sobre una base ya existente creada previamente.
   No recrea tablas base del master, sólo agrega compatibilidad
   con el proyecto actual.
============================================================ */

/* ============================================================
   1. HD: tabla faltante hd.tipo_error
============================================================ */
IF OBJECT_ID('hd.tipo_error', 'U') IS NULL
BEGIN
    CREATE TABLE hd.tipo_error (
        ID_TipoError int IDENTITY(1,1) NOT NULL PRIMARY KEY,
        Nombre       varchar(60)  NOT NULL,
        Descripcion  varchar(200) NULL,
        Activo       bit NOT NULL CONSTRAINT DF_hd_te_Activo DEFAULT (1),
        CONSTRAINT UQ_hd_te_Nombre UNIQUE (Nombre)
    );
END
GO

IF OBJECT_ID('hd.tipo_error', 'U') IS NOT NULL
AND COL_LENGTH('hd.tipo_error', 'Descripcion') IS NULL
BEGIN
    ALTER TABLE hd.tipo_error ADD Descripcion varchar(200) NULL;
END
GO

IF NOT EXISTS (SELECT 1 FROM hd.tipo_error)
BEGIN
    SET IDENTITY_INSERT hd.tipo_error ON;
    INSERT INTO hd.tipo_error (ID_TipoError, Nombre, Descripcion, Activo) VALUES
    (1, 'SOFTWARE / REDES',        'Fallas de software, red o acceso', 1),
    (2, 'HARDWARE / DISPOSITIVOS', 'Equipos y dispositivos fisicos',    1),
    (3, 'PERIFERICOS / OTROS',     'Perifericos y otros incidentes',    1);
    SET IDENTITY_INSERT hd.tipo_error OFF;
END
GO

IF NOT EXISTS (
    SELECT 1
    FROM sys.foreign_keys
    WHERE name = 'FK_hd_error_tipo_error_v5'
)
AND EXISTS (SELECT 1 FROM sys.tables t JOIN sys.schemas s ON t.schema_id = s.schema_id WHERE s.name = 'hd' AND t.name = 'error')
AND NOT EXISTS (
    SELECT 1
    FROM hd.[error] e
    LEFT JOIN hd.tipo_error t ON t.ID_TipoError = e.Tipo
    WHERE t.ID_TipoError IS NULL
)
BEGIN
    ALTER TABLE hd.[error]
        ADD CONSTRAINT FK_hd_error_tipo_error_v5
            FOREIGN KEY (Tipo) REFERENCES hd.tipo_error(ID_TipoError);
END
GO

/* ============================================================
   2. RH: tabla faltante rh.fuente_reclutamiento
============================================================ */
IF OBJECT_ID('rh.fuente_reclutamiento', 'U') IS NULL
BEGIN
    CREATE TABLE rh.fuente_reclutamiento (
        ID_Fuente   int IDENTITY(1,1) NOT NULL PRIMARY KEY,
        Nombre      varchar(80)  NOT NULL,
        Descripcion varchar(200) NULL,
        Activo      bit NOT NULL CONSTRAINT DF_rh_fr_Activo DEFAULT (1),
        CONSTRAINT UQ_rh_fr_Nombre UNIQUE (Nombre)
    );
END
GO

IF OBJECT_ID('rh.fuente_reclutamiento', 'U') IS NOT NULL
AND COL_LENGTH('rh.fuente_reclutamiento', 'Descripcion') IS NULL
BEGIN
    ALTER TABLE rh.fuente_reclutamiento ADD Descripcion varchar(200) NULL;
END
GO

MERGE rh.fuente_reclutamiento AS tgt
USING (VALUES
    ('OCC Mundial',           'Portal OCC Mundial', 1),
    ('Indeed',                'Portal Indeed', 1),
    ('LinkedIn',              'Red profesional', 1),
    ('Referido interno',      'Referencia interna', 1),
    ('Bolsa de trabajo IMSS', 'Bolsa de trabajo', 1),
    ('Agencia de colocacion', 'Agencia externa', 1),
    ('Feria de empleo',       'Evento de reclutamiento', 1),
    ('Periodico / Anuncio',   'Medio impreso o anuncio', 1),
    ('Cartera interna',       'Base interna', 1),
    ('Otro',                  'Otro origen', 1)
) AS src (Nombre, Descripcion, Activo)
ON tgt.Nombre = src.Nombre
WHEN MATCHED THEN
    UPDATE SET Descripcion = src.Descripcion, Activo = src.Activo
WHEN NOT MATCHED THEN
    INSERT (Nombre, Descripcion, Activo)
    VALUES (src.Nombre, src.Descripcion, src.Activo);
GO

/* ============================================================
   3. RH: tablas usadas por el backend y ausentes en master 3
============================================================ */
IF OBJECT_ID('rh.entrevista_evaluador', 'U') IS NULL
BEGIN
    CREATE TABLE rh.entrevista_evaluador (
        ID_EvalEntrevista int IDENTITY(1,1) NOT NULL PRIMARY KEY,
        ID_Entrevista     int NOT NULL,
        ID_Usuario        int NOT NULL,
        Rol               varchar(50) NULL,
        Calificacion      decimal(5,2) NULL,
        Comentarios       varchar(max) NULL,
        FechaEvaluacion   datetime2(0) NULL,
        CONSTRAINT UQ_rh_ee UNIQUE (ID_Entrevista, ID_Usuario),
        CONSTRAINT FK_rh_ee_entrevista FOREIGN KEY (ID_Entrevista) REFERENCES rh.entrevista(ID_Entrevista),
        CONSTRAINT FK_rh_ee_usuario    FOREIGN KEY (ID_Usuario) REFERENCES core.usuario(ID_Usuario)
    );
END
GO

IF OBJECT_ID('rh.oferta_laboral', 'U') IS NULL
BEGIN
    CREATE TABLE rh.oferta_laboral (
        ID_Oferta         int IDENTITY(1,1) NOT NULL PRIMARY KEY,
        ID_Candidato      int NOT NULL,
        ID_Vacante        int NOT NULL,
        SalarioOfertado   decimal(12,2) NOT NULL,
        FechaOferta       datetime2(0) NOT NULL CONSTRAINT DF_rh_ol_FO DEFAULT (SYSDATETIME()),
        FechaVencimiento  datetime2(0) NULL,
        FechaRespuesta    datetime2(0) NULL,
        Estatus           varchar(20) NOT NULL CONSTRAINT DF_rh_ol_Est DEFAULT ('ENVIADA'),
        MotivoRechazo     varchar(300) NULL,
        Contrapropuesta   decimal(12,2) NULL,
        FechaIngreso      date NULL,
        Activo            bit NOT NULL CONSTRAINT DF_rh_ol_Activo DEFAULT (1),
        FechaCreacion     datetime2(0) NOT NULL CONSTRAINT DF_rh_ol_FC DEFAULT (SYSDATETIME()),
        FechaModificacion datetime2(0) NULL,
        CONSTRAINT FK_rh_ol_candidato FOREIGN KEY (ID_Candidato) REFERENCES rh.candidato(ID_Candidato),
        CONSTRAINT FK_rh_ol_vacante   FOREIGN KEY (ID_Vacante) REFERENCES rh.vacante(ID_Vacante),
        CONSTRAINT CHK_rh_ol_Estatus CHECK (Estatus IN ('ENVIADA','ACEPTADA','RECHAZADA','NEGOCIACION')),
        CONSTRAINT CHK_rh_ol_Salario CHECK (SalarioOfertado >= 0),
        CONSTRAINT CHK_rh_ol_Contra  CHECK (Contrapropuesta IS NULL OR Contrapropuesta >= 0)
    );
END
GO

IF NOT EXISTS (
    SELECT 1 FROM sys.indexes
    WHERE name = 'IX_rh_ee_Entrevista'
      AND object_id = OBJECT_ID('rh.entrevista_evaluador')
)
BEGIN
    CREATE INDEX IX_rh_ee_Entrevista ON rh.entrevista_evaluador(ID_Entrevista);
END
GO

IF NOT EXISTS (
    SELECT 1 FROM sys.indexes
    WHERE name = 'IX_rh_ol_Candidato'
      AND object_id = OBJECT_ID('rh.oferta_laboral')
)
BEGIN
    CREATE INDEX IX_rh_ol_Candidato ON rh.oferta_laboral(ID_Candidato);
END
GO

IF NOT EXISTS (
    SELECT 1 FROM sys.indexes
    WHERE name = 'IX_rh_ol_Vacante'
      AND object_id = OBJECT_ID('rh.oferta_laboral')
)
BEGIN
    CREATE INDEX IX_rh_ol_Vacante ON rh.oferta_laboral(ID_Vacante);
END
GO

/* ============================================================
   4. COM: columna Activo en com.semana
============================================================ */
IF OBJECT_ID('com.semana', 'U') IS NOT NULL
AND COL_LENGTH('com.semana', 'Activo') IS NULL
BEGIN
    ALTER TABLE com.semana
        ADD Activo bit NOT NULL
            CONSTRAINT DF_com_sem_Activo DEFAULT (1);
END
GO

/* ============================================================
   5. PED: columna Orden en ped.estado_pedido
============================================================ */
IF OBJECT_ID('ped.estado_pedido', 'U') IS NOT NULL
AND COL_LENGTH('ped.estado_pedido', 'Orden') IS NULL
BEGIN
    ALTER TABLE ped.estado_pedido
        ADD Orden int NOT NULL
            CONSTRAINT DF_ped_ep_Orden DEFAULT (99);
END
GO

IF OBJECT_ID('ped.estado_pedido', 'U') IS NOT NULL
AND COL_LENGTH('ped.estado_pedido', 'Orden') IS NOT NULL
BEGIN
    UPDATE ped.estado_pedido
    SET Orden = CASE UPPER(Nombre)
        WHEN 'PENDIENTE'   THEN 1
        WHEN 'EN PROCESO'  THEN 2
        WHEN 'DESPACHADO'  THEN 3
        WHEN 'ENTREGADO'   THEN 4
        WHEN 'CANCELADO'   THEN 5
        WHEN 'AUTORIZADO'  THEN 2
        WHEN 'SURTIDO'     THEN 3
        ELSE Orden
    END;
END
GO

PRINT 'solo_parche.sql aplicado correctamente.';
GO
