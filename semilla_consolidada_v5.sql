/* ============================================================
   PORTAL V2 - semilla_consolidada_v5.sql

   Generado desde:
   - semilla_v4.sql                (base validada e idempotente)
   - semilla_datos.sql             (catalogo completo de puestos)
   - semilla_fix.sql               (catalogo completo de empleados)
   - ped_*.sql                     (solo referencia; no se incluyen CREATE/DROP
                                    ni tabla obsoleta ped.pedido)

   Correcciones incluidas:
   - SET options requeridos por SQL Server para indices filtrados.
   - Valida existencia de PortalV2 antes de cargar.
   - Agrega ped.estado_pedido.Orden si falta.
   - Mantiene PED alineado a master 5: ped.pedidos, ped.pedido_detalle,
     ped.pedido_estado_log. No recrea ped.pedido singular.
   - Carga adicional de puestos y empleados como inserciones idempotentes.
============================================================ */

SET NOCOUNT ON;
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
SET ANSI_WARNINGS ON;
SET ANSI_PADDING ON;
SET CONCAT_NULL_YIELDS_NULL ON;
SET ARITHABORT ON;
GO

IF DB_ID(N'PortalV2') IS NULL
BEGIN
    RAISERROR('No existe la base PortalV2. Ejecuta primero master 5.txt.', 16, 1);
    RETURN;
END;
GO

USE PortalV2;
GO

IF OBJECT_ID(N'ped.estado_pedido', N'U') IS NOT NULL
   AND COL_LENGTH(N'ped.estado_pedido', N'Orden') IS NULL
BEGIN
    ALTER TABLE ped.estado_pedido
        ADD Orden int NOT NULL
            CONSTRAINT DF_ped_ep_Orden DEFAULT (99);
END;
GO

IF OBJECT_ID(N'ped.estado_pedido', N'U') IS NOT NULL
BEGIN
    UPDATE ped.estado_pedido
    SET Orden = CASE UPPER(Nombre)
        WHEN 'PENDIENTE'   THEN 1
        WHEN 'EN PROCESO'  THEN 2
        WHEN 'AUTORIZADO'  THEN 2
        WHEN 'DESPACHADO'  THEN 3
        WHEN 'SURTIDO'     THEN 3
        WHEN 'ENTREGADO'   THEN 4
        WHEN 'CANCELADO'   THEN 5
        ELSE Orden
    END;
END;
GO

USE PortalV2;
GO

/* ============================================================
   PORTAL V2 â€” semilla_v4.sql
   Version corregida de semilla_v2_desde_v1.sql

   Correcciones aplicadas vs version anterior:
     [FIX-1] hd.[error] entre corchetes (palabra reservada T-SQL)
     [FIX-2] Hash de contrasenas actualizado a bcrypt $2y$12$
     [FIX-3] UPDATE de hash para logins existentes con hash anterior
     [FIX-4] DELETE de estatus_candidato huerfanos antes del MERGE
             para evitar duplicados semanticos de semillas previas

   Objetivo:
   - Reusar catalogos base heredables de db_tiket_ti (V1)
   - Excluir datos transaccionales:
       hd.ticket, hd.comentario, hd.encuesta_ticket,
       ped.pedidos, ped.pedido_detalle, ped.pedido_estado_log
   - Generar datos semilla para tablas nuevas de V2

   Alcance:
   - Catalogos CORE / CAT / HD / PED / RH
   - Datos demo para tablas nuevas RH / COM / CORE
============================================================ */

/* ============================================================
   1. CORE: roles del proyecto actual
============================================================ */
MERGE core.rol AS tgt
USING (VALUES
    ('ADMINISTRADOR', 1),
    ('GERENTE',       1),
    ('GENERALISTA',   1),
    ('CXC',           1),
    ('SOPORTE HD',    1),
    ('RH',            1),
    ('EMPLEADO',      1)
) AS src (Nombre, Activo)
ON tgt.Nombre = src.Nombre
WHEN MATCHED THEN
    UPDATE SET Activo = src.Activo
WHEN NOT MATCHED THEN
    INSERT (Nombre, Activo) VALUES (src.Nombre, src.Activo);
GO

/* ============================================================
   2. CORE: areas heredadas de V1 + Serie para V2
============================================================ */
SET IDENTITY_INSERT core.area ON;
MERGE core.area AS tgt
USING (VALUES
  (1,  'SUCURSAL MEXICALI',                        'MXL', 1),
  (2,  'COMPRAS',                                  'CMP', 1),
  (3,  'SUCURSAL HERMOSILLO',                      'HMO', 1),
  (4,  'SUCURSAL TIJUANA',                         'TIJ', 1),
  (5,  'SUCURSAL ENSENADA',                        'ENS', 1),
  (6,  'SUCURSAL OBREGON',                         'OBR', 1),
  (7,  'CREDITO Y COBRANZA',                       'CXC', 1),
  (8,  'MKT Y RP',                                 'MKT', 1),
  (9,  'TECNOLOGIAS',                              'TIC', 1),
  (10, 'DIRECCION GENERAL',                        'DGN', 1),
  (11, 'AUDITORIA',                                'AUD', 1),
  (12, 'CONTABILIDAD',                             'CON', 1),
  (13, 'FINANZAS',                                 'FIN', 1),
  (14, 'RECURSOS HUMANOS',                         'RRH', 1),
  (15, 'LOGISTICA',                                'LOG', 1),
  (16, 'PRODUCCION',                               'PRD', 1),
  (17, 'TESORERIA',                                'TES', 1),
  (18, 'COMERCIAL',                                'CMR', 1),
  (19, 'MANTENIMIENTO',                            'MTO', 1),
  (20, 'CALIDAD',                                  'CAL', 1),
  (21, 'OPERACIONES',                              'OPS', 1),
  (22, 'CUENTAS POR COBRAR',                       'CPC', 1),
  (23, 'COORDINADOR (A) MANTENIMIENTO',            'CMT', 0),
  (24, 'AUXILIAR ALMACEN',                         'AAL', 0),
  (25, 'COORDINADOR DE MANTENIMIENTO DE FLOTILLA', 'CMF', 0),
  (26, 'AUXILIAR PRODUCCION',                      'APD', 0),
  (27, 'JEFE (A) DE DEPARTAMENTO',                 'JDP', 0),
  (28, 'AUXILIAR LABORATORIO',                     'ALB', 0),
  (29, 'AUXILIAR DE DEPARTAMENTO',                 'ADP', 0),
  (30, 'COORDINADOR (A) PRODUCCION',               'CPD', 0),
  (31, 'ENCARGADO (A) DE ALMACEN',                 'EAL', 0),
  (32, 'LIDER MANTENIMIENTO T1',                   'LM1', 0),
  (33, 'JEFE (A) SANIDAD',                         'JSA', 0),
  (34, 'JEFE (A) CALIDAD Y DESARROLLO',            'JCD', 0),
  (35, 'AUXILIAR SUPERVISOR',                      'ASP', 0),
  (36, 'INSPECTOR (A) JR',                         'IJR', 0),
  (37, 'AUXILIAR MANTENIMIENTO T2',                'AM2', 0),
  (38, 'ANALISTA DE PRODUCCION',                   'ANA', 0),
  (39, 'AUXILIAR DOCUMENTADOR',                    'ADO', 0),
  (40, 'AUXILIAR SOPORTE',                         'AST', 0),
  (41, 'AUXILIAR MANTENIMIENTO T3',                'AM3', 0),
  (42, 'HORNOS',                                   'HOR', 1),
  (43, 'CARNES FRIAS INYECCION',                   'CFI', 1),
  (44, 'LABORATORIO',                              'LAB', 1),
  (45, 'CARNICERIA',                               'CAR', 1),
  (46, 'EMBUTIDOS MATUTINO',                       'EMM', 1),
  (47, 'MANTECA',                                  'MAN', 1),
  (48, 'ALMACEN REFRIGERADOS',                     'ALR', 1),
  (49, 'EMPAQUE',                                  'EMP', 1),
  (50, 'SANIDAD',                                  'SAN', 1),
  (51, 'CALIDAD Y DESARROLLO',                     'CYD', 1),
  (52, 'REFACCIONES',                              'REF', 1),
  (53, 'EMBUTIDOS VESPERTINO',                     'EMV', 1),
  (54, 'DOCUMENTACION',                            'DOC', 1)
) AS src (ID_Area, Nombre, Serie, Activo)
ON tgt.ID_Area = src.ID_Area
WHEN MATCHED THEN
    UPDATE SET Nombre = src.Nombre, Serie = src.Serie, Activo = src.Activo
WHEN NOT MATCHED THEN
    INSERT (ID_Area, Nombre, Serie, Activo)
    VALUES (src.ID_Area, src.Nombre, src.Serie, src.Activo);
SET IDENTITY_INSERT core.area OFF;
GO

/* ============================================================
   3. CORE: sucursales heredadas de V1
============================================================ */
MERGE core.sucursal AS tgt
USING (VALUES
    (1, 'MEXICALI',            'MEXICALI',   1),
    (2, 'ADMINISTRACION',      'TIJUANA',    1),
    (3, 'HERMOSILLO',          'HERMOSILLO', 1),
    (4, 'TIJUANA',             'TIJUANA',    1),
    (5, 'ENSENADA',            'ENSENADA',   1),
    (6, 'OBREGON',             'OBREGON',    1),
    (7, 'CENTRO DISTRIBUCION', 'TIJUANA',    1),
    (8, 'PLANTA',              'TIJUANA',    1)
) AS src (ID_Sucursal, Nombre, Ciudad, Activo)
ON tgt.ID_Sucursal = src.ID_Sucursal
WHEN MATCHED THEN
    UPDATE SET Nombre = src.Nombre, Ciudad = src.Ciudad, Activo = src.Activo
WHEN NOT MATCHED THEN
    INSERT (ID_Sucursal, Nombre, Ciudad, Activo)
    VALUES (src.ID_Sucursal, src.Nombre, src.Ciudad, src.Activo);
GO

/* ============================================================
   4. CORE: puestos base suficientes para empleados demo
============================================================ */
SET IDENTITY_INSERT core.puesto ON;
MERGE core.puesto AS tgt
USING (VALUES
    (6,  'P006', 'ANALISTA SISTEMAS',            2, 'A', 'GEN', 'N', 1),
    (7,  'P007', 'ANALISTA SOPORTE TECNICO',     2, 'A', 'GEN', 'N', 1),
    (20, 'P020', 'CAJERO PUNTO DE VENTA',        2, 'A', 'GEN', 'N', 1),
    (26, 'P026', 'COORDINADOR ADMINISTRATIVO',   3, 'A', 'GEN', 'N', 1),
    (33, 'P033', 'DIRECTOR GENERAL',             5, 'A', 'GEN', 'S', 1),
    (35, 'P035', 'GENERALISTA RRHH',             2, 'A', 'GEN', 'N', 1),
    (36, 'P036', 'GERENTE ADMON Y FINANZAS',     4, 'A', 'GEN', 'S', 1),
    (40, 'P040', 'GERENTE DE SUCURSAL',          4, 'A', 'GEN', 'S', 1),
    (43, 'P043', 'JEFE CREDITO Y COBRANZA',      3, 'A', 'GEN', 'N', 1),
    (46, 'P046', 'JEFE DE SOPORTE',              3, 'A', 'GEN', 'N', 1),
    (52, 'P052', 'SUPERVISOR VENTAS MODERNO',    3, 'A', 'GEN', 'N', 1),
    (53, 'P053', 'SUPERVISOR VENTAS TRADICIONAL',3, 'A', 'GEN', 'N', 1)
) AS src (ID_Puesto, Clave, Descripcion, Nivel, Categoria, Segmento, Responsabilidad, Activo)
ON tgt.ID_Puesto = src.ID_Puesto
WHEN MATCHED THEN
    UPDATE SET Clave = src.Clave, Descripcion = src.Descripcion, Nivel = src.Nivel,
               Categoria = src.Categoria, Segmento = src.Segmento,
               Responsabilidad = src.Responsabilidad, Activo = src.Activo
WHEN NOT MATCHED THEN
    INSERT (ID_Puesto, Clave, Descripcion, Nivel, Categoria, Segmento, Responsabilidad, Activo)
    VALUES (src.ID_Puesto, src.Clave, src.Descripcion, src.Nivel, src.Categoria, src.Segmento, src.Responsabilidad, src.Activo);
SET IDENTITY_INSERT core.puesto OFF;
GO

