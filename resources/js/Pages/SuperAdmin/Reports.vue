<template>
    <Head title="Reports" />

    <DashboardSuperAdmin>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Reports</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900">Total Missions</h3>
                            <p class="mt-2 text-3xl font-bold text-indigo-600">{{ stats.totalMissions }}</p>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900">Completed Missions</h3>
                            <p class="mt-2 text-3xl font-bold text-green-600">{{ stats.completedMissions }}</p>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900">Active Checkers</h3>
                            <p class="mt-2 text-3xl font-bold text-blue-600">{{ stats.activeCheckers }}</p>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900">Completion Rate</h3>
                            <p class="mt-2 text-3xl font-bold text-purple-600">{{ stats.completionRate }}%</p>
                        </div>
                    </div>
                </div>

                <!-- Mission Type Distribution -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-8">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Mission Type Distribution</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <h4 class="text-sm font-medium text-gray-500">Check-in Missions</h4>
                                <p class="mt-1 text-2xl font-semibold text-indigo-600">{{ stats.checkinMissions }}</p>
                            </div>
                            <div>
                                <h4 class="text-sm font-medium text-gray-500">Check-out Missions</h4>
                                <p class="mt-1 text-2xl font-semibold text-indigo-600">{{ stats.checkoutMissions }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top Performing Checkers -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-8">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Top Performing Checkers</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Completed Missions</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Completion Rate</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="checker in topCheckers" :key="checker.id">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ checker.name }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ checker.completed_missions_count }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ checker.completion_rate }}%
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Recent Activity</h3>
                        <div class="space-y-4">
                            <div v-for="activity in recentActivity" :key="activity.id" class="flex items-start space-x-4">
                                <div class="flex-shrink-0">
                                    <span class="inline-flex items-center justify-center h-8 w-8 rounded-full" :class="getActivityColor(activity.type)">
                                        <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getActivityIcon(activity.type)"/>
                                        </svg>
                                    </span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900">{{ activity.description }}</p>
                                    <p class="text-sm text-gray-500">{{ new Date(activity.created_at).toLocaleString() }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </DashboardSuperAdmin>
</template>

<script setup>
import { Head } from '@inertiajs/vue3'
import DashboardSuperAdmin from '@/Layouts/DashboardSuperAdmin.vue'

const props = defineProps({
    stats: {
        type: Object,
        required: true,
        default: () => ({
            totalMissions: 0,
            completedMissions: 0,
            activeCheckers: 0,
            completionRate: 0,
            checkinMissions: 0,
            checkoutMissions: 0
        })
    },
    topCheckers: {
        type: Array,
        required: true,
        default: () => []
    },
    recentActivity: {
        type: Array,
        required: true,
        default: () => []
    }
})

const getActivityColor = (type) => {
    const colors = {
        mission_created: 'bg-blue-500',
        mission_completed: 'bg-green-500',
        checker_assigned: 'bg-yellow-500',
        checker_removed: 'bg-red-500'
    }
    return colors[type] || 'bg-gray-500'
}

const getActivityIcon = (type) => {
    const icons = {
        mission_created: 'M12 4v16m8-8H4',
        mission_completed: 'M5 13l4 4L19 7',
        checker_assigned: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
        checker_removed: 'M6 18L18 6M6 6l12 12'
    }
    return icons[type] || 'M12 4v16m8-8H4'
}
</script> 