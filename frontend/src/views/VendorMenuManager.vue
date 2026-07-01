<template>
  <section>
    <div class="page-heading">
      <div>
        <span class="eyebrow">Vendor</span>
        <h1>Menu manager</h1>
        <p>Add products, edit prices, and remove items from your storefront.</p>
      </div>
    </div>

    <p v-if="message" class="feedback feedback--success" role="status">{{ message }}</p>
    <p v-if="error" class="feedback feedback--error" role="alert">{{ error }}</p>

    <div class="menu-layout">
      <form class="panel menu-form" @submit.prevent="saveItem">
        <h2>{{ editingId ? 'Edit item' : 'Add item' }}</h2>

        <label>
          Item name
          <input v-model.trim="form.name" type="text" required minlength="3" maxlength="160" />
        </label>

        <label>
          Description
          <textarea v-model.trim="form.description" maxlength="500" rows="4"></textarea>
        </label>

        <div class="form-grid">
          <label>
            Price (RM)
            <input v-model.number="form.price" type="number" required min="0.01" max="999.99" step="0.01" />
          </label>
          <label>
            Category
            <select v-model="form.category" required>
              <option v-for="category in categories" :key="category" :value="category">{{ category }}</option>
            </select>
          </label>
        </div>

        <div class="checks">
          <label><input v-model="form.is_halal" type="checkbox" /> Halal</label>
          <label><input v-model="form.is_vegetarian" type="checkbox" /> Vegetarian</label>
          <label><input v-model="form.in_stock" type="checkbox" /> In stock</label>
        </div>

        <div class="form-actions">
          <button class="button" type="submit" :disabled="saving">
            {{ saving ? 'Saving...' : editingId ? 'Save changes' : 'Add item' }}
          </button>
          <button v-if="editingId" class="button button--ghost" type="button" @click="resetForm">Cancel</button>
        </div>
      </form>

      <div class="panel">
        <div class="section-heading">
          <div>
            <h2>{{ vendor?.name || 'Your menu' }}</h2>
            <p>{{ items.length }} items in your catalog</p>
          </div>
          <button class="button button--small" type="button" @click="fetchMenu">Refresh</button>
        </div>

        <div v-if="loading" class="state-card list-state">
          <span class="spinner"></span><div><h3>Loading menu...</h3></div>
        </div>
        <div v-else-if="!items.length" class="state-card list-state">
          <span>0</span><div><h3>No items yet</h3><p>Add your first product to start selling.</p></div>
        </div>
        <div v-else class="item-list">
          <article v-for="item in items" :key="item.id" class="item-card" :class="{ muted: !item.in_stock }">
            <div>
              <span class="status" :class="{ inactive: !item.in_stock }">{{ item.in_stock ? 'In stock' : 'Hidden' }}</span>
              <h3>{{ item.name }}</h3>
              <p>{{ item.description || 'No description' }}</p>
              <small>{{ item.category }} | RM {{ money(item.price) }}</small>
            </div>
            <div class="item-actions">
              <button class="button button--small" type="button" @click="editItem(item)">Edit</button>
              <button class="button button--small button--ghost" type="button" :disabled="deletingId === item.id" @click="deleteItem(item)">
                {{ deletingId === item.id ? 'Removing...' : 'Delete' }}
              </button>
            </div>
          </article>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import api from '../services/api'

const categories = ['Rice', 'Noodles', 'Drinks', 'Snacks']
const vendor = ref(null)
const items = ref([])
const loading = ref(true)
const saving = ref(false)
const deletingId = ref(null)
const editingId = ref('')
const error = ref('')
const message = ref('')

const emptyForm = () => ({
  name: '',
  description: '',
  price: 1,
  category: 'Rice',
  is_halal: true,
  is_vegetarian: false,
  in_stock: true
})
const form = ref(emptyForm())

async function fetchMenu() {
  loading.value = true
  error.value = ''
  try {
    const { data } = await api.get('/vendor/menu')
    vendor.value = data.vendor
    items.value = data.menu_items ?? []
  } catch (requestError) {
    error.value = requestError.response?.data?.error || 'Menu could not be loaded.'
  } finally {
    loading.value = false
  }
}

