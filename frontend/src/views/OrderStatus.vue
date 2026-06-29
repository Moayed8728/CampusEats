<template>
  <section>
    <div v-if="loading" class="state-card"><span class="spinner"></span><div><h3>Loading order...</h3><p>Checking the latest kitchen status.</p></div></div>
    <div v-else-if="error" class="state-card state-card--error"><span>!</span><div><h3>Unable to load order.</h3><p>{{ error }}</p></div><button class="button button--small" @click="fetchOrder">Retry</button></div>
    <template v-else-if="order">
      <div class="success-heading"><div class="success-icon">✓</div><div><span class="eyebrow">Order received</span><h1>Lunch is in motion.</h1><p>We’ll keep the status right here as the kitchen works its magic.</p></div></div>
      <div class="order-layout">
        <div class="status-card">
          <div class="status-top"><div><span>Order</span><h2>#{{ shortId(order.id) }}</h2></div><strong :class="`status-badge status-badge--${order.status}`">{{ title(order.status) }}</strong></div>
          <OrderStatusTimeline :current-status="order.status" />
          <div class="pickup-note"><span>⌖</span><div><small>Pickup from</small><strong>{{ order.vendor_name }}</strong><p>{{ formatDate(order.pickup_at) }}</p></div></div>
        </div>
        <aside class="receipt-card">
          <div><span class="eyebrow">Your receipt</span><h2>Order summary</h2></div>
          <p v-if="order.customer_name" class="customer">For {{ order.customer_name }}</p>
          <ul><li v-for="item in order.items" :key="item.name"><span><b>{{ item.qty }}×</b> {{ item.name }}</span><strong>RM {{ money(item.unit_price * item.qty) }}</strong></li></ul>
          <div class="receipt-total"><span>Total paid</span><strong>RM {{ money(order.total) }}</strong></div>
        </aside>
      </div>
      <ReviewForm v-if="auth.role === 'student' && order.status === 'collected' && order.vendor_id" :vendor-id="order.vendor_id" />
      <RouterLink class="back-home" to="/">← Back to kitchens</RouterLink>
    </template>
    <div v-else class="state-card"><span>CE</span><div><h3>Order not found.</h3><p>Please check your order history.</p></div><RouterLink class="button button--small" to="/orders/history">Order History</RouterLink></div>
  </section>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import api from '../services/api'
import OrderStatusTimeline from '../components/OrderStatusTimeline.vue'
import ReviewForm from '../components/ReviewForm.vue'
import { useAuthStore } from '../stores/auth'

const route = useRoute()
const auth = useAuthStore()
const order = ref(null)
const loading = ref(true)
const error = ref('')

async function fetchOrder() {
  loading.value = true
  error.value = ''
  try {
    const { data } = await api.get(`/orders/${route.params.id}`)
    order.value = data.order
  } catch (requestError) {
    error.value = requestError.response?.data?.error || 'Please check the order link and try again.'
  } finally { loading.value = false }
}

function money(value) { return Number(value).toFixed(2) }
function title(value = '') { return value.charAt(0).toUpperCase() + value.slice(1) }
function shortId(value = '') { return value.split('-')[0].toUpperCase() }
function formatDate(value) { return new Intl.DateTimeFormat('en-MY', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value.replace(' ', 'T'))) }
onMounted(fetchOrder)
</script>

<style scoped>
.success-heading{display:flex;align-items:center;gap:22px;margin-bottom:34px}.success-icon{display:grid;flex:0 0 68px;height:68px;place-items:center;border-radius:22px;color:white;background:var(--brand);font-size:1.7rem;box-shadow:0 12px 25px rgba(23,107,58,.25)}.success-heading h1{margin-bottom:7px}.success-heading p{margin:0}.order-layout{display:grid;grid-template-columns:1.3fr .7fr;gap:24px;align-items:start}.status-card,.receipt-card{padding:clamp(22px,4vw,34px);border:1px solid var(--line);border-radius:23px;background:white;box-shadow:0 10px 35px rgba(22,51,32,.06)}.status-top{display:flex;align-items:center;justify-content:space-between}.status-top span{color:var(--muted);font-size:.75rem}.status-top h2{margin:4px 0 0}.status-badge{padding:8px 12px;border-radius:999px;color:#725313;background:#fff2d3;font-size:.75rem}.status-badge--preparing{color:#2456a4;background:#e9f1ff}.status-badge--ready{color:#147342;background:#e3f6ea}.status-badge--collected{color:#666;background:#eee}.pickup-note{display:flex;gap:14px;align-items:center;margin-top:25px;padding:18px;border-radius:15px;background:#f3f7f2}.pickup-note>span{display:grid;width:42px;height:42px;place-items:center;border-radius:13px;color:var(--brand);background:white;font-size:1.2rem}.pickup-note small{display:block;color:var(--muted)}.pickup-note strong{display:block;margin:2px 0}.pickup-note p{margin:0;font-size:.8rem}.receipt-card h2{margin:6px 0 0;font-size:1.25rem}.customer{margin:8px 0 20px;font-size:.8rem}.receipt-card ul{display:grid;gap:15px;margin:0;padding:20px 0;border-top:1px solid var(--line);border-bottom:1px solid var(--line);list-style:none}.receipt-card li,.receipt-total{display:flex;justify-content:space-between;gap:15px;font-size:.84rem}.receipt-card li b{margin-right:5px;color:var(--brand)}.receipt-total{align-items:center;padding-top:20px}.receipt-total strong{font-family:Manrope,sans-serif;font-size:1.15rem}.back-home{display:inline-block;margin-top:25px;color:var(--muted);font-weight:700;text-decoration:none}.back-home:hover{color:var(--brand)}
@media(max-width:800px){.order-layout{grid-template-columns:1fr}}@media(max-width:520px){.success-heading{align-items:flex-start}.success-icon{flex-basis:50px;height:50px;border-radius:15px}.status-top{align-items:flex-start;gap:15px}}
</style>
