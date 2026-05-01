import client from './client'

export const coreApi = {
  empleados: (params) => client.get('/core/empleados', { params }),
  empleado: (numero) => client.get(`/core/empleados/${numero}`),
  equiposEmpleado: (numero) => client.get(`/core/empleados/${numero}/equipos`),
  usuarios: (params) => client.get('/core/usuarios', { params }),
  resetPasswordUsuario: (idUsuario, data = {}) => client.post(`/core/usuarios/${idUsuario}/reset-password`, data),
}
