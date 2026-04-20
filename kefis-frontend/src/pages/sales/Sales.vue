<template>
  <DashboardLayout>
    <div class="page" :style="cart.length && activeTab === 'products' ? 'padding-bottom: 90px' : ''">

      <div class="page-header">
        <div>
          <h1 class="page-title">💰 Sales</h1>
          <p class="page-sub">Tap + to add · adjust qty inline · confirm at bottom</p>
        </div>
        <div class="header-right">
          <div class="tabs">
            <button class="tab" :class="{ active: activeTab === 'products' }" @click="activeTab = 'products'">
              <svg width="15" height="15" viewBox="0 0 15 15" fill="none">
                <rect x="1" y="1" width="5.5" height="5.5" rx="1" stroke="currentColor" stroke-width="1.5"/>
                <rect x="8.5" y="1" width="5.5" height="5.5" rx="1" stroke="currentColor" stroke-width="1.5"/>
                <rect x="1" y="8.5" width="5.5" height="5.5" rx="1" stroke="currentColor" stroke-width="1.5"/>
                <rect x="8.5" y="8.5" width="5.5" height="5.5" rx="1" stroke="currentColor" stroke-width="1.5"/>
              </svg>
              Products
            </button>
            <button class="tab" :class="{ active: activeTab === 'history' }" @click="switchToHistory">
              <svg width="15" height="15" viewBox="0 0 15 15" fill="none">
                <path d="M1 3h13M1 7.5h13M1 12h13" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
              </svg>
              History
              <span class="tab-badge" v-if="sales.length">{{ sales.length }}</span>
            </button>
          </div>
        </div>
      </div>

      <!-- PRODUCTS TAB -->
      <div v-if="activeTab === 'products'">
        <div v-if="loading" class="state-box"><div class="spinner"></div></div>
        <div v-else>
          <div class="branch-bar" v-if="authStore.isManager">
            <label>Branch:</label>
            <select v-model="form.branch_id" class="branch-select">
              <option value="">Select Branch</option>
              <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
            </select>
          </div>
          <div class="branch-bar" v-else>
            <label>Branch:</label>
            <span class="branch-name">{{ branchName }}</span>
          </div>

          <div v-for="(group, category) in groupedProducts" :key="category" class="category-section">
            <div class="category-header">
              <span class="category-icon">{{ getCategoryIcon(category) }}</span>
              <span class="category-name">{{ category }}</span>
              <span class="category-count">{{ group.length }}</span>
            </div>

            <div class="product-grid">
              <div
                class="product-card"
                v-for="p in group"
                :key="p.id"
                :class="{
                  'in-cart':        !!getCartItem(p.id),
                  'no-stock':       getStock(p.id) === 0,
                  'low-stock-card': isLowStock(p.id),
                }"
              >
                <div class="card-top">
                  <div class="product-icon">{{ getCategoryIcon(category) }}</div>
                  <div class="product-info">
                    <span class="product-sku">{{ p.sku }}</span>
                    <h4 class="product-name">{{ p.name }}</h4>
                    <span class="product-unit">per {{ p.unit }}</span>
                  </div>
                </div>

                <!--
                  COMPANY DROPDOWN — shown for any product that has companies.
                  Currently only Grocery products have companies in the DB (per design),
                  but this will also work if other categories gain companies later.
                -->
                <div class="company-row" v-if="p.companies && p.companies.length > 0">
                  <label class="company-label">Brand</label>
                  <select
                    class="company-select"
                    :value="selectedCompany[p.id]?.id"
                    @change="onCompanyChange(p, Number(($event.target as HTMLSelectElement).value))"
                  >
                    <option v-for="c in p.companies" :key="c.id" :value="c.id">
                      {{ c.name }} — KES {{ Number(c.selling_price).toLocaleString() }}
                    </option>
                  </select>
                </div>

                <div class="card-meta">
                  <span class="card-price">
                    KES {{ Number(getActivePrice(p)).toLocaleString() }}
                  </span>
                  <span
                    class="stock-chip"
                    :class="{
                      'zero': getStock(p.id) === 0,
                      'low':  isAtMinimum(p.id) || isLowStock(p.id),
                    }"
                  >
                    {{ getStock(p.id) !== null ? `Stock: ${getStock(p.id)}` : '—' }}
                  </span>
                </div>

                <div class="card-controls" :class="{ disabled: isBlockedFromSale(p.id) }">
                  <button class="ctrl-btn minus" @click="decrement(p)" :disabled="!getCartItem(p.id) || isBlockedFromSale(p.id)">−</button>
                  <span class="ctrl-qty" :class="{ active: !!getCartItem(p.id) }">{{ getCartItem(p.id)?.quantity ?? 0 }}</span>
                  <button class="ctrl-btn plus" @click="increment(p)" :disabled="isBlockedFromSale(p.id)">+</button>
                </div>

                <div class="low-stock-warning out" v-if="getStock(p.id) === 0">
                  🔴 Out of stock — sale blocked
                </div>
                <div class="low-stock-warning out" v-else-if="isAtMinimum(p.id)">
                  🚫 At minimum threshold — sale blocked
                </div>
                <div class="low-stock-warning" v-else-if="isLowStock(p.id)">
                  ⚠️ Low stock — {{ getStock(p.id) }} {{ p.unit }} left
                </div>
              </div>
            </div>
          </div>

          <div class="state-box" v-if="Object.keys(groupedProducts).length === 0">
            <div class="empty-icon">📦</div><p>No products found.</p>
          </div>
        </div>
      </div>

      <!-- HISTORY TAB -->
      <div v-if="activeTab === 'history'" class="history-panel">
        <div v-if="loading" class="state-box"><div class="spinner"></div></div>
        <div v-else-if="sales.length === 0" class="state-box">
          <div class="empty-icon">💰</div><p>No sales yet.</p>
        </div>
        <div v-else class="history-table-wrap">
          <table class="history-table">
            <thead>
              <tr>
                <th>Date</th><th>Branch</th><th>Product</th><th>Company</th>
                <th>Qty</th><th>Unit Price</th><th>Total</th><th>Customer</th><th>Ref</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="s in sales" :key="s.id">
                <td>{{ formatDate(s.sale_date) }}</td>
                <td>{{ s.branch?.name ?? '-' }}</td>
                <td><span class="product-pill">{{ s.product?.name }}</span></td>
                <td>
                  <span class="company-pill" v-if="s.product_company">{{ s.product_company.name }}</span>
                  <span v-else class="muted">—</span>
                </td>
                <td>{{ s.quantity }}</td>
                <td>KES {{ Number(s.unit_price).toLocaleString() }}</td>
                <td class="total-cell">KES {{ Number(s.total_amount).toLocaleString() }}</td>
                <td>{{ s.customer_name ?? '-' }}</td>
                <td><span class="ref-badge">{{ s.reference ?? '-' }}</span></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Floating Checkout Bar -->
      <transition name="bar-rise">
        <div class="checkout-bar" v-if="cart.length > 0 && activeTab === 'products'">
          <div class="checkout-bar__left">
            <span class="bar-items">{{ totalUnits }} units · {{ cart.length }} products</span>
            <strong class="bar-total">KES {{ cartTotal }}</strong>
          </div>
          <button class="bar-btn" @click="checkoutOpen = true">
            Review &amp; Confirm
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
              <path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </button>
        </div>
      </transition>

      <transition name="fade">
        <div class="overlay" v-if="checkoutOpen" @click.self="checkoutOpen = false"></div>
      </transition>

      <!-- Checkout Panel -->
      <transition name="slide">
        <div class="checkout-panel" v-if="checkoutOpen">
          <div class="checkout-panel__header">
            <h2>Confirm Sale <span>{{ cart.length }} items</span></h2>
            <button class="close-btn" @click="checkoutOpen = false">✕</button>
          </div>

          <div class="checkout-panel__body">
            <div class="summary-list">
              <div class="summary-item" v-for="item in cart" :key="item.product.id">
                <span class="summary-icon">{{ getCategoryIcon(item.product.category) }}</span>
                <div class="summary-main">
                  <span class="summary-name">{{ item.product.name }}</span>
                  <span class="summary-company" v-if="item.company">{{ item.company.name }}</span>
                </div>
                <div class="summary-right">
                  <div class="inline-controls">
                    <button class="sm-btn" @click="decrement(item.product)">−</button>
                    <span class="sm-qty">{{ item.quantity }}</span>
                    <button class="sm-btn" @click="increment(item.product)">+</button>
                  </div>
                  <div class="summary-price-col">
                    <input class="price-input" v-model="item.unit_price" type="number" min="0" placeholder="Price" />
                    <span class="price-unit">/{{ item.product.unit }}</span>
                  </div>
                  <button class="remove-btn" @click="removeFromCart(item.product.id)">✕</button>
                </div>
              </div>
            </div>

            <div class="checkout-fields">
              <div class="field">
                <label>Customer Name (optional)</label>
                <input v-model="form.customer_name" type="text" placeholder="e.g. John Doe" />
              </div>
              <div class="field-row">
                <div class="field">
                  <label>Sale Date</label>
                  <input v-model="form.sale_date" type="date" />
                </div>
                <div class="field">
                  <label>Reference</label>
                  <input v-model="form.reference" type="text" placeholder="e.g. SO-001" />
                </div>
              </div>
            </div>
          </div>

          <div class="checkout-panel__footer">
            <div class="cart-total-row">
              <span>Total</span>
              <strong>KES {{ cartTotal }}</strong>
            </div>
            <div class="alert alert--error"   v-if="formError">{{ formError }}</div>
            <div class="alert alert--success" v-if="formSuccess">{{ formSuccess }}</div>
            <button class="btn-confirm" :disabled="submitting" @click="handleSubmit">
              <span class="btn-spinner" v-if="submitting"></span>
              {{ submitting ? 'Recording…' : 'Record Sale' }}
            </button>
          </div>
        </div>
      </transition>

    </div>
  </DashboardLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import DashboardLayout from '../../layouts/DashboardLayout.vue'
