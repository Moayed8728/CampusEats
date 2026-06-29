<template>
  <div class="app-shell">
    <header class="navbar">
      <RouterLink class="brand" to="/"><span class="brand-mark"><i></i><i></i></span><span>CampusEats</span></RouterLink>
      <button class="menu-toggle" aria-label="Toggle navigation" @click="menuOpen=!menuOpen">{{ menuOpen?'×':'☰' }}</button>
      <nav :class="{open:menuOpen}" aria-label="Main navigation" @click="menuOpen=false">
        <RouterLink to="/">Home</RouterLink>
        <RouterLink v-if="auth.role==='student'" to="/cart">Cart <span v-if="cart.count" class="nav-count">{{ cart.count }}</span></RouterLink>
        <template v-if="auth.role==='vendor'"><RouterLink to="/vendor/dashboard">Dashboard</RouterLink><RouterLink to="/vendor/analytics">Analytics</RouterLink></template>
        <template v-if="auth.role==='admin'"><RouterLink to="/admin">Dashboard</RouterLink><RouterLink to="/admin/users">Users</RouterLink><RouterLink to="/admin/vendors">Vendors</RouterLink><RouterLink to="/admin/orders">Orders</RouterLink></template>
      </nav>
      <div class="account-actions">
        <template v-if="auth.isAuthenticated"><span class="user-chip"><i>{{ userInitial }}</i><span><small>{{ auth.role }}</small>{{ auth.user?.name }}</span></span><button class="logout-button" @click="logout">Logout</button></template>
        <template v-else><RouterLink class="login-link" to="/login">Log in</RouterLink><RouterLink class="button button--small" to="/register">Get started</RouterLink></template>
      </div>
    </header>
    <main class="container"><RouterView /></main>
    <footer><RouterLink class="brand footer-brand" to="/"><span class="brand-mark"><i></i><i></i></span><span>CampusEats</span></RouterLink><p>Made for better breaks on campus.</p><small>Order ahead. Pick up happy.</small></footer>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from './stores/auth'
import { useCartStore } from './stores/cart'
const auth=useAuthStore();const cart=useCartStore();const router=useRouter();const menuOpen=ref(false);const userInitial=computed(()=>auth.user?.name?.[0]?.toUpperCase()||'?')
function logout(){auth.logout();cart.clearCart();router.push('/login')}
</script>

<style scoped>
.navbar{position:sticky;top:0;z-index:10;min-height:76px;padding:12px max(24px,calc((100vw - 1280px)/2));display:flex;align-items:center;gap:34px;background:rgba(248,250,247,.9);border-bottom:1px solid rgba(220,228,221,.9);backdrop-filter:blur(15px)}.brand{display:flex;align-items:center;gap:10px;color:var(--ink);font-family:Manrope,sans-serif;font-size:1.12rem;font-weight:800;text-decoration:none}.brand-mark{position:relative;display:block;width:39px;height:39px;overflow:hidden;border-radius:13px;background:var(--ink);box-shadow:0 7px 16px rgba(23,34,27,.18)}.brand-mark i{position:absolute;width:19px;height:11px;border:3px solid #d5f6a5;border-top:0;border-radius:0 0 16px 16px}.brand-mark i:first-child{top:10px;left:5px;transform:rotate(35deg)}.brand-mark i:last-child{right:5px;bottom:9px;transform:rotate(-35deg)}nav{display:flex;align-items:center;gap:4px}nav a{padding:9px 12px;border-radius:10px;color:var(--muted);text-decoration:none;font-size:.88rem;font-weight:700}nav a:hover,nav a.router-link-active{color:var(--brand-dark);background:var(--brand-soft)}.nav-count{display:inline-grid;width:20px;height:20px;margin-left:4px;place-items:center;border-radius:50%;color:white;background:var(--brand);font-size:.65rem}.account-actions{display:flex;align-items:center;gap:10px;margin-left:auto}.login-link,.logout-button{padding:9px;border:0;color:var(--ink);background:none;cursor:pointer;text-decoration:none;font-size:.84rem;font-weight:800}.logout-button:hover,.login-link:hover{color:var(--brand)}.user-chip{display:flex;align-items:center;gap:8px;padding-right:7px;color:var(--ink);font-size:.78rem;font-weight:700}.user-chip i{display:grid;width:34px;height:34px;place-items:center;border-radius:50%;color:white;background:var(--brand);font-style:normal}.user-chip small{display:block;color:var(--muted);font-size:.58rem;text-transform:uppercase}.container{width:min(100%,1328px);min-height:calc(100vh - 200px);margin:0 auto;padding:42px 24px 72px}.menu-toggle{display:none;margin-left:auto;border:0;background:none;font-size:1.25rem}footer{display:grid;grid-template-columns:1fr auto 1fr;align-items:center;gap:20px;padding:30px max(24px,calc((100vw - 1280px)/2));border-top:1px solid var(--line);background:white}.footer-brand{font-size:.95rem}.footer-brand .brand-mark{width:32px;height:32px}.footer-brand .brand-mark i{transform:scale(.8)}footer p{margin:0;font-size:.8rem}footer>small{justify-self:end;color:var(--muted);font-size:.72rem}
@media(max-width:760px){.navbar{padding:12px 16px;gap:12px}.menu-toggle{display:block}nav{position:absolute;top:69px;left:12px;right:12px;display:none;padding:10px;flex-direction:column;align-items:stretch;border:1px solid var(--line);border-radius:15px;background:white;box-shadow:var(--shadow)}nav.open{display:flex}.account-actions>.user-chip span{display:none}.account-actions .button{display:none}.container{padding:28px 16px 55px}footer{grid-template-columns:1fr;text-align:center}footer>*{justify-self:center!important}}
</style>
