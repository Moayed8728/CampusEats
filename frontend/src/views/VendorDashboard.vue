<template>
  <section class="dashboard">
    <div class="page-heading">
      <div>
        <span class="eyebrow">Sunday, 21 June</span>
        <h1>Good afternoon.</h1>
        <p>Here’s what’s happening at Campus Nasi Corner today.</p>
      </div>
      <span class="open-badge"><span></span> Store is open</span>
    </div>

    <div class="analytics-grid">
      <div class="stat-card">
        <span class="stat-icon">01</span>
        <h3>Orders today</h3>
        <p>{{ orders.length }}</p>
      </div>

      <div class="stat-card">
        <span class="stat-icon">RM</span>
        <h3>Revenue today</h3>
        <p>RM {{ revenueToday.toFixed(2) }}</p>
      </div>

      <div class="stat-card">
        <span class="stat-icon">02</span>
        <h3>Pending orders</h3>
        <p>{{ pendingOrders }}</p>
      </div>

      <div class="stat-card">
        <span class="stat-icon">★</span>
        <h3>Top item</h3>
        <p>{{ topItem }}</p>
      </div>
    </div>

    <div class="section-heading">
      <div><h2>Live orders</h2><p>Move orders through each stage as you prepare them.</p></div>
    </div>

    <div class="columns">
      <div class="order-column">
        <div class="column-heading"><h2>Placed</h2><span>{{ ordersByStatus('placed').length }}</span></div>
        <OrderCard
          v-for="order in ordersByStatus('placed')"
          :key="order.id"
          :order="order"
          @update-status="updateStatus"
        />
        <p v-if="!ordersByStatus('placed').length" class="empty-state">No placed orders</p>
      </div>

      <div class="order-column">
        <div class="column-heading"><h2>Preparing</h2><span>{{ ordersByStatus('preparing').length }}</span></div>
        <OrderCard
          v-for="order in ordersByStatus('preparing')"
          :key="order.id"
          :order="order"
          @update-status="updateStatus"
        />
        <p v-if="!ordersByStatus('preparing').length" class="empty-state">Nothing being prepared</p>
      </div>

      <div class="order-column">
        <div class="column-heading"><h2>Ready</h2><span>{{ ordersByStatus('ready').length }}</span></div>
        <OrderCard
          v-for="order in ordersByStatus('ready')"
          :key="order.id"
          :order="order"
          @update-status="updateStatus"
        />
        <p v-if="!ordersByStatus('ready').length" class="empty-state">No orders ready</p>
      </div>

      <div class="order-column">
        <div class="column-heading"><h2>Collected</h2><span>{{ ordersByStatus('collected').length }}</span></div>
        <OrderCard
          v-for="order in ordersByStatus('collected')"
          :key="order.id"
          :order="order"
          @update-status="updateStatus"
        />
        <p v-if="!ordersByStatus('collected').length" class="empty-state">No collected orders yet</p>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, ref } from 'vue'
import OrderCard from '../components/OrderCard.vue'

const orders = ref([
  {
    id: '1001',
    status: 'placed',
    pickupTime: '12:30 PM',
    total: 10.50,
    items: [
      { name: 'Nasi Goreng Kampung', qty: 1 },
      { name: 'Iced Milo', qty: 1 }
    ]
  },
  {
    id: '1002',
    status: 'preparing',
    pickupTime: '12:45 PM',
    total: 8.00,
    items: [
      { name: 'Chicken Rice', qty: 1 }
    ]
  },
  {
    id: '1003',
    status: 'ready',
    pickupTime: '1:00 PM',
    total: 6.50,
    items: [
      { name: 'Vegetarian Fried Noodles', qty: 1 }
    ]
  }
])

function ordersByStatus(status) {
  return orders.value.filter(order => order.status === status)
}

function updateStatus(orderId, newStatus) {
  const order = orders.value.find(order => order.id === orderId)

  if (order) {
    order.status = newStatus
  }
}

const revenueToday = computed(() => {
  return orders.value.reduce((sum, order) => sum + order.total, 0)
})

const pendingOrders = computed(() => {
  return orders.value.filter(order => order.status !== 'collected').length
})

const topItem = computed(() => {
  const itemCount = {}

  orders.value.forEach(order => {
    order.items.forEach(item => {
      itemCount[item.name] = (itemCount[item.name] || 0) + item.qty
    })
  })

  return Object.entries(itemCount).sort((a, b) => b[1] - a[1])[0]?.[0] || 'N/A'
})
</script>

<style scoped>
.dashboard {
  width: 100%;
}

.page-heading {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  gap: 24px;
}

.page-heading p,
.section-heading p {
  margin-bottom: 0;
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

.open-badge {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 9px 13px;
  border: 1px solid #cfe7d7;
  border-radius: 999px;
  color: var(--brand-dark);
  background: var(--brand-soft);
  font-size: 0.82rem;
  font-weight: 700;
}

.open-badge span {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #27a75f;
}

.analytics-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 16px;
  margin: 32px 0 42px;
}

.stat-card {
  position: relative;
  min-height: 142px;
  padding: 22px;
  border: 1px solid var(--line);
  border-radius: 18px;
  background: var(--surface);
  box-shadow: 0 4px 18px rgba(22, 51, 32, 0.04);
}

.stat-icon {
  position: absolute;
  top: 18px;
  right: 18px;
  display: grid;
  width: 34px;
  height: 34px;
  place-items: center;
  border-radius: 10px;
  color: var(--brand);
  background: var(--brand-soft);
  font-size: 0.68rem;
  font-weight: 800;
}

.stat-card h3 {
  margin: 42px 0 5px;
  font-family: 'DM Sans', sans-serif;
  font-size: 0.78rem;
  color: var(--muted);
  font-weight: 600;
}

.stat-card p {
  margin: 0;
  color: var(--ink);
  font-family: 'Manrope', sans-serif;
  font-size: clamp(1.25rem, 2vw, 1.65rem);
  font-weight: 800;
  line-height: 1.2;
}

.section-heading {
  margin-bottom: 18px;
}

.section-heading h2 {
  margin: 0 0 4px;
  font-size: 1.35rem;
}

.columns {
  display: grid;
  grid-template-columns: repeat(4, minmax(225px, 1fr));
  gap: 14px;
  overflow-x: auto;
  padding-bottom: 8px;
}

.order-column {
  min-width: 225px;
  padding: 12px;
  border-radius: 18px;
  background: rgba(232, 237, 233, 0.68);
}

.column-heading {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 4px 4px 12px;
}

.column-heading h2 {
  margin: 0;
  font-size: 0.92rem;
}

.column-heading span {
  display: grid;
  width: 24px;
  height: 24px;
  place-items: center;
  border-radius: 8px;
  color: var(--muted);
  background: white;
  font-size: 0.72rem;
  font-weight: 800;
}

.order-column :deep(.order-card + .order-card) {
  margin-top: 10px;
}

.empty-state {
  margin: 0;
  padding: 24px 10px;
  border: 1px dashed #ccd5ce;
  border-radius: 13px;
  text-align: center;
  font-size: 0.78rem;
}

@media (max-width: 900px) {
  .analytics-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 560px) {
  .page-heading {
    align-items: flex-start;
    flex-direction: column;
  }

  .analytics-grid {
    grid-template-columns: 1fr;
  }
}
</style>
