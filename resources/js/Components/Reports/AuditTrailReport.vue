<template>
    <div class="audit-trail-report">
        <div class="report-header flex items-center justify-between mb-6">
            <div>
                <h2 class="text-2xl font-bold text-text-primary">Audit Trail Report</h2>
                <p class="text-text-secondary">Track all system activities and changes for compliance</p>
            </div>
            <div class="header-actions flex items-center space-x-3">
                <SecondaryButton @click="exportAuditTrail('csv')" :disabled="loading">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Export CSV
                </SecondaryButton>
                <PrimaryButton @click="generatePDFReport" :disabled="loading">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                    Generate PDF
                </PrimaryButton>
            </div>
        </div>

        <!-- Filters -->
        <div class="audit-filters bg-white rounded-xl shadow-md p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-text-secondary mb-2">Date Range</label>
                    <div class="space-y-2">
                        <input
                            v-model="filters.date_from"
                            type="date"
                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-primary focus:border-primary"
                            placeholder="From date"
                        />
                        <input
                            v-model="filters.date_to"
                            type="date"
                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-primary focus:border-primary"
                            placeholder="To date"
                        />
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-text-secondary mb-2">User</label>
                    <select 
                        v-model="filters.user_id"
                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-primary focus:border-primary"
                    >
                        <option value="">All Users</option>
                        <option 
                            v-for="user in users" 
                            :key="user.id" 
                            :value="user.id"
                        >
                            {{ user.name }} ({{ user.email }})
                        </option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-text-secondary mb-2">Action Type</label>
                    <select 
                        v-model="filters.event"
                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-primary focus:border-primary"
                    >
                        <option value="">All Actions</option>
                        <option value="created">Created</option>
                        <option value="updated">Updated</option>
                        <option value="deleted">Deleted</option>
                        <option value="restored">Restored</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-text-secondary mb-2">Model Type</label>
                    <select 
                        v-model="filters.auditable_type"
                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-primary focus:border-primary"
                    >
                        <option value="">All Models</option>
                        <option value="App\Models\BailMobilite">Bail Mobilités</option>
                        <option value="App\Models\Mission">Missions</option>
                        <option value="App\Models\Checklist">Checklists</option>
                        <option value="App\Models\User">Users</option>
                        <option value="App\Models\IncidentReport">Incidents</option>
                        <option value="App\Models\ContractTemplate">Contract Templates</option>
                    </select>
                </div>
            </div>
            
            <div class="flex items-center justify-between mt-4">
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-text-secondary">
                        {{ auditLogs.length }} records found
                    </span>
                    <div v-if="hasActiveFilters" class="flex items-center space-x-2">
                        <span class="text-xs text-text-secondary">Filters active:</span>
                        <SecondaryButton @click="resetFilters" size="sm">
                            Clear All
                        </SecondaryButton>
                    </div>
                </div>
                <PrimaryButton @click="loadAuditLogs" :disabled="loading">
                    <svg v-if="loading" class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <svg v-else class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Refresh
                </PrimaryButton>
            </div>
        </div>

        <!-- Summary Stats -->
        <div class="audit-summary grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <div class="stat-card bg-white rounded-xl shadow-md p-6 border-l-4 border-info-border">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-semibold text-text-secondary">Total Actions</h3>
                        <p class="text-3xl font-bold text-info-text mt-2">{{ summary.total }}</p>
                    </div>
                    <div class="p-3 rounded-full bg-info-bg">
                        <svg class="w-6 h-6 text-info-text" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="stat-card bg-white rounded-xl shadow-md p-6 border-l-4 border-success-border">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-semibold text-text-secondary">Created</h3>
                        <p class="text-3xl font-bold text-success-text mt-2">{{ summary.created }}</p>
                    </div>
                    <div class="p-3 rounded-full bg-success-bg">
                        <svg class="w-6 h-6 text-success-text" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="stat-card bg-white rounded-xl shadow-md p-6 border-l-4 border-warning-border">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-semibold text-text-secondary">Updated</h3>
                        <p class="text-3xl font-bold text-warning-text mt-2">{{ summary.updated }}</p>
                    </div>
                    <div class="p-3 rounded-full bg-warning-bg">
                        <svg class="w-6 h-6 text-warning-text" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="stat-card bg-white rounded-xl shadow-md p-6 border-l-4 border-error-border">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-semibold text-text-secondary">Deleted</h3>
                        <p class="text-3xl font-bold text-error-text mt-2">{{ summary.deleted }}</p>
                    </div>
                    <div class="p-3 rounded-full bg-error-bg">
                        <svg class="w-6 h-6 text-error-text" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Audit Log Table -->
        <div class="audit-table bg-white rounded-xl shadow-md overflow-hidden">
            <div class="table-header p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-text-primary">Audit Log Entries</h3>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                                Timestamp
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                                User
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                                Action
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                                Model
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                                Changes
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                                IP Address
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr 
                            v-for="log in paginatedLogs" 
                            :key="log.id"
                            class="hover:bg-gray-50 cursor-pointer"
                            @click="showLogDetails(log)"
                        >
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-text-primary">
                                {{ formatDateTime(log.created_at) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-8 w-8">
                                        <div class="h-8 w-8 rounded-full bg-primary flex items-center justify-center">
                                            <span class="text-xs font-medium text-white">
                                                {{ getUserInitials(log.user?.name || 'System') }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="ml-3">
                                        <div class="text-sm font-medium text-text-primary">
                                            {{ log.user?.name || 'System' }}
                                        </div>
                                        <div class="text-sm text-text-secondary">
                                            {{ log.user?.email || 'system@app.com' }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span :class="getActionBadgeClass(log.event)">
                                    {{ formatAction(log.event) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-text-secondary">
                                <div>
                                    <div class="font-medium">{{ formatModelType(log.auditable_type) }}</div>
                                    <div class="text-xs">ID: {{ log.auditable_id }}</div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-text-secondary">
                                <div class="max-w-xs truncate">
                                    {{ getChangeSummary(log) }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-text-secondary">
                                {{ log.ip_address || 'N/A' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="pagination-controls px-6 py-4 border-t border-gray-200 flex items-center justify-between">
                <div class="text-sm text-text-secondary">
                    Showing {{ ((currentPage - 1) * itemsPerPage) + 1 }} to {{ Math.min(currentPage * itemsPerPage, auditLogs.length) }} of {{ auditLogs.length }} entries
                </div>
                <div class="flex items-center space-x-2">
                    <SecondaryButton 
                        @click="currentPage--" 
                        :disabled="currentPage === 1"
                        size="sm"
                    >
                        Previous
                    </SecondaryButton>
                    <span class="text-sm text-text-secondary">
                        Page {{ currentPage }} of {{ totalPages }}
                    </span>
                    <SecondaryButton 
                        @click="currentPage++" 
                        :disabled="currentPage === totalPages"
                        size="sm"
                    >
                        Next
                    </SecondaryButton>
                </div>
            </div>
        </div>

        <!-- Log Details Modal -->
        <Modal v-if="selectedLog" @close="selectedLog = null">
            <div class="log-details">
                <div class="modal-header flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-text-primary">Audit Log Details</h3>
                    <button @click="selectedLog = null" class="text-text-secondary hover:text-text-primary">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="log-info space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-text-secondary">Timestamp</label>
                            <p class="text-sm text-text-primary">{{ formatDateTime(selectedLog.created_at) }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-text-secondary">User</label>
                            <p class="text-sm text-text-primary">{{ selectedLog.user?.name || 'System' }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-text-secondary">Action</label>
                            <p class="text-sm text-text-primary">{{ formatAction(selectedLog.event) }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-text-secondary">Model</label>
                            <p class="text-sm text-text-primary">{{ formatModelType(selectedLog.auditable_type) }} (ID: {{ selectedLog.auditable_id }})</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-text-secondary">IP Address</label>
                            <p class="text-sm text-text-primary">{{ selectedLog.ip_address || 'N/A' }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-text-secondary">User Agent</label>
                            <p class="text-sm text-text-primary truncate">{{ selectedLog.user_agent || 'N/A' }}</p>
                        </div>
                    </div>

                    <div v-if="selectedLog.old_values && Object.keys(selectedLog.old_values).length > 0">
                        <label class="block text-sm font-medium text-text-secondary mb-2">Old Values</label>
                        <pre class="bg-gray-100 p-3 rounded-md text-xs overflow-x-auto">{{ JSON.stringify(selectedLog.old_values, null, 2) }}</pre>
                    </div>

                    <div v-if="selectedLog.new_values && Object.keys(selectedLog.new_values).length > 0">
                        <label class="block text-sm font-medium text-text-secondary mb-2">New Values</label>
                        <pre class="bg-gray-100 p-3 rounded-md text-xs overflow-x-auto">{{ JSON.stringify(selectedLog.new_values, null, 2) }}</pre>
                    </div>
                </div>
            </div>
        </Modal>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import PrimaryButton from '../PrimaryButton.vue'
import SecondaryButton from '../SecondaryButton.vue'
import Modal from '../Modal.vue'

const props = defineProps({
    users: {
        type: Array,
        default: () => []
    }
})

const loading = ref(false)
const auditLogs = ref([])
const selectedLog = ref(null)
const currentPage = ref(1)
const itemsPerPage = ref(25)

const filters = ref({
    date_from: '',
    date_to: '',
    user_id: '',
    event: '',
    auditable_type: ''
})

const summary = computed(() => {
    return {
        total: auditLogs.value.length,
        created: auditLogs.value.filter(log => log.event === 'created').length,
        updated: auditLogs.value.filter(log => log.event === 'updated').length,
        deleted: auditLogs.value.filter(log => log.event === 'deleted').length
    }
})

const hasActiveFilters = computed(() => {
    return Object.values(filters.value).some(value => value !== '')
})

const totalPages = computed(() => {
    return Math.ceil(auditLogs.value.length / itemsPerPage.value)
})

const paginatedLogs = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage.value
    const end = start + itemsPerPage.value
    return auditLogs.value.slice(start, end)
})

const loadAuditLogs = async () => {
    loading.value = true
    try {
        const params = new URLSearchParams()
        Object.entries(filters.value).forEach(([key, value]) => {
            if (value) params.append(key, value)
        })

        const response = await fetch(`/api/audit-trail?${params}`)
        const data = await response.json()
        auditLogs.value = data.data || []
    } catch (error) {
        console.error('Error loading audit logs:', error)
    } finally {
        loading.value = false
    }
}

const exportAuditTrail = async (format) => {
    loading.value = true
    try {
        const params = new URLSearchParams({
            format,
            ...filters.value
        })

        const response = await fetch(`/api/export/audit-trail?${params}`)
        
        if (format === 'csv') {
            const blob = await response.blob()
            downloadFile(blob, `audit_trail_${new Date().toISOString().split('T')[0]}.csv`)
        }
    } catch (error) {
        console.error('Error exporting audit trail:', error)
    } finally {
        loading.value = false
    }
}

const generatePDFReport = async () => {
    loading.value = true
    try {
        const params = new URLSearchParams(filters.value)
        const response = await fetch(`/api/reports/audit-trail?${params}`)
        const blob = await response.blob()
        downloadFile(blob, `audit_trail_report_${new Date().toISOString().split('T')[0]}.pdf`)
    } catch (error) {
        console.error('Error generating PDF report:', error)
    } finally {
        loading.value = false
    }
}

const downloadFile = (blob, filename) => {
    const url = window.URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = filename
    document.body.appendChild(a)
    a.click()
    window.URL.revokeObjectURL(url)
    document.body.removeChild(a)
}

const resetFilters = () => {
    filters.value = {
        date_from: '',
        date_to: '',
        user_id: '',
        event: '',
        auditable_type: ''
    }
    currentPage.value = 1
    loadAuditLogs()
}

const showLogDetails = (log) => {
    selectedLog.value = log
}

const formatDateTime = (dateString) => {
    return new Date(dateString).toLocaleString('fr-FR', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    })
}

const formatAction = (action) => {
    const actions = {
        'created': 'Created',
        'updated': 'Updated',
        'deleted': 'Deleted',
        'restored': 'Restored'
    }
    return actions[action] || action
}

const formatModelType = (modelType) => {
    const types = {
        'App\\Models\\BailMobilite': 'Bail Mobilité',
        'App\\Models\\Mission': 'Mission',
        'App\\Models\\Checklist': 'Checklist',
        'App\\Models\\User': 'User',
        'App\\Models\\IncidentReport': 'Incident Report',
        'App\\Models\\ContractTemplate': 'Contract Template'
    }
    return types[modelType] || modelType.split('\\').pop()
}

const getActionBadgeClass = (action) => {
    const classes = {
        'created': 'px-2 py-1 text-xs font-medium rounded-full bg-success-bg text-success-text',
        'updated': 'px-2 py-1 text-xs font-medium rounded-full bg-warning-bg text-warning-text',
        'deleted': 'px-2 py-1 text-xs font-medium rounded-full bg-error-bg text-error-text',
        'restored': 'px-2 py-1 text-xs font-medium rounded-full bg-info-bg text-info-text'
    }
    return classes[action] || 'px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800'
}

const getUserInitials = (name) => {
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}

const getChangeSummary = (log) => {
    if (log.event === 'created') {
        return 'New record created'
    } else if (log.event === 'deleted') {
        return 'Record deleted'
    } else if (log.event === 'updated' && log.new_values) {
        const changedFields = Object.keys(log.new_values)
        if (changedFields.length === 1) {
            return `Changed: ${changedFields[0]}`
        } else if (changedFields.length > 1) {
            return `Changed: ${changedFields.slice(0, 2).join(', ')}${changedFields.length > 2 ? ` +${changedFields.length - 2} more` : ''}`
        }
    }
    return 'No changes recorded'
}

onMounted(() => {
    // Set default date range to last 30 days
    const today = new Date()
    const thirtyDaysAgo = new Date(today.getTime() - 30 * 24 * 60 * 60 * 1000)
    
    filters.value.date_from = thirtyDaysAgo.toISOString().split('T')[0]
    filters.value.date_to = today.toISOString().split('T')[0]
    
    loadAuditLogs()
})
</script>

<style scoped>
.audit-trail-report {
    max-width: 100%;
}

.log-details {
    max-width: 800px;
    max-height: 80vh;
    overflow-y: auto;
}

.table-header {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
}

.stat-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
}
</style>