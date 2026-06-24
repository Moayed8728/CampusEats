<template>
  <div class="timeline">
    <div
      v-for="status in statuses"
      :key="status"
      class="timeline-item"
      :class="{ active: isActive(status) }"
    >
      <span class="dot"></span>
      <span>{{ formatStatus(status) }}</span>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  currentStatus: {
    type: String,
    required: true
  }
})

const statuses = ['placed', 'preparing', 'ready', 'collected']

function isActive(status) {
  return statuses.indexOf(status) <= statuses.indexOf(props.currentStatus)
}

function formatStatus(status) {
  return status.charAt(0).toUpperCase() + status.slice(1)
}
</script>

<style scoped>
.timeline {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  margin: 16px 0 0;
  padding: 26px 0;
  border-top: 1px solid var(--line);
  border-bottom: 1px solid var(--line);
}

.timeline-item {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 9px;
  color: #9aa29c;
  font-size: 0.76rem;
  font-weight: 600;
}

.timeline-item:not(:last-child)::after {
  content: '';
  position: absolute;
  z-index: 0;
  top: 7px;
  left: calc(50% + 12px);
  width: calc(100% - 24px);
  height: 2px;
  background: #dce2dd;
}

.timeline-item.active {
  color: var(--brand-dark);
  font-weight: 700;
}

.timeline-item.active:not(:last-child)::after {
  background: #83bf99;
}

.dot {
  position: relative;
  z-index: 1;
  display: block;
  width: 14px;
  height: 14px;
  border: 3px solid white;
  border-radius: 50%;
  background: #b9c0bb;
  box-shadow: 0 0 0 2px #dce2dd;
}

.timeline-item.active .dot {
  background: var(--brand);
  border-color: white;
  box-shadow: 0 0 0 2px #83bf99;
}

@media (max-width: 480px) {
  .timeline {
    grid-template-columns: 1fr;
    gap: 0;
  }

  .timeline-item {
    min-height: 50px;
    flex-direction: row;
    align-items: flex-start;
  }

  .timeline-item:not(:last-child)::after {
    top: 14px;
    left: 7px;
    width: 2px;
    height: calc(100% - 14px);
  }
}
</style>
