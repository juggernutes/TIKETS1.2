<template>
  <!-- Botón campana -->
  <div class="notif-wrap" ref="wrapRef">
    <Button icon="pi pi-bell" text rounded
      :badge="store.noLeidas > 0 ? String(store.noLeidas) : undefined"
      badgeSeverity="danger"
      v-tooltip.bottom="'Notificaciones'"
      @click="toggle" />

    <!-- Panel desplegable -->
    <Transition name="panel">
      <div v-if="abierto" class="notif-panel">
        <div class="panel-header">
          <span class="panel-titulo">Notificaciones</span>
          <Button v-if="store.tieneNuevas" label="Marcar todas leídas"
            text size="small" @click="store.marcarTodas()" />
        </div>

        <div class="panel-body">
          <div v-if="store.lista.length === 0" class="panel-empty">
            <i class="pi pi-check-circle empty-icon" />
            <p>Sin notificaciones pendientes</p>
          </div>

          <div v-for="n in store.lista" :key="n.ID_Notificacion"
            class="notif-item" :class="{ 'no-leida': !n.Leida }"
            @click="abrir(n)">
            <i :class="['notif-icono pi', iconoPor(n.Modulo)]" />
            <div class="notif-contenido">
              <p class="notif-titulo">{{ n.Titulo }}</p>
              <p v-if="n.Mensaje" class="notif-msg">{{ n.Mensaje }}</p>
              <p class="notif-fecha">{{ formatTiempo(n.FechaCreacion) }}</p>
            </div>
            <Button icon="pi pi-times" text rounded size="small"
              class="notif-del" @click.stop="store.eliminar(n.ID_Notificacion)" />
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useNotificacionesStore } from '../../stores/notificaciones'
import Button from 'primevue/button'

const router = useRouter()
const store  = useNotificacionesStore()
const abierto = ref(false)
const wrapRef = ref(null)
let intervalo = null

function toggle() { abierto.value = !abierto.value }

function cerrar(e) {
  if (wrapRef.value && !wrapRef.value.contains(e.target)) {
    abierto.value = false
  }
}

function abrir(n) {
  store.marcarLeida(n.ID_Notificacion)
  abierto.value = false
  if (n.ID_Referencia) {
    const rutas = { HD: `/hd/tickets/${n.ID_Referencia}`,
                    RH: `/rh/candidatos/${n.ID_Referencia}`,
                    PED: `/ped/pedidos/${n.ID_Referencia}`,
                    COM: `/com/calculos/${n.ID_Referencia}` }
    const ruta = rutas[n.Modulo]
    if (ruta) router.push(ruta)
  }
}

function iconoPor(modulo) {
  const m = { HD: 'pi-ticket', RH: 'pi-users', PED: 'pi-box', COM: 'pi-wallet' }
  return m[modulo] ?? 'pi-bell'
}

function formatTiempo(iso) {
  if (!iso) return ''
  const diff = Date.now() - new Date(iso).getTime()
  const min  = Math.floor(diff / 60000)
  if (min < 1)   return 'ahora'
  if (min < 60)  return `hace ${min}m`
  const hrs = Math.floor(min / 60)
  if (hrs < 24)  return `hace ${hrs}h`
  return new Date(iso).toLocaleDateString('es-MX', { day:'2-digit', month:'2-digit' })
}

onMounted(() => {
  store.cargar()
  intervalo = setInterval(() => store.cargar(), 60000) // polling cada 1 min
  document.addEventListener('click', cerrar)
})

onUnmounted(() => {
  clearInterval(intervalo)
  document.removeEventListener('click', cerrar)
})
</script>

<style scoped>
.notif-wrap { position: relative; }

.notif-panel {
  position: absolute;
  top: calc(100% + 8px);
  right: 0;
  width: 340px;
  background: var(--p-surface-0);
  border: 1px solid var(--p-surface-200);
  border-radius: 8px;
  box-shadow: 0 8px 24px rgba(0,0,0,.12);
  z-index: 1000;
  overflow: hidden;
}

.panel-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.75rem 1rem;
  border-bottom: 1px solid var(--p-surface-200);
}
.panel-titulo { font-weight: 600; font-size: 0.9rem; }

.panel-body {
  max-height: 380px;
  overflow-y: auto;
}

.panel-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 2rem;
  gap: 0.5rem;
  color: var(--p-text-muted-color);
}
.empty-icon { font-size: 2rem; }

.notif-item {
  display: flex;
  align-items: flex-start;
  gap: 0.65rem;
  padding: 0.75rem 1rem;
  border-bottom: 1px solid var(--p-surface-100);
  cursor: pointer;
  transition: background .15s;
}
.notif-item:hover { background: var(--p-surface-50); }
.notif-item.no-leida { background: var(--p-primary-50, #eff6ff); }
.notif-item.no-leida:hover { background: var(--p-primary-100, #dbeafe); }

.notif-icono {
  font-size: 1rem;
  margin-top: 2px;
  color: var(--p-primary-500);
  flex-shrink: 0;
}
.notif-contenido { flex: 1; min-width: 0; }
.notif-titulo    { margin: 0 0 2px; font-size: 0.82rem; font-weight: 600;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.notif-msg       { margin: 0 0 3px; font-size: 0.78rem; color: var(--p-text-muted-color);
  display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.notif-fecha     { margin: 0; font-size: 0.72rem; color: var(--p-text-muted-color); }

.notif-del { flex-shrink: 0; opacity: 0; transition: opacity .15s; }
.notif-item:hover .notif-del { opacity: 1; }

/* Transición */
.panel-enter-active, .panel-leave-active { transition: opacity .15s, transform .15s; }
.panel-enter-from, .panel-leave-to { opacity: 0; transform: translateY(-6px); }
</style>
