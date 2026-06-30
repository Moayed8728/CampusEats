<template>
  <div class="stripe-form">
    <div ref="cardMount" class="card-element"></div>
    <p v-if="error" class="stripe-error">{{ error }}</p>
    <button class="button checkout-button stripe-button" :disabled="processing || !ready || disabled" @click="confirmPayment">
      {{ processing ? 'Processing payment...' : 'Pay' }}
      <span v-if="!processing">→</span>
    </button>
    <small>Test card: 4242 4242 4242 4242</small>
  </div>
</template>

<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue'
import { loadStripe } from '@stripe/stripe-js'
import api from '../services/api'

const props = defineProps({
  amount: {
    type: Number,
    required: true
  },
  disabled: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['payment-success', 'payment-error', 'processing-change'])

const cardMount = ref(null)
const ready = ref(false)
const processing = ref(false)
const error = ref('')

let stripe
let elements
let cardElement

async function confirmPayment() {
  error.value = ''
  if (props.disabled) {
    error.value = 'Choose a pickup time before paying.'
    emit('payment-error', error.value)
    return
  }
  if (!stripe || !cardElement) {
    error.value = 'Stripe is still loading. Please try again.'
    emit('payment-error', error.value)
    return
  }

  processing.value = true
  emit('processing-change', true)
  try {
    const amountSen = Math.round(Number(props.amount) * 100)
    const { data } = await api.post('/payments/create-intent', { amount: amountSen })
    const result = await stripe.confirmCardPayment(data.client_secret, {
      payment_method: { card: cardElement }
    })

    if (result.error) {
      error.value = result.error.message || 'Stripe payment failed. Please try again.'
      emit('payment-error', error.value)
      return
    }
    if (result.paymentIntent?.status !== 'succeeded') {
      error.value = 'Stripe payment was not completed.'
      emit('payment-error', error.value)
      return
    }

    emit('payment-success', {
      payment_status: 'paid',
      payment_reference: result.paymentIntent.id || data.payment_intent_id,
      payment_method: 'stripe'
    })
  } catch (requestError) {
    error.value = requestError.response?.data?.error || 'Stripe payment failed. Please try again.'
    emit('payment-error', error.value)
  } finally {
    processing.value = false
    emit('processing-change', false)
  }
}

onMounted(async () => {
  const publicKey = import.meta.env.VITE_STRIPE_PUBLIC_KEY
  if (!publicKey) {
    error.value = 'Stripe is not configured.'
    return
  }

  try {
    stripe = await loadStripe(publicKey)
    if (!stripe || !cardMount.value) {
      error.value = 'Stripe could not load.'
      return
    }

    elements = stripe.elements()
    cardElement = elements.create('card', {
      hidePostalCode: true,
      style: {
        base: {
          color: '#ffffff',
          fontFamily: 'Inter, system-ui, sans-serif',
          fontSize: '15px',
          '::placeholder': { color: '#9fb0a5' }
        },
        invalid: { color: '#ffd0ca' }
      }
    })
    cardElement.mount(cardMount.value)
    ready.value = true
  } catch {
    error.value = 'Stripe could not load.'
  }
})

onBeforeUnmount(() => {
  if (cardElement) cardElement.destroy()
})
</script>

<style scoped>
.stripe-form{display:grid;gap:12px}.card-element{min-height:46px;padding:14px;border:1px solid #486052;border-radius:11px;background:#21382a}.stripe-button{margin-top:0}.stripe-error{margin:0;color:#ffd0ca;font-size:.78rem}.stripe-form small{display:block;margin-top:0;color:#91a095;text-align:center;font-size:.68rem}
</style>
