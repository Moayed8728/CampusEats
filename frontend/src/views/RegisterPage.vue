<template>
  <section class="auth-page">
    <div class="auth-message"><span class="eyebrow">Join the table</span><h1>One account.<br><em>Much better breaks.</em></h1><p>Create your CampusEats account and turn long food queues into a thing of the past.</p><div class="auth-quote"><span>✦</span><p>Order ahead. Pick up fresh. Get back to the good part of campus life.</p></div></div>
    <form class="auth-card" @submit.prevent="submit"><div><p class="step-label">Student account</p><h2>Create account</h2></div><label>Full name<input v-model.trim="form.name" type="text" autocomplete="name" placeholder="Your name" required></label><label>Email<input v-model.trim="form.email" type="email" autocomplete="email" placeholder="you@campus.edu" required></label><label>Password<input v-model="form.password" type="password" autocomplete="new-password" minlength="8" placeholder="At least 8 characters" required></label><p class="auth-note">Vendor access requires approval after sign up.</p><p v-if="error" class="form-error">{{ error }}</p><button class="button auth-button" :disabled="loading">{{ loading ? 'Creating account…' : 'Create account' }} <span v-if="!loading">→</span></button><p class="auth-switch">Already have an account? <RouterLink to="/login">Sign in</RouterLink></p></form>
  </section>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
const auth=useAuthStore();const router=useRouter();const form=reactive({name:'',email:'',password:''});const loading=ref(false);const error=ref('')
async function submit(){loading.value=true;error.value='';try{await auth.register({...form});await router.push({name:'login',query:{registered:'1'}})}catch(requestError){error.value=requestError.response?.data?.error||'We couldn’t create the account. Please try again.'}finally{loading.value=false}}
</script>

<style scoped src="../styles/auth.css"></style>
