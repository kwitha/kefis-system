<template>
  <DashboardLayout>
    <div class="products-page">

      <!-- LOW STOCK BANNER -->
      <div class="stock-alert-banner" v-if="lowStockProducts.length > 0">
        <span class="stock-alert-banner__icon">⚠️</span>
        <span class="stock-alert-banner__text">
          {{ lowStockProducts.length }} product(s) are running low on stock:
          <strong>{{ lowStockProducts.map(p => p.name).join(', ') }}</strong>
        </span>
      </div>

      <!-- Page Header -->
      <div class="page-header">
        <div>
          <h1 class="page-title">Products</h1>
          <p class="page-sub">Manage your product catalogue and supplier companies</p>
        </div>
        <button class="btn-add" @click="openAddProduct">
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
            <path d="M8 3v10M3 8h10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
          </svg>
          Add Product
        </button>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="state-box"><div class="spinner"></div></div>

      <div v-else>
        <!-- Category sections -->
        <div v-for="(group, category) in groupedProducts" :key="category" class="category-section">
          <div class="category-header">
            <span class="category-icon">{{ getCategoryIcon(category) }}</span>
            <span class="category-name">{{ category }}</span>
            <span class="category-count">{{ group.length }}</span>
          </div>

          <div class="product-grid">
            <div class="product-card" v-for="p in group" :key="p.id">
              <div class="card-top">
                <div class="product-icon">{{ getCategoryIcon(category) }}</div>
                <div class="product-info">
                  <span class="product-sku">{{ p.sku }}</span>
                  <h4 class="product-name">{{ p.name }}</h4>
                  <span class="product-unit">per {{ p.unit }}</span>
                </div>
                <div class="card-actions">
                  <button class="icon-btn edit" @click="openEdit(p)" title="Edit product">✏️</button>
                  <button class="icon-btn delete" @click="confirmDelete(p)" title="Delete product">🗑️</button>
                </div>
              </div>

             <div class="card-prices">
  <div class="price-row">
    <span class="price-label">Sell</span>
    <span class="price-val sell">
      <template v-if="p.category === 'Groceries'">
        From KES {{ getLowestPrice(p.companies).toLocaleString() }}
      </template>
      <template v-else>
        KES {{ Number(p.selling_price || 0).toLocaleString() }}
      </template>
    </span>
  </div>

  <!-- ✅ ADD THIS -->
  <div class="price-row" v-if="p.category !== 'Groceries'">
    <span class="price-label">Buy</span>
    <span class="price-val buy">
      KES {{ Number(p.buying_price || 0).toLocaleString() }}
    </span>
  </div>
