<template>
  <div class="review-card">
    <div><span class="eyebrow">Review</span><h2>How was your pickup?</h2><p>Share a quick note for this vendor.</p></div>
    <form v-if="!submitted" @submit.prevent="submitReview">
      <label>Rating
        <select v-model.number="rating">
          <option v-for="value in [5,4,3,2,1]" :key="value" :value="value">{{ value }} stars</option>
        </select>
      </label>
      <label>Comment
        <textarea v-model.trim="comment" rows="3" placeholder="Fast service and good food."></textarea>
      </label>
      <p v-if="error" class="form-error">{{ error }}</p>
      <button class="button" :disabled="loading">{{ loading ? 'Submitting...' : 'Submit review' }}</button>
    </form>
    <div v-else class="success-message"><strong>Review submitted.</strong><p>Thanks for helping the campus eat better.</p></div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import api from '../services/api'
const props = defineProps({ vendorId: { type: String, required: true } })
const rating = ref(5); const comment = ref(''); const loading = ref(false); const error = ref(''); const submitted = ref(false)
async function submitReview(){loading.value=true;error.value='';try{await api.post('/reviews',{vendor_id:props.vendorId,rating:rating.value,comment:comment.value});submitted.value=true}catch(requestError){error.value=requestError.response?.data?.error||'Review could not be submitted.'}finally{loading.value=false}}
</script>

<style scoped>
.review-card{margin-top:24px;padding:clamp(22px,4vw,30px);border:1px solid var(--line);border-radius:23px;background:white;box-shadow:0 10px 35px rgba(22,51,32,.06)}.review-card h2{margin:6px 0 4px;font-size:1.25rem}.review-card p{margin-bottom:18px}form{display:grid;gap:14px}label{display:grid;gap:7px;color:var(--muted);font-size:.78rem;font-weight:800}select,textarea{width:100%;padding:12px;border:1px solid var(--line);border-radius:11px;background:#fbfdfb;color:var(--ink)}textarea{resize:vertical}.form-error{margin:0;color:#9c443c;font-size:.8rem}.success-message{padding:16px;border-radius:15px;background:var(--brand-soft)}.success-message strong{display:block;margin-bottom:4px;color:var(--brand-dark)}.success-message p{margin:0}
</style>
