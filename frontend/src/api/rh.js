import client from './client'

export const rhApi = {
  // Vacantes
  listarVacantes:      (params) => client.get('/rh/vacantes', { params }),
  crearVacante:        (data)   => client.post('/rh/vacantes', data),
  verVacante:          (id)     => client.get(`/rh/vacantes/${id}`),
  actualizarVacante:   (id, data) => client.patch(`/rh/vacantes/${id}`, data),
  cambiarEstatusVacante:(id, data) => client.patch(`/rh/vacantes/${id}/estatus`, data),
  listarVacantesPublicas: (params) => client.get('/public/rh/vacantes', { params }),
  verVacantePublica:   (id)     => client.get(`/public/rh/vacantes/${id}`),
  postularVacante:     (id, data) => client.post(`/public/rh/vacantes/${id}/postulaciones`, data, {
    headers: { 'Content-Type': 'multipart/form-data' },
  }),

  // Candidatos
  listarCandidatos:    (params) => client.get('/rh/candidatos', { params }),
  crearCandidato:      (data)   => client.post('/rh/candidatos', data),
  verCandidato:        (id)     => client.get(`/rh/candidatos/${id}`),
  cambiarEstatusCandidato:(id, data) => client.patch(`/rh/candidatos/${id}/estatus`, data),

  // Entrevistas
  agendarEntrevista:   (data)   => client.post('/rh/entrevistas', data),
  registrarResultado:  (id, data) => client.patch(`/rh/entrevistas/${id}/resultado`, data),
  agregarEvaluador:    (id, data) => client.post(`/rh/entrevistas/${id}/evaluadores`, data),

  // Ofertas laborales
  enviarOferta:        (data)   => client.post('/rh/ofertas', data),
  responderOferta:     (id, data) => client.patch(`/rh/ofertas/${id}/respuesta`, data),
}
