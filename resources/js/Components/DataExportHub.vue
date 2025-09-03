<template>
    <div class="data-export-hub">
        <div class="hub-header flex items-center justify-between mb-6">
            <div>
                <h2 class="text-2xl font-bold text-text-primary">Data Export & Reporting Hub</h2>
                <p class="text-text-secondary">Export data and generate comprehensive reports for analysis and compliance</p>
            </div>
            <div class="header-actions">
                <SecondaryButton @click="showScheduledExports = !showScheduledExports">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Scheduled Exports
                </SecondaryButton>
            </div>
        </div>

        <!-- Quick Export Cards -->
        <div class="export-cards grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <!-- Bail Mobilités Export -->
            <div class="export-card bg-white rounded-xl shadow-md p-6 border-l-4 border-primary">
                <div class="card-header flex items-center justify-between mb-4">
                    <div class="flex items-center">
                        <div class="p-2 bg-primary bg-opacity-10 rounded-lg mr-3">
                            <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-text-primary">Bail Mobilités</h3>
                            <p class="text-sm text-text-secondary">Export all bail mobilité records</p>
                        </div>
                    </div>
                </div>
                <div class="export-actions space-y-2">
                    <div class="flex space-x-2">
                        <SecondaryButton @click="exportData('bail-mobilites', 'csv')" :disabled="loading" size="sm" class="flex-1">
                            CSV
                        </SecondaryButton>
                        <SecondaryButton @click="exportData('bail-mobilites', 'json')" :disabled="loading" size="sm" class="flex-1">
                            JSON
                        </SecondaryButton>
                    </div>
                    <PrimaryButton @click="showExportModal('bail-mobilites')" class="w-full" size="sm">
                        Advanced Export
                    </PrimaryButton>
                </div>
            </div>

            <!-- Missions Export -->
            <div class="export-card bg-white rounded-xl shadow-md p-6 border-l-4 border-info-border">
                <div class="card-header flex items-center justify-between mb-4">
                    <div class="flex items-center">
                        <div class="p-2 bg-info-bg rounded-lg mr-3">
                            <svg class="w-6 h-6 text-info-text" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-text-primary">Missions</h3>
                            <p class="text-sm text-text-secondary">Export mission data and assignments</p>
                        </div>
                    </div>
                </div>
                <div class="export-actions space-y-2">
                    <div class="flex space-x-2">
                        <SecondaryButton @click="exportData('missions', 'csv')" :disabled="loading" size="sm" class="flex-1">
                            CSV
                        </SecondaryButton>
                        <SecondaryButton @click="exportData('missions', 'json')" :disabled="loading" size="sm" class="flex-1">
                            JSON
                        </SecondaryButton>
                    </div>
                    <PrimaryButton @click="showExportModal('missions')" class="w-full" size="sm">
                        Advanced Export
                    </PrimaryButton>
                </div>
            </div>

            <!-- Checklists Export -->
            <div class="export-card bg-white rounded-xl shadow-md p-6 border-l-4 border-success-border">
                <div class="card-header flex items-center justify-between mb-4">
                    <div class="flex items-center">
                        <div class="p-2 bg-success-bg rounded-lg mr-3">
                            <svg class="w-6 h-6 text-success-text" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-text-primary">Checklists</h3>
                            <p class="text-sm text-text-secondary">Export checklist data and validations</p>
                        </div>
                    </div>
                </div>
                <div class="export-actions space-y-2">
                    <div class="flex space-x-2">
                        <SecondaryButton @click="exportData('checklists', 'csv')" :disabled="loading" size="sm" class="flex-1">
                            CSV
                        </SecondaryButton>
                        <SecondaryButton @click="exportData('checklists', 'json')" :disabled="loading" size="sm" class="flex-1">
                            JSON
                        </SecondaryButton>
                    </div>
                    <PrimaryButton @click="showExportModal('checklists')" class="w-full" size="sm">
                        Advanced Export
                    </PrimaryButton>
                </div>
            </div>

            <!-- Analytics Export -->
            <div class="export-card bg-white rounded-xl shadow-md p-6 border-l-4 border-warning-border">
                <div class="card-header flex items-center justify-between mb-4">
                    <div class="flex items-center">
                        <div class="p-2 bg-warning-bg rounded-lg mr-3">
                            <svg class="w-6 h-6 text-warning-text" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-text-primary">Analytics</h3>
                            <p class="text-sm text-text-secondary">Export performance analytics</p>
                        </div>
                    </div>
                </div>
                <div class="export-actions space-y-2">
                    <div class="flex space-x-2">
                        <SecondaryButton @click="exportData('analytics', 'csv')" :disabled="loading" size="sm" class="flex-1">
                            CSV
                        </SecondaryButton>
                        <SecondaryButton @click="exportData('analytics', 'json')" :disabled="loading" size="sm" class="flex-1">
                            JSON
                        </SecondaryButton>
                    </div>
                    <PrimaryButton @click="generateReport('analytics')" class="w-full" size="sm">
                        Generate Report
                    </PrimaryButton>
                </div>
            </div>

            <!-- Incidents Export -->
            <div class="export-card bg-white rounded-xl shadow-md p-6 border-l-4 border-error-border">
                <div class="card-header flex items-center justify-between mb-4">
                    <div class="flex items-center">
                        <div class="p-2 bg-error-bg rounded-lg mr-3">
                            <svg class="w-6 h-6 text-error-text" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-text-primary">Incidents</h3>
                            <p class="text-sm text-text-secondary">Export incident reports and resolutions</p>
                        </div>
                    </div>
                </div>
                <div class="export-actions space-y-2">
                    <div class="flex space-x-2">
                        <SecondaryButton @click="exportData('incidents', 'csv')" :disabled="loading" size="sm" class="flex-1">
                            CSV
                        </SecondaryButton>
                        <SecondaryButton @click="exportData('incidents', 'json')" :disabled="loading" size="sm" class="flex-1">
                            JSON
                        </SecondaryButton>
                    </div>
                    <PrimaryButton @click="generateReport('incidents')" class="w-full" size="sm">
                        Generate Report
                    </PrimaryButton>
                </div>
            </div>

            <!-- Audit Trail Export -->
            <div class="export-card bg-white rounded-xl shadow-md p-6 border-l-4 border-purple-500">
                <div class="card-header flex items-center justify-between mb-4">
                    <div class="flex items-center">
                        <div class="p-2 bg-purple-100 rounded-lg mr-3">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-text-primary">Audit Trail</h3>
                            <p class="text-sm text-text-secondary">Export system audit logs</p>
                        </div>
                    </div>
                </div>
                <div class="export-actions space-y-2">
                    <div class="flex space-x-2">
                        <SecondaryButton @click="exportData('audit-trail', 'csv')" :disabled="loading" size="sm" class="flex-1">
                            CSV
                        </SecondaryButton>
                        <SecondaryButton @click="exportData('audit-trail', 'json')" :disabled="loading" size="sm" class="flex-1">
                            JSON
                        </SecondaryButton>
                    </div>
                    <PrimaryButton @click="showAuditTrailReport" class="w-full" size="sm">
                        View Audit Trail
                    </PrimaryButton>
                </div>
            </div>
        </div>

        <!-- Recent Exports -->
        <div class="recent-exports bg-white rounded-xl shadow-md p-6 mb-6">
            <h3 class="text-lg font-semibold text-text-primary mb-4">Recent Exports</h3>
            <div class="exports-list space-y-3">
                <div 
                    v-for="export in recentExports" 
                    :key="export.id"
                    class="export-item flex items-center justify-between p-3 border border-gray-200 rounded-lg hover:bg-gray-50"
                >
                    <div class="flex items-center">
                        <div class="export-icon p-2 bg-gray-100 rounded-lg mr-3">
                            <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <div>
                            <div class="font-medium text-text-primary">{{ export.name }}</div>
                            <div class="text-sm text-text-secondary">{{ export.type }} • {{ formatDate(export.created_at) }}</div>
                        </div>
                    </div>
                    <div class="export-actions flex items-center space-x-2">
                        <span :class="getStatusBadgeClass(export.status)">
                            {{ export.status }}
                        </span>
                        <SecondaryButton v-if="export.status === 'completed'" @click="downloadExport(export)" size="sm">
                            Download
                        </SecondaryButton>
                    </div>
                </div>
                <div v-if="recentExports.length === 0" class="text-center py-8 text-text-secondary">
                    No recent exports found
                </div>
            </div>
        </div>

        <!-- Export Modal -->
        <Modal v-if="showModal" @close="showModal = false">
            <div class="export-modal">
                <div class="modal-header flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-text-primary">
                        Export {{ currentExportType }}
                    </h3>
                    <button @click="showModal = false" class="text-text-secondary hover:text-text-primary">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <AdvancedFilters
                    :available-statuses="getAvailableStatuses(currentExportType)"
                    :ops-users="opsUsers"
                    :checkers="checkers"
                    @filters-changed="updateExportFilters"
                />

                <div class="modal-actions flex items-center justify-end space-x-3 mt-6 pt-6 border-t border-gray-200">
                    <SecondaryButton @click="showModal = false">
                        Cancel
                    </SecondaryButton>
                    <div class="flex space-x-2">
                        <PrimaryButton @click="exportWithFilters('csv')" :disabled="loading">
                            Export CSV
                        </PrimaryButton>
                        <PrimaryButton @click="exportWithFilters('json')" :disabled="loading">
                            Export JSON
                        </PrimaryButton>
                    </div>
                </div>
            </div>
        </Modal>

        <!-- Audit Trail Modal -->
        <Modal v-if="showAuditModal" @close="showAuditModal = false" size="full">
            <AuditTrailReport :users="allUsers" />
        </Modal>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import PrimaryButton from './PrimaryButton.vue'
