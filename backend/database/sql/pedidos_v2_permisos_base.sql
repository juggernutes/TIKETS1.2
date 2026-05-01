SET NOCOUNT ON;

/*
  Permisos base para Pedidos V2.
  Idempotente: puede ejecutarse mas de una vez.
*/

IF NOT EXISTS (SELECT 1 FROM core.rol WHERE Nombre = 'ALMACEN')
BEGIN
    INSERT INTO core.rol (Nombre, Activo) VALUES ('ALMACEN', 1);
END;

DECLARE @Permisos TABLE (
    Clave varchar(50) PRIMARY KEY,
    Nombre varchar(100),
    Modulo varchar(30),
    Descripcion varchar(200)
);

INSERT INTO @Permisos (Clave, Nombre, Modulo, Descripcion)
VALUES
('ped.pedidos.ver', 'Ver pedidos', 'PED', 'Acceso al modulo de pedidos.'),
('ped.pedidos.crear', 'Crear pedidos', 'PED', 'Captura pedidos desde unidad operacional.'),
('ped.pedidos.ver_propios', 'Ver pedidos propios', 'PED', 'Consulta pedidos de la unidad propia.'),
('ped.pedidos.ver_por_autorizar', 'Ver pedidos por autorizar', 'PED', 'Consulta bandeja de pedidos capturados.'),
('ped.pedidos.autorizar', 'Autorizar pedidos', 'PED', 'Autoriza pedidos dentro de su alcance.'),
('ped.pedidos.ver_por_surtir', 'Ver pedidos por surtir', 'PED', 'Consulta bandeja de pedidos autorizados.'),
('ped.pedidos.surtir', 'Surtir pedidos', 'PED', 'Surtir pedidos autorizados dentro de su alcance.'),
('ped.pedidos.cancelar', 'Cancelar pedidos', 'PED', 'Cancela pedidos dentro de su alcance.'),
('ped.pedidos.csv', 'Exportar CSV pedidos', 'PED', 'Genera CSV de surtido.'),
('ped.catalogos.ver', 'Ver catalogos pedidos', 'PED', 'Consulta catalogos de pedidos.'),
('ped.unidades.admin', 'Administrar unidades', 'PED', 'Administra unidades operacionales y cambios administrativos.');

MERGE core.permiso AS tgt
USING @Permisos AS src
    ON tgt.Clave = src.Clave
WHEN MATCHED THEN
    UPDATE SET
        tgt.Nombre = src.Nombre,
        tgt.Modulo = src.Modulo,
        tgt.Descripcion = src.Descripcion,
        tgt.Activo = 1
WHEN NOT MATCHED THEN
    INSERT (Clave, Nombre, Modulo, Descripcion, Activo)
    VALUES (src.Clave, src.Nombre, src.Modulo, src.Descripcion, 1);

DECLARE @Asignaciones TABLE (
    Rol varchar(50),
    Clave varchar(50),
    PRIMARY KEY (Rol, Clave)
);

INSERT INTO @Asignaciones (Rol, Clave)
SELECT 'ADMIN', Clave FROM @Permisos;

INSERT INTO @Asignaciones (Rol, Clave)
VALUES
('VENDEDOR', 'ped.pedidos.ver'),
('VENDEDOR', 'ped.pedidos.crear'),
('VENDEDOR', 'ped.pedidos.ver_propios'),
('VENDEDOR', 'ped.catalogos.ver'),
('SUPERVISOR', 'ped.pedidos.ver'),
('SUPERVISOR', 'ped.pedidos.ver_por_autorizar'),
('SUPERVISOR', 'ped.pedidos.autorizar'),
('SUPERVISOR', 'ped.pedidos.cancelar'),
('SUPERVISOR', 'ped.catalogos.ver'),
('GERENTE_SUCURSAL', 'ped.pedidos.ver'),
('GERENTE_SUCURSAL', 'ped.pedidos.ver_por_autorizar'),
('GERENTE_SUCURSAL', 'ped.pedidos.autorizar'),
('GERENTE_SUCURSAL', 'ped.pedidos.cancelar'),
('GERENTE_SUCURSAL', 'ped.catalogos.ver'),
('ALMACEN', 'ped.pedidos.ver'),
('ALMACEN', 'ped.pedidos.ver_por_surtir'),
('ALMACEN', 'ped.pedidos.surtir'),
('ALMACEN', 'ped.pedidos.csv'),
('ALMACEN', 'ped.catalogos.ver');

INSERT INTO @Asignaciones (Rol, Clave)
SELECT 'ADMINISTRADOR', Clave
FROM @Permisos
WHERE EXISTS (SELECT 1 FROM core.rol WHERE Nombre = 'ADMINISTRADOR');

INSERT INTO @Asignaciones (Rol, Clave)
VALUES
('GERENTE', 'ped.pedidos.ver'),
('GERENTE', 'ped.pedidos.ver_por_autorizar'),
('GERENTE', 'ped.pedidos.autorizar'),
('GERENTE', 'ped.pedidos.cancelar'),
('GERENTE', 'ped.catalogos.ver');

MERGE core.rol_permiso AS tgt
USING (
    SELECT r.ID_Rol, p.ID_Permiso
    FROM @Asignaciones a
    JOIN core.rol r ON r.Nombre = a.Rol
    JOIN core.permiso p ON p.Clave = a.Clave
) AS src
    ON tgt.ID_Rol = src.ID_Rol AND tgt.ID_Permiso = src.ID_Permiso
WHEN MATCHED THEN
    UPDATE SET tgt.Activo = 1
WHEN NOT MATCHED THEN
    INSERT (ID_Rol, ID_Permiso, Activo, FechaCreacion)
    VALUES (src.ID_Rol, src.ID_Permiso, 1, SYSDATETIMEOFFSET());

SELECT r.Nombre AS Rol, p.Clave
FROM core.rol_permiso rp
JOIN core.rol r ON r.ID_Rol = rp.ID_Rol
JOIN core.permiso p ON p.ID_Permiso = rp.ID_Permiso
WHERE p.Modulo = 'PED' AND rp.Activo = 1
ORDER BY r.Nombre, p.Clave;
