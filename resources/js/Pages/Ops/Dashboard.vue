<template>
    <div>
        <Head title="Ops Dashboard" />

        <DashboardOps>
            <template #header>
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-3xl font-bold text-primary">
                            Welcome back, {{ $page.props.auth.user.name }}!
                        </h2>
                        <p class="text-text-secondary mt-1">
                            Here's what's happening with your properties today.
                        </p>
                    </div>
                    <div class="flex items-center space-x-3">
                        <!-- View Toggle -->
                        <div class="flex bg-gray-100 rounded-lg p-1 shadow-inner">
                            <button
                                @click="currentView = 'overview'"
                                :class="[
                                    'px-4 py-2 text-sm font-medium rounded-md transition-all duration-200 flex items-center gap-2',
                                    currentView === 'overview' 
                                        ? 'bg-white text-primary shadow-sm transform scale-105' 
                                        : 'text-text-secondary hover:text-primary hover:bg-white/50'
                                ]"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                                </svg>
                                Vue d'ensemble
                            </button>
                            <button
                                @click="currentView = 'kanban'"
                                :class="[
                                    'px-4 py-2 text-sm font-medium rounded-md transition-all duration-200 flex items-center gap-2',
                                    currentView === 'kanban' 
                                        ? 'bg-white text-primary shadow-sm transform scale-105' 
                                        : 'text-text-secondary hover:text-primary hover:bg-white/50'
                                ]"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 0a2 2 0 012 2v6a2 2 0 01-2 2m-6 0a2 2 0 002 2h2a2 2 0 002-2"/>
                                </svg>
                                Kanban
                            </button>
                            <button
                                @click="currentView = 'analytics'"
                                :class="[
                                    'px-4 py-2 text-sm font-medium rounded-md transition-all duration-200 flex items-center gap-2',
                                    currentView === 'analytics' 
                                        ? 'bg-white text-primary shadow-sm transform scale-105' 
                                        : 'text-text-secondary hover:text-primary hover:bg-white/50'
                                ]"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                </svg>
                                Analyses
                            </button>
                        </div>
                        
                        <!-- Export Button -->
                        <div class="relative">
                            <SecondaryButton @click="showExportMenu = !showExportMenu">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                Exporter
                            </SecondaryButton>
                            
                            <!-- Export Menu -->
                            <div v-if="showExportMenu" class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-10 border border-gray-200">
                                <div class="py-1">
                                    <button
                                        @click="exportData('csv')"
                                        class="block w-full text-left px-4 py-2 text-sm text-text-secondary hover:bg-gray-100"
                                    >
                                        Exporter en CSV
                                    </button>
                                    <button
                                        @click="exportData('json')"
                                        class="block w-full text-left px-4 py-2 text-sm text-text-secondary hover:bg-gray-100"
                                    >
                                        Exporter en JSON
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <PrimaryButton :href="route('ops.bail-mobilites.create')">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Nouveau Bail Mobilité
                        </PrimaryButton>
                    </div>
                </div>
            </template>

            <ErrorBoundary fallback-message="Failed to load ops dashboard">
            <div class="py-8">
                <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
                    <!-- Filters -->
                    <div v-if="currentView !== 'overview'" class="bg-white rounded-xl shadow-md p-4">
                        <div class="flex flex-wrap items-center gap-4">
                            <div class="flex-1 min-w-64">
                                <input
                                    v-model="filters.search"
                                    @input="debouncedSearch"
                                    type="text"
                                    placeholder="Rechercher par nom, adresse, email..."
                                    class="w-full bg-white border-gray-200 rounded-md shadow-sm p-md focus:ring-2 focus:ring-primary focus:border-transparent"
                                />
                            </div>
                            
                            <select
                                v-model="filters.status"
                                @change="applyFilters"
                                class="bg-white border-gray-200 rounded-md shadow-sm p-md focus:ring-2 focus:ring-primary focus:border-transparent"
                            >
                                <option value="">Tous les statuts</option>
                                <option value="assigned">Assigné</option>
                                <option value="in_progress">En Cours</option>
                                <option value="completed">Terminé</option>
                                <option value="incident">Incident</option>
                            </select>
                            
                            <select
                                v-model="filters.ops_user_id"
                                @change="applyFilters"
                                class="bg-white border-gray-200 rounded-md shadow-sm p-md focus:ring-2 focus:ring-primary focus:border-transparent"
                            >
                                <option value="">Tous les Ops</option>
                                <option v-for="user in opsUsers" :key="user.id" :value="user.id">
                                    {{ user.name }}
                                </option>
                            </select>
                            
                            <select
                                v-model="filters.checker_id"
                                @change="applyFilters"
                                class="bg-white border-gray-200 rounded-md shadow-sm p-md focus:ring-2 focus:ring-primary focus:border-transparent"
                            >
                                <option value="">Tous les checkers</option>
                                <option v-for="checker in checkers" :key="checker.id" :value="checker.id">
                                    {{ checker.name }}
                                </option>
                            </select>
                            
                            <input
                                v-model="filters.date_from"
                                @change="applyFilters"
                                type="date"
                                class="bg-white border-gray-200 rounded-md shadow-sm p-md focus:ring-2 focus:ring-primary focus:border-transparent"
                            />
                            
                            <input
                                v-model="filters.date_to"
                                @change="applyFilters"
                                type="date"
                                class="bg-white border-gray-200 rounded-md shadow-sm p-md focus:ring-2 focus:ring-primary focus:border-transparent"
                            />
                            
                            <button
                                @click="resetFilters"
                                class="px-3 py-2 text-sm text-text-secondary hover:text-primary"
                            >
                                Réinitialiser
                            </button>
                        </div>
                    </div>

                    <!-- Overview View -->
                    <div v-if="currentView === 'overview'">
                        <LazyDashboardComponents.OverviewStats
                            :metrics="metrics"
                            :recent-activities="recentActivities"
                            :today-missions="todayMissions"
                            :kanban-data="kanbanData"
                        />

                    </div>

                    <!-- Kanban View -->
                    <div v-if="currentView === 'kanban'" class="space-y-6">
                        <LazyDashboardComponents.KanbanBoard
                            :items="kanbanData"
                            :loading="loading"
                            @drop="handleKanbanDrop"
                            @item-click="viewBailMobilite"
                            @bulk-action="handleBulkAction"
                        />
                    </div>

                    <!-- Analytics View -->
                    <div v-if="currentView === 'analytics'" class="space-y-6">
                        <LazyDashboardComponents.AnalyticsView
                            :data="{
                                metrics,
                                trends: performanceTrends,
                                checker_performance: metrics.checker_performance || [],
                                incident_types: metrics.incident_types || {}
                            }"
                        />
                    </div>
                </div>
            </div>
            </ErrorBoundary>
        </DashboardOps>
    </div>
