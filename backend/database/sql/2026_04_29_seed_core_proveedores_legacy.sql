SET IDENTITY_INSERT core.proveedor ON;

IF NOT EXISTS (
    SELECT 1 FROM core.proveedor
    WHERE ID_Proveedor = 205 OR Correo = 'sapbo@empacadorarosarito.com.mx'
)
BEGIN
    INSERT INTO core.proveedor (ID_Proveedor, Nombre, Correo, Telefono, ID_Usuario, Activo)
    VALUES (205, 'OSCAR PEREZ MUÑOZ', 'sapbo@empacadorarosarito.com.mx', '2292193202', NULL, 1);
END;

IF NOT EXISTS (
    SELECT 1 FROM core.proveedor
    WHERE ID_Proveedor = 206 OR Correo = 'rsierra@sedis.app'
)
BEGIN
    INSERT INTO core.proveedor (ID_Proveedor, Nombre, Correo, Telefono, ID_Usuario, Activo)
    VALUES (206, 'RAMON SIERRA VEGA', 'rsierra@sedis.app', '6641215443', NULL, 1);
END;

SET IDENTITY_INSERT core.proveedor OFF;

DBCC CHECKIDENT ('core.proveedor', RESEED, 206);
