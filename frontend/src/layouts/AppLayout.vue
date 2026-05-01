<template>
  <div class="app-shell">
    <nav class="sidebar" :class="{ collapsed: sidebarColapsado }">
      <div class="sidebar-header">
        <div class="sidebar-brand" :class="{ centered: sidebarColapsado }">
          <img :src="appAssets.brandLogo" class="sidebar-logo-img" alt="ROSARITO SG2" />
          <div v-if="!sidebarColapsado" class="sidebar-brand-copy">
            <span class="sidebar-brand-kicker">Portal interno</span>
            <strong>ROSARITO SG2</strong>
          </div>
        </div>

        <Button
          :icon="sidebarColapsado ? 'pi pi-angle-right' : 'pi pi-angle-left'"
          text
          rounded
          size="small"
          severity="secondary"
          class="collapse-btn"
          @click="sidebarColapsado = !sidebarColapsado"
        />
      </div>

      <PanelMenu :model="menuItems" class="sidebar-menu" />

      <div class="sidebar-footer">
        <div v-if="!sidebarColapsado" class="usuario-info">
          <span class="pi pi-user usuario-icono" />
          <div class="usuario-copy">
            <span class="usuario-label">Sesión activa</span>
            <span class="usuario-nombre">{{ auth.nombre }}</span>
          </div>
        </div>
        <NotificacionesPanel />
        <Button icon="pi pi-sign-out" text rounded size="small"
          severity="secondary"
          v-tooltip.right="'Cerrar sesión'"
          @click="cerrarSesion" />
      </div>
    </nav>

    <main class="main-content">
      <Toast />
      <ConfirmDialog />
      <div class="content-shell">
        <RouterView />
      </div>
    </main>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useCatalogosStore } from '../stores/catalogos'
import Button             from 'primevue/button'
import PanelMenu          from 'primevue/panelmenu'
import Toast             from 'primevue/toast'
import ConfirmDialog     from 'primevue/confirmdialog'
import NotificacionesPanel from '../components/shared/NotificacionesPanel.vue'
import { appAssets } from '../config/appAssets'

const router  = useRouter()
const auth    = useAuthStore()
const cats    = useCatalogosStore()

// Precarga catálogos al entrar al layout
cats.cargar()

const sidebarColapsado = ref(false)

const allMenuItems = [
  {
    label: 'Help Desk',
    icon:  'pi pi-ticket',
    items: [
      { label: 'Tickets', icon: 'pi pi-list',  command: () => router.push('/hd/tickets') },
      { label: 'SLA',     icon: 'pi pi-clock', command: () => router.push('/hd/sla') },
      { label: 'Empleados', icon: 'pi pi-id-card', command: () => router.push('/core/empleados') },
      { label: 'Usuarios',  icon: 'pi pi-user-edit', command: () => router.push('/core/usuarios') },
    ],
  },
  {
    label: 'Recursos Humanos',
    icon:  'pi pi-users',
    items: [
      { label: 'Vacantes',   icon: 'pi pi-briefcase', command: () => router.push('/rh/vacantes') },
      { label: 'Candidatos', icon: 'pi pi-user-plus',  command: () => router.push('/rh/candidatos') },
    ],
  },
  {
    label: 'Pedidos',
    icon:  'pi pi-box',
    permiso: 'ped.pedidos.ver',
    items: [
      { label: 'Pedidos', icon: 'pi pi-list', permiso: 'ped.pedidos.ver', command: () => router.push('/ped/pedidos') },
    ],
  },
  {
    label: 'Comisiones',
    icon:  'pi pi-wallet',
    items: [
      { label: 'Corridas',        icon: 'pi pi-play-circle',  command: () => router.push('/com/corridas') },
      { label: 'Semanas',         icon: 'pi pi-calendar',     command: () => router.push('/com/semanas') },
      { label: 'Metas mensuales', icon: 'pi pi-flag',         command: () => router.push('/com/metas') },
      { label: 'Base empleados',  icon: 'pi pi-users',        command: () => router.push('/com/base-empleados') },
      { label: 'Carga ventas',    icon: 'pi pi-chart-line',   command: () => router.push('/com/resultados') },
      { label: 'Checklist DOC',   icon: 'pi pi-file-check',   command: () => router.push('/com/resultado-doc') },
      { label: 'Reglas',          icon: 'pi pi-sliders-h',    command: () => router.push('/com/reglas') },
      { label: 'Cálculos',        icon: 'pi pi-calculator',   command: () => router.push('/com/calculos') },
      { label: 'Resumen semana',  icon: 'pi pi-chart-bar',    command: () => router.push('/com/resumen') },
    ],
  },
]

const menuItems = computed(() => filtrarMenu(allMenuItems))

function filtrarMenu(items) {
  return items
    .filter(item => !item.permiso || auth.puede(item.permiso))
    .map(item => {
      const filtrado = { ...item }
      if (item.items) {
        filtrado.items = filtrarMenu(item.items)
      }
      delete filtrado.permiso
      return filtrado
    })
    .filter(item => !item.items || item.items.length > 0)
}

async function cerrarSesion() {
  await auth.logout()
  router.push('/login')
}
</script>

<style scoped>
.app-shell {
  display: flex;
  min-height: 100vh;
  overflow: hidden;
}

