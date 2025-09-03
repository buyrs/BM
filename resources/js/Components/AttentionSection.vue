<template>
    <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-error-border">
        <h3 class="text-xl font-bold text-error-text flex items-center mb-4">
            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
            </svg>
            Missions Requiring Attention
        </h3>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Overdue Missions -->
            <Link
                v-if="overdueMissions > 0"
                href="#"
                class="block p-4 bg-error-bg rounded-lg hover:bg-red-100 transition-colors duration-200"
            >
                <div class="flex items-center text-error-text">
                    <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <h4 class="font-semibold">Missions en Retard</h4>
                </div>
                <p class="text-3xl font-bold text-error-text mt-2">{{ overdueMissions }}</p>
                <p class="text-xs text-error-text">Voir Détails</p>
            </Link>
            
            <!-- Unassigned Missions -->
            <Link
                v-if="unassignedMissions > 0"
                href="#"
                class="block p-4 bg-warning-bg rounded-lg hover:bg-orange-100 transition-colors duration-200"
            >
                <div class="flex items-center text-warning-text">
                    <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                    </svg>
                    <h4 class="font-semibold">Missions Non Assignées</h4>
                </div>
                <p class="text-3xl font-bold text-warning-text mt-2">{{ unassignedMissions }}</p>
                <p class="text-xs text-warning-text">Voir Détails</p>
            </Link>
            
            <!-- Critical Incidents -->
            <Link
                v-if="criticalIncidents > 0"
                href="#"
                class="block p-4 bg-yellow-50 rounded-lg hover:bg-yellow-100 transition-colors duration-200"
            >
                <div class="flex items-center text-yellow-600">
                    <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                    </svg>
                    <h4 class="font-semibold">Incidents Critiques</h4>
                </div>
                <p class="text-3xl font-bold text-yellow-800 mt-2">{{ criticalIncidents }}</p>
                <p class="text-xs text-yellow-600">Voir Détails</p>
            </Link>
        </div>
        
        <!-- Quick Actions -->
        <div class="mt-6 pt-4 border-t border-gray-200">
            <div class="flex flex-wrap gap-3">
                <SecondaryButton
                    v-if="unassignedMissions > 0"
                    @click="$emit('bulkAssign')"
                    class="text-sm"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                    </svg>
                    Assigner en Lot
                </SecondaryButton>
                
                <SecondaryButton
                    v-if="overdueMissions > 0"
                    @click="$emit('reschedule')"
                    class="text-sm"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    Reprogrammer
                </SecondaryButton>
                
                <SecondaryButton
                    v-if="criticalIncidents > 0"
                    @click="$emit('handleIncidents')"
                    class="text-sm"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                    </svg>
                    Gérer Incidents
                </SecondaryButton>
                
                <Link
                    :href="route('ops.notifications')"
                    class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-text-secondary bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM4 19h6v-6H4v6zM16 3h5v5h-5V3zM4 3h6v6H4V3z"/>
                    </svg>
                    Voir Toutes les Notifications
                </Link>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import SecondaryButton from './SecondaryButton.vue'

defineProps({
    overdueMissions: {
        type: Number,
        default: 0
    },
    unassignedMissions: {
        type: Number,
        default: 0
    },
    criticalIncidents: {
        type: Number,
        default: 0
    }
})

defineEmits(['bulkAssign', 'reschedule', 'handleIncidents'])
</script>