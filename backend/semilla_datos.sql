/* ============================================================
   PORTAL V2 — semilla_datos.sql
   Migración MySQL → SQL Server
   Tablas: hd.tipo_error (nueva), core.area, core.sucursal,
           core.puesto, core.tipo_equipo, hd.estatus,
           hd.solucion, hd.[error], cat.sistema, core.empleado
   Fecha: 2026-04-23
   ============================================================ */

USE PortalV2;
GO

/* ============================================================
   0. hd.tipo_error  (tabla faltante en master_v3, referenciada
      por las rutas /tipo-error y /errores-hd)
   ============================================================ */
IF NOT EXISTS (
    SELECT 1 FROM sys.tables t
    JOIN sys.schemas s ON t.schema_id = s.schema_id
    WHERE s.name = 'hd' AND t.name = 'tipo_error'
)
BEGIN
    CREATE TABLE hd.tipo_error (
        ID_TipoError int          IDENTITY(1,1) NOT NULL PRIMARY KEY,
        Nombre       varchar(50)  NOT NULL,
        Activo       bit          NOT NULL CONSTRAINT DF_hd_te_Activo DEFAULT (1),
        CONSTRAINT UQ_hd_tipo_error_Nombre UNIQUE (Nombre)
    );
END
GO

SET IDENTITY_INSERT hd.tipo_error ON;
INSERT INTO hd.tipo_error (ID_TipoError, Nombre, Activo) VALUES
(1, 'SOFTWARE / REDES',        1),
(2, 'HARDWARE / DISPOSITIVOS', 1),
(3, 'PERIFÉRICOS / OTROS',     1);
SET IDENTITY_INSERT hd.tipo_error OFF;
GO

/* ============================================================
   1. core.area
   ============================================================ */
SET IDENTITY_INSERT core.area ON;
INSERT INTO core.area (ID_Area, Nombre, Serie, Activo) VALUES
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
(54, 'DOCUMENTACION',                            'DOC', 1);
SET IDENTITY_INSERT core.area OFF;
GO

/* ============================================================
   2. core.sucursal  (sin IDENTITY — PK manual)
   ============================================================ */
INSERT INTO core.sucursal (ID_Sucursal, Nombre, Ciudad, Activo) VALUES
(1, 'MEXICALI',            'MEXICALI',   1),
(2, 'ADMINISTRACION',      'TIJUANA',    1),
(3, 'HERMOSILLO',          'HERMOSILLO', 1),
(4, 'TIJUANA',             'TIJUANA',    1),
(5, 'ENSENADA',            'ENSENADA',   1),
(6, 'OBREGON',             'OBREGON',    1),
(7, 'CENTRO DISTRIBUCION', 'TIJUANA',    1),
(8, 'PLANTA',              'TIJUANA',    1);
GO

/* ============================================================
   3. core.puesto
   Campos adicionales sin equivalente en MySQL se llenan con
   valores neutros. El usuario puede afinarlos después.
   Clave: P001-P080 | Nivel: 1 | Categoria: A |
   Segmento: GEN   | Responsabilidad: N
   ============================================================ */
SET IDENTITY_INSERT core.puesto ON;
INSERT INTO core.puesto (ID_Puesto, Clave, Descripcion, Nivel, Categoria, Segmento, Responsabilidad, Activo) VALUES
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
(80, 'P080', 'AUXILIAR MANTENIMIENTO T3',             1, 'A', 'GEN', 'N', 1);
SET IDENTITY_INSERT core.puesto OFF;
GO

/* ============================================================
   4. core.tipo_equipo
   ============================================================ */
SET IDENTITY_INSERT core.tipo_equipo ON;
INSERT INTO core.tipo_equipo (ID_TipoEquipo, Nombre, Descripcion, Activo) VALUES
(1, 'DESKTOP',          'EQUIPO DE ESCRITORIO',     1),
(2, 'LAPTOP',           'COMPUTO MOVIL',             1),
(3, 'CELULAR',          'TELEFONO MOVIL',            1),
(4, 'IMPRESORA',        'IMPRESORA DE OFICINA',      1),
(5, 'IMPRESORA TERMICA','IMPRESORA MOVIL TERMICA',   1),
(6, 'TABLETA',          'TABLETA',                   1);
SET IDENTITY_INSERT core.tipo_equipo OFF;
GO

