<template>
  <section>
    <div class="page-heading">
      <div>
        <span class="eyebrow">Admin</span>
        <h1>Loyalty rewards</h1>
        <p>Monitor point balances and awarded orders across students.</p>
      </div>
    </div>

    <div v-if="loading" class="state-card admin-state">
      <span class="spinner"></span><div><h3>Loading rewards...</h3><p>Syncing collected orders.</p></div>
    </div>
    <div v-else-if="error" class="state-card state-card--error admin-state">
      <span>!</span><div><h3>Rewards unavailable</h3><p>{{ error }}</p></div>
      <button class="button button--small" @click="fetchRewards">Retry</button>
    </div>

    <template v-else>
      <div class="summary-grid">
        <article class="summary-card"><span>Total points</span><strong>{{ summary.total_points }}</strong></article>
        <article class="summary-card"><span>Rewarded orders</span><strong>{{ summary.rewarded_orders }}</strong></article>
        <article class="summary-card"><span>Students earning</span><strong>{{ summary.students_with_points }}</strong></article>
      </div>

      <div class="section-heading">
        <div><span class="eyebrow">Balances</span><h2>Student points</h2></div>
      </div>
      <div v-if="balances.length" class="table-card">
        <div class="table-row table-head"><span>Student</span><span>Email</span><span>Orders</span><span>Points</span></div>
        <div v-for="student in balances" :key="student.id" class="table-row">
          <strong>{{ student.name }}</strong>
          <span>{{ student.email }}</span>
          <span>{{ student.rewarded_orders }}</span>
          <strong class="points">{{ student.points }}</strong>
        </div>
      </div>
      <div v-else class="state-card"><span>0</span><div><h3>No rewards yet.</h3><p>Collected student orders will appear here.</p></div></div>

      <div class="section-heading section-heading--spaced">
        <div><span class="eyebrow">Recent</span><h2>Point activity</h2></div>
      </div>
      <div v-if="recentTransactions.length" class="activity-list">
        <article v-for="transaction in recentTransactions" :key="transaction.id" class="activity-item">
          <div>
            <h3>{{ transaction.student_name }} earned {{ transaction.points }} points</h3>
            <p>{{ transaction.vendor_name }} • {{ transaction.description }} • {{ formatDate(transaction.created_at) }}</p>
          </div>
          <strong>+{{ transaction.points }}</strong>
        </article>
      </div>
      <div v-else class="state-card"><span>CE</span><div><h3>No activity yet.</h3><p>Rewards activity starts when orders are collected.</p></div></div>
    </template>
  </section>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import api from '../services/api'

const summary = ref({ total_points: 0, rewarded_orders: 0, students_with_points: 0 })
const balances = ref([])
const recentTransactions = ref([])
const loading = ref(true)
const error = ref('')

async function fetchRewards() {
  loading.value = true
  error.value = ''
  try {
    const { data } = await api.get('/admin/rewards')
    summary.value = data.summary ?? summary.value
    balances.value = data.balances ?? []
    recentTransactions.value = data.recent_transactions ?? []
  } catch (requestError) {
    error.value = requestError.response?.data?.error || 'Please check the API and try again.'
  } finally {
    loading.value = false
  }
}

function formatDate(value) {
  return new Intl.DateTimeFormat('en-MY', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value.replace(' ', 'T')))
}

onMounted(fetchRewards)
</script>

<style scoped>
.page-heading{margin-bottom:28px}.page-heading p{margin:0}.admin-state{margin-top:24px}.summary-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px;margin-bottom:34px}.summary-card{display:grid;min-height:140px;padding:22px;border:1px solid var(--line);border-radius:18px;background:white;box-shadow:0 8px 28px rgba(22,51,32,.05)}.summary-card span{color:var(--muted);font-size:.74rem;font-weight:800;text-transform:uppercase}.summary-card strong{align-self:end;color:var(--ink);font-family:Manrope,sans-serif;font-size:2rem}.section-heading{margin-bottom:16px}.section-heading--spaced{margin-top:34px}.section-heading h2{margin:4px 0 0}.table-card{overflow:hidden;border:1px solid var(--line);border-radius:18px;background:white}.table-row{display:grid;grid-template-columns:1.1fr 1.5fr .6fr .6fr;gap:16px;align-items:center;padding:15px 18px;border-bottom:1px solid #edf0ed;font-size:.88rem}.table-row:last-child{border-bottom:0}.table-head{color:var(--muted);background:#f7faf7;font-size:.72rem;font-weight:800;text-transform:uppercase}.points{color:var(--brand)}.activity-list{display:grid;gap:12px}.activity-item{display:flex;align-items:center;justify-content:space-between;gap:18px;padding:18px 20px;border:1px solid var(--line);border-radius:16px;background:white}.activity-item h3{margin:0 0 5px;font-size:.98rem}.activity-item p{margin:0;font-size:.82rem}.activity-item strong{color:var(--brand);font-family:Manrope,sans-serif;font-size:1.25rem}@media(max-width:760px){.summary-grid{grid-template-columns:1fr}.table-row{grid-template-columns:1fr}.table-head{display:none}.activity-item{align-items:flex-start;flex-direction:column}}
</style>
