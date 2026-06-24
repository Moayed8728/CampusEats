<template>
  <section class="tracking">
    <span class="eyebrow">Live update</span>
    <h1>Track your order</h1>
    <p class="subtitle">We’ll keep this page updated as your food is prepared.</p>

    <div class="tracking-card">
      <div class="order-summary">
        <div><span>Order number</span><h2>#{{ order.id }}</h2></div>
        <span class="current-status">{{ formatStatus(order.status) }}</span>
      </div>

      <div class="details">
        <div><span>Vendor</span><strong>{{ order.vendor }}</strong></div>
        <div><span>Pickup time</span><strong>{{ order.pickupTime }}</strong></div>
      </div>

      <OrderStatusTimeline :current-status="order.status" />

      <div class="notice"><span>✓</span><div><strong>Your order is being prepared</strong><p>Please arrive close to your pickup time.</p></div></div>
    </div>
  </section>
</template>

<script setup>
import { ref } from 'vue'
import OrderStatusTimeline from '../components/OrderStatusTimeline.vue'

const order = ref({
  id: '1001',
  vendor: 'Campus Nasi Corner',
  pickupTime: '12:30 PM',
  status: 'preparing'
})

function formatStatus(status) {
  return status.charAt(0).toUpperCase() + status.slice(1)
}
</script>

<style scoped>
.tracking {
  width: 100%;
}

.eyebrow {
  display: block;
  margin-bottom: 10px;
  color: var(--brand);
  font-size: 0.78rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.subtitle {
  margin-bottom: 0;
}

.tracking-card {
  margin-top: 32px;
  padding: clamp(22px, 4vw, 36px);
  border: 1px solid var(--line);
  border-radius: 20px;
  max-width: 760px;
  background: var(--surface);
  box-shadow: var(--shadow);
}

.order-summary,
.details,
.notice {
  display: flex;
}

.order-summary {
  align-items: center;
  justify-content: space-between;
  padding-bottom: 24px;
  border-bottom: 1px solid var(--line);
}

.order-summary span,
.details span {
  display: block;
  margin-bottom: 4px;
  color: var(--muted);
  font-size: 0.76rem;
}

.order-summary h2 {
  margin: 0;
  font-size: 1.5rem;
}

.current-status {
  padding: 7px 11px;
  border-radius: 999px;
  color: #2456a4 !important;
  background: #e9f1ff;
  font-size: 0.75rem !important;
  font-weight: 800;
}

.details {
  gap: 50px;
  padding: 24px 0;
}

.details strong {
  color: var(--ink);
  font-size: 0.9rem;
}

.notice {
  align-items: center;
  gap: 13px;
  margin-top: 28px;
  padding: 15px;
  border-radius: 13px;
  background: var(--brand-soft);
}

.notice > span {
  display: grid;
  flex: 0 0 34px;
  height: 34px;
  place-items: center;
  border-radius: 50%;
  color: white;
  background: var(--brand);
}

.notice strong {
  color: var(--brand-dark);
  font-size: 0.86rem;
}

.notice p {
  margin: 2px 0 0;
  font-size: 0.76rem;
}

@media (max-width: 520px) {
  .details {
    gap: 24px;
    flex-direction: column;
  }
}
</style>
