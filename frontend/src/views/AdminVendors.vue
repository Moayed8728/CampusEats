<template>
  <section>
    <div class="page-heading">
      <div>
        <span class="eyebrow">Admin</span>
        <h1>Vendors</h1>
        <p>Manage campus kitchens, storefront availability, and removals.</p>
      </div>
    </div>

    <p v-if="message" class="feedback feedback--success" role="status">{{ message }}</p>

    <div v-if="loading" class="state-card list-state">
      <span class="spinner"></span><div><h3>Loading vendors...</h3></div>
    </div>
    <div v-else-if="error" class="state-card state-card--error list-state">
      <span>!</span><div><h3>Vendors unavailable</h3><p>{{ error }}</p></div>
      <button class="button button--small" @click="fetchVendors">Retry</button>
    </div>
    <div v-else-if="!vendors.length" class="state-card list-state">
      <span>0</span><div><h3>No vendors yet</h3><p>Vendor profiles will appear here.</p></div>
    </div>
    <div v-else class="vendor-grid">
      <article v-for="vendor in vendors" :key="vendor.id" class="vendor-card">
        <div>
          <span class="status" :class="{ inactive: !vendor.is_active }">{{ vendor.is_active ? 'Active' : 'Inactive' }}</span>
          <h2>{{ vendor.name }}</h2>
          <p>{{ vendor.location }}</p>
        </div>
        <dl>
          <div><dt>Owner</dt><dd>{{ vendor.owner_name }}</dd></div>
          <div><dt>Hours</dt><dd>{{ vendor.opening_hours }}</dd></div>
        </dl>
        <div class="actions">
          <button class="button button--small" :disabled="updatingId === vendor.id" @click="toggleVendor(vendor)">
            {{ updatingId === vendor.id ? 'Updating...' : vendor.is_active ? 'Deactivate' : 'Restore' }}
          </button>
          <button v-if="vendor.is_active" class="button button--small button--danger" :disabled="removingId === vendor.id" @click="removeVendor(vendor)">
            {{ removingId === vendor.id ? 'Removing...' : 'Remove' }}
          </button>
        </div>
      </article>
    </div>
  </section>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import api from '../services/api'

const vendors = ref([])
const loading = ref(true)
const error = ref('')
const message = ref('')
const updatingId = ref(null)
const removingId = ref(null)

async function fetchVendors() {
  loading.value = true
  error.value = ''
  try {
    const { data } = await api.get('/admin/vendors')
    vendors.value = data.vendors ?? []
  } catch (requestError) {
    error.value = requestError.response?.data?.error || 'Please check the API and try again.'
  } finally {
    loading.value = false
  }
}

async function toggleVendor(vendor) {
  updatingId.value = vendor.id
  message.value = ''
  try {
    const { data } = await api.patch(`/admin/vendors/${vendor.id}/toggle-active`)
    vendor.is_active = data.vendor?.is_active ?? !vendor.is_active
    message.value = `${vendor.name} is now ${vendor.is_active ? 'active' : 'inactive'}.`
  } catch (requestError) {
    error.value = requestError.response?.data?.error || 'Vendor could not be updated.'
  } finally {
    updatingId.value = null
  }
}

async function removeVendor(vendor) {
  if (!window.confirm(`Remove ${vendor.name} from public listings? You can restore it later.`)) return
  removingId.value = vendor.id
  message.value = ''
  error.value = ''
  try {
    const { data } = await api.delete(`/admin/vendors/${vendor.id}`)
    vendor.is_active = false
    message.value = data.message || `${vendor.name} removed.`
  } catch (requestError) {
    error.value = requestError.response?.data?.error || 'Vendor could not be removed.'
  } finally {
    removingId.value = null
  }
}

onMounted(fetchVendors)
</script>

<style scoped>
.page-heading{margin-bottom:24px}.page-heading p{margin:0}.list-state{margin-top:24px}.feedback{display:inline-block;margin:0 0 16px;padding:10px 13px;border-radius:999px;font-size:.8rem;font-weight:800}.feedback--success{border:1px solid #b9dbc4;color:var(--brand-dark);background:#edf9f0}.vendor-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px}.vendor-card{display:grid;gap:18px;padding:22px;border:1px solid var(--line);border-radius:18px;background:white;box-shadow:0 8px 28px rgba(22,51,32,.05)}.vendor-card h2{margin:10px 0 4px;font-size:1.2rem}.vendor-card p{margin:0}.status{display:inline-block;padding:5px 8px;border-radius:8px;color:var(--brand);background:var(--brand-soft);font-size:.7rem;font-weight:800;text-transform:uppercase}.status.inactive{color:#8b3831;background:#fff0ee}dl{display:grid;gap:10px;margin:0}dl div{display:flex;justify-content:space-between;gap:14px;padding-top:10px;border-top:1px solid #edf0ed}dt{color:var(--muted);font-size:.76rem}dd{margin:0;text-align:right;font-weight:800}.actions{display:flex;gap:10px;flex-wrap:wrap}.button--danger{background:#fff0ee;color:#8b3831}
@media(max-width:900px){.vendor-grid{grid-template-columns:repeat(2,1fr)}}@media(max-width:560px){.vendor-grid{grid-template-columns:1fr}}
</style>
