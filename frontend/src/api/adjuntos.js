import client from './client'

export const adjuntosApi = {
  listar: (params) => client.get('/adjuntos', { params }),
  subir: (data) => client.post('/adjuntos', data, {
    headers: { 'Content-Type': 'multipart/form-data' },
  }),
  eliminar: (id) => client.delete(`/adjuntos/${id}`),
  descargar: (id) => client.get(`/adjuntos/${id}/download`, {
    responseType: 'blob',
  }),
}
