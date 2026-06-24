<template>
  <div class="order-card">
    <div class="order-header">
      <div>
        <h3>Order #{{ order.id }}</h3>
        <p>Pickup: {{ order.pickupTime }}</p>
      </div>

      <span class="status" :class="`status--${order.status}`">
        <span class="status-dot"></span>{{ formatStatus(order.status) }}
      </span>
    </div>

    <ul class="item-list">
      <li v-for="item in order.items" :key="item.name">
        <span>{{ item.name }}</span><strong>×{{ item.qty }}</strong>
      </li>
    </ul>

    <div class="total-row">
      <span>Total</span><strong>RM {{ order.total.toFixed(2) }}</strong>
    </div>

    <div class="actions">
      <button
        v-if="order.status === 'placed'"
        @click="$emit('update-status', order.id, 'preparing')"
      >
        Mark Preparing
      </button>

      <button
        v-if="order.status === 'preparing'"
        @click="$emit('update-status', order.id, 'ready')"
      >
        Mark Ready
      </button>

      <button
        v-if="order.status === 'ready'"
        @click="$emit('update-status', order.id, 'collected')"
      >
        Mark Collected
      </button>
    </div>
  </div>
</template>

<script setup>
defineProps({
  order: {
    type: Object,
    required: true
  }
})

defineEmits(['update-status'])

function formatStatus(status) {
  return status.charAt(0).toUpperCase() + status.slice(1)
}
</script>

<style scoped>
.order-card {
  padding: 18px;
  border: 1px solid var(--line);
  border-radius: 16px;
  background: var(--surface);
  box-shadow: 0 3px 12px rgba(22, 51, 32, 0.035);
  transition: transform 180ms ease, box-shadow 180ms ease;
}

.order-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow);
}

.order-header {
  display: flex;
  justify-content: space-between;
  gap: 16px;
  align-items: flex-start;
}

.order-header h3 {
  margin: 0 0 4px;
  font-size: 1rem;
}

.order-header p {
  margin: 0;
  font-size: 0.82rem;
}

.status {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 9px;
  border-radius: 999px;
  color: #725313;
  background: #fff4d8;
  font-size: 0.72rem;
  font-weight: 700;
}

.status-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: currentColor;
}

.status--preparing {
  color: #2456a4;
  background: #e9f1ff;
}

.status--ready {
  color: #147342;
  background: #e3f6ea;
}

.status--collected {
  color: #6b7280;
  background: #eef0f2;
}

.item-list {
  display: grid;
  gap: 9px;
  margin: 18px 0;
  padding: 14px 0;
  border-top: 1px solid #edf0ed;
  border-bottom: 1px solid #edf0ed;
  list-style: none;
}

.item-list li,
.total-row {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  font-size: 0.87rem;
}

.item-list strong {
  color: var(--muted);
}

.total-row span {
  color: var(--muted);
}

.total-row strong {
  color: var(--ink);
  font-size: 0.98rem;
}

.actions {
  display: flex;
  gap: 10px;
  margin-top: 16px;
}

button {
  width: 100%;
  padding: 10px 14px;
  border: none;
  border-radius: 10px;
  background: var(--brand);
  color: white;
  cursor: pointer;
  font-size: 0.84rem;
  font-weight: 700;
  transition: background 160ms ease, transform 160ms ease;
}

button:hover {
  background: var(--brand-dark);
  transform: translateY(-1px);
}
</style>