/* ============================================================
   5. CORE: tipo_equipo heredado de V1
============================================================ */
SET IDENTITY_INSERT core.tipo_equipo ON;
MERGE core.tipo_equipo AS tgt
USING (VALUES
    (1, 'DESKTOP',           'EQUIPO DE ESCRITORIO',    1),
    (2, 'LAPTOP',            'COMPUTO MOVIL',           1),
    (3, 'CELULAR',           'TELEFONO MOVIL',          1),
    (4, 'IMPRESORA',         'IMPRESORA DE OFICINA',    1),
    (5, 'IMPRESORA TERMICA', 'IMPRESORA MOVIL TERMICA', 1),
    (6, 'TABLETA',           'TABLETA',                 1)
) AS src (ID_TipoEquipo, Nombre, Descripcion, Activo)
ON tgt.ID_TipoEquipo = src.ID_TipoEquipo
WHEN MATCHED THEN
    UPDATE SET Nombre = src.Nombre, Descripcion = src.Descripcion, Activo = src.Activo
WHEN NOT MATCHED THEN
    INSERT (ID_TipoEquipo, Nombre, Descripcion, Activo)
    VALUES (src.ID_TipoEquipo, src.Nombre, src.Descripcion, src.Activo);
SET IDENTITY_INSERT core.tipo_equipo OFF;
GO

/* ============================================================
   6. CORE: usuarios y empleados demo
============================================================ */
IF NOT EXISTS (SELECT 1 FROM core.usuario WHERE Email = 'admin@portalv2.local')
BEGIN
    DECLARE @idRolAdmin int = (SELECT TOP 1 ID_Rol FROM core.rol WHERE Nombre = 'ADMINISTRADOR');
    DECLARE @idRolRh    int = (SELECT TOP 1 ID_Rol FROM core.rol WHERE Nombre = 'RH');
    DECLARE @idRolHd    int = (SELECT TOP 1 ID_Rol FROM core.rol WHERE Nombre = 'SOPORTE HD');
    DECLARE @idRolGer   int = (SELECT TOP 1 ID_Rol FROM core.rol WHERE Nombre = 'GERENTE');
    DECLARE @idAreaTi   int = (SELECT TOP 1 ID_Area FROM core.area WHERE Nombre = 'TECNOLOGIAS');
    DECLARE @idAreaRh   int = (SELECT TOP 1 ID_Area FROM core.area WHERE Nombre = 'RECURSOS HUMANOS');
    DECLARE @idAreaCom  int = (SELECT TOP 1 ID_Area FROM core.area WHERE Nombre = 'COMERCIAL');

    INSERT INTO core.usuario (Nombre, Email, ID_Rol, ID_Area, Activo)
    VALUES
      ('ADMIN PORTAL V2',   'admin@portalv2.local',   @idRolAdmin, @idAreaTi,  1),
      ('SOPORTE HELP DESK', 'soporte@portalv2.local', @idRolHd,    @idAreaTi,  1),
      ('GENERALISTA RH',    'rh@portalv2.local',      @idRolRh,    @idAreaRh,  1),
      ('GERENTE COMERCIAL', 'gerente@portalv2.local', @idRolGer,   @idAreaCom, 1);
END
GO

/* [FIX-3] Actualizar hash si los logins ya existen con hash anterior */
UPDATE core.[login]
SET PasswordHash = '$2y$12$sqrVCjjvNHY6/f2uhJ.0iuewvSw0E.IP7U3K.Fa4vjAm/dcLFS7Ju'
WHERE Cuenta IN ('admin','soporte','rh','gerente')
  AND PasswordHash <> '$2y$12$sqrVCjjvNHY6/f2uhJ.0iuewvSw0E.IP7U3K.Fa4vjAm/dcLFS7Ju';
GO

/* [FIX-2] Hash bcrypt $2y$12$ correcto */
IF NOT EXISTS (SELECT 1 FROM core.[login] WHERE Cuenta = 'admin')
BEGIN
    DECLARE @idAdmin   int = (SELECT TOP 1 ID_Usuario FROM core.usuario WHERE Email = 'admin@portalv2.local');
    DECLARE @idSoporte int = (SELECT TOP 1 ID_Usuario FROM core.usuario WHERE Email = 'soporte@portalv2.local');
    DECLARE @idRh      int = (SELECT TOP 1 ID_Usuario FROM core.usuario WHERE Email = 'rh@portalv2.local');
    DECLARE @idGer     int = (SELECT TOP 1 ID_Usuario FROM core.usuario WHERE Email = 'gerente@portalv2.local');

    INSERT INTO core.[login] (Cuenta, PasswordHash, ID_Usuario, Activo, DebeCambiarPassword)
    VALUES
      ('admin',   '$2y$12$sqrVCjjvNHY6/f2uhJ.0iuewvSw0E.IP7U3K.Fa4vjAm/dcLFS7Ju', @idAdmin,   1, 0),
      ('soporte', '$2y$12$sqrVCjjvNHY6/f2uhJ.0iuewvSw0E.IP7U3K.Fa4vjAm/dcLFS7Ju', @idSoporte, 1, 0),
      ('rh',      '$2y$12$sqrVCjjvNHY6/f2uhJ.0iuewvSw0E.IP7U3K.Fa4vjAm/dcLFS7Ju', @idRh,      1, 0),
      ('gerente', '$2y$12$sqrVCjjvNHY6/f2uhJ.0iuewvSw0E.IP7U3K.Fa4vjAm/dcLFS7Ju', @idGer,     1, 0);
END
GO

IF NOT EXISTS (SELECT 1 FROM core.empleado WHERE Numero_Empleado IN (90001,90002,90003,90004))
BEGIN
    DECLARE @idSucAdm    int = (SELECT TOP 1 ID_Sucursal FROM core.sucursal WHERE Nombre = 'ADMINISTRACION');
    DECLARE @idPuestoSis int = (SELECT TOP 1 ID_Puesto FROM core.puesto WHERE Descripcion = 'ANALISTA SISTEMAS');
    DECLARE @idPuestoSup int = (SELECT TOP 1 ID_Puesto FROM core.puesto WHERE Descripcion = 'JEFE DE SOPORTE');
    DECLARE @idPuestoRh  int = (SELECT TOP 1 ID_Puesto FROM core.puesto WHERE Descripcion = 'GENERALISTA RRHH');
    DECLARE @idPuestoGer int = (SELECT TOP 1 ID_Puesto FROM core.puesto WHERE Descripcion = 'GERENTE DE SUCURSAL');
    DECLARE @idAreaTi2   int = (SELECT TOP 1 ID_Area FROM core.area WHERE Nombre = 'TECNOLOGIAS');
    DECLARE @idAreaRh2   int = (SELECT TOP 1 ID_Area FROM core.area WHERE Nombre = 'RECURSOS HUMANOS');
    DECLARE @idAreaCom2  int = (SELECT TOP 1 ID_Area FROM core.area WHERE Nombre = 'COMERCIAL');

    INSERT INTO core.empleado (Numero_Empleado, Nombre, Correo, ID_Sucursal, ID_Puesto, ID_Area, Activo)
    VALUES
      (90001, 'ADMIN PORTAL V2',   'admin@portalv2.local',   @idSucAdm, @idPuestoSis, @idAreaTi2,  1),
      (90002, 'SOPORTE HELP DESK', 'soporte@portalv2.local', @idSucAdm, @idPuestoSup, @idAreaTi2,  1),
      (90003, 'GENERALISTA RH',    'rh@portalv2.local',      @idSucAdm, @idPuestoRh,  @idAreaRh2,  1),
      (90004, 'GERENTE COMERCIAL', 'gerente@portalv2.local', @idSucAdm, @idPuestoGer, @idAreaCom2, 1);
END
GO

MERGE core.usuario_relacion AS tgt
USING (
    SELECT u.ID_Usuario, e.Numero_Empleado
    FROM core.usuario u
    JOIN core.empleado e ON e.Correo = u.Email
    WHERE u.Email IN ('admin@portalv2.local','soporte@portalv2.local','rh@portalv2.local','gerente@portalv2.local')
) AS src
ON tgt.ID_Usuario = src.ID_Usuario
WHEN MATCHED THEN
    UPDATE SET Numero_Empleado = src.Numero_Empleado, Activo = 1
WHEN NOT MATCHED THEN
    INSERT (ID_Usuario, Numero_Empleado, Activo)
    VALUES (src.ID_Usuario, src.Numero_Empleado, 1);
GO

/* ============================================================
   7. CAT / HD: catalogos base heredados y adaptados
============================================================ */
MERGE cat.sistema AS tgt
USING (VALUES
    (9, 'SAP',                 'Sistema ERP (SAP)',                    1),
    (9, 'App Calidad',         'Aplicacion de calidad',                1),
    (9, 'App Movil',           'Aplicacion o portal de ventas',        1),
    (9, 'Red',                 'Conexion de red e interrupciones',     1),
    (9, 'Equipo de Computo',   'Perifericos, hardware y software',     1),
    (9, 'TRESS',               'Sistema de nomina',                    1),
    (9, 'Office',              'Aplicaciones de oficina',              1),
    (9, 'Otros',               'Cualquier otro sistema',               1),
    (9, 'CCTV',                'Problemas relacionados con CCTV',      1),
    (9, 'Sistema de gestion',  'Portal de incidencias de tecnologias', 1),
    (9, 'Dashboard',           'Portal de indicadores operativos',     1),
    (9, 'Suministro electrico','Incidencias de energia',               1),
    (9, 'APP',                 'Programas instalados en equipo',       1)
) AS src (ID_Area, Nombre, Descripcion, Activo)
ON tgt.Nombre = src.Nombre
WHEN MATCHED THEN
    UPDATE SET ID_Area = src.ID_Area, Descripcion = src.Descripcion, Activo = src.Activo
WHEN NOT MATCHED THEN
    INSERT (ID_Area, Nombre, Descripcion, Activo)
    VALUES (src.ID_Area, src.Nombre, src.Descripcion, src.Activo);
GO

MERGE hd.estatus AS tgt
USING (VALUES
    ('Nuevo',      1),
    ('Asignado',   2),
    ('En proceso', 3),
    ('En espera',  4),
    ('Resuelto',   5),
    ('Cerrado',    6),
    ('Cancelado',  7)
) AS src (Nombre, Orden)
ON tgt.Nombre = src.Nombre
WHEN MATCHED THEN
    UPDATE SET Orden = src.Orden
WHEN NOT MATCHED THEN
    INSERT (Nombre, Orden) VALUES (src.Nombre, src.Orden);
GO

MERGE hd.solucion AS tgt
USING (VALUES
    ('Reinicio de sesion / cache del navegador', 1),
    ('Restablecimiento de contrasena',           1),
    ('Alta o baja de permisos',                  1),
    ('Reinstalacion de controladores o software',1),
    ('Sustitucion o configuracion de hardware',  1),
    ('Correccion de datos en base de datos',     1),
    ('Configuracion de red / VPN',               1),
    ('Capacitacion al usuario',                  1),
    ('Escalado a proveedor externo',             1),
    ('Cierre sin accion',                        1)
) AS src (Descripcion, Activo)
ON tgt.Descripcion = src.Descripcion
WHEN MATCHED THEN
    UPDATE SET Activo = src.Activo
WHEN NOT MATCHED THEN
    INSERT (Descripcion, Activo) VALUES (src.Descripcion, src.Activo);
GO

IF EXISTS (SELECT 1 FROM sys.tables t JOIN sys.schemas s ON s.schema_id = t.schema_id WHERE s.name = 'hd' AND t.name = 'tipo_error')
BEGIN
    MERGE hd.tipo_error AS tgt
    USING (VALUES
        ('Error de sistema',    'Fallo en aplicacion o modulo',           1),
        ('Error de usuario',    'Operacion incorrecta del usuario',       1),
        ('Error de red',        'Conectividad o VPN',                     1),
        ('Error de hardware',   'Equipo fisico danado',                   1),
        ('Error de datos',      'Datos incorrectos o corruptos',          1),
        ('Solicitud de acceso', 'Alta de usuario o permiso',              1),
        ('Instalacion',         'Instalacion o configuracion de software',1),
        ('Consulta',            'Pregunta o duda funcional',              1),
        ('Otro',                'No clasificado',                         1)
    ) AS src (Nombre, Descripcion, Activo)
    ON tgt.Nombre = src.Nombre
    WHEN MATCHED THEN
        UPDATE SET Descripcion = src.Descripcion, Activo = src.Activo
    WHEN NOT MATCHED THEN
        INSERT (Nombre, Descripcion, Activo) VALUES (src.Nombre, src.Descripcion, src.Activo);
END
GO

