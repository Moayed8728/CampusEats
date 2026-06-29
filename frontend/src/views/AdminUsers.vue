<template>
  <section>
    <div class="page-heading"><div><span class="eyebrow">Admin</span><h1>Users</h1><p>All registered CampusEats accounts.</p></div></div>
    <div v-if="loading" class="state-card list-state"><span class="spinner"></span><div><h3>Loading users...</h3></div></div>
    <div v-else-if="error" class="state-card state-card--error list-state"><span>!</span><div><h3>Users unavailable</h3><p>{{ error }}</p></div><button class="button button--small" @click="fetchUsers">Retry</button></div>
    <div v-else-if="!users.length" class="state-card list-state"><span>0</span><div><h3>No users yet</h3><p>New registrations will appear here.</p></div></div>
    <div v-else class="table-card">
      <div class="table-row table-head"><span>Name</span><span>Email</span><span>Role</span><span>Created</span></div>
      <div v-for="user in users" :key="user.id" class="table-row">
        <strong>{{ user.name }}</strong><span>{{ user.email }}</span><span class="pill">{{ user.role }}</span><span>{{ formatDate(user.created_at) }}</span>
      </div>
    </div>
  </section>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import api from '../services/api'
const users = ref([]); const loading = ref(true); const error = ref('')
async function fetchUsers(){loading.value=true;error.value='';try{const{data}=await api.get('/admin/users');users.value=data.users??[]}catch(requestError){error.value=requestError.response?.data?.error||'Please check the API and try again.'}finally{loading.value=false}}
function formatDate(value){return new Intl.DateTimeFormat('en-MY',{dateStyle:'medium',timeStyle:'short'}).format(new Date(value.replace(' ','T')))}
onMounted(fetchUsers)
</script>

<style scoped>
.page-heading{margin-bottom:24px}.page-heading p{margin:0}.list-state{margin-top:24px}.table-card{overflow:hidden;border:1px solid var(--line);border-radius:18px;background:white;box-shadow:0 8px 28px rgba(22,51,32,.05)}.table-row{display:grid;grid-template-columns:1.1fr 1.5fr .7fr 1fr;gap:16px;align-items:center;padding:15px 18px;border-bottom:1px solid #edf0ed;font-size:.88rem}.table-row:last-child{border-bottom:0}.table-head{color:var(--muted);background:#f7faf7;font-size:.72rem;font-weight:800;text-transform:uppercase}.pill{justify-self:start;padding:5px 8px;border-radius:8px;color:var(--brand);background:var(--brand-soft);font-size:.72rem;font-weight:800;text-transform:capitalize}
@media(max-width:760px){.table-row{grid-template-columns:1fr}.table-head{display:none}}
</style>