import { useAuthStore } from '../../stores/auth'
import api from '../../services/api'

const authStore    = useAuthStore()
const activeTab    = ref<'products' | 'history'>('products')
const checkoutOpen = ref(false)

const branches      = ref<any[]>([])
const products      = ref<any[]>([])
const sales         = ref<any[]>([])
const stockBalances = ref<any[]>([])

const loading     = ref(false)
const submitting  = ref(false)
const formError   = ref('')
const formSuccess = ref('')
const salesLoaded = ref(false)

// Cart includes company reference for products that have companies
const cart = ref<{ product: any; quantity: number; unit_price: string; company: any | null }[]>([])

// Selected company per product id
const selectedCompany = ref<Record<number, any>>({})

const form = ref({
  branch_id:     authStore.isManager ? '' : authStore.user?.branch_id,
  customer_name: '',
  sale_date:     new Date().toISOString().split('T')[0],
  reference:     '',
})

const branchName = computed(() =>
  branches.value.find(b => b.id === authStore.user?.branch_id)?.name ?? 'Your Branch'
)

// Flat grouping — each product gets its own card.
// Company selection is handled via the dropdown on the card, not by duplicating cards.
const groupedProducts = computed(() => {
  const g: Record<string, any[]> = {}
  for (const p of products.value) {
    const c = p.category ?? 'Uncategorised'
    if (!g[c]) g[c] = []
    g[c].push(p)
  }
  return g
})

