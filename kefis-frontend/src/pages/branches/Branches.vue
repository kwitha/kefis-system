<template>
    <DashboardLayout>
        <div class="row g-4">

            <!-- Add Branch Form -->
            <div class="col-md-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white fw-bold border-bottom">
                        🏢 {{ editMode ? 'Edit Branch' : 'Add Branch' }}
                    </div>
                    <div class="card-body">

                        <div v-if="formError" class="alert alert-danger py-2 small">{{ formError }}</div>
                        <div v-if="formSuccess" class="alert alert-success py-2 small">{{ formSuccess }}</div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Branch Name</label>
                            <input
                                v-model="form.name"
                                type="text"
                                class="form-control"
                                placeholder="Enter branch name"
                            />
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Location</label>
                            <input
                                v-model="form.location"
                                type="text"
                                class="form-control"
                                placeholder="Enter location"
                            />
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Phone</label>
                            <input
                                v-model="form.phone"
                                type="text"
                                class="form-control"
                                placeholder="Enter phone number"
                            />
                        </div>

                        <div class="d-flex gap-2">
                            <button
                                class="btn btn-primary flex-grow-1"
                                :disabled="submitting"
                                @click="handleSubmit"
                            >
                                <span v-if="submitting" class="spinner-border spinner-border-sm me-2"></span>
                                {{ submitting ? 'Saving...' : editMode ? 'Update Branch' : 'Add Branch' }}
                            </button>
                            <button
                                v-if="editMode"
                                class="btn btn-secondary"
                                @click="cancelEdit"
                            >
                                Cancel
                            </button>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Branches List -->
            <div class="col-md-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white fw-bold border-bottom d-flex justify-content-between">
                        <span>📋 All Branches</span>
                        <span class="badge bg-primary">{{ branches.length }} branches</span>
                    </div>
                    <div class="card-body p-0">
                        <div v-if="loading" class="text-center p-4">
                            <div class="spinner-border text-primary"></div>
                        </div>
                        <div v-else-if="branches.length === 0" class="text-center text-muted p-4">
                            No branches found.
                        </div>
                        <div v-else class="table-responsive">
                            <table class="table table-hover mb-0 small">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Name</th>
                                        <th>Location</th>
                                        <th>Phone</th>
                                        <th>Users</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="b in branches" :key="b.id">
                                        <td>{{ b.id }}</td>
                                        <td class="fw-semibold">{{ b.name }}</td>
                                        <td>{{ b.location ?? '-' }}</td>
                                        <td>{{ b.phone ?? '-' }}</td>
                                        <td>{{ b.users_count }}</td>
                                        <td>
                                            <span
                                                class="badge"
                                                :class="b.is_active ? 'bg-success' : 'bg-danger'"
                                            >
                                                {{ b.is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td>
                                            <button
                                                class="btn btn-sm btn-outline-primary me-1"
                                                @click="editBranch(b)"
                                            >
                                                ✏️
                                            </button>
                                            <button
                                                class="btn btn-sm btn-outline-danger"
                                                @click="deleteBranch(b.id)"
                                            >
                                                🗑️
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </DashboardLayout>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import DashboardLayout from '../../layouts/DashboardLayout.vue'
import api from '../../services/api'

const branches    = ref<any[]>([])
const loading     = ref(false)
const submitting  = ref(false)
const formError   = ref('')
const formSuccess = ref('')
const editMode    = ref(false)
const editId      = ref<number | null>(null)

const form = ref({
    name:     '',
    location: '',
    phone:    '',
})

const resetForm = () => {
    form.value  = { name: '', location: '', phone: '' }
    editMode.value = false
    editId.value   = null
}

const cancelEdit = () => {
    resetForm()
    formError.value   = ''
    formSuccess.value = ''
}

const loadBranches = async () => {
    loading.value = true
    try {
        const res      = await api.get('/branches')
        branches.value = res.data
    } catch (e) {
        console.error(e)
    } finally {
        loading.value = false
    }
}

const handleSubmit = async () => {
    formError.value   = ''
    formSuccess.value = ''

    if (!form.value.name) {
        formError.value = 'Branch name is required.'
        return
    }

    submitting.value = true

    try {
        if (editMode.value && editId.value) {
            await api.put(`/branches/${editId.value}`, form.value)
            formSuccess.value = '✅ Branch updated successfully!'
        } else {
            await api.post('/branches', form.value)
            formSuccess.value = '✅ Branch added successfully!'
        }

        resetForm()
        await loadBranches()

    } catch (e: any) {
        formError.value = e.response?.data?.message ?? 'Failed to save branch.'
    } finally {
        submitting.value = false
    }
}

const editBranch = (branch: any) => {
    editMode.value     = true
    editId.value       = branch.id
    form.value.name     = branch.name
    form.value.location = branch.location ?? ''
    form.value.phone    = branch.phone ?? ''
    formError.value     = ''
    formSuccess.value   = ''
}

const deleteBranch = async (id: number) => {
    if (!confirm('Are you sure you want to delete this branch?')) return

    try {
        await api.delete(`/branches/${id}`)
        await loadBranches()
    } catch (e: any) {
        alert(e.response?.data?.message ?? 'Failed to delete branch.')
    }
}

onMounted(() => loadBranches())
</script>