import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import api from '../services/api'

function savedUser() {
  try {
    return JSON.parse(localStorage.getItem('user'))
  } catch {
    return null
  }
}

export const useAuthStore = defineStore('auth', () => {
  const user = ref(savedUser())
  const token = ref(localStorage.getItem('token'))

  const isAuthenticated = computed(() => Boolean(token.value))
  const role = computed(() => user.value?.role ?? null)

  async function login(email, password) {
    const { data } = await api.post('/auth/login', { email, password })
    token.value = data.token
    user.value = data.user
    localStorage.setItem('token', data.token)
    localStorage.setItem('user', JSON.stringify(data.user))
    return data.user
  }

  async function register(payload) {
    const { data } = await api.post('/auth/register', payload)
    return data.user
  }

  function logout() {
    token.value = null
    user.value = null
    localStorage.removeItem('token')
    localStorage.removeItem('user')
  }

  return { user, token, isAuthenticated, role, login, register, logout }
})
