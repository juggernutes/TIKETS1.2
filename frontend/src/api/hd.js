import client from './client'

export const hdApi = {
  // Tickets
  listarTickets:     (params)   => client.get('/hd/tickets', { params }),
  crearTicket:       (data)     => client.post('/hd/tickets', data),
  verTicket:         (id)       => client.get(`/hd/tickets/${id}`),
  actualizarEstatus: (id, data) => client.patch(`/hd/tickets/${id}/estatus`, data),
  asignarAgente:     (id, data) => client.patch(`/hd/tickets/${id}/agente`, data),
  enviarProveedor:   (id, data) => client.patch(`/hd/tickets/${id}/proveedor`, data),

  // Comentarios
  listarComentarios: (ticketId)       => client.get(`/hd/tickets/${ticketId}/comentarios`),
  agregarComentario: (ticketId, data) => client.post(`/hd/tickets/${ticketId}/comentarios`, data),

  // Encuestas
  registrarEncuesta: (ticketId, data) => client.post(`/hd/tickets/${ticketId}/encuesta`, data),
  verEncuesta:       (ticketId)       => client.get(`/hd/tickets/${ticketId}/encuesta`),
  resumenEncuestas:  (params)         => client.get('/hd/encuestas/resumen', { params }),

  // SLA
  listarSla:   (params)   => client.get('/hd/sla', { params }),
  crearSla:    (data)     => client.post('/hd/sla', data),
  editarSla:   (id, data) => client.patch(`/hd/sla/${id}`, data),
  eliminarSla: (id)       => client.delete(`/hd/sla/${id}`),

  // Empleados (búsqueda para formularios)
  buscarEmpleados: (q, idArea = null) => client.get('/catalogos/empleados', { params: { q, id_area: idArea } }),
}
