USE PortalV2;
GO

/* ============================================================
   PORTAL V2 — semilla_v2_desde_v1.sql

   Objetivo:
   - Reusar catálogos base heredables de db_tiket_ti (V1)
   - Excluir datos transaccionales:
       hd.ticket, hd.comentario, hd.encuesta_ticket,
       ped.pedidos, ped.pedido_detalle, ped.pedido_estado_log
   - Generar datos semilla para tablas nuevas de V2

   Alcance:
   - Catálogos CORE / CAT / HD / PED / RH
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
   2. CORE: áreas heredadas de V1 + Serie para V2
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
    (1, 'DESKTOP',           'EQUIPO DE ESCRITORIO',   1),
    (2, 'LAPTOP',            'COMPUTO MOVIL',          1),
    (3, 'CELULAR',           'TELEFONO MOVIL',         1),
    (4, 'IMPRESORA',         'IMPRESORA DE OFICINA',   1),
    (5, 'IMPRESORA TERMICA', 'IMPRESORA MOVIL TERMICA',1),
    (6, 'TABLETA',           'TABLETA',                1)
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
   6. CORE: usuarios y empleados demo para tablas nuevas
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
      ('ADMIN PORTAL V2',      'admin@portalv2.local',   @idRolAdmin, @idAreaTi,  1),
      ('SOPORTE HELP DESK',    'soporte@portalv2.local', @idRolHd,    @idAreaTi,  1),
      ('GENERALISTA RH',       'rh@portalv2.local',      @idRolRh,    @idAreaRh,  1),
      ('GERENTE COMERCIAL',    'gerente@portalv2.local', @idRolGer,   @idAreaCom, 1);
END
GO

IF NOT EXISTS (SELECT 1 FROM core.login WHERE Cuenta = 'admin')
BEGIN
    DECLARE @idAdmin int = (SELECT TOP 1 ID_Usuario FROM core.usuario WHERE Email = 'admin@portalv2.local');
    DECLARE @idSoporte int = (SELECT TOP 1 ID_Usuario FROM core.usuario WHERE Email = 'soporte@portalv2.local');
    DECLARE @idRh int = (SELECT TOP 1 ID_Usuario FROM core.usuario WHERE Email = 'rh@portalv2.local');
    DECLARE @idGer int = (SELECT TOP 1 ID_Usuario FROM core.usuario WHERE Email = 'gerente@portalv2.local');

    INSERT INTO core.[login] (Cuenta, PasswordHash, ID_Usuario, Activo, DebeCambiarPassword)
    VALUES
      ('admin',   '$2y$10$4i8pf3s3.ZH4mH2.OrAR0euC8uBL9wrOJ1YT7QA/widtZIO0pXzgm', @idAdmin,   1, 0),
      ('soporte', '$2y$10$4i8pf3s3.ZH4mH2.OrAR0euC8uBL9wrOJ1YT7QA/widtZIO0pXzgm', @idSoporte, 1, 0),
      ('rh',      '$2y$10$4i8pf3s3.ZH4mH2.OrAR0euC8uBL9wrOJ1YT7QA/widtZIO0pXzgm', @idRh,      1, 0),
      ('gerente', '$2y$10$4i8pf3s3.ZH4mH2.OrAR0euC8uBL9wrOJ1YT7QA/widtZIO0pXzgm', @idGer,     1, 0);
END
GO

IF NOT EXISTS (SELECT 1 FROM core.empleado WHERE Numero_Empleado IN (90001,90002,90003,90004))
BEGIN
    DECLARE @idSucAdm int = (SELECT TOP 1 ID_Sucursal FROM core.sucursal WHERE Nombre = 'ADMINISTRACION');
    DECLARE @idPuestoSis int = (SELECT TOP 1 ID_Puesto FROM core.puesto WHERE Descripcion = 'ANALISTA SISTEMAS');
    DECLARE @idPuestoSup int = (SELECT TOP 1 ID_Puesto FROM core.puesto WHERE Descripcion = 'JEFE DE SOPORTE');
    DECLARE @idPuestoRh int = (SELECT TOP 1 ID_Puesto FROM core.puesto WHERE Descripcion = 'GENERALISTA RRHH');
    DECLARE @idPuestoGer int = (SELECT TOP 1 ID_Puesto FROM core.puesto WHERE Descripcion = 'GERENTE DE SUCURSAL');
    DECLARE @idAreaTi2 int = (SELECT TOP 1 ID_Area FROM core.area WHERE Nombre = 'TECNOLOGIAS');
    DECLARE @idAreaRh2 int = (SELECT TOP 1 ID_Area FROM core.area WHERE Nombre = 'RECURSOS HUMANOS');
    DECLARE @idAreaCom2 int = (SELECT TOP 1 ID_Area FROM core.area WHERE Nombre = 'COMERCIAL');

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
    WHERE u.Email IN ('admin@portalv2.local', 'soporte@portalv2.local', 'rh@portalv2.local', 'gerente@portalv2.local')
) AS src
ON tgt.ID_Usuario = src.ID_Usuario
WHEN MATCHED THEN
    UPDATE SET Numero_Empleado = src.Numero_Empleado, Activo = 1
WHEN NOT MATCHED THEN
    INSERT (ID_Usuario, Numero_Empleado, Activo)
    VALUES (src.ID_Usuario, src.Numero_Empleado, 1);
GO

/* ============================================================
   7. CAT / HD: catálogos base heredados y adaptados
============================================================ */
MERGE cat.sistema AS tgt
USING (VALUES
    (9, 'SAP',                'Sistema ERP (SAP)',                      1),
    (9, 'App Calidad',        'Aplicacion de calidad',                  1),
    (9, 'App Movil',          'Aplicacion o portal de ventas',          1),
    (9, 'Red',                'Conexion de red e interrupciones',       1),
    (9, 'Equipo de Computo',  'Perifericos, hardware y software',       1),
    (9, 'TRESS',              'Sistema de nomina',                      1),
    (9, 'Office',             'Aplicaciones de oficina',                1),
    (9, 'Otros',              'Cualquier otro sistema',                 1),
    (9, 'CCTV',               'Problemas relacionados con CCTV',        1),
    (9, 'Sistema de gestion', 'Portal de incidencias de tecnologias',   1),
    (9, 'Dashboard',          'Portal de indicadores operativos',       1),
    (9, 'Suministro electrico','Incidencias de energia',                1),
    (9, 'APP',                'Programas instalados en equipo',         1)
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
    ('Nuevo',       1),
    ('Asignado',    2),
    ('En proceso',  3),
    ('En espera',   4),
    ('Resuelto',    5),
    ('Cerrado',     6),
    ('Cancelado',   7)
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

IF EXISTS (SELECT 1 FROM sys.tables t JOIN sys.schemas s ON s.schema_id=t.schema_id WHERE s.name='hd' AND t.name='tipo_error')
BEGIN
    MERGE hd.tipo_error AS tgt
    USING (VALUES
        ('Error de sistema',    'Fallo en aplicacion o modulo', 1),
        ('Error de usuario',    'Operacion incorrecta del usuario', 1),
        ('Error de red',        'Conectividad o VPN', 1),
        ('Error de hardware',   'Equipo fisico danado', 1),
        ('Error de datos',      'Datos incorrectos o corruptos', 1),
        ('Solicitud de acceso', 'Alta de usuario o permiso', 1),
        ('Instalacion',         'Instalacion o configuracion de software', 1),
        ('Consulta',            'Pregunta o duda funcional', 1),
        ('Otro',                'No clasificado', 1)
    ) AS src (Nombre, Descripcion, Activo)
    ON tgt.Nombre = src.Nombre
    WHEN MATCHED THEN
        UPDATE SET Descripcion = src.Descripcion, Activo = src.Activo
    WHEN NOT MATCHED THEN
        INSERT (Nombre, Descripcion, Activo) VALUES (src.Nombre, src.Descripcion, src.Activo);
END
GO

IF NOT EXISTS (SELECT 1 FROM hd.error)
BEGIN
    DECLARE @teSistema int = (SELECT TOP 1 ID_TipoError FROM hd.tipo_error WHERE Nombre = 'Error de sistema');
    DECLARE @teAcceso  int = (SELECT TOP 1 ID_TipoError FROM hd.tipo_error WHERE Nombre = 'Solicitud de acceso');
    DECLARE @teRed     int = (SELECT TOP 1 ID_TipoError FROM hd.tipo_error WHERE Nombre = 'Error de red');
    DECLARE @teHard    int = (SELECT TOP 1 ID_TipoError FROM hd.tipo_error WHERE Nombre = 'Error de hardware');
    DECLARE @teDatos   int = (SELECT TOP 1 ID_TipoError FROM hd.tipo_error WHERE Nombre = 'Error de datos');
    DECLARE @teInst    int = (SELECT TOP 1 ID_TipoError FROM hd.tipo_error WHERE Nombre = 'Instalacion');
    DECLARE @teConsulta int = (SELECT TOP 1 ID_TipoError FROM hd.tipo_error WHERE Nombre = 'Consulta');
    DECLARE @teOtro int = (SELECT TOP 1 ID_TipoError FROM hd.tipo_error WHERE Nombre = 'Otro');

    INSERT INTO hd.error (Descripcion, Tipo, Activo)
    VALUES
      ('No puedo iniciar sesion',           @teSistema,  1),
      ('No tengo acceso al modulo',         @teAcceso,   1),
      ('Sin conexion a internet / VPN',     @teRed,      1),
      ('La impresora no imprime',           @teHard,     1),
      ('Datos incorrectos en el sistema',   @teDatos,    1),
      ('Necesito instalar un programa',     @teInst,     1),
      ('Duda sobre como usar el sistema',   @teConsulta, 1),
      ('Otro problema no listado',          @teOtro,     1);
END
GO

IF NOT EXISTS (SELECT 1 FROM hd.sla)
BEGIN
    INSERT INTO hd.sla (Nombre, ID_Area, Prioridad, HorasRespuesta, HorasResolucion, Activo)
    VALUES
      ('Critico global', NULL, 'CRITICA', 1, 4, 1),
      ('Alto global',    NULL, 'ALTA',    2, 8, 1),
      ('Media global',   NULL, 'MEDIA',   4, 24,1),
      ('Baja global',    NULL, 'BAJA',    8, 48,1);
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
    DECLARE @gSal int = (SELECT TOP 1 IdGrupoArticulo FROM cat.grupo_articulo WHERE Nombre = 'Salchichas');
    DECLARE @gJam int = (SELECT TOP 1 IdGrupoArticulo FROM cat.grupo_articulo WHERE Nombre = 'Jamon');
    DECLARE @gBol int = (SELECT TOP 1 IdGrupoArticulo FROM cat.grupo_articulo WHERE Nombre = 'Bolonia');
    DECLARE @gLom int = (SELECT TOP 1 IdGrupoArticulo FROM cat.grupo_articulo WHERE Nombre = 'Lomo');
    DECLARE @gQue int = (SELECT TOP 1 IdGrupoArticulo FROM cat.grupo_articulo WHERE Nombre = 'Queso');
    DECLARE @gMan int = (SELECT TOP 1 IdGrupoArticulo FROM cat.grupo_articulo WHERE Nombre = 'Manteca');

    INSERT INTO cat.articulo (Nombre, NombreCorto, IdTipoArticulo, Peso, IdGrupoArticulo, Activo)
    VALUES
      ('SALCHICHA VIENA',     'SALV',   @tipoPz, 0.250, @gSal, 1),
      ('JAMON COCIDO',        'JAMC',   @tipoPz, 0.200, @gJam, 1),
      ('BOLONIA REGULAR',     'BOLR',   @tipoPz, 0.500, @gBol, 1),
      ('LOMO DE CERDO',       'LOMC',   @tipoPz, 0.300, @gLom, 1),
      ('QUESO MANCHEGO',      'QMAN',   @tipoKg, 1.000, @gQue, 1),
      ('MANTECA TRADICIONAL', 'MANT',   @tipoKg, 1.000, @gMan, 1);
END
GO

/* ============================================================
   9. PED: catálogos heredados de V1, sin pedidos ni detalle
============================================================ */
IF NOT EXISTS (SELECT 1 FROM ped.capacidaduv)
BEGIN
    INSERT INTO ped.capacidaduv (Nombre, Descripcion, CapacidadMinima, CapacidadMaxima, activo)
    VALUES
      ('RUTA CHICA',  'Hasta 50 clientes',       1,   50,  1),
      ('RUTA MEDIA',  'De 51 a 100 clientes',    51,  100, 1),
      ('RUTA GRANDE', 'Mas de 100 clientes',     101, 300, 1),
      ('ALMACEN',     'Sin limite operativo',    NULL,NULL,1);
END
GO

MERGE ped.tipounidad AS tgt
USING (VALUES
    ('VENDEDOR TRADICIONAL',  'VENDEDOR DE LINEA TRADICIONAL', 1),
    ('VENDEDOR MODERNO',      'VENDEDOR DE LINEA MODERNO',     1),
    ('SUPERVISOR TRADICIONAL','SUPERVISOR DE LINEA TRADICIONAL',1),
    ('SUPERVISOR MODERNO',    'SUPERVISOR DE LINEA MODERNO',   1),
    ('ALMACEN',               'ALMACEN DE SUCURSAL',           1),
    ('ADMINISTRADOR',         'ADMINISTRADOR',                 1),
    ('GERENTE SUCURSAL',      'GERENTES DE SUCURSAL',          1)
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
    INSERT (Nombre, Descripcion, activo, Orden) VALUES (src.Nombre, src.Descripcion, src.activo, src.Orden);
GO

IF NOT EXISTS (SELECT 1 FROM ped.unidadoperacional)
BEGIN
    DECLARE @idUserAdmin int = (SELECT TOP 1 ID_Usuario FROM core.usuario WHERE Email = 'admin@portalv2.local');
    DECLARE @idUserGer   int = (SELECT TOP 1 ID_Usuario FROM core.usuario WHERE Email = 'gerente@portalv2.local');
    DECLARE @idSucTj int = (SELECT TOP 1 ID_Sucursal FROM core.sucursal WHERE Nombre = 'TIJUANA');
    DECLARE @idCapAlm int = (SELECT TOP 1 IdCapacidadUV FROM ped.capacidaduv WHERE Nombre = 'ALMACEN');
    DECLARE @idCapMed int = (SELECT TOP 1 IdCapacidadUV FROM ped.capacidaduv WHERE Nombre = 'RUTA MEDIA');
    DECLARE @idTipoAlm int = (SELECT TOP 1 IdTipoUnidad FROM ped.tipounidad WHERE Nombre = 'ALMACEN');
    DECLARE @idTipoSup int = (SELECT TOP 1 IdTipoUnidad FROM ped.tipounidad WHERE Nombre = 'SUPERVISOR TRADICIONAL');
    DECLARE @idTipoVen int = (SELECT TOP 1 IdTipoUnidad FROM ped.tipounidad WHERE Nombre = 'VENDEDOR TRADICIONAL');

    INSERT INTO ped.unidadoperacional (IdTipoUnidad, IdUsuario, IdSucursal, IdCapacidadUV, Nombre, Descripcion, activo)
    VALUES
      (@idTipoAlm, @idUserAdmin, @idSucTj, @idCapAlm, 'ALMACEN TIJUANA', 'Almacen demo', 1),
      (@idTipoSup, @idUserGer,   @idSucTj, @idCapAlm, 'ST101 TIJUANA',   'Supervisor demo', 1);

    DECLARE @idSupDemo int = (SELECT TOP 1 IdUnidad FROM ped.unidadoperacional WHERE Nombre = 'ST101 TIJUANA');

    INSERT INTO ped.unidadoperacional (IdTipoUnidad, IdUsuario, IdSupervisor, IdSucursal, IdCapacidadUV, Nombre, Descripcion, activo)
    VALUES
      (@idTipoVen, @idUserGer, @idSupDemo, @idSucTj, @idCapMed, 'RT101', 'Ruta demo tradicional 101', 1),
      (@idTipoVen, @idUserGer, @idSupDemo, @idSucTj, @idCapMed, 'RT102', 'Ruta demo tradicional 102', 1);
END
GO

/* ============================================================
   10. RH: catálogos y datos demo para tablas nuevas
============================================================ */
MERGE rh.fuente_reclutamiento AS tgt
USING (VALUES
    ('LinkedIn',             'Red profesional',            1),
    ('OCC Mundial',          'Portal OCC',                 1),
    ('Indeed',               'Portal Indeed',              1),
    ('Referido interno',     'Referencia de empleado',     1),
    ('Bolsa de trabajo',     'Bolsa universitaria o local',1),
    ('Agencia externa',      'Agencia de reclutamiento',   1),
    ('Candidatura espontanea','CV directo',                1),
    ('Otro',                 'Otro canal',                 1)
) AS src (Nombre, Descripcion, Activo)
ON tgt.Nombre = src.Nombre
WHEN MATCHED THEN
    UPDATE SET Descripcion = src.Descripcion, Activo = src.Activo
WHEN NOT MATCHED THEN
    INSERT (Nombre, Descripcion, Activo) VALUES (src.Nombre, src.Descripcion, src.Activo);
GO

MERGE rh.estatus_candidato AS tgt
USING (VALUES
    ('NUEVO',          'Candidato recien registrado',         1, 1),
    ('EN_REVISION',    'CV en revision por RH',               2, 1),
    ('CITADO',         'Citado a entrevista',                 3, 1),
    ('ENTREVISTADO',   'Entrevistado, pendiente resultado',   4, 1),
    ('SELECCIONADO',   'Candidato seleccionado',              5, 1),
    ('OFERTA_ENVIADA', 'Oferta laboral enviada',              6, 1),
    ('CONTRATADO',     'Candidato contratado',                7, 1),
    ('RECHAZADO',      'No paso el proceso',                  8, 1),
    ('DESCARTADO',     'Descartado por RH',                   9, 1)
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
    DECLARE @idAreaRhVac int = (SELECT TOP 1 ID_Area FROM core.area WHERE Nombre = 'RECURSOS HUMANOS');
    DECLARE @idPuestoRhVac int = (SELECT TOP 1 ID_Puesto FROM core.puesto WHERE Descripcion = 'GENERALISTA RRHH');
    DECLARE @idSucAdmVac int = (SELECT TOP 1 ID_Sucursal FROM core.sucursal WHERE Nombre = 'ADMINISTRACION');
    DECLARE @idUsrRh int = (SELECT TOP 1 ID_Usuario FROM core.usuario WHERE Email = 'rh@portalv2.local');

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
    DECLARE @idVacDemo int = (SELECT TOP 1 ID_Vacante FROM rh.vacante WHERE Folio = 'VAC-2026-001');
    DECLARE @idEstNuevo int = (SELECT TOP 1 ID_EstatusCandidato FROM rh.estatus_candidato WHERE Nombre = 'NUEVO');

    INSERT INTO rh.candidato
        (ID_Vacante, ID_EstatusCandidato, Nombre, ApellidoPaterno, ApellidoMaterno, Correo, Telefono,
         Escolaridad, Profesion, Fuente, PretensionSalarial, Observaciones, Activo)
    VALUES
        (@idVacDemo, @idEstNuevo, 'JUAN', 'PEREZ', 'LOPEZ', 'juan.perez.demo@mail.com', '6640000001',
         'LICENCIATURA', 'ADMINISTRACION', 'LinkedIn', 20000, 'Candidato demo generado', 1),
        (@idVacDemo, @idEstNuevo, 'MARIA', 'GARCIA', 'SOTO', 'maria.garcia.demo@mail.com', '6640000002',
         'LICENCIATURA', 'PSICOLOGIA', 'Referido interno', 21000, 'Candidata demo generada', 1);
END
GO

IF NOT EXISTS (SELECT 1 FROM rh.entrevista)
BEGIN
    DECLARE @idCand1 int = (SELECT TOP 1 ID_Candidato FROM rh.candidato WHERE Correo = 'juan.perez.demo@mail.com');
    DECLARE @idVac1 int = (SELECT TOP 1 ID_Vacante FROM rh.vacante WHERE Folio = 'VAC-2026-001');
    DECLARE @idUsrRhEnt int = (SELECT TOP 1 ID_Usuario FROM core.usuario WHERE Email = 'rh@portalv2.local');

    INSERT INTO rh.entrevista
        (ID_Candidato, ID_Vacante, ID_UsuarioEntrevistador, TipoEntrevista, FechaEntrevista,
         DuracionMinutos, Ubicacion, Medio, Resultado, Calificacion, Comentarios, Activo)
    VALUES
        (@idCand1, @idVac1, @idUsrRhEnt, 'RH', DATEADD(DAY, 2, SYSDATETIME()), 45, 'Sala RH', 'Presencial', 'PENDIENTE', NULL, 'Entrevista inicial demo', 1);
END
GO

IF EXISTS (SELECT 1 FROM sys.tables t JOIN sys.schemas s ON s.schema_id=t.schema_id WHERE s.name='rh' AND t.name='entrevista_evaluador')
AND NOT EXISTS (SELECT 1 FROM rh.entrevista_evaluador)
BEGIN
    DECLARE @idEntDemo int = (SELECT TOP 1 ID_Entrevista FROM rh.entrevista);
    DECLARE @idUsrRhEval int = (SELECT TOP 1 ID_Usuario FROM core.usuario WHERE Email = 'rh@portalv2.local');
    INSERT INTO rh.entrevista_evaluador (ID_Entrevista, ID_Usuario, Rol, Calificacion, Comentarios, FechaEvaluacion)
    VALUES (@idEntDemo, @idUsrRhEval, 'RH', 8.50, 'Buen perfil para continuar proceso', SYSDATETIME());
END
GO

IF EXISTS (SELECT 1 FROM sys.tables t JOIN sys.schemas s ON s.schema_id=t.schema_id WHERE s.name='rh' AND t.name='oferta_laboral')
AND NOT EXISTS (SELECT 1 FROM rh.oferta_laboral)
BEGIN
    DECLARE @idCandOferta int = (SELECT TOP 1 ID_Candidato FROM rh.candidato WHERE Correo = 'maria.garcia.demo@mail.com');
    DECLARE @idVacOferta int = (SELECT TOP 1 ID_Vacante FROM rh.vacante WHERE Folio = 'VAC-2026-001');
    INSERT INTO rh.oferta_laboral
        (ID_Candidato, ID_Vacante, SalarioOfertado, FechaVencimiento, Estatus, FechaIngreso, Activo)
    VALUES
        (@idCandOferta, @idVacOferta, 21000, DATEADD(DAY, 7, SYSDATETIME()), 'ENVIADA', DATEADD(DAY, 15, CAST(SYSDATETIME() AS date)), 1);
END
GO

IF EXISTS (SELECT 1 FROM sys.tables t JOIN sys.schemas s ON s.schema_id=t.schema_id WHERE s.name='rh' AND t.name='nota_proceso')
AND NOT EXISTS (SELECT 1 FROM rh.nota_proceso)
BEGIN
    DECLARE @idVacNota int = (SELECT TOP 1 ID_Vacante FROM rh.vacante WHERE Folio = 'VAC-2026-001');
    DECLARE @idCandNota int = (SELECT TOP 1 ID_Candidato FROM rh.candidato WHERE Correo = 'juan.perez.demo@mail.com');
    DECLARE @idUsrRhNota int = (SELECT TOP 1 ID_Usuario FROM core.usuario WHERE Email = 'rh@portalv2.local');
    INSERT INTO rh.nota_proceso (ID_Vacante, ID_Candidato, Nota, ID_Usuario)
    VALUES (@idVacNota, @idCandNota, 'Nota demo de seguimiento del proceso.', @idUsrRhNota);
END
GO

/* ============================================================
   11. CORE / COM: parametros y datos demo para tablas nuevas V2
============================================================ */
IF NOT EXISTS (SELECT 1 FROM core.parametro)
BEGIN
    INSERT INTO core.parametro (Clave, Valor, TipoDato, Modulo, Descripcion)
    VALUES
      ('LOGIN_MAX_INTENTOS',      '5',    'INT',     'core', 'Intentos fallidos antes de bloqueo'),
      ('LOGIN_BLOQUEO_MINUTOS',   '15',   'INT',     'core', 'Minutos de bloqueo'),
      ('SESION_EXPIRACION_HORAS', '8',    'INT',     'core', 'Horas de vigencia de sesion'),
      ('TOKEN_RESET_MINUTOS',     '30',   'INT',     'core', 'Minutos de vigencia token'),
      ('HD_SLA_CRITICO_HORAS',    '4',    'INT',     'hd',   'SLA critico'),
      ('HD_SLA_NORMAL_HORAS',     '24',   'INT',     'hd',   'SLA normal'),
      ('RH_DIAS_VIGENCIA_VACANTE','90',   'INT',     'rh',   'Dias maximos de vacante');
END
GO

IF NOT EXISTS (SELECT 1 FROM com.meta_mes_portada)
BEGIN
    DECLARE @idUsrAdminMeta int = (SELECT TOP 1 ID_Usuario FROM core.usuario WHERE Email = 'admin@portalv2.local');
    INSERT INTO com.meta_mes_portada (Anio, Mes, Nombre, DiasHabiles, ID_UsuarioCreo)
    VALUES (2026, 4, 'ABRIL', 22, @idUsrAdminMeta),
           (2026, 5, 'MAYO', 21, @idUsrAdminMeta);
END
GO

IF NOT EXISTS (SELECT 1 FROM com.semana)
BEGIN
    DECLARE @idMesAbr int = (SELECT TOP 1 ID_MetaMes FROM com.meta_mes_portada WHERE Anio = 2026 AND Mes = 4);
    DECLARE @idMesMay int = (SELECT TOP 1 ID_MetaMes FROM com.meta_mes_portada WHERE Anio = 2026 AND Mes = 5);
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
      (@idVol2, 'EMB', 'Embutidos', 1, 1),
      (@idVol2, 'CF',  'Carnes frias', 2, 1),
      (@idVol2, 'QSO', 'Queso', 3, 1),
      (@idVol2, 'MTK', 'Manteca', 4, 1),
      (@idCob2, 'BOL', 'Bolonia', 1, 1),
      (@idCob2, 'JAM', 'Jamon', 2, 1),
      (@idCob2, 'LOM', 'Lomo', 3, 1),
      (@idDoc2, 'CHK', 'Check list de unidades', 1, 1),
      (@idDoc2, 'LIQ', 'Liquidacion perfecta', 2, 1),
      (@idDoc2, 'MES', 'Mesa de control', 3, 1);
END
GO

PRINT 'semilla_v2_desde_v1.sql aplicado correctamente.';
GO
