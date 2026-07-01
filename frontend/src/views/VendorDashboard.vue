<template>
  <section class="dashboard">
    <div class="page-heading">
      <div>
        <span class="eyebrow">{{ todayLabel }}</span>
        <h1>Good {{ greeting }}.</h1>
        <p>Here’s what’s happening at Campus Nasi Corner today.</p>
      </div>
      <span class="open-badge"><span></span> Store is open</span>
    </div>

    <p v-if="successMessage" class="feedback feedback--success">✓ {{ successMessage }}</p>
    <p v-if="actionError" class="feedback feedback--error">! {{ actionError }}</p>
    <p class="refresh-note">{{ refreshNote }}</p>

    <div v-if="loading" class="state-card dashboard-state">
      <span class="spinner"></span><div><h3>Loading orders...</h3><p>Checking the kitchen queue.</p></div>
    </div>
    <div v-else-if="error" class="state-card state-card--error dashboard-state">
      <span>!</span><div><h3>Unable to load orders.</h3><p>{{ error }}</p></div>
      <button class="button button--small" @click="fetchOrders">Retry</button>
    </div>
    <div v-else-if="!orders.length" class="state-card dashboard-state">
      <span>CE</span><div><h3>No incoming orders.</h3><p>New customer orders will appear here when you refresh.</p></div>
      <button class="button button--small" @click="fetchOrders">Refresh</button>
    </div>

    <template v-else>
    <div class="analytics-grid">
      <div class="stat-card">
        <span class="stat-icon">01</span>
        <h3>Orders today</h3>
        <p>{{ orders.length }}</p>
      </div>

      <div class="stat-card">
        <span class="stat-icon">RM</span>
        <h3>Revenue today</h3>
        <p>RM {{ revenueToday.toFixed(2) }}</p>
      </div>

      <div class="stat-card">
        <span class="stat-icon">02</span>
        <h3>Pending orders</h3>
        <p>{{ pendingOrders }}</p>
      </div>

      <div class="stat-card">
        <span class="stat-icon">★</span>
        <h3>Top item</h3>
        <p>{{ topItem }}</p>
      </div>
    </div>

    <div class="section-heading">
      <div><h2>Live orders</h2><p>Move orders through each stage as you prepare them.</p></div>
    </div>

    <div class="columns">
      <div v-for="group in statusGroups" :key="group.key" class="order-column" :class="`order-column--${group.key}`">
        <div class="column-heading"><h2><i :class="`status-dot status-dot--${group.key}`"></i>{{ group.label }}</h2><span>{{ group.orders.length }}</span></div>
        <OrderCard
          v-for="order in group.orders"
          :key="order.id"
          :order="order"
          :updating="updatingOrderId === order.id"
          @update-status="updateStatus"
        />
        <p v-if="!group.orders.length" class="empty-state">{{ group.empty }}</p>
      </div>
    </div>
    </template>
  </section>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import OrderCard from '../components/OrderCard.vue'
import api from '../services/api'

const router = useRouter()
const orders = ref([])
const loading = ref(true)
const error = ref('')
const successMessage = ref('')
const actionError = ref('')
const updatingOrderId = ref(null)
let messageTimer
let pollTimer

const todayLabel = new Intl.DateTimeFormat('en-MY', {
  weekday: 'long', day: 'numeric', month: 'long'
}).format(new Date())
const hour = new Date().getHours()
const greeting = hour < 12 ? 'morning' : hour < 18 ? 'afternoon' : 'evening'

async function fetchOrders({ quiet = false } = {}) {
  if (!quiet) loading.value = true
  error.value = ''

  try {
    const { data } = await api.get('/vendor/orders')
    orders.value = data.orders ?? []
  } catch (requestError) {
    if (requestError.response?.status === 401) {
      stopPolling()
      localStorage.removeItem('token')
      localStorage.removeItem('user')
      await router.push('/login')
      return
    }
    if (requestError.response?.status === 403) {
      stopPolling()
      error.value = 'This page needs a vendor account. Please logout and sign in as vendor@test.com.'
      return
    }
    error.value = requestError.response?.data?.error || 'Please check the API and try again.'
  } finally {
    loading.value = false
  }
}

function ordersByStatus(status) {
  return orders.value.filter(order => order.status === status)
}

function startPolling() {
  if (pollTimer) return
  pollTimer = setInterval(() => {
    fetchOrders({ quiet: true })
  }, 5000)
}

function stopPolling() {
  clearInterval(pollTimer)
  pollTimer = null
}

const refreshNote = computed(() => 'Auto-refreshing every 5 seconds.')

const statusGroups = computed(() => [
  { key: 'placed', label: 'Placed', empty: 'No placed orders', orders: ordersByStatus('placed') },
  { key: 'preparing', label: 'Preparing', empty: 'Nothing being prepared', orders: ordersByStatus('preparing') },
  { key: 'ready', label: 'Ready', empty: 'No orders ready', orders: ordersByStatus('ready') },
  { key: 'collected', label: 'Collected', empty: 'No collected orders yet', orders: ordersByStatus('collected') }
])

