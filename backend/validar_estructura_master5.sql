/*
    Valida estructura esperada segun "master 5.txt".
    No crea, modifica ni elimina objetos.
*/

SET NOCOUNT ON;

IF DB_ID(N'PortalV2') IS NULL
BEGIN
    SELECT 'ERROR' AS Resultado, 'No existe la base de datos PortalV2' AS Detalle;
    RETURN;
END;

USE PortalV2;

DECLARE @SchemasEsperados TABLE (SchemaName sysname NOT NULL PRIMARY KEY);
INSERT INTO @SchemasEsperados (SchemaName)
VALUES (N'core'), (N'cat'), (N'hd'), (N'ped'), (N'rh'), (N'com');

DECLARE @TablasEsperadas TABLE (
    SchemaName sysname NOT NULL,
    TableName sysname NOT NULL,
    PRIMARY KEY (SchemaName, TableName)
);

INSERT INTO @TablasEsperadas (SchemaName, TableName)
VALUES
(N'cat', N'articulo'),
(N'cat', N'grupo_articulo'),
(N'cat', N'linea_articulo'),
(N'cat', N'sistema'),
(N'cat', N'tipo_articulo'),
(N'com', N'acumulado_empleado'),
(N'com', N'ajuste_comision'),
(N'com', N'base_comision_semanal'),
(N'com', N'calculo_comision'),
(N'com', N'comision_base'),
(N'com', N'corrida_comision'),
(N'com', N'corrida_comision_log'),
(N'com', N'indicador'),
(N'com', N'meta_mensual_contenido'),
(N'com', N'meta_mes_portada'),
(N'com', N'meta_semanal'),
(N'com', N'regla_comision'),
(N'com', N'regla_comision_historial'),
(N'com', N'resultado_doc'),
(N'com', N'resultado_indicador'),
(N'com', N'semana'),
(N'com', N'sub_indicador'),
(N'core', N'adjunto'),
(N'core', N'area'),
(N'core', N'auditoria'),
(N'core', N'dia_no_laboral'),
(N'core', N'empleado'),
(N'core', N'empleado_equipo'),
(N'core', N'equipo'),
(N'core', N'folio_area_diario'),
(N'core', N'login'),
(N'core', N'notificacion'),
(N'core', N'parametro'),
(N'core', N'password_resets'),
(N'core', N'permiso'),
(N'core', N'proveedor'),
(N'core', N'puesto'),
(N'core', N'rol'),
(N'core', N'rol_permiso'),
(N'core', N'sesion'),
(N'core', N'sucursal'),
(N'core', N'tipo_equipo'),
(N'core', N'usuario'),
(N'core', N'usuario_relacion'),
(N'hd', N'comentario'),
(N'hd', N'encuesta_ticket'),
(N'hd', N'error'),
(N'hd', N'estatus'),
(N'hd', N'sla'),
(N'hd', N'solucion'),
(N'hd', N'ticket'),
(N'hd', N'ticket_asignacion_area'),
(N'hd', N'tipo_error'),
(N'ped', N'capacidaduv'),
(N'ped', N'estado_pedido'),
(N'ped', N'pedido_detalle'),
(N'ped', N'pedido_estado_log'),
(N'ped', N'pedidos'),
(N'ped', N'tipounidad'),
(N'ped', N'unidadoperacional'),
(N'rh', N'candidato'),
(N'rh', N'candidato_log'),
(N'rh', N'documento_candidato'),
(N'rh', N'entrevista'),
(N'rh', N'entrevista_evaluador'),
(N'rh', N'estatus_candidato'),
(N'rh', N'fuente_reclutamiento'),
(N'rh', N'nota_proceso'),
(N'rh', N'oferta_laboral'),
(N'rh', N'vacante'),
(N'rh', N'vacante_log');

SELECT 'SCHEMA_FALTANTE' AS Resultado, s.SchemaName AS Detalle
FROM @SchemasEsperados s
WHERE NOT EXISTS (
    SELECT 1
    FROM sys.schemas ss
    WHERE ss.name = s.SchemaName
);

SELECT 'TABLA_FALTANTE' AS Resultado, CONCAT(t.SchemaName, '.', t.TableName) AS Detalle
FROM @TablasEsperadas t
WHERE NOT EXISTS (
    SELECT 1
    FROM sys.tables tb
    INNER JOIN sys.schemas sc ON sc.schema_id = tb.schema_id
    WHERE sc.name = t.SchemaName
      AND tb.name = t.TableName
)
ORDER BY Detalle;

SELECT
    'RESUMEN' AS Resultado,
    CONCAT(
        'Schemas esperados: ', (SELECT COUNT(*) FROM @SchemasEsperados),
        ', schemas existentes: ', (
            SELECT COUNT(*)
            FROM @SchemasEsperados s
            WHERE EXISTS (SELECT 1 FROM sys.schemas ss WHERE ss.name = s.SchemaName)
        ),
        ', tablas esperadas: ', (SELECT COUNT(*) FROM @TablasEsperadas),
        ', tablas existentes: ', (
            SELECT COUNT(*)
            FROM @TablasEsperadas t
            WHERE EXISTS (
                SELECT 1
                FROM sys.tables tb
                INNER JOIN sys.schemas sc ON sc.schema_id = tb.schema_id
                WHERE sc.name = t.SchemaName
                  AND tb.name = t.TableName
            )
        )
    ) AS Detalle;
