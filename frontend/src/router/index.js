import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const routes = [
  // ── Auth ──────────────────────────────────────────────────────────────
  {
    path: '/login',
    name: 'login',
    component: () => import('../pages/auth/LoginPage.vue'),
    meta: { public: true },
  },
  {
    path: '/cambiar-password',
    name: 'cambiar-password',
    component: () => import('../pages/auth/CambiarPasswordPage.vue'),
    meta: { requiresAuth: true },
  },

  // ── App principal ─────────────────────────────────────────────────────
  {
    path: '/',
    component: () => import('../layouts/AppLayout.vue'),
    meta: { requiresAuth: true },
    children: [
      { path: '', redirect: '/hd/tickets' },

      // ── Help Desk ──────────────────────────────────────────────────
      { path: 'hd/tickets',     name: 'hd-tickets',    component: () => import('../pages/hd/TicketsPage.vue') },
      { path: 'hd/tickets/:id', name: 'hd-ticket-det', component: () => import('../pages/hd/TicketDetalle.vue') },
      { path: 'hd/sla',         name: 'hd-sla',        component: () => import('../pages/hd/SlaPage.vue') },
      { path: 'core/empleados', name: 'core-empleados', component: () => import('../pages/core/EmpleadosPage.vue') },
      { path: 'core/empleados/:numero', name: 'core-empleado-det', component: () => import('../pages/core/EmpleadoDetalle.vue') },
      { path: 'core/usuarios', name: 'core-usuarios', component: () => import('../pages/core/UsuariosPage.vue') },

      // ── RH ─────────────────────────────────────────────────────────
      { path: 'rh/vacantes',       name: 'rh-vacantes',    component: () => import('../pages/rh/VacantesPage.vue') },
      { path: 'rh/vacantes/:id',   name: 'rh-vacante-det', component: () => import('../pages/rh/VacanteDetalle.vue') },
      { path: 'rh/candidatos',     name: 'rh-candidatos',  component: () => import('../pages/rh/CandidatosPage.vue') },
      { path: 'rh/candidatos/:id', name: 'rh-cand-det',    component: () => import('../pages/rh/CandidatoDetalle.vue') },

      // ── Pedidos ────────────────────────────────────────────────────
      { path: 'ped/pedidos',     name: 'ped-pedidos',    component: () => import('../pages/ped/PedidosPage.vue') },
      { path: 'ped/pedidos/:id', name: 'ped-pedido-det', component: () => import('../pages/ped/PedidoDetalle.vue') },

      // ── Comisiones ─────────────────────────────────────────────────
      { path: 'com/corridas',       name: 'com-corridas',    component: () => import('../pages/com/CorridasPage.vue') },
      { path: 'com/semanas',        name: 'com-semanas',     component: () => import('../pages/com/SemanasPage.vue') },
      { path: 'com/metas',          name: 'com-metas',       component: () => import('../pages/com/MetasMensualesPage.vue') },
      { path: 'com/base-empleados', name: 'com-base',        component: () => import('../pages/com/BaseEmpleadosPage.vue') },
      { path: 'com/resultados',     name: 'com-resultados',  component: () => import('../pages/com/ResultadosPage.vue') },
      { path: 'com/resultado-doc',  name: 'com-doc',         component: () => import('../pages/com/ResultadoDocPage.vue') },
      { path: 'com/reglas',         name: 'com-reglas',      component: () => import('../pages/com/ReglasPage.vue') },
      { path: 'com/calculos',       name: 'com-calculos',    component: () => import('../pages/com/CalculosPage.vue') },
      { path: 'com/calculos/:id',   name: 'com-calculo-det', component: () => import('../pages/com/CalculoDetalle.vue') },
      { path: 'com/resumen',        name: 'com-resumen',     component: () => import('../pages/com/ResumenSemana.vue') },
    ],
  },

  // Catch-all
  { path: '/:pathMatch(.*)*', redirect: '/' },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach(async (to) => {
  const auth = useAuthStore()

  if (to.meta.public) return true

  if (!auth.estaAutenticado) return { name: 'login' }

  if (auth.debeCambiarPassword && to.name !== 'cambiar-password') {
    return { name: 'cambiar-password' }
  }

  // Carga datos del usuario si aún no se han cargado
  if (!auth.usuario) {
    try { await auth.cargarUsuario() }
    catch { return { name: 'login' } }
  }

  return true
})

export default router
