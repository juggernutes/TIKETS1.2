:setvar SourceDb "PortalV2_prod_compare"
:setvar TargetDb "PortalV2"

SET NOCOUNT ON;
SET XACT_ABORT ON;
SET QUOTED_IDENTIFIER ON;
SET ANSI_NULLS ON;
SET ANSI_WARNINGS ON;
SET ANSI_PADDING ON;
SET CONCAT_NULL_YIELDS_NULL ON;
SET ARITHABORT ON;
SET NUMERIC_ROUNDABORT OFF;

DECLARE @SourceDb sysname = N'$(SourceDb)';
DECLARE @TargetDb sysname = N'$(TargetDb)';
DECLARE @DryRun bit = 0;

IF DB_ID(@SourceDb) IS NULL
    THROW 51000, 'Source database does not exist.', 1;

IF DB_ID(@TargetDb) IS NULL
    THROW 51001, 'Target database does not exist.', 1;

DECLARE @Tables TABLE (
    Orden int NOT NULL,
    SchemaName sysname NOT NULL,
    TableName sysname NOT NULL,
    PRIMARY KEY (Orden, SchemaName, TableName)
);

INSERT INTO @Tables (Orden, SchemaName, TableName)
VALUES
(10,  'core', 'rol'),
(20,  'core', 'permiso'),
(30,  'core', 'usuario'),
(40,  'core', 'login'),
(50,  'core', 'rol_permiso'),
(60,  'cat',  'grupo_articulo'),
(70,  'cat',  'articulo'),
(75,  'cat',  'sistema'),
(100, 'com',  'regla_comision'),
(110, 'com',  'regla_comision_historial'),
(120, 'core', 'dia_no_laboral'),
(130, 'core', 'notificacion'),
(140, 'hd',   'solucion'),
(150, 'hd',   'error'),
(160, 'ped',  'capacidaduv'),
(170, 'ped',  'unidadoperacional'),
(180, 'core', 'adjunto'),
(190, 'core', 'folio_area_diario'),
(200, 'core', 'proveedor'),
(210, 'ped',  'pedidos'),
(220, 'ped',  'pedido_detalle'),
(230, 'ped',  'pedido_estado_log'),
(240, 'hd',   'ticket'),
(250, 'hd',   'ticket_estatus_log'),
(260, 'hd',   'ticket_agente_log'),
(270, 'hd',   'ticket_asignacion_area'),
(280, 'hd',   'encuesta_ticket');

DECLARE @Report TABLE (
    Orden int NOT NULL,
    TableName nvarchar(300) NOT NULL,
    MissingRows bigint NOT NULL
);

DECLARE
    @Orden int,
    @SchemaName sysname,
    @TableName sysname,
    @QualifiedTarget nvarchar(400),
    @QualifiedSource nvarchar(400),
    @TargetObjectId int,
    @SourceObjectId int,
    @ColumnList nvarchar(max),
    @SelectList nvarchar(max),
    @Predicate nvarchar(max),
    @IdentityColumn sysname,
    @MissingRows bigint,
    @Sql nvarchar(max);

IF @DryRun = 0
    BEGIN TRANSACTION;

BEGIN TRY

DECLARE table_cursor CURSOR LOCAL FAST_FORWARD FOR
SELECT Orden, SchemaName, TableName
FROM @Tables
ORDER BY Orden;

OPEN table_cursor;
FETCH NEXT FROM table_cursor INTO @Orden, @SchemaName, @TableName;

