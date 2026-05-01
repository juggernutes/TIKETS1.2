import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { authApi } from '../api/auth'

export const useAuthStore = defineStore('auth', () => {
  const usuario  = ref(null)
  const token    = ref(localStorage.getItem('token') ?? null)
  const debeCambiarPassword = ref(false)

  const estaAutenticado   = computed(() => !!token.value)
  const nombre            = computed(() => usuario.value?.nombre ?? usuario.value?.Nombre ?? '')
  const rol               = computed(() => usuario.value?.rol ?? usuario.value?.rol?.Nombre ?? '')
  const permisos          = computed(() => usuario.value?.permisos ?? [])

  function puede(permiso) {
    if (!permiso) return true
    return permisos.value.includes(permiso)
  }

  async function login(cuenta, password) {
    const { data } = await authApi.login({ cuenta, password })
    token.value   = data.token
    usuario.value = data.usuario
    debeCambiarPassword.value = !!data.debe_cambiar_password
    localStorage.setItem('token', data.token)
    return data
  }

  async function cargarUsuario() {
    if (!token.value) return
    const { data } = await authApi.me()
    usuario.value = data
    debeCambiarPassword.value = !!data.debe_cambiar_password
  }

  async function actualizarPassword(payload) {
    const { data } = await authApi.cambiarPassword(payload)
    debeCambiarPassword.value = false
    return data
  }

  async function logout() {
    try { await authApi.logout() } catch {}
    token.value   = null
    usuario.value = null
    debeCambiarPassword.value = false
    localStorage.removeItem('token')
  }

  return {
    usuario, token, debeCambiarPassword,
    estaAutenticado, nombre, rol, permisos,
    puede,
    login, cargarUsuario, actualizarPassword, logout,
  }
})