/* [FIX-1] hd.[error] entre corchetes â€” palabra reservada en T-SQL */
IF NOT EXISTS (SELECT 1 FROM hd.[error])
BEGIN
    DECLARE @teSistema  int = (SELECT TOP 1 ID_TipoError FROM hd.tipo_error WHERE Nombre = 'Error de sistema');
    DECLARE @teAcceso   int = (SELECT TOP 1 ID_TipoError FROM hd.tipo_error WHERE Nombre = 'Solicitud de acceso');
    DECLARE @teRed      int = (SELECT TOP 1 ID_TipoError FROM hd.tipo_error WHERE Nombre = 'Error de red');
    DECLARE @teHard     int = (SELECT TOP 1 ID_TipoError FROM hd.tipo_error WHERE Nombre = 'Error de hardware');
    DECLARE @teDatos    int = (SELECT TOP 1 ID_TipoError FROM hd.tipo_error WHERE Nombre = 'Error de datos');
    DECLARE @teInst     int = (SELECT TOP 1 ID_TipoError FROM hd.tipo_error WHERE Nombre = 'Instalacion');
    DECLARE @teConsulta int = (SELECT TOP 1 ID_TipoError FROM hd.tipo_error WHERE Nombre = 'Consulta');
    DECLARE @teOtro     int = (SELECT TOP 1 ID_TipoError FROM hd.tipo_error WHERE Nombre = 'Otro');

    INSERT INTO hd.[error] (Descripcion, Tipo, Activo)
    VALUES
      ('No puedo iniciar sesion',          @teSistema,  1),
      ('No tengo acceso al modulo',        @teAcceso,   1),
      ('Sin conexion a internet / VPN',    @teRed,      1),
      ('La impresora no imprime',          @teHard,     1),
      ('Datos incorrectos en el sistema',  @teDatos,    1),
      ('Necesito instalar un programa',    @teInst,     1),
      ('Duda sobre como usar el sistema',  @teConsulta, 1),
      ('Otro problema no listado',         @teOtro,     1);
END
GO

IF NOT EXISTS (SELECT 1 FROM hd.sla)
BEGIN
    INSERT INTO hd.sla (Nombre, ID_Area, Prioridad, HorasRespuesta, HorasResolucion, Activo)
    VALUES
      ('Critico global', NULL, 'CRITICA', 1, 4,  1),
      ('Alto global',    NULL, 'ALTA',    2, 8,  1),
      ('Media global',   NULL, 'MEDIA',   4, 24, 1),
      ('Baja global',    NULL, 'BAJA',    8, 48, 1);
END
GO

/* ============================================================
   8. CAT: articulos base
============================================================ */
MERGE cat.tipo_articulo AS tgt
USING (VALUES
    ('Piezas',     'PZ', 1),
    ('Kilogramos', 'KG', 1)
) AS src (Nombre, Medida, Activo)
ON tgt.Nombre = src.Nombre
WHEN MATCHED THEN
    UPDATE SET Medida = src.Medida, Activo = src.Activo
WHEN NOT MATCHED THEN
    INSERT (Nombre, Medida, Activo) VALUES (src.Nombre, src.Medida, src.Activo);
GO

MERGE cat.linea_articulo AS tgt
USING (VALUES
    ('Embutido',    'Grupo de Embutidos',    1),
    ('Carnes frias','Grupo de Carnes frias', 1),
    ('Queso',       'Grupo de Quesos',       1),
    ('Manteca',     'Grupo de Manteca',      1)
) AS src (Nombre, Descripcion, Activo)
ON tgt.Nombre = src.Nombre
WHEN MATCHED THEN
    UPDATE SET Descripcion = src.Descripcion, Activo = src.Activo
WHEN NOT MATCHED THEN
    INSERT (Nombre, Descripcion, Activo) VALUES (src.Nombre, src.Descripcion, src.Activo);
GO

IF NOT EXISTS (SELECT 1 FROM cat.grupo_articulo)
BEGIN
    DECLARE @lineaEmb int = (SELECT TOP 1 IdLineaArticulo FROM cat.linea_articulo WHERE Nombre = 'Embutido');
    DECLARE @lineaCf  int = (SELECT TOP 1 IdLineaArticulo FROM cat.linea_articulo WHERE Nombre = 'Carnes frias');
    DECLARE @lineaQ   int = (SELECT TOP 1 IdLineaArticulo FROM cat.linea_articulo WHERE Nombre = 'Queso');
    DECLARE @lineaM   int = (SELECT TOP 1 IdLineaArticulo FROM cat.linea_articulo WHERE Nombre = 'Manteca');

    INSERT INTO cat.grupo_articulo (IdLineaArticulo, Nombre, Descripcion, Activo)
    VALUES
      (@lineaEmb, 'Salchichas', 'Grupo heredado de V1', 1),
      (@lineaEmb, 'Mortadela',  'Grupo heredado de V1', 1),
      (@lineaCf,  'Jamon',      'Grupo heredado de V1', 1),
      (@lineaCf,  'Bolonia',    'Grupo heredado de V1', 1),
      (@lineaCf,  'Lomo',       'Grupo heredado de V1', 1),
      (@lineaQ,   'Queso',      'Grupo heredado de V1', 1),
      (@lineaM,   'Manteca',    'Grupo heredado de V1', 1);
END
GO

IF NOT EXISTS (SELECT 1 FROM cat.articulo)
BEGIN
    DECLARE @tipoPz int = (SELECT TOP 1 IdTipoArticulo FROM cat.tipo_articulo WHERE Nombre = 'Piezas');
    DECLARE @tipoKg int = (SELECT TOP 1 IdTipoArticulo FROM cat.tipo_articulo WHERE Nombre = 'Kilogramos');
    DECLARE @gSal   int = (SELECT TOP 1 IdGrupoArticulo FROM cat.grupo_articulo WHERE Nombre = 'Salchichas');
    DECLARE @gJam   int = (SELECT TOP 1 IdGrupoArticulo FROM cat.grupo_articulo WHERE Nombre = 'Jamon');
    DECLARE @gBol   int = (SELECT TOP 1 IdGrupoArticulo FROM cat.grupo_articulo WHERE Nombre = 'Bolonia');
    DECLARE @gLom   int = (SELECT TOP 1 IdGrupoArticulo FROM cat.grupo_articulo WHERE Nombre = 'Lomo');
    DECLARE @gQue   int = (SELECT TOP 1 IdGrupoArticulo FROM cat.grupo_articulo WHERE Nombre = 'Queso');
    DECLARE @gMan   int = (SELECT TOP 1 IdGrupoArticulo FROM cat.grupo_articulo WHERE Nombre = 'Manteca');

    INSERT INTO cat.articulo (Nombre, NombreCorto, IdTipoArticulo, Peso, IdGrupoArticulo, Activo)
    VALUES
      ('SALCHICHA VIENA',     'SALV', @tipoPz, 0.250, @gSal, 1),
      ('JAMON COCIDO',        'JAMC', @tipoPz, 0.200, @gJam, 1),
      ('BOLONIA REGULAR',     'BOLR', @tipoPz, 0.500, @gBol, 1),
      ('LOMO DE CERDO',       'LOMC', @tipoPz, 0.300, @gLom, 1),
      ('QUESO MANCHEGO',      'QMAN', @tipoKg, 1.000, @gQue, 1),
      ('MANTECA TRADICIONAL', 'MANT', @tipoKg, 1.000, @gMan, 1);
END
GO

/* ============================================================
   9. PED: catalogos heredados de V1, sin pedidos ni detalle
============================================================ */
IF NOT EXISTS (SELECT 1 FROM ped.capacidaduv)
BEGIN
    INSERT INTO ped.capacidaduv (Nombre, Descripcion, CapacidadMinima, CapacidadMaxima, activo)
    VALUES
      ('RUTA CHICA',  'Hasta 50 clientes',    1,   50,  1),
      ('RUTA MEDIA',  'De 51 a 100 clientes', 51,  100, 1),
      ('RUTA GRANDE', 'Mas de 100 clientes',  101, 300, 1),
      ('ALMACEN',     'Sin limite operativo', NULL,NULL, 1);
END
GO

MERGE ped.tipounidad AS tgt
USING (VALUES
    ('VENDEDOR TRADICIONAL',   'VENDEDOR DE LINEA TRADICIONAL',   1),
    ('VENDEDOR MODERNO',       'VENDEDOR DE LINEA MODERNO',       1),
    ('SUPERVISOR TRADICIONAL', 'SUPERVISOR DE LINEA TRADICIONAL', 1),
    ('SUPERVISOR MODERNO',     'SUPERVISOR DE LINEA MODERNO',     1),
    ('ALMACEN',                'ALMACEN DE SUCURSAL',             1),
    ('ADMINISTRADOR',          'ADMINISTRADOR',                   1),
    ('GERENTE SUCURSAL',       'GERENTES DE SUCURSAL',            1)
) AS src (Nombre, Descripcion, activo)
ON tgt.Nombre = src.Nombre
WHEN MATCHED THEN
    UPDATE SET Descripcion = src.Descripcion, activo = src.activo
WHEN NOT MATCHED THEN
    INSERT (Nombre, Descripcion, activo) VALUES (src.Nombre, src.Descripcion, src.activo);
GO

MERGE ped.estado_pedido AS tgt
USING (VALUES
    ('PENDIENTE',  'Pedido capturado por la unidad', 1, 1),
    ('AUTORIZADO', 'Aprobado por supervisor',        1, 2),
    ('SURTIDO',    'Suministrado por almacen',       1, 3),
    ('CANCELADO',  'Pedido cancelado',               1, 4)
) AS src (Nombre, Descripcion, activo, Orden)
ON tgt.Nombre = src.Nombre
WHEN MATCHED THEN
    UPDATE SET Descripcion = src.Descripcion, activo = src.activo, Orden = src.Orden
WHEN NOT MATCHED THEN
    INSERT (Nombre, Descripcion, activo, Orden)
    VALUES (src.Nombre, src.Descripcion, src.activo, src.Orden);
GO

IF NOT EXISTS (SELECT 1 FROM ped.unidadoperacional)
BEGIN
    DECLARE @idUserAdmin int = (SELECT TOP 1 ID_Usuario FROM core.usuario WHERE Email = 'admin@portalv2.local');
    DECLARE @idUserGer   int = (SELECT TOP 1 ID_Usuario FROM core.usuario WHERE Email = 'gerente@portalv2.local');
    DECLARE @idSucTj     int = (SELECT TOP 1 ID_Sucursal FROM core.sucursal WHERE Nombre = 'TIJUANA');
    DECLARE @idCapAlm    int = (SELECT TOP 1 IdCapacidadUV FROM ped.capacidaduv WHERE Nombre = 'ALMACEN');
    DECLARE @idCapMed    int = (SELECT TOP 1 IdCapacidadUV FROM ped.capacidaduv WHERE Nombre = 'RUTA MEDIA');
    DECLARE @idTipoAlm   int = (SELECT TOP 1 IdTipoUnidad FROM ped.tipounidad WHERE Nombre = 'ALMACEN');
    DECLARE @idTipoSup   int = (SELECT TOP 1 IdTipoUnidad FROM ped.tipounidad WHERE Nombre = 'SUPERVISOR TRADICIONAL');
    DECLARE @idTipoVen   int = (SELECT TOP 1 IdTipoUnidad FROM ped.tipounidad WHERE Nombre = 'VENDEDOR TRADICIONAL');

    INSERT INTO ped.unidadoperacional (IdTipoUnidad, IdUsuario, IdSucursal, IdCapacidadUV, Nombre, Descripcion, activo)
    VALUES
      (@idTipoAlm, @idUserAdmin, @idSucTj, @idCapAlm, 'ALMACEN TIJUANA', 'Almacen demo',    1),
      (@idTipoSup, @idUserGer,   @idSucTj, @idCapAlm, 'ST101 TIJUANA',   'Supervisor demo', 1);

    DECLARE @idSupDemo int = (SELECT TOP 1 IdUnidad FROM ped.unidadoperacional WHERE Nombre = 'ST101 TIJUANA');

    INSERT INTO ped.unidadoperacional (IdTipoUnidad, IdUsuario, IdSupervisor, IdSucursal, IdCapacidadUV, Nombre, Descripcion, activo)
    VALUES
      (@idTipoVen, @idUserGer, @idSupDemo, @idSucTj, @idCapMed, 'RT101', 'Ruta demo tradicional 101', 1),
      (@idTipoVen, @idUserGer, @idSupDemo, @idSucTj, @idCapMed, 'RT102', 'Ruta demo tradicional 102', 1);
END
GO

/* ============================================================
   10. RH: catalogos y datos demo para tablas nuevas
============================================================ */
MERGE rh.fuente_reclutamiento AS tgt
USING (VALUES
    ('LinkedIn',              'Red profesional',             1),
    ('OCC Mundial',           'Portal OCC',                  1),
    ('Indeed',                'Portal Indeed',               1),
    ('Referido interno',      'Referencia de empleado',      1),
    ('Bolsa de trabajo',      'Bolsa universitaria o local', 1),
    ('Agencia externa',       'Agencia de reclutamiento',    1),
    ('Candidatura espontanea','CV directo',                  1),
    ('Otro',                  'Otro canal',                  1)
) AS src (Nombre, Descripcion, Activo)
ON tgt.Nombre = src.Nombre
WHEN MATCHED THEN
    UPDATE SET Descripcion = src.Descripcion, Activo = src.Activo