</div>

              <!-- Companies section — Grocery products only -->
              <div class="companies-section" v-if="p.category === 'Groceries'">
                <div class="companies-header">
                  <span class="companies-title">
                    🏭 Suppliers
                    <span class="companies-count">{{ (p.companies ?? []).length }}</span>
                  </span>
                  <button
                    class="btn-add-company"
                    @click="openAddCompany(p)"
                    :disabled="(p.companies ?? []).length >= 4"
                    title="Add supplier company"
                  >+ Add</button>
                </div>

                <div class="company-list" v-if="(p.companies ?? []).length > 0">
                  <div class="company-row" v-for="c in p.companies" :key="c.id">
                    <span class="company-name">{{ c.name }}</span>
                    <span class="company-price">
                       Buy: KES {{ Number(c.buying_price).toLocaleString() }} |
                       Sell: KES {{ Number(c.selling_price).toLocaleString() }}
                      </span>
                    <div class="company-actions">
                      <button class="icon-btn-sm edit" @click="openEditCompany(p, c)" title="Edit">✏️</button>
                      <button class="icon-btn-sm delete" @click="deleteCompany(p, c)" title="Remove">✕</button>
                    </div>
                  </div>
                </div>
                <div class="companies-empty" v-else>
                  No suppliers yet — add up to 4
                </div>
              </div>

              <div
                class="stock-status-chip"
                :class="{
                  'ok':       p.stock_status === 'ok',
                  'low':      p.stock_status === 'low',
                  'out':      p.stock_status === 'out_of_stock',
                }"
              >
                {{
                  p.stock_status === 'ok'          ? '✅ In stock' :
                  p.stock_status === 'low'          ? '⚠️ Low stock' :
                  p.stock_status === 'out_of_stock' ? '🔴 Out of stock' : '—'
                }}
              </div>
            </div>
          </div>
        </div>

        <div class="state-box" v-if="Object.keys(groupedProducts).length === 0">
          <div class="empty-icon">📦</div><p>No products yet. Add your first product.</p>
        </div>
      </div>

      <!-- ═══════════════════════════════════════════════════════ -->
      <!-- PRODUCT ADD/EDIT PANEL                                  -->
      <!-- ═══════════════════════════════════════════════════════ -->
      <transition name="fade"><div class="overlay" v-if="productPanelOpen" @click.self="productPanelOpen = false"></div></transition>
      <transition name="slide">
        <div class="side-panel" v-if="productPanelOpen">
          <div class="side-panel__header">
            <h2>{{ editingProduct ? 'Edit Product' : 'Add Product' }}</h2>
            <button class="close-btn" @click="productPanelOpen = false">✕</button>
          </div>
          <div class="side-panel__body">
            <div class="field">
              <label>Name <span class="req">*</span></label>
              <input v-model="productForm.name" type="text" placeholder="e.g. Sugar" />
            </div>
            <div class="field">
              <label>SKU</label>
              <input v-model="productForm.sku" type="text" placeholder="Auto-generated if empty" />
            </div>
            <div class="field-row">
              <div class="field">
                <label>Category</label>
                <select v-model="productForm.category">
                  <option value="">Select category</option>
                  <option v-for="c in categories" :key="c" :value="c">{{ c }}</option>
                </select>
              </div>
              <div class="field">
                <label>Unit <span class="req">*</span></label>
                <select v-model="productForm.unit">
                  <option value="">Select unit</option>
                  <option value="kg">kg</option>
                  <option value="litres">litres</option>
                  <option value="grams">grams</option>
                  <option value="pieces">pieces</option>
                  <option value="pack">pack</option>
                </select>
              </div>
            </div>
            <div class="field-row">
              <div class="field">
                <label>Buying Price <span class="req">*</span></label>
                <input v-model="productForm.buying_price" type="number" min="0" placeholder="0.00" />
              </div>
              <div class="field" v-if="productForm.category !== 'Groceries'">
                <label>Selling Price <span class="req">*</span></label>
                <input v-model="productForm.selling_price" type="number" min="0" placeholder="0.00" />
              </div>
            </div>
            <div class="field">
              <label>Minimum Stock</label>
              <input v-model="productForm.minimum_stock" type="number" min="0" placeholder="10" />
            </div>
            <div class="field-note" v-if="productForm.category === 'Groceries'">
              💡 Grocery products use supplier company prices instead of a single selling price. Add companies after saving.
            </div>
          </div>
          <div class="side-panel__footer">
            <div class="alert alert--error" v-if="formError">{{ formError }}</div>
            <div class="alert alert--success" v-if="formSuccess">{{ formSuccess }}</div>
            <button class="btn-confirm" :disabled="submitting" @click="saveProduct">
              <span class="btn-spinner" v-if="submitting"></span>
              {{ submitting ? 'Saving…' : editingProduct ? 'Update Product' : 'Add Product' }}
            </button>
          </div>
        </div>
      </transition>

      <!-- ═══════════════════════════════════════════════════════ -->
      <!-- COMPANY ADD/EDIT PANEL                                  -->
      <!-- ═══════════════════════════════════════════════════════ -->
      <transition name="fade"><div class="overlay overlay--top" v-if="companyPanelOpen" @click.self="companyPanelOpen = false"></div></transition>
      <transition name="slide">
        <div class="side-panel company-panel" v-if="companyPanelOpen">
          <div class="side-panel__header">
            <div>
              <h2>{{ editingCompany ? 'Edit Supplier' : 'Add Supplier' }}</h2>
              <p class="panel-sub">{{ activeProduct?.name }}</p>
            </div>
            <button class="close-btn" @click="companyPanelOpen = false">✕</button>
          </div>
          <div class="side-panel__body">
            <div class="existing-companies" v-if="(activeProduct?.companies ?? []).length > 0">
              <p class="section-label">Current suppliers ({{ (activeProduct?.companies ?? []).length }}/4)</p>
              <div class="company-pill" v-for="c in activeProduct?.companies" :key="c.id">
                <span class="cp-name">{{ c.name }}</span>
                <span class="cp-price"> Buy: KES {{ Number(c.buying_price ?? 0).toLocaleString() }}|
                                        sell: KES {{ Number(c.selling_price ?? 0) .toLocaleString() }} |
                                        stock: KES {{ Number(c.stock) .toLocaleString() }}
                </span>
              </div>
            </div>
            <div class="field">
              <label>Company / Supplier Name <span class="req">*</span></label>
              <input v-model="companyForm.name" type="text" placeholder="e.g. Mumias Sugar" />
            </div>
            <div class="field">
              <label>buying_price {{ activeProduct?.unit }} <span class="req">*</span></label>
              <input v-model="companyForm.buying_price" type="number" min="0" placeholder="0.00" />
            </div>
            <div class="field">
              <label>selling_price {{ activeProduct?.unit }} <span class="req">*</span></label>
              <input v-model="companyForm.selling_price" type="number" min="0" placeholder="0.00" />
            </div>
            <div class="field">
              <label>stock {{ activeProduct?.unit }} <span class="req">*</span></label>
              <input v-model="companyForm.stock" type="number" min="0" placeholder="0.00" />
            </div>
            <div class="field-toggle">
              <label class="toggle-label">
                <input type="checkbox" v-model="companyForm.is_active" class="toggle-checkbox" />
                <span class="toggle-track">
                  <span class="toggle-thumb"></span>
                </span>
                Active
              </label>
            </div>
          </div>
          <div class="side-panel__footer">
            <div class="alert alert--error" v-if="companyError">{{ companyError }}</div>
            <div class="alert alert--success" v-if="companySuccess">{{ companySuccess }}</div>
            <button class="btn-confirm" :disabled="companySubmitting" @click="saveCompany">
              <span class="btn-spinner" v-if="companySubmitting"></span>
              {{ companySubmitting ? 'Saving…' : editingCompany ? 'Update Supplier' : 'Add Supplier' }}
            </button>
          </div>
        </div>
      </transition>

      <!-- Delete confirm modal -->
      <transition name="fade">
        <div class="modal-overlay" v-if="deleteTarget" @click.self="deleteTarget = null">
          <div class="modal">
            <h3>Delete Product?</h3>
            <p>This will permanently delete <strong>{{ deleteTarget?.name }}</strong> and all its stock data.</p>
            <div class="modal-actions">
              <button class="btn-cancel" @click="deleteTarget = null">Cancel</button>
              <button class="btn-delete" :disabled="submitting" @click="doDelete">
                <span class="btn-spinner" v-if="submitting"></span>
                {{ submitting ? 'Deleting…' : 'Delete' }}
              </button>
            </div>
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

