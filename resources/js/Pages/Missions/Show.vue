<template>
    <component :is="$page.props.auth.user.roles.includes('super-admin') ? DashboardSuperAdmin : DashboardChecker">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Mission Details
                </h2>
                <div v-if="$page.props.auth.user.roles.includes('super-admin')" class="flex space-x-4">
                    <Link
                        :href="route('missions.edit', mission.id)"
                        class="px-4 py-2 bg-yellow-600 text-white rounded hover:bg-yellow-700"
                    >
                        Edit Mission
                    </Link>
                    <button
                        @click="deleteMission"
                        class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700"
                    >
                        Delete Mission
                    </button>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <!-- Status Badge -->
                        <div class="mb-6 flex justify-between items-center">
                            <span :class="getStatusClass(mission.status)" class="text-sm">
                                {{ mission.status }}
                            </span>
                            <div v-if="canUpdateStatus" class="flex space-x-4">
                                <button
                                    v-if="mission.status === 'assigned'"
                                    @click="updateStatus('in_progress')"
                                    class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
                                >
                                    Start Mission
                                </button>
                                <Link
                                    v-if="mission.status === 'in_progress'"
                                    :href="route('checklist.create', mission.id)"
                                    class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700"
                                >
                                    Démarrer checklist
                                </Link>
                                <button
                                    v-if="mission.status === 'in_progress'"
                                    @click="updateStatus('completed')"
                                    class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700"
                                >
                                    Complete Mission
                                </button>
                            </div>
                        </div>

                        <!-- Mission Details -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <h3 class="text-lg font-semibold mb-4">Mission Information</h3>
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Type</label>
                                        <p class="mt-1">{{ mission.type === 'checkin' ? 'Check-in' : 'Check-out' }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Scheduled Date/Time</label>
                                        <p class="mt-1">{{ formatDate(mission.scheduled_at) }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Address</label>
                                        <p class="mt-1">{{ mission.address }}</p>
                                    </div>
                                    <div v-if="mission.notes">
                                        <label class="block text-sm font-medium text-gray-700">Notes</label>
                                        <p class="mt-1">{{ mission.notes }}</p>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <h3 class="text-lg font-semibold mb-4">Tenant Information</h3>
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Name</label>
                                        <p class="mt-1">{{ mission.tenant_name }}</p>
                                    </div>
                                    <div v-if="mission.tenant_phone">
                                        <label class="block text-sm font-medium text-gray-700">Phone</label>
                                        <p class="mt-1">{{ mission.tenant_phone }}</p>
                                    </div>
                                    <div v-if="mission.tenant_email">
                                        <label class="block text-sm font-medium text-gray-700">Email</label>
                                        <p class="mt-1">{{ mission.tenant_email }}</p>
                                    </div>
                                </div>

                                <div class="mt-8">
                                    <h3 class="text-lg font-semibold mb-4">Assigned Agent</h3>
                                    <div v-if="mission.agent">
                                        <p>{{ mission.agent.name }}</p>
                                        <p class="text-sm text-gray-600">{{ mission.agent.email }}</p>
                                    </div>
                                    <p v-else class="text-gray-600">No agent assigned</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </component>
</template>

<script setup>
import { computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import DashboardSuperAdmin from '@/Layouts/DashboardSuperAdmin.vue'
import DashboardChecker from '@/Layouts/DashboardChecker.vue'

const props = defineProps({
    mission: Object
})

const canUpdateStatus = computed(() => {
    const user = usePage().props.auth.user
    return (
        (user.roles.includes('checker') && props.mission.agent_id === user.id) ||
        user.roles.includes('super-admin')
    )
})

const formatDate = (date) => {
    return new Date(date).toLocaleString()
}

const getStatusClass = (status) => {
    const classes = {
        unassigned: 'bg-gray-100 text-gray-800',
        assigned: 'bg-yellow-100 text-yellow-800',
        in_progress: 'bg-blue-100 text-blue-800',
        completed: 'bg-green-100 text-green-800',
        cancelled: 'bg-red-100 text-red-800'
    }
    return `px-3 py-1 rounded-full ${classes[status]}`
}

const updateStatus = (newStatus) => {
    router.patch(route('missions.update-status', props.mission.id), {
        status: newStatus
    })
}

const deleteMission = () => {
    if (confirm('Are you sure you want to delete this mission?')) {
        router.delete(route('missions.destroy', props.mission.id))
    }
}
</script>