<template>
  <section class="tracking">
    <span class="eyebrow">Live update</span>
    <h1>Track your order</h1>
    <p class="subtitle">{{ trackingSubtitle }}</p>

    <div v-if="loading" class="state-card tracking-state">
      <span class="spinner"></span><div><h3>Loading order...</h3><p>Checking the latest status.</p></div>
    </div>
    <div v-else-if="error" class="state-card state-card--error tracking-state">
      <span>!</span><div><h3>Unable to load order.</h3><p>{{ error }}</p></div>
      <button class="button button--small" @click="fetchOrder">Retry</button>
    </div>
    <div v-else-if="!order" class="state-card tracking-state">
      <span>CE</span><div><h3>Order not found.</h3><p>Please check your order history.</p></div>
      <RouterLink class="button button--small" to="/orders/history">Order History</RouterLink>
    </div>

    <div v-else class="tracking-card">
      <div class="order-summary">
        <div><span>Order number</span><h2>#{{ shortId(order.id) }}</h2></div>
        <span class="current-status" :class="`current-status--${order.status}`">{{ formatStatus(order.status) }}</span>
      </div>

      <div class="details">
        <div><span>Vendor</span><strong>{{ order.vendor_name }}</strong></div>
        <div><span>Pickup time</span><strong>{{ formatPickup(order.pickup_at) }}</strong></div>
        <div><span>Total</span><strong>RM {{ money(order.total) }}</strong></div>
      </div>

      <OrderStatusTimeline :current-status="order.status" />

      <div class="notice">
        <span>✓</span>
        <div><strong>{{ noticeTitle }}</strong><p>Last refreshed {{ lastRefreshed }}.</p></div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import api from '../services/api'
import OrderStatusTimeline from '../components/OrderStatusTimeline.vue'

const route = useRoute()
const order = ref(null)
const loading = ref(true)
const error = ref('')
const lastLoadedAt = ref(null)
const streamConnected = ref(false)
let pollTimer
let orderStream
const sseEnabled = import.meta.env.PROD || import.meta.env.VITE_ENABLE_SSE !== 'false'

const trackingSubtitle = computed(() => {
  if (streamConnected.value) return 'Tracking updates automatically with live updates.'
  if (!sseEnabled) return 'SSE disabled. Tracking updates every 5 seconds.'
  return 'Connecting live tracking. Polling is used only if the stream fails.'
})

const noticeTitle = computed(() => {
  if (order.value?.status === 'collected') return 'Your order has been collected'
  if (order.value?.status === 'ready') return 'Your order is ready for pickup'
  if (order.value?.status === 'preparing') return 'Your order is being prepared'
  return 'Your order has been placed'
})

const lastRefreshed = computed(() => {
  if (!lastLoadedAt.value) return 'just now'
  return new Intl.DateTimeFormat('en-MY', { timeStyle: 'medium' }).format(lastLoadedAt.value)
})

async function fetchOrder({ quiet = false } = {}) {
  if (!quiet) loading.value = true
  error.value = ''

  try {
    const { data } = await api.get(`/orders/${route.params.id}`)
    order.value = data.order
    lastLoadedAt.value = new Date()
    if (order.value?.status === 'collected') {
      stopPolling()
      closeOrderStream()
    }
  } catch (requestError) {
    error.value = requestError.response?.data?.error || 'Please check the order link and try again.'
  } finally {
    loading.value = false
  }
}

function startPolling() {
  if (pollTimer || order.value?.status === 'collected') return
  pollTimer = setInterval(() => {
    fetchOrder({ quiet: true })
  }, 5000)
}

function stopPolling() {
  clearInterval(pollTimer)
  pollTimer = null
}

function closeOrderStream() {
  orderStream?.close()
  orderStream = null
  streamConnected.value = false
}

function streamUrl() {
  const token = localStorage.getItem('token')
  if (!token) return null
  const baseUrl = api.defaults.baseURL || 'http://localhost:8000/api'
  return `${baseUrl}/student/orders/${route.params.id}/stream?token=${encodeURIComponent(token)}`
}

function startOrderStream() {
  if (!sseEnabled || order.value?.status === 'collected' || typeof EventSource === 'undefined') {
    startPolling()
    return
  }
  if (orderStream) return

  const url = streamUrl()
  if (!url) {
    startPolling()
    return
  }

  stopPolling()
  orderStream = new EventSource(url)
  orderStream.addEventListener('order_status_update', (event) => {
    try {
      const payload = JSON.parse(event.data)
      if (payload.order) {
        order.value = payload.order
        lastLoadedAt.value = new Date()
        error.value = ''
        streamConnected.value = true
        stopPolling()

        if (order.value.status === 'collected') {
          closeOrderStream()
        }
      }
    } catch {
      closeOrderStream()
      startPolling()
    }
  })
  orderStream.onerror = () => {
    closeOrderStream()
    startPolling()
  }
}

function money(value) { return Number(value).toFixed(2) }
function formatStatus(status = '') { return status.charAt(0).toUpperCase() + status.slice(1) }
function shortId(value = '') { return value.split('-')[0].toUpperCase() }
function formatPickup(value) {
  if (!value) return 'Pickup time unavailable'
  return new Intl.DateTimeFormat('en-MY', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value.replace(' ', 'T')))
}

onMounted(async () => {
  await fetchOrder()
  startOrderStream()
})

onBeforeUnmount(() => {
  stopPolling()
  closeOrderStream()
})
</script>

<style scoped>
.tracking { width: 100%; }
.subtitle { margin-bottom: 0; }
.tracking-state { margin-top: 32px; }
.tracking-card { margin-top: 32px; max-width: 820px; padding: clamp(22px, 4vw, 36px); border: 1px solid var(--line); border-radius: 20px; background: var(--surface); box-shadow: var(--shadow); }
.order-summary, .details, .notice { display: flex; }
.order-summary { align-items: center; justify-content: space-between; gap: 18px; padding-bottom: 24px; border-bottom: 1px solid var(--line); }
.order-summary span, .details span { display: block; margin-bottom: 4px; color: var(--muted); font-size: 0.76rem; }
.order-summary h2 { margin: 0; font-size: 1.5rem; }
.current-status { padding: 7px 11px; border-radius: 999px; color: #725313 !important; background: #fff2d3; font-size: 0.75rem !important; font-weight: 800; }
.current-status--preparing { color: #2456a4 !important; background: #e9f1ff; }
.current-status--ready { color: #147342 !important; background: #e3f6ea; }
.current-status--collected { color: #666 !important; background: #eee; }
.details { gap: 42px; padding: 24px 0; flex-wrap: wrap; }
.details strong { color: var(--ink); font-size: 0.9rem; }
.notice { align-items: center; gap: 13px; margin-top: 28px; padding: 15px; border-radius: 13px; background: var(--brand-soft); }
.notice > span { display: grid; flex: 0 0 34px; height: 34px; place-items: center; border-radius: 50%; color: white; background: var(--brand); }
.notice strong { color: var(--brand-dark); font-size: 0.86rem; }
.notice p { margin: 2px 0 0; font-size: 0.76rem; }
@media (max-width: 520px) {
  .order-summary { align-items: flex-start; flex-direction: column; }
  .details { gap: 24px; flex-direction: column; }
}
</style>
