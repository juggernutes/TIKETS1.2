import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { notificacionesApi } from '../api/notificaciones'

export const useNotificacionesStore = defineStore('notificaciones', () => {
  const lista    = ref([])
  const noLeidas = ref(0)

  const tieneNuevas = computed(() => noLeidas.value > 0)

  async function cargar() {
    try {
      const { data } = await notificacionesApi.listar({ per_page: 30, leida: 0 })
      lista.value    = data.data
      noLeidas.value = data.no_leidas ?? data.data.length
    } catch { /* silencioso — no bloquear la UI */ }
  }

  async function marcarLeida(id) {
    try {
      await notificacionesApi.marcarLeida(id)
      const n = lista.value.find(n => n.ID_Notificacion === id)
      if (n) { n.Leida = true; noLeidas.value = Math.max(0, noLeidas.value - 1) }
    } catch { /* ignorar */ }
  }

  async function marcarTodas() {
    try {
      await notificacionesApi.marcarTodas()
      lista.value.forEach(n => { n.Leida = true })
      noLeidas.value = 0
    } catch { /* ignorar */ }
  }

  async function eliminar(id) {
    try {
      await notificacionesApi.eliminar(id)
      const idx = lista.value.findIndex(n => n.ID_Notificacion === id)
      if (idx !== -1) {
        if (!lista.value[idx].Leida) noLeidas.value = Math.max(0, noLeidas.value - 1)
        lista.value.splice(idx, 1)
      }
    } catch { /* ignorar */ }
  }

  return { lista, noLeidas, tieneNuevas, cargar, marcarLeida, marcarTodas, eliminar }
})
