import { beforeEach, describe, expect, it } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { useCartStore } from '../cart'

describe('cart store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('calculates the total price across multiple items and quantities', () => {
    const cart = useCartStore()

    cart.addItem({ id: 'item-1', vendor_id: 'vendor-1', price: 8.5 })
    cart.addItem({ id: 'item-2', vendor_id: 'vendor-1', price: 4 })
    cart.increaseQty('item-2') // item-2 qty becomes 2

    expect(cart.total).toBeCloseTo(16.5) // 8.5 + (4 * 2)
    expect(cart.count).toBe(3) // 1 + 2
  })

  it('blocks adding an item from a second vendor while the cart is not empty', () => {
    const cart = useCartStore()

    cart.addItem({ id: 'item-1', vendor_id: 'vendor-1', price: 10 })
    const result = cart.addItem({ id: 'item-2', vendor_id: 'vendor-2', price: 5 })

    expect(result.ok).toBe(false)
    expect(cart.items).toHaveLength(1)
    expect(cart.total).toBe(10)
  })

  it('removes the item and resets the vendor lock once quantity drops to zero', () => {
    const cart = useCartStore()

    cart.addItem({ id: 'item-1', vendor_id: 'vendor-1', price: 6 })
    cart.decreaseQty('item-1') // qty was 1, should remove the item entirely

    expect(cart.items).toHaveLength(0)
    expect(cart.vendorId).toBeNull()

    // vendor lock is lifted, so a different vendor can now be added
    const result = cart.addItem({ id: 'item-2', vendor_id: 'vendor-2', price: 3 })
    expect(result.ok).toBe(true)
  })
})