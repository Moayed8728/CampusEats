import { createRouter, createWebHistory } from 'vue-router'
import StudentHome from '../views/StudentHome.vue'
import LoginPage from '../views/LoginPage.vue'
import RegisterPage from '../views/RegisterPage.vue'
import VendorMenu from '../views/VendorMenu.vue'
import CartPage from '../views/CartPage.vue'
import OrderStatus from '../views/OrderStatus.vue'
import VendorDashboard from '../views/VendorDashboard.vue'
import VendorAnalytics from '../views/VendorAnalytics.vue'
import OrderTracking from '../views/OrderTracking.vue'
import AdminPage from '../views/AdminPage.vue'

const routes = [
  { path: '/', name: 'home', component: StudentHome },
  { path: '/login', name: 'login', component: LoginPage, meta: { guestOnly: true } },
  { path: '/register', name: 'register', component: RegisterPage, meta: { guestOnly: true } },
  { path: '/vendors/:id/menu', name: 'vendor-menu', component: VendorMenu },
  { path: '/cart', name: 'cart', component: CartPage, meta: { requiresAuth: true, roles: ['student'] } },
  { path: '/orders/:id', name: 'order-status', component: OrderStatus, meta: { requiresAuth: true } },
  { path: '/vendor/dashboard', name: 'vendor-dashboard', component: VendorDashboard, meta: { requiresAuth: true, roles: ['vendor'] } },
  { path: '/vendor/analytics', name: 'vendor-analytics', component: VendorAnalytics, meta: { requiresAuth: true, roles: ['vendor'] } },
  { path: '/orders/:id/tracking', name: 'order-tracking', component: OrderTracking, meta: { requiresAuth: true } },
  { path: '/admin', name: 'admin', component: AdminPage, meta: { requiresAuth: true, roles: ['admin'] } }
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior: () => ({ top: 0 })
})

router.beforeEach((to) => {
  const token = localStorage.getItem('token')
  let user = null

  try {
    user = JSON.parse(localStorage.getItem('user'))
  } catch {
    localStorage.removeItem('user')
  }

  if (to.meta.requiresAuth && !token) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }

  if (to.meta.roles && !to.meta.roles.includes(user?.role)) {
    return user?.role === 'vendor' ? { name: 'vendor-dashboard' } : { name: 'home' }
  }

  if (to.meta.guestOnly && token) {
    return user?.role === 'vendor' ? { name: 'vendor-dashboard' } : { name: 'home' }
  }
})

export default router