import SecondaryButton from './SecondaryButton.vue'
import Modal from './Modal.vue'
import AdvancedFilters from './Filters/AdvancedFilters.vue'
import AuditTrailReport from './Reports/AuditTrailReport.vue'

const props = defineProps({
    opsUsers: {
        type: Array,
        default: () => []
    },
    checkers: {
        type: Array,
        default: () => []
    },
    allUsers: {
        type: Array,
        default: () => []
    }
})

const loading = ref(false)
const showModal = ref(false)
const showAuditModal = ref(false)
const showScheduledExports = ref(false)
const currentExportType = ref('')
const exportFilters = ref({})

const recentExports = ref([
    {
        id: 1,
        name: 'Bail Mobilités Export',
        type: 'CSV',
        status: 'completed',
        created_at: new Date().toISOString()
    },
    {
        id: 2,
        name: 'Analytics Report',
        type: 'PDF',
        status: 'processing',
        created_at: new Date(Date.now() - 3600000).toISOString()
    }
])

const exportData = async (type, format) => {
    loading.value = true
    try {
        const response = await fetch(`/api/export/${type}?format=${format}`)
        
        if (format === 'json') {
            const data = await response.json()
            downloadJSON(data, `${type}_export_${new Date().toISOString().split('T')[0]}.json`)
        } else {
            const blob = await response.blob()
            downloadFile(blob, `${type}_export_${new Date().toISOString().split('T')[0]}.${format}`)
        }
        
        // Add to recent exports
        recentExports.value.unshift({
            id: Date.now(),
            name: `${type} Export`,
            type: format.toUpperCase(),
            status: 'completed',
            created_at: new Date().toISOString()
        })
    } catch (error) {
        console.error('Export error:', error)
        alert('Export failed. Please try again.')
    } finally {
        loading.value = false
    }
}