// ── Price helpers ─────────────────────────────────────────────────────────────
// Grocery products use selected company's price_per_unit.
// Other products use selling_price from the product itself.
const getActivePrice = (p: any): number => {
  if (p.companies && p.companies.length > 0) {
    const company = selectedCompany.value[p.id]
    return company ? Number(company.selling_price) : Number(p.companies[0].selling_price)
  }
  return Number(p.selling_price)
}

const onCompanyChange = (product: any, companyId: number) => {
  const company = product.companies.find((c: any) => c.id === companyId) ?? null
  selectedCompany.value[product.id] = company

  const item = getCartItem(product.id)
  if (item) {
    item.company    = company
    item.unit_price = String(company ? company.selling_price : product.selling_price)
  }
}

// ── Stock helpers ─────────────────────────────────────────────────────────────
const getCartItem = (id: number) => cart.value.find(i => i.product.id === id)

const getStock = (productId: number) => {
  const branchId = authStore.isManager ? form.value.branch_id : authStore.user?.branch_id
  const s = stockBalances.value.find(s => s.branch_id == branchId && s.product_id == productId)
  return s ? s.quantity : null
}

const getMinStock = (productId: number): number => {
  const p = products.value.find(p => p.id === productId)
  return p?.minimum_stock ?? 10
}

