<template>
  <section>
    <div class="page-intro">
      <span class="eyebrow">Past pickups</span>
      <h1>Order history</h1>
      <p>Review your previous CampusEats orders and open any receipt for details.</p>
    </div>

    <div v-if="loading" class="state-card">
      <span class="spinner"></span><div><h3>Loading order history...</h3><p>Finding your previous orders.</p></div>
    </div>
    <div v-else-if="error" class="state-card state-card--error">
      <span>!</span><div><h3>Unable to load order history.</h3><p>{{ error }}</p></div>
      <button class="button button--small" @click="fetchOrders">Retry</button>
    </div>
    <div v-else-if="!orders.length" class="state-card">
      <span>CE</span><div><h3>No previous orders.</h3><p>Your completed and active orders will appear here.</p></div>
      <RouterLink class="button button--small" to="/">Browse vendors</RouterLink>
    </div>

    <div v-else class="history-grid">
      <RouterLink v-for="order in orders" :key="order.id" class="history-card" :to="`/orders/${order.id}`">
        <div class="card-top">
          <div><span>Order ID</span><h2>#{{ shortId(order.id) }}</h2></div>
          <strong :class="`status-badge status-badge--${order.status}`">{{ title(order.status) }}</strong>
        </div>
        <div class="detail-row"><span>Vendor</span><strong>{{ order.vendor_name }}</strong></div>
        <div class="detail-row"><span>Pickup Time</span><strong>{{ formatDate(order.pickup_at) }}</strong></div>
        <div class="detail-row total"><span>Total</span><strong>RM {{ money(order.total) }}</strong></div>
      </RouterLink>
    </div>
  </section>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import api from '../services/api'

const orders = ref([])
const loading = ref(true)
const error = ref('')

async function fetchOrders() {
  loading.value = true
  error.value = ''

  try {
    const { data } = await api.get('/student/orders')
    orders.value = data.orders ?? []
  } catch (requestError) {
    error.value = requestError.response?.data?.error || 'Please try again.'
  } finally {
    loading.value = false
  }
}

function money(value) { return Number(value).toFixed(2) }
function title(value = '') { return value.charAt(0).toUpperCase() + value.slice(1) }
function shortId(value = '') { return value.split('-')[0].toUpperCase() }
function formatDate(value) {
  if (!value) return 'Pickup time unavailable'
  return new Intl.DateTimeFormat('en-MY', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value.replace(' ', 'T')))
}

onMounted(fetchOrders)
</script>

<style scoped>
.page-intro { margin-bottom: 32px; }
.page-intro p { margin-bottom: 0; }
.history-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; }
.history-card { display: grid; gap: 15px; padding: 22px; border: 1px solid var(--line); border-radius: 18px; color: var(--ink); background: var(--surface); box-shadow: 0 6px 22px rgba(22,51,32,.05); text-decoration: none; transition: transform 160ms ease, box-shadow 160ms ease; }
.history-card:hover { transform: translateY(-3px); box-shadow: var(--shadow); }
.card-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; padding-bottom: 14px; border-bottom: 1px solid var(--line); }
.card-top span, .detail-row span { display: block; color: var(--muted); font-size: .74rem; font-weight: 700; }
.card-top h2 { margin: 4px 0 0; font-size: 1.25rem; }
.status-badge { padding: 7px 10px; border-radius: 999px; color: #725313; background: #fff2d3; font-size: .72rem; }
.status-badge--preparing { color: #2456a4; background: #e9f1ff; }
.status-badge--ready { color: #147342; background: #e3f6ea; }
.status-badge--collected { color: #666; background: #eee; }
.detail-row { display: flex; justify-content: space-between; gap: 15px; font-size: .88rem; }
.detail-row strong { text-align: right; }
.total { padding-top: 14px; border-top: 1px solid var(--line); }
.total strong { font-family: 'Manrope', sans-serif; font-size: 1.05rem; }
@media(max-width: 980px) { .history-grid { grid-template-columns: repeat(2, 1fr); } }
@media(max-width: 620px) { .history-grid { grid-template-columns: 1fr; } }
</style>
