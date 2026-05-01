/*
    Continua despues del error:
    Msg 207 - El nombre de columna 'Orden' no es valido.

    Ejecutar en la misma instancia donde se corrio master 5.txt.
    No elimina datos.
*/

IF DB_ID(N'PortalV2') IS NULL
BEGIN
    RAISERROR('No existe la base PortalV2 en esta instancia.', 16, 1);
    RETURN;
END;
GO

USE PortalV2;
GO

IF OBJECT_ID(N'ped.estado_pedido', N'U') IS NULL
BEGIN
    RAISERROR('No existe la tabla ped.estado_pedido.', 16, 1);
    RETURN;
END;
GO

IF COL_LENGTH(N'ped.estado_pedido', N'Orden') IS NULL
BEGIN
    ALTER TABLE ped.estado_pedido
        ADD Orden int NOT NULL
            CONSTRAINT DF_ped_ep_Orden DEFAULT (99);
END;
GO

UPDATE ped.estado_pedido
SET Orden = CASE UPPER(Nombre)
    WHEN 'PENDIENTE'   THEN 1
    WHEN 'EN PROCESO'  THEN 2
    WHEN 'DESPACHADO'  THEN 3
    WHEN 'ENTREGADO'   THEN 4
    WHEN 'CANCELADO'   THEN 5
    WHEN 'AUTORIZADO'  THEN 2
    WHEN 'SURTIDO'     THEN 3
    ELSE Orden
END;
GO

SELECT 'OK' AS Resultado, 'ped.estado_pedido.Orden validada' AS Detalle;
GO
