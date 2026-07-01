<template>
  <section>
    <div class="page-heading"><div><span class="eyebrow">Admin</span><h1>Vendor Applications</h1><p>Review student requests to open vendor storefronts.</p></div></div>
    <p v-if="message" class="feedback feedback--success">{{ message }}</p>
    <div v-if="loading" class="state-card list-state"><span class="spinner"></span><div><h3>Loading applications...</h3></div></div>
    <div v-else-if="error" class="state-card state-card--error list-state"><span>!</span><div><h3>Applications unavailable</h3><p>{{ error }}</p></div><button class="button button--small" @click="fetchApplications">Retry</button></div>
    <div v-else-if="!applications.length" class="state-card list-state"><span>0</span><div><h3>No applications yet</h3><p>Student vendor requests will appear here.</p></div></div>
    <div v-else class="application-grid">
      <article v-for="application in applications" :key="application.id" class="application-card">
        <div>
          <span class="status" :class="`status--${application.status}`">{{ application.status }}</span>
          <h2>{{ application.vendor_name }}</h2>
          <p>{{ application.description || 'No description provided.' }}</p>
        </div>
        <dl>
          <div><dt>Applicant</dt><dd>{{ application.applicant_name }}<br><small>{{ application.applicant_email }}</small></dd></div>
          <div><dt>Location</dt><dd>{{ application.location || 'Not provided' }}</dd></div>
          <div><dt>Hours</dt><dd>{{ application.opening_hours || 'Not provided' }}</dd></div>
          <div><dt>Submitted</dt><dd>{{ formatDate(application.created_at) }}</dd></div>
        </dl>
        <div v-if="application.status === 'pending'" class="actions">
          <button class="button button--small" :disabled="updatingId === application.id" @click="review(application, 'approve')">Approve</button>
          <button class="button button--small button-secondary" :disabled="updatingId === application.id" @click="review(application, 'reject')">Reject</button>
        </div>
      </article>
    </div>
  </section>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import api from '../services/api'

const applications = ref([])
const loading = ref(true)
const error = ref('')
const message = ref('')
const updatingId = ref(null)

async function fetchApplications() {
  loading.value = true
  error.value = ''
  try {
    const { data } = await api.get('/admin/vendor-applications')
    applications.value = data.applications ?? []
  } catch (requestError) {
    error.value = requestError.response?.data?.error || 'Please check the API and try again.'
  } finally {
    loading.value = false
  }
}

async function review(application, action) {
  updatingId.value = application.id
  message.value = ''
  error.value = ''
  try {
    const { data } = await api.patch(`/admin/vendor-applications/${application.id}/${action}`)
    const index = applications.value.findIndex((item) => item.id === application.id)
    if (index !== -1 && data.application) applications.value[index] = data.application
    message.value = data.message || `Application ${action}d.`
  } catch (requestError) {
    error.value = requestError.response?.data?.error || 'Application could not be updated.'
  } finally {
    updatingId.value = null
  }
}

function formatDate(value) {
  return new Intl.DateTimeFormat('en-MY', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value.replace(' ', 'T')))
}

onMounted(fetchApplications)
</script>

<style scoped>
.page-heading{margin-bottom:24px}.page-heading p{margin:0}.list-state{margin-top:24px}.feedback{display:inline-block;margin:0 0 16px;padding:10px 13px;border-radius:999px;font-size:.8rem;font-weight:800}.feedback--success{color:var(--brand-dark);background:#edf9f0}.application-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px}.application-card{display:grid;gap:18px;padding:22px;border:1px solid var(--line);border-radius:18px;background:white;box-shadow:0 8px 28px rgba(22,51,32,.05)}.application-card h2{margin:10px 0 4px;font-size:1.2rem}.application-card p{margin:0}.status{display:inline-block;padding:5px 8px;border-radius:8px;color:#725313;background:#fff2d3;font-size:.7rem;font-weight:800;text-transform:uppercase}.status--approved{color:var(--brand);background:var(--brand-soft)}.status--rejected{color:#8b3831;background:#fff0ee}dl{display:grid;gap:10px;margin:0}dl div{display:flex;justify-content:space-between;gap:14px;padding-top:10px;border-top:1px solid #edf0ed}dt{color:var(--muted);font-size:.76rem}dd{margin:0;text-align:right;font-weight:800}dd small{color:var(--muted);font-weight:700}.actions{display:flex;gap:8px}.button-secondary{color:var(--brand);background:var(--brand-soft)}
@media(max-width:900px){.application-grid{grid-template-columns:repeat(2,1fr)}}@media(max-width:560px){.application-grid{grid-template-columns:1fr}.actions{flex-direction:column}}
</style>
