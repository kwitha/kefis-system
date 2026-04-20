<template>
  <DashboardLayout>
    <div class="page" :style="cart.length && activeTab === 'products' ? 'padding-bottom: 90px' : ''">

      <div class="page-header">
        <div>
          <h1 class="page-title">🔄 Transfers</h1>
          <p class="page-sub">Move stock between your branches</p>
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
              <span class="tab-badge" v-if="transfers.length">{{ transfers.length }}</span>
            </button>
          </div>
        </div>
      </div>

      <!-- PRODUCTS TAB -->
      <div v-if="activeTab === 'products'">
        <div v-if="loading" class="state-box"><div class="spinner"></div></div>
        <div v-else>

          <!-- Branch selectors -->
          <div class="branch-row">
            <div class="branch-box">
              <label>From Branch</label>
              <select
                v-if="authStore.isManager"
                v-model="form.from_branch_id"
                class="branch-select"
                @change="onBranchChange"
              >
                <option value="">Select Source</option>
                <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
              </select>
              <span v-else class="branch-name">{{ branchName }}</span>
            </div>
            <div class="branch-arrow">
              <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                <path d="M4 10h12M12 5l5 5-5 5" stroke="#94a3b8" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            <div class="branch-box">
              <label>To Branch</label>
              <select v-model="form.to_branch_id" class="branch-select">
                <option value="">Select Destination</option>
                <option v-for="b in toBranches" :key="b.id" :value="b.id">{{ b.name }}</option>
              </select>
            </div>
          </div>

          <!-- Category sections -->
          <div v-for="(group, category) in groupedProducts" :key="category" class="category-section">
            <div class="category-header">
              <span class="category-icon">{{ getCategoryIcon(category) }}</span>
              <span class="category-name">{{ category }}</span>
              <span class="category-count">{{ group.length }} items</span>
            </div>

            <div class="product-grid">
              <div
                class="product-card"
                v-for="p in group"
                :key="p.id"
                :class="{
                  'in-cart':   isInCart(p.id),
                  'low-stock': getStock(p.id) === 0,
                }"
              >
                <div class="product-card__icon">{{ getCategoryIcon(category) }}</div>
                <div class="product-card__info">
                  <span class="product-card__sku">{{ p.sku }}</span>
                  <h4 class="product-card__name">{{ p.name }}</h4>
                  <span class="product-card__unit">per {{ p.unit }}</span>
                </div>

                <!--
                  COMPANY DROPDOWN FOR TRANSFERS
                  When a grocery product is transferred, the shopkeeper picks which
                  company's stock to move (e.g. transfer 10kg of Mumias Sugar, not
                  just 10kg of Sugar). This ensures stock follows the company correctly.
                -->
                <div class="company-row" v-if="p.companies && p.companies.length > 0">
                  <label class="company-label">Company</label>
                  <select
                    class="company-select"
                    :value="selectedCompany[p.id]?.id"
                    @change="onCompanyChange(p, Number(($event.target as HTMLSelectElement).value))"
                  >
                    <option v-for="c in p.companies" :key="c.id" :value="c.id">
                      {{ c.name }}
                    </option>
                  </select>
                </div>

                <div class="stock-bar">
                  <span class="stock-label">Available:</span>
                  <span class="stock-val" :class="getStock(p.id) === 0 ? 'zero' : ''">
                    {{ getStock(p.id) ?? '—' }}
                  </span>
                </div>

                <div class="card-controls" :class="{ disabled: getStock(p.id) === 0 }">
                  <button class="ctrl-btn minus" @click.stop="decrement(p)" :disabled="!isInCart(p.id) || getStock(p.id) === 0">−</button>
                  <span class="ctrl-qty" :class="{ active: isInCart(p.id) }">{{ getCartItem(p.id)?.quantity ?? 0 }}</span>
                  <button class="ctrl-btn plus" @click.stop="increment(p)" :disabled="getStock(p.id) === 0">+</button>
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
        <div v-else-if="transfers.length === 0" class="state-box">
          <div class="empty-icon">🔄</div><p>No transfers recorded yet.</p>
        </div>
        <div v-else class="history-table-wrap">
          <table class="history-table">
            <thead>
              <tr>
                <th>Date</th><th>Product</th><th>Company</th>
                <th>From</th><th>To</th><th>Qty</th><th>Status</th><th>Notes</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="t in transfers" :key="t.id">
                <td>{{ formatDate(t.created_at) }}</td>
                <td><span class="product-pill">{{ t.product?.name }}</span></td>
                <td>
                  <span class="company-pill" v-if="t.product_company">{{ t.product_company.name }}</span>
                  <span v-else class="muted">—</span>
                </td>
                <td><span class="branch-pill from">{{ t.from_branch?.name }}</span></td>
                <td><span class="branch-pill to">{{ t.to_branch?.name }}</span></td>
                <td class="qty-cell">{{ t.quantity }}</td>
                <td><span class="status-badge" :class="t.status">{{ t.status }}</span></td>
                <td class="notes-cell">{{ t.notes ?? '-' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Floating Bar -->
      <transition name="bar-rise">
        <div class="checkout-bar" v-if="cart.length > 0 && activeTab === 'products'">
          <div class="checkout-bar__left">
            <span class="bar-items">{{ cart.reduce((s,i) => s + i.quantity, 0) }} units · {{ cart.length }} products</span>
            <strong class="bar-total">{{ cart.length }} transfer item(s)</strong>
          </div>
          <button class="bar-btn" @click="cartOpen = true">
            Review &amp; Confirm
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
              <path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </button>
        </div>
      </transition>

      <transition name="fade">
        <div class="overlay" v-if="cartOpen" @click.self="cartOpen = false"></div>
      </transition>

      <!-- Transfer Panel -->
      <transition name="slide">
        <div class="cart-panel" v-if="cartOpen">
          <div class="cart-panel__header">
            <h2>Transfer List <span>{{ cart.length }} items</span></h2>
            <button class="close-btn" @click="cartOpen = false">✕</button>
          </div>

          <div class="cart-panel__body">
            <!-- Branch summary -->
            <div class="transfer-summary" v-if="cart.length > 0">
              <div class="branch-summary-pill from">{{ fromBranchName || 'Source Branch' }}</div>
              <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                <path d="M3 8h10M9 4l4 4-4 4" stroke="#94a3b8" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              <div class="branch-summary-pill to">{{ toBranchName || 'Destination Branch' }}</div>
            </div>

            <div class="cart-item" v-for="item in cart" :key="item.product.id + (item.company?.id ?? '')">
              <div class="cart-item__icon">{{ getCategoryIcon(item.product.category) }}</div>
              <div class="cart-item__info">
                <p class="cart-item__name">{{ item.product.name }}</p>
                <!-- Show company name below product name in panel -->
                <p class="cart-item__company" v-if="item.company">{{ item.company.name }}</p>
                <p class="cart-item__sku">Stock: {{ getStock(item.product.id) ?? '—' }}</p>
              </div>
              <div class="cart-item__controls">
                <button class="qty-btn" @click="changeQty(item, -1)">−</button>
                <span class="qty-val">{{ item.quantity }}</span>
                <button class="qty-btn" @click="changeQty(item, 1)">+</button>
              </div>
              <button class="remove-btn" @click="removeFromCart(item.product.id)">✕</button>
            </div>

            <div class="checkout-fields" v-if="cart.length > 0">
              <div class="field">
                <label>Notes (optional)</label>
                <textarea v-model="form.notes" placeholder="e.g. Monthly restock..." rows="2"></textarea>
              </div>
            </div>
          </div>

          <div class="cart-panel__footer" v-if="cart.length > 0">
            <div class="transfer-count">
              <span>{{ cart.length }} product(s)</span>
              <strong>{{ cart.reduce((s, i) => s + i.quantity, 0) }} total units</strong>
            </div>
            <div class="alert alert--error"   v-if="formError">{{ formError }}</div>
            <div class="alert alert--success" v-if="formSuccess">{{ formSuccess }}</div>
            <button class="btn-checkout" :disabled="submitting" @click="handleSubmit">
              <span class="btn-spinner" v-if="submitting"></span>
              {{ submitting ? 'Transferring…' : 'Confirm Transfer' }}
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

const authStore  = useAuthStore()
const activeTab  = ref<'products' | 'history'>('products')
const cartOpen   = ref(false)

const branches      = ref<any[]>([])
const products      = ref<any[]>([])
const transfers     = ref<any[]>([])
const stockBalances = ref<any[]>([])

const loading          = ref(false)
const submitting       = ref(false)
const formError        = ref('')
const formSuccess      = ref('')
const transfersLoaded  = ref(false)

// Cart includes company for grocery products — stock follows company between branches
const cart = ref<{ product: any; quantity: number; company: any | null }[]>([])

// Selected company per product id
const selectedCompany = ref<Record<number, any>>({})

const form = ref({
  from_branch_id: authStore.isManager ? '' : authStore.user?.branch_id,
  to_branch_id:   '',
  notes:          '',
})

const branchName = computed(() => {
  const b = branches.value.find(b => b.id === authStore.user?.branch_id)
  return b?.name ?? 'Your Branch'
})

const fromBranchName = computed(() => {
  const id = authStore.isManager ? form.value.from_branch_id : authStore.user?.branch_id
  return branches.value.find(b => b.id == id)?.name ?? ''
})

const toBranchName = computed(() =>
  branches.value.find(b => b.id == form.value.to_branch_id)?.name ?? ''
)

const toBranches = computed(() => {
  const fromId = authStore.isManager ? form.value.from_branch_id : authStore.user?.branch_id
  return branches.value.filter(b => b.id != fromId)
})

// Flat grouping — each product is one card with a company dropdown if applicable
const groupedProducts = computed(() => {
  const groups: Record<string, any[]> = {}
  for (const p of products.value) {
    const cat = p.category ?? 'Uncategorised'
    if (!groups[cat]) groups[cat] = []
    groups[cat].push(p)
  }
  return groups
})

const getStock = (productId: number) => {
  const branchId = authStore.isManager ? form.value.from_branch_id : authStore.user?.branch_id
  const s = stockBalances.value.find(s => s.branch_id == branchId && s.product_id == productId)
  return s ? s.quantity : null
}

const onBranchChange = () => { cart.value = [] }

const onCompanyChange = (product: any, companyId: number) => {
  const company = product.companies.find((c: any) => c.id === companyId) ?? null
  selectedCompany.value[product.id] = company

  // If in cart, update the company reference
  const item = getCartItem(product.id)
  if (item) item.company = company
}

const categoryIcons: Record<string, string> = {
  Electronics: '🔌', Electric: '⚡', Groceries: '🛒', Beverages: '🥤',
  Dairy: '🥛', Bakery: '🍞', Cleaning: '🧹', Stationery: '📝',
  Clothing: '👕', Furniture: '🪑', Snacks: '📦', Uncategorised: '📦',
}
const getCategoryIcon = (cat: string) => {
  for (const key of Object.keys(categoryIcons)) {
    if (cat?.toLowerCase().includes(key.toLowerCase())) return categoryIcons[key]
  }
  return '📦'
}

const isInCart    = (id: number) => cart.value.some(i => i.product.id === id)
const getCartItem = (id: number) => cart.value.find(i => i.product.id === id)

const increment = (p: any) => {
  if (getStock(p.id) === 0) return
  const item = getCartItem(p.id)
  if (item) {
    item.quantity++
  } else {
    const company = (p.companies && p.companies.length > 0)
      ? (selectedCompany.value[p.id] ?? p.companies[0])
      : null
    cart.value.push({ product: p, quantity: 1, company })
  }
}
const decrement = (p: any) => {
  const item = getCartItem(p.id)
  if (!item) return
  if (item.quantity <= 1) removeFromCart(p.id)
  else item.quantity--
}
const removeFromCart = (id: number) => { cart.value = cart.value.filter(i => i.product.id !== id) }
const changeQty = (item: any, delta: number) => { item.quantity = Math.max(1, item.quantity + delta) }

const formatDate = (date: string) =>
  new Date(date).toLocaleDateString('en-KE', { day: '2-digit', month: 'short', year: 'numeric' })

// ── Data loading ──────────────────────────────────────────────────────────────
const loadProducts = async () => {
  loading.value = true
  try {
    const branchId = authStore.user?.branch_id
    const [b, p, sb] = await Promise.all([
      api.get('/branches'),
      api.get('/products'),  // Eager-loads companies[]
      api.get('/stock-balances', { params: branchId ? { branch_id: branchId } : {} }),
    ])
    branches.value      = b.data
    products.value      = p.data
    stockBalances.value = sb.data

    // Initialise selectedCompany with first company per product
    for (const prod of p.data as any[]) {
      if (prod.companies && prod.companies.length > 0) {
        selectedCompany.value[prod.id] = prod.companies[0]
      }
    }
  } catch (e) { console.error(e) }
  finally { loading.value = false }
}

const loadTransfers = async () => {
  if (transfersLoaded.value) return
  loading.value = true
  try {
    const branchId = authStore.user?.branch_id
    const res = await api.get('/transfers', {
      params: branchId ? { from_branch_id: branchId } : {},
    })
    transfers.value      = res.data
    transfersLoaded.value = true
  } catch (e) { console.error(e) }
  finally { loading.value = false }
}

const switchToHistory = () => {
  activeTab.value = 'history'
  loadTransfers()
}

const handleSubmit = async () => {
  formError.value = ''; formSuccess.value = ''
  const fromId = authStore.isManager ? form.value.from_branch_id : authStore.user?.branch_id
  if (!fromId)                     { formError.value = 'Please select source branch.'; return }
  if (!form.value.to_branch_id)    { formError.value = 'Please select destination branch.'; return }
  if (cart.value.length === 0)     { formError.value = 'No products selected.'; return }

  // Validate: grocery products must have a company selected
  for (const item of cart.value) {
    if (item.product.companies?.length > 0 && !item.company) {
      formError.value = `Please select a company for ${item.product.name}.`
      return
    }
  }

  submitting.value = true
  try {
    for (const item of cart.value) {
      await api.post('/transfers', {
        from_branch_id:     fromId,
        to_branch_id:       form.value.to_branch_id,
        product_id:         item.product.id,
        product_company_id: item.company?.id ?? null,  // ← stock follows company
        quantity:           item.quantity,
        notes:              form.value.notes,
      })
    }
    formSuccess.value = `✅ ${cart.value.length} transfer(s) completed!`
    cart.value = []
    form.value.to_branch_id = ''; form.value.notes = ''
    transfersLoaded.value = false
    await loadProducts()
    setTimeout(() => { cartOpen.value = false; formSuccess.value = '' }, 1500)
  } catch (e: any) {
    formError.value = e.response?.data?.message ?? 'Failed to transfer stock.'
  } finally { submitting.value = false }
}

onMounted(() => loadProducts())
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap');
* { box-sizing: border-box; }
.page { padding: 2rem; min-height: 100vh; background: #f7f8fa; font-family: 'Sora', sans-serif; position: relative; }
.page-header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 1.75rem; flex-wrap: wrap; gap: 1rem; }
.page-title { font-size: 1.7rem; font-weight: 700; color: #0f172a; letter-spacing: -0.03em; margin: 0 0 0.2rem; }
.page-sub { font-size: 0.85rem; color: #94a3b8; margin: 0; }
.header-right { display: flex; align-items: center; gap: 0.75rem; }
.tabs { display: flex; background: #e8ecf0; border-radius: 10px; padding: 3px; gap: 2px; }
.tab { display: flex; align-items: center; gap: 0.4rem; padding: 0.45rem 1rem; border-radius: 8px; border: none; font-size: 0.82rem; font-weight: 600; cursor: pointer; color: #64748b; background: transparent; transition: all 0.2s; font-family: 'Sora', sans-serif; }
.tab.active { background: #fff; color: #0f172a; box-shadow: 0 1px 4px rgba(0,0,0,0.1); }
.tab-badge { background: #f59e0b; color: #fff; border-radius: 20px; padding: 0 6px; font-size: 0.7rem; font-weight: 700; }
.branch-row { display: flex; align-items: center; gap: 0.75rem; background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1rem 1.25rem; margin-bottom: 1.75rem; flex-wrap: wrap; }
.branch-box { display: flex; flex-direction: column; gap: 0.3rem; flex: 1; min-width: 140px; }
.branch-box label { font-size: 0.72rem; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; }
.branch-select { border: 1.5px solid #e2e8f0; border-radius: 7px; padding: 0.4rem 0.7rem; font-size: 0.85rem; font-family: 'Sora', sans-serif; outline: none; color: #0f172a; }
.branch-select:focus { border-color: #f59e0b; }
.branch-name { font-size: 0.9rem; font-weight: 700; color: #d97706; }
.branch-arrow { color: #94a3b8; flex-shrink: 0; }
.category-section { margin-bottom: 2rem; }
.category-header { display: flex; align-items: center; gap: 0.6rem; margin-bottom: 0.9rem; padding-bottom: 0.5rem; border-bottom: 1.5px solid #e2e8f0; }
.category-icon { font-size: 1.2rem; }
.category-name { font-size: 0.95rem; font-weight: 700; color: #0f172a; }
.category-count { font-size: 0.72rem; font-weight: 600; color: #94a3b8; background: #f1f5f9; padding: 0.15rem 0.5rem; border-radius: 20px; }
.product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(190px, 1fr)); gap: 1rem; }
.product-card { background: #fff; border: 1.5px solid #e2e8f0; border-radius: 14px; padding: 1.1rem; transition: all 0.2s; display: flex; flex-direction: column; gap: 0.65rem; }
.product-card.in-cart  { border-color: #f59e0b; background: #fffbeb; }
.product-card.low-stock { opacity: 0.5; }
.product-card__icon { font-size: 2rem; width: 48px; height: 48px; background: #f8fafc; border-radius: 10px; display: flex; align-items: center; justify-content: center; }
.product-card__sku  { font-size: 0.65rem; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.06em; font-family: 'JetBrains Mono', monospace; display: block; }
.product-card__name { font-size: 0.9rem; font-weight: 700; color: #0f172a; margin: 0.15rem 0 0; }
.product-card__unit { font-size: 0.72rem; color: #94a3b8; }
/* Company row */
.company-row { display: flex; align-items: center; gap: 0.5rem; padding-top: 0.5rem; margin-top: 0.05rem; border-top: 1px dashed #e2e8f0; }
.company-label { font-size: 0.68rem; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; white-space: nowrap; }
.company-select { flex: 1; border: 1.5px solid #e2e8f0; border-radius: 7px; padding: 0.25rem 0.4rem; font-size: 0.72rem; font-family: 'Sora', sans-serif; color: #0f172a; outline: none; background: #f8fafc; cursor: pointer; transition: border-color 0.15s; }
.company-select:focus { border-color: #f59e0b; }
.stock-bar { display: flex; align-items: center; gap: 0.35rem; }
.stock-label { font-size: 0.68rem; color: #94a3b8; font-weight: 500; }
.stock-val { font-size: 0.75rem; font-weight: 700; color: #d97706; font-family: 'JetBrains Mono', monospace; }
.stock-val.zero { color: #ef4444; }
.card-controls { display: flex; align-items: center; border-radius: 10px; overflow: hidden; border: 1.5px solid #e2e8f0; background: #f8fafc; height: 40px; }
.card-controls.disabled { opacity: 0.3; pointer-events: none; }
.ctrl-btn { border: none; background: transparent; cursor: pointer; width: 40px; height: 40px; font-size: 1.3rem; font-weight: 700; color: #64748b; display: flex; align-items: center; justify-content: center; transition: background 0.15s; flex-shrink: 0; user-select: none; }
.ctrl-btn:disabled { opacity: 0.25; cursor: not-allowed; }
.ctrl-btn.plus { color: #d97706; }
.ctrl-btn.plus:not(:disabled):hover { background: #fffbeb; }
.ctrl-btn.minus:not(:disabled):hover { background: #fff1f2; color: #dc2626; }
.ctrl-qty { flex: 1; text-align: center; font-size: 1rem; font-weight: 700; color: #cbd5e1; font-family: 'JetBrains Mono', monospace; border-left: 1.5px solid #e2e8f0; border-right: 1.5px solid #e2e8f0; height: 100%; display: flex; align-items: center; justify-content: center; min-width: 40px; transition: color 0.2s, background 0.2s; }
.ctrl-qty.active { color: #d97706; background: #fff; }
.product-card.in-cart .card-controls { border-color: #fcd34d; background: #fff; }
.product-card.in-cart .ctrl-qty { border-color: #fde68a; }
/* Floating bar */
.checkout-bar { position: fixed; bottom: 0; left: 0; right: 0; background: #451a03; color: #fff; display: flex; align-items: center; justify-content: space-between; padding: 1rem 2rem; z-index: 90; box-shadow: 0 -4px 24px rgba(0,0,0,0.18); }
.checkout-bar__left { display: flex; flex-direction: column; gap: 0.1rem; }
.bar-items { font-size: 0.75rem; color: #fcd34d; font-weight: 500; }
.bar-total { font-size: 1.1rem; font-weight: 700; }
.bar-btn { display: flex; align-items: center; gap: 0.5rem; background: #d97706; color: #fff; border: none; border-radius: 10px; padding: 0.7rem 1.5rem; font-size: 0.875rem; font-weight: 700; cursor: pointer; font-family: 'Sora', sans-serif; transition: background 0.2s; }
.bar-btn:hover { background: #b45309; }
.bar-rise-enter-active, .bar-rise-leave-active { transition: transform 0.3s cubic-bezier(0.4,0,0.2,1); }
.bar-rise-enter-from, .bar-rise-leave-to { transform: translateY(100%); }
/* History */
.history-panel { margin-top: 0.5rem; }
.history-table-wrap { background: #fff; border-radius: 14px; border: 1px solid #e2e8f0; overflow: hidden; }
.history-table { width: 100%; border-collapse: collapse; font-size: 0.82rem; }
.history-table thead { background: #f8fafc; }
.history-table th { padding: 0.8rem 1rem; text-align: left; font-weight: 600; color: #64748b; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 1px solid #e2e8f0; }
.history-table td { padding: 0.8rem 1rem; color: #374151; border-bottom: 1px solid #f1f5f9; }
.history-table tr:last-child td { border-bottom: none; }
.history-table tr:hover td { background: #fafaf9; }
.product-pill { background: #fffbeb; color: #d97706; font-weight: 600; padding: 0.2rem 0.6rem; border-radius: 6px; font-size: 0.75rem; }
.company-pill { background: #eff6ff; color: #2563eb; font-weight: 600; padding: 0.2rem 0.6rem; border-radius: 6px; font-size: 0.75rem; }
.muted { color: #cbd5e1; font-size: 0.75rem; }
.branch-pill { font-weight: 600; padding: 0.2rem 0.6rem; border-radius: 6px; font-size: 0.75rem; }
.branch-pill.from { background: #eff6ff; color: #2563eb; }
.branch-pill.to   { background: #f0fdf4; color: #059669; }
.qty-cell { font-weight: 700; font-family: 'JetBrains Mono', monospace; color: #0f172a; }
.notes-cell { color: #94a3b8; font-size: 0.78rem; max-width: 150px; }
.status-badge { font-size: 0.7rem; font-weight: 700; padding: 0.2rem 0.6rem; border-radius: 20px; text-transform: capitalize; background: #f0fdf4; color: #059669; }
/* State */
.state-box { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 4rem 2rem; gap: 1rem; color: #94a3b8; }
.empty-icon { font-size: 2.5rem; }
.spinner { width: 28px; height: 28px; border: 3px solid #e2e8f0; border-top-color: #f59e0b; border-radius: 50%; animation: spin 0.7s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
/* Panel */
.overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.2); backdrop-filter: blur(2px); z-index: 100; }
.fade-enter-active, .fade-leave-active { transition: opacity 0.25s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
.cart-panel { position: fixed; top: 0; right: 0; bottom: 0; width: 460px; background: #fff; z-index: 101; display: flex; flex-direction: column; box-shadow: -4px 0 32px rgba(0,0,0,0.12); }
.slide-enter-active, .slide-leave-active { transition: transform 0.3s cubic-bezier(0.4,0,0.2,1); }
.slide-enter-from, .slide-leave-to { transform: translateX(100%); }
.cart-panel__header { display: flex; align-items: center; justify-content: space-between; padding: 1.4rem 1.75rem; border-bottom: 1px solid #f1f5f9; }
.cart-panel__header h2 { font-size: 1.1rem; font-weight: 700; color: #0f172a; margin: 0; }
.cart-panel__header h2 span { font-size: 0.8rem; font-weight: 400; color: #94a3b8; margin-left: 0.4rem; }
.close-btn { background: #f1f5f9; border: none; border-radius: 8px; width: 32px; height: 32px; cursor: pointer; color: #64748b; font-size: 0.85rem; transition: background 0.15s; }
.close-btn:hover { background: #e2e8f0; }
.cart-panel__body { flex: 1; overflow-y: auto; padding: 1.25rem 1.75rem; display: flex; flex-direction: column; gap: 0.75rem; }
.transfer-summary { display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem; background: #f8fafc; border-radius: 10px; border: 1px solid #e2e8f0; }
.branch-summary-pill { font-size: 0.78rem; font-weight: 700; padding: 0.3rem 0.75rem; border-radius: 6px; flex: 1; text-align: center; }
.branch-summary-pill.from { background: #eff6ff; color: #2563eb; }
.branch-summary-pill.to   { background: #f0fdf4; color: #059669; }
.cart-item { display: flex; align-items: center; gap: 0.75rem; background: #f8fafc; border-radius: 12px; padding: 0.85rem; border: 1px solid #e2e8f0; }
.cart-item__icon { font-size: 1.5rem; flex-shrink: 0; }
.cart-item__info { flex: 1; min-width: 0; }
.cart-item__name    { font-size: 0.85rem; font-weight: 700; color: #0f172a; margin: 0 0 0.1rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.cart-item__company { font-size: 0.72rem; font-weight: 600; color: #2563eb; margin: 0 0 0.1rem; }
.cart-item__sku     { font-size: 0.7rem; color: #d97706; font-weight: 600; margin: 0; }
.cart-item__controls { display: flex; align-items: center; gap: 0.4rem; flex-shrink: 0; }
.qty-btn { background: #e2e8f0; border: none; border-radius: 6px; width: 26px; height: 26px; cursor: pointer; font-size: 1rem; color: #0f172a; display: flex; align-items: center; justify-content: center; font-weight: 700; transition: background 0.15s; }
.qty-btn:hover { background: #cbd5e1; }
.qty-val { font-size: 0.85rem; font-weight: 700; color: #0f172a; min-width: 20px; text-align: center; }
.remove-btn { background: none; border: none; color: #cbd5e1; cursor: pointer; font-size: 0.75rem; flex-shrink: 0; padding: 4px; border-radius: 4px; transition: color 0.15s; }
.remove-btn:hover { color: #ef4444; }
.checkout-fields { display: flex; flex-direction: column; gap: 0.85rem; margin-top: 0.5rem; padding-top: 1rem; border-top: 1px dashed #e2e8f0; }
.field { display: flex; flex-direction: column; gap: 0.3rem; }
.field label { font-size: 0.75rem; font-weight: 600; color: #374151; }
.field textarea { border: 1.5px solid #e2e8f0; border-radius: 8px; padding: 0.5rem 0.75rem; font-size: 0.82rem; font-family: 'Sora', sans-serif; color: #0f172a; outline: none; resize: none; transition: border-color 0.15s; }
.field textarea:focus { border-color: #f59e0b; }
.cart-panel__footer { padding: 1.25rem 1.75rem; border-top: 1px solid #f1f5f9; display: flex; flex-direction: column; gap: 0.75rem; }
.transfer-count { display: flex; justify-content: space-between; align-items: center; }
.transfer-count span { font-size: 0.85rem; color: #64748b; font-weight: 500; }
.transfer-count strong { font-size: 1rem; font-weight: 700; color: #0f172a; font-family: 'JetBrains Mono', monospace; }
.btn-checkout { background: #d97706; color: #fff; border: none; border-radius: 10px; padding: 0.75rem; font-size: 0.875rem; font-weight: 700; cursor: pointer; font-family: 'Sora', sans-serif; display: flex; align-items: center; justify-content: center; gap: 0.5rem; transition: background 0.2s; }
.btn-checkout:hover:not(:disabled) { background: #b45309; }
.btn-checkout:disabled { opacity: 0.6; cursor: not-allowed; }
.btn-spinner { width: 14px; height: 14px; border: 2px solid rgba(255,255,255,0.4); border-top-color: #fff; border-radius: 50%; animation: spin 0.6s linear infinite; }
.alert { padding: 0.6rem 0.85rem; border-radius: 8px; font-size: 0.8rem; font-weight: 500; }
.alert--error   { background: #fff1f2; color: #be123c; border: 1px solid #fecdd3; }
.alert--success { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }
</style>