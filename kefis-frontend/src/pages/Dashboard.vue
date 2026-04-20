<template>
    <DashboardLayout>
        <div class="row g-4">

            <!-- Stats Cards -->
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="text-muted small">Total Branches</div>
                        <div class="fs-4 fw-bold text-primary">{{ stats.branches }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="text-muted small">Total Products</div>
                        <div class="fs-4 fw-bold text-success">{{ stats.products }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="text-muted small">Total Purchases</div>
                        <div class="fs-4 fw-bold text-warning">{{ stats.purchases }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="text-muted small">Total Sales</div>
                        <div class="fs-4 fw-bold text-danger">{{ stats.sales }}</div>
                    </div>
                </div>
            </div>

            <!-- Welcome Message -->
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h5 class="fw-bold">Welcome, {{ authStore.user?.name }}! 👋</h5>
                        <p class="text-muted mb-0">
                            {{ authStore.isManager
                                ? 'You have full access to all branches and reports.'
                                : `You are managing ${branchName}. You can record purchases, sales and transfers for your branch.`
                            }}
                        </p>
                    </div>
                </div>
            </div>

        </div>
    </DashboardLayout>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import DashboardLayout from '../layouts/DashboardLayout.vue'
import { useAuthStore } from '../stores/auth'
import api from '../services/api'

const authStore = useAuthStore()

const stats = ref({
    branches:  0,
    products:  0,
    purchases: 0,
    sales:     0,
})

const branchName = computed(() => {
    const branches: Record<number, string> = {
        1: 'Branch Nairobi',
        2: 'Branch Mombasa',
    }
    return authStore.user?.branch_id
        ? branches[authStore.user.branch_id] ?? 'your branch'
        : 'all branches'
})

onMounted(async () => {
    try {
        const branchId = authStore.user?.branch_id

        const [branches, products, purchases, sales] = await Promise.all([
            api.get('/branches'),
            api.get('/products'),
            api.get('/purchases', { params: branchId ? { branch_id: branchId } : {} }),
            api.get('/sales',     { params: branchId ? { branch_id: branchId } : {} }),
        ])

        stats.value.branches  = branches.data.length
        stats.value.products  = products.data.length
        stats.value.purchases = purchases.data.length
        stats.value.sales     = sales.data.length
    } catch (e) {
        console.error('Failed to load stats', e)
    }
})
</script>