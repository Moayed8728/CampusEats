<template>
  <section>
    <div class="page-heading">
      <div>
        <span class="eyebrow">Admin</span>
        <h1>Campus overview</h1>
        <p>Track users, vendors, orders, and revenue across CampusEats.</p>
      </div>
    </div>

    <div v-if="loading" class="state-card admin-state">
      <span class="spinner"></span><div><h3>Loading dashboard...</h3><p>Gathering platform totals.</p></div>
    </div>
    <div v-else-if="error" class="state-card state-card--error admin-state">
      <span>!</span><div><h3>Dashboard unavailable</h3><p>{{ error }}</p></div>
      <button class="button button--small" @click="fetchSummary">Retry</button>
    </div>
    <div v-else class="summary-grid">
      <article class="summary-card">
        <span>Users</span>
        <strong>{{ summary.total_users }}</strong>
        <RouterLink to="/admin/users">View users</RouterLink>
      </article>
      <article class="summary-card">
        <span>Vendors</span>
        <strong>{{ summary.total_vendors }}</strong>
        <RouterLink to="/admin/vendors">Manage vendors</RouterLink>
      </article>
      <article class="summary-card">
        <span>Orders</span>
        <strong>{{ summary.total_orders }}</strong>
        <RouterLink to="/admin/orders">View orders</RouterLink>
      </article>
      <article class="summary-card">
        <span>Revenue</span>
        <strong>RM {{ money(summary.total_revenue) }}</strong>
        <RouterLink to="/admin/orders">Order ledger</RouterLink>
      </article>
      <article class="summary-card">
        <span>Loyalty points</span>
        <strong>{{ summary.total_loyalty_points }}</strong>
        <RouterLink to="/admin/rewards">View rewards</RouterLink>
      </article>
    </div>
  </section>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import api from '../services/api'

const summary = ref({ total_users: 0, total_vendors: 0, total_orders: 0, total_revenue: 0, total_loyalty_points: 0 })
const loading = ref(true)
const error = ref('')

async function fetchSummary() {
  loading.value = true
  error.value = ''
  try {
    const { data } = await api.get('/admin/summary')
    summary.value = data.summary ?? summary.value
  } catch (requestError) {
    error.value = requestError.response?.data?.error || 'Please check the API and try again.'
  } finally {
    loading.value = false
  }
}

function money(value) {
  return Number(value).toFixed(2)
}

onMounted(fetchSummary)
</script>

<style scoped>
.page-heading{margin-bottom:28px}.page-heading p{margin-bottom:0}.admin-state{margin-top:24px}.summary-grid{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:16px}.summary-card{display:grid;min-height:170px;padding:24px;border:1px solid var(--line);border-radius:18px;background:white;box-shadow:0 8px 28px rgba(22,51,32,.05)}.summary-card span{color:var(--muted);font-size:.78rem;font-weight:800;text-transform:uppercase}.summary-card strong{align-self:center;color:var(--ink);font-family:Manrope,sans-serif;font-size:clamp(1.7rem,3vw,2.3rem)}.summary-card a{align-self:end;color:var(--brand);font-size:.82rem;font-weight:800;text-decoration:none}.summary-card a:hover{color:var(--brand-dark)}
@media(max-width:900px){.summary-grid{grid-template-columns:repeat(2,1fr)}}@media(max-width:560px){.summary-grid{grid-template-columns:1fr}}
</style>