const showExportModal = (type) => {
    currentExportType.value = type
    showModal.value = true
}

const showAuditTrailReport = () => {
    showAuditModal.value = true
}

const updateExportFilters = (filters) => {
    exportFilters.value = filters
}

const exportWithFilters = async (format) => {
    loading.value = true
    try {
        const params = new URLSearchParams({
            format,
            ...exportFilters.value
        })
        
        const response = await fetch(`/api/export/${currentExportType.value}?${params}`)
        
        if (format === 'json') {
            const data = await response.json()
            downloadJSON(data, `${currentExportType.value}_filtered_${new Date().toISOString().split('T')[0]}.json`)
        } else {
            const blob = await response.blob()
            downloadFile(blob, `${currentExportType.value}_filtered_${new Date().toISOString().split('T')[0]}.${format}`)
        }
        
        showModal.value = false
    } catch (error) {
        console.error('Export error:', error)
        alert('Export failed. Please try again.')
    } finally {
        loading.value = false
    }
}

const generateReport = async (type) => {
    loading.value = true
    try {
        const response = await fetch(`/api/reports/${type}`)
        const blob = await response.blob()
        downloadFile(blob, `${type}_report_${new Date().toISOString().split('T')[0]}.pdf`)
    } catch (error) {
        console.error('Report generation error:', error)
        alert('Report generation failed. Please try again.')
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

const downloadJSON = (data, filename) => {
    const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' })
    downloadFile(blob, filename)
}

const downloadExport = (exportItem) => {
    // In a real implementation, this would download the actual file
    console.log('Downloading export:', exportItem)
}

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

const getStatusBadgeClass = (status) => {
    const classes = {
        'completed': 'px-2 py-1 text-xs font-medium rounded-full bg-success-bg text-success-text',
        'processing': 'px-2 py-1 text-xs font-medium rounded-full bg-warning-bg text-warning-text',
        'failed': 'px-2 py-1 text-xs font-medium rounded-full bg-error-bg text-error-text'
    }
    return classes[status] || 'px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800'
}

const getAvailableStatuses = (type) => {
    const statusMap = {
        'bail-mobilites': [
            { value: 'assigned', label: 'Assigned' },
            { value: 'in_progress', label: 'In Progress' },
            { value: 'completed', label: 'Completed' },
            { value: 'incident', label: 'Incident' }
        ],
        'missions': [
            { value: 'pending', label: 'Pending' },
            { value: 'assigned', label: 'Assigned' },
            { value: 'in_progress', label: 'In Progress' },
            { value: 'completed', label: 'Completed' },
            { value: 'cancelled', label: 'Cancelled' }
        ],
        'checklists': [
            { value: 'pending', label: 'Pending' },
            { value: 'submitted', label: 'Submitted' },
            { value: 'validated', label: 'Validated' },
            { value: 'rejected', label: 'Rejected' }
        ]
    }
    return statusMap[type] || []
}

onMounted(() => {
    // Load recent exports or other initialization
})
</script>

<style scoped>
.export-card {
    transition: all 0.2s ease;
}

.export-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
}

.export-modal {
    max-width: 800px;
    max-height: 90vh;
    overflow-y: auto;
}
</style>