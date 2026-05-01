import client from './client'

export const pedApi = {
  listarPedidos:  (params)    => client.get('/ped/pedidos', { params }),
  porAutorizar:   (params)    => client.get('/ped/pedidos/por-autorizar', { params }),
  porSurtir:      (params)    => client.get('/ped/pedidos/por-surtir', { params }),
  miUnidad:       ()          => client.get('/ped/mi-unidad'),
  crearPedido:    (data)      => client.post('/ped/pedidos', data),
  verPedido:      (id)        => client.get(`/ped/pedidos/${id}`),
  autorizar:      (id, data)  => client.post(`/ped/pedidos/${id}/autorizar`, data),
  surtir:         (id, data)  => client.post(`/ped/pedidos/${id}/surtir`, data),
  cancelar:       (id, data)  => client.post(`/ped/pedidos/${id}/cancelar`, data),
  cambiarEstado:  (id, data)  => client.patch(`/ped/pedidos/${id}/estado`, data),
}
