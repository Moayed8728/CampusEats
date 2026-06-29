<template>
  <section>
    <div class="page-heading"><div><span class="eyebrow">Admin</span><h1>Orders</h1><p>Review platform order activity.</p></div></div>
    <div v-if="loading" class="state-card list-state"><span class="spinner"></span><div><h3>Loading orders...</h3></div></div>
    <div v-else-if="error" class="state-card state-card--error list-state"><span>!</span><div><h3>Orders unavailable</h3><p>{{ error }}</p></div><button class="button button--small" @click="fetchOrders">Retry</button></div>
    <div v-else-if="!orders.length" class="state-card list-state"><span>0</span><div><h3>No orders yet</h3><p>Student orders will appear here.</p></div></div>
    <div v-else class="order-list">
      <article v-for="order in orders" :key="order.id" class="order-card">
        <div><span class="eyebrow">#{{ shortId(order.id) }}</span><h2>{{ order.customer_name }}</h2><p>{{ order.vendor_name }}</p></div>
        <span class="status" :class="`status--${order.status}`">{{ title(order.status) }}</span>
        <div><small>Pickup</small><strong>{{ formatDate(order.pickup_at) }}</strong></div>
        <div><small>Total</small><strong>RM {{ money(order.total) }}</strong></div>
      </article>
    </div>
  </section>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import api from '../services/api'
const orders=ref([]);const loading=ref(true);const error=ref('')
async function fetchOrders(){loading.value=true;error.value='';try{const{data}=await api.get('/admin/orders');orders.value=data.orders??[]}catch(requestError){error.value=requestError.response?.data?.error||'Please check the API and try again.'}finally{loading.value=false}}
function money(value){return Number(value).toFixed(2)}function title(value=''){return value.charAt(0).toUpperCase()+value.slice(1)}function shortId(value=''){return value.split('-')[0].toUpperCase()}function formatDate(value){return new Intl.DateTimeFormat('en-MY',{dateStyle:'medium',timeStyle:'short'}).format(new Date(value.replace(' ','T')))}
onMounted(fetchOrders)
</script>

<style scoped>
.page-heading{margin-bottom:24px}.page-heading p{margin:0}.list-state{margin-top:24px}.order-list{display:grid;gap:12px}.order-card{display:grid;grid-template-columns:1.2fr auto 1fr .6fr;gap:18px;align-items:center;padding:18px;border:1px solid var(--line);border-radius:18px;background:white;box-shadow:0 8px 28px rgba(22,51,32,.04)}.order-card h2{margin:2px 0 4px;font-size:1rem}.order-card p{margin:0}.order-card small{display:block;color:var(--muted);font-size:.72rem}.order-card strong{display:block;margin-top:4px}.status{justify-self:start;padding:7px 10px;border-radius:999px;color:#725313;background:#fff2d3;font-size:.74rem;font-weight:800}.status--preparing{color:#2456a4;background:#e9f1ff}.status--ready{color:#147342;background:#e3f6ea}.status--collected{color:#666;background:#eee}
@media(max-width:760px){.order-card{grid-template-columns:1fr}}
</style>
