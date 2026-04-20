<template>
    <div class="min-vh-100 d-flex align-items-center justify-content-center bg-light">
        <div class="card shadow-sm p-4" style="width: 100%; max-width: 420px;">

            <div class="text-center mb-4">
                <h4 class="fw-bold text-primary mb-1">🛒 KEFIS</h4>
                <p class="text-muted small mb-0">Mini Supermarket Management System</p>
                <hr />
            </div>

            <div v-if="error" class="alert alert-danger py-2 small">
                {{ error }}
            </div>

            <div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Email Address</label>
                    <input
                        v-model="form.email"
                        type="email"
                        class="form-control"
                        placeholder="Enter your email"
                        @keyup.enter="handleLogin"
                    />
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">Password</label>
                    <input
                        v-model="form.password"
                        type="password"
                        class="form-control"
                        placeholder="Enter your password"
                        @keyup.enter="handleLogin"
                    />
                </div>

                <button
                    class="btn btn-primary w-100 mb-3"
                    :disabled="loading"
                    @click="handleLogin"
                >
                    <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
                    {{ loading ? 'Signing in...' : 'Sign In' }}
                </button>

                <!-- Go back to home -->
                <button
                    class="btn btn-outline-secondary w-100"
                    @click="router.push('/')"
                >
                    ← Back to Home
                </button>
            </div>

            <div class="mt-4 p-3 bg-light rounded small text-muted">
                <p class="mb-1 fw-semibold">Test Credentials:</p>
                <p class="mb-0">👤 manager@kefis.com / manager123</p>
                <p class="mb-0">👤 shop1@kefis.com / shop1123</p>
                <p class="mb-0">👤 shop2@kefis.com / shop2123</p>
            </div>

        </div>
    </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'

const router    = useRouter()
const authStore = useAuthStore()

const form = ref({
    email:    '',
    password: '',
})

const loading = ref(false)
const error   = ref('')

const handleLogin = async () => {
    if (!form.value.email || !form.value.password) {
        error.value = 'Please enter your email and password.'
        return
    }

    loading.value = true
    error.value   = ''

    try {
        await authStore.login(form.value)
        router.push('/dashboard')

    } catch (err: any) {
        error.value = err.response?.data?.message || 'Invalid credentials. Please try again.'
    } finally {
        loading.value = false
    }
}
</script>