const authStore = useAuthStore()

const products    = ref<any[]>([])
const loading     = ref(false)
const submitting  = ref(false)
const formError   = ref('')
const formSuccess = ref('')

// ── Product panel ──
const productPanelOpen = ref(false)
const editingProduct   = ref<any>(null)
const deleteTarget     = ref<any>(null)

const productForm = ref({
  name: '', sku: '', category: '', unit: '',
  buying_price: '', selling_price: '', minimum_stock: 10,
})

// ── Company panel ──
const companyPanelOpen  = ref(false)
const editingCompany    = ref<any>(null)
const activeProduct     = ref<any>(null)
const companySubmitting = ref(false)
const companyError      = ref('')
const companySuccess    = ref('')

const companyForm = ref({ name: '', buying_price: '',selling_price: '',stock: 0, is_active: true })

const categories = ['Groceries', 'Dairy', 'Bakery', 'Beverages', 'Snacks', 'Cleaning', 'Stationery', 'Electronics', 'Clothing', 'Furniture']

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

const groupedProducts = computed(() => {
  const g: Record<string, any[]> = {}
  for (const p of products.value) {
    const c = p.category ?? 'Uncategorised'
    if (!g[c]) g[c] = []
    g[c].push(p)
  }
  return g
})

const lowStockProducts = computed(() =>
  products.value.filter(p => p.stock_status === 'low' || p.stock_status === 'out_of_stock')
)