WHEN NOT MATCHED THEN
    INSERT (Nombre, Descripcion, Activo) VALUES (src.Nombre, src.Descripcion, src.Activo);
GO

/* [FIX-4] Eliminar estatus huerfanos de semillas anteriores que no tienen
   candidatos asignados y cuyo nombre no pertenece al catalogo definitivo. */
DELETE FROM rh.estatus_candidato
WHERE Nombre NOT IN (
    'NUEVO','EN_REVISION','CITADO','ENTREVISTADO',
    'SELECCIONADO','OFERTA_ENVIADA','CONTRATADO','RECHAZADO','DESCARTADO'
)
  AND NOT EXISTS (
    SELECT 1 FROM rh.candidato c
    WHERE c.ID_EstatusCandidato = rh.estatus_candidato.ID_EstatusCandidato
);
GO

MERGE rh.estatus_candidato AS tgt
USING (VALUES
    ('NUEVO',          'Candidato recien registrado',       1, 1),
    ('EN_REVISION',    'CV en revision por RH',             2, 1),
    ('CITADO',         'Citado a entrevista',               3, 1),
    ('ENTREVISTADO',   'Entrevistado, pendiente resultado', 4, 1),
    ('SELECCIONADO',   'Candidato seleccionado',            5, 1),
    ('OFERTA_ENVIADA', 'Oferta laboral enviada',            6, 1),
    ('CONTRATADO',     'Candidato contratado',              7, 1),
    ('RECHAZADO',      'No paso el proceso',                8, 1),
    ('DESCARTADO',     'Descartado por RH',                 9, 1)
) AS src (Nombre, Descripcion, OrdenProceso, Activo)
ON tgt.Nombre = src.Nombre
WHEN MATCHED THEN
    UPDATE SET Descripcion = src.Descripcion, OrdenProceso = src.OrdenProceso, Activo = src.Activo
WHEN NOT MATCHED THEN
    INSERT (Nombre, Descripcion, OrdenProceso, Activo)
    VALUES (src.Nombre, src.Descripcion, src.OrdenProceso, src.Activo);
GO

IF NOT EXISTS (SELECT 1 FROM rh.vacante)
BEGIN
    DECLARE @idAreaRhVac  int = (SELECT TOP 1 ID_Area FROM core.area WHERE Nombre = 'RECURSOS HUMANOS');
    DECLARE @idPuestoRhVac int = (SELECT TOP 1 ID_Puesto FROM core.puesto WHERE Descripcion = 'GENERALISTA RRHH');
    DECLARE @idSucAdmVac  int = (SELECT TOP 1 ID_Sucursal FROM core.sucursal WHERE Nombre = 'ADMINISTRACION');
    DECLARE @idUsrRh      int = (SELECT TOP 1 ID_Usuario FROM core.usuario WHERE Email = 'rh@portalv2.local');

    INSERT INTO rh.vacante
        (Folio, Titulo, Descripcion, Perfil, Requisitos, SalarioMin, SalarioMax, NumeroPosiciones,
         ID_Area, ID_Puesto, ID_Sucursal, ID_UsuarioSolicita, ID_UsuarioResponsable, Estatus, Activo)
    VALUES
        ('VAC-2026-001', 'ANALISTA DE RECLUTAMIENTO', 'Vacante demo para pruebas RH',
         'Perfil administrativo', 'Experiencia en reclutamiento', 18000, 22000, 1,
         @idAreaRhVac, @idPuestoRhVac, @idSucAdmVac, @idUsrRh, @idUsrRh, 'ABIERTA', 1);
END
GO

IF NOT EXISTS (SELECT 1 FROM rh.candidato)
BEGIN
    DECLARE @idVacDemo  int = (SELECT TOP 1 ID_Vacante FROM rh.vacante WHERE Folio = 'VAC-2026-001');
    DECLARE @idEstNuevo int = (SELECT TOP 1 ID_EstatusCandidato FROM rh.estatus_candidato WHERE Nombre = 'NUEVO');

    INSERT INTO rh.candidato
        (ID_Vacante, ID_EstatusCandidato, Nombre, ApellidoPaterno, ApellidoMaterno, Correo, Telefono,
         Escolaridad, Profesion, Fuente, PretensionSalarial, Observaciones, Activo)
    VALUES
        (@idVacDemo, @idEstNuevo, 'JUAN',  'PEREZ',  'LOPEZ', 'juan.perez.demo@mail.com',  '6640000001',
         'LICENCIATURA', 'ADMINISTRACION', 'LinkedIn',       20000, 'Candidato demo generado',  1),
        (@idVacDemo, @idEstNuevo, 'MARIA', 'GARCIA', 'SOTO',  'maria.garcia.demo@mail.com', '6640000002',
         'LICENCIATURA', 'PSICOLOGIA',     'Referido interno', 21000, 'Candidata demo generada', 1);
END
GO

IF NOT EXISTS (SELECT 1 FROM rh.entrevista)
BEGIN
    DECLARE @idCand1    int = (SELECT TOP 1 ID_Candidato FROM rh.candidato WHERE Correo = 'juan.perez.demo@mail.com');
    DECLARE @idVac1     int = (SELECT TOP 1 ID_Vacante FROM rh.vacante WHERE Folio = 'VAC-2026-001');
    DECLARE @idUsrRhEnt int = (SELECT TOP 1 ID_Usuario FROM core.usuario WHERE Email = 'rh@portalv2.local');

    INSERT INTO rh.entrevista
        (ID_Candidato, ID_Vacante, ID_UsuarioEntrevistador, TipoEntrevista, FechaEntrevista,
         DuracionMinutos, Ubicacion, Medio, Resultado, Calificacion, Comentarios, Activo)
    VALUES
        (@idCand1, @idVac1, @idUsrRhEnt, 'RH', DATEADD(DAY,2,SYSDATETIME()),
         45, 'Sala RH', 'Presencial', 'PENDIENTE', NULL, 'Entrevista inicial demo', 1);
END
GO

IF EXISTS (SELECT 1 FROM sys.tables t JOIN sys.schemas s ON s.schema_id=t.schema_id WHERE s.name='rh' AND t.name='entrevista_evaluador')
AND NOT EXISTS (SELECT 1 FROM rh.entrevista_evaluador)
BEGIN
    DECLARE @idEntDemo   int = (SELECT TOP 1 ID_Entrevista FROM rh.entrevista);
    DECLARE @idUsrRhEval int = (SELECT TOP 1 ID_Usuario FROM core.usuario WHERE Email = 'rh@portalv2.local');

    INSERT INTO rh.entrevista_evaluador (ID_Entrevista, ID_Usuario, Rol, Calificacion, Comentarios, FechaEvaluacion)
    VALUES (@idEntDemo, @idUsrRhEval, 'RH', 8.50, 'Buen perfil para continuar proceso', SYSDATETIME());
END
GO

IF EXISTS (SELECT 1 FROM sys.tables t JOIN sys.schemas s ON s.schema_id=t.schema_id WHERE s.name='rh' AND t.name='oferta_laboral')
AND NOT EXISTS (SELECT 1 FROM rh.oferta_laboral)
BEGIN
    DECLARE @idCandOferta int = (SELECT TOP 1 ID_Candidato FROM rh.candidato WHERE Correo = 'maria.garcia.demo@mail.com');
    DECLARE @idVacOferta  int = (SELECT TOP 1 ID_Vacante FROM rh.vacante WHERE Folio = 'VAC-2026-001');

    INSERT INTO rh.oferta_laboral
        (ID_Candidato, ID_Vacante, SalarioOfertado, FechaVencimiento, Estatus, FechaIngreso, Activo)
    VALUES
        (@idCandOferta, @idVacOferta, 21000,
         DATEADD(DAY,7,SYSDATETIME()), 'ENVIADA',
         DATEADD(DAY,15,CAST(SYSDATETIME() AS date)), 1);
END
GO

IF EXISTS (SELECT 1 FROM sys.tables t JOIN sys.schemas s ON s.schema_id=t.schema_id WHERE s.name='rh' AND t.name='nota_proceso')
AND NOT EXISTS (SELECT 1 FROM rh.nota_proceso)
BEGIN
    DECLARE @idVacNota  int = (SELECT TOP 1 ID_Vacante FROM rh.vacante WHERE Folio = 'VAC-2026-001');
    DECLARE @idCandNota int = (SELECT TOP 1 ID_Candidato FROM rh.candidato WHERE Correo = 'juan.perez.demo@mail.com');
    DECLARE @idUsrRhNota int = (SELECT TOP 1 ID_Usuario FROM core.usuario WHERE Email = 'rh@portalv2.local');

    INSERT INTO rh.nota_proceso (ID_Vacante, ID_Candidato, Nota, ID_Usuario)
    VALUES (@idVacNota, @idCandNota, 'Nota demo de seguimiento del proceso.', @idUsrRhNota);
END
GO

/* ============================================================
   11. CORE / COM: parametros y datos demo
============================================================ */
IF NOT EXISTS (SELECT 1 FROM core.parametro)
BEGIN
    INSERT INTO core.parametro (Clave, Valor, TipoDato, Modulo, Descripcion)
    VALUES
      ('LOGIN_MAX_INTENTOS',      '5',  'INT', 'core', 'Intentos fallidos antes de bloqueo'),
      ('LOGIN_BLOQUEO_MINUTOS',   '15', 'INT', 'core', 'Minutos de bloqueo'),
      ('SESION_EXPIRACION_HORAS', '8',  'INT', 'core', 'Horas de vigencia de sesion'),
      ('TOKEN_RESET_MINUTOS',     '30', 'INT', 'core', 'Minutos de vigencia token'),
      ('HD_SLA_CRITICO_HORAS',    '4',  'INT', 'hd',   'SLA critico'),
      ('HD_SLA_NORMAL_HORAS',     '24', 'INT', 'hd',   'SLA normal'),
      ('RH_DIAS_VIGENCIA_VACANTE','90', 'INT', 'rh',   'Dias maximos de vacante');
END
GO

IF NOT EXISTS (SELECT 1 FROM com.meta_mes_portada)
BEGIN
    DECLARE @idUsrAdminMeta int = (SELECT TOP 1 ID_Usuario FROM core.usuario WHERE Email = 'admin@portalv2.local');

    INSERT INTO com.meta_mes_portada (Anio, Mes, Nombre, DiasHabiles, ID_UsuarioCreo)
    VALUES
      (2026, 4, 'ABRIL', 22, @idUsrAdminMeta),
      (2026, 5, 'MAYO',  21, @idUsrAdminMeta);
END
GO

IF NOT EXISTS (SELECT 1 FROM com.semana)
BEGIN
    DECLARE @idMesAbr        int = (SELECT TOP 1 ID_MetaMes FROM com.meta_mes_portada WHERE Anio = 2026 AND Mes = 4);
    DECLARE @idMesMay        int = (SELECT TOP 1 ID_MetaMes FROM com.meta_mes_portada WHERE Anio = 2026 AND Mes = 5);
    DECLARE @idUsrAdminSemana int = (SELECT TOP 1 ID_Usuario FROM core.usuario WHERE Email = 'admin@portalv2.local');

    INSERT INTO com.semana (Anio, Semana, FechaInicio, FechaFin, ID_MetaMesInicio, ID_MetaMesFinal, DiasMesInicio, DiasMesFinal, ID_UsuarioCreo, Activo)
    VALUES
      (2026, 17, '2026-04-20', '2026-04-26', @idMesAbr, @idMesAbr, 7, 0, @idUsrAdminSemana, 1),
      (2026, 18, '2026-04-27', '2026-05-03', @idMesAbr, @idMesMay, 4, 3, @idUsrAdminSemana, 1);
END
GO

IF NOT EXISTS (SELECT 1 FROM com.indicador)
BEGIN
    INSERT INTO com.indicador (Clave, Nombre, Categoria, OrdenResumen, Activo)
    VALUES
      ('VOL', 'Volumen de venta',        'VENTAS',     1, 1),
      ('COB', 'Cobertura de categoria',  'VENTAS',     2, 1),
      ('EFE', 'Efectividad',             'VENTAS',     3, 1),
      ('EFI', 'Eficiencia',              'VENTAS',     4, 1),
      ('DF1', 'Devolucion F1',           'CALIDAD',    5, 1),
      ('DAU', 'Devolucion autoservicio', 'CALIDAD',    6, 1),
      ('NSE', 'NSE nivel de servicio',   'CALIDAD',    7, 1),
      ('DOC', 'Documentos y checklist',  'DOCUMENTOS', 8, 1);
END
GO

