<template>
  <section>
    <RouterLink class="back-link" to="/">← All kitchens</RouterLink>
    <div class="menu-heading">
      <div><span class="eyebrow">Made for pickup</span><h1>{{ vendor.name || 'Today’s menu' }}</h1><p>{{ vendor.description || 'Choose something delicious. We’ll handle the queue.' }}</p></div>
      <RouterLink class="cart-pill" to="/cart"><span>Bag</span><strong>{{ cart.count }}</strong></RouterLink>
    </div>

    <div v-if="notice" class="toast" role="status">{{ notice }}</div>
    <div v-if="loading" class="state-card"><span class="spinner"></span><div><h3>Loading menu...</h3><p>Checking today’s available items.</p></div></div>
    <div v-else-if="error" class="state-card state-card--error"><span>!</span><div><h3>Unable to load menu.</h3><p>{{ error }}</p></div><button class="button button--small" @click="fetchMenu">Retry</button></div>
    <div v-else-if="menuItems.length" class="menu-grid">
      <article v-for="item in menuItems" :key="item.id" class="menu-card">
        <div class="food-art"><span>{{ foodEmoji(item.name) }}</span><small>RM {{ money(item.price) }}</small></div>
        <div class="menu-card-body">
          <div class="badges">
            <span v-if="item.halal" class="badge">Halal</span>
            <span v-if="item.vegetarian" class="badge badge--leaf">Vegetarian</span>
            <span v-if="item.in_stock !== false && item.in_stock !== 0" class="badge badge--neutral">In stock</span>
          </div>
          <h3>{{ item.name }}</h3>
          <p>{{ item.description || 'Prepared fresh for your pickup.' }}</p>
          <div class="menu-action"><strong>RM {{ money(item.price) }}</strong><button class="add-button" @click="addToCart(item)">Add <span>+</span></button></div>
        </div>
      </article>
    </div>
    <div v-else class="state-card"><span>CE</span><div><h3>No menu items available.</h3><p>Please check again later.</p></div></div>
  </section>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { useCartStore } from '../stores/cart'
import api from '../services/api'

const route = useRoute()
const cart = useCartStore()
const vendor = ref({})
const menuItems = ref([])
const loading = ref(true)
const error = ref('')
const notice = ref('')
let noticeTimer

async function fetchMenu() {
  loading.value = true
  error.value = ''
  try {
    const { data } = await api.get(`/vendors/${route.params.id}/menu`)
    vendor.value = data.vendor ?? {}
    menuItems.value = data.menu_items ?? data.menu ?? []
  } catch (requestError) {
    error.value = requestError.response?.data?.error || 'Please try again in a moment.'
  } finally {
    loading.value = false
  }
}

function addToCart(item) {
  const result = cart.addItem({ ...item, vendor_id: item.vendor_id ?? route.params.id })
  notice.value = result.ok ? `${item.name} added to your bag.` : result.message
  clearTimeout(noticeTimer)
  noticeTimer = setTimeout(() => { notice.value = '' }, 3200)
}

function money(value) { return Number(value).toFixed(2) }
function foodEmoji(name = '') {
  const text = name.toLowerCase()
  if (text.includes('drink') || text.includes('milo') || text.includes('tea')) return '🥤'
  if (text.includes('rice') || text.includes('nasi')) return '🍛'
  if (text.includes('noodle') || text.includes('mee')) return '🍜'
  if (text.includes('chicken')) return '🍗'
  return '🍽️'
}

onMounted(fetchMenu)
</script>

<style scoped>
.back-link { display: inline-block; margin-bottom: 30px; color: var(--muted); font-size: .88rem; font-weight: 700; text-decoration: none; }.back-link:hover{color:var(--brand)}
.menu-heading { display: flex; align-items: flex-end; justify-content: space-between; gap: 30px; margin-bottom: 36px; }.menu-heading h1{margin-bottom:10px}.menu-heading p{margin:0;font-size:1.05rem}.cart-pill { display:flex;align-items:center;gap:12px;padding:12px 14px 12px 18px;border:1px solid var(--line);border-radius:999px;color:var(--ink);background:white;text-decoration:none;font-weight:800;box-shadow:0 7px 20px rgba(22,51,32,.07)}.cart-pill strong{display:grid;width:28px;height:28px;place-items:center;border-radius:50%;color:white;background:var(--ink);font-size:.75rem}
.toast { position: fixed; z-index: 20; top: 92px; left: 50%; padding: 12px 18px; transform: translateX(-50%); border: 1px solid #b8d8c2; border-radius: 999px; color: #174f2b; background: rgba(240,250,242,.96); box-shadow: var(--shadow); font-size: .84rem; font-weight: 800; backdrop-filter: blur(10px); }
.menu-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:20px}.menu-card{overflow:hidden;border:1px solid var(--line);border-radius:22px;background:white;box-shadow:0 8px 28px rgba(22,51,32,.05);transition:.2s ease}.menu-card:hover{transform:translateY(-4px);box-shadow:var(--shadow)}.food-art{position:relative;display:grid;height:175px;place-items:center;background:linear-gradient(145deg,#e4f1de,#f4f7ed)}.food-art>span{font-size:5.3rem;filter:drop-shadow(0 12px 12px rgba(0,0,0,.14))}.food-art small{position:absolute;right:14px;bottom:14px;padding:7px 10px;border-radius:999px;color:white;background:#17221b;font-weight:800}.menu-card-body{padding:20px}.badges{display:flex;min-height:24px;gap:6px;flex-wrap:wrap}.badge{padding:4px 7px;border-radius:6px;color:#695217;background:#fff1c9;font-size:.64rem;font-weight:800;text-transform:uppercase}.badge--leaf{color:#176b3a;background:#e4f4e9}.badge--neutral{color:#5f6862;background:#eff2ef}.menu-card h3{margin:12px 0 7px;font-size:1.15rem}.menu-card p{min-height:42px;margin-bottom:20px;font-size:.88rem;line-height:1.5}.menu-action{display:flex;align-items:center;justify-content:space-between;padding-top:16px;border-top:1px solid var(--line)}.menu-action>strong{font-family:Manrope,sans-serif}.add-button{display:flex;align-items:center;gap:10px;padding:9px 10px 9px 15px;border:0;border-radius:999px;color:white;background:var(--brand);cursor:pointer;font-weight:800}.add-button span{display:grid;width:23px;height:23px;place-items:center;border-radius:50%;color:var(--brand);background:white;font-size:1rem}.add-button:hover{background:var(--brand-dark)}
@media(max-width:900px){.menu-grid{grid-template-columns:repeat(2,1fr)}}@media(max-width:580px){.menu-heading{align-items:flex-start;flex-direction:column}.menu-grid{grid-template-columns:1fr}.cart-pill{position:fixed;z-index:9;right:16px;bottom:16px}.toast{top:auto;bottom:78px;width:calc(100% - 32px);text-align:center}}
</style>
