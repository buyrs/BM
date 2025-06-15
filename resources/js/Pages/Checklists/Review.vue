<template>
    <DashboardChecker>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ mission.type === 'checkin' ? 'Check-in' : 'Check-out' }} Review
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <!-- General Information -->
                        <div class="mb-8">
                            <h3 class="text-lg font-semibold mb-4">General Information</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div v-for="(value, key) in checklist.general_info" :key="key">
                                    <h4 class="font-medium text-gray-700 capitalize">{{ formatKey(key) }}</h4>
                                    <div class="mt-1">
                                        <p v-if="typeof value === 'object'">
                                            <span class="font-medium">Type:</span> {{ value.type }}<br>
                                            <span class="font-medium">Condition:</span> {{ value.condition }}<br>
                                            <span class="font-medium">Comment:</span> {{ value.comment }}
                                        </p>
                                        <p v-else>{{ value }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Rooms -->
                        <div class="mb-8">
                            <h3 class="text-lg font-semibold mb-4">Rooms</h3>
                            <div class="space-y-6">
                                <div v-for="(room, roomName) in checklist.rooms" :key="roomName" class="border rounded-lg p-4">
                                    <h4 class="font-medium text-lg capitalize mb-3">{{ formatKey(roomName) }}</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div v-for="(value, key) in room" :key="key">
                                            <h5 class="font-medium text-gray-700 capitalize">{{ formatKey(key) }}</h5>
                                            <div class="mt-1">
                                                <p v-if="typeof value === 'object'">
                                                    <span class="font-medium">Condition:</span> {{ value.condition }}<br>
                                                    <span class="font-medium">Comment:</span> {{ value.comment }}
                                                </p>
                                                <p v-else>{{ value }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Utilities -->
                        <div class="mb-8">
                            <h3 class="text-lg font-semibold mb-4">Utilities</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div v-for="(value, key) in checklist.utilities" :key="key">
                                    <h4 class="font-medium text-gray-700 capitalize">{{ formatKey(key) }}</h4>
                                    <div class="mt-1">
                                        <p v-if="typeof value === 'object'">
                                            <span class="font-medium">Type:</span> {{ value.type }}<br>
                                            <span class="font-medium">Condition:</span> {{ value.condition }}<br>
                                            <span class="font-medium">Comment:</span> {{ value.comment }}
                                        </p>
                                        <p v-else>{{ value }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Signatures -->
                        <div class="mt-8">
                            <h3 class="text-lg font-semibold mb-4">Signatures</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                <div>
                                    <h4 class="font-medium text-gray-700 mb-2">Tenant Signature</h4>
                                    <img v-if="checklist.tenant_signature" :src="checklist.tenant_signature" alt="Tenant Signature" class="border rounded p-2" />
                                    <p v-else class="text-gray-500">No signature provided</p>
                                </div>
                                <div>
                                    <h4 class="font-medium text-gray-700 mb-2">Agent Signature</h4>
                                    <img v-if="checklist.agent_signature" :src="checklist.agent_signature" alt="Agent Signature" class="border rounded p-2" />
                                    <p v-else class="text-gray-500">No signature provided</p>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="mt-8 flex justify-end space-x-4">
                            <Link
                                :href="route('checklist.edit', [mission.id, checklist.id])"
                                class="px-4 py-2 bg-yellow-600 text-white rounded hover:bg-yellow-700"
                            >
                                Edit Checklist
                            </Link>
                            <Link
                                :href="route('missions.show', mission.id)"
                                class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700"
                            >
                                Back to Mission
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </DashboardChecker>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import DashboardChecker from '@/Layouts/DashboardChecker.vue'

const props = defineProps({
    mission: Object,
    checklist: Object
})

const formatKey = (key) => {
    return key.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ')
}
</script> 