<template>
    <Head title="Admin Dashboard" />

    <DashboardAdmin>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Admin Dashboard</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="flex justify-end mb-6">
                    <Link :href="route('missions.create')">
                        <PrimaryButton>Create Mission</PrimaryButton>
                    </Link>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <!-- Total Missions Card -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6 text-gray-900">
                            <h3 class="text-lg font-medium">Total Missions</h3>
                            <p class="text-3xl font-bold text-indigo-600">{{ stats.totalMissions }}</p>
                        </div>
                    </div>
                    <!-- Assigned Missions Card -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6 text-gray-900">
                            <h3 class="text-lg font-medium">Assigned Missions</h3>
                            <p class="text-3xl font-bold text-blue-600">{{ stats.assignedMissions }}</p>
                        </div>
                    </div>
                    <!-- Completed Missions Card -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6 text-gray-900">
                            <h3 class="text-lg font-medium">Completed Missions</h3>
                            <p class="text-3xl font-bold text-green-600">{{ stats.completedMissions }}</p>
                        </div>
                    </div>
                </div>

                <!-- Analytics Charts Placeholder -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-8">
                    <div class="p-6 text-gray-900 flex items-center justify-center h-48">
                        <span class="text-gray-500 text-lg">Analytics Charts Coming Soon</span>
                    </div>
                </div>

                <div class="flex justify-end mb-8">
                    <Link :href="route('admin.checkers')">
                        <PrimaryButton>Manage Checkers</PrimaryButton>
                    </Link>
                </div>

                <div class="flex justify-end mb-8">
                    <Link :href="route('admin.settings')">
                        <PrimaryButton>Settings</PrimaryButton>
                    </Link>
                </div>

                <!-- Recent Missions Section -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-medium mb-4">Recent Missions</h3>
                        <ul v-if="recentMissions.length">
                            <li v-for="mission in recentMissions" :key="mission.id" class="border-b last:border-b-0 py-2">
                                <p class="font-semibold">{{ mission.address }}</p>
                                <p class="text-sm text-gray-600">Type: {{ mission.type }} | Status: {{ mission.status }}</p>
                                <p class="text-sm text-gray-600">Agent: {{ mission.agent ? mission.agent.name : 'N/A' }}</p>
                                <p class="text-sm text-gray-600">Scheduled: {{ new Date(mission.scheduled_at).toLocaleString() }}</p>
                            </li>
                        </ul>
                        <p v-else class="text-gray-600">No recent missions to display.</p>
                    </div>
                </div>
            </div>
        </div>
    </DashboardAdmin>
</template>

<script setup>
import DashboardAdmin from '@/Layouts/DashboardAdmin.vue';
import { Head } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';

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