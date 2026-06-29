<template>
  <section class="cart-page">
    <div class="page-intro"><span class="eyebrow">Almost yours</span><h1>Your pickup bag</h1><p>Check the details, choose a time, and we’ll send it to the kitchen.</p></div>

    <div v-if="!cart.items.length" class="empty-cart">
      <span>CE</span><h2>Your cart is empty.</h2><p>Add something delicious from a campus kitchen.</p><RouterLink class="button" to="/">Browse vendors</RouterLink>
    </div>

    <div v-else class="cart-layout">
      <div class="cart-list">
        <article v-for="item in cart.items" :key="item.id" class="cart-item">
          <div class="item-icon">{{ foodEmoji(item.name) }}</div>
          <div class="item-info"><h3>{{ item.name }}</h3><p>RM {{ money(item.price) }} each</p><button class="remove-link" @click="cart.removeItem(item.id)">Remove</button></div>
          <div class="quantity" aria-label="Quantity controls"><button @click="cart.decreaseQty(item.id)">−</button><strong>{{ item.qty }}</strong><button @click="cart.increaseQty(item.id)">+</button></div>
          <strong class="line-price">RM {{ money(Number(item.price) * item.qty) }}</strong>
        </article>
      </div>

      <aside class="checkout-card">
        <span class="step-label">Pickup details</span><h2>When should it be ready?</h2>
        <label for="pickup">Pickup date and time</label>
        <input id="pickup" v-model="pickupAt" type="datetime-local" :min="minimumPickup" required>
        <div class="bill-row"><span>{{ cart.count }} {{ cart.count === 1 ? 'item' : 'items' }}</span><strong>RM {{ money(cart.total) }}</strong></div>
        <div class="bill-row bill-total"><span>Total</span><strong>RM {{ money(cart.total) }}</strong></div>
        <p v-if="error" class="form-error">{{ error }}</p>
        <button class="button checkout-button" :disabled="submitting" @click="placeOrder">
          {{ submitting ? 'Sending to kitchen…' : 'Place order' }} <span v-if="!submitting">→</span>
        </button>
        <small>Prices are securely confirmed by the kitchen.</small>
      </aside>
    </div>
  </section>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useCartStore } from '../stores/cart'
import api from '../services/api'

const cart = useCartStore()
const router = useRouter()
const pickupAt = ref('')
const submitting = ref(false)
const error = ref('')

const minimumPickup = computed(() => {
  const date = new Date(Date.now() + 15 * 60 * 1000)
  date.setMinutes(date.getMinutes() - date.getTimezoneOffset())
  return date.toISOString().slice(0, 16)
})

async function placeOrder() {
  error.value = ''
  if (!pickupAt.value) {
    error.value = 'Choose a pickup time before placing your order.'
    return
  }

  submitting.value = true
  try {
    const payload = {
      vendor_id: cart.vendorId,
      pickup_at: `${pickupAt.value.replace('T', ' ')}:00`,
      items: cart.items.map((item) => ({ menu_item_id: item.id, qty: item.qty }))
    }
    const { data } = await api.post('/orders', payload)
    cart.clearCart()
    await router.push(`/orders/${data.order_id}`)
  } catch (requestError) {
    if (requestError.response?.status === 401) {
      error.value = 'Your session expired. Please sign in again.'
    } else {
      error.value = requestError.response?.data?.error || 'We couldn’t place the order. Please try again.'
    }
  } finally {
    submitting.value = false
  }
}

function money(value) { return Number(value).toFixed(2) }
function foodEmoji(name = '') { return /rice|nasi/i.test(name) ? '🍛' : /noodle|mee/i.test(name) ? '🍜' : /milo|tea|drink/i.test(name) ? '🥤' : '🍽️' }
</script>

<style scoped>
.page-intro{margin-bottom:34px}.page-intro p{margin-bottom:0}.cart-layout{display:grid;grid-template-columns:minmax(0,1.5fr) minmax(310px,.7fr);gap:28px;align-items:start}.cart-list{display:grid;gap:12px}.cart-item{display:grid;grid-template-columns:auto 1fr auto auto;gap:18px;align-items:center;padding:18px;border:1px solid var(--line);border-radius:18px;background:white}.item-icon{display:grid;width:66px;height:66px;place-items:center;border-radius:17px;background:#edf5e8;font-size:2rem}.item-info h3{margin:0 0 4px;font-size:1rem}.item-info p{margin:0;font-size:.8rem}.remove-link{margin-top:8px;padding:0;border:0;color:#9a4a43;background:none;cursor:pointer;font-size:.72rem;font-weight:700}.quantity{display:flex;align-items:center;gap:10px;padding:5px;border:1px solid var(--line);border-radius:999px}.quantity button{display:grid;width:28px;height:28px;place-items:center;border:0;border-radius:50%;background:#f0f3f0;cursor:pointer;font-size:1rem}.quantity button:hover{background:var(--brand-soft)}.quantity strong{min-width:14px;text-align:center;font-size:.85rem}.line-price{min-width:78px;text-align:right;font-family:Manrope,sans-serif;font-size:.9rem}.checkout-card{position:sticky;top:100px;padding:26px;border:1px solid var(--line);border-radius:22px;background:#15271b;box-shadow:0 20px 48px rgba(20,39,27,.2)}.checkout-card h2{margin:8px 0 24px;color:white;font-size:1.35rem}.step-label{color:#bce6a9;font-size:.7rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase}.checkout-card label{display:block;margin-bottom:8px;color:#c6d0c9;font-size:.76rem;font-weight:700}.checkout-card input{width:100%;padding:13px;border:1px solid #486052;border-radius:11px;color:white;background:#21382a;color-scheme:dark}.bill-row{display:flex;justify-content:space-between;margin-top:24px;padding-bottom:13px;border-bottom:1px solid #384d40;color:#aebbb2;font-size:.84rem}.bill-row strong{color:white}.bill-total{margin-top:13px;border:0;color:white;font-size:1rem}.bill-total strong{font-size:1.25rem}.checkout-button{width:100%;margin-top:18px;justify-content:space-between}.checkout-card>small{display:block;margin-top:12px;color:#91a095;text-align:center;font-size:.68rem}.form-error{margin:10px 0 0;color:#ffd0ca;font-size:.78rem}.empty-cart{display:grid;max-width:620px;margin:60px auto;padding:55px 30px;place-items:center;border:1px dashed #cbd5cd;border-radius:25px;text-align:center;background:rgba(255,255,255,.65)}.empty-cart>span{display:grid;width:72px;height:72px;margin-bottom:20px;place-items:center;border-radius:50%;color:var(--brand);background:var(--brand-soft);font-size:2rem}.empty-cart h2{margin-bottom:7px}.empty-cart .button{margin-top:14px}
@media(max-width:850px){.cart-layout{grid-template-columns:1fr}.checkout-card{position:static}}@media(max-width:580px){.cart-item{grid-template-columns:auto 1fr}.quantity{justify-self:start}.line-price{justify-self:end}.item-icon{width:55px;height:55px}}
</style>
