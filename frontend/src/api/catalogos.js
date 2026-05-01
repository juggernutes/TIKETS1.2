import client from './client'

export const catalogosApi = {
  estatusHd:           () => client.get('/catalogos/estatus-hd'),
  tipoError:           () => client.get('/catalogos/tipo-error'),
  sistemas:            () => client.get('/catalogos/sistemas'),
  areas:               () => client.get('/catalogos/areas'),
  sucursales:          () => client.get('/catalogos/sucursales'),
  puestos:             () => client.get('/catalogos/puestos'),
  fuentesReclutamiento:() => client.get('/catalogos/fuentes-reclutamiento'),
  estatusCandidato:    () => client.get('/catalogos/estatus-candidato'),
  semanas:             () => client.get('/catalogos/semanas'),
  solucionesHd:        () => client.get('/catalogos/soluciones-hd'),
  erroresHd:           () => client.get('/catalogos/errores-hd'),
  proveedores:         () => client.get('/catalogos/proveedores'),
  indicadores:         () => client.get('/catalogos/indicadores'),
  subIndicadores:      () => client.get('/catalogos/sub-indicadores'),
  usuarios:            () => client.get('/catalogos/usuarios'),
}
