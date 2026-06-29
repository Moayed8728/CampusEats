<template>
  <section>
    <div class="page-heading"><div><span class="eyebrow">Student</span><h1>Notifications</h1><p>Kitchen updates for your orders.</p></div></div>
    <div v-if="loading" class="state-card list-state"><span class="spinner"></span><div><h3>Loading notifications...</h3></div></div>
    <div v-else-if="error" class="state-card state-card--error list-state"><span>!</span><div><h3>Notifications unavailable</h3><p>{{ error }}</p></div><button class="button button--small" @click="fetchNotifications">Retry</button></div>
    <div v-else-if="!notifications.length" class="state-card list-state"><span>0</span><div><h3>No notifications yet</h3><p>Order updates will appear here.</p></div></div>
    <div v-else class="notification-list">
      <article v-for="notification in notifications" :key="notification.id" class="notification-card" :class="{read: notification.is_read}">
        <div><h2>{{ notification.message }}</h2><p>{{ formatDate(notification.created_at) }}</p></div>
        <button v-if="!notification.is_read" class="button button--small" @click="markRead(notification)">Mark as read</button>
        <span v-else class="read-badge">Read</span>
      </article>
    </div>
  </section>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import api from '../services/api'
const notifications=ref([]);const loading=ref(true);const error=ref('')
async function fetchNotifications(){loading.value=true;error.value='';try{const{data}=await api.get('/notifications');notifications.value=data.notifications??[]}catch(requestError){error.value=requestError.response?.data?.error||'Please check the API and try again.'}finally{loading.value=false}}
async function markRead(notification){try{await api.patch(`/notifications/${notification.id}/read`);notification.is_read=true}catch(requestError){error.value=requestError.response?.data?.error||'Notification could not be updated.'}}
function formatDate(value){return new Intl.DateTimeFormat('en-MY',{dateStyle:'medium',timeStyle:'short'}).format(new Date(value.replace(' ','T')))}
onMounted(fetchNotifications)
</script>

<style scoped>
.page-heading{margin-bottom:24px}.page-heading p{margin:0}.list-state{margin-top:24px}.notification-list{display:grid;gap:12px}.notification-card{display:flex;align-items:center;justify-content:space-between;gap:18px;padding:18px;border:1px solid var(--line);border-radius:18px;background:white;box-shadow:0 8px 28px rgba(22,51,32,.04)}.notification-card.read{opacity:.72}.notification-card h2{margin:0 0 5px;font-size:1rem}.notification-card p{margin:0;font-size:.8rem}.read-badge{padding:7px 10px;border-radius:999px;color:var(--muted);background:#eff2ef;font-size:.72rem;font-weight:800}
@media(max-width:560px){.notification-card{align-items:flex-start;flex-direction:column}}
</style>