const isLowStock = (productId: number): boolean => {
  const stock = getStock(productId)
  if (stock === null || stock === 0) return false
  const min = getMinStock(productId)
  return stock > min && stock < min * 2
}

const isAtMinimum = (productId: number): boolean => {
  const stock = getStock(productId)
  if (stock === null || stock === 0) return false
  return stock <= getMinStock(productId)
}

const isBlockedFromSale = (productId: number): boolean => {
  const stock = getStock(productId)
  if (stock === null) return false
  return stock <= getMinStock(productId)
}

const cartTotal  = computed(() =>
  cart.value.reduce((s, i) => s + (parseFloat(i.unit_price) || 0) * i.quantity, 0)
    .toLocaleString('en-KE', { minimumFractionDigits: 2 })
)
const totalUnits = computed(() => cart.value.reduce((s, i) => s + i.quantity, 0))

const increment = (p: any) => {
  if (isBlockedFromSale(p.id)) return
  const item = getCartItem(p.id)
  if (item) {
    item.quantity++
  } else {
    const company = (p.companies && p.companies.length > 0)
      ? (selectedCompany.value[p.id] ?? p.companies[0])
      : null
    cart.value.push({
      product:    p,
      quantity:   1,
      unit_price: String(company ? company.selling_price : p.selling_price),
      company,
    })
  }
}
const decrement = (p: any) => {
  const item = getCartItem(p.id)
  if (!item) return
  if (item.quantity <= 1) removeFromCart(p.id)
  else item.quantity--
}
const removeFromCart = (id: number) => { cart.value = cart.value.filter(i => i.product.id !== id) }

const categoryIcons: Record<string, string> = {
  Electronics: '🔌', Electric: '⚡', Groceries: '🛒', Beverages: '🥤',
  Dairy: '🥛', Bakery: '🍞', Cleaning: '🧹', Stationery: '📝',
  Clothing: '👕', Furniture: '🪑', Snacks: '📦', Uncategorised: '📦',
}
const getCategoryIcon = (cat: string) => {
  for (const k of Object.keys(categoryIcons)) {
    if (cat?.toLowerCase().includes(k.toLowerCase())) return categoryIcons[k]
  }
  return '📦'
}
const formatDate = (d: string) =>
  new Date(d).toLocaleDateString('en-KE', { day: '2-digit', month: 'short', year: 'numeric' })

// ── Data loading ──────────────────────────────────────────────────────────────
const loadProducts = async () => {
  loading.value = true
  try {
    const bid = authStore.user?.branch_id
    const [b, p, sb] = await Promise.all([
      api.get('/branches'),
      api.get('/products'),   // ProductController eager-loads companies
      api.get('/stock-balances', { params: bid ? { branch_id: bid } : {} }),
    ])
    branches.value      = b.data
    products.value      = p.data
    stockBalances.value = sb.data

    // Initialise selectedCompany with first active company per product
    for (const prod of p.data as any[]) {
      if (prod.companies && prod.companies.length > 0) {
        selectedCompany.value[prod.id] = prod.companies[0]
      }
    }
  } catch (e) { console.error(e) }
  finally { loading.value = false }
}

const loadSales = async () => {
  if (salesLoaded.value) return
  loading.value = true
  try {
    const bid = authStore.user?.branch_id
    const res = await api.get('/sales', { params: bid ? { branch_id: bid } : {} })
    sales.value   = res.data
    salesLoaded.value = true
  } catch (e) { console.error(e) }
  finally { loading.value = false }
}

