import { computed, ref } from 'vue'
import { defineStore } from 'pinia'

export const useCartStore = defineStore('cart', () => {
  const items = ref([])
  const vendorId = ref(null)

  const total = computed(() => items.value.reduce(
    (sum, item) => sum + Number(item.price) * item.qty,
    0
  ))
  const count = computed(() => items.value.reduce((sum, item) => sum + item.qty, 0))

  function addItem(item) {
    const itemVendorId = item.vendor_id ?? item.vendorId

    if (vendorId.value && itemVendorId !== vendorId.value) {
      return {
        ok: false,
        message: 'Your cart already contains food from another vendor. Clear it first to switch.'
      }
    }

    vendorId.value = itemVendorId
    const existing = items.value.find((cartItem) => cartItem.id === item.id)

    if (existing) {
      existing.qty += 1
    } else {
      items.value.push({ ...item, qty: 1 })
    }

    return { ok: true }
  }

  function removeItem(id) {
    items.value = items.value.filter((item) => item.id !== id)
    if (!items.value.length) vendorId.value = null
  }

  function increaseQty(id) {
    const item = items.value.find((cartItem) => cartItem.id === id)
    if (item) item.qty += 1
  }

  function decreaseQty(id) {
    const item = items.value.find((cartItem) => cartItem.id === id)
    if (!item) return
    if (item.qty === 1) removeItem(id)
    else item.qty -= 1
  }

  function clearCart() {
    items.value = []
    vendorId.value = null
  }

  return {
    items,
    vendorId,
    total,
    count,
    addItem,
    removeItem,
    increaseQty,
    decreaseQty,
    clearCart
  }
})