const getLowestPrice = (companies: any[]) => {
  if (!companies?.length) return 0
  const prices = companies
  .map(c => Number(c?.selling_price))
  .filter(p =>!isNaN(p))
  if(prices.length)return 0

  return Math.min(...prices)
}

// ── Load ──────────────────────────────────────────────────────────────────────
const loadProducts = async () => {
  loading.value = true
  try {
    const bid = authStore.user?.branch_id
    const res = await api.get('/products', { params: bid ? { branch_id: bid } : {} })
    products.value = res.data
    console.log('PRODUCTS:',products.value);    
  } catch (e) { console.error(e) }
  finally { loading.value = false }
}

// ── Product CRUD ──────────────────────────────────────────────────────────────
const openAddProduct = () => {
  editingProduct.value = null
  productForm.value = { name: '', sku: '', category: '', unit: '', buying_price: '', selling_price: '', minimum_stock: 10 }
  formError.value = ''; formSuccess.value = ''
  productPanelOpen.value = true
}

const openEdit = (p: any) => {
  editingProduct.value = p
  productForm.value = {
    name: p.name, sku: p.sku, category: p.category ?? '',
    unit: p.unit, buying_price: p.buying_price,
    selling_price: p.selling_price, minimum_stock: p.minimum_stock ?? 10,
  }
  formError.value = ''; formSuccess.value = ''
  productPanelOpen.value = true
}

const saveProduct = async () => {
  formError.value = ''
  if (!productForm.value.name) { formError.value = 'Name is required.'; return }
  if (!productForm.value.unit) { formError.value = 'Unit is required.'; return }
  submitting.value = true
  try {
    const payload = { ...productForm.value }
    if (payload.category === 'Groceries') payload.selling_price = '0'
    if (editingProduct.value) {
      await api.put(`/products/${editingProduct.value.id}`, payload)
      formSuccess.value = 'Product updated!'
    } else {
      await api.post('/products', payload)
      formSuccess.value = 'Product added!'
    }
    await loadProducts()
    setTimeout(() => { productPanelOpen.value = false; formSuccess.value = '' }, 1200)
  } catch (e: any) {
    formError.value = e.response?.data?.errors
      ? Object.values(e.response.data.errors).flat().join(' ')
      : e.response?.data?.message ?? 'Failed to save.'
  } finally { submitting.value = false }
}

const confirmDelete = (p: any) => { deleteTarget.value = p }
const doDelete = async () => {
  if (!deleteTarget.value) return
  submitting.value = true
  try {
    await api.delete(`/products/${deleteTarget.value.id}`)
    await loadProducts()
    deleteTarget.value = null
  } catch (e: any) {
    formError.value = e.response?.data?.message ?? 'Delete failed.'
  } finally { submitting.value = false }
}

