<template>
    <div>
        <Head title="Missions" />

        <DashboardAdmin v-if="$page.props.auth.user.roles.includes('super-admin') || $page.props.auth.user.roles.includes('admin')">
            <template #header>
                <div class="flex justify-between items-center">
                    <h2 class="font-semibold text-xl text-gray-900 leading-tight">
                        Missions
                    </h2>
                    <Link
                        :href="route('missions.create')"
                        class="inline-flex items-center px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-lg hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Create Mission
                    </Link>
                </div>
            </template>

            <div class="py-12">
                <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <!-- Filters -->
                    <div class="bg-white rounded-xl shadow-sm p-4 sm:p-6 mb-4 sm:mb-6">
                        <div class="space-y-4">
                            <!-- Type Filters -->
                            <div>
                                <h3 class="text-sm font-medium text-gray-700 mb-2">Mission Type</h3>
                                <div class="flex flex-wrap gap-2">
                                    <button
                                        v-for="type in ['all', 'checkin', 'checkout']"
                                        :key="type"
                                        @click="filterType = type"
                                        :class="[
                                            'px-3 sm:px-4 py-2 rounded-lg text-xs sm:text-sm font-medium transition-colors duration-200',
                                            filterType === type
                                                ? 'bg-primary-100 text-primary-800'
                                                : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                                        ]"
                                    >
                                        {{ type === 'all' ? 'All Types' : type === 'checkin' ? 'Check-in' : 'Check-out' }}
                                    </button>
                                </div>
                            </div>

                            <!-- Status Filters -->
                            <div>
                                <h3 class="text-sm font-medium text-gray-700 mb-2">Status</h3>
                                <div class="flex flex-wrap gap-2">
                                    <button
                                        v-for="status in ['all', 'unassigned', 'assigned', 'in_progress', 'completed', 'cancelled']"
                                        :key="status"
                                        @click="filterStatus = status"
                                        :class="[
                                            'px-3 sm:px-4 py-2 rounded-lg text-xs sm:text-sm font-medium transition-colors duration-200',
                                            filterStatus === status
                                                ? 'bg-primary-100 text-primary-800'
                                                : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                                        ]"
                                    >
                                        {{ formatStatus(status) }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Missions Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
                        <MissionCard
                            v-for="mission in filteredMissions"
                            :key="mission.id"
                            :mission="mission"
                        />
                    </div>

                    <!-- Empty State -->
                    <div v-if="filteredMissions.length === 0" class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No missions found</h3>
                        <p class="mt-1 text-sm text-gray-500">Get started by creating a new mission.</p>
                        <div class="mt-6">
                            <Link
                                :href="route('missions.create')"
                                class="inline-flex items-center px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-lg hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                            >
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                Create Mission
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </DashboardAdmin>

        <DashboardChecker v-else>
            <template #header>
                <h2 class="font-semibold text-xl text-gray-900 leading-tight">
                    My Missions
                </h2>
            </template>

            <div class="py-12">
                <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <!-- Missions Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
                        <MissionCard
                            v-for="mission in missions.data"
                            :key="mission.id"
                            :mission="mission"
                        />
                    </div>

                    <!-- Empty State -->
                    <div v-if="missions.data.length === 0" class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No missions assigned</h3>
                        <p class="mt-1 text-sm text-gray-500">You don't have any missions assigned to you yet.</p>
                    </div>
                </div>
            </div>
        </DashboardChecker>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, Head } from '@inertiajs/vue3'
import DashboardAdmin from '@/Layouts/DashboardAdmin.vue'
import DashboardChecker from '@/Layouts/DashboardChecker.vue'
import MissionCard from '@/Components/MissionCard.vue'

const props = defineProps({
    missions: Object
})

const filterStatus = ref('all')
const filterType = ref('all')

const filteredMissions = computed(() => {
    return props.missions.data.filter(mission => {
        const statusMatch = filterStatus.value === 'all' || mission.status === filterStatus.value
        const typeMatch = filterType.value === 'all' || mission.type === filterType.value
        return statusMatch && typeMatch
    })
})

const formatStatus = (status) => {
    const statusMap = {
        all: 'All',
        unassigned: 'Unassigned',
        assigned: 'Assigned',
        in_progress: 'In Progress',
        completed: 'Completed',
        cancelled: 'Cancelled'
    }
    return statusMap[status] || status
}
</script>