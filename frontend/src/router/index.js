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
import OrderHistory from '../views/OrderHistory.vue'
import AdminPage from '../views/AdminPage.vue'
import AdminUsers from '../views/AdminUsers.vue'
import AdminVendors from '../views/AdminVendors.vue'
import AdminOrders from '../views/AdminOrders.vue'
import NotificationsPage from '../views/NotificationsPage.vue'
import RecommendationsPage from '../views/RecommendationsPage.vue'
import AIAssistantPage from '../views/AIAssistantPage.vue'

const routes = [
  { path: '/', name: 'home', component: StudentHome, meta: { requiresAuth: true, roles: ['student'] } },
  { path: '/login', name: 'login', component: LoginPage, meta: { guestOnly: true } },
  { path: '/register', name: 'register', component: RegisterPage, meta: { guestOnly: true } },
  { path: '/vendors/:id/menu', name: 'vendor-menu', component: VendorMenu, meta: { requiresAuth: true, roles: ['student'] } },
  { path: '/cart', name: 'cart', component: CartPage, meta: { requiresAuth: true, roles: ['student'] } },
  { path: '/recommendations', name: 'recommendations', component: RecommendationsPage, meta: { requiresAuth: true, roles: ['student'] } },
  { path: '/ai-assistant', name: 'ai-assistant', component: AIAssistantPage, meta: { requiresAuth: true, roles: ['student'] } },
  { path: '/notifications', name: 'notifications', component: NotificationsPage, meta: { requiresAuth: true, roles: ['student'] } },
  { path: '/orders/history', name: 'order-history', component: OrderHistory, meta: { requiresAuth: true, roles: ['student'] } },
  { path: '/orders/:id/tracking', name: 'order-tracking', component: OrderTracking, meta: { requiresAuth: true, roles: ['student'] } },
  { path: '/orders/:id', name: 'order-status', component: OrderStatus, meta: { requiresAuth: true } },
  { path: '/vendor/dashboard', name: 'vendor-dashboard', component: VendorDashboard, meta: { requiresAuth: true, roles: ['vendor'] } },
  { path: '/vendor/analytics', name: 'vendor-analytics', component: VendorAnalytics, meta: { requiresAuth: true, roles: ['vendor'] } },
  { path: '/admin', name: 'admin', component: AdminPage, meta: { requiresAuth: true, roles: ['admin'] } },
  { path: '/admin/users', name: 'admin-users', component: AdminUsers, meta: { requiresAuth: true, roles: ['admin'] } },
  { path: '/admin/vendors', name: 'admin-vendors', component: AdminVendors, meta: { requiresAuth: true, roles: ['admin'] } },
  { path: '/admin/orders', name: 'admin-orders', component: AdminOrders, meta: { requiresAuth: true, roles: ['admin'] } }
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior: () => ({ top: 0 })
})

function roleHome(role) {
  if (role === 'vendor') return { name: 'vendor-dashboard' }
  if (role === 'admin') return { name: 'admin' }
  return { name: 'home' }
}

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

  if (to.meta.requiresAuth && token && !user) {
    localStorage.removeItem('token')
    return { name: 'login', query: { redirect: to.fullPath } }
  }

  if (to.meta.roles && !to.meta.roles.includes(user?.role)) {
    return roleHome(user?.role)
  }

  if (to.meta.guestOnly && token) {
    return roleHome(user?.role)
  }
})

export default router
