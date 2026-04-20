<template>
    <div class="d-flex min-vh-100">

        <!-- Sidebar -->
        <div class="bg-dark text-white d-flex flex-column" style="width: 250px; min-height: 100vh;">

            <!-- Logo -->
            <div class="p-3 border-bottom border-secondary">
                <h5 class="fw-bold text-white mb-0">🛒 KEFIS</h5>
                <small class="text-secondary">Mini Supermarket</small>
            </div>

            <!-- Navigation -->
            <nav class="flex-grow-1 p-2 mt-2">

                <router-link
                    to="/dashboard"
                    class="nav-link text-white py-2 px-3 rounded mb-1 d-flex align-items-center gap-2"
                    active-class="bg-primary"
                >
                    📊 Dashboard
                </router-link>

                <router-link
                    to="/purchases"
                    class="nav-link text-white py-2 px-3 rounded mb-1 d-flex align-items-center gap-2"
                    active-class="bg-primary"
                >
                    🛒 Purchases
                </router-link>

                <router-link
                    to="/sales"
                    class="nav-link text-white py-2 px-3 rounded mb-1 d-flex align-items-center gap-2"
                    active-class="bg-primary"
                >
                    💰 Sales
                </router-link>

                <router-link
                    to="/transfers"
                    class="nav-link text-white py-2 px-3 rounded mb-1 d-flex align-items-center gap-2"
                    active-class="bg-primary"
                >
                    🔄 Transfers
                </router-link>

                <router-link
                    to="/stock"
                    class="nav-link text-white py-2 px-3 rounded mb-1 d-flex align-items-center gap-2"
                    active-class="bg-primary"
                >
                    📦 Stock Report
                </router-link>

                <!-- Manager only links -->
                <template v-if="authStore.isManager">
                    <hr class="border-secondary my-2" />
                    <div class="text-secondary small px-3 mb-1">Management</div>

                    <router-link
                        to="/branches"
                        class="nav-link text-white py-2 px-3 rounded mb-1 d-flex align-items-center gap-2"
                        active-class="bg-primary"
                    >
                        🏢 Branches
                    </router-link>

                    <router-link
                        to="/products"
                        class="nav-link text-white py-2 px-3 rounded mb-1 d-flex align-items-center gap-2"
                        active-class="bg-primary"
                    >
                        🏷️ Products
                    </router-link>
                </template>

            </nav>

            <!-- User info at bottom -->
            <div class="p-3 border-top border-secondary">
                <div class="text-secondary small mb-1">Logged in as</div>
                <div class="fw-semibold text-white small">{{ authStore.user?.name }}</div>
                <div class="small text-secondary">
                    {{ authStore.isManager ? 'All Branches' : branchName }}
                </div>
                <span class="badge bg-primary mt-1 text-capitalize">{{ authStore.user?.role }}</span>
            </div>

        </div>

        <!-- Main content area -->
        <div class="flex-grow-1 d-flex flex-column">

            <!-- Top Navbar -->
            <nav class="navbar bg-white border-bottom px-4 py-2 shadow-sm">
                <span class="fw-semibold text-dark">{{ pageTitle }}</span>
                <div class="ms-auto d-flex align-items-center gap-3">
                    <span class="text-muted small">
                        {{ authStore.isManager ? 'All Branches' : branchName }}
                    </span>
                    <button
                        class="btn btn-outline-danger btn-sm"
                        @click="handleLogout"
                    >
                        🚪 Logout
                    </button>
                </div>
            </nav>

            <!-- Page Content -->
            <div class="flex-grow-1 p-4 bg-light overflow-auto">
                <slot />
            </div>

        </div>

    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const router    = useRouter()
const route     = useRoute()
const authStore = useAuthStore()

const branchName = computed(() => {
    const branches: Record<number, string> = {
        1: 'Branch Nairobi',
        2: 'Branch Mombasa',
    }
    return authStore.user?.branch_id
        ? branches[authStore.user.branch_id] ?? 'Unknown Branch'
        : 'All Branches'
})

const pageTitle = computed(() => {
    const titles: Record<string, string> = {
        '/dashboard': '📊 Dashboard',
        '/branches':  '🏢 Branches',
        '/products':  '🏷️ Products',
        '/purchases': '🛒 Purchases',
        '/sales':     '💰 Sales',
        '/transfers': '🔄 Transfers',
        '/stock':     '📦 Stock Report',
    }
    return titles[route.path] || '📊 Dashboard'
})

const handleLogout = async () => {
    await authStore.logout()
    router.push('/login')
}
</script>