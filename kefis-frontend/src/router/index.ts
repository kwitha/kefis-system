import { createRouter, createWebHistory } from 'vue-router'

import Welcome       from '../pages/Welcome.vue'
import Login         from '../pages/auth/Login.vue'
import Dashboard     from '../pages/Dashboard.vue'
import Branches      from '../pages/branches/Branches.vue'
import Products      from '../pages/products/Products.vue'
import Purchases     from '../pages/purchases/Purchases.vue'
import Sales         from '../pages/sales/Sales.vue'
import Transfers     from '../pages/transfers/Transfers.vue'
import StockBalances from '../pages/stock/StockBalances.vue'

const routes = [
    { path: '/',          component: Welcome,       meta: { guest: false } },
    { path: '/welcome',   component: Welcome,       meta: { guest: false } },
    { path: '/login',     component: Login,         meta: { guest: true } },
    { path: '/dashboard', component: Dashboard,     meta: { requiresAuth: true } },
    { path: '/branches',  component: Branches,      meta: { requiresAuth: true } },
    { path: '/products',  component: Products,      meta: { requiresAuth: true } },
    { path: '/purchases', component: Purchases,     meta: { requiresAuth: true } },
    { path: '/sales',     component: Sales,         meta: { requiresAuth: true } },
    { path: '/transfers', component: Transfers,     meta: { requiresAuth: true } },
    { path: '/stock',     component: StockBalances, meta: { requiresAuth: true } },
]

const router = createRouter({
    history: createWebHistory(),
    routes,
})

router.beforeEach((to, from, next) => {
    const token = localStorage.getItem('token')

    if (to.meta.requiresAuth && !token) {
        next('/login')
    } else if (to.meta.guest && token) {
        next('/dashboard')
    } else {
        next()
    }
})

export default router