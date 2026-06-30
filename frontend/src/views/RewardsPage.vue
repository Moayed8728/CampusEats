<template>
  <section>
    <div class="rewards-hero">
      <div>
        <span class="eyebrow">CampusEats rewards</span>
        <h1>{{ balance }} points</h1>
        <p>Earn 100 points for every RM1 when an order is collected.</p>
      </div>
      <div class="rate-card">
        <span>Rate</span>
        <strong>RM1 = 100 points</strong>
      </div>
    </div>

    <div class="progress-card">
      <div>
        <span>Next milestone</span>
        <strong>{{ nextMilestone }} points</strong>
        <p>{{ milestoneText }}</p>
      </div>
      <div class="progress-track"><i :style="{ width: progressPercent + '%' }"></i></div>
    </div>

    <div class="reward-grid">
      <article class="reward-card">
        <span>2500</span>
        <h3>Snack boost</h3>
        <p>Save points for small treats between classes.</p>
      </article>
      <article class="reward-card">
        <span>5000</span>
        <h3>Lunch goal</h3>
        <p>Keep collecting toward your next campus lunch.</p>
      </article>
      <article class="reward-card">
        <span>10000</span>
        <h3>Big saver</h3>
        <p>A steady order habit turns into serious rewards.</p>
      </article>
    </div>

    <div class="section-heading">
      <div>
        <span class="eyebrow">History</span>
        <h2>Points earned</h2>
      </div>
      <button class="button button--small" @click="fetchRewards">Refresh</button>
    </div>

    <div v-if="loading" class="state-card">
      <span class="spinner"></span><div><h3>Loading rewards...</h3><p>Checking your points balance.</p></div>
    </div>
    <div v-else-if="error" class="state-card state-card--error">
      <span>!</span><div><h3>Unable to load rewards.</h3><p>{{ error }}</p></div>
      <button class="button button--small" @click="fetchRewards">Retry</button>
    </div>
    <div v-else-if="transactions.length" class="history-list">
      <article v-for="transaction in transactions" :key="transaction.id" class="history-item">
        <div>
          <h3>{{ transaction.description }}</h3>
          <p>{{ transaction.vendor_name }} • RM {{ money(transaction.total) }} • {{ formatDate(transaction.created_at) }}</p>
        </div>
        <strong>+{{ transaction.points }}</strong>
      </article>
    </div>
    <div v-else class="state-card">
      <span>CE</span><div><h3>No points yet.</h3><p>Points appear here when your orders are marked collected.</p></div>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import api from '../services/api'

const balance = ref(0)
const collectedOrders = ref(0)
const nextMilestone = ref(2500)
const pointsToNextMilestone = ref(2500)
const transactions = ref([])
const loading = ref(true)
const error = ref('')

const progressPercent = computed(() => {
  if (!nextMilestone.value) return 100
  return Math.min(100, Math.round((balance.value / nextMilestone.value) * 100))
})

const milestoneText = computed(() => {
  if (pointsToNextMilestone.value <= 0) return 'You reached this milestone.'
  return `${pointsToNextMilestone.value} points to go from ${collectedOrders.value} collected orders.`
})

async function fetchRewards() {
  loading.value = true
  error.value = ''

  try {
    const { data } = await api.get('/rewards')
    balance.value = data.balance ?? 0
    collectedOrders.value = data.collected_orders ?? 0
    nextMilestone.value = data.next_milestone ?? 2500
    pointsToNextMilestone.value = data.points_to_next_milestone ?? nextMilestone.value
    transactions.value = data.transactions ?? []
  } catch (requestError) {
    error.value = requestError.response?.data?.error || 'Please try again in a moment.'
  } finally {
    loading.value = false
  }
}

function money(value) { return Number(value).toFixed(2) }
function formatDate(value) {
  return new Intl.DateTimeFormat('en-MY', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value.replace(' ', 'T')))
}

onMounted(fetchRewards)
</script>

<style scoped>
.rewards-hero{display:flex;align-items:flex-end;justify-content:space-between;gap:24px;margin-bottom:18px;padding:clamp(26px,5vw,46px);border:1px solid var(--line);border-radius:24px;background:linear-gradient(135deg,#f1f8ee,#fff8df);box-shadow:var(--shadow)}.rewards-hero h1{margin:8px 0;font-size:clamp(2.4rem,7vw,5rem)}.rewards-hero p{margin:0}.rate-card{min-width:210px;padding:20px;border:1px solid #cfe7d7;border-radius:18px;background:white}.rate-card span,.progress-card span{display:block;color:var(--muted);font-size:.72rem;font-weight:800;text-transform:uppercase}.rate-card strong{display:block;margin-top:7px;color:var(--brand-dark);font-family:Manrope,sans-serif;font-size:1.2rem}.progress-card{display:grid;gap:16px;margin-bottom:28px;padding:20px;border:1px solid var(--line);border-radius:18px;background:white}.progress-card strong{display:block;margin:5px 0;color:var(--ink);font-family:Manrope,sans-serif;font-size:1.25rem}.progress-card p{margin:0;font-size:.84rem}.progress-track{overflow:hidden;height:12px;border-radius:999px;background:#edf0ed}.progress-track i{display:block;height:100%;border-radius:inherit;background:var(--brand);transition:width .25s ease}.reward-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px;margin-bottom:40px}.reward-card{padding:22px;border:1px solid var(--line);border-radius:18px;background:white}.reward-card span{display:grid;width:42px;height:42px;place-items:center;border-radius:14px;color:white;background:var(--brand);font-weight:900}.reward-card h3{margin:18px 0 7px;font-size:1rem}.reward-card p{margin:0;font-size:.84rem}.section-heading{display:flex;align-items:flex-end;justify-content:space-between;gap:16px;margin-bottom:18px}.section-heading h2{margin:4px 0 0}.history-list{display:grid;gap:12px}.history-item{display:flex;align-items:center;justify-content:space-between;gap:18px;padding:18px 20px;border:1px solid var(--line);border-radius:16px;background:white}.history-item h3{margin:0 0 5px;font-size:.98rem}.history-item p{margin:0;font-size:.82rem}.history-item strong{color:var(--brand);font-family:Manrope,sans-serif;font-size:1.35rem}@media(max-width:760px){.rewards-hero{align-items:flex-start;flex-direction:column}.rate-card{width:100%}.reward-grid{grid-template-columns:1fr}.section-heading{align-items:flex-start;flex-direction:column}}
</style>
