import client from './client'

export const authApi = {
  login:          (data)    => client.post('/auth/login', data),
  logout:         ()        => client.post('/auth/logout'),
  me:             ()        => client.get('/auth/me'),
  cambiarPassword:(data)    => client.post('/auth/cambiar-password', data),
}