WHILE @@FETCH_STATUS = 0
BEGIN
    SET @ColumnList = NULL;
    SET @SelectList = NULL;
    SET @Predicate = NULL;
    SET @IdentityColumn = NULL;
    SET @MissingRows = 0;
    SET @Sql = NULL;

    SET @QualifiedTarget = QUOTENAME(@TargetDb) + N'.' + QUOTENAME(@SchemaName) + N'.' + QUOTENAME(@TableName);
    SET @QualifiedSource = QUOTENAME(@SourceDb) + N'.' + QUOTENAME(@SchemaName) + N'.' + QUOTENAME(@TableName);

    SET @TargetObjectId = OBJECT_ID(@QualifiedTarget, 'U');
    SET @SourceObjectId = OBJECT_ID(@QualifiedSource, 'U');

    IF @TargetObjectId IS NOT NULL AND @SourceObjectId IS NOT NULL
    BEGIN
        SELECT @ColumnList = STRING_AGG(CONVERT(nvarchar(max), QUOTENAME(tc.name)), N', ')
                   WITHIN GROUP (ORDER BY tc.column_id),
               @SelectList = STRING_AGG(CONVERT(nvarchar(max), N's.' + QUOTENAME(tc.name)), N', ')
                   WITHIN GROUP (ORDER BY tc.column_id)
        FROM sys.columns tc
        WHERE tc.object_id = @TargetObjectId
          AND tc.is_computed = 0
          AND tc.system_type_id <> 189;

        SELECT @Predicate = STRING_AGG(
                   CONVERT(nvarchar(max),
                       N'CONVERT(nvarchar(4000), t.' + QUOTENAME(c.name) + N') COLLATE SQL_Latin1_General_CP1_CI_AS = ' +
                       N'CONVERT(nvarchar(4000), s.' + QUOTENAME(c.name) + N') COLLATE SQL_Latin1_General_CP1_CI_AS'
                   ),
                   N' AND '
               ) WITHIN GROUP (ORDER BY ic.key_ordinal)
        FROM sys.indexes i
        JOIN sys.index_columns ic
          ON ic.object_id = i.object_id
         AND ic.index_id = i.index_id
        JOIN sys.columns c
          ON c.object_id = ic.object_id
         AND c.column_id = ic.column_id
        WHERE i.object_id = @TargetObjectId
          AND i.is_primary_key = 1;

        SELECT @IdentityColumn = c.name
        FROM sys.columns c
        WHERE c.object_id = @TargetObjectId
          AND c.is_identity = 1;

        SET @Sql = N'
            SELECT @MissingRowsOut = COUNT(*)
            FROM ' + @QualifiedSource + N' AS s
            WHERE NOT EXISTS (
                SELECT 1
                FROM ' + @QualifiedTarget + N' AS t
                WHERE ' + @Predicate + N'
            );';

        EXEC sp_executesql @Sql, N'@MissingRowsOut bigint OUTPUT', @MissingRowsOut = @MissingRows OUTPUT;

        INSERT INTO @Report (Orden, TableName, MissingRows)
        VALUES (@Orden, QUOTENAME(@SchemaName) + N'.' + QUOTENAME(@TableName), @MissingRows);

        IF @DryRun = 0 AND @MissingRows > 0
        BEGIN
            SET @Sql = N'
                ' + CASE WHEN @IdentityColumn IS NOT NULL THEN N'SET IDENTITY_INSERT ' + QUOTENAME(@SchemaName) + N'.' + QUOTENAME(@TableName) + N' ON;' ELSE N'' END + N'
                INSERT INTO ' + @QualifiedTarget + N' (' + @ColumnList + N')
                SELECT ' + @SelectList + N'
                FROM ' + @QualifiedSource + N' AS s
                WHERE NOT EXISTS (
                    SELECT 1
                    FROM ' + @QualifiedTarget + N' AS t
                    WHERE ' + @Predicate + N'
                );
                ' + CASE WHEN @IdentityColumn IS NOT NULL THEN N'SET IDENTITY_INSERT ' + QUOTENAME(@SchemaName) + N'.' + QUOTENAME(@TableName) + N' OFF;' ELSE N'' END;

            EXEC sp_executesql @Sql;
        END;
    END;

    FETCH NEXT FROM table_cursor INTO @Orden, @SchemaName, @TableName;
END;

CLOSE table_cursor;
DEALLOCATE table_cursor;

IF @DryRun = 0
    COMMIT TRANSACTION;

SELECT TableName, MissingRows
FROM @Report
WHERE MissingRows > 0
ORDER BY Orden;

IF @DryRun = 1
BEGIN
    PRINT 'DRY RUN: no data was inserted. Run with -v DryRun=0 to execute.';
END
ELSE
BEGIN
    PRINT 'Incremental merge completed.';
END

END TRY
BEGIN CATCH
    IF CURSOR_STATUS('local', 'table_cursor') >= -1
    BEGIN
        CLOSE table_cursor;
        DEALLOCATE table_cursor;
    END;

    IF XACT_STATE() <> 0
        ROLLBACK TRANSACTION;

    THROW;
END CATCH;
