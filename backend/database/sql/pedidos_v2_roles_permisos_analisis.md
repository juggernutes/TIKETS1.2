# Analisis V1 a V2 - Roles y permisos para Pedidos

Fecha: 2026-04-30

## Hallazgos en V1

La version anterior no usa una tabla formal de permisos para pedidos. El flujo se decide por el valor de `$_SESSION['rol']`, asignado al iniciar sesion.

Roles relevantes:

- VENDEDOR: entra directo a `views/pedido.php`.
- SUPERVISOR: entra a `views/dashboardPedidos.php`.
- GERENTE: entra a `views/dashboardPedidos.php`.
- ALMACEN: entra a `views/dashboardPedidos.php`.
- ADMINISTRADOR/SOPORTE/EMPLEADO/PROVEEDOR/JEFE DE AREA: flujo principal de Help Desk.

Comportamiento por rol en pedidos:

- VENDEDOR captura pedidos desde su unidad operacional.
- SUPERVISOR ve pedidos capturados donde su unidad aparece como supervisor, puede editar cantidades autorizadas, autorizar y cancelar.
- GERENTE ve pedidos capturados de su sucursal, puede autorizar y cancelar.
- ALMACEN ve pedidos autorizados donde su unidad aparece como almacen, puede surtir y generar CSV.

El alcance de datos en V1 sale de `unidadoperacional`:

- `IDUO`: unidad del usuario actual.
- `ID_SUPERVISOR_UO`: supervisor asignado a la unidad vendedora.
- `ID_ALMACEN_UO`: almacen asignado a la sucursal/unidad.
- Para gerente, la consulta se resuelve por sucursal.

## Estado actual en V2

V2 ya tiene tablas preparadas para permisos:

- `core.rol`
- `core.permiso`
- `core.rol_permiso`

Pero todavia no se estan usando de forma completa:

- El login devuelve rol, pero no devuelve lista de permisos.
- El frontend muestra menus fijos, sin filtrar por permisos.
- Las rutas usan `auth:sanctum`, pero no hay middleware de permiso.
- El modulo Pedidos ya tiene parte del alcance por datos:
  - supervisor por `IdSupervisor`,
  - gerente por `IdSucursal`,
  - almacen por `IdAlmacen`,
  - administrador sin restriccion.

## Propuesta para V2

Usar permisos como control principal de interfaz y backend, y mantener el alcance por unidad/sucursal dentro de Pedidos.

Permisos recomendados para Pedidos:

- `ped.pedidos.ver`: ver modulo de pedidos.
- `ped.pedidos.crear`: capturar pedido.
- `ped.pedidos.ver_propios`: ver pedidos de la unidad propia.
- `ped.pedidos.ver_por_autorizar`: ver pedidos pendientes de autorizar.
- `ped.pedidos.autorizar`: autorizar pedidos.
- `ped.pedidos.ver_por_surtir`: ver pedidos autorizados para almacen.
- `ped.pedidos.surtir`: surtir pedidos.
- `ped.pedidos.cancelar`: cancelar pedidos dentro del alcance.
- `ped.pedidos.csv`: generar/descargar CSV de surtido.
- `ped.catalogos.ver`: consultar catalogos de pedidos.
- `ped.unidades.admin`: administrar unidades operacionales.

Asignacion recomendada:

| Rol V2 | Permisos |
| --- | --- |
| ADMIN / ADMINISTRADOR | Todos los permisos |
| VENDEDOR | `ped.pedidos.ver`, `ped.pedidos.crear`, `ped.pedidos.ver_propios`, `ped.catalogos.ver` |
| SUPERVISOR | `ped.pedidos.ver`, `ped.pedidos.ver_por_autorizar`, `ped.pedidos.autorizar`, `ped.pedidos.cancelar`, `ped.catalogos.ver` |
| GERENTE_SUCURSAL / GERENTE | `ped.pedidos.ver`, `ped.pedidos.ver_por_autorizar`, `ped.pedidos.autorizar`, `ped.pedidos.cancelar`, `ped.catalogos.ver` |
| ALMACEN | `ped.pedidos.ver`, `ped.pedidos.ver_por_surtir`, `ped.pedidos.surtir`, `ped.pedidos.csv`, `ped.catalogos.ver` |
| SOPORTE_HD | definir si sera solo soporte tecnico o administrador operativo; si opera pedidos, asignar permisos explicitamente |

## Reglas de alcance que deben conservarse

Los permisos dicen que puede hacer el usuario. El alcance define sobre que registros puede hacerlo:

- VENDEDOR: solo su unidad operacional.
- SUPERVISOR: pedidos donde `ped.pedido.IdSupervisor` sea su `IdUnidad`.
- GERENTE: pedidos de unidades de su misma `IdSucursal`.
- ALMACEN: pedidos donde `ped.pedido.IdAlmacen` sea su `IdUnidad`.
- ADMIN: sin limite de alcance.

Esto evita depender solamente del frontend.

## Cambios tecnicos sugeridos

Backend:

- Crear modelo `App\Models\Core\Permiso`.
- Agregar relacion `Rol::permisos()`.
- Hacer que `/api/auth/login` y `/api/auth/me` devuelvan `permisos`.
- Crear middleware `permiso:clave`.
- Aplicar middleware a rutas sensibles de pedidos.
- Reforzar `cancelar` y `cambiarEstado`, porque hoy no validan alcance igual que `autorizar` y `surtir`.

Frontend:

- Guardar `permisos` en `stores/auth.js`.
- Agregar helper `auth.puede('clave')`.
- Filtrar `menuItems` del sidebar por permisos.
- En `PedidosPage.vue`, mostrar tabs/botones segun permisos:
  - Crear pedido: `ped.pedidos.crear`
  - Por autorizar: `ped.pedidos.ver_por_autorizar`
  - Autorizar: `ped.pedidos.autorizar`
  - Por surtir: `ped.pedidos.ver_por_surtir`
  - Surtir/CSV: `ped.pedidos.surtir` y `ped.pedidos.csv`

Base de datos:

- Insertar claves en `core.permiso`.
- Relacionar roles en `core.rol_permiso`.
- Definir si los roles de pedidos seran genericos (`VENDEDOR`, `SUPERVISOR`, `ALMACEN`, `GERENTE_SUCURSAL`) o especificos por canal (`VENDEDOR MODERNO`, `VENDEDOR TRADICIONAL`, etc.).

Recomendacion: mantener roles generales para permisos y usar `ped.tipounidad`/`ped.unidadoperacional` para canal, sucursal y relacion operativa. Asi no se duplica la matriz de permisos por cada canal.

## Orden recomendado de implementacion

1. Terminar creacion/mapeo de usuarios V2.
2. Confirmar nombres definitivos de roles en `core.rol`.
3. Cargar permisos base en `core.permiso` y `core.rol_permiso`.
4. Exponer permisos en login/me.
5. Filtrar menus y acciones del frontend.
6. Agregar middleware de permiso en rutas.
7. Probar flujo completo con una sucursal: vendedor -> supervisor/gerente -> almacen.