</template>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, reactive, onMounted, computed } from 'vue'
import ErrorBoundary from '@/Components/ErrorBoundary.vue'
import LoadingSpinner from '@/Components/LoadingSpinner.vue'
import DashboardOps from '@/Layouts/DashboardOps.vue'
import BailMobiliteCard from '@/Components/BailMobiliteCard.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import { LazyDashboardComponents } from '@/utils/lazyLoading'
// Simple debounce function
const debounce = (func, wait) => {
    let timeout
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout)
            func(...args)
        }
        clearTimeout(timeout)
        timeout = setTimeout(later, wait)
    }
}

const props = defineProps({
    metrics: {
        type: Object,
        default: () => ({}),
    },
    kanbanData: {
        type: Object,
        default: () => ({
            assigned: [],
            in_progress: [],
            completed: [],
            incident: [],
        }),
    },
    pendingNotifications: {
        type: Array,
        default: () => [],
    },
    missionsForValidation: {
        type: Array,
        default: () => [],
    },
    endingSoon: {
        type: Array,
        default: () => [],
    },
    notificationStats: {
        type: Object,
        default: () => ({}),
    },
    performanceTrends: {
        type: Object,
        default: () => ({}),
    },
    todayMissions: {
        type: Array,
        default: () => [],
    },
    recentActivities: {
        type: Array,
        default: () => [],
    },
})

// Reactive state
const currentView = ref('overview')
const showExportMenu = ref(false)
const loading = ref(false)
const kanbanData = ref(props.kanbanData || { assigned: [], in_progress: [], completed: [], incident: [] })
const opsUsers = ref([])
const checkers = ref([])

const filters = reactive({
    search: '',
    status: '',
    ops_user_id: '',
    checker_id: '',
    date_from: '',
    date_to: '',
    incident_only: false,
    ending_soon: false,
})

// Computed properties
const debouncedSearch = debounce(() => {
    applyFilters()
}, 300)

// Helper methods for attention alerts
const getOverdueMissions = () => {
    const now = new Date();
    return props.todayMissions.filter(mission => 
        new Date(mission.scheduled_date) < now && mission.status !== 'completed'
    ).length;
};

const getUnassignedMissions = () => {
    return Object.values(props.kanbanData).flat().filter(bm => 
        bm.status === 'assigned' && (!bm.entry_mission?.agent_id || !bm.exit_mission?.agent_id)
    ).length;
};

const getCriticalIncidents = () => {
    return props.kanbanData.incident?.length || 0;
};

// Methods
const formatDate = (date) => {
    return new Date(date).toLocaleDateString('fr-FR')
}

const getRemainingDays = (endDate) => {
    const today = new Date()
    const end = new Date(endDate)
    const diffTime = end - today
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
    return Math.max(0, diffDays)
}

const getStatusClass = (status) => {
    const classes = {
        assigned: 'bg-yellow-100 text-yellow-800',
        in_progress: 'bg-blue-100 text-blue-800',
        completed: 'bg-green-100 text-green-800',
        incident: 'bg-red-100 text-red-800'
    }
    return classes[status] || 'bg-gray-100 text-gray-800'
}

const getStatusLabel = (status) => {
    const labels = {
        assigned: 'Assigné',
        in_progress: 'En Cours',
        completed: 'Terminé',
        incident: 'Incident'
    }
    return labels[status] || status
}

