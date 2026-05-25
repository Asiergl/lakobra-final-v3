import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'

import HomeView         from '../views/HomeView.vue'
import AuthView         from '../views/AuthView.vue'
import DashboardView    from '../views/DashboardView.vue'
import EventosView      from '../views/EventosView.vue'
import ArtistasView     from '../views/ArtistasView.vue'
import AdminEventosView from '../views/AdminEventosView.vue'
import EscanerView      from '../views/EscanerView.vue'
import TxandasView      from '../views/TxandasView.vue'

const routes = [
  { path: '/',                  name: 'Home',        component: HomeView },
  { path: '/auth',              name: 'Auth',        component: AuthView },
  { path: '/eventos',           name: 'Eventos',     component: EventosView },
  { path: '/solicitud-artista', name: 'Artistas',    component: ArtistasView },

  {
    path: '/dashboard',
    name: 'Dashboard',
    component: DashboardView,
    meta: { requiresAuth: true }
  },
  {
    path: '/txandas',
    name: 'Txandas',
    component: TxandasView,
    meta: { requiresAuth: true }
  },
  {
    path: '/admin/eventos',
    name: 'AdminEventos',
    component: AdminEventosView,
    meta: { requiresAuth: true, requiresAdmin: true }
  },
  {
    path: '/escanear',
    name: 'Escanear',
    component: EscanerView,
    meta: { requiresAuth: true, requiresPortero: true }
  },
]

const router = createRouter({
  history: createWebHistory('/'),
  routes
})

router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore()
  if (!authStore.isAuthenticated) await authStore.checkSession()

  if (to.meta.requiresAuth && !authStore.isAuthenticated) return next('/auth')
  if (to.meta.requiresAdmin && authStore.user?.rol !== 'admin') return next('/dashboard')
  if (to.meta.requiresPortero) {
    const rol = authStore.user?.rol
    if (rol !== 'admin' && rol !== 'txandalari') return next('/dashboard')
  }
  next()
})

export default router
