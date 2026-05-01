/* ============================================================
   PORTAL V2 — validar_semilla_v4.sql
   Valida conteos y datos criticos de cada tabla sembrada
============================================================ */

USE PortalV2;
GO

/* ============================================================
   RESUMEN DE CONTEOS
============================================================ */
SELECT 'core.rol'                  AS Tabla, COUNT(*) AS Filas FROM core.rol                  UNION ALL
SELECT 'core.area',                           COUNT(*)         FROM core.area                  UNION ALL
SELECT 'core.sucursal',                       COUNT(*)         FROM core.sucursal              UNION ALL
SELECT 'core.puesto',                         COUNT(*)         FROM core.puesto                UNION ALL
SELECT 'core.tipo_equipo',                    COUNT(*)         FROM core.tipo_equipo           UNION ALL
SELECT 'core.usuario',                        COUNT(*)         FROM core.usuario               UNION ALL
SELECT 'core.[login]',                        COUNT(*)         FROM core.[login]               UNION ALL
SELECT 'core.empleado',                       COUNT(*)         FROM core.empleado              UNION ALL
SELECT 'core.usuario_relacion',               COUNT(*)         FROM core.usuario_relacion      UNION ALL
SELECT 'core.parametro',                      COUNT(*)         FROM core.parametro             UNION ALL
SELECT 'cat.sistema',                         COUNT(*)         FROM cat.sistema                UNION ALL
SELECT 'cat.tipo_articulo',                   COUNT(*)         FROM cat.tipo_articulo          UNION ALL
SELECT 'cat.linea_articulo',                  COUNT(*)         FROM cat.linea_articulo         UNION ALL
SELECT 'cat.grupo_articulo',                  COUNT(*)         FROM cat.grupo_articulo         UNION ALL
SELECT 'cat.articulo',                        COUNT(*)         FROM cat.articulo               UNION ALL
SELECT 'hd.estatus',                          COUNT(*)         FROM hd.estatus                 UNION ALL
SELECT 'hd.solucion',                         COUNT(*)         FROM hd.solucion                UNION ALL
SELECT 'hd.tipo_error',                       COUNT(*)         FROM hd.tipo_error              UNION ALL
SELECT 'hd.[error]',                          COUNT(*)         FROM hd.[error]                 UNION ALL
SELECT 'hd.sla',                              COUNT(*)         FROM hd.sla                     UNION ALL
SELECT 'ped.capacidaduv',                     COUNT(*)         FROM ped.capacidaduv            UNION ALL
SELECT 'ped.tipounidad',                      COUNT(*)         FROM ped.tipounidad             UNION ALL
SELECT 'ped.estado_pedido',                   COUNT(*)         FROM ped.estado_pedido          UNION ALL
SELECT 'ped.unidadoperacional',               COUNT(*)         FROM ped.unidadoperacional      UNION ALL
SELECT 'rh.fuente_reclutamiento',             COUNT(*)         FROM rh.fuente_reclutamiento    UNION ALL
SELECT 'rh.estatus_candidato',                COUNT(*)         FROM rh.estatus_candidato       UNION ALL
SELECT 'rh.vacante',                          COUNT(*)         FROM rh.vacante                 UNION ALL
SELECT 'rh.candidato',                        COUNT(*)         FROM rh.candidato               UNION ALL
SELECT 'rh.entrevista',                       COUNT(*)         FROM rh.entrevista              UNION ALL
SELECT 'rh.entrevista_evaluador',             COUNT(*)         FROM rh.entrevista_evaluador    UNION ALL
SELECT 'rh.oferta_laboral',                   COUNT(*)         FROM rh.oferta_laboral          UNION ALL
SELECT 'rh.nota_proceso',                     COUNT(*)         FROM rh.nota_proceso            UNION ALL
SELECT 'com.meta_mes_portada',                COUNT(*)         FROM com.meta_mes_portada       UNION ALL
SELECT 'com.semana',                          COUNT(*)         FROM com.semana                 UNION ALL
SELECT 'com.indicador',                       COUNT(*)         FROM com.indicador              UNION ALL
SELECT 'com.sub_indicador',                   COUNT(*)         FROM com.sub_indicador
ORDER BY Tabla;
GO