/* ============================================================
   5. hd.estatus
   ============================================================ */
SET IDENTITY_INSERT hd.estatus ON;
INSERT INTO hd.estatus (ID_Estatus, Nombre, Orden) VALUES
(1, 'ABIERTO',     1),
(2, 'EN PROCESO',  2),
(3, 'RESUELTO',    3),
(4, 'CERRADO',     4);
SET IDENTITY_INSERT hd.estatus OFF;
GO

/* ============================================================
   6. hd.solucion
   ============================================================ */
SET IDENTITY_INSERT hd.solucion ON;
INSERT INTO hd.solucion (ID_Solucion, Descripcion, Activo) VALUES
(1,  'INSTALACION',              1),
(2,  'MANTENIMIENTO',            1),
(3,  'ACTUALIZACION',            1),
(4,  'SUSTITUCION',              1),
(5,  'CONFIGURACIÓN',            1),
(6,  'DESBLOQUEO / RECUPERACIÓN',1),
(7,  'DIAGNÓSTICO',              1),
(8,  'CONECTIVIDAD',             1),
(9,  'INCIDENTES / SEGURIDAD',   1),
(10, 'OTROS',                    1),
(11, 'CANCELACION DE USUARIO',   1),
(12, 'EN PROCESO',               1);
SET IDENTITY_INSERT hd.solucion OFF;
GO

/* ============================================================
   7. hd.[error]
   Tipo: 1=SOFTWARE/REDES  2=HARDWARE/DISPOSITIVOS
         3=PERIFÉRICOS/OTROS
   ============================================================ */
SET IDENTITY_INSERT hd.[error] ON;
INSERT INTO hd.[error] (ID_Error, Descripcion, Tipo, Activo) VALUES
(1,  'APP ESCRITORIO',                          1, 1),
(2,  'APP MÓVIL',                               1, 1),
(3,  'AUDIFONOS',                               3, 1),
(4,  'CCTV',                                    3, 1),
(5,  'CELULAR',                                 2, 1),
(6,  'CHECADOR BIOMÉTRICO',                     3, 1),
(7,  'CORREO - OFFIMÁTICA',                     3, 1),
(8,  'DASHBOARD',                               3, 1),
(9,  'EQUIPO DE CÓMPUTO',                       2, 1),
(10, 'FUENTE DE PODER',                         3, 1),
(11, 'IMPRESIÓN',                               2, 1),
(12, 'IMPRESORA',                               3, 0),
(13, 'INTERFACE',                               1, 1),
(14, 'MODEM - ENLACE',                          1, 1),
(15, 'MONITOR',                                 3, 1),
(16, 'MOUSE',                                   3, 1),
(17, 'PÁGINA WEB',                              3, 1),
(18, 'PUNTO DE ACCESO AP',                      1, 1),
(19, 'RED',                                     1, 1),
(20, 'ROUTER',                                  1, 1),
(21, 'SAP - BEAS',                              1, 1),
(22, 'SAP - B1',                                1, 1),
(23, 'SERVIDOR',                                1, 1),
(24, 'TECLADO',                                 3, 1),
(25, 'TELEFONÍA',                               2, 1),
(26, 'TRESS',                                   1, 1),
(27, 'UPS',                                     2, 1),
(28, 'WINDOWS',                                 3, 1),
(29, 'USUARIO',                                 3, 1),
(30, 'DRIVERS',                                 3, 1),
(39, 'ERROR DE LOTE (TIKET ROJO)',               2, 1),
(40, 'MANTENIMIENTO',                           3, 1),
(41, 'INSTALACION DE SOFTWARE',                  2, 1),
(42, 'INDEFINIDO',                              1, 1),
(43, 'SISTEMA DE GESTION',                      2, 1),
(44, 'CANCELACION REMISION',                    1, 1),
(45, 'ALTA EMPLEADO',                           1, 1),
(46, 'CAPACITACION',                            1, 1),
(47, 'COORDENADAS',                             1, 1),
(48, 'XLM',                                     1, 1),
(49, 'PLANTILLAS',                              1, 1),
(50, 'VIATICOS',                                1, 1),
(51, 'ADDENDA',                                 1, 1),
(52, 'ADAPTADOR DE VIDEO',                      2, 1),
(53, 'CABLEADO ESTRUCTURADO',                   1, 1),
(54, 'SERVICIO PROPORCIONADO POR UN EXTERNO',   1, 1),
(55, 'NOTAS CRÉDITO AUTOMÁTICAS',               1, 1),
(56, 'ALTA',                                    1, 1);
SET IDENTITY_INSERT hd.[error] OFF;
GO

