<template>
    <div>
        <Head title="Ops Dashboard" />

        <DashboardOps>
            <template #header>
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-3xl font-bold text-gray-900">Welcome back, {{ $page.props.auth.user.name }}!</h2>
                        <p class="text-gray-600 mt-1">Here's what's happening with your properties today.</p>
                    </div>
                    <div class="flex items-center space-x-3">
                        <!-- View Toggle -->
                        <div class="flex bg-gray-100 dark:bg-gray-800 rounded-lg p-1 shadow-inner">
                            <button
                                @click="currentView = 'overview'"
                                :class="[
                                    'px-4 py-2 text-sm font-medium rounded-md transition-all duration-200 flex items-center gap-2',
                                    currentView === 'overview' 
                                        ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm transform scale-105' 
                                        : 'text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-white/50 dark:hover:bg-gray-700/50'
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
                                        ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm transform scale-105' 
                                        : 'text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-white/50 dark:hover:bg-gray-700/50'
                                ]"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 0a2 2 0 012 2v6a2 2 0 01-2 2m-6 0a2 2 0 002 2h2a2 2 0 002-2"/>
                                </svg>
                                Kanban
                            </button>
                        </div>
                        
                        <button class="bg-blue-600 text-white px-6 py-3 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50 transition-colors duration-200 flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Schedule New Mission
                        </button>
                    </div>
                </div>
            </template>

            <div class="py-8">
                <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
                    <!-- Overview View -->
                    <div v-if="currentView === 'overview'">
                        <!-- Missions Requiring Attention -->
                        <div class="bg-white rounded-xl shadow p-6 border border-red-200">
                            <h3 class="text-xl font-bold text-red-700 flex items-center mb-4">
                                <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                                </svg>
                                Missions Requiring Attention
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <Link href="#" class="block p-4 bg-red-50 rounded-lg hover:bg-red-100 transition-colors duration-200">
                                    <div class="flex items-center text-red-600">
                                        <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        <h4 class="font-semibold">Overdue Missions</h4>
                                    </div>
                                    <p class="text-3xl font-bold text-red-800 mt-2">{{ getOverdueMissions() }}</p>
                                    <p class="text-xs text-red-600">View Details</p>
                                </Link>
                                <Link href="#" class="block p-4 bg-orange-50 rounded-lg hover:bg-orange-100 transition-colors duration-200">
                                    <div class="flex items-center text-orange-600">
                                        <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                                        </svg>
                                        <h4 class="font-semibold">Unassigned Missions</h4>
                                    </div>
                                    <p class="text-3xl font-bold text-orange-800 mt-2">{{ getUnassignedMissions() }}</p>
                                    <p class="text-xs text-orange-600">View Details</p>
                                </Link>
                                <Link href="#" class="block p-4 bg-yellow-50 rounded-lg hover:bg-yellow-100 transition-colors duration-200">
                                    <div class="flex items-center text-yellow-600">
                                        <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                                        </svg>
                                        <h4 class="font-semibold">Critical Incidents</h4>
                                    </div>
                                    <p class="text-3xl font-bold text-yellow-800 mt-2">{{ getCriticalIncidents() }}</p>
                                    <p class="text-xs text-yellow-600">View Details</p>
                                </Link>
                            </div>
                        </div>

                        <!-- Statistics Cards -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div class="bg-white rounded-xl shadow p-6 border-l-4 border-blue-500">
                                <h3 class="text-lg font-semibold text-gray-600">Active Missions</h3>
                                <p class="text-4xl font-extrabold text-gray-900 mt-2">{{ metrics.basic?.assigned || 0 }}</p>
                            </div>
                            <div class="bg-white rounded-xl shadow p-6 border-l-4 border-yellow-400">
                                <h3 class="text-lg font-semibold text-gray-600">Open Incidents</h3>
                                <p class="text-4xl font-extrabold text-gray-900 mt-2">{{ metrics.incidents?.total_open || 0 }}</p>
                            </div>
                            <div class="bg-white rounded-xl shadow p-6 border-l-4 border-green-500">
                                <h3 class="text-lg font-semibold text-gray-600">Avg. Completion Time</h3>
                                <p class="text-4xl font-extrabold text-gray-900 mt-2">{{ metrics.average_duration || 0 }} days</p>
                            </div>
                        </div>

                        <!-- Main Content Grid -->
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                            <!-- Recent Incidents -->
                            <div class="lg:col-span-2 bg-white rounded-xl shadow p-6">
                                <div class="flex justify-between items-center mb-4">
                                    <h3 class="text-xl font-bold text-gray-900">Recent Incidents</h3>
                                    <Link href="#" class="text-sm font-medium text-blue-600 hover:text-blue-800">View all</Link>
                                </div>
                                <div class="overflow-x-auto">
                                    <table class="w-full text-left">
                                        <thead>
                                            <tr class="border-b border-gray-200">
                                                <th class="py-3 px-4 text-xs font-medium text-gray-600 uppercase tracking-wider">Incident ID</th>
                                                <th class="py-3 px-4 text-xs font-medium text-gray-600 uppercase tracking-wider">Property Address</th>
                                                <th class="py-3 px-4 text-xs font-medium text-gray-600 uppercase tracking-wider">Status</th>
                                                <th class="py-3 px-4 text-xs font-medium text-gray-600 uppercase tracking-wider">Reported Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="notification in pendingNotifications.slice(0, 3)" :key="notification.id" class="border-b border-gray-200 hover:bg-gray-50 transition-colors duration-150">
                                                <td class="py-4 px-4 text-sm text-gray-600">#INC{{ String(notification.id).padStart(5, '0') }}</td>
                                                <td class="py-4 px-4 text-sm text-gray-600">{{ notification.bail_mobilite?.address || 'N/A' }}</td>
                                                <td class="py-4 px-4">
                                                    <span :class="getStatusClass(notification.status)" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium">
                                                        {{ notification.status }}
                                                    </span>
                                                </td>
                                                <td class="py-4 px-4 text-sm text-gray-600">{{ formatDate(notification.created_at) }}</td>
                                            </tr>
                                            <tr v-if="pendingNotifications.length === 0">
                                                <td colspan="4" class="py-8 text-center text-gray-500">No recent incidents to display</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Analytics -->
                            <div class="bg-white rounded-xl shadow p-6">
                                <h3 class="text-xl font-bold text-gray-900 mb-4">Analytics</h3>
                                <div class="mb-6">
                                    <div class="flex justify-between items-baseline">
                                        <p class="text-sm font-medium text-gray-600">Mission Completion Rate</p>
                                        <p class="text-2xl font-bold text-green-600">{{ getCompletionRate() }}%</p>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2.5 mt-2">
                                        <div class="bg-green-500 h-2.5 rounded-full" :style="{ width: getCompletionRate() + '%' }"></div>
                                    </div>
                                </div>
                                <div>
                                    <div class="flex justify-between items-baseline">
                                        <p class="text-sm font-medium text-gray-600">Incident Trends</p>
                                        <p class="text-2xl font-bold text-red-500">{{ metrics.incidents?.detected_this_week || 0 }}</p>
                                    </div>
                                    <div class="h-40 mt-2 flex items-end justify-center">
                                        <svg class="w-full h-full" fill="none" preserveAspectRatio="none" viewBox="0 0 472 150">
                                            <path d="M0 109C18.1538 109 18.1538 21 36.3077 21C54.4615 21 54.4615 41 72.6154 41C90.7692 41 90.7692 93 108.923 93C127.077 93 127.077 33 145.231 33C163.385 33 163.385 101 181.538 101C199.692 101 199.692 61 217.846 61C236 61 236 45 254.154 45C272.308 45 272.308 121 290.462 121C308.615 121 308.615 149 326.769 149C344.923 149 344.923 1 363.077 1C381.231 1 381.231 81 399.385 81C417.538 81 417.538 129 435.692 129C453.846 129 453.846 25 472 25" stroke="#3B82F6" stroke-linecap="round" stroke-width="3"/>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Notifications and Actions -->
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <!-- Pending Notifications -->
                            <NotificationPanel :notifications="pendingNotifications" />

                            <!-- Missions for Validation -->
                            <div class="bg-white rounded-xl shadow p-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">Missions à Valider</h3>
                                <div class="space-y-3">
                                    <div v-for="mission in missionsForValidation" :key="mission.id" class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">{{ mission.bail_mobilite?.tenant_name || 'N/A' }}</p>
                                            <p class="text-xs text-gray-500">{{ mission.type === 'checkin' ? 'Entrée' : 'Sortie' }} - {{ mission.agent?.name || 'N/A' }}</p>
                                        </div>
                                        <Link
                                            :href="route('ops.bail-mobilites.show', mission.bail_mobilite?.id)"
                                            class="text-blue-600 hover:text-blue-700 text-sm font-medium"
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
                        <div class="bg-white rounded-xl shadow p-6">
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
                                            class="text-xs bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700"
                                        >
                                            Voir Détails
                                        </Link>
                                        <button
                                            v-if="!bailMobilite.exit_mission?.agent_id"
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
                    </div>

                    <!-- Kanban View -->
                    <div v-if="currentView === 'kanban'" class="space-y-6">
                        <!-- Filters -->
                        <div class="bg-white rounded-xl shadow-sm p-4">
                            <div class="flex flex-wrap items-center gap-4">
                                <div class="flex-1 min-w-64">
                                    <input
                                        v-model="filters.search"
                                        @input="debouncedSearch"
                                        type="text"
                                        placeholder="Rechercher par nom, adresse, email..."
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    />
                                </div>
                                
                                <select
                                    v-model="filters.status"
                                    @change="applyFilters"
                                    class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                >
                                    <option value="">Tous les statuts</option>
                                    <option value="assigned">Assigné</option>
                                    <option value="in_progress">En Cours</option>
                                    <option value="completed">Terminé</option>
                                    <option value="incident">Incident</option>
                                </select>
                                
                                <button
                                    @click="resetFilters"
                                    class="px-3 py-2 text-sm text-gray-600 hover:text-gray-900"
                                >
                                    Réinitialiser
                                </button>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 min-h-screen">
                            <!-- Assigned Column -->
                            <div class="bg-gray-50 rounded-xl p-4">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="font-semibold text-gray-900">Assigné</h3>
                                    <span class="bg-yellow-100 text-yellow-800 text-xs font-medium px-2 py-1 rounded-full">
                                        {{ kanbanData.assigned?.length || 0 }}
                                    </span>
                                </div>
                                <div class="space-y-3">
                                    <BailMobiliteCard
                                        v-for="bm in kanbanData.assigned"
                                        :key="bm.id"
                                        :bail-mobilite="bm"
                                        @view-details="viewBailMobilite"
                                        @assign-entry="handleAssignEntry"
                                        @assign-exit="handleAssignExit"
                                        @handle-incident="handleIncident"
                                    />
                                </div>
                            </div>

                            <!-- In Progress Column -->
                            <div class="bg-gray-50 rounded-xl p-4">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="font-semibold text-gray-900">En Cours</h3>
                                    <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2 py-1 rounded-full">
                                        {{ kanbanData.in_progress?.length || 0 }}
                                    </span>
                                </div>
                                <div class="space-y-3">
                                    <BailMobiliteCard
                                        v-for="bm in kanbanData.in_progress"
                                        :key="bm.id"
                                        :bail-mobilite="bm"
                                        @view-details="viewBailMobilite"
                                        @assign-entry="handleAssignEntry"
                                        @assign-exit="handleAssignExit"
                                        @handle-incident="handleIncident"
                                    />
                                </div>
                            </div>

                            <!-- Completed Column -->
                            <div class="bg-gray-50 rounded-xl p-4">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="font-semibold text-gray-900">Terminé</h3>
                                    <span class="bg-green-100 text-green-800 text-xs font-medium px-2 py-1 rounded-full">
                                        {{ kanbanData.completed?.length || 0 }}
                                    </span>
                                </div>
                                <div class="space-y-3">
                                    <BailMobiliteCard
                                        v-for="bm in kanbanData.completed"
                                        :key="bm.id"
                                        :bail-mobilite="bm"
                                        @view-details="viewBailMobilite"
                                        @assign-entry="handleAssignEntry"
                                        @assign-exit="handleAssignExit"
                                        @handle-incident="handleIncident"
                                    />
                                </div>
                            </div>

                            <!-- Incident Column -->
                            <div class="bg-gray-50 rounded-xl p-4">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="font-semibold text-gray-900">Incident</h3>
                                    <span class="bg-red-100 text-red-800 text-xs font-medium px-2 py-1 rounded-full">
                                        {{ kanbanData.incident?.length || 0 }}
                                    </span>
                                </div>
                                <div class="space-y-3">
                                    <BailMobiliteCard
                                        v-for="bm in kanbanData.incident"
                                        :key="bm.id"
                                        :bail-mobilite="bm"
                                        @view-details="viewBailMobilite"
                                        @assign-entry="handleAssignEntry"
                                        @assign-exit="handleAssignExit"
                                        @handle-incident="handleIncident"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </DashboardOps>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import DashboardOps from '@/Layouts/DashboardOps.vue';
