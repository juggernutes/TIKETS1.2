import client from './client'

export const pedApi = {
  listarPedidos:  (params)    => client.get('/ped/pedidos', { params }),
  crearPedido:    (data)      => client.post('/ped/pedidos', data),
  verPedido:      (id)        => client.get(`/ped/pedidos/${id}`),
  cambiarEstado:  (id, data)  => client.patch(`/ped/pedidos/${id}/estado`, data),
}
