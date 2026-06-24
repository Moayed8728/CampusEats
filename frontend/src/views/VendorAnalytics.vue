<template>
  <section class="analytics">
    <span class="eyebrow">Performance</span>
    <h1>Vendor analytics</h1>
    <p class="subtitle">A clear look at today’s orders and revenue.</p>

    <div class="grid">
      <div class="card">
        <span class="card-label">Today</span>
        <h3>Orders Today</h3>
        <p>{{ ordersToday }}</p>
      </div>
      <div class="card">
        <span class="card-label">Sales</span>
        <h3>Revenue Today</h3>
        <p>RM {{ revenueToday.toFixed(2) }}</p>
      </div>
      <div class="card">
        <span class="card-label">Popular</span>
        <h3>Top Item</h3>
        <p>{{ topItem }}</p>
      </div>
      <div class="card">
        <span class="card-label">Busiest</span>
        <h3>Peak Hour</h3>
        <p>{{ peakHour }}</p>
      </div>
    </div>

    <div class="card summary-card">
      <div class="summary-heading"><div><h2>Order status</h2><p>Today’s order distribution</p></div><strong>{{ ordersToday }} total</strong></div>
      <div v-for="item in statusSummary" :key="item.status" class="summary-row">
        <span><i :class="`dot dot--${item.key}`"></i>{{ item.status }}</span>
        <strong>{{ item.count }}</strong>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, ref } from 'vue'

const orders = ref([
  {
    id: '1001',
    status: 'placed',
    total: 10.5,
    pickupTime: '12:30 PM',
    items: [{ name: 'Nasi Goreng Kampung', qty: 1 }]
  },
  {
    id: '1002',
    status: 'preparing',
    total: 8.0,
    pickupTime: '12:45 PM',
    items: [{ name: 'Chicken Rice', qty: 1 }]
  },
  {
    id: '1003',
    status: 'ready',
    total: 6.5,
    pickupTime: '1:00 PM',
    items: [{ name: 'Vegetarian Fried Noodles', qty: 1 }]
  }
])

const ordersToday = computed(() => orders.value.length)

const revenueToday = computed(() => {
  return orders.value.reduce((sum, order) => sum + order.total, 0)
})

const topItem = computed(() => {
  const count = {}

  orders.value.forEach(order => {
    order.items.forEach(item => {
      count[item.name] = (count[item.name] || 0) + item.qty
    })
  })

  return Object.entries(count).sort((a, b) => b[1] - a[1])[0]?.[0] || 'N/A'
})

const peakHour = computed(() => '12:00 PM - 1:00 PM')

const statusSummary = computed(() => {
  const statuses = ['placed', 'preparing', 'ready', 'collected']

  return statuses.map(status => ({
    key: status,
    status: status.charAt(0).toUpperCase() + status.slice(1),
    count: orders.value.filter(order => order.status === status).length
  }))
})
</script>

<style scoped>
.analytics {
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

.grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 16px;
  margin: 32px 0;
}

.card {
  padding: 22px;
  border: 1px solid var(--line);
  border-radius: 18px;
  background: var(--surface);
  box-shadow: 0 4px 18px rgba(22, 51, 32, 0.04);
}

.card-label {
  display: inline-block;
  margin-bottom: 26px;
  padding: 5px 8px;
  border-radius: 7px;
  color: var(--brand);
  background: var(--brand-soft);
  font-size: 0.68rem;
  font-weight: 800;
  text-transform: uppercase;
}

.card h3 {
  margin: 0 0 8px;
  color: var(--muted);
  font-family: 'DM Sans', sans-serif;
  font-size: 0.78rem;
  font-weight: 600;
}

.card p {
  margin: 0;
  color: var(--ink);
  font-family: 'Manrope', sans-serif;
  font-size: clamp(1.2rem, 2vw, 1.6rem);
  font-weight: 800;
  line-height: 1.25;
}

.summary-card {
  max-width: 760px;
}

.summary-heading {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 20px;
  padding-bottom: 18px;
  border-bottom: 1px solid var(--line);
}

.summary-heading h2 {
  margin: 0 0 4px;
  font-size: 1.2rem;
}

.summary-heading p {
  margin: 0;
  font-size: 0.82rem;
}

.summary-heading > strong {
  padding: 6px 10px;
  border-radius: 8px;
  color: var(--brand);
  background: var(--brand-soft);
  font-size: 0.76rem;
}

.summary-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid #edf0ed;
  padding: 15px 2px;
}

.summary-row:last-child {
  border-bottom: 0;
  padding-bottom: 0;
}

.summary-row > span {
  display: flex;
  align-items: center;
  gap: 10px;
  color: var(--muted);
}

.dot {
  width: 9px;
  height: 9px;
  border-radius: 50%;
  background: #d9a927;
}

.dot--preparing { background: #4e7fd0; }
.dot--ready { background: #27a75f; }
.dot--collected { background: #9ba39e; }

@media (max-width: 900px) {
  .grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 520px) {
  .grid {
    grid-template-columns: 1fr;
  }
}
</style>
