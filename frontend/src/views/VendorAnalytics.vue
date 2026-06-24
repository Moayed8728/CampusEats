<template>
  <section class="analytics">
    <span class="eyebrow">Performance</span>
    <h1>Vendor analytics</h1>
    <p class="subtitle">A clear look at today’s orders and revenue.</p>

    <div v-if="loading" class="state-card analytics-state"><span class="spinner"></span><div><h3>Calculating today’s numbers…</h3></div></div>
    <div v-else-if="error" class="state-card state-card--error analytics-state"><span>!</span><div><h3>Analytics unavailable</h3><p>{{ error }}</p></div><button class="button button--small" @click="fetchAnalytics">Try again</button></div>

    <template v-else>
    <div class="grid">
      <div class="card">
        <span class="card-label">Today</span>
        <h3>Orders Today</h3>
        <p>{{ analytics.orders_today }}</p>
      </div>
      <div class="card">
        <span class="card-label">Sales</span>
        <h3>Revenue Today</h3>
        <p>RM {{ money(analytics.revenue_today) }}</p>
      </div>
      <div class="card">
        <span class="card-label">Popular</span>
        <h3>Top Item</h3>
        <p>{{ analytics.top_item || 'No sales yet' }}</p>
      </div>
      <div class="card">
        <span class="card-label">Busiest</span>
        <h3>Peak Hour</h3>
        <p>{{ analytics.peak_hour || 'No peak yet' }}</p>
      </div>
      <div class="card">
        <span class="card-label">In queue</span>
        <h3>Pending Orders</h3>
        <p>{{ analytics.pending_orders }}</p>
      </div>
    </div>

    <div class="card summary-card">
      <div class="summary-heading"><div><h2>Order status</h2><p>Today’s order distribution</p></div><strong>{{ analytics.orders_today }} total</strong></div>
      <div v-for="item in statusSummary" :key="item.status" class="summary-row">
        <span><i :class="`dot dot--${item.key}`"></i>{{ item.status }}</span>
        <strong>{{ item.count }}</strong>
      </div>
    </div>
    </template>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '../services/api'

const router = useRouter()
const analytics = ref(null)
const loading = ref(true)
const error = ref('')

async function fetchAnalytics() {
  loading.value = true
  error.value = ''

  try {
    const { data } = await api.get('/vendor/analytics')
    analytics.value = data.analytics
  } catch (requestError) {
    if (requestError.response?.status === 401) {
      localStorage.removeItem('token')
      localStorage.removeItem('user')
      await router.push('/login')
      return
    }
    error.value = requestError.response?.data?.error || 'Please check the API and try again.'
  } finally {
    loading.value = false
  }
}

const statusSummary = computed(() => {
  const statuses = ['placed', 'preparing', 'ready', 'collected']

  return statuses.map(status => ({
    key: status,
    status: status.charAt(0).toUpperCase() + status.slice(1),
    count: analytics.value?.status_summary?.[status] ?? 0
  }))
})

function money(value) {
  return Number(value).toFixed(2)
}

onMounted(fetchAnalytics)
</script>

<style scoped>
.analytics {
  width: 100%;
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

.subtitle {
  margin-bottom: 0;
}

.analytics-state {
  margin-top: 32px;
}

.grid {
  display: grid;
  grid-template-columns: repeat(5, minmax(0, 1fr));
  gap: 16px;
  margin: 32px 0;
}

.card {
  padding: 22px;
  border: 1px solid var(--line);
  border-radius: 18px;
  background: var(--surface);
  box-shadow: 0 4px 18px rgba(22, 51, 32, 0.04);
}

.card-label {
  display: inline-block;
  margin-bottom: 26px;
  padding: 5px 8px;
  border-radius: 7px;
  color: var(--brand);
  background: var(--brand-soft);
  font-size: 0.68rem;
  font-weight: 800;
  text-transform: uppercase;
}

.card h3 {
  margin: 0 0 8px;
  color: var(--muted);
  font-family: 'DM Sans', sans-serif;
  font-size: 0.78rem;
  font-weight: 600;
}

.card p {
  margin: 0;
  color: var(--ink);
  font-family: 'Manrope', sans-serif;
  font-size: clamp(1.2rem, 2vw, 1.6rem);
  font-weight: 800;
  line-height: 1.25;
}

.summary-card {
  max-width: 760px;
}

.summary-heading {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 20px;
  padding-bottom: 18px;
  border-bottom: 1px solid var(--line);
}

.summary-heading h2 {
  margin: 0 0 4px;
  font-size: 1.2rem;
}

.summary-heading p {
  margin: 0;
  font-size: 0.82rem;
}

.summary-heading > strong {
  padding: 6px 10px;
  border-radius: 8px;
  color: var(--brand);
  background: var(--brand-soft);
  font-size: 0.76rem;
}

.summary-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid #edf0ed;
  padding: 15px 2px;
}

.summary-row:last-child {
  border-bottom: 0;
  padding-bottom: 0;
}

.summary-row > span {
  display: flex;
  align-items: center;
  gap: 10px;
  color: var(--muted);
}

.dot {
  width: 9px;
  height: 9px;
  border-radius: 50%;
  background: #d9a927;
}

.dot--preparing { background: #4e7fd0; }
.dot--ready { background: #27a75f; }
.dot--collected { background: #9ba39e; }

@media (max-width: 1100px) {
  .grid {
    grid-template-columns: repeat(3, 1fr);
  }
}

@media (max-width: 900px) {
  .grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 520px) {
  .grid {
    grid-template-columns: 1fr;
  }
}
</style>
