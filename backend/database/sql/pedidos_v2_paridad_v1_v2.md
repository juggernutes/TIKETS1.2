# Validacion paridad Pedidos V1 vs V2

Fecha: 2026-04-30

Origen revisado: `C:\Users\ANALISTA BD\Downloads\db_tiket_ti(1).sql`

Servidor V2 validado: `http://172.30.11.13`

## Conteos V1 extraidos del respaldo

- `ped.estado_pedido`: 4
- `ped.capacidaduv`: 7
- `ped.tipounidad`: 7
- `cat.tipo_articulo`: 2
- `cat.linea_articulo`: 4
- `cat.grupo_articulo`: 8
- `cat.articulo`: 58
- `core.sucursal`: 8
- `core.usuario`: 211
- `ped.unidadoperacional`: 115
- `ped.pedidos`: 193
- `ped.pedido_detalle`: 364

## Conteos V2 observados por API

- `/api/ped/estados`: 4
- `/api/ped/unidades`: 4
- `/api/catalogos/articulos`: 6
- `/api/catalogos/sucursales`: 8
- `/api/catalogos/usuarios`: 4
- `/api/ped/pedidos`: 0

## Diagnostico

V2 ya tiene registros base, por lo que no se debe hacer una carga directa del respaldo V1.

La carga segura debe ser incremental:

- No eliminar registros actuales.
- No actualizar registros actuales.
- Insertar solo catálogos faltantes.
- No insertar unidades operacionales automaticamente hasta mapear usuarios reales de V1 contra usuarios V2.
- No importar pedidos historicos todavia, porque dependen de unidades, usuarios y articulos ya conciliados.

Resultado del validador ejecutado:

- `ped.capacidaduv`: V1 tiene 7, V2 tiene 4, faltan 7 por nombre. Se pueden insertar como capacidades nuevas porque no pisan las actuales.
- `ped.tipounidad`: V1 tiene 7, V2 tiene 7, faltan 0. No requiere carga.
- `ped.estado_pedido`: V1 tiene 4, V2 tiene 4. Por equivalencia no falta nada: `Capturado` de V1 equivale a `PENDIENTE` en V2.
- `cat.articulo`: V1 tiene 58, V2 tiene 6, faltan 58. Se pueden insertar como catalogo nuevo porque no coinciden por IdArticulo, Nombre ni NombreCorto.
- `ped.unidadoperacional`: no se carga automaticamente porque casi todos los usuarios V1 no existen en `core.usuario` V2 por Email; solo `ST101 TIJUANA` ya existe por nombre.

## Archivos generados

- `pedidos_v2_validar_paridad_desde_v1.sql`: consulta de validacion sin cambios en BD.
- `pedidos_v2_carga_catalogos_desde_v1.sql`: carga incremental de catalogos faltantes, sin borrar ni actualizar.

## Decision tecnica

El script de carga incluye catalogos PED/CAT desde V1: capacidades, tipos de unidad, estados, tipos/lineas/grupos/articulos.

Las unidades operacionales quedan como reporte porque los `IdUsuario` de V1 no son equivalentes a los de V2. Insertarlas sin mapear usuarios puede romper el flujo vendedor-supervisor-almacen.

Actualizacion: el script de carga ya no inserta estados de pedido. Solo reporta la equivalencia `Capturado` -> `PENDIENTE`.

Actualizacion posterior a la carga: el validador fue ajustado para calcular estados por equivalencia y para ocultar unidades que ya existen por nombre en V2.

Actualizacion de limpieza: el validador ahora muestra un resumen de unidades pendientes por tipo/sucursal/correo y deja el listado completo solo como detalle opcional. Tambien se agrego `pedidos_v2_mapeo_unidades_pendiente.sql` como plantilla para capturar el mapeo manual de usuarios V2 antes de cargar unidades operacionales.
