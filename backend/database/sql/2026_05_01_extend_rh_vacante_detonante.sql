/* Portal V2 - RH: detonantes de vacante
   Idempotente. Agrega metadatos para identificar por que se abre una vacante.
*/

IF COL_LENGTH('rh.vacante', 'DetonanteTipo') IS NULL
BEGIN
    ALTER TABLE rh.vacante ADD DetonanteTipo varchar(30) NULL;
END;

IF COL_LENGTH('rh.vacante', 'DetonanteEmpleadoNumero') IS NULL
BEGIN
    ALTER TABLE rh.vacante ADD DetonanteEmpleadoNumero int NULL;
END;

IF COL_LENGTH('rh.vacante', 'DetonantePuestoNombre') IS NULL
BEGIN
    ALTER TABLE rh.vacante ADD DetonantePuestoNombre varchar(150) NULL;
END;

IF COL_LENGTH('rh.vacante', 'DetonanteComentario') IS NULL
BEGIN
    ALTER TABLE rh.vacante ADD DetonanteComentario varchar(500) NULL;
END;

GO

IF NOT EXISTS (
    SELECT 1
    FROM sys.check_constraints
    WHERE name = 'CK_rh_vacante_DetonanteTipo'
      AND parent_object_id = OBJECT_ID('rh.vacante')
)
BEGIN
    ALTER TABLE rh.vacante
    ADD CONSTRAINT CK_rh_vacante_DetonanteTipo
    CHECK (
        DetonanteTipo IS NULL
        OR DetonanteTipo IN ('BAJA_EMPLEADO', 'CREACION_PUESTO', 'NUEVA_POSICION')
    );
END;
