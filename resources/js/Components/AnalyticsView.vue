<template>
    <div class="analytics-view space-y-6">
        <!-- Export Controls -->
        <div class="bg-white rounded-xl shadow-md p-4">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-text-primary">Analyses et Rapports</h3>
                <div class="flex items-center space-x-3">
                    <!-- Date Range Selector -->
                    <div class="flex items-center space-x-2">
                        <input
                            v-model="dateRange.from"
                            type="date"
                            class="border border-gray-300 rounded-md px-3 py-2 text-sm"
                        />
                        <span class="text-text-secondary">à</span>
                        <input
                            v-model="dateRange.to"
                            type="date"
                            class="border border-gray-300 rounded-md px-3 py-2 text-sm"
                        />
                    </div>
                    
                    <!-- Export Buttons -->
                    <SecondaryButton @click="exportData('csv')" :disabled="loading">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Export CSV
                    </SecondaryButton>
                    
                    <SecondaryButton @click="exportData('pdf')" :disabled="loading">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                        Export PDF
                    </SecondaryButton>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Monthly Trends Chart -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <h3 class="text-lg font-semibold text-text-primary mb-4">Tendances Mensuelles</h3>
                <div class="h-64">
                    <canvas ref="monthlyChart"></canvas>
                </div>
                <div class="flex justify-center space-x-4 mt-4">
                    <div class="flex items-center">
                        <div class="w-3 h-3 bg-success-border rounded mr-2"></div>
                        <span class="text-xs text-text-secondary">Terminés</span>
                    </div>
                    <div class="flex items-center">
                        <div class="w-3 h-3 bg-info-border rounded mr-2"></div>
                        <span class="text-xs text-text-secondary">Créés</span>
                    </div>
                    <div class="flex items-center">
                        <div class="w-3 h-3 bg-error-border rounded mr-2"></div>
                        <span class="text-xs text-text-secondary">Incidents</span>
                    </div>
                </div>
            </div>

            <!-- Status Distribution Pie Chart -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <h3 class="text-lg font-semibold text-text-primary mb-4">Répartition des Statuts</h3>
                <div class="h-64">
                    <canvas ref="statusChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Performance Metrics -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Key Metrics -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <h3 class="text-lg font-semibold text-text-primary mb-4">Métriques Clés</h3>
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-text-secondary">Durée moyenne</span>
                        <span class="font-semibold">{{ data.metrics?.average_duration || 0 }} jours</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-text-secondary">Taux d'incidents</span>
                        <span class="font-semibold text-error-text">{{ data.metrics?.incident_rate || 0 }}%</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-text-secondary">Taux de completion</span>
                        <span class="font-semibold text-success-text">{{ completionRate }}%</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-text-secondary">Temps moyen de résolution</span>
                        <span class="font-semibold">{{ data.metrics?.avg_resolution_time || 0 }}h</span>
                    </div>
                </div>
            </div>

            <!-- Checker Performance -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <h3 class="text-lg font-semibold text-text-primary mb-4">Top Checkers</h3>
                <div class="space-y-3">
                    <div
                        v-for="(checker, index) in topCheckers"
                        :key="checker.name"
                        class="flex items-center justify-between"
                    >
                        <div class="flex items-center">
                            <span class="w-6 h-6 bg-secondary text-primary text-xs font-medium rounded-full flex items-center justify-center mr-3">
                                {{ index + 1 }}
                            </span>
                            <span class="text-sm font-medium text-text-primary">{{ checker.name }}</span>
                        </div>
                        <div class="text-right">
                            <div class="text-sm text-text-secondary">{{ checker.missions_completed }}</div>
                            <div class="text-xs text-success-text">{{ checker.success_rate }}%</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Incident Analysis -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <h3 class="text-lg font-semibold text-text-primary mb-4">Analyse des Incidents</h3>
                <div class="space-y-3">
                    <div
                        v-for="(count, type) in incidentTypes"
                        :key="type"
                        class="flex items-center justify-between"
                    >
                        <span class="text-sm text-text-secondary">{{ getIncidentTypeLabel(type) }}</span>
                        <span class="font-semibold">{{ count }}</span>
                    </div>
                </div>
                <div class="mt-4 pt-4 border-t border-gray-200">
                    <div class="text-sm text-text-secondary">
                        Temps moyen de résolution: <span class="font-semibold">{{ data.metrics?.avg_incident_resolution || 0 }}h</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detailed Tables -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-text-primary">Données Détaillées</h3>
                <div class="flex space-x-2">
                    <button
                        v-for="tab in tabs"
                        :key="tab.key"
                        @click="activeTab = tab.key"
                        :class="[
                            'px-3 py-2 text-sm font-medium rounded-md transition-colors',
                            activeTab === tab.key
                                ? 'bg-primary text-white'
                                : 'text-text-secondary hover:text-primary hover:bg-secondary'
                        ]"
                    >
                        {{ tab.label }}
                    </button>
                </div>
            </div>

            <!-- Performance Table -->
            <div v-if="activeTab === 'performance'" class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                                Checker
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                                Missions
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                                Taux de Succès
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                                Temps Moyen
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="checker in data.checker_performance" :key="checker.name">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-text-primary">
                                {{ checker.name }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-text-secondary">
                                {{ checker.missions_completed }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-text-secondary">
                                {{ checker.success_rate }}%
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-text-secondary">
                                {{ checker.avg_time }}h
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Trends Table -->
            <div v-if="activeTab === 'trends'" class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                                Période
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                                Créés
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                                Terminés
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                                Incidents
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="trend in data.trends?.monthly" :key="trend.month">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-text-primary">
                                {{ trend.month }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-text-secondary">
                                {{ trend.created }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-text-secondary">
                                {{ trend.completed }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-text-secondary">
                                {{ trend.incidents }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import SecondaryButton from './SecondaryButton.vue'

const props = defineProps({
    data: {
        type: Object,
        default: () => ({})
    }
})

const loading = ref(false)
const activeTab = ref('performance')
const monthlyChart = ref(null)
const statusChart = ref(null)

const dateRange = ref({
    from: new Date(new Date().getFullYear(), new Date().getMonth() - 6, 1).toISOString().split('T')[0],
    to: new Date().toISOString().split('T')[0]
})

const tabs = [
    { key: 'performance', label: 'Performance' },
    { key: 'trends', label: 'Tendances' }
]

const topCheckers = computed(() => {
    return (props.data.checker_performance || []).slice(0, 5)
})

const completionRate = computed(() => {
    const total = props.data.metrics?.total || 1
    const completed = props.data.metrics?.completed || 0
    return Math.round((completed / total) * 100)
})

const incidentTypes = computed(() => {
    return props.data.incident_types || {}
})

const getIncidentTypeLabel = (type) => {
    const labels = {
        'property_damage': 'Dégâts matériels',
        'missing_items': 'Objets manquants',
        'access_issues': 'Problèmes d\'accès',
        'tenant_issues': 'Problèmes locataire',
        'other': 'Autres'
    }
    return labels[type] || type
}

const exportData = async (format) => {
    loading.value = true
    try {
        const params = new URLSearchParams({
            format,
            from: dateRange.value.from,
            to: dateRange.value.to
        })
        
        const response = await fetch(route('ops.api.analytics-export') + '?' + params)
        
        if (format === 'csv') {
            const blob = await response.blob()
            downloadFile(blob, `analytics_${new Date().toISOString().split('T')[0]}.csv`)
        } else if (format === 'pdf') {
            const blob = await response.blob()
            downloadFile(blob, `analytics_${new Date().toISOString().split('T')[0]}.pdf`)
        }
    } catch (error) {
        console.error('Error exporting data:', error)
        alert('Erreur lors de l\'export des données')
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

const initializeCharts = () => {
    // Initialize Chart.js charts
    if (monthlyChart.value && props.data.trends?.monthly) {
        const ctx = monthlyChart.value.getContext('2d')
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: props.data.trends.monthly.map(m => m.month),
                datasets: [
                    {
                        label: 'Terminés',
                        data: props.data.trends.monthly.map(m => m.completed),
                        borderColor: 'rgb(34, 197, 94)',
                        backgroundColor: 'rgba(34, 197, 94, 0.1)',
                        tension: 0.4
                    },
                    {
                        label: 'Créés',
                        data: props.data.trends.monthly.map(m => m.created),
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.4
                    },
                    {
                        label: 'Incidents',
                        data: props.data.trends.monthly.map(m => m.incidents),
                        borderColor: 'rgb(239, 68, 68)',
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        })
    }

    if (statusChart.value && props.data.metrics?.basic) {
        const ctx = statusChart.value.getContext('2d')
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Assigné', 'En Cours', 'Terminé', 'Incident'],
                datasets: [{
                    data: [
                        props.data.metrics.basic.assigned,
                        props.data.metrics.basic.in_progress,
                        props.data.metrics.basic.completed,
                        props.data.metrics.basic.incident
                    ],
                    backgroundColor: [
                        'rgb(245, 158, 11)',
                        'rgb(59, 130, 246)',
                        'rgb(34, 197, 94)',
                        'rgb(239, 68, 68)'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        })
    }
}

onMounted(() => {
    // Load Chart.js if not already loaded
    if (typeof Chart === 'undefined') {
        const script = document.createElement('script')
        script.src = 'https://cdn.jsdelivr.net/npm/chart.js'
        script.onload = initializeCharts
        document.head.appendChild(script)
    } else {
        initializeCharts()
    }
})

watch(() => props.data, () => {
    initializeCharts()
}, { deep: true })
</script>