/* ============================================================
   8. cat.sistema
   ID_Area = 9 (TECNOLOGIAS) para todos
   ============================================================ */
SET IDENTITY_INSERT cat.sistema ON;
INSERT INTO cat.sistema (ID_Sistema, ID_Area, Nombre, Descripcion, Activo) VALUES
(1,  9, 'SAP',               'Sistema ERP (SAP)',                             1),
(2,  9, 'App Calidad',        'Aplicación de calidad',                        1),
(3,  9, 'App Móvil',          'Aplicación o portal de ventas',                1),
(4,  9, 'Red',                'Conexión de red e interrupciones',             1),
(5,  9, 'Equipo de Cómputo',  'Periféricos, hardware y software',             1),
(6,  9, 'TRESS',              'Sistema de nómina',                            1),
(7,  9, 'Office',             'Aplicaciones de oficina (Word, Excel, etc.)',  1),
(8,  9, 'Otros',              'Cualquier otro sistema',                       1),
(9,  9, 'CCTV',               'Problemas relacionados con CCTV',              1),
(10, 9, 'Sistema de gestion', 'Portal de incidencias de tecnologias',         1),
(11, 9, 'Dashboard',          'Portal de indicadores operativos',             1),
(12, 9, 'Prueba',             NULL,                                           0),
(13, 9, 'Suministro eléctrico','',                                            1),
(14, 9, 'APP',                'Programas que se instalan en equipo.',         1);
SET IDENTITY_INSERT cat.sistema OFF;
GO

/* ============================================================
   9. core.empleado  (sin IDENTITY — Numero_Empleado es PK manual)

   Notas de limpieza aplicadas:
   - Correos con '---...' o sin '@' → NULL
   - Correos duplicados en activos: se conserva el más reciente,
     los anteriores quedan en NULL
   - Empleados inactivos (Activo=0): correo → NULL
   ============================================================ */
INSERT INTO core.empleado
    (Numero_Empleado, Nombre, Correo, Extension, Telefono,
     UsuarioAnyDesk, ClaveAnyDesk, ID_Sucursal, ID_Puesto, ID_Area, Activo)