const switchToHistory = () => {
  activeTab.value = 'history'
  loadSales()
}

const handleSubmit = async () => {
  formError.value = ''; formSuccess.value = ''
  if (!form.value.branch_id) { formError.value = 'Please select a branch.'; return }
  if (!cart.value.length)    { formError.value = 'Cart is empty.'; return }

  for (const item of cart.value) {
    if (item.product.companies?.length > 0 && !item.company) {
      formError.value = `Please select a supplier for ${item.product.name}.`
      return
    }
    if (!item.unit_price) {
      formError.value = `Enter price for ${item.product.name}.`
      return
    }
  }

  submitting.value = true
  try {
    for (const item of cart.value) {
      await api.post('/sales', {
        branch_id:          form.value.branch_id,
        product_id:         item.product.id,
        product_company_id: item.company?.id ?? null,
        quantity:           item.quantity,
        unit_price:         item.unit_price,
        customer_name:      form.value.customer_name,
        sale_date:          form.value.sale_date,
        reference:          form.value.reference,
      })
    }
    formSuccess.value = `✅ ${cart.value.length} sale(s) recorded!`
    cart.value = []
    form.value.customer_name = ''
    form.value.reference     = ''
    form.value.sale_date     = new Date().toISOString().split('T')[0]
    salesLoaded.value = false
    await loadProducts()
    setTimeout(() => { checkoutOpen.value = false; formSuccess.value = '' }, 1500)
  } catch (e: any) {
    formError.value = e.response?.data?.message ?? 'Failed.'
  } finally { submitting.value = false }
}

