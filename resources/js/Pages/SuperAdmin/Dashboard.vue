<template>
    <Head title="Super Admin Dashboard" />

    <DashboardSuperAdmin>
        <template #header>
            <h2 class="font-semibold text-xl text-text-primary leading-tight">Super Admin Dashboard</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                    <!-- Total Missions Card -->
                    <div class="bg-white overflow-hidden shadow-md rounded-lg">
                        <div class="p-6 text-text-primary">
                            <h3 class="text-lg font-medium">Total Missions</h3>
                            <p class="text-3xl font-bold text-primary">{{ stats.totalMissions }}</p>
                        </div>
                    </div>

                    <!-- Pending Missions Card -->
                    <div class="bg-white overflow-hidden shadow-md rounded-lg">
                        <div class="p-6 text-text-primary">
                            <h3 class="text-lg font-medium">Pending Missions</h3>
                            <p class="text-3xl font-bold text-warning-text">{{ stats.pendingMissions }}</p>
                        </div>
                    </div>

                    <!-- Assigned Missions Card -->
                    <div class="bg-white overflow-hidden shadow-md rounded-lg">
                        <div class="p-6 text-text-primary">
                            <h3 class="text-lg font-medium">Assigned Missions</h3>
                            <p class="text-3xl font-bold text-info-text">{{ stats.assignedMissions }}</p>
                        </div>
                    </div>

                    <!-- Completed Missions Card -->
                    <div class="bg-white overflow-hidden shadow-md rounded-lg">
                        <div class="p-6 text-text-primary">
                            <h3 class="text-lg font-medium">Completed Missions</h3>
                            <p class="text-3xl font-bold text-success-text">{{ stats.completedMissions }}</p>
                        </div>
                    </div>

                    <!-- Total Checkers Card -->
                    <div class="bg-white overflow-hidden shadow-md rounded-lg">
                        <div class="p-6 text-text-primary">
                            <h3 class="text-lg font-medium">Total Checkers</h3>
                            <p class="text-3xl font-bold text-primary">{{ stats.totalCheckers }}</p>
                        </div>
                    </div>
                </div>

                <!-- Recent Missions Section -->
                <div class="bg-white overflow-hidden shadow-md rounded-lg">
                    <div class="p-6 text-text-primary">
                        <h3 class="text-lg font-medium mb-4">Recent Missions</h3>
                        <ul v-if="recentMissions.length">
                            <li v-for="mission in recentMissions" :key="mission.id" class="border-b last:border-b-0 py-2">
                                <p class="font-semibold">{{ mission.address }}</p>
                                <p class="text-sm text-text-secondary">Type: {{ mission.type }} | Status: {{ mission.status }}</p>
                                <p class="text-sm text-text-secondary">Agent: {{ mission.agent ? mission.agent.name : 'N/A' }}</p>
                                <p class="text-sm text-text-secondary">Scheduled: {{ new Date(mission.scheduled_at).toLocaleString() }}</p>
                            </li>
                        </ul>
                        <p v-else class="text-text-secondary">No recent missions to display.</p>
                    </div>
                </div>
            </div>
        </div>
    </DashboardSuperAdmin>
</template>

<script setup>
import DashboardSuperAdmin from '@/Layouts/DashboardSuperAdmin.vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    stats: {
        type: Object,
        required: true,
        default: () => ({}),
    },
    recentMissions: {
        type: Array,
        required: true,
        default: () => [],
    },
});
</script>