<template>
  <section>
    <div class="page-heading">
      <div>
        <span class="eyebrow">AI Assistant</span>
        <h1>AI Food Assistant</h1>
        <p>Describe what you want and CampusEats will suggest matching meals.</p>
      </div>
    </div>

    <form class="assistant-panel" @submit.prevent="submitQuery">
      <label>Food request
        <textarea v-model.trim="query" rows="3" placeholder="I want halal rice under RM10"></textarea>
      </label>
      <button class="button" :disabled="loading">{{ loading ? 'Thinking...' : 'Find meals' }}</button>
    </form>

    <p v-if="notice" class="notice">{{ notice }}</p>
    <p v-if="source === 'rule_based_fallback'" class="source-note">Used fallback parser.</p>
    <p v-else-if="source === 'gemini'" class="source-note">Source used: Gemini</p>

    <div v-if="loading" class="state-card list-state"><span class="spinner"></span><div><h3>Reading your request...</h3><p>Parsing budget, category, and preferences.</p></div></div>
    <div v-else-if="error" class="state-card state-card--error list-state"><span>!</span><div><h3>Assistant unavailable</h3><p>{{ error }}</p></div><button class="button button--small" @click="submitQuery">Retry</button></div>

    <template v-else-if="parsed">
      <div class="parsed-card">
        <span class="eyebrow">Parsed preferences</span>
        <div class="parsed-grid">
          <div><small>Budget</small><strong>{{ parsed.budget ? `RM ${money(parsed.budget)}` : 'Any' }}</strong></div>
          <div><small>Category</small><strong>{{ parsed.category || 'Any' }}</strong></div>
          <div><small>Halal</small><strong>{{ parsed.halal ? 'Yes' : 'Any' }}</strong></div>
          <div><small>Vegetarian</small><strong>{{ parsed.vegetarian ? 'Yes' : 'Any' }}</strong></div>
          <div><small>Keyword</small><strong>{{ parsed.keyword || 'None' }}</strong></div>
        </div>
      </div>

      <div v-if="!recommendations.length" class="state-card list-state"><span>0</span><div><h3>No matching meals found.</h3><p>Try changing your budget or category.</p></div></div>
      <div v-else class="recommendation-grid">
        <article v-for="item in recommendations" :key="item.id" class="recommendation-card">
          <div class="card-top">
            <div><span class="eyebrow">{{ item.category || 'Meal' }}</span><h2>{{ item.name }}</h2><p>{{ item.vendor_name }}</p></div>
            <strong>RM {{ money(item.price) }}</strong>
          </div>
          <p>{{ item.description || 'Prepared fresh for pickup.' }}</p>
          <div class="badges">
            <span v-if="item.is_halal">Halal</span>
            <span v-if="item.is_vegetarian">Vegetarian</span>
          </div>
          <button class="button button--small" @click="addToCart(item)">Add to cart</button>
        </article>
      </div>
    </template>
  </section>
</template>

<script setup>
import { ref } from 'vue'
import api from '../services/api'
import { useCartStore } from '../stores/cart'

const cart = useCartStore()
const query = ref('I want halal rice under RM10')
const parsed = ref(null)
const source = ref('')
const recommendations = ref([])
const loading = ref(false)
const error = ref('')
const notice = ref('')
let noticeTimer

async function submitQuery() {
  if (!query.value) {
    error.value = 'Type a food request first.'
    return
  }
  loading.value = true
  error.value = ''
  try {
    const { data } = await api.post('/ai/query', { query: query.value })
    source.value = data.source ?? ''
    parsed.value = data.parsed
    recommendations.value = data.recommendations ?? []
  } catch (requestError) {
    error.value = requestError.response?.data?.error || 'Please check the API and try again.'
  } finally {
    loading.value = false
  }
}

function addToCart(item) {
  const result = cart.addItem({ ...item, vendor_id: item.vendor_id })
  notice.value = result.ok ? `${item.name} added to cart.` : result.message
  clearTimeout(noticeTimer)
  noticeTimer = setTimeout(() => { notice.value = '' }, 3200)
}

function money(value) {
  return Number(value).toFixed(2)
}
</script>

<style scoped>
.page-heading{margin-bottom:24px}.page-heading p{margin:0}.assistant-panel{display:grid;grid-template-columns:1fr auto;gap:14px;align-items:end;margin-bottom:24px;padding:18px;border:1px solid var(--line);border-radius:18px;background:white;box-shadow:0 8px 28px rgba(22,51,32,.04)}label{display:grid;gap:7px;color:var(--muted);font-size:.76rem;font-weight:800}textarea{width:100%;padding:13px;border:1px solid var(--line);border-radius:12px;background:#fbfdfb;color:var(--ink);resize:vertical}.notice,.source-note{display:inline-block;margin:0 0 16px;padding:10px 13px;border:1px solid #b9dbc4;border-radius:999px;color:var(--brand-dark);background:#edf9f0;font-size:.8rem;font-weight:800}.source-note{border-color:var(--line);color:var(--muted);background:white}.list-state{margin-top:24px}.parsed-card{margin-bottom:24px;padding:20px;border:1px solid var(--line);border-radius:18px;background:white;box-shadow:0 8px 28px rgba(22,51,32,.04)}.parsed-grid{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:12px}.parsed-grid div{padding:14px;border-radius:13px;background:#f5f8f5}.parsed-grid small{display:block;margin-bottom:5px;color:var(--muted);font-size:.68rem;font-weight:800;text-transform:uppercase}.parsed-grid strong{font-size:.92rem}.recommendation-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px}.recommendation-card{display:grid;gap:16px;padding:20px;border:1px solid var(--line);border-radius:18px;background:white;box-shadow:0 8px 28px rgba(22,51,32,.05)}.card-top{display:flex;justify-content:space-between;gap:16px}.card-top h2{margin:4px 0;font-size:1.1rem}.card-top p,.recommendation-card>p{margin:0}.card-top strong{white-space:nowrap;color:var(--brand);font-family:Manrope,sans-serif}.badges{display:flex;gap:8px;flex-wrap:wrap}.badges span{padding:5px 8px;border-radius:8px;color:var(--brand);background:var(--brand-soft);font-size:.7rem;font-weight:800;text-transform:uppercase}
@media(max-width:1000px){.parsed-grid,.recommendation-grid{grid-template-columns:repeat(2,1fr)}}@media(max-width:620px){.assistant-panel,.parsed-grid,.recommendation-grid{grid-template-columns:1fr}.card-top{flex-direction:column}}
</style>
