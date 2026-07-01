<template>
  <section>
    <div class="page-heading">
      <div><span class="eyebrow">Vendor access</span><h1>Apply as Vendor</h1><p>Tell the admin team about the campus kitchen you want to run.</p></div>
    </div>

    <div v-if="loadingStatus" class="state-card list-state"><span class="spinner"></span><div><h3>Checking application...</h3><p>Loading your latest vendor request.</p></div></div>
    <div v-else-if="statusError" class="state-card state-card--error list-state"><span>!</span><div><h3>Unable to load application.</h3><p>{{ statusError }}</p></div><button class="button button--small" @click="fetchApplication">Retry</button></div>

    <div v-else-if="application?.status === 'pending'" class="status-card">
      <span class="status status--pending">Pending</span>
      <h2>Your vendor application is currently under review.</h2>
      <p>We’ll show the result here after an admin reviews {{ application.vendor_name }}.</p>
    </div>

    <div v-else-if="application?.status === 'approved'" class="status-card">
      <span class="status status--approved">Approved</span>
      <h2>Your vendor application has been approved.</h2>
      <p>You can now manage your vendor dashboard.</p>
      <RouterLink class="button" to="/vendor/dashboard">Open Vendor Dashboard</RouterLink>
    </div>

    <template v-else>
      <div v-if="application?.status === 'rejected'" class="status-card status-card--soft">
        <span class="status status--rejected">Rejected</span>
        <h2>Your previous application was rejected. You may submit a new application.</h2>
      </div>

      <form class="application-card" @submit.prevent="submit">
        <label>Vendor name<input v-model.trim="form.vendor_name" required maxlength="160" placeholder="Campus Nasi Corner"></label>
        <label>Description<textarea v-model.trim="form.description" rows="4" required maxlength="500" placeholder="Describe your menu, cuisine, and service style."></textarea></label>
        <label>Location<input v-model.trim="form.location" required maxlength="160" placeholder="Food Court A"></label>
        <label>Opening hours<input v-model.trim="form.opening_hours" required maxlength="120" placeholder="Mon-Fri 9:00 AM - 5:00 PM"></label>
        <p v-if="message" class="feedback feedback--success">{{ message }}</p>
        <p v-if="error" class="feedback feedback--error">{{ error }}</p>
        <ul v-if="fieldErrors.length" class="field-errors"><li v-for="fieldError in fieldErrors" :key="fieldError">{{ fieldError }}</li></ul>
        <button class="button" :disabled="submitting">{{ submitting ? 'Submitting...' : 'Submit application' }} <span v-if="!submitting">→</span></button>
      </form>
    </template>
  </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useAuthStore } from '../stores/auth'
import api from '../services/api'

const auth = useAuthStore()
const form = reactive({ vendor_name: '', description: '', location: '', opening_hours: '' })
const application = ref(null)
const loadingStatus = ref(true)
const submitting = ref(false)
const statusError = ref('')
const error = ref('')
const message = ref('')
const fieldMap = ref({})

const fieldErrors = computed(() => Object.values(fieldMap.value))

function looksLikeWords(value) {
  return /[a-z0-9]/i.test(value) && /[a-z]{2,}/i.test(value) && !/^[^a-z0-9]+$/i.test(value)
}

function isRealisticOpeningHours(value) {
  const trimmed = value.trim()
  if (trimmed.length < 7 || trimmed.length > 120 || !/\b(am|pm)\b/i.test(trimmed) || !trimmed.includes('-')) return false
  const matches = [...trimmed.matchAll(/(?<!\d)(\d{1,2})(?::(\d{2}))?\s*(am|pm)\b/gi)]
  if (matches.length < 2) return false
  return matches.every((match) => {
    const hour = Number(match[1])
    const minute = match[2] ? Number(match[2]) : 0
    return hour >= 1 && hour <= 12 && minute >= 0 && minute <= 59
  })
}

function validateForm() {
  const fields = {}
  if (form.vendor_name.length < 3 || form.vendor_name.length > 160 || !looksLikeWords(form.vendor_name)) fields.vendor_name = 'Vendor name must be 3-160 characters and include readable text.'
  if (form.description.length < 20 || form.description.length > 500) fields.description = 'Description must be 20-500 characters.'
  if (form.location.length < 3 || form.location.length > 160 || !looksLikeWords(form.location)) fields.location = 'Location must be 3-160 characters and include readable text.'
  if (!isRealisticOpeningHours(form.opening_hours)) fields.opening_hours = 'Opening hours must look like 9:00 AM - 5:00 PM or Mon-Fri 9:00 AM - 5:00 PM.'
  fieldMap.value = fields
  return Object.keys(fields).length === 0
}

async function fetchApplication() {
  loadingStatus.value = true
  statusError.value = ''
  try {
    const { data } = await api.get('/vendor-applications/me')
    application.value = data.application
    if (data.role && auth.user) {
      auth.user.role = data.role
      localStorage.setItem('user', JSON.stringify(auth.user))
    }
  } catch (requestError) {
    statusError.value = requestError.response?.data?.error || 'Please check the API and try again.'
  } finally {
    loadingStatus.value = false
  }
}

async function submit() {
  error.value = ''
  message.value = ''
  if (!validateForm()) return

  submitting.value = true
  try {
    const { data } = await api.post('/vendor-applications', { ...form })
    application.value = data.application
    message.value = 'Application submitted. An admin can review it now.'
    form.vendor_name = ''
    form.description = ''
    form.location = ''
    form.opening_hours = ''
  } catch (requestError) {
    fieldMap.value = requestError.response?.data?.fields || {}
    error.value = requestError.response?.data?.error || 'Application could not be submitted.'
  } finally {
    submitting.value = false
  }
}

onMounted(fetchApplication)
</script>

<style scoped>
.page-heading{margin-bottom:24px}.page-heading p{margin:0}.list-state{margin-top:24px}.application-card,.status-card{display:grid;gap:16px;max-width:720px;padding:26px;border:1px solid var(--line);border-radius:18px;background:white;box-shadow:0 8px 28px rgba(22,51,32,.05)}.status-card{margin-bottom:18px}.status-card h2{margin:0;font-size:1.35rem}.status-card p{margin:0}.status-card--soft{background:#fffdf8}label{display:grid;gap:8px;color:var(--ink);font-size:.82rem;font-weight:800}input,textarea{width:100%;padding:13px;border:1px solid var(--line);border-radius:12px;color:var(--ink);background:#fbfdfb;font:inherit;font-weight:600}textarea{resize:vertical}.feedback{margin:0;padding:10px 13px;border-radius:12px;font-size:.8rem;font-weight:800}.feedback--success{color:var(--brand-dark);background:#edf9f0}.feedback--error{color:#8b3831;background:#fff0ee}.field-errors{display:grid;gap:6px;margin:0;padding:12px 16px 12px 28px;border-radius:12px;color:#8b3831;background:#fff0ee;font-size:.78rem;font-weight:800}.status{width:max-content;padding:5px 8px;border-radius:8px;font-size:.7rem;font-weight:800;text-transform:uppercase}.status--pending{color:#725313;background:#fff2d3}.status--approved{color:var(--brand);background:var(--brand-soft)}.status--rejected{color:#8b3831;background:#fff0ee}
</style>