onMounted(() => loadProducts())
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap');
* { box-sizing: border-box; }
.page { padding: 2rem; min-height: 100vh; background: #f7f8fa; font-family: 'Sora', sans-serif; position: relative; transition: padding-bottom 0.3s; }
.page-header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 1.75rem; flex-wrap: wrap; gap: 1rem; }
.page-title { font-size: 1.7rem; font-weight: 700; color: #0f172a; letter-spacing: -0.03em; margin: 0 0 0.2rem; }
.page-sub { font-size: 0.82rem; color: #94a3b8; margin: 0; }
.header-right { display: flex; align-items: center; gap: 0.75rem; }
.tabs { display: flex; background: #e8ecf0; border-radius: 10px; padding: 3px; gap: 2px; }
.tab { display: flex; align-items: center; gap: 0.4rem; padding: 0.45rem 1rem; border-radius: 8px; border: none; font-size: 0.82rem; font-weight: 600; cursor: pointer; color: #64748b; background: transparent; transition: all 0.2s; font-family: 'Sora', sans-serif; }
.tab.active { background: #fff; color: #0f172a; box-shadow: 0 1px 4px rgba(0,0,0,0.1); }
.tab-badge { background: #10b981; color: #fff; border-radius: 20px; padding: 0 6px; font-size: 0.7rem; font-weight: 700; }
.branch-bar { display: flex; align-items: center; gap: 0.75rem; background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 0.6rem 1rem; margin-bottom: 1.5rem; font-size: 0.85rem; }
.branch-bar label { font-weight: 600; color: #0f172a; }
.branch-select { border: 1.5px solid #e2e8f0; border-radius: 7px; padding: 0.3rem 0.6rem; font-size: 0.82rem; font-family: 'Sora', sans-serif; outline: none; }
.branch-select:focus { border-color: #10b981; }
.branch-name { font-weight: 600; color: #10b981; }
.category-section { margin-bottom: 2rem; }
.category-header { display: flex; align-items: center; gap: 0.6rem; margin-bottom: 0.9rem; padding-bottom: 0.5rem; border-bottom: 1.5px solid #e2e8f0; }
.category-icon { font-size: 1.2rem; }
.category-name { font-size: 0.95rem; font-weight: 700; color: #0f172a; }
.category-count { font-size: 0.72rem; font-weight: 600; color: #94a3b8; background: #f1f5f9; padding: 0.15rem 0.5rem; border-radius: 20px; }
.product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem; }
.product-card { background: #fff; border: 1.5px solid #e2e8f0; border-radius: 16px; padding: 1rem; display: flex; flex-direction: column; gap: 0.8rem; transition: border-color 0.2s, box-shadow 0.2s; }
.product-card.in-cart     { border-color: #10b981; background: #f0fdf8; box-shadow: 0 2px 12px rgba(16,185,129,0.1); }
.product-card.no-stock    { opacity: 0.45; }
.product-card.low-stock-card { border-color: #fbbf24; background: #fffdf0; }
.card-top { display: flex; gap: 0.75rem; align-items: flex-start; }
.product-icon { font-size: 1.75rem; width: 44px; height: 44px; background: #f8fafc; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.product-sku { font-size: 0.62rem; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.06em; font-family: 'JetBrains Mono', monospace; display: block; }
.product-name { font-size: 0.88rem; font-weight: 700; color: #0f172a; margin: 0.15rem 0 0.1rem; line-height: 1.2; }
.product-unit { font-size: 0.7rem; color: #94a3b8; }
.company-row { display: flex; align-items: center; gap: 0.5rem; padding-top: 0.55rem; margin-top: 0.1rem; border-top: 1px dashed #e2e8f0; }
.company-label { font-size: 0.68rem; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; white-space: nowrap; }
.company-select { flex: 1; border: 1.5px solid #e2e8f0; border-radius: 7px; padding: 0.25rem 0.4rem; font-size: 0.72rem; font-family: 'Sora', sans-serif; color: #0f172a; outline: none; background: #f8fafc; cursor: pointer; transition: border-color 0.15s; }
.company-select:focus { border-color: #10b981; }
.card-meta { display: flex; align-items: center; justify-content: space-between; }
.card-price { font-size: 0.9rem; font-weight: 700; color: #0f172a; font-family: 'JetBrains Mono', monospace; }
.stock-chip { font-size: 0.7rem; font-weight: 600; padding: 0.15rem 0.5rem; border-radius: 20px; background: #f0fdf4; color: #10b981; }
.stock-chip.zero { background: #fff1f2; color: #ef4444; }
.stock-chip.low  { background: #fff7ed; color: #c2410c; }
.low-stock-warning { font-size: 0.7rem; font-weight: 600; color: #c2410c; background: #fff7ed; border: 1px solid #fed7aa; border-radius: 6px; padding: 0.25rem 0.5rem; text-align: center; }
.low-stock-warning.out { color: #be123c; background: #fff1f2; border-color: #fecdd3; }
.card-controls { display: flex; align-items: center; border-radius: 10px; overflow: hidden; border: 1.5px solid #e2e8f0; background: #f8fafc; height: 40px; }
.card-controls.disabled { opacity: 0.4; pointer-events: none; }
.ctrl-btn { border: none; background: transparent; cursor: pointer; width: 40px; height: 40px; font-size: 1.3rem; font-weight: 700; color: #64748b; display: flex; align-items: center; justify-content: center; transition: background 0.15s, color 0.15s; flex-shrink: 0; user-select: none; }
.ctrl-btn:disabled { opacity: 0.25; cursor: not-allowed; }
.ctrl-btn.plus { color: #059669; }
.ctrl-btn.plus:not(:disabled):hover { background: #f0fdf4; }
.ctrl-btn.minus:not(:disabled):hover { background: #fff1f2; color: #dc2626; }
.ctrl-qty { flex: 1; text-align: center; font-size: 1rem; font-weight: 700; color: #cbd5e1; font-family: 'JetBrains Mono', monospace; border-left: 1.5px solid #e2e8f0; border-right: 1.5px solid #e2e8f0; height: 100%; display: flex; align-items: center; justify-content: center; min-width: 40px; transition: color 0.2s, background 0.2s; }
.ctrl-qty.active { color: #059669; background: #fff; }
.product-card.in-cart .card-controls { border-color: #6ee7b7; background: #fff; }
.product-card.in-cart .ctrl-qty { border-color: #a7f3d0; }
.checkout-bar { position: fixed; bottom: 0; left: 0; right: 0; background: #064e3b; color: #fff; display: flex; align-items: center; justify-content: space-between; padding: 1rem 2rem; z-index: 90; box-shadow: 0 -4px 24px rgba(0,0,0,0.18); }
.checkout-bar__left { display: flex; flex-direction: column; gap: 0.1rem; }
.bar-items { font-size: 0.75rem; color: #6ee7b7; font-weight: 500; }
.bar-total { font-size: 1.2rem; font-weight: 700; font-family: 'JetBrains Mono', monospace; }
.bar-btn { display: flex; align-items: center; gap: 0.5rem; background: #10b981; color: #fff; border: none; border-radius: 10px; padding: 0.7rem 1.5rem; font-size: 0.875rem; font-weight: 700; cursor: pointer; font-family: 'Sora', sans-serif; transition: background 0.2s; }
.bar-btn:hover { background: #059669; }
.bar-rise-enter-active, .bar-rise-leave-active { transition: transform 0.3s cubic-bezier(0.4,0,0.2,1); }
.bar-rise-enter-from, .bar-rise-leave-to { transform: translateY(100%); }
.history-panel { margin-top: 0.5rem; }
.history-table-wrap { background: #fff; border-radius: 14px; border: 1px solid #e2e8f0; overflow: hidden; }
.history-table { width: 100%; border-collapse: collapse; font-size: 0.82rem; }
.history-table thead { background: #f8fafc; }
.history-table th { padding: 0.8rem 1rem; text-align: left; font-weight: 600; color: #64748b; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 1px solid #e2e8f0; }
.history-table td { padding: 0.8rem 1rem; color: #374151; border-bottom: 1px solid #f1f5f9; }
.history-table tr:last-child td { border-bottom: none; }
.history-table tr:hover td { background: #f8fafc; }
.product-pill  { background: #f0fdf4; color: #059669; font-weight: 600; padding: 0.2rem 0.6rem; border-radius: 6px; font-size: 0.75rem; }
.company-pill  { background: #eff6ff; color: #2563eb; font-weight: 600; padding: 0.2rem 0.6rem; border-radius: 6px; font-size: 0.75rem; }
.muted { color: #cbd5e1; font-size: 0.75rem; }
.total-cell { font-weight: 700; color: #0f172a; font-family: 'JetBrains Mono', monospace; }
.ref-badge { font-family: 'JetBrains Mono', monospace; font-size: 0.72rem; color: #64748b; }
.state-box { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 4rem 2rem; gap: 1rem; color: #94a3b8; }
.empty-icon { font-size: 2.5rem; }
.spinner { width: 28px; height: 28px; border: 3px solid #e2e8f0; border-top-color: #10b981; border-radius: 50%; animation: spin 0.7s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
.overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.25); backdrop-filter: blur(2px); z-index: 100; }
.fade-enter-active, .fade-leave-active { transition: opacity 0.25s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
.checkout-panel { position: fixed; top: 0; right: 0; bottom: 0; width: 480px; background: #fff; z-index: 101; display: flex; flex-direction: column; box-shadow: -4px 0 32px rgba(0,0,0,0.12); }
.slide-enter-active, .slide-leave-active { transition: transform 0.3s cubic-bezier(0.4,0,0.2,1); }
.slide-enter-from, .slide-leave-to { transform: translateX(100%); }
.checkout-panel__header { display: flex; align-items: center; justify-content: space-between; padding: 1.4rem 1.75rem; border-bottom: 1px solid #f1f5f9; }
.checkout-panel__header h2 { font-size: 1.1rem; font-weight: 700; color: #0f172a; margin: 0; }
.checkout-panel__header h2 span { font-size: 0.8rem; font-weight: 400; color: #94a3b8; margin-left: 0.4rem; }
.close-btn { background: #f1f5f9; border: none; border-radius: 8px; width: 32px; height: 32px; cursor: pointer; color: #64748b; transition: background 0.15s; }
.close-btn:hover { background: #e2e8f0; }
.checkout-panel__body { flex: 1; overflow-y: auto; padding: 1.25rem 1.75rem; display: flex; flex-direction: column; gap: 1rem; }
.summary-list { display: flex; flex-direction: column; gap: 0.5rem; }
.summary-item { display: flex; align-items: center; gap: 0.6rem; padding: 0.7rem 0.85rem; background: #f8fafc; border-radius: 10px; border: 1px solid #e2e8f0; }
.summary-icon { font-size: 1.25rem; flex-shrink: 0; }
.summary-main { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 0.1rem; overflow: hidden; }
.summary-name { font-size: 0.82rem; font-weight: 600; color: #0f172a; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.summary-company { font-size: 0.7rem; font-weight: 600; color: #2563eb; }
.summary-right { display: flex; align-items: center; gap: 0.5rem; flex-shrink: 0; }
.inline-controls { display: flex; align-items: center; border: 1.5px solid #e2e8f0; border-radius: 7px; overflow: hidden; }
.sm-btn { background: #f1f5f9; border: none; width: 26px; height: 28px; cursor: pointer; font-size: 1rem; font-weight: 700; color: #64748b; display: flex; align-items: center; justify-content: center; transition: background 0.15s; }
.sm-btn:hover { background: #e2e8f0; }
.sm-qty { font-size: 0.82rem; font-weight: 700; color: #0f172a; min-width: 28px; text-align: center; font-family: 'JetBrains Mono', monospace; border-left: 1px solid #e2e8f0; border-right: 1px solid #e2e8f0; height: 28px; line-height: 28px; }
.summary-price-col { display: flex; align-items: center; gap: 2px; }
.price-input { width: 72px; border: 1.5px solid #e2e8f0; border-radius: 6px; padding: 0.25rem 0.4rem; font-size: 0.75rem; font-family: 'JetBrains Mono', monospace; color: #0f172a; outline: none; text-align: right; }
.price-input:focus { border-color: #10b981; }
.price-unit { font-size: 0.65rem; color: #94a3b8; }
.remove-btn { background: none; border: none; color: #cbd5e1; cursor: pointer; font-size: 0.72rem; padding: 3px; border-radius: 4px; transition: color 0.15s; }
.remove-btn:hover { color: #ef4444; }
.checkout-fields { display: flex; flex-direction: column; gap: 0.85rem; padding-top: 1rem; border-top: 1px dashed #e2e8f0; }
.field { display: flex; flex-direction: column; gap: 0.3rem; }
.field-row { display: grid; grid-template-columns: 1fr 1fr; gap: 0.6rem; }
.field label { font-size: 0.75rem; font-weight: 600; color: #374151; }
.field input { border: 1.5px solid #e2e8f0; border-radius: 8px; padding: 0.5rem 0.75rem; font-size: 0.82rem; font-family: 'Sora', sans-serif; color: #0f172a; outline: none; transition: border-color 0.15s; }
.field input:focus { border-color: #10b981; }
.checkout-panel__footer { padding: 1.25rem 1.75rem; border-top: 1px solid #f1f5f9; display: flex; flex-direction: column; gap: 0.75rem; }
.cart-total-row { display: flex; justify-content: space-between; align-items: center; }
.cart-total-row span { font-size: 0.85rem; color: #64748b; font-weight: 500; }
.cart-total-row strong { font-size: 1.15rem; font-weight: 700; color: #0f172a; font-family: 'JetBrains Mono', monospace; }
.btn-confirm { background: #059669; color: #fff; border: none; border-radius: 10px; padding: 0.75rem; font-size: 0.875rem; font-weight: 700; cursor: pointer; font-family: 'Sora', sans-serif; display: flex; align-items: center; justify-content: center; gap: 0.5rem; transition: background 0.2s; }
.btn-confirm:hover:not(:disabled) { background: #047857; }
.btn-confirm:disabled { opacity: 0.6; cursor: not-allowed; }
.btn-spinner { width: 14px; height: 14px; border: 2px solid rgba(255,255,255,0.4); border-top-color: #fff; border-radius: 50%; animation: spin 0.6s linear infinite; }
.alert { padding: 0.6rem 0.85rem; border-radius: 8px; font-size: 0.8rem; font-weight: 500; }
.alert--error { background: #fff1f2; color: #be123c; border: 1px solid #fecdd3; }
.alert--success { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }
</style>