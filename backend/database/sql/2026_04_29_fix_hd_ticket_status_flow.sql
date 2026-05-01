SET NOCOUNT ON;

DECLARE @ID_Abierto int = (
    SELECT TOP 1 ID_Estatus
      FROM hd.estatus
     WHERE UPPER(Nombre) = 'ABIERTO'
     ORDER BY ID_Estatus
);

DECLARE @ID_Nuevo int = (
    SELECT TOP 1 ID_Estatus
      FROM hd.estatus
     WHERE UPPER(Nombre) = 'NUEVO'
     ORDER BY ID_Estatus
);

IF @ID_Abierto IS NULL AND @ID_Nuevo IS NOT NULL
BEGIN
    UPDATE hd.estatus
       SET Nombre = 'Abierto',
           Orden = 1
     WHERE ID_Estatus = @ID_Nuevo;

    SET @ID_Abierto = @ID_Nuevo;
    SET @ID_Nuevo = NULL;
END;

IF @ID_Abierto IS NULL
BEGIN
    INSERT INTO hd.estatus (Nombre, Orden)
    VALUES ('Abierto', 1);

    SET @ID_Abierto = SCOPE_IDENTITY();
END;

IF @ID_Nuevo IS NOT NULL AND @ID_Nuevo <> @ID_Abierto
BEGIN
    UPDATE hd.ticket
       SET ID_Estatus = @ID_Abierto
     WHERE ID_Estatus = @ID_Nuevo;

    IF OBJECT_ID('hd.ticket_estatus_log', 'U') IS NOT NULL
    BEGIN
        UPDATE hd.ticket_estatus_log
           SET ID_Estatus_Anterior = @ID_Abierto
         WHERE ID_Estatus_Anterior = @ID_Nuevo;

        UPDATE hd.ticket_estatus_log
           SET ID_Estatus_Nuevo = @ID_Abierto
         WHERE ID_Estatus_Nuevo = @ID_Nuevo;
    END;

    DELETE FROM hd.estatus
     WHERE ID_Estatus = @ID_Nuevo;
END;

UPDATE hd.estatus SET Nombre = 'Abierto', Orden = 1 WHERE UPPER(Nombre) = 'ABIERTO';
UPDATE hd.estatus SET Nombre = 'En proceso', Orden = 2 WHERE UPPER(Nombre) = 'EN PROCESO';
UPDATE hd.estatus SET Nombre = 'Asignado', Orden = 3 WHERE UPPER(Nombre) = 'ASIGNADO';
UPDATE hd.estatus SET Nombre = 'En espera', Orden = 4 WHERE UPPER(Nombre) = 'EN ESPERA';
UPDATE hd.estatus SET Nombre = 'Resuelto', Orden = 5 WHERE UPPER(Nombre) = 'RESUELTO';
UPDATE hd.estatus SET Nombre = 'Cerrado', Orden = 6 WHERE UPPER(Nombre) = 'CERRADO';
UPDATE hd.estatus SET Nombre = 'Cancelado', Orden = 7 WHERE UPPER(Nombre) = 'CANCELADO';

SELECT ID_Estatus, Nombre, Orden
  FROM hd.estatus
 ORDER BY Orden, ID_Estatus;
