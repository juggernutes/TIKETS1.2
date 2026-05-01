import client from './client'

export const comApi = {
  // Cálculos
  listarCalculos:    (params)    => client.get('/com/calculos', { params }),
  verCalculo:        (id)        => client.get(`/com/calculos/${id}`),
  cambiarEstatus:    (id, data)  => client.patch(`/com/calculos/${id}/estatus`, data),
  aprobar:           (id, data)  => client.patch(`/com/calculos/${id}/aprobar`, data),
  resumenCorrida:    (idCorrida) => client.get(`/com/corridas/${idCorrida}/resumen`),

  // Corridas
  listarCorridas:    (params)    => client.get('/com/corridas', { params }),
  verCorrida:        (id)        => client.get(`/com/corridas/${id}`),
  crearCorrida:      (data)      => client.post('/com/corridas', data),
  cambiarEstatusCorrida: (id, data) => client.patch(`/com/corridas/${id}/estatus`, data),
  calcularCorrida:   (id, data)  => client.post(`/com/corridas/${id}/calcular`, data),

  // Semanas
  listarSemanas:     (params)    => client.get('/com/semanas', { params }),
  verSemana:         (id)        => client.get(`/com/semanas/${id}`),
  crearSemana:       (data)      => client.post('/com/semanas', data),
  actualizarSemana:  (id, data)  => client.patch(`/com/semanas/${id}`, data),
  resumenSemana:     (id)        => client.get(`/com/semanas/${id}/resumen`),

  // Reglas
  listarReglas:      (params)    => client.get('/com/reglas', { params }),
  crearRegla:        (data)      => client.post('/com/reglas', data),
  actualizarRegla:   (id, data)  => client.patch(`/com/reglas/${id}`, data),
  bulkReglas:        (data)      => client.post('/com/reglas/bulk', data),

  // Ajustes
  listarAjustes:     (params)    => client.get('/com/ajustes', { params }),
  crearAjuste:       (data)      => client.post('/com/ajustes', data),
  ajustesPorBase:    (idBase, data) => client.post(`/com/ajustes/base/${idBase}`, data),
  eliminarAjuste:    (id)        => client.delete(`/com/ajustes/${id}`),

  // Metas mensuales (Gerentes)
  listarPortadas:           (params)      => client.get('/com/metas/portadas', { params }),
  verPortada:               (id)          => client.get(`/com/metas/portadas/${id}`),
  crearPortada:             (data)        => client.post('/com/metas/portadas', data),
  actualizarPortada:        (id, data)    => client.patch(`/com/metas/portadas/${id}`, data),
  storeMetas:               (id, data)    => client.post(`/com/metas/portadas/${id}/contenido`, data),
  calcularMetasSemanales:   (id)          => client.post(`/com/metas/portadas/${id}/calcular-semanales`),

  // Base empleados (Generalistas)
  listarBaseEmpleados:  (params)       => client.get('/com/base-empleados', { params }),
  listarBaseCalculo:    (params)       => client.get('/com/base-calculo', { params }),
  crearBase:            (data)         => client.post('/com/base-empleados', data),
  bulkBase:             (data)         => client.post('/com/base-empleados/bulk', data),
  eliminarBase:         (id)           => client.delete(`/com/base-empleados/${id}`),

  // Resultado indicadores (Admin + CxC)
  listarResultados:     (params)       => client.get('/com/resultado-indicador', { params }),
  storeResultado:       (idBase, data) => client.post(`/com/resultado-indicador/base/${idBase}`, data),
  storeResultadoBulk:   (data)         => client.post('/com/resultado-indicador/bulk', data),
  storeDevolucion:      (idBase, data) => client.post(`/com/resultado-indicador/base/${idBase}/dev`, data),

  // Resultado DOC (Gerentes)
  listarResultadosDoc:  (params)       => client.get('/com/resultado-doc', { params }),
  storeResultadoDoc:    (idBase, data) => client.post(`/com/resultado-doc/base/${idBase}`, data),

  // Reglas de comisión (Admin)
  eliminarRegla:        (id)           => client.delete(`/com/reglas/${id}`),
  verRegla:             (id)           => client.get(`/com/reglas/${id}`),
}
