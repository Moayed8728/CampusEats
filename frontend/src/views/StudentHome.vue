<template>
  <section>
    <div class="hero">
      <div class="hero-copy">
        <span class="eyebrow">Skip the queue, keep the flavour</span>
        <h1>Your campus cravings,<br><em>ready when you are.</em></h1>
        <p>Order ahead from the best food spots on campus and spend your break actually taking a break.</p>
        <a class="button button--dark" href="#vendors">Explore today’s kitchens <span>↓</span></a>
      </div>
      <div class="hero-art" aria-hidden="true">
        <div class="orbit orbit--one"></div>
        <div class="orbit orbit--two"></div>
        <div class="plate"><span>🍜</span></div>
        <div class="floating-note note--one">Freshly made <b>•</b></div>
        <div class="floating-note note--two">No long queues <b>✓</b></div>
      </div>
    </div>

    <div id="vendors" class="section-heading">
      <div>
        <span class="eyebrow">Open on campus</span>
        <h2>What sounds good?</h2>
      </div>
      <span v-if="!loading && !error" class="result-count">{{ vendors.length }} kitchens</span>
    </div>

    <div v-if="loading" class="state-card">
      <span class="spinner"></span><div><h3>Loading vendors...</h3><p>Finding open kitchens on campus.</p></div>
    </div>

    <div v-else-if="error" class="state-card state-card--error">
      <span>!</span><div><h3>Unable to load vendors.</h3><p>{{ error }}</p></div>
      <button class="button button--small" @click="fetchVendors">Retry</button>
    </div>

    <div v-else-if="vendors.length" class="vendor-grid">
      <article v-for="(vendor, index) in vendors" :key="vendor.id" class="vendor-card">
        <div class="vendor-visual" :class="`vendor-visual--${index % 3}`">
          <span>{{ initials(vendor.name) }}</span>
          <small><i></i> Taking orders</small>
        </div>
        <div class="vendor-body">
          <div><p class="micro-label">Campus kitchen</p><h3>{{ vendor.name }}</h3></div>
          <p>{{ vendor.description || 'Good food, made nearby and ready for pickup.' }}</p>
          <RouterLink class="card-link" :to="`/vendors/${vendor.id}/menu`">
            View menu <span>↗</span>
          </RouterLink>
        </div>
      </article>
    </div>

    <div v-else class="state-card"><span>CE</span><div><h3>No vendors available.</h3><p>Check back in a little while.</p></div></div>
  </section>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import api from '../services/api'

const vendors = ref([])
const loading = ref(true)
const error = ref('')

async function fetchVendors() {
  loading.value = true
  error.value = ''

  try {
    const { data } = await api.get('/vendors', { skipAuth: true })
    vendors.value = data.vendors ?? []
  } catch (requestError) {
    error.value = requestError.response?.data?.error || 'Please make sure the CampusEats API is running.'
  } finally {
    loading.value = false
  }
}

function initials(name = '') {
  return name.split(' ').slice(0, 2).map((word) => word[0]).join('').toUpperCase()
}

onMounted(fetchVendors)
</script>

