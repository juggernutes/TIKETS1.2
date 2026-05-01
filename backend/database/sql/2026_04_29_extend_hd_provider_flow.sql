IF COL_LENGTH('hd.ticket', 'ID_Proveedor') IS NULL
    ALTER TABLE hd.ticket ADD ID_Proveedor int NULL;

IF COL_LENGTH('hd.ticket', 'FechaEnvioProveedor') IS NULL
    ALTER TABLE hd.ticket ADD FechaEnvioProveedor datetime2(0) NULL;

IF COL_LENGTH('hd.ticket', 'SeguimientoProveedor') IS NULL
    ALTER TABLE hd.ticket ADD SeguimientoProveedor nvarchar(max) NULL;

IF COL_LENGTH('hd.ticket', 'ID_Proveedor') IS NOT NULL
   AND OBJECT_ID('hd.FK_hd_tkt_proveedor', 'F') IS NULL
    ALTER TABLE hd.ticket ADD CONSTRAINT FK_hd_tkt_proveedor
    FOREIGN KEY (ID_Proveedor) REFERENCES core.proveedor(ID_Proveedor);