async function saveItem() {
  saving.value = true
  error.value = ''
  message.value = ''
  try {
    const payload = { ...form.value }
    if (editingId.value) {
      const { data } = await api.patch(`/vendor/menu-items/${editingId.value}`, payload)
      const index = items.value.findIndex((item) => item.id === editingId.value)
      if (index !== -1) items.value[index] = data.menu_item
      message.value = 'Menu item updated.'
    } else {
      const { data } = await api.post('/vendor/menu-items', payload)
      items.value.unshift(data.menu_item)
      message.value = 'Menu item added.'
    }
    resetForm()
  } catch (requestError) {
    const fields = requestError.response?.data?.fields
    error.value = fields ? Object.values(fields)[0] : requestError.response?.data?.error || 'Menu item could not be saved.'
  } finally {
    saving.value = false
  }
}

function editItem(item) {
  editingId.value = item.id
  form.value = {
    name: item.name,
    description: item.description || '',
    price: Number(item.price),
    category: item.category || 'Rice',
    is_halal: Boolean(item.is_halal),
    is_vegetarian: Boolean(item.is_vegetarian),
    in_stock: Boolean(item.in_stock)
  }
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

async function deleteItem(item) {
  if (!window.confirm(`Remove ${item.name} from your menu?`)) return
  deletingId.value = item.id
  error.value = ''
  message.value = ''
  try {
    const { data } = await api.delete(`/vendor/menu-items/${item.id}`)
    if (data.deleted) {
      items.value = items.value.filter((current) => current.id !== item.id)
    } else {
      const current = items.value.find((currentItem) => currentItem.id === item.id)
      if (current) current.in_stock = false
    }
    message.value = data.message || 'Menu item removed.'
  } catch (requestError) {
    error.value = requestError.response?.data?.error || 'Menu item could not be removed.'
  } finally {
    deletingId.value = null
  }
}

function resetForm() {
  editingId.value = ''
  form.value = emptyForm()
}

function money(value) {
  return Number(value).toFixed(2)
}

onMounted(fetchMenu)
</script>

<style scoped>
.page-heading{margin-bottom:24px}.page-heading p{margin:0}.menu-layout{display:grid;grid-template-columns:minmax(280px,380px) 1fr;gap:18px;align-items:start}.panel{padding:22px;border:1px solid var(--line);border-radius:18px;background:white;box-shadow:0 8px 28px rgba(22,51,32,.05)}.menu-form{display:grid;gap:16px;position:sticky;top:96px}.menu-form h2,.section-heading h2{margin:0;font-size:1.2rem}.menu-form label{display:grid;gap:7px;color:var(--ink);font-size:.78rem;font-weight:800}input,textarea,select{width:100%;border:1px solid var(--line);border-radius:12px;padding:12px 13px;background:#fbfdfb;color:var(--ink);font:inherit}textarea{resize:vertical}.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}.checks{display:flex;flex-wrap:wrap;gap:10px}.checks label{display:flex;grid-template-columns:auto;align-items:center;gap:7px;padding:8px 10px;border:1px solid var(--line);border-radius:999px;background:#f7faf7}.checks input{width:auto}.form-actions,.item-actions{display:flex;gap:10px;flex-wrap:wrap}.section-heading{display:flex;align-items:flex-start;justify-content:space-between;gap:14px;margin-bottom:16px}.section-heading p{margin:4px 0 0}.item-list{display:grid;gap:12px}.item-card{display:flex;align-items:flex-start;justify-content:space-between;gap:18px;padding:16px;border:1px solid #edf0ed;border-radius:14px;background:#fbfdfb}.item-card.muted{opacity:.7}.item-card h3{margin:8px 0 4px;font-size:1rem}.item-card p{margin:0 0 8px}.item-card small{color:var(--muted);font-weight:800}.status{display:inline-block;padding:5px 8px;border-radius:8px;color:var(--brand);background:var(--brand-soft);font-size:.68rem;font-weight:800;text-transform:uppercase}.status.inactive{color:#8b3831;background:#fff0ee}.feedback{display:inline-block;margin:0 0 16px;padding:10px 13px;border-radius:999px;font-size:.8rem;font-weight:800}.feedback--success{border:1px solid #b9dbc4;color:var(--brand-dark);background:#edf9f0}.feedback--error{border:1px solid #efc1bc;color:#8b3831;background:#fff0ee}.list-state{margin-top:12px}.button--ghost{color:var(--brand-dark);background:#eef6ef}
@media(max-width:900px){.menu-layout{grid-template-columns:1fr}.menu-form{position:static}}@media(max-width:560px){.form-grid,.item-card{grid-template-columns:1fr}.item-card{display:grid}.section-heading{flex-direction:column}}
</style>
