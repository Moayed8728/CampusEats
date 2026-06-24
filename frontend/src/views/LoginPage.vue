<template>
  <section class="auth-page">
    <div class="auth-message"><span class="eyebrow">Welcome back</span><h1>Good food is<br><em>closer than ever.</em></h1><p>Sign in, pick your kitchen, and skip straight past the queue.</p><div class="auth-quote"><span>“</span><p>Built for busy campus days and very serious lunch decisions.</p></div></div>
    <form class="auth-card" @submit.prevent="submit"><div><p class="step-label">CampusEats account</p><h2>Sign in</h2></div><label>Email<input v-model.trim="email" type="email" autocomplete="email" placeholder="you@campus.edu" required></label><label>Password<input v-model="password" type="password" autocomplete="current-password" placeholder="At least 8 characters" required></label><p v-if="error" class="form-error">{{ error }}</p><button class="button auth-button" :disabled="loading">{{ loading ? 'Signing in…' : 'Sign in' }} <span v-if="!loading">→</span></button><p class="auth-switch">New to CampusEats? <RouterLink to="/register">Create an account</RouterLink></p></form>
  </section>
</template>

<script setup>
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
const auth=useAuthStore();const router=useRouter();const route=useRoute();const email=ref('');const password=ref('');const loading=ref(false);const error=ref('')
async function submit(){loading.value=true;error.value='';try{const user=await auth.login(email.value,password.value);const fallback=user.role==='vendor'?'/vendor/dashboard':user.role==='admin'?'/admin':'/';await router.push(route.query.redirect||fallback)}catch(requestError){error.value=requestError.response?.data?.error||'Sign in failed. Check your details and try again.'}finally{loading.value=false}}
</script>

<style scoped src="../styles/auth.css"></style>
