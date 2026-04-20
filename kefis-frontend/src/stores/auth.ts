import { defineStore } from 'pinia'
import api from '../services/api'

interface User {
    id:        number
    name:      string
    email:     string
    role:      string
    branch_id: number | null
}

interface AuthState {
    token: string | null
    user:  User | null
}

export const useAuthStore = defineStore('auth', {
    state: (): AuthState => ({
        token: localStorage.getItem('token') || null,
        user:  JSON.parse(localStorage.getItem('user') || 'null'),
    }),

    getters: {
        isLoggedIn:  (state): boolean         => !!state.token,
        isManager:   (state): boolean         => state.user?.role === 'manager',
        isShopkeeper:(state): boolean         => state.user?.role === 'shopkeeper',
        branchId:    (state): number | null   => state.user?.branch_id ?? null,
        currentUser: (state): User | null     => state.user,
    },

    actions: {
        async login(credentials: { email: string; password: string }) {
            const response = await api.post('/auth/login', credentials)

            this.token = response.data.access_token
            this.user  = response.data.user

            localStorage.setItem('token', this.token!)
            localStorage.setItem('user',  JSON.stringify(this.user))
        },

        async logout() {
            try {
                await api.post('/auth/logout')
            } catch {
                // silently fail if token already expired
            } finally {
                this.token = null
                this.user  = null
                localStorage.removeItem('token')
                localStorage.removeItem('user')
            }
        },

        async fetchMe() {
            const response = await api.get('/auth/me')
            this.user = response.data
            localStorage.setItem('user', JSON.stringify(this.user))
        },
    },
})