VALUES
(11,   'CATALINA ORTEGA MUÑOZ',                  NULL,                                            NULL,   NULL, NULL, NULL, 8,  65, 16, 1),
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
(1716, 'KARLA EVELYN NUÑEZ PLANCARTE',           'k.nunez@empacadorarosarito.com.mx',             NULL,   NULL, NULL, NULL, 2,  28, 14, 1),
(1730, 'YESMIN ROCIO BERNAL ROMERO',             'r.bernal@empacadorarosarito.com.mx',            NULL,   NULL, NULL, NULL, 2,  24, 12, 1),
(1837, 'LUIS ALBERTO FIMBRES VILLASEÑOR',        'l.fimbres@empacadorarosarito.com.mx',           '1125', NULL, NULL, NULL, 8,  58, 10, 1),
(1849, 'JOSE ALFREDO MUNGUIA VALENZUELA',        NULL,                                            NULL,   NULL, NULL, NULL, 3,  53,  3, 0),
(1864, 'RAFAEL ERNESTO BARRERA BERNAL',          'apt.her@empacadorarosarito.com.mx',             NULL,   NULL, NULL, NULL, 3,   2,  3, 1),
(1945, 'ANAYELI MONTAÑO FLORES',                 'cxc.hermosillo@empacadorarosarito.com.mx',      NULL,   NULL, NULL, NULL, 3,  26,  3, 1),
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
(3319, 'RAFAEL DANIEL MUÑOZ CONTRERAS',          NULL,                                            NULL,   NULL, NULL, NULL, 8,  63, 52, 1),
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
(4828, 'ULISES AGUSTIN ZAMORA BAÑUELOS',         NULL,                                            NULL,   NULL, NULL, NULL, 8,  67, 44, 1),
(4840, 'JUAN CARLOS GARCIA HERNANDEZ',           NULL,                                            NULL,   NULL, NULL, NULL, 8,  75, 51, 1),
(4871, 'SERGIO CATAÑO GOMEZ',                    NULL,                                            NULL,   NULL, NULL, NULL, 2,  63,  2, 1),
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
(5080, 'ABIGAIL CASTAÑEDA MARTINEZ',             'rh.mexicali@empacadorarosarito.com.mx',         NULL,   NULL, NULL, NULL, 1,  35,  1, 1),
(5088, 'GUILLERMO SUSUNAGA RODRIGUEZ',           NULL,                                            NULL,   NULL, NULL, NULL, 8,  75, 51, 1),
(5095, 'DALAI ESPINOZA GUTIERREZ',               'inspeccion@empacadorarosarito.com.mx',          '1136', NULL, NULL, NULL, 8,  59, 20, 1),
(5119, 'FRANCISCO RODRIGUEZ SALAZAR',            'almacenplantasecos@empacadorarosarito.com.mx',  NULL,   NULL, NULL, NULL, 2,   2,  2, 1),
(5120, 'JESUS BENJAMIN BORQUEZ VERDUGO',         'apt.obregon@empacadorarosarito.com.mx',         NULL,   NULL, NULL, NULL, 6,   3,  6, 1),
(5121, 'JOSE LUIS GUDIÑO PICHARDO',              NULL,                                            NULL,   NULL, NULL, NULL, 6,  53,  6, 1),
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
(5237, 'ARIANA CASTAÑEDA DEL CASTILLO',          NULL,                                            NULL,   NULL, NULL, NULL, 8,  75, 51, 1),
(5255, 'AIDA ARACELI OCHOA MARTINEZ',            'a.ochoa@empacadorarosarito.com.mx',             NULL,   NULL, NULL, NULL, 2,  39, 10, 1),
(5270, 'ANGEL ADOLFO ACUÑA MARTINEZ',            'apt.mexicali@empacadorarosarito.com.mx',        NULL,   NULL, NULL, NULL, 1,   1,  1, 1),
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
(5407, 'EMANUEL SERGIO BARTOLON MUÑOZ',          'cd.calidad@empacadorarosarito.com.mx',          NULL,   NULL, NULL, NULL, 4,  41,  4, 1),
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
(5641, 'ZADYURY ARIADNA CASTAÑEDA BARRAZA',      NULL,                                            NULL,   NULL, NULL, NULL, 5,  20,  5, 0),
(5642, 'GUSTAVO MANGOL GONZALEZ',                NULL,                                            NULL,   NULL, NULL, NULL, 4,  52,  4, 0),
(5646, 'JONATHAN CASTRO CHAVEZ',                 NULL,                                            NULL,   NULL, NULL, NULL, 2,  15, 12, 0),
(5651, 'JESSICA ANAHI NORIEGA YOC',              NULL,                                            NULL,   NULL, NULL, NULL, 8,  75, 51, 1),
(5660, 'EDGAR RICARDO GOMEZ LEDESMA',            NULL,                                            NULL,   NULL, NULL, NULL, 1,  52,  1, 0),
(5661, 'MONICA RAMIREZ NAVARRO',                 'm.ramirez@empacadorarosarito.com.mx',           NULL,   NULL, NULL, NULL, 2,  47, 14, 1),
(5667, 'KARLA ANABEL PEÑA ZEPEDA',               NULL,                                            NULL,   NULL, NULL, NULL, 1,  52,  1, 1),
(5669, 'ANGELICA MARIA GOMEZ MUÑIZ',             NULL,                                            NULL,   NULL, NULL, NULL, 4,  35,  4, 1),
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
(5821, 'MARTHA LIZETH QUIÑONEZ MARTINEZ',        NULL,                                            NULL,   NULL, NULL, NULL, 8,  22, 16, 1),
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
(5852, 'ITZEL CRUZ RODRIGUEZ',                   NULL,                                            NULL,   NULL, NULL, NULL, 8,  75, 51, 1);
GO

PRINT 'Semilla de datos cargada correctamente.';
GO
