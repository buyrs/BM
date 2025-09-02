<template>
    <Head title="Admin Dashboard" />

    <DashboardAdmin>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-3xl font-bold text-gray-900 dark:text-white">
                        Welcome back, Admin!
                    </h2>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">
                        Here's what's happening with your properties today.
                    </p>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <!-- Active Missions Card -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6 border-l-4 border-blue-500">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300">
                                    Total Missions
                                </h3>
                                <p class="text-4xl font-extrabold text-blue-600 mt-2">
                                    {{ stats.totalMissions || 0 }}
                                </p>
                            </div>
                            <div class="p-3 rounded-full bg-blue-100 dark:bg-blue-900/50">
                                <svg class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Assigned Missions Card -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6 border-l-4 border-yellow-400">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300">
                                    Assigned Missions
                                </h3>
                                <p class="text-4xl font-extrabold text-yellow-600 mt-2">
                                    {{ stats.assignedMissions || 0 }}
                                </p>
                            </div>
                            <div class="p-3 rounded-full bg-yellow-100 dark:bg-yellow-900/50">
                                <svg class="w-8 h-8 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Completed Missions Card -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6 border-l-4 border-green-500">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300">
                                    Completed Missions
                                </h3>
                                <p class="text-4xl font-extrabold text-green-600 mt-2">
                                    {{ stats.completedMissions || 0 }}
                                </p>
                            </div>
                            <div class="p-3 rounded-full bg-green-100 dark:bg-green-900/50">
                                <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Completion Rate Card -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6 border-l-4 border-purple-500">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300">
                                    Completion Rate
                                </h3>
                                <p class="text-4xl font-extrabold text-purple-600 mt-2">
                                    {{ getCompletionRate() }}%
                                </p>
                            </div>
                            <div class="p-3 rounded-full bg-purple-100 dark:bg-purple-900/50">
                                <svg class="w-8 h-8 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Missions Table -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white">
                            Recent Missions
                        </h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="border-b border-gray-200 dark:border-gray-700">
                                    <th class="py-3 px-4 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Mission ID
                                    </th>
                                    <th class="py-3 px-4 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Property Address
                                    </th>
                                    <th class="py-3 px-4 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th class="py-3 px-4 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Date
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="mission in recentMissions.slice(0, 5)"
                                    :key="mission.id"
                                    class="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-150"
                                >
                                    <td class="py-4 px-4 text-sm text-gray-900 dark:text-gray-300">
                                        #{{ mission.id }}
                                    </td>
                                    <td class="py-4 px-4 text-sm text-gray-900 dark:text-gray-300">
                                        {{ mission.address || 'N/A' }}
                                    </td>
                                    <td class="py-4 px-4">
                                        <span
                                            :class="getStatusClass(mission.status)"
                                            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium"
                                        >
                                            {{ formatStatus(mission.status) }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-4 text-sm text-gray-900 dark:text-gray-300">
                                        {{ formatDate(mission.created_at) }}
                                    </td>
                                </tr>
                                <tr v-if="recentMissions.length === 0">
                                    <td colspan="4" class="py-8 text-center text-gray-500 dark:text-gray-400">
                                        No recent missions to display
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </DashboardAdmin>
</template>

<script setup>
import DashboardAdmin from "@/Layouts/DashboardAdmin.vue";
import { Head } from "@inertiajs/vue3";

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({
            totalMissions: 0,
            assignedMissions: 0,
            completedMissions: 0
        }),
    },
    recentMissions: {
        type: Array,
        default: () => [],
    },
});

// Helper methods for dashboard calculations
const getCompletionRate = () => {
    if (!props.stats.totalMissions || props.stats.totalMissions === 0) return 0;
    return Math.round((props.stats.completedMissions / props.stats.totalMissions) * 100);
};

const getStatusClass = (status) => {
    const statusClasses = {
        completed: "bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300",
        in_progress: "bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300",
        assigned: "bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300",
        unassigned: "bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300",
        incident: "bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300",
    };
    return statusClasses[status] || "bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300";
};

const formatStatus = (status) => {
    const statusLabels = {
        completed: "Completed",
        in_progress: "In Progress",
        assigned: "Assigned",
        unassigned: "Unassigned",
        incident: "Incident",
    };
    return statusLabels[status] || status;
};

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString("en-US", {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};
</script>