/* ============================================================
   DETALLE: core.usuario + login (verificar hash y relacion)
============================================================ */
PRINT '--- Usuarios y logins ---';
SELECT
    u.ID_Usuario,
    u.Nombre,
    u.Email,
    r.Nombre           AS Rol,
    l.Cuenta,
    LEFT(l.PasswordHash, 7) + '...' AS Hash_Inicio,
    l.Activo           AS Login_Activo,
    l.DebeCambiarPassword
FROM core.usuario u
LEFT JOIN core.rol   r ON r.ID_Rol    = u.ID_Rol
LEFT JOIN core.[login] l ON l.ID_Usuario = u.ID_Usuario
WHERE u.Email LIKE '%@portalv2.local'
ORDER BY u.ID_Usuario;
GO

/* ============================================================
   DETALLE: core.empleado — relacion con usuario
============================================================ */
PRINT '--- Empleados demo y su usuario ---';
SELECT
    e.Numero_Empleado,
    e.Nombre,
    e.Correo,
    ur.ID_Usuario,
    s.Nombre  AS Sucursal,
    p.Descripcion AS Puesto,
    a.Nombre  AS Area
FROM core.empleado e
LEFT JOIN core.usuario_relacion ur ON ur.Numero_Empleado = e.Numero_Empleado
LEFT JOIN core.sucursal s          ON s.ID_Sucursal = e.ID_Sucursal
LEFT JOIN core.puesto   p          ON p.ID_Puesto   = e.ID_Puesto
LEFT JOIN core.area     a          ON a.ID_Area     = e.ID_Area
WHERE e.Numero_Empleado IN (90001,90002,90003,90004);
GO

/* ============================================================
   DETALLE: hd.tipo_error + hd.[error]
============================================================ */
PRINT '--- Tipos de error y errores ---';
SELECT
    te.Nombre    AS TipoError,
    e.Descripcion AS Error,
    e.Activo
FROM hd.[error] e
JOIN hd.tipo_error te ON te.ID_TipoError = e.Tipo
ORDER BY te.Nombre, e.ID_Error;
GO

/* ============================================================
   DETALLE: ped.estado_pedido (verifica columna Orden)
============================================================ */
PRINT '--- Estados de pedido con Orden ---';
SELECT IdEstado, Nombre, Orden, activo
FROM ped.estado_pedido
ORDER BY Orden;
GO

/* ============================================================
   DETALLE: rh.estatus_candidato (verifica sin duplicados)
============================================================ */
PRINT '--- Estatus candidato ---';
SELECT ID_EstatusCandidato, Nombre, OrdenProceso, Activo
FROM rh.estatus_candidato
ORDER BY OrdenProceso;
GO

/* ============================================================
   DETALLE: com.semana (verifica columna Activo)
============================================================ */
PRINT '--- Semanas COM ---';
SELECT ID_Semana, Anio, Semana, FechaInicio, FechaFin, Activo
FROM com.semana
ORDER BY Anio, Semana;
GO

/* ============================================================
   DETALLE: rh — flujo completo vacante > candidato > entrevista > oferta
============================================================ */
PRINT '--- Flujo RH demo ---';
SELECT
    v.Folio          AS Vacante,
    c.Nombre + ' ' + ISNULL(c.ApellidoPaterno,'') AS Candidato,
    ec.Nombre        AS Estatus,
    e.TipoEntrevista,
    e.Resultado      AS ResultadoEntrevista,
    ol.SalarioOfertado,
    ol.Estatus       AS EstatusOferta
FROM rh.vacante v
LEFT JOIN rh.candidato    c  ON c.ID_Vacante          = v.ID_Vacante
LEFT JOIN rh.estatus_candidato ec ON ec.ID_EstatusCandidato = c.ID_EstatusCandidato
LEFT JOIN rh.entrevista   e  ON e.ID_Candidato         = c.ID_Candidato
LEFT JOIN rh.oferta_laboral ol ON ol.ID_Candidato      = c.ID_Candidato
ORDER BY c.ID_Candidato;
GO

PRINT '============================================================';
PRINT 'Validacion completada.';
PRINT '============================================================';
GO