IF NOT EXISTS (SELECT 1 FROM com.sub_indicador)
BEGIN
    DECLARE @idVol2 int = (SELECT TOP 1 ID_Indicador FROM com.indicador WHERE Clave = 'VOL');
    DECLARE @idCob2 int = (SELECT TOP 1 ID_Indicador FROM com.indicador WHERE Clave = 'COB');
    DECLARE @idDoc2 int = (SELECT TOP 1 ID_Indicador FROM com.indicador WHERE Clave = 'DOC');

    INSERT INTO com.sub_indicador (ID_Indicador, Clave, Nombre, Orden, Activo)
    VALUES
      (@idVol2, 'EMB', 'Embutidos',              1, 1),
      (@idVol2, 'CF',  'Carnes frias',            2, 1),
      (@idVol2, 'QSO', 'Queso',                  3, 1),
      (@idVol2, 'MTK', 'Manteca',                4, 1),
      (@idCob2, 'BOL', 'Bolonia',                1, 1),
      (@idCob2, 'JAM', 'Jamon',                  2, 1),
      (@idCob2, 'LOM', 'Lomo',                   3, 1),
      (@idDoc2, 'CHK', 'Check list de unidades', 1, 1),
      (@idDoc2, 'LIQ', 'Liquidacion perfecta',   2, 1),
      (@idDoc2, 'MES', 'Mesa de control',         3, 1);
END
GO

PRINT '============================================================';
PRINT 'semilla_v4.sql aplicado correctamente.';
PRINT '============================================================';
GO


/* ============================================================
   12. CORE: puestos completos heredados de semilla_datos.sql
============================================================ */
SET IDENTITY_INSERT core.puesto ON;

INSERT INTO core.puesto (ID_Puesto, Clave, Descripcion, Nivel, Categoria, Segmento, Responsabilidad, Activo)
SELECT v.ID_Puesto, v.Clave, v.Descripcion, v.Nivel, v.Categoria, v.Segmento, v.Responsabilidad, v.Activo
FROM (VALUES
(1,  'P001', 'ALMACENISTA',                        1, 'A', 'GEN', 'N', 1),
(2,  'P002', 'ALMACENISTA A',                      1, 'A', 'GEN', 'N', 1),
(3,  'P003', 'ALMACENISTA B',                      1, 'A', 'GEN', 'N', 1),
(4,  'P004', 'ANALISTA CREDITO Y COBRANZA',         2, 'A', 'GEN', 'N', 1),
(5,  'P005', 'ANALISTA CUENTA CLAVE',               2, 'A', 'GEN', 'N', 1),
(6,  'P006', 'ANALISTA SISTEMAS',                   2, 'A', 'GEN', 'N', 1),
(7,  'P007', 'ANALISTA SOPORTE TECNICO',             2, 'A', 'GEN', 'N', 1),
(8,  'P008', 'ASISTENTE DIRECCION',                 2, 'A', 'GEN', 'N', 1),
(9,  'P009', 'AUDITOR JR',                          2, 'A', 'GEN', 'N', 1),
(10, 'P010', 'AUDITOR SENIOR',                      3, 'A', 'GEN', 'N', 1),
(11, 'P011', 'AUXILIAR COBRANZA',                   1, 'A', 'GEN', 'N', 1),
(12, 'P012', 'AUXILIAR COMPRAS',                    1, 'A', 'GEN', 'N', 1),
(13, 'P013', 'AUXILIAR CONTABILIDAD',               1, 'A', 'GEN', 'N', 1),
(14, 'P014', 'AUXILIAR COSTOS',                     1, 'A', 'GEN', 'N', 1),
(15, 'P015', 'AUXILIAR IMPUESTOS',                  1, 'A', 'GEN', 'N', 1),
(16, 'P016', 'AUXILIAR PROCEDIMIENTOS',             1, 'A', 'GEN', 'N', 1),
(17, 'P017', 'AUXILIAR SEGURIDAD Y SALUD',          1, 'A', 'GEN', 'N', 1),
(18, 'P018', 'CAJERO GENERAL',                      2, 'A', 'GEN', 'N', 1),
(19, 'P019', 'CAJERO LIQUIDADOR',                   2, 'A', 'GEN', 'N', 1),
(20, 'P020', 'CAJERO PUNTO DE VENTA',               2, 'A', 'GEN', 'N', 1),
(21, 'P021', 'CAPTURISTA A',                        1, 'A', 'GEN', 'N', 1),
(22, 'P022', 'CAPTURISTA B',                        1, 'A', 'GEN', 'N', 1),
(23, 'P023', 'COBRADOR',                            1, 'A', 'GEN', 'N', 1),
(24, 'P024', 'CONTADOR COSTOS',                     3, 'A', 'GEN', 'N', 1),
(25, 'P025', 'CONTADOR GENERAL',                    3, 'A', 'GEN', 'N', 1),
(26, 'P026', 'COORDINADOR ADMINISTRATIVO',          3, 'A', 'GEN', 'N', 1),
(27, 'P027', 'COORDINADOR CAPACITACION',            3, 'A', 'GEN', 'N', 1),
(28, 'P028', 'COORDINADOR NOMINA',                  3, 'A', 'GEN', 'N', 1),
(29, 'P029', 'COORDINADOR PAGOS',                   3, 'A', 'GEN', 'N', 1),
(30, 'P030', 'COORDINADOR PROCEDIMIENTOS',          3, 'A', 'GEN', 'N', 1),
(31, 'P031', 'COORDINADOR TRAFICO',                 3, 'A', 'GEN', 'N', 1),
(32, 'P032', 'CUENTA CLAVE AUTOSERVICIO',           2, 'A', 'GEN', 'N', 1),
(33, 'P033', 'DIRECTOR GENERAL',                    5, 'A', 'GEN', 'S', 1),
(34, 'P034', 'ENFERMERO',                           2, 'A', 'GEN', 'N', 1),
(35, 'P035', 'GENERALISTA RRHH',                    2, 'A', 'GEN', 'N', 1),
(36, 'P036', 'GERENTE ADMON Y FINANZAS',             4, 'A', 'GEN', 'S', 1),
(37, 'P037', 'GERENTE COMERCIAL',                   4, 'A', 'GEN', 'S', 1),
(38, 'P038', 'GERENTE DE MERCADOTECNIA Y RP',        4, 'A', 'GEN', 'S', 1),
(39, 'P039', 'GERENTE DE RECURSOS HUMANOS',          4, 'A', 'GEN', 'S', 1),
(40, 'P040', 'GERENTE DE SUCURSAL',                  4, 'A', 'GEN', 'S', 1),
(41, 'P041', 'INSPECTOR CONTROL CALIDAD',            2, 'A', 'GEN', 'N', 1),
(42, 'P042', 'JEFE ADMINISTRACION DE PERSONAL',     3, 'A', 'GEN', 'N', 1),
(43, 'P043', 'JEFE CREDITO Y COBRANZA',              3, 'A', 'GEN', 'N', 1),
(44, 'P044', 'JEFE DE COMPRAS',                      3, 'A', 'GEN', 'N', 1),
(45, 'P045', 'JEFE DE LOGISTICA',                    3, 'A', 'GEN', 'N', 1),
(46, 'P046', 'JEFE DE SOPORTE',                      3, 'A', 'GEN', 'N', 1),
(47, 'P047', 'JEFE DESARROLLO ORGANIZACI',           3, 'A', 'GEN', 'N', 1),
(48, 'P048', 'JEFE PRODUCCION',                      3, 'A', 'GEN', 'N', 1),
(49, 'P049', 'JEFE SEGURIDAD Y SALUD',               3, 'A', 'GEN', 'N', 1),
(50, 'P050', 'JEFE TECNOLOGIA DE INFORMACION',       3, 'A', 'GEN', 'N', 1),
(51, 'P051', 'RECEPCIONISTA',                        1, 'A', 'GEN', 'N', 1),
(52, 'P052', 'SUPERVISOR VENTAS MODERNO',             3, 'A', 'GEN', 'N', 1),
(53, 'P053', 'SUPERVISOR VENTAS TRADICIONAL',         3, 'A', 'GEN', 'N', 1),
(54, 'P054', 'TESORERO',                             3, 'A', 'GEN', 'N', 1),
(55, 'P055', 'COORDINADOR MANTENIMIENTO',            3, 'A', 'GEN', 'N', 1),
(56, 'P056', 'DOCUMENTACION',                        1, 'A', 'GEN', 'N', 1),
(57, 'P057', 'JEFE DE MANTENIMIENTO',                3, 'A', 'GEN', 'N', 1),
(58, 'P058', 'GERENTE DE OPERACIONES',               4, 'A', 'GEN', 'S', 1),
(59, 'P059', 'INSPECTOR DE CALIDAD SR',              2, 'A', 'GEN', 'N', 1),
(60, 'P060', 'ENCARGADO ALMACEN',                    2, 'A', 'GEN', 'N', 1),
(61, 'P061', 'JEFE LABORATORIO',                     3, 'A', 'GEN', 'N', 1),
(62, 'P062', 'COORDINADOR (A) MANTENIMIENTO',        3, 'A', 'GEN', 'N', 1),
(63, 'P063', 'AUXILIAR ALMACEN',                     1, 'A', 'GEN', 'N', 1),
(64, 'P064', 'COORDINADOR DE MANTENIMIENTO DE FLOTILLA', 3, 'A', 'GEN', 'N', 1),
(65, 'P065', 'AUXILIAR PRODUCCION',                  1, 'A', 'GEN', 'N', 1),
(66, 'P066', 'JEFE (A) DE DEPARTAMENTO',             3, 'A', 'GEN', 'N', 1),
(67, 'P067', 'AUXILIAR LABORATORIO',                 1, 'A', 'GEN', 'N', 1),
(68, 'P068', 'AUXILIAR DE DEPARTAMENTO',             1, 'A', 'GEN', 'N', 1),
(69, 'P069', 'COORDINADOR (A) PRODUCCION',           3, 'A', 'GEN', 'N', 1),
(70, 'P070', 'ENCARGADO (A) DE ALMACEN',             2, 'A', 'GEN', 'N', 1),
(71, 'P071', 'LIDER MANTENIMIENTO T1',               2, 'A', 'GEN', 'N', 1),
(72, 'P072', 'JEFE (A) SANIDAD',                     3, 'A', 'GEN', 'N', 1),
(73, 'P073', 'JEFE (A) CALIDAD Y DESARROLLO',        3, 'A', 'GEN', 'N', 1),
(74, 'P074', 'AUXILIAR SUPERVISOR',                  1, 'A', 'GEN', 'N', 1),
(75, 'P075', 'INSPECTOR (A) JR',                     2, 'A', 'GEN', 'N', 1),
(76, 'P076', 'AUXILIAR MANTENIMIENTO T2',             1, 'A', 'GEN', 'N', 1),
(77, 'P077', 'ANALISTA DE PRODUCCION',               2, 'A', 'GEN', 'N', 1),
(78, 'P078', 'AUXILIAR DOCUMENTADOR',                1, 'A', 'GEN', 'N', 1),
(79, 'P079', 'AUXILIAR SOPORTE',                     1, 'A', 'GEN', 'N', 1),
(80, 'P080', 'AUXILIAR MANTENIMIENTO T3',             1, 'A', 'GEN', 'N', 1)
) AS v (ID_Puesto, Clave, Descripcion, Nivel, Categoria, Segmento, Responsabilidad, Activo)
WHERE NOT EXISTS (
    SELECT 1 FROM core.puesto p WHERE p.ID_Puesto = v.ID_Puesto
);

SET IDENTITY_INSERT core.puesto OFF;
GO

/* ============================================================
   13. CORE: empleados completos heredados de semilla_fix.sql
============================================================ */
INSERT INTO core.empleado
    (Numero_Empleado, Nombre, Correo, Extension, Telefono,
     UsuarioAnyDesk, ClaveAnyDesk, ID_Sucursal, ID_Puesto, ID_Area, Activo)
SELECT v.Numero_Empleado, v.Nombre, v.Correo, v.Extension, v.Telefono,
       v.UsuarioAnyDesk, v.ClaveAnyDesk, v.ID_Sucursal, v.ID_Puesto, v.ID_Area, v.Activo
