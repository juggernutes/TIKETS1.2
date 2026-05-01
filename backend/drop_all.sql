/* ============================================================
   PORTAL V2 — drop_all.sql
   Elimina TODAS las FK constraints y tablas de los schemas
   del portal (core, hd, rh, com, cat, ped).
   Conserva: dbo.migrations y dbo.personal_access_tokens
   ============================================================ */

USE PortalV2;
GO

/* PASO 1: Eliminar todas las FK constraints */
DECLARE @sql NVARCHAR(MAX) = N''
SELECT @sql = @sql +
    'ALTER TABLE ' + QUOTENAME(s.name) + '.' + QUOTENAME(t.name) +
    ' DROP CONSTRAINT '  + QUOTENAME(fk.name) + ';' + CHAR(13)
FROM sys.foreign_keys fk
JOIN sys.tables  t ON fk.parent_object_id = t.object_id
JOIN sys.schemas s ON t.schema_id = s.schema_id
WHERE s.name IN ('core','hd','rh','com','cat','ped')
IF LEN(@sql) > 0 EXEC sp_executesql @sql
PRINT 'FK constraints eliminadas.'
GO

/* PASO 2: Eliminar todas las tablas de los schemas del portal */
DECLARE @sql NVARCHAR(MAX) = N''
SELECT @sql = @sql +
    'DROP TABLE ' + QUOTENAME(s.name) + '.' + QUOTENAME(t.name) + ';' + CHAR(13)
FROM sys.tables  t
JOIN sys.schemas s ON t.schema_id = s.schema_id
WHERE s.name IN ('core','hd','rh','com','cat','ped')
IF LEN(@sql) > 0 EXEC sp_executesql @sql
PRINT 'Tablas eliminadas.'
GO

/* Verificación */
SELECT s.name AS Esquema, t.name AS Tabla
FROM sys.tables t
JOIN sys.schemas s ON t.schema_id = s.schema_id
WHERE s.name IN ('core','hd','rh','com','cat','ped')
ORDER BY s.name, t.name
GO