async function updateStatus(orderId, newStatus) {
  updatingOrderId.value = orderId
  actionError.value = ''

  try {
    const { data } = await api.patch(`/orders/${orderId}/status`, { status: newStatus })
    const order = orders.value.find((item) => item.id === orderId)
    if (order) order.status = data.order?.status ?? newStatus

    successMessage.value = `Order #${orderId.split('-')[0].toUpperCase()} moved to ${newStatus}.`
    clearTimeout(messageTimer)
    messageTimer = setTimeout(() => { successMessage.value = '' }, 3500)
  } catch (requestError) {
    actionError.value = requestError.response?.data?.error || 'The order status could not be updated.'
    clearTimeout(messageTimer)
    messageTimer = setTimeout(() => { actionError.value = '' }, 4500)
  } finally {
    updatingOrderId.value = null
  }
}

const revenueToday = computed(() => {
  return orders.value.reduce((sum, order) => sum + order.total, 0)
})

const pendingOrders = computed(() => {
  return orders.value.filter(order => order.status !== 'collected').length
})

const topItem = computed(() => {
  const itemCount = {}

  orders.value.forEach(order => {
    order.items.forEach(item => {
      itemCount[item.name] = (itemCount[item.name] || 0) + item.qty
    })
  })

  return Object.entries(itemCount).sort((a, b) => b[1] - a[1])[0]?.[0] || 'N/A'
})

onMounted(async () => {
  await fetchOrders()
  if (!error.value) startPolling()
})

onBeforeUnmount(() => {
  stopPolling()
  clearTimeout(messageTimer)
})
</script>

<style scoped>
.dashboard {
  width: 100%;
}

.dashboard-state {
  margin-top: 32px;
}

.refresh-note {
  margin: 18px 0 0;
  color: var(--muted);
  font-size: 0.78rem;
  font-weight: 800;
}

.feedback {
  position: fixed;
  z-index: 20;
  top: 92px;
  left: 50%;
  margin: 0;
  padding: 11px 17px;
  transform: translateX(-50%);
  border-radius: 999px;
  font-size: 0.8rem;
  font-weight: 800;
  box-shadow: var(--shadow);
}

.feedback--success {
  color: var(--brand-dark);
  background: #edf9f0;
  border: 1px solid #b9dbc4;
}

.feedback--error {
  color: #8b3831;
  background: #fff0ee;
  border: 1px solid #efc1bc;
}

.page-heading {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  gap: 24px;
}

.page-heading p,
.section-heading p {
  margin-bottom: 0;
}

.eyebrow {
  display: block;
  margin-bottom: 10px;
  color: var(--brand);
  font-size: 0.78rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.open-badge {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 9px 13px;
  border: 1px solid #cfe7d7;
  border-radius: 999px;
  color: var(--brand-dark);
  background: var(--brand-soft);
  font-size: 0.82rem;
  font-weight: 700;
}

.open-badge span {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #27a75f;
}

.analytics-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 16px;
  margin: 32px 0 42px;
}

.stat-card {
  position: relative;
  min-height: 142px;
  padding: 22px;
  border: 1px solid var(--line);
  border-radius: 18px;
  background: var(--surface);
  box-shadow: 0 4px 18px rgba(22, 51, 32, 0.04);
}

.stat-icon {
  position: absolute;
  top: 18px;
  right: 18px;
  display: grid;
  width: 34px;
  height: 34px;
  place-items: center;
  border-radius: 10px;
  color: var(--brand);
  background: var(--brand-soft);
  font-size: 0.68rem;
  font-weight: 800;
}

.stat-card h3 {
  margin: 42px 0 5px;
  font-family: 'DM Sans', sans-serif;
  font-size: 0.78rem;
  color: var(--muted);
  font-weight: 600;
}

.stat-card p {
  margin: 0;
  color: var(--ink);
  font-family: 'Manrope', sans-serif;
  font-size: clamp(1.25rem, 2vw, 1.65rem);
  font-weight: 800;
  line-height: 1.2;
}

.section-heading {
  margin-bottom: 18px;
}

.section-heading h2 {
  margin: 0 0 4px;
  font-size: 1.35rem;
}

.columns {
  display: grid;
  grid-template-columns: repeat(4, minmax(225px, 1fr));
  gap: 14px;
  overflow-x: auto;
  padding-bottom: 8px;
}

.order-column {
  min-width: 225px;
  padding: 12px;
  border-radius: 18px;
  background: rgba(232, 237, 233, 0.68);
}

.column-heading {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 4px 4px 12px;
}

.column-heading h2 {
  display: flex;
  align-items: center;
  gap: 8px;
  margin: 0;
  font-size: 0.92rem;
}

.column-heading span {
  display: grid;
  width: 24px;
  height: 24px;
  place-items: center;
  border-radius: 8px;
  color: var(--muted);
  background: white;
  font-size: 0.72rem;
  font-weight: 800;
}

.order-column :deep(.order-card + .order-card) {
  margin-top: 10px;
}

.empty-state {
  margin: 0;
  padding: 24px 10px;
  border: 1px dashed #ccd5ce;
  border-radius: 13px;
  text-align: center;
  font-size: 0.78rem;
}

.status-dot {
  width: 9px;
  height: 9px;
  border-radius: 50%;
  background: #d9a927;
}

.status-dot--preparing { background: #4e7fd0; }
.status-dot--ready { background: #27a75f; }
.status-dot--collected { background: #9ba39e; }

@media (max-width: 900px) {
  .analytics-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 560px) {
  .page-heading {
    align-items: flex-start;
    flex-direction: column;
  }

  .analytics-grid {
    grid-template-columns: 1fr;
  }
}
</style>
