<template>
  <section>
    <div class="page-heading">
      <div>
        <span class="eyebrow">Recommendations</span>
        <h1>Smart Meal Recommendations</h1>
        <p>Filter by budget, category, and preferences before adding items to your cart.</p>
      </div>
    </div>

    <form class="filter-panel" @submit.prevent="fetchRecommendations">
      <label>Budget
        <input v-model="filters.budget" type="number" min="0" step="0.50" placeholder="10">
      </label>
      <label>Category
        <select v-model="filters.category">
          <option value="">Any category</option>
          <option>Rice</option>
          <option>Noodles</option>
          <option>Drinks</option>
          <option>Snacks</option>
        </select>
      </label>
      <label>Keyword
        <input v-model.trim="filters.keyword" type="text" placeholder="chicken, spicy, milo">
      </label>
      <label class="check-row"><input v-model="filters.halal" type="checkbox"> Halal</label>
      <label class="check-row"><input v-model="filters.vegetarian" type="checkbox"> Vegetarian</label>
      <button class="button" :disabled="loading">{{ loading ? 'Searching...' : 'Search' }}</button>
    </form>

    <p v-if="notice" class="notice">{{ notice }}</p>

    <div v-if="loading" class="state-card list-state"><span class="spinner"></span><div><h3>Finding recommendations...</h3><p>Checking active kitchens and in-stock items.</p></div></div>
    <div v-else-if="error" class="state-card state-card--error list-state"><span>!</span><div><h3>Recommendations unavailable</h3><p>{{ error }}</p></div><button class="button button--small" @click="fetchRecommendations">Retry</button></div>
    <div v-else-if="searched && !recommendations.length" class="state-card list-state"><span>0</span><div><h3>No recommendations found.</h3><p>Try changing your budget or category.</p></div></div>
    <div v-else-if="recommendations.length" class="recommendation-grid">
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
  </section>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import api from '../services/api'
import { useCartStore } from '../stores/cart'

const cart = useCartStore()
const filters = reactive({ budget: '10', category: '', halal: true, vegetarian: false, keyword: '' })
const recommendations = ref([])
const loading = ref(false)
const error = ref('')
const notice = ref('')
const searched = ref(false)
let noticeTimer

async function fetchRecommendations() {
  loading.value = true
  error.value = ''
  searched.value = true
  try {
    const params = {
      budget: filters.budget || undefined,
      category: filters.category || undefined,
      halal: filters.halal || undefined,
      vegetarian: filters.vegetarian || undefined,
      keyword: filters.keyword || undefined
    }
    const { data } = await api.get('/recommendations', { params })
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

onMounted(fetchRecommendations)
</script>

<style scoped>
.page-heading{margin-bottom:24px}.page-heading p{margin:0}.filter-panel{display:grid;grid-template-columns:repeat(3,minmax(0,1fr)) auto auto auto;gap:12px;align-items:end;margin-bottom:24px;padding:18px;border:1px solid var(--line);border-radius:18px;background:white;box-shadow:0 8px 28px rgba(22,51,32,.04)}label{display:grid;gap:7px;color:var(--muted);font-size:.76rem;font-weight:800}input,select{width:100%;padding:11px;border:1px solid var(--line);border-radius:11px;background:#fbfdfb;color:var(--ink)}.check-row{display:flex;align-items:center;gap:8px;min-height:42px;color:var(--ink)}.check-row input{width:auto}.notice{display:inline-block;margin:0 0 16px;padding:10px 13px;border:1px solid #b9dbc4;border-radius:999px;color:var(--brand-dark);background:#edf9f0;font-size:.8rem;font-weight:800}.list-state{margin-top:24px}.recommendation-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px}.recommendation-card{display:grid;gap:16px;padding:20px;border:1px solid var(--line);border-radius:18px;background:white;box-shadow:0 8px 28px rgba(22,51,32,.05)}.card-top{display:flex;justify-content:space-between;gap:16px}.card-top h2{margin:4px 0;font-size:1.1rem}.card-top p,.recommendation-card>p{margin:0}.card-top strong{white-space:nowrap;color:var(--brand);font-family:Manrope,sans-serif}.badges{display:flex;gap:8px;flex-wrap:wrap}.badges span{padding:5px 8px;border-radius:8px;color:var(--brand);background:var(--brand-soft);font-size:.7rem;font-weight:800;text-transform:uppercase}
@media(max-width:1000px){.filter-panel{grid-template-columns:repeat(2,1fr)}.recommendation-grid{grid-template-columns:repeat(2,1fr)}}@media(max-width:620px){.filter-panel,.recommendation-grid{grid-template-columns:1fr}.card-top{flex-direction:column}}
</style>