const getChangeClass = (change) => {
    if (change > 0) return 'text-green-600'
    if (change < 0) return 'text-red-600'
    return 'text-gray-600'
}

const getChangeText = (change) => {
    if (change > 0) return `+${change}`
    return change.toString()
}

const assignExit = (bailMobilite) => {
    router.visit(route('ops.bail-mobilites.show', bailMobilite.id), {
        data: { action: 'assign_exit' }
    })
}

const viewBailMobilite = (bailMobilite) => {
    const id = typeof bailMobilite === 'object' ? bailMobilite.id : bailMobilite
    router.visit(route('ops.bail-mobilites.show', id))
}

const handleAssignEntry = (bailMobilite) => {
    router.visit(route('ops.bail-mobilites.show', bailMobilite.id), {
        data: { action: 'assign_entry' }
    })
}

const handleAssignExit = (bailMobilite) => {
    router.visit(route('ops.bail-mobilites.show', bailMobilite.id), {
        data: { action: 'assign_exit' }
    })
}

const handleIncident = (bailMobilite) => {
    router.visit(route('ops.bail-mobilites.show', bailMobilite.id), {
        data: { action: 'handle_incident' }
    })
}

const applyFilters = async () => {
    if (currentView.value === 'kanban') {
        await loadKanbanData()
    }
}

const resetFilters = () => {
    Object.keys(filters).forEach(key => {
        if (typeof filters[key] === 'boolean') {
            filters[key] = false
        } else {
            filters[key] = ''
        }
    })
    applyFilters()
}

const loadKanbanData = async () => {
    try {
        const response = await fetch(route('ops.api.kanban-data', filters))
        const data = await response.json()
        kanbanData.value = data.kanbanData
    } catch (error) {
        console.error('Error loading kanban data:', error)
    }
}

const loadFilterOptions = async () => {
    try {
        const [opsResponse, checkersResponse] = await Promise.all([
            fetch(route('ops.api.ops-users')),
            fetch(route('ops.api.checkers'))
        ])
        
        opsUsers.value = await opsResponse.json()
        checkers.value = await checkersResponse.json()
    } catch (error) {
        console.error('Error loading filter options:', error)
    }
}

const handleKanbanDrop = async (event) => {
    const { item, fromStatus, toStatus } = event
    loading.value = true
    
    try {
        await router.post(route('ops.bail-mobilites.update-status', item.id), {
            status: toStatus,
            from_status: fromStatus
        }, {
            preserveScroll: true,
            onSuccess: () => {
                // Update local kanban data
                const fromArray = kanbanData.value[fromStatus]
                const toArray = kanbanData.value[toStatus]
                
                if (fromArray && toArray) {
                    const itemIndex = fromArray.findIndex(i => i.id === item.id)
                    if (itemIndex > -1) {
                        const [movedItem] = fromArray.splice(itemIndex, 1)
                        movedItem.status = toStatus
                        toArray.push(movedItem)
                    }
                }
            },
            onError: (errors) => {
                console.error('Error updating status:', errors)
                alert('Erreur lors de la mise à jour du statut')
            }
        })
    } catch (error) {
        console.error('Error in kanban drop:', error)
        alert('Erreur lors de la mise à jour')
    } finally {
        loading.value = false
    }
}

const handleBulkAction = async (payload) => {
    loading.value = true
    
    try {
        await router.post(route('ops.bail-mobilites.bulk-action'), payload, {
            preserveScroll: true,
            onSuccess: () => {
                // Refresh kanban data
                loadKanbanData()
            },
            onError: (errors) => {
                console.error('Error in bulk action:', errors)
                alert('Erreur lors de l\'action en lot')
            }
        })
    } catch (error) {
        console.error('Error in bulk action:', error)
        alert('Erreur lors de l\'action en lot')
    } finally {
        loading.value = false
    }
}

const exportData = async (format) => {
    showExportMenu.value = false
    
    try {
        const params = new URLSearchParams({
            format,
            ...filters
        })
        
        const response = await fetch(route('ops.api.export') + '?' + params)
        
        if (format === 'csv') {
            const blob = await response.blob()
            const url = window.URL.createObjectURL(blob)
            const a = document.createElement('a')
            a.href = url
            a.download = `bail_mobilites_${new Date().toISOString().split('T')[0]}.csv`
            document.body.appendChild(a)
            a.click()
            window.URL.revokeObjectURL(url)
            document.body.removeChild(a)
        } else {
            const data = await response.json()
            const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' })
            const url = window.URL.createObjectURL(blob)
            const a = document.createElement('a')
            a.href = url
            a.download = `bail_mobilites_${new Date().toISOString().split('T')[0]}.json`
            document.body.appendChild(a)
            a.click()
            window.URL.revokeObjectURL(url)
            document.body.removeChild(a)
        }
    } catch (error) {
        console.error('Error exporting data:', error)
        alert('Erreur lors de l\'export des données')
    }
}

// Lifecycle
onMounted(() => {
    loadFilterOptions()
    
    // Close export menu when clicking outside
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.relative')) {
            showExportMenu.value = false
        }
    })
})
</script>