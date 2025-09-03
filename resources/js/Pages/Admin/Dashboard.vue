<template>
    <Head title="Admin Dashboard" />

    <DashboardAdmin>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-3xl font-bold text-text-primary">
                        Welcome back, Admin!
                    </h2>
                    <p class="text-text-secondary mt-1">
                        Here's what's happening with your properties today.
                    </p>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
                    <!-- Active Missions Card -->
                    <div class="bg-white rounded-xl shadow-md p-4 sm:p-6 border-l-4 border-info-border">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-sm sm:text-lg font-semibold text-text-secondary">
                                    Total Missions
                                </h3>
                                <p class="text-2xl sm:text-4xl font-extrabold text-info-text mt-1 sm:mt-2">
                                    {{ stats.totalMissions || 0 }}
                                </p>
                            </div>
                            <div class="p-2 sm:p-3 rounded-full bg-info-bg">
                                <svg class="w-6 h-6 sm:w-8 sm:h-8 text-info-text" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Assigned Missions Card -->
                    <div class="bg-white rounded-xl shadow-md p-4 sm:p-6 border-l-4 border-warning-border">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-sm sm:text-lg font-semibold text-text-secondary">
                                    Assigned Missions
                                </h3>
                                <p class="text-2xl sm:text-4xl font-extrabold text-warning-text mt-1 sm:mt-2">
                                    {{ stats.assignedMissions || 0 }}
                                </p>
                            </div>
                            <div class="p-2 sm:p-3 rounded-full bg-warning-bg">
                                <svg class="w-6 h-6 sm:w-8 sm:h-8 text-warning-text" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Completed Missions Card -->
                    <div class="bg-white rounded-xl shadow-md p-4 sm:p-6 border-l-4 border-success-border">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-sm sm:text-lg font-semibold text-text-secondary">
                                    Completed Missions
                                </h3>
                                <p class="text-2xl sm:text-4xl font-extrabold text-success-text mt-1 sm:mt-2">
                                    {{ stats.completedMissions || 0 }}
                                </p>
                            </div>
                            <div class="p-2 sm:p-3 rounded-full bg-success-bg">
                                <svg class="w-6 h-6 sm:w-8 sm:h-8 text-success-text" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Completion Rate Card -->
                    <div class="bg-white rounded-xl shadow-md p-4 sm:p-6 border-l-4 border-primary">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-sm sm:text-lg font-semibold text-text-secondary">
                                    Completion Rate
                                </h3>
                                <p class="text-2xl sm:text-4xl font-extrabold text-primary mt-1 sm:mt-2">
                                    {{ getCompletionRate() }}%
                                </p>
                            </div>
                            <div class="p-2 sm:p-3 rounded-full bg-secondary">
                                <svg class="w-6 h-6 sm:w-8 sm:h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Missions Table -->
                <div class="bg-white rounded-xl shadow-md p-4 sm:p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg sm:text-xl font-bold text-text-primary">
                            Recent Missions
                        </h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="border-b border-gray-200">
                                    <th class="py-3 px-2 sm:px-4 text-xs font-medium text-text-secondary uppercase tracking-wider">
                                        Mission ID
                                    </th>
                                    <th class="py-3 px-2 sm:px-4 text-xs font-medium text-text-secondary uppercase tracking-wider hidden sm:table-cell">
                                        Property Address
                                    </th>
                                    <th class="py-3 px-2 sm:px-4 text-xs font-medium text-text-secondary uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th class="py-3 px-2 sm:px-4 text-xs font-medium text-text-secondary uppercase tracking-wider hidden md:table-cell">
                                        Date
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="mission in recentMissions.slice(0, 5)"
                                    :key="mission.id"
                                    class="border-b border-gray-200 hover:bg-gray-50 transition-colors duration-150"
                                >
                                    <td class="py-3 sm:py-4 px-2 sm:px-4 text-sm text-text-primary">
                                        <div class="font-medium">#{{ mission.id }}</div>
                                        <div class="sm:hidden text-xs text-text-secondary mt-1">
                                            {{ mission.address || 'N/A' }}
                                        </div>
                                    </td>
                                    <td class="py-3 sm:py-4 px-2 sm:px-4 text-sm text-text-primary hidden sm:table-cell">
                                        {{ mission.address || 'N/A' }}
                                    </td>
                                    <td class="py-3 sm:py-4 px-2 sm:px-4">
                                        <span
                                            :class="getStatusClass(mission.status)"
                                            class="inline-flex items-center px-2 sm:px-3 py-1 rounded-full text-xs font-medium"
                                        >
                                            {{ formatStatus(mission.status) }}
                                        </span>
                                        <div class="md:hidden text-xs text-text-secondary mt-1">
                                            {{ formatDate(mission.created_at) }}
                                        </div>
                                    </td>
                                    <td class="py-3 sm:py-4 px-2 sm:px-4 text-sm text-text-primary hidden md:table-cell">
                                        {{ formatDate(mission.created_at) }}
                                    </td>
                                </tr>
                                <tr v-if="recentMissions.length === 0">
                                    <td colspan="4" class="py-8 text-center text-text-secondary">
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
        completed: "bg-success-bg text-success-text",
        in_progress: "bg-info-bg text-info-text",
        assigned: "bg-warning-bg text-warning-text",
        unassigned: "bg-gray-100 text-gray-800",
        incident: "bg-error-bg text-error-text",
    };
    return statusClasses[status] || "bg-gray-100 text-gray-800";
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