<template>
  <DashboardLayout>
    <div class="page">

      <div class="page-header">
        <div>
          <h1 class="page-title">📦 Stock Report</h1>
          <p class="page-sub">Current stock levels across branches, grouped by product and supplier</p>
        </div>
        <button class="btn-export" @click="exportCSV">
          <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
            <path d="M7 1v8M4 6l3 3 3-3M2 11h10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          Export CSV
        </button>
      </div>

      <!-- Filters -->
      <div class="filter-bar">
        <div class="filter-group">
          <label>Branch</label>
          <select v-model="filters.branch_id" class="filter-select" @change="loadStock">
            <option value="">All Branches</option>
            <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
          </select>
        </div>
        <div class="filter-group">
          <label>Category</label>
          <select v-model="filters.category" class="filter-select">
            <option value="">All Categories</option>
            <option v-for="c in categories" :key="c" :value="c">{{ c }}</option>
          </select>
        </div>
        <div class="filter-group search-group">
          <label>Search</label>
          <input v-model="filters.search" type="text" class="filter-input" placeholder="Product name…" />
        </div>
        <div class="filter-group">
          <label>Show</label>
          <select v-model="filters.stockFilter" class="filter-select">
            <option value="all">All stock</option>
            <option value="low">Low stock only</option>
            <option value="out">Out of stock only</option>
          </select>
        </div>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="state-box"><div class="spinner"></div></div>

      <div v-else>
        <!-- Summary chips -->
        <div class="summary-chips">
          <div class="chip total">
            <span class="chip-label">Total Products</span>
            <span class="chip-val">{{ summaryStats.total }}</span>
          </div>
          <div class="chip ok">
            <span class="chip-label">In Stock</span>
            <span class="chip-val">{{ summaryStats.ok }}</span>
          </div>
          <div class="chip low">
            <span class="chip-label">Low Stock</span>
            <span class="chip-val">{{ summaryStats.low }}</span>
          </div>
          <div class="chip out">
            <span class="chip-label">Out of Stock</span>
            <span class="chip-val">{{ summaryStats.out }}</span>
          </div>
        </div>

        <!-- Stock table -->
        <div class="table-wrap" v-if="filteredRows.length > 0">
          <table class="stock-table">
            <thead>
              <tr>
                <th>Product</th>
                <th>Category</th>
                <!--
                  Company column — shows the supplier for grocery products.
                  For non-grocery products this cell shows '—'.
                -->
                <th>Company / Supplier</th>
                <th>Branch</th>
                <th>Unit</th>
                <th>Qty</th>
                <th>Min Stock</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="row in filteredRows"
                :key="row.id"
                :class="{
                  'row-low': row.status === 'low',
                  'row-out': row.status === 'out',
                }"
              >
                <td>
                  <div class="product-cell">
                    <span class="product-cell__icon">{{ getCategoryIcon(row.category) }}</span>
                    <div>
                      <p class="product-cell__name">{{ row.product_name }}</p>
                      <p class="product-cell__sku">{{ row.sku }}</p>
                    </div>
                  </div>
                </td>
                <td>
                  <span class="cat-badge">{{ row.category }}</span>
                </td>
                <td>
                  <span class="company-badge" v-if="row.company_name">{{ row.company_name }}</span>
                  <span class="muted" v-else>—</span>
                </td>
                <td>{{ row.branch_name }}</td>
                <td class="unit-cell">{{ row.unit }}</td>
                <td>
                  <span class="qty-val" :class="row.status">{{ row.quantity }}</span>
                </td>
                <td class="min-cell">{{ row.minimum_stock }}</td>
                <td>
                  <span class="status-badge" :class="row.status">
                    {{
                      row.status === 'ok'  ? '✅ OK' :
                      row.status === 'low' ? '⚠️ Low' : '🔴 Out'
                    }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="state-box" v-else>
          <div class="empty-icon">📦</div>
          <p>No stock records match your filters.</p>
        </div>
      </div>

    </div>
  </DashboardLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import DashboardLayout from '../../layouts/DashboardLayout.vue'
import { useAuthStore } from '../../stores/auth'
import api from '../../services/api'

const authStore = useAuthStore()

const branches      = ref<any[]>([])
const stockBalances = ref<any[]>([])
const loading       = ref(false)

const filters = ref({
  branch_id:   authStore.isManager ? '' : String(authStore.user?.branch_id ?? ''),
  category:    '',
  search:      '',
  stockFilter: 'all',
})

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

// ── Build flat rows from stock balances ───────────────────────────────────────
// Each row = one stock_balance record, enriched with product + company info.
// For grocery products the row includes company_name from product_company.
// For other products company_name is null.
const rows = computed(() =>
  stockBalances.value.map((sb: any) => {
    const stock = Number(sb.quantity)
    const min   = sb.product?.minimum_stock ?? 10
    let status: 'ok' | 'low' | 'out' = 'ok'
    if (stock <= 0)       status = 'out'
    else if (stock <= min) status = 'low'

    return {
      id:            sb.id,
      product_name:  sb.product?.name ?? '—',
      sku:           sb.product?.sku  ?? '—',
      category:      sb.product?.category ?? 'Uncategorised',
      unit:          sb.product?.unit ?? '—',
      minimum_stock: min,
      // company comes from product_company relation on stock_balance
      company_name:  sb.product_company?.name ?? null,
      branch_name:   sb.branch?.name ?? '—',
      quantity:      stock,
      status,
    }
  })
)

const filteredRows = computed(() => {
  let r = rows.value

  if (filters.value.branch_id)
    r = r.filter(row => {
      const sb = stockBalances.value.find(s => s.id === row.id)
      return String(sb?.branch_id) === String(filters.value.branch_id)
    })

  if (filters.value.category)
    r = r.filter(row => row.category === filters.value.category)

  if (filters.value.search)
    r = r.filter(row =>
      row.product_name.toLowerCase().includes(filters.value.search.toLowerCase()) ||
      (row.company_name ?? '').toLowerCase().includes(filters.value.search.toLowerCase())
    )

  if (filters.value.stockFilter === 'low')
    r = r.filter(row => row.status === 'low')
  else if (filters.value.stockFilter === 'out')
    r = r.filter(row => row.status === 'out')

  return r
})

const summaryStats = computed(() => ({
  total: filteredRows.value.length,
  ok:    filteredRows.value.filter(r => r.status === 'ok').length,
  low:   filteredRows.value.filter(r => r.status === 'low').length,
  out:   filteredRows.value.filter(r => r.status === 'out').length,
}))

// ── Load ──────────────────────────────────────────────────────────────────────
const loadStock = async () => {
  loading.value = true
  try {
    const bid = authStore.isManager ? filters.value.branch_id : authStore.user?.branch_id
    const params: Record<string, any> = {}
    if (bid) params.branch_id = bid

    const [b, sb] = await Promise.all([
      api.get('/branches'),
      // StockBalanceController should eager-load product, product_company, and branch
      api.get('/stock-balances', { params }),
    ])
    branches.value      = b.data
    stockBalances.value = sb.data
  } catch (e) { console.error(e) }
  finally { loading.value = false }
}

// ── CSV Export ────────────────────────────────────────────────────────────────
const exportCSV = () => {
  const header = ['Product', 'SKU', 'Category', 'Company', 'Branch', 'Unit', 'Qty', 'Min Stock', 'Status']
  const rowsData = filteredRows.value.map(r => [
    r.product_name, r.sku, r.category, r.company_name ?? '—',
    r.branch_name, r.unit, r.quantity, r.minimum_stock, r.status,
  ])
  const csv = [header, ...rowsData].map(r => r.join(',')).join('\n')
  const blob = new Blob([csv], { type: 'text/csv' })
  const url  = URL.createObjectURL(blob)
  const a    = document.createElement('a')
  a.href     = url
  a.download = `kefis-stock-${new Date().toISOString().split('T')[0]}.csv`
  a.click()
  URL.revokeObjectURL(url)
}

onMounted(() => loadStock())
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap');
* { box-sizing: border-box; }
.page { padding: 2rem; min-height: 100vh; background: #f7f8fa; font-family: 'Sora', sans-serif; }

/* Header */
.page-header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem; }
.page-title  { font-size: 1.7rem; font-weight: 700; color: #0f172a; letter-spacing: -0.03em; margin: 0 0 0.2rem; }
.page-sub    { font-size: 0.82rem; color: #94a3b8; margin: 0; }
.btn-export  { display: flex; align-items: center; gap: 0.5rem; background: #fff; color: #0f172a; border: 1.5px solid #e2e8f0; border-radius: 10px; padding: 0.55rem 1.1rem; font-size: 0.82rem; font-weight: 600; cursor: pointer; font-family: 'Sora', sans-serif; transition: border-color 0.15s, box-shadow 0.15s; }
.btn-export:hover { border-color: #0f172a; box-shadow: 0 1px 6px rgba(0,0,0,0.07); }

/* Filters */
.filter-bar { display: flex; flex-wrap: wrap; gap: 0.75rem; margin-bottom: 1.5rem; background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1rem 1.25rem; }
.filter-group { display: flex; flex-direction: column; gap: 0.3rem; }
.filter-group label { font-size: 0.68rem; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; }
.search-group { flex: 1; min-width: 180px; }
.filter-select,
.filter-input { border: 1.5px solid #e2e8f0; border-radius: 7px; padding: 0.35rem 0.65rem; font-size: 0.82rem; font-family: 'Sora', sans-serif; color: #0f172a; outline: none; transition: border-color 0.15s; background: #fff; }
.filter-select:focus,
.filter-input:focus { border-color: #0f172a; }

/* Summary chips */
.summary-chips { display: flex; gap: 0.75rem; margin-bottom: 1.25rem; flex-wrap: wrap; }
.chip { display: flex; flex-direction: column; gap: 0.15rem; background: #fff; border: 1.5px solid #e2e8f0; border-radius: 10px; padding: 0.65rem 1rem; min-width: 100px; }
.chip-label { font-size: 0.68rem; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; }
.chip-val   { font-size: 1.2rem; font-weight: 700; color: #0f172a; font-family: 'JetBrains Mono', monospace; }
.chip.ok  { border-color: #bbf7d0; }
.chip.low { border-color: #fde68a; }
.chip.out { border-color: #fecdd3; }

/* Table */
.table-wrap { background: #fff; border-radius: 14px; border: 1px solid #e2e8f0; overflow: hidden; overflow-x: auto; }
.stock-table { width: 100%; border-collapse: collapse; font-size: 0.82rem; min-width: 700px; }
.stock-table thead { background: #f8fafc; }
.stock-table th { padding: 0.8rem 1rem; text-align: left; font-weight: 600; color: #64748b; font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 1px solid #e2e8f0; white-space: nowrap; }
.stock-table td { padding: 0.8rem 1rem; color: #374151; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
.stock-table tr:last-child td { border-bottom: none; }
.stock-table tr:hover td { background: #f8fafc; }
.stock-table tr.row-low td { background: #fffdf0; }
.stock-table tr.row-out td { background: #fff8f8; }

/* Product cell */
.product-cell { display: flex; align-items: center; gap: 0.6rem; }
.product-cell__icon { font-size: 1.2rem; flex-shrink: 0; }
.product-cell__name { font-size: 0.82rem; font-weight: 600; color: #0f172a; margin: 0 0 0.1rem; white-space: nowrap; }
.product-cell__sku  { font-size: 0.65rem; color: #94a3b8; font-family: 'JetBrains Mono', monospace; margin: 0; }

/* Badges */
.cat-badge { font-size: 0.7rem; font-weight: 600; background: #f1f5f9; color: #64748b; padding: 0.2rem 0.5rem; border-radius: 6px; white-space: nowrap; }
.company-badge { font-size: 0.72rem; font-weight: 600; background: #eff6ff; color: #2563eb; padding: 0.2rem 0.6rem; border-radius: 6px; white-space: nowrap; }
.muted { color: #cbd5e1; font-size: 0.75rem; }
.unit-cell { font-family: 'JetBrains Mono', monospace; font-size: 0.75rem; color: #64748b; }
.min-cell  { font-family: 'JetBrains Mono', monospace; font-size: 0.75rem; color: #94a3b8; }

.qty-val { font-weight: 700; font-family: 'JetBrains Mono', monospace; font-size: 0.88rem; color: #0f172a; }
.qty-val.low { color: #d97706; }
.qty-val.out { color: #ef4444; }

.status-badge { font-size: 0.7rem; font-weight: 700; padding: 0.25rem 0.6rem; border-radius: 20px; white-space: nowrap; }
.status-badge.ok  { background: #f0fdf4; color: #059669; }
.status-badge.low { background: #fffbeb; color: #d97706; }
.status-badge.out { background: #fff1f2; color: #dc2626; }

/* State */
.state-box  { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 4rem 2rem; gap: 1rem; color: #94a3b8; }
.empty-icon { font-size: 2.5rem; }
.spinner    { width: 28px; height: 28px; border: 3px solid #e2e8f0; border-top-color: #0f172a; border-radius: 50%; animation: spin 0.7s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
</style>