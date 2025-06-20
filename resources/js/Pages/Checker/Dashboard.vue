<template>
    <Head title="Checker Dashboard" />

    <DashboardChecker>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Checker Dashboard</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                    <!-- Assigned Missions Card -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6 text-gray-900">
                            <h3 class="text-lg font-medium">Assigned Missions</h3>
                            <p class="text-3xl font-bold text-blue-600">{{ assignedMissions.length }}</p>
                        </div>
                    </div>

                    <!-- Completed Missions Card -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6 text-gray-900">
                            <h3 class="text-lg font-medium">Completed Missions</h3>
                            <p class="text-3xl font-bold text-green-600">{{ completedMissionsCount }}</p>
                        </div>
                    </div>

                    <!-- Pending Checklists Card -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6 text-gray-900">
                            <h3 class="text-lg font-medium">Pending Checklists</h3>
                            <p class="text-3xl font-bold text-yellow-600">{{ pendingChecklistsCount }}</p>
                        </div>
                    </div>
                </div>

                <!-- Recent Assigned Missions Section -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-medium mb-4">Recent Assigned Missions</h3>
                        <ul v-if="assignedMissions.length">
                            <li v-for="mission in assignedMissions" :key="mission.id" class="border-b last:border-b-0 py-2">
                                <p class="font-semibold">{{ mission.address }}</p>
                                <p class="text-sm text-gray-600">Type: {{ mission.type }} | Status: {{ mission.status }}</p>
                                <p class="text-sm text-gray-600">Scheduled: {{ new Date(mission.scheduled_at).toLocaleString() }}</p>
                            </li>
                        </ul>
                        <p v-else class="text-gray-600">No assigned missions to display.</p>
                    </div>
                </div>
            </div>
        </div>
    </DashboardChecker>
</template>

<script setup>
import DashboardChecker from '@/Layouts/DashboardChecker.vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    assignedMissions: {
        type: Array,
        required: true,
        default: () => [],
    },
    completedMissionsCount: {
        type: Number,
        required: true,
        default: 0,
    },
    pendingChecklistsCount: {
        type: Number,
        required: true,
        default: 0,
    },
});
</script>