.sidebar {
  width: 310px;
  min-width: 310px;
  background:
    linear-gradient(180deg, rgba(197, 22, 46, 0.12), transparent 20%),
    linear-gradient(180deg, #0c1627 0%, #0a1321 100%);
  color: #edf3fb;
  display: flex;
  flex-direction: column;
  transition: width 0.2s ease, min-width 0.2s ease;
  overflow: hidden;
  border-right: 1px solid rgba(159, 176, 199, 0.18);
  box-shadow: 18px 0 40px rgba(0, 0, 0, 0.18);
}

.sidebar.collapsed {
  width: 92px;
  min-width: 92px;
}

.sidebar-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 0.75rem;
  padding: 1.25rem 1rem 1rem;
  border-bottom: 1px solid rgba(159, 176, 199, 0.16);
}

.sidebar-brand {
  display: flex;
  align-items: center;
  gap: 0.85rem;
  min-width: 0;
}

.sidebar-brand.centered {
  justify-content: center;
}

.sidebar-brand-copy {
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.sidebar-brand-kicker {
  font-size: 0.72rem;
  text-transform: uppercase;
  letter-spacing: 0.16em;
  color: #9fb0c7;
}

.sidebar-brand-copy strong {
  font-size: 1.1rem;
  letter-spacing: 0.06em;
  color: #ffffff;
  white-space: nowrap;
}

.collapse-btn {
  margin-top: 0.25rem;
}

.sidebar-menu {
  flex: 1;
  overflow-y: auto;
  padding: 1rem 0.85rem;
  border: none;
  background: transparent;
}

.sidebar-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem;
  border-top: 1px solid rgba(159, 176, 199, 0.16);
  gap: 0.5rem;
  background: rgba(0, 0, 0, 0.12);
}

.usuario-info {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  min-width: 0;
}

.usuario-icono {
  width: 2rem;
  height: 2rem;
  display: grid;
  place-items: center;
  border-radius: 999px;
  background: linear-gradient(180deg, #d2223a, #971223);
  color: #fff;
  overflow: hidden;
}

.usuario-copy {
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
  min-width: 0;
}

.usuario-label {
  font-size: 0.72rem;
  text-transform: uppercase;
  letter-spacing: 0.12em;
  color: #9fb0c7;
}

.usuario-nombre {
  font-size: 0.92rem;
  font-weight: 600;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.main-content {
  flex: 1;
  overflow-y: auto;
  background:
    radial-gradient(820px 420px at 100% 0%, rgba(70, 194, 255, 0.1), transparent 55%),
    linear-gradient(180deg, rgba(255, 255, 255, 0.02), transparent 25%),
    #0b1423;
  padding: clamp(1rem, 2vw, 2rem);
}

.content-shell {
  width: min(100%, 1640px);
  min-height: calc(100vh - clamp(2rem, 4vw, 4rem));
  margin: 0 auto;
  padding: clamp(1rem, 2vw, 1.75rem);
  border: 1px solid rgba(159, 176, 199, 0.14);
  border-radius: 28px;
  background: linear-gradient(180deg, rgba(15, 25, 41, 0.9), rgba(9, 18, 31, 0.82));
  box-shadow: 0 24px 50px rgba(0, 0, 0, 0.22);
}

.sidebar-logo-img {
  width: 72px;
  min-width: 72px;
  height: 72px;
  object-fit: contain;
  filter: drop-shadow(0 10px 18px rgba(0, 0, 0, 0.25));
}

:deep(.sidebar-menu .p-panelmenu-panel) {
  margin-bottom: 0.55rem;
  border: 1px solid rgba(159, 176, 199, 0.1);
  border-radius: 16px;
  overflow: hidden;
  background: rgba(255, 255, 255, 0.02);
}

:deep(.sidebar-menu .p-panelmenu-header-link),
:deep(.sidebar-menu .p-panelmenu-content .p-menuitem-link) {
  background: transparent;
  color: #edf3fb;
}

:deep(.sidebar-menu .p-panelmenu-header-link) {
  padding: 0.9rem 1rem;
  font-weight: 700;
}

:deep(.sidebar-menu .p-panelmenu-content .p-menuitem-link) {
  padding: 0.75rem 1rem 0.75rem 1.25rem;
}

:deep(.sidebar-menu .p-panelmenu-header-link:hover),
:deep(.sidebar-menu .p-panelmenu-content .p-menuitem-link:hover) {
  background: rgba(255, 255, 255, 0.05);
}

:deep(.sidebar-menu .p-panelmenu-content) {
  border-top: 1px solid rgba(159, 176, 199, 0.08);
  background: rgba(255, 255, 255, 0.015);
}

@media (max-width: 1024px) {
  .sidebar {
    width: 260px;
    min-width: 260px;
  }

  .sidebar.collapsed {
    width: 78px;
    min-width: 78px;
  }

  .content-shell {
    border-radius: 22px;
    padding: 1rem;
  }
}

@media (max-width: 768px) {
  .app-shell {
    flex-direction: column;
  }

  .sidebar,
  .sidebar.collapsed {
    width: 100%;
    min-width: 100%;
  }

  .sidebar-header,
  .sidebar-footer {
    padding-inline: 1rem;
  }

  .main-content {
    padding: 0.85rem;
  }

  .content-shell {
    min-height: auto;
  }
}
</style>