// ── Company CRUD ──────────────────────────────────────────────────────────────
const openAddCompany = (product: any) => {
  if ((product.companies ?? []).length >= 4) return
  activeProduct.value = product
  editingCompany.value = null
  companyForm.value = { name: '', buying_price: '',selling_price: '', stock: 0, is_active: true }
  companyError.value = ''; companySuccess.value = ''
  companyPanelOpen.value = true
}

const openEditCompany = (product: any, company: any) => {
  activeProduct.value = product
  editingCompany.value = company
  companyForm.value = { name: company.name, 
    buying_price: company.buying_price ?? 0, 
    selling_price: company.selling_price ?? 0,
    stock: company.stock ?? 0,
    is_active: company.is_active

   }
  companyError.value = ''; companySuccess.value = ''
  companyPanelOpen.value = true
}

const saveCompany = async () => {
  companyError.value = ''
  if (!companyForm.value.name) { companyError.value = 'Company name is required.'; return }
  if (companyForm.value.buying_price === '' || companyForm.value.buying_price == null) { 
    companyError.value = 'buying_price is required.'; return 
  }
  if (companyForm.value.selling_price === '' || companyForm.value.selling_price == null){
    companyError.value = 'selling_price is required.'
    return
  }
  companySubmitting.value = true
  try {
    const payload ={
      name: companyForm.value.name,
      buying_price: companyForm.value.buying_price,
      selling_price: companyForm.value.selling_price,
      stock: companyForm.value.stock,
      is_active: companyForm.value.is_active,
    }
    if (editingCompany.value) {
      await api.put(`/products/${activeProduct.value.id}/companies/${editingCompany.value.id}`, payload)
      companySuccess.value = 'Supplier updated!'
    } else {
      await api.post(`/products/${activeProduct.value.id}/companies`, payload)
      companySuccess.value = 'Supplier added!'
    }
    await loadProducts()
    // Refresh activeProduct ref so company list updates live in panel
    activeProduct.value = products.value.find(p => p.id === activeProduct.value.id) ?? activeProduct.value
    setTimeout(() => {
      if (!editingCompany.value) companyForm.value = { name: '', buying_price: '', selling_price: '',stock: 0, is_active: true }
      companySuccess.value = ''
    }, 1200)
  } catch (e: any) {
    companyError.value = e.response?.data?.message ?? 'Failed to save supplier.'
  } finally { companySubmitting.value = false }
}

const deleteCompany = async (product: any, company: any) => {
  if (!confirm(`Remove ${company.name} from ${product.name}?`)) return
  try {
    await api.delete(`/products/${product.id}/companies/${company.id}`)
    await loadProducts()
    if (activeProduct.value?.id === product.id) {
      activeProduct.value = products.value.find(p => p.id === product.id) ?? null
    }
  } catch (e: any) {
    alert(e.response?.data?.message ?? 'Delete failed.')
  }
}