<style scoped>
.hero { position: relative; display: grid; grid-template-columns: 1.1fr .9fr; min-height: 530px; overflow: hidden; margin-bottom: 76px; border-radius: 32px; color: white; background: #12271b; box-shadow: 0 28px 70px rgba(18,39,27,.18); }
.hero::before { content: ''; position: absolute; inset: 0; opacity: .25; background-image: radial-gradient(rgba(255,255,255,.3) 1px, transparent 1px); background-size: 24px 24px; mask-image: linear-gradient(to right, black, transparent 65%); }
.hero-copy { position: relative; z-index: 1; align-self: center; padding: clamp(36px, 7vw, 78px); }
.hero .eyebrow { color: #b9e9a9; }
.hero h1 { max-width: 680px; color: white; font-size: clamp(2.6rem, 5.5vw, 5.2rem); }
.hero h1 em { color: #d5f6a5; font-family: Georgia, serif; font-weight: 400; }
.hero p { max-width: 580px; margin: 22px 0 32px; color: #c4d0c8; font-size: 1.08rem; line-height: 1.7; }
.hero-art { position: relative; min-height: 430px; background: linear-gradient(145deg, transparent, rgba(140,211,118,.14)); }
.plate { position: absolute; top: 50%; left: 48%; display: grid; width: 250px; height: 250px; place-items: center; transform: translate(-50%,-50%) rotate(-7deg); border: 18px solid #f3f1e8; border-radius: 50%; background: #d8f0c7; box-shadow: 0 28px 55px rgba(0,0,0,.32), inset 0 0 0 3px rgba(0,0,0,.08); }
.plate span { font-size: 7rem; filter: drop-shadow(0 12px 10px rgba(0,0,0,.18)); }
.orbit { position: absolute; border: 1px solid rgba(213,246,165,.3); border-radius: 50%; }
.orbit--one { width: 380px; height: 380px; top: 50%; left: 48%; transform: translate(-50%,-50%); }
.orbit--two { width: 500px; height: 500px; top: 50%; left: 48%; transform: translate(-50%,-50%); }
.floating-note { position: absolute; z-index: 2; padding: 13px 16px; border: 1px solid rgba(255,255,255,.35); border-radius: 14px; color: #203225; background: rgba(255,255,255,.92); box-shadow: var(--shadow); font-size: .82rem; font-weight: 700; backdrop-filter: blur(10px); }
.floating-note b { color: #3c9b58; }.note--one { top: 18%; left: 1%; transform: rotate(-5deg); }.note--two { right: 8%; bottom: 20%; transform: rotate(4deg); }
.section-heading { display: flex; align-items: flex-end; justify-content: space-between; margin-bottom: 24px; }.section-heading h2 { margin: 0; font-size: clamp(1.8rem,4vw,2.7rem); }.result-count { color: var(--muted); font-size: .88rem; font-weight: 700; }
.vendor-grid { display: grid; grid-template-columns: repeat(3,minmax(0,1fr)); gap: 20px; }
.vendor-card { overflow: hidden; border: 1px solid var(--line); border-radius: 23px; background: var(--surface); box-shadow: 0 8px 28px rgba(22,51,32,.06); transition: transform .2s ease, box-shadow .2s ease; }.vendor-card:hover { transform: translateY(-5px); box-shadow: 0 20px 45px rgba(22,51,32,.12); }
.vendor-visual { position: relative; display: grid; height: 180px; place-items: center; background: #e2efdb; }.vendor-visual--1 { background: #f5e6ca; }.vendor-visual--2 { background: #dcebf0; }.vendor-visual > span { display: grid; width: 88px; height: 88px; place-items: center; border-radius: 28px; color: white; background: #183d28; font-family: Manrope,sans-serif; font-size: 1.6rem; font-weight: 800; transform: rotate(-7deg); box-shadow: 0 15px 30px rgba(0,0,0,.15); }.vendor-visual small { position: absolute; right: 14px; bottom: 14px; padding: 7px 10px; border-radius: 999px; color: #285a38; background: rgba(255,255,255,.9); font-size: .7rem; font-weight: 800; }.vendor-visual small i { display: inline-block; width: 7px; height: 7px; margin-right: 5px; border-radius: 50%; background: #38a45c; }
.vendor-body { padding: 22px; }.vendor-body h3 { margin: 3px 0 12px; font-size: 1.25rem; }.vendor-body > p { min-height: 44px; margin-bottom: 20px; line-height: 1.55; }.micro-label { margin: 0; color: var(--brand); font-size: .68rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }.card-link { display: flex; justify-content: space-between; padding-top: 16px; border-top: 1px solid var(--line); color: var(--ink); font-weight: 800; text-decoration: none; }.card-link span { color: var(--brand); }
.skeleton-card { height: 360px; padding: 20px; }.skeleton-card span,.skeleton-card i { display: block; border-radius: 12px; background: linear-gradient(90deg,#edf0ed,#f8faf8,#edf0ed); background-size: 200%; animation: shimmer 1.4s infinite; }.skeleton-card span { height: 175px; }.skeleton-card i { height: 18px; margin-top: 20px; }.skeleton-card i:last-child { width: 60%; } @keyframes shimmer { to { background-position: -200% 0; } }
@media(max-width:900px){.hero{grid-template-columns:1fr}.hero-art{display:none}.vendor-grid{grid-template-columns:repeat(2,1fr)}}
@media(max-width:600px){.hero{min-height:auto;margin-bottom:52px;border-radius:23px}.hero-copy{padding:40px 25px}.vendor-grid{grid-template-columns:1fr}.section-heading{align-items:flex-start;flex-direction:column;gap:8px}}
</style>