FROM (VALUES
(11,   'CATALINA ORTEGA MUÃ‘OZ',                  NULL,                                            NULL,   NULL, NULL, NULL, 8,  65, 16, 1),
(12,   'LUIS JAVIER FIMBRES ASTIAZARAN',         'luisfa@empacadorarosarito.com',                 NULL,   NULL, NULL, NULL, 2,  33, 10, 1),
(16,   'MARIA IVONE FIMBRES ASTIAZARAN',         NULL,                                            NULL,   NULL, NULL, NULL, 2,   8, 10, 1),
(18,   'ANSELMO SOLIS MARTINEZ',                 NULL,                                            NULL,   NULL, NULL, NULL, 8,  66, 42, 1),
(19,   'JUAN MANUEL GRANADOS CARRILLO',          NULL,                                            NULL,   NULL, NULL, NULL, 8,  66, 43, 1),
(27,   'PAULO MENDEZ AGUILAR',                   NULL,                                            NULL,   NULL, NULL, NULL, 2,  63,  2, 1),
(28,   'MA HILDA HERNANDEZ RODRIGUEZ',           NULL,                                            NULL,   NULL, NULL, NULL, 8,  67, 44, 1),
(38,   'HECTOR IVAN SALCEDO HERNANDEZ',          'i.salcedo@empacadorarosarito.com.mx',           NULL,   NULL, NULL, NULL, 2,  44,  2, 1),
(42,   'ALVARO GARCIA MURILLO',                  NULL,                                            NULL,   NULL, NULL, NULL, 2,  14, 12, 1),
(48,   'VERONICA CRUZ MILLAN',                   'v.cruz@empacadorarosarito.com.mx',              NULL,   NULL, NULL, NULL, 2,  29, 17, 1),
(53,   'JORGE MONTES PEREZ',                     NULL,                                            NULL,   NULL, NULL, NULL, 8,  68, 45, 1),
(72,   'ANTONIO NELSON ACOSTA HIGUERA',          NULL,                                            NULL,   NULL, NULL, NULL, 8,  66, 46, 1),
(89,   'MARIA TERESA BASTIDAS BURGOS',           't.bastidas@empacadorarosarito.com.mx',          NULL,   NULL, NULL, NULL, 2,  51, 17, 1),
(94,   'ROGELIO ISAIAS JIMENEZ PEREZ',           NULL,                                            NULL,   NULL, NULL, NULL, 8,  66, 45, 1),
(97,   'JORGE DOMINGO ZATARAIN BALDENEGRO',      NULL,                                            NULL,   NULL, NULL, NULL, 8,  66, 47, 1),
(100,  'GERARDO GOMEZ MOLINA',                   NULL,                                            NULL,   NULL, NULL, NULL, 3,  63,  3, 1),
(104,  'MARIA DEL ROSARIO RIOS VILLALOBOS',      'r.rios@empacadorarosarito.com.mx',              NULL,   NULL, NULL, NULL, 8,  48, 16, 1),
(106,  'ALMA LUZ ESPARZA AGUAYO',                NULL,                                            NULL,   NULL, NULL, NULL, 8,  69, 16, 1),
(135,  'HILDA IRENE GARCIA RIVAS',               'h.garcia@empacadorarosarito.com.mx',            NULL,   NULL, NULL, NULL, 2,  10, 11, 1),
(141,  'JERARDO CARRILLO CAMPOZ',                NULL,                                            NULL,   NULL, NULL, NULL, 2,  23,  7, 1),
(155,  'BERNARDINO PEREZ CERVANTES',             NULL,                                            NULL,   NULL, NULL, NULL, 8,  70, 48, 1),
(171,  'SERGIO OSUNA OSUNA',                     NULL,                                            NULL,   NULL, NULL, NULL, 8,  66, 42, 1),
(314,  'LIDIA RAMIREZ PARRA',                    'l.ramirez@empacadorarosarito.com.mx',           NULL,   NULL, NULL, NULL, 6,  26,  6, 1),
(447,  'MIGUEL ANGEL RAMIREZ RODRIGUEZ',         NULL,                                            NULL,   NULL, NULL, NULL, 8,  63, 48, 1),
(457,  'MARIO JESUS ESCOBEDO FIMBRES',           'm.escobedo@empacadorarosarito.com.mx',          NULL,   NULL, NULL, NULL, 7,  38, 10, 1),
(539,  'ROSA ARMIDA LOPEZ DIAZ',                 'r.lopez@empacdorarosarito.com.mx',              NULL,   NULL, NULL, NULL, 1,  40,  1, 1),
(640,  'JONATHAN MARQUEZ ROSALES',               NULL,                                            NULL,   NULL, NULL, NULL, 8,  71, 19, 1),
(667,  'CARINA ADRIANA CHAPARRO GONZALEZ',       'aux.reclutamiento@empacadorarosarito.com.mx',   NULL,   NULL, NULL, NULL, 2,  35, 14, 1),
(687,  'MAYRA MIRANDA RIOS',                     NULL,                                            NULL,   NULL, NULL, NULL, 3,  52,  3, 1),
(1054, 'HECTOR MANUEL RODRIGUEZ MORONES',        'h.rodriguez@empacadorarosarito.com.mx',         NULL,   NULL, NULL, NULL, 7,  37, 10, 1),
(1100, 'ALMA PATRICIA LOPEZ BURGOS',             'a.lopez@empacadorarosarito.com.mx',             NULL,   NULL, NULL, NULL, 2,  36, 10, 1),
(1214, 'ISRAEL ARMANDO AGUILAR LICEA',           'i.aguilar@empacadorarosarito.com.mx',           NULL,   NULL, NULL, NULL, 7,  45, 15, 1),
(1232, 'CRISTINA SAUCEDO RAMIREZ',               'c.saucedo@empacadorarosarito.com.mx',           '1142', NULL, NULL, NULL, 2,  25, 12, 1),
(1263, 'JOSE ALFREDO ZAMORA BENITES',            NULL,                                            NULL,   NULL, NULL, NULL, 8,  70, 16, 1),
(1410, 'JOSE ALFREDO VAZQUEZ LOPEZ',             NULL,                                            NULL,   NULL, NULL, NULL, 1,  53,  1, 1),
(1479, 'MARCIA CRISTINA FIMBRES ASTIAZARAN',     'm.fimbres@empacadorarosarito.com.mx',           NULL,   NULL, NULL, NULL, 2,  54, 17, 1),
(1642, 'JOEL LEONEL GARCIA GARCIA',              'j.garcia@empacadorarosarito.com.mx',            NULL,   NULL, NULL, NULL, 2,   9, 11, 1),
(1662, 'MARCELA JIMENEZ REGALADO',               NULL,                                            NULL,   NULL, NULL, NULL, 8,  66, 49, 1),
(1716, 'KARLA EVELYN NUÃ‘EZ PLANCARTE',           'k.nunez@empacadorarosarito.com.mx',             NULL,   NULL, NULL, NULL, 2,  28, 14, 1),
(1730, 'YESMIN ROCIO BERNAL ROMERO',             'r.bernal@empacadorarosarito.com.mx',            NULL,   NULL, NULL, NULL, 2,  24, 12, 1),
(1837, 'LUIS ALBERTO FIMBRES VILLASEÃ‘OR',        'l.fimbres@empacadorarosarito.com.mx',           '1125', NULL, NULL, NULL, 8,  58, 10, 1),
(1849, 'JOSE ALFREDO MUNGUIA VALENZUELA',        NULL,                                            NULL,   NULL, NULL, NULL, 3,  53,  3, 0),
(1864, 'RAFAEL ERNESTO BARRERA BERNAL',          'apt.her@empacadorarosarito.com.mx',             NULL,   NULL, NULL, NULL, 3,   2,  3, 1),
(1945, 'ANAYELI MONTAÃ‘O FLORES',                 'cxc.hermosillo@empacadorarosarito.com.mx',      NULL,   NULL, NULL, NULL, 3,  26,  3, 1),
(1951, 'JESUS ALBERTO LEYVA MARTINEZ',           NULL,                                            NULL,   NULL, NULL, NULL, 3,  40,  3, 0),
(2081, 'KARIM ARMANDO SOTO CARRILLO',            'a.soto@empacadorarosarito.com.mx',              '1131', NULL, NULL, NULL, 8,  60, 19, 1),
(2105, 'FRANCISCO GILBERTO VALENZUELA MORENO',   NULL,                                            NULL,   NULL, NULL, NULL, 6,  63,  6, 1),
(2189, 'JESUS RAMON MURILLO AYON',               NULL,                                            NULL,   NULL, NULL, NULL, 8,  72, 50, 1),
(2242, 'ADALBERTO JIMENEZ TORRUCO',              'a.jimenez@empacadorarosarito.com.mx',           '1138', NULL, NULL, NULL, 8,  55, 19, 1),
(2321, 'HUGO DIAZ',                              NULL,                                            NULL,   NULL, NULL, NULL, 8,  63, 48, 1),
(2373, 'JORGE LUIS BORRAYO ROSAS',               NULL,                                            NULL,   NULL, NULL, NULL, 8,  68, 46, 1),
(2376, 'CYNTHIA LUCIA ROMERO JIMENEZ',           'enfermeria@empacadorarosarito.com.mx',          NULL,   NULL, NULL, NULL, 2,  34, 14, 1),
(2515, 'VICTOR GONZALEZ GONZALEZ',               NULL,                                            NULL,   NULL, NULL, NULL, 1,  53,  1, 1),
(2575, 'IRIS VANESSA LANGARICA MARTINEZ',        'v.langarica@empacadorarosarito.com.mx',         NULL,   NULL, NULL, NULL, 8,  46, 16, 1),
(2622, 'JOSE ENRIQUE MURRIETA DELGADO',          NULL,                                            NULL,   NULL, NULL, NULL, 8,  68, 43, 1),
(2623, 'JUANA LIZBETH MURRIETA DELGADO',         'a.costos@empacadorarosarito.com.mx',            NULL,   NULL, NULL, NULL, 2,  14, 12, 1),
(2720, 'BRIANDA DIANEY MARTINEZ PONCE',          'cxc.mexicali@empacadorarosarito.com.mx',        NULL,   NULL, NULL, NULL, 1,  11,  1, 1),
(2754, 'NORMAN JONATHAN VALENCIA TAPIA',         NULL,                                            NULL,   NULL, NULL, NULL, 8,  73, 51, 1),
(2781, 'LUIS ALBERTO RODRIGUEZ PAEZ',            'l.rodriguez@empacadorarosarito.com.mx',         NULL,   NULL, NULL, NULL, 6,  41,  6, 1),
(2784, 'LORENA JIMENEZ CORRAL',                  'l.jimenez@empacadorarosarito.com.mx',           NULL,   NULL, NULL, NULL, 3,  41,  3, 1),
(2793, 'EDGAR HERIBERTO BENAVIDES SALAZAR',      NULL,                                            NULL,   NULL, NULL, NULL, 4,  53,  4, 1),
(2874, 'RAFAEL MANUEL PINEDA MARSHALL',          'r.pineda@empacadorarosarito.com.mx',            '1122', NULL, NULL, NULL, 8,  51, 19, 1),
(2924, 'DIANA JUDITH OSUNA SANTIAGO',            'documentacion@empacadorarosarito.com.mx',       '1141', NULL, NULL, NULL, 8,  56, 20, 1),
(2953, 'SAMUEL MEJIA CHACON',                    's.mejia@empacadorarosarito.com.mx',             NULL,   NULL, NULL, NULL, 2,  12,  2, 1),
(3134, 'JUAN MARTIN HUIZAR VILLANUEVA',          NULL,                                            NULL,   NULL, NULL, NULL, 3,  52,  3, 1),
(3240, 'GRECIA MAGDALENA VILLA CONTRERAS',       NULL,                                            NULL,   NULL, NULL, NULL, 3,  18,  3, 0),
(3257, 'SILVIA VALENZUELA ENCINAS',              'pv.obregon@empacadorarosarito.com.mx',          NULL,   NULL, NULL, NULL, 6,  20,  6, 1),
(3258, 'RUBEN NOE SANCHEZ',                      'apt.ensenada@empacadorarosarito.com.mx',        NULL,   NULL, NULL, NULL, 5,   3,  5, 1),
(3319, 'RAFAEL DANIEL MUÃ‘OZ CONTRERAS',          NULL,                                            NULL,   NULL, NULL, NULL, 8,  63, 52, 1),
(3400, 'SERGIO ANDREY MONTES HERNANDEZ',         NULL,                                            NULL,   NULL, NULL, NULL, 5,  53,  5, 1),
(3401, 'CARLOS MANUEL FUENTES GOMEZ',            NULL,                                            NULL,   NULL, NULL, NULL, 5,  53,  5, 1),
(3428, 'CLAUDIA DIAZ GALLEGOS',                  'cxc.ensenada@empacadorarosarito.com.mx',        NULL,   NULL, NULL, NULL, 5,  11,  5, 1),
(3504, 'STEPHANY LIZETH RIVAS VALLE',            'pv.tijuana@empacadorarosarito.com.mx',          NULL,   NULL, NULL, NULL, 4,  20,  4, 1),
(3572, 'MIRIAN NOEMI LUA MAGALLANES',            'c.mexicali@empacadorarosarito.com.mx',          NULL,   NULL, NULL, NULL, 1,  19,  1, 1),
(3578, 'JULIO CESAR LOPEZ MAC GREW',             NULL,                                            NULL,   NULL, NULL, NULL, 3,  53,  3, 1),
(3620, 'DAVID MONTES DIAZ',                      NULL,                                            NULL,   NULL, NULL, NULL, 7,  63, 15, 1),
(3657, 'YESENIA BORBON APODACA',                 NULL,                                            NULL,   NULL, NULL, NULL, 1,  52,  1, 0),
(3740, 'RICARDO VILLALVAZO FALCON',              'r.villalvazo@empacadorarosarito.com.mx',        NULL,   NULL, NULL, NULL, 5,  40,  5, 1),
(3806, 'FABIOLA CHAVEZ URREA',                   'f.chavez@empacadorarosarito.com.mx',            NULL,   NULL, NULL, NULL, 6,  35,  6, 1),
(3884, 'EDWARD ANTONIO RANGEL LANDINO',          'e.rangel@empacadorarosarito.com.mx',            NULL,   NULL, NULL, NULL, 7,  31, 18, 1),
(3925, 'JESUS EDUARDO VILLEGAS ARMENTA',         'j.villegas@empacadorarosarito.com.mx',          NULL,   NULL, NULL, NULL, 2,  13, 12, 1),
(3927, 'YOLANDA MERAZ MERAZ',                    NULL,                                            NULL,   NULL, NULL, NULL, 4,  52,  4, 1),
(3935, 'MARIA INES GOMEZ LEON',                  'pv.mexicali@empacadorarosarito.com.mx',         NULL,   NULL, NULL, NULL, 1,  20,  1, 1),
(3958, 'FRANCIA NISSAN GALLEGOS HUITRON',        'c.ensenada@empacadorarosarito.com.mx',          NULL,   NULL, NULL, NULL, 5,  19,  5, 1),
(4049, 'KAREN SHANTELL BAYLISS GONZALEZ',        NULL,                                            NULL,   NULL, NULL, NULL, 4,  19,  4, 0),
(4075, 'AZUCENA CORONADO CRUZ',                  'rh.hermosillo@empacadorarosarito.com.mx',       NULL,   NULL, NULL, NULL, 3,  35,  3, 1),
(4159, 'HUGO IVAN BARBA SORIA',                  NULL,                                            NULL,   NULL, NULL, NULL, 8,  35, 16, 1),
(4263, 'MICHELLE DESIRE CARMONA ALBARRAN',       'rh.ensenada@empacadorarosarito.com.mx',         NULL,   NULL, NULL, NULL, 5,  35,  5, 1),
(4389, 'MARIA ALEJANDRA ACEVEDO AMADOR',         'e.calidad@empacadorarosarito.com.mx',           NULL,   NULL, NULL, NULL, 5,  20,  5, 1),
(4441, 'KEILA ROCHA TAGLE',                      'k.rocha@empacadorarosarito.com.mx',             NULL,   NULL, NULL, NULL, 2,  30, 13, 1),
(4517, 'OSCAR ALEJANDRO RUELAS OBESO',           NULL,                                            NULL,   NULL, NULL, NULL, 7,  63, 15, 1),
(4525, 'GERARDO ALONSO SEGOVIANO SANCHEZ',       NULL,                                            NULL,   NULL, NULL, NULL, 4,  53,  4, 1),
(4558, 'HUMBERTO ROJAS GERONIMO',                NULL,                                            NULL,   NULL, NULL, NULL, 8,  21, 16, 0),
(4559, 'ALEJANDRA ELISA CASTRO MURILLO',         NULL,                                            NULL,   NULL, NULL, NULL, 6,  19,  6, 0),
(4562, 'RAUL RICARDO GARCIA DAVALOS',            NULL,                                            NULL,   NULL, NULL, NULL, 8,  68, 42, 1),
(4578, 'MANUEL DIAZ MENDEZ',                     'm.diaz@empacadorarosarito.com.mx',              NULL,   NULL, NULL, NULL, 2,  50,  9, 1),
(4638, 'JAQUELINE LOPEZ RAMIREZ',                NULL,                                            NULL,   NULL, NULL, NULL, 8,  67, 44, 1),
(4663, 'SAUL ADRIAN GALAVIZ GARCIA',             NULL,                                            NULL,   NULL, NULL, NULL, 3,  63,  3, 1),
(4676, 'EMILIO ZAMORA TORRES',                   'e.zamora@empacadorarosarito.com.mx',            NULL,   NULL, NULL, NULL, 2,   7,  9, 1),
(4692, 'ITZEL CUADRAS GAXIOLA',                  NULL,                                            '1136', NULL, NULL, NULL, 8,  59, 20, 1),
(4744, 'JORGE ALEXIS FREGOSO RUIZ',              NULL,                                            NULL,   NULL, NULL, NULL, 4,  41,  4, 1),
(4758, 'JOSE LUIS RODRIGUEZ FLORES',             NULL,                                            NULL,   NULL, NULL, NULL, 8,  74, 50, 1),
(4766, 'GLADIS ADRIANA GARCIA VALENZUELA',       'supervisor.her@empacadorarosarito.com.mx',      NULL,   NULL, NULL, NULL, 3,  52,  3, 1),
(4828, 'ULISES AGUSTIN ZAMORA BAÃ‘UELOS',         NULL,                                            NULL,   NULL, NULL, NULL, 8,  67, 44, 1),
(4840, 'JUAN CARLOS GARCIA HERNANDEZ',           NULL,                                            NULL,   NULL, NULL, NULL, 8,  75, 51, 1),
(4871, 'SERGIO CATAÃ‘O GOMEZ',                    NULL,                                            NULL,   NULL, NULL, NULL, 2,  63,  2, 1),
(4873, 'RAFAEL SALDIVAR CARRILLO',               NULL,                                            NULL,   NULL, NULL, NULL, 4,  63,  4, 1),
(4894, 'MARCO ANTONIO PATRON SAUCEDA',           NULL,                                            NULL,   NULL, NULL, NULL, 2,  16, 13, 1),
(4904, 'NOHEMI GUADALUPE GUTIERREZ GARCIA',      'c.hermosillo@empacadorarosarito.com.mx',        NULL,   NULL, NULL, NULL, 3,  19,  3, 1),
(4921, 'NORA GUADALUPE TORRES ONTIVEROS',        'pv.hermosillo@empacadorarosarito.com.mx',       NULL,   NULL, NULL, NULL, 3,  20,  3, 1),
(4922, 'MARIA DEL CARMEN TRAVANINO MEJIA',       'm.travanino@empacadorarosarito.com.mx',         NULL,   NULL, NULL, NULL, 2,  49, 14, 1),
(4946, 'FRANCISCO ANTONIO OLIVAS MARTINEZ',      NULL,                                            NULL,   NULL, NULL, NULL, 3,  53,  3, 0),
(4975, 'YESENIA MARQUEZ ARADILLAS',              'y.marquez@empacadorarosarito.com.mx',           NULL,   NULL, NULL, NULL, 2,  27, 14, 1),
(4981, 'ROBERTO MENDOZA TRUJILLO',               'r.mendoza@empacadorarosarito.com.mx',           NULL,   NULL, NULL, NULL, 7,  32,  8, 1),
(5026, 'MAYRA IRASEMA PARTIDA LOZANO',           'c3.hermosillo@empacadorarosarito.com.mx',       NULL,   NULL, NULL, NULL, 3,  11,  3, 1),
(5039, 'LARISSA RIVERA RODRIGUEZ',               NULL,                                            NULL,   NULL, NULL, NULL, 2,   4,  7, 0),
(5041, 'VIANEY RODRIGUEZ GARCIA',                NULL,                                            NULL,   NULL, NULL, NULL, 4,  19,  4, 0),
(5064, 'MIRIAM NAYE JIMENEZ MADERO',             NULL,                                            NULL,   NULL, NULL, NULL, 8,  75, 51, 1),
(5072, 'DIEGO ANTONIO MORALES CATALAN',          'auxss@empacadorarosarito.com.mx',               NULL,   NULL, NULL, NULL, 2,  17, 14, 1),
(5080, 'ABIGAIL CASTAÃ‘EDA MARTINEZ',             'rh.mexicali@empacadorarosarito.com.mx',         NULL,   NULL, NULL, NULL, 1,  35,  1, 1),
(5088, 'GUILLERMO SUSUNAGA RODRIGUEZ',           NULL,                                            NULL,   NULL, NULL, NULL, 8,  75, 51, 1),
(5095, 'DALAI ESPINOZA GUTIERREZ',               'inspeccion@empacadorarosarito.com.mx',          '1136', NULL, NULL, NULL, 8,  59, 20, 1),
(5119, 'FRANCISCO RODRIGUEZ SALAZAR',            'almacenplantasecos@empacadorarosarito.com.mx',  NULL,   NULL, NULL, NULL, 2,   2,  2, 1),
(5120, 'JESUS BENJAMIN BORQUEZ VERDUGO',         'apt.obregon@empacadorarosarito.com.mx',         NULL,   NULL, NULL, NULL, 6,   3,  6, 1),
(5121, 'JOSE LUIS GUDIÃ‘O PICHARDO',              NULL,                                            NULL,   NULL, NULL, NULL, 6,  53,  6, 1),
(5126, 'JESUS GIOVANNI SILVA MORALES',           NULL,                                            NULL,   NULL, NULL, NULL, 8,  68, 53, 1),
(5129, 'JOSE GILBERTO BELTRAN CRUZ',             NULL,                                            NULL,   NULL, NULL, NULL, 8,  76, 19, 1),
(5133, 'MARIA DE JESUS GARCIA RAMIREZ',          'ca1.produccion@empacadorarosarito.com.mx',      NULL,   NULL, NULL, NULL, 8,  22, 16, 1),
(5152, 'LAZARO CORNEJO SANCHEZ',                 'a.procedimientos@empacadorarosarito.com.mx',    NULL,   NULL, NULL, NULL, 2,  16, 13, 1),
(5182, 'MARCELA LORELEY ESQUIVEL GONZALEZ',      'supervisor.mex@empacadorarosarito.com.mx',      NULL,   NULL, NULL, NULL, 1,  52,  1, 1),
(5183, 'ERIKA ALEJANDRA FLORES MONTERO',         NULL,                                            NULL,   NULL, NULL, NULL, 4,  52,  4, 1),
(5189, 'NAYDA DEL CARMEN ROQUE CARRILLO',        'a.nomina@empacadorarosarito.com.mx',            NULL,   NULL, NULL, NULL, 2,  35, 14, 1),
(5196, 'ULISES ADALBERTO SAUCEDA LOPEZ',         'reclutamiento@empacadorarosarito.com.mx',       NULL,   NULL, NULL, NULL, 2,  35, 14, 1),
(5217, 'MANUEL ENRIQUE ARGUELLES IBARRA',        NULL,                                            NULL,   NULL, NULL, NULL, 3,  63,  3, 1),
(5218, 'EDUARDO JIMENEZ MENDEZ',                 NULL,                                            NULL,   NULL, NULL, NULL, 1,  63,  1, 1),
(5225, 'IVER GEOVANNY VELAZCO ARMENDARIZ',       NULL,                                            NULL,   NULL, NULL, NULL, 4,   2,  4, 0),
(5230, 'DANIEL ALEJANDRO SALAS MONTOYA',         NULL,                                            NULL,   NULL, NULL, NULL, 4,  21,  4, 1),
(5237, 'ARIANA CASTAÃ‘EDA DEL CASTILLO',          NULL,                                            NULL,   NULL, NULL, NULL, 8,  75, 51, 1),
(5255, 'AIDA ARACELI OCHOA MARTINEZ',            'a.ochoa@empacadorarosarito.com.mx',             NULL,   NULL, NULL, NULL, 2,  39, 10, 1),
(5270, 'ANGEL ADOLFO ACUÃ‘A MARTINEZ',            'apt.mexicali@empacadorarosarito.com.mx',        NULL,   NULL, NULL, NULL, 1,   1,  1, 1),
(5290, 'JOSE JESUS GARCIA LEYVA',                NULL,                                            NULL,   NULL, NULL, NULL, 6,  63,  6, 1),
(5300, 'MACARIO PEREZ VENTURA',                  NULL,                                            NULL,   NULL, NULL, NULL, 8,  68, 47, 1),
(5312, 'MIGUEL RODRIGUEZ BORBON',                NULL,                                            NULL,   NULL, NULL, NULL, 6,  53,  6, 1),
(5316, 'JOAHNNA MICHELLE PEREZ CASTILLO',        'a.contable@empacadorarosarito.com.mx',          NULL,   NULL, NULL, NULL, 2,  13, 12, 1),
(5341, 'JUAN DANIEL GOMEZ HERRERA',              NULL,                                            NULL,   NULL, NULL, NULL, 5,  63,  5, 1),
(5343, 'MARIA ELENA RODRIGUEZ DAVILA',           'e.rodriguez@empacadorarosarito.com.mx',         NULL,   NULL, NULL, NULL, 2,  42, 14, 1),
(5346, 'EDGAR ANTONIO GOMEZ LOPEZ',              'e.gomez@empacadorarosarito.com.mx',             NULL,   NULL, NULL, NULL, 2,   6,  9, 1),
(5347, 'MARIA FERNANDA DE LA CRUZ PERALTA',      NULL,                                            NULL,   NULL, NULL, NULL, 8,  75, 51, 1),
(5348, 'MARIA FERNANDA LOPEZ VAZQUEZ',           NULL,                                            NULL,   NULL, NULL, NULL, 8,  77, 16, 1),
(5351, 'HECTOR NARCISO MERCADO FLORES',          NULL,                                            NULL,   NULL, NULL, NULL, 4,  52,  4, 1),
(5353, 'MARIA GUADALUPE PADILLA BORQUEZ',        NULL,                                            NULL,   NULL, NULL, NULL, 8,  75, 51, 1),
(5373, 'ALEJANDRO DELGADO CARDENAS',             NULL,                                            NULL,   NULL, NULL, NULL, 4,  40,  4, 0),
(5407, 'EMANUEL SERGIO BARTOLON MUÃ‘OZ',          'cd.calidad@empacadorarosarito.com.mx',          NULL,   NULL, NULL, NULL, 4,  41,  4, 1),
(5411, 'MANUEL IGNACIO COTA BLAKE',              'supervisor.ens@empacadorarosarito.com.mx',      NULL,   NULL, NULL, NULL, 5,  52,  5, 1),
(5419, 'LIZBETH GAMA VELAZQUEZ',                 NULL,                                            NULL,   NULL, NULL, NULL, 8,  78, 54, 1),
(5426, 'CARLOS EDUARDO LOPEZ ENCINAS',           'c.lopez@empacadorarosarito.com.mx',             NULL,   NULL, NULL, NULL, 6,  40,  6, 1),
(5439, 'JOSE GERARDO CASAS ARMENTA',             NULL,                                            NULL,   NULL, NULL, NULL, 2,  63,  2, 1),
(5444, 'IVAN DE JESUS SANCHEZ OLIVAS',           NULL,                                            NULL,   NULL, NULL, NULL, 1,  63,  1, 1),
(5488, 'MARCO ANTONIO QUEZADA GARCIA',           NULL,                                            NULL,   NULL, NULL, NULL, 4,  63,  4, 1),
(5502, 'OSCAR ALEJANDRO FIMBRES TORRES',         NULL,                                            NULL,   NULL, NULL, NULL, 4,  52,  4, 1),
(5504, 'JOSE OSWALDO CAMPOY ORTIZ',              'supervisor.obr@empacadorarosarito.com.mx',      NULL,   NULL, NULL, NULL, 6,  53,  6, 1),
(5527, 'BRIAN DANIEL ASTUDILLO BELTRAN',         NULL,                                            NULL,   NULL, NULL, NULL, 8,  21, 16, 1),
(5530, 'JOHANA PATRICIA CHAPARRO GONZALEZ',      'pv.alamos@empacadorarosarito.com.mx',           NULL,   NULL, NULL, NULL, 4,  20,  4, 1),
(5534, 'JOSUE RODOLFO LOPEZ ZAMARRIPA',          NULL,                                            NULL,   NULL, NULL, NULL, 8,  79, 16, 1),
(5537, 'ANGEL ROBERTO FLORES DURAZO',            'a.flores@empacadorarosarito.com.mx',            NULL,   NULL, NULL, NULL, 7,   5,  8, 1),
(5551, 'DIANA CRISTINA FLORES AMORES',           'ca2.produccion@empacadorarosarito.com.mx',      NULL,   NULL, NULL, NULL, 8,  22, 16, 1),
(5555, 'CRISTINA LIZBETH PARRA VENTURA',         'calidadmexicali@empacadorarosarito.com.mx',     NULL,   NULL, NULL, NULL, 1,  41,  1, 1),
(5559, 'RICARDO DE LA TRINIDAD FELIX',           NULL,                                            NULL,   NULL, NULL, NULL, 8,  75, 51, 1),
(5567, 'ZINDY LISSETTE DELFIN HERNANDEZ',        'cd.tijuana@empacadorarosarito.com.mx',          NULL,   NULL, NULL, NULL, 7,  21, 15, 1),
(5575, 'MARTIN ELEAZAR AYALA IBARRA',            NULL,                                            NULL,   NULL, NULL, NULL, 8,  66, 53, 1),
(5594, 'RICARDO ARCADIO HERNANDEZ LUNA',         'laboratorio@empacadorarosarito.com.mx',         '1126', NULL, NULL, NULL, 8,  61, 20, 1),
(5601, 'MARISELA AMADOR GODINEZ',                'marisela.a@empacadorarosarito.com.mx',          NULL,   NULL, NULL, NULL, 2,  43,  7, 1),
(5607, 'ARTURO NIETO ESTRADA',                   NULL,                                            NULL,   NULL, NULL, NULL, 8,  63, 52, 1),
(5612, 'MIGUEL ANGEL ACOSTA RODRIGUEZ',          'supervisor.tij@empacadorarosarito.com.mx',      NULL,   NULL, NULL, NULL, 4,  52,  4, 1),
(5636, 'MIRIAM SANCHEZ RIVERA',                  'adm.tijuana@empacadorarosarito.com.mx',         NULL,   NULL, NULL, NULL, 2,   4,  7, 1),
(5637, 'KARINA BERENICE RODRIGUEZ CABALLERO',    NULL,                                            NULL,   NULL, NULL, NULL, 4,  11, 22, 0),
(5641, 'ZADYURY ARIADNA CASTAÃ‘EDA BARRAZA',      NULL,                                            NULL,   NULL, NULL, NULL, 5,  20,  5, 0),
(5642, 'GUSTAVO MANGOL GONZALEZ',                NULL,                                            NULL,   NULL, NULL, NULL, 4,  52,  4, 0),
(5646, 'JONATHAN CASTRO CHAVEZ',                 NULL,                                            NULL,   NULL, NULL, NULL, 2,  15, 12, 0),
(5651, 'JESSICA ANAHI NORIEGA YOC',              NULL,                                            NULL,   NULL, NULL, NULL, 8,  75, 51, 1),
(5660, 'EDGAR RICARDO GOMEZ LEDESMA',            NULL,                                            NULL,   NULL, NULL, NULL, 1,  52,  1, 0),
(5661, 'MONICA RAMIREZ NAVARRO',                 'm.ramirez@empacadorarosarito.com.mx',           NULL,   NULL, NULL, NULL, 2,  47, 14, 1),
(5667, 'KARLA ANABEL PEÃ‘A ZEPEDA',               NULL,                                            NULL,   NULL, NULL, NULL, 1,  52,  1, 1),
(5669, 'ANGELICA MARIA GOMEZ MUÃ‘IZ',             NULL,                                            NULL,   NULL, NULL, NULL, 4,  35,  4, 1),
(5671, 'JOSE RAMON VELAZQUEZ ANGUIANO',          NULL,                                            NULL,   NULL, NULL, NULL, 2,  63,  2, 1),
(5684, 'SERGIO NOE RODRIGUEZ SANCHEZ',           NULL,                                            NULL,   NULL, NULL, NULL, 8,  63, 16, 1),
(5692, 'NOEMI GUADALUPE TORRES ESCOBAR',         NULL,                                            NULL,   NULL, NULL, NULL, 2,  15, 12, 1),
(5697, 'LUIS RODRIGO RAMOS LUNA',                NULL,                                            NULL,   NULL, NULL, NULL, 2,  15, 12, 1),
(5703, 'MARIA ELENA ESTRADA FELIX',              NULL,                                            NULL,   NULL, NULL, NULL, 1,  52,  1, 1),
(5715, 'BLANCA JUDITH CHAVARRIA SOTELO',         'C.OBREGON@EMPACADORAROSARITO.COM.MX',           NULL,   NULL, NULL, NULL, 6,  19,  6, 1),
(5721, 'RICARDO GERARDO FLORES PICO',            NULL,                                            NULL,   NULL, NULL, NULL, 6,  53,  6, 1),
(5754, 'LEOBARDO BRACAMONTES MARTINEZ',          NULL,                                            NULL,   NULL, NULL, NULL, 4,  63,  4, 1),
(5761, 'GERARDO MCDONAUGH MURA',                 NULL,                                            NULL,   '6642572541', NULL, NULL, 4, 40, 4, 0),
(5768, 'LLOICE GUTIERREZ AMARILLAS',             'pv.ensenada@empacadorarosarito.com.mx',         NULL,   NULL, NULL, NULL, 5,  20,  5, 1),
(5772, 'SAUL ROJAS BEAVEN',                      NULL,                                            NULL,   NULL, NULL, NULL, 8,  80, 19, 1),
(5781, 'NAYELI GANTES RIVERO',                   NULL,                                            NULL,   NULL, NULL, NULL, 2,  18, 22, 1),
(5783, 'VICTOR MANUEL PACHECO CARRILLO',         NULL,                                            NULL,   NULL, NULL, NULL, 3,  63,  3, 1),
(5786, 'HUGO ALBERTO AHUMADA JUAREZ',            NULL,                                            NULL,   NULL, NULL, NULL, 4,  40,  4, 0),
(5792, 'LUIS ARTURO GUTIERREZ HERNANDEZ',        NULL,                                            NULL,   NULL, NULL, NULL, 2,  14, 12, 1),
(5798, 'DIEGO ARTURO RIVERA GOMEZ',              NULL,                                            NULL,   NULL, NULL, NULL, 5,  63,  5, 1),
(5803, 'KEVIN EDUARDO HERRERA DE LA CRUZ',       NULL,                                            NULL,   NULL, NULL, NULL, 8,  67, 44, 1),
(5816, 'SUSEL EILEEN RAMIREZ ZATARAIN',          NULL,                                            NULL,   NULL, NULL, NULL, 8,  75, 51, 1),
(5821, 'MARTHA LIZETH QUIÃ‘ONEZ MARTINEZ',        NULL,                                            NULL,   NULL, NULL, NULL, 8,  22, 16, 1),
(5822, 'LUIS AGUSTIN CRUZ RAMIREZ',              NULL,                                            NULL,   NULL, NULL, NULL, 4,  63,  4, 1),
(5828, 'QUEMICH AARON CONTRERAS SEGOVIA',        NULL,                                            NULL,   NULL, NULL, NULL, 3,  63,  3, 1),
(5831, 'CLAUDIA BARRIOS RAMIREZ',                NULL,                                            NULL,   NULL, NULL, NULL, 4,  19,  4, 1),
(5834, 'MIGUEL ANGEL ESTRADA MATA',              NULL,                                            NULL,   NULL, NULL, NULL, 3,  63,  3, 1),
(5839, 'FELIX HUMBERTO ARRIAGA RAYAS',           NULL,                                            NULL,   NULL, NULL, NULL, 4,   2,  4, 1),
(5841, 'VERONICA TEQUIHUATLE TLEHUATLE',         NULL,                                            NULL,   NULL, NULL, NULL, 4,  19,  4, 1),
(5842, 'YURIRIA ALEJANDRA VELAZQUEZ DEL RAZO',   NULL,                                            NULL,   NULL, NULL, NULL, 4,  26,  4, 1),
(5843, 'ELVIX JAIR GONZALEZ RAMOS',              NULL,                                            NULL,   NULL, NULL, NULL, 8,  79, 16, 1),
(5845, 'FERNANDO DELGADO CHAVEZ',                NULL,                                            NULL,   NULL, NULL, NULL, 1,  19,  1, 1),
(5846, 'JOSE GUADALUPE VEGA MORALES',            NULL,                                            NULL,   NULL, NULL, NULL, 4,  63,  4, 1),
(5849, 'CARLOS GABRIEL MEDINA GALVAN',           NULL,                                            NULL,   NULL, NULL, NULL, 7,  64, 18, 1),
(5852, 'ITZEL CRUZ RODRIGUEZ',                   NULL,                                            NULL,   NULL, NULL, NULL, 8,  75, 51, 1)
) AS v (Numero_Empleado, Nombre, Correo, Extension, Telefono,
        UsuarioAnyDesk, ClaveAnyDesk, ID_Sucursal, ID_Puesto, ID_Area, Activo)
WHERE NOT EXISTS (
    SELECT 1 FROM core.empleado e WHERE e.Numero_Empleado = v.Numero_Empleado
);
GO

PRINT '============================================================';
PRINT 'semilla_consolidada_v5.sql aplicado correctamente.';
PRINT '============================================================';
GO
