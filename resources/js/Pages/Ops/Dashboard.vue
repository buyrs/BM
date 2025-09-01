<template>
    <div>
        <Head title="Ops Dashboard" />

        <DashboardOps>
            <template #header>
                <div class="flex justify-between items-center">
                    <h2 class="font-semibold text-xl text-gray-900 leading-tight">
                        Tableau de Bord Ops
                    </h2>
                    <Link
                        :href="route('ops.bail-mobilites.create')"
                        class="inline-flex items-center px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-lg hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Nouveau Bail Mobilité
                    </Link>
                </div>
            </template>

            <div class="py-12">
                <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    <!-- Statistics Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div class="bg-white rounded-xl shadow-sm p-6">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-yellow-100">
                                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-600">Assignés</p>
                                    <p class="text-2xl font-semibold text-gray-900">{{ stats.assigned }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-xl shadow-sm p-6">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-blue-100">
                                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-600">En Cours</p>
                                    <p class="text-2xl font-semibold text-gray-900">{{ stats.in_progress }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-xl shadow-sm p-6">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-green-100">
                                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-600">Terminés</p>
                                    <p class="text-2xl font-semibold text-gray-900">{{ stats.completed }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-xl shadow-sm p-6">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-red-100">
                                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-600">Incidents</p>
                                    <p class="text-2xl font-semibold text-gray-900">{{ stats.incident }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Notifications and Actions -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Pending Notifications -->
                        <div class="bg-white rounded-xl shadow-sm p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Notifications en Attente</h3>
                            <div class="space-y-3">
                                <div v-for="notification in pendingNotifications" :key="notification.id" class="flex items-center p-3 bg-gray-50 rounded-lg">
                                    <div class="flex-shrink-0">
                                        <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                                    </div>
                                    <div class="ml-3 flex-1">
                                        <p class="text-sm font-medium text-gray-900">{{ notification.data.message }}</p>
                                        <p class="text-xs text-gray-500">{{ formatDate(notification.scheduled_at) }}</p>
                                    </div>
                                </div>
                                <div v-if="pendingNotifications.length === 0" class="text-center py-4 text-gray-500">
                                    Aucune notification en attente
                                </div>
                            </div>
                        </div>

                        <!-- Missions for Validation -->
                        <div class="bg-white rounded-xl shadow-sm p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Missions à Valider</h3>
                            <div class="space-y-3">
                                <div v-for="mission in missionsForValidation" :key="mission.id" class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">{{ mission.bail_mobilite.tenant_name }}</p>
                                        <p class="text-xs text-gray-500">{{ mission.type === 'checkin' ? 'Entrée' : 'Sortie' }} - {{ mission.agent.name }}</p>
                                    </div>
                                    <Link
                                        :href="route('ops.bail-mobilites.show', mission.bail_mobilite.id)"
                                        class="text-primary-600 hover:text-primary-700 text-sm font-medium"
                                    >
                                        Valider
                                    </Link>
                                </div>
                                <div v-if="missionsForValidation.length === 0" class="text-center py-4 text-gray-500">
                                    Aucune mission à valider
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Ending Soon -->
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Se Terminent Bientôt (10 jours)</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div v-for="bailMobilite in endingSoon" :key="bailMobilite.id" class="border border-orange-200 rounded-lg p-4 bg-orange-50">
                                <div class="flex items-center justify-between mb-2">
                                    <h4 class="font-medium text-gray-900">{{ bailMobilite.tenant_name }}</h4>
                                    <span class="text-xs bg-orange-100 text-orange-800 px-2 py-1 rounded-full">
                                        {{ getRemainingDays(bailMobilite.end_date) }} jours
                                    </span>
                                </div>
                                <p class="text-sm text-gray-600 mb-2">{{ bailMobilite.address }}</p>
                                <p class="text-xs text-gray-500 mb-3">Fin: {{ formatDate(bailMobilite.end_date) }}</p>
                                <div class="flex space-x-2">
                                    <Link
                                        :href="route('ops.bail-mobilites.show', bailMobilite.id)"
                                        class="text-xs bg-primary-600 text-white px-3 py-1 rounded hover:bg-primary-700"
                                    >
                                        Voir Détails
                                    </Link>
                                    <button
                                        v-if="!bailMobilite.exit_mission.agent_id"
                                        @click="assignExit(bailMobilite)"
                                        class="text-xs bg-orange-600 text-white px-3 py-1 rounded hover:bg-orange-700"
                                    >
                                        Assigner Sortie
                                    </button>
                                </div>
                            </div>
                            <div v-if="endingSoon.length === 0" class="col-span-full text-center py-8 text-gray-500">
                                Aucun Bail Mobilité se terminant bientôt
                            </div>
                        </div>
                    </div>

                    <!-- Recent Bail Mobilités -->
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">Bail Mobilités Récents</h3>
                            <Link
                                :href="route('ops.bail-mobilites.index')"
                                class="text-primary-600 hover:text-primary-700 text-sm font-medium"
                            >
                                Voir Tous
                            </Link>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Locataire</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Adresse</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dates</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="bailMobilite in recentBailMobilites" :key="bailMobilite.id">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {{ bailMobilite.tenant_name }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ bailMobilite.address }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ formatDate(bailMobilite.start_date) }} - {{ formatDate(bailMobilite.end_date) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span :class="getStatusClass(bailMobilite.status)" class="px-2 py-1 text-xs font-medium rounded-full">
                                                {{ getStatusLabel(bailMobilite.status) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <Link
                                                :href="route('ops.bail-mobilites.show', bailMobilite.id)"
                                                class="text-primary-600 hover:text-primary-700"
                                            >
                                                Voir
                                            </Link>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </DashboardOps>
    </div>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'
import DashboardOps from '@/Layouts/DashboardOps.vue'

const props = defineProps({
    stats: Object,
    recentBailMobilites: Array,
    pendingNotifications: Array,
    missionsForValidation: Array,
    endingSoon: Array,
})

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

const assignExit = (bailMobilite) => {
    // This would open a modal or redirect to assignment page
    console.log('Assign exit for:', bailMobilite.id)
}
</script>