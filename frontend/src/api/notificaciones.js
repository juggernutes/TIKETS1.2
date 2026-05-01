import client from './client'

export const notificacionesApi = {
  listar:          (params) => client.get('/notificaciones', { params }),
  marcarLeida:     (id)     => client.patch(`/notificaciones/${id}/leida`),
  marcarTodas:     ()       => client.post('/notificaciones/marcar-todas-leidas'),
  eliminar:        (id)     => client.delete(`/notificaciones/${id}`),
}