import NotificationPanel from '@/Components/NotificationPanel.vue';
import BailMobiliteCard from '@/Components/BailMobiliteCard.vue';
import { debounce } from 'lodash';

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
});

// Reactive data
const currentView = ref('overview');
const filters = ref({
    search: '',
    status: '',
    ops_user_id: '',
    checker_id: '',
    date_from: '',
    date_to: '',
});

// Helper methods
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

const getCompletionRate = () => {
    const total = props.metrics.basic?.total || 0;
    const completed = props.metrics.basic?.completed || 0;
    if (total === 0) return 0;
    return Math.round((completed / total) * 100);
};

const getStatusClass = (status) => {
    const statusClasses = {
        'pending': 'bg-yellow-100 text-yellow-800',
        'handled': 'bg-green-100 text-green-800',
        'open': 'bg-yellow-100 text-yellow-800',
        'resolved': 'bg-green-100 text-green-800',
        'completed': 'bg-green-100 text-green-800',
        'in_progress': 'bg-blue-100 text-blue-800',
        'assigned': 'bg-yellow-100 text-yellow-800',
        'incident': 'bg-red-100 text-red-800',
    };
    return statusClasses[status] || 'bg-gray-100 text-gray-800';
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('fr-FR');
};

const getRemainingDays = (endDate) => {
    const now = new Date();
    const end = new Date(endDate);
    const diffTime = end - now;
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    return Math.max(0, diffDays);
};

// Event handlers
const viewBailMobilite = (bailMobilite) => {
    router.visit(route('ops.bail-mobilites.show', bailMobilite.id));
};

const handleAssignEntry = (bailMobilite) => {
    router.visit(route('ops.bail-mobilites.show', bailMobilite.id), {
        data: { action: 'assign_entry' }
    });
};

const handleAssignExit = (bailMobilite) => {
    router.visit(route('ops.bail-mobilites.show', bailMobilite.id), {
        data: { action: 'assign_exit' }
    });
};

const handleIncident = (bailMobilite) => {
    router.visit(route('ops.bail-mobilites.show', bailMobilite.id), {
        data: { action: 'handle_incident' }
    });
};

const assignExit = (bailMobilite) => {
    handleAssignExit(bailMobilite);
};

// Filter methods
const debouncedSearch = debounce(() => {
    applyFilters();
}, 300);

const applyFilters = () => {
    router.get(route('ops.dashboard'), filters.value, {
        preserveState: true,
        preserveScroll: true,
    });
};

const resetFilters = () => {
    filters.value = {
        search: '',
        status: '',
        ops_user_id: '',
        checker_id: '',
        date_from: '',
        date_to: '',
    };
    applyFilters();
};
</script>