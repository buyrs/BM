<template>
    <Head title="Checker Missions" />

    <DashboardChecker>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">My Missions</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <!-- Filters -->
                        <div class="mb-6 flex space-x-4">
                            <select
                                v-model="filterStatus"
                                class="rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                            >
                                <option value="all">All Status</option>
                                <option value="assigned">Assigned</option>
                                <option value="in_progress">In Progress</option>
                                <option value="completed">Completed</option>
                            </select>

                            <select
                                v-model="filterType"
                                class="rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                            >
                                <option value="all">All Types</option>
                                <option value="checkin">Check-in</option>
                                <option value="checkout">Check-out</option>
                            </select>
                        </div>

                        <!-- Missions Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
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
                            <p class="mt-1 text-sm text-gray-500">No missions match your current filters.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </DashboardChecker>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head } from '@inertiajs/vue3'
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
</script> 