onMounted(() => loadProducts())
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap');
* { box-sizing: border-box; }
.products-page { padding: 2rem; min-height: 100vh; background: #f7f8fa; font-family: 'Sora', sans-serif; }

/* Alert banner */
.stock-alert-banner { display: flex; align-items: center; gap: 0.75rem; background: #fffbeb; border: 1.5px solid #fcd34d; border-radius: 12px; padding: 0.85rem 1.25rem; margin-bottom: 1.5rem; font-size: 0.82rem; color: #92400e; }
.stock-alert-banner__icon { font-size: 1.1rem; flex-shrink: 0; }

/* Header */
.page-header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 1.75rem; flex-wrap: wrap; gap: 1rem; }
.page-title  { font-size: 1.7rem; font-weight: 700; color: #0f172a; letter-spacing: -0.03em; margin: 0 0 0.2rem; }
.page-sub    { font-size: 0.82rem; color: #94a3b8; margin: 0; }
.btn-add     { display: flex; align-items: center; gap: 0.5rem; background: #0f172a; color: #fff; border: none; border-radius: 10px; padding: 0.6rem 1.2rem; font-size: 0.82rem; font-weight: 700; cursor: pointer; font-family: 'Sora', sans-serif; transition: background 0.2s; }
.btn-add:hover { background: #1e293b; }

/* Category */
.category-section { margin-bottom: 2rem; }
.category-header  { display: flex; align-items: center; gap: 0.6rem; margin-bottom: 0.9rem; padding-bottom: 0.5rem; border-bottom: 1.5px solid #e2e8f0; }
.category-icon  { font-size: 1.2rem; }
.category-name  { font-size: 0.95rem; font-weight: 700; color: #0f172a; }
.category-count { font-size: 0.72rem; font-weight: 600; color: #94a3b8; background: #f1f5f9; padding: 0.15rem 0.5rem; border-radius: 20px; }

/* Product grid */
.product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 1rem; }
.product-card { background: #fff; border: 1.5px solid #e2e8f0; border-radius: 16px; padding: 1rem; display: flex; flex-direction: column; gap: 0.75rem; }

/* Card top */
.card-top { display: flex; gap: 0.75rem; align-items: flex-start; }
.product-icon   { font-size: 1.75rem; width: 44px; height: 44px; background: #f8fafc; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.product-info   { flex: 1; min-width: 0; }
.product-sku    { font-size: 0.62rem; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.06em; font-family: 'JetBrains Mono', monospace; display: block; }
.product-name   { font-size: 0.88rem; font-weight: 700; color: #0f172a; margin: 0.15rem 0 0.1rem; }
.product-unit   { font-size: 0.7rem; color: #94a3b8; }
.card-actions   { display: flex; gap: 0.25rem; flex-shrink: 0; }
.icon-btn       { background: none; border: none; cursor: pointer; font-size: 0.85rem; padding: 4px; border-radius: 6px; transition: background 0.15s; }
.icon-btn.edit:hover   { background: #eff6ff; }
.icon-btn.delete:hover { background: #fff1f2; }

/* Prices */
.card-prices { display: flex; gap: 0.5rem; }
.price-row    { display: flex; flex-direction: column; gap: 0.1rem; background: #f8fafc; border-radius: 8px; padding: 0.4rem 0.7rem; flex: 1; }
.price-label  { font-size: 0.62rem; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; }
.price-val    { font-size: 0.82rem; font-weight: 700; font-family: 'JetBrains Mono', monospace; }
.price-val.buy  { color: #dc2626; }
.price-val.sell { color: #059669; }

/* Companies section */
.companies-section { border-top: 1px dashed #e2e8f0; padding-top: 0.75rem; display: flex; flex-direction: column; gap: 0.5rem; }
.companies-header  { display: flex; align-items: center; justify-content: space-between; }
.companies-title   { font-size: 0.72rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; display: flex; align-items: center; gap: 0.3rem; }
.companies-count   { background: #f1f5f9; color: #64748b; padding: 0.1rem 0.4rem; border-radius: 10px; font-size: 0.65rem; }
.btn-add-company   { font-size: 0.72rem; font-weight: 700; background: #0f172a; color: #fff; border: none; border-radius: 6px; padding: 0.25rem 0.6rem; cursor: pointer; transition: background 0.15s; font-family: 'Sora', sans-serif; }
.btn-add-company:hover:not(:disabled) { background: #1e293b; }
.btn-add-company:disabled { opacity: 0.4; cursor: not-allowed; }
.company-list  { display: flex; flex-direction: column; gap: 0.35rem; }
.company-row   { display: flex; align-items: center; gap: 0.5rem; background: #f8fafc; border-radius: 8px; padding: 0.4rem 0.6rem; border: 1px solid #e2e8f0; }
.company-name  { font-size: 0.75rem; font-weight: 600; color: #0f172a; flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.company-price { font-size: 0.72rem; font-weight: 700; color: #059669; font-family: 'JetBrains Mono', monospace; white-space: nowrap; }
.company-actions { display: flex; gap: 0.2rem; flex-shrink: 0; }
.icon-btn-sm   { background: none; border: none; cursor: pointer; font-size: 0.72rem; padding: 3px; border-radius: 4px; transition: background 0.15s; }
.icon-btn-sm.edit:hover   { background: #eff6ff; }
.icon-btn-sm.delete:hover { background: #fff1f2; color: #ef4444; }
.companies-empty { font-size: 0.72rem; color: #94a3b8; font-style: italic; padding: 0.3rem 0; }

/* Stock chip */
.stock-status-chip { font-size: 0.7rem; font-weight: 600; padding: 0.25rem 0.6rem; border-radius: 20px; text-align: center; }
.stock-status-chip.ok  { background: #f0fdf4; color: #059669; }
.stock-status-chip.low { background: #fffbeb; color: #d97706; }
.stock-status-chip.out { background: #fff1f2; color: #dc2626; }

/* State */
.state-box  { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 4rem 2rem; gap: 1rem; color: #94a3b8; }
.empty-icon { font-size: 2.5rem; }
.spinner    { width: 28px; height: 28px; border: 3px solid #e2e8f0; border-top-color: #0f172a; border-radius: 50%; animation: spin 0.7s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

/* Overlay & panels */
.overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.25); backdrop-filter: blur(2px); z-index: 100; }
.overlay--top { z-index: 110; }
.fade-enter-active, .fade-leave-active { transition: opacity 0.25s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
.side-panel { position: fixed; top: 0; right: 0; bottom: 0; width: 420px; background: #fff; z-index: 111; display: flex; flex-direction: column; box-shadow: -4px 0 32px rgba(0,0,0,0.12); }
.slide-enter-active, .slide-leave-active { transition: transform 0.3s cubic-bezier(0.4,0,0.2,1); }
.slide-enter-from, .slide-leave-to { transform: translateX(100%); }
.side-panel__header { display: flex; align-items: flex-start; justify-content: space-between; padding: 1.4rem 1.75rem; border-bottom: 1px solid #f1f5f9; }
.side-panel__header h2 { font-size: 1.1rem; font-weight: 700; color: #0f172a; margin: 0 0 0.15rem; }
.panel-sub { font-size: 0.78rem; color: #94a3b8; margin: 0; }
.close-btn { background: #f1f5f9; border: none; border-radius: 8px; width: 32px; height: 32px; cursor: pointer; color: #64748b; font-size: 0.85rem; transition: background 0.15s; flex-shrink: 0; }
.close-btn:hover { background: #e2e8f0; }
.side-panel__body { flex: 1; overflow-y: auto; padding: 1.25rem 1.75rem; display: flex; flex-direction: column; gap: 0.85rem; }
.side-panel__footer { padding: 1.25rem 1.75rem; border-top: 1px solid #f1f5f9; display: flex; flex-direction: column; gap: 0.75rem; }

/* Company panel z-index override */
.company-panel { z-index: 120; }

/* Existing companies in company panel */
.existing-companies { background: #f8fafc; border-radius: 10px; padding: 0.85rem 1rem; border: 1px solid #e2e8f0; display: flex; flex-direction: column; gap: 0.4rem; margin-bottom: 0.5rem; }
.section-label { font-size: 0.68rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; margin: 0 0 0.4rem; }
.company-pill  { display: flex; align-items: center; justify-content: space-between; background: #fff; border: 1px solid #e2e8f0; border-radius: 7px; padding: 0.3rem 0.6rem; }
.cp-name  { font-size: 0.75rem; font-weight: 600; color: #0f172a; }
.cp-price { font-size: 0.72rem; font-weight: 700; color: #059669; font-family: 'JetBrains Mono', monospace; }

/* Fields */
.field          { display: flex; flex-direction: column; gap: 0.3rem; }
.field-row      { display: grid; grid-template-columns: 1fr 1fr; gap: 0.6rem; }
.field label    { font-size: 0.75rem; font-weight: 600; color: #374151; }
.req            { color: #ef4444; }
.field input,
.field select,
.field textarea { border: 1.5px solid #e2e8f0; border-radius: 8px; padding: 0.5rem 0.75rem; font-size: 0.82rem; font-family: 'Sora', sans-serif; color: #0f172a; outline: none; transition: border-color 0.15s; width: 100%; }
.field input:focus,
.field select:focus,
.field textarea:focus { border-color: #0f172a; }
.field-note { font-size: 0.75rem; color: #64748b; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 0.6rem 0.85rem; }

/* Toggle */
.field-toggle    { display: flex; align-items: center; }
.toggle-label    { display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-size: 0.82rem; font-weight: 600; color: #374151; user-select: none; }
.toggle-checkbox { display: none; }
.toggle-track    { width: 36px; height: 20px; background: #e2e8f0; border-radius: 10px; position: relative; transition: background 0.2s; }
.toggle-checkbox:checked + .toggle-track { background: #10b981; }
.toggle-thumb    { position: absolute; top: 2px; left: 2px; width: 16px; height: 16px; background: #fff; border-radius: 50%; transition: transform 0.2s; box-shadow: 0 1px 3px rgba(0,0,0,0.15); }
.toggle-checkbox:checked + .toggle-track .toggle-thumb { transform: translateX(16px); }

/* Confirm / delete buttons */
.btn-confirm   { background: #0f172a; color: #fff; border: none; border-radius: 10px; padding: 0.75rem; font-size: 0.875rem; font-weight: 700; cursor: pointer; font-family: 'Sora', sans-serif; display: flex; align-items: center; justify-content: center; gap: 0.5rem; transition: background 0.2s; }
.btn-confirm:hover:not(:disabled) { background: #1e293b; }
.btn-confirm:disabled { opacity: 0.6; cursor: not-allowed; }
.btn-spinner   { width: 14px; height: 14px; border: 2px solid rgba(255,255,255,0.4); border-top-color: #fff; border-radius: 50%; animation: spin 0.6s linear infinite; }
.alert         { padding: 0.6rem 0.85rem; border-radius: 8px; font-size: 0.8rem; font-weight: 500; }
.alert--error  { background: #fff1f2; color: #be123c; border: 1px solid #fecdd3; }
.alert--success{ background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }

/* Delete modal */
.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.3); backdrop-filter: blur(2px); z-index: 200; display: flex; align-items: center; justify-content: center; }
.modal         { background: #fff; border-radius: 16px; padding: 2rem; max-width: 380px; width: 90%; box-shadow: 0 8px 40px rgba(0,0,0,0.15); }
.modal h3      { font-size: 1.1rem; font-weight: 700; color: #0f172a; margin: 0 0 0.5rem; }
.modal p       { font-size: 0.85rem; color: #64748b; margin: 0 0 1.5rem; line-height: 1.5; }
.modal-actions { display: flex; gap: 0.75rem; }
.btn-cancel    { flex: 1; background: #f1f5f9; border: none; border-radius: 8px; padding: 0.7rem; font-size: 0.85rem; font-weight: 600; color: #64748b; cursor: pointer; font-family: 'Sora', sans-serif; transition: background 0.15s; }
.btn-cancel:hover { background: #e2e8f0; }
.btn-delete    { flex: 1; background: #dc2626; color: #fff; border: none; border-radius: 8px; padding: 0.7rem; font-size: 0.85rem; font-weight: 700; cursor: pointer; font-family: 'Sora', sans-serif; display: flex; align-items: center; justify-content: center; gap: 0.4rem; transition: background 0.2s; }
.btn-delete:hover:not(:disabled) { background: #b91c1c; }
.btn-delete:disabled { opacity: 0.6; cursor: not-allowed; }
</style>