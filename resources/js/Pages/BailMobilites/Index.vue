<template>
    <div>
        <Head title="Bail Mobilités" />

        <DashboardOps>
            <template #header>
                <div class="flex justify-between items-center">
                    <h2 class="font-semibold text-xl text-text-primary leading-tight">
                        Gestion des Bail Mobilités
                    </h2>
                    <Link :href="route('ops.bail-mobilites.create')">
                        <PrimaryButton>
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Nouveau Bail Mobilité
                        </PrimaryButton>
                    </Link>
                </div>
            </template>

            <div class="py-12">
                <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <!-- Filters -->
                    <div class="bg-white rounded-xl shadow-md p-6 mb-6">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-text-secondary mb-2">Checker</label>
                                <select v-model="filters.checker_id" @change="applyFilters" class="w-full bg-white border-gray-200 rounded-md shadow-sm p-md focus:ring-2 focus:ring-primary focus:border-transparent">
                                    <option value="">Tous les checkers</option>
                                    <option v-for="checker in checkers" :key="checker.id" :value="checker.id">
                                        {{ checker.name }}
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-text-secondary mb-2">Date de début</label>
                                <input v-model="filters.date_from" @change="applyFilters" type="date" class="w-full bg-white border-gray-200 rounded-md shadow-sm p-md focus:ring-2 focus:ring-primary focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-text-secondary mb-2">Date de fin</label>
                                <input v-model="filters.date_to" @change="applyFilters" type="date" class="w-full bg-white border-gray-200 rounded-md shadow-sm p-md focus:ring-2 focus:ring-primary focus:border-transparent">
                            </div>
                            <div class="flex items-end">
                                <SecondaryButton @click="clearFilters" class="w-full">
                                    Effacer les filtres
                                </SecondaryButton>
                            </div>
                        </div>
                    </div>

                    <!-- Kanban Board -->
                    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                        <!-- Assigned Column -->
                        <div class="bg-white rounded-xl shadow-md">
                            <div class="p-4 border-b border-gray-200">
                                <div class="flex items-center justify-between">
                                    <h3 class="font-semibold text-text-primary">Assignés</h3>
                                    <span class="bg-warning-bg text-warning-text text-xs font-medium px-2.5 py-0.5 rounded-full">
                                        {{ kanbanData.assigned.length }}
                                    </span>
                                </div>
                            </div>
                            <div class="p-4 space-y-3 max-h-96 overflow-y-auto">
                                <BailMobiliteCard
                                    v-for="bailMobilite in kanbanData.assigned"
                                    :key="bailMobilite.id"
                                    :bail-mobilite="bailMobilite"
                                    :checkers="checkers"
                                    @assign-entry="handleAssignEntry"
                                    @assign-exit="handleAssignExit"
                                    @view-details="viewDetails"
                                />
                                <div v-if="kanbanData.assigned.length === 0" class="text-center py-8 text-text-secondary">
                                    Aucun BM assigné
                                </div>
                            </div>
                        </div>

                        <!-- In Progress Column -->
                        <div class="bg-white rounded-xl shadow-md">
                            <div class="p-4 border-b border-gray-200">
                                <div class="flex items-center justify-between">
                                    <h3 class="font-semibold text-text-primary">En Cours</h3>
                                    <span class="bg-info-bg text-info-text text-xs font-medium px-2.5 py-0.5 rounded-full">
                                        {{ kanbanData.in_progress.length }}
                                    </span>
                                </div>
                            </div>
                            <div class="p-4 space-y-3 max-h-96 overflow-y-auto">
                                <BailMobiliteCard
                                    v-for="bailMobilite in kanbanData.in_progress"
                                    :key="bailMobilite.id"
                                    :bail-mobilite="bailMobilite"
                                    :checkers="checkers"
                                    @assign-entry="handleAssignEntry"
                                    @assign-exit="handleAssignExit"
                                    @view-details="viewDetails"
                                />
                                <div v-if="kanbanData.in_progress.length === 0" class="text-center py-8 text-text-secondary">
                                    Aucun BM en cours
                                </div>
                            </div>
                        </div>

                        <!-- Completed Column -->
                        <div class="bg-white rounded-xl shadow-md">
                            <div class="p-4 border-b border-gray-200">
                                <div class="flex items-center justify-between">
                                    <h3 class="font-semibold text-text-primary">Terminés</h3>
                                    <span class="bg-success-bg text-success-text text-xs font-medium px-2.5 py-0.5 rounded-full">
                                        {{ kanbanData.completed.length }}
                                    </span>
                                </div>
                            </div>
                            <div class="p-4 space-y-3 max-h-96 overflow-y-auto">
                                <BailMobiliteCard
                                    v-for="bailMobilite in kanbanData.completed"
                                    :key="bailMobilite.id"
                                    :bail-mobilite="bailMobilite"
                                    :checkers="checkers"
                                    @view-details="viewDetails"
                                />
                                <div v-if="kanbanData.completed.length === 0" class="text-center py-8 text-text-secondary">
                                    Aucun BM terminé
                                </div>
                            </div>
                        </div>

                        <!-- Incident Column -->
                        <div class="bg-white rounded-xl shadow-md">
                            <div class="p-4 border-b border-gray-200">
                                <div class="flex items-center justify-between">
                                    <h3 class="font-semibold text-text-primary">Incidents</h3>
                                    <span class="bg-error-bg text-error-text text-xs font-medium px-2.5 py-0.5 rounded-full">
                                        {{ kanbanData.incident.length }}
                                    </span>
                                </div>
                            </div>
                            <div class="p-4 space-y-3 max-h-96 overflow-y-auto">
                                <BailMobiliteCard
                                    v-for="bailMobilite in kanbanData.incident"
                                    :key="bailMobilite.id"
                                    :bail-mobilite="bailMobilite"
                                    :checkers="checkers"
                                    @handle-incident="handleIncident"
                                    @view-details="viewDetails"
                                />
                                <div v-if="kanbanData.incident.length === 0" class="text-center py-8 text-text-secondary">
                                    Aucun incident
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </DashboardOps>

        <!-- Assignment Modal -->
        <AssignmentModal
            v-if="showAssignmentModal"
            :bail-mobilite="selectedBailMobilite"
            :checkers="checkers"
            :assignment-type="assignmentType"
            @close="closeAssignmentModal"
            @assign="handleAssignment"
        />

        <!-- Incident Modal -->
        <IncidentModal
            v-if="showIncidentModal"
            :bail-mobilite="selectedBailMobilite"
            :checkers="checkers"
            @close="closeIncidentModal"
            @resolve="handleIncidentResolution"
        />
    </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import DashboardOps from '@/Layouts/DashboardOps.vue'
import BailMobiliteCard from '@/Components/BailMobiliteCard.vue'
import AssignmentModal from '@/Components/AssignmentModal.vue'
import IncidentModal from '@/Components/IncidentModal.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'

const props = defineProps({
    kanbanData: Object,
    checkers: Array,
    filters: Object
})

const filters = reactive({
    checker_id: props.filters.checker_id || '',
    date_from: props.filters.date_from || '',
    date_to: props.filters.date_to || '',
})

const showAssignmentModal = ref(false)
const showIncidentModal = ref(false)
const selectedBailMobilite = ref(null)
const assignmentType = ref('entry') // 'entry' or 'exit'

const applyFilters = () => {
    router.get(route('ops.bail-mobilites.index'), filters, {
        preserveState: true,
        preserveScroll: true
    })
}

const clearFilters = () => {
    filters.checker_id = ''
    filters.date_from = ''
    filters.date_to = ''
    applyFilters()
}

const handleAssignEntry = (bailMobilite) => {
    selectedBailMobilite.value = bailMobilite
    assignmentType.value = 'entry'
    showAssignmentModal.value = true
}

const handleAssignExit = (bailMobilite) => {
    selectedBailMobilite.value = bailMobilite
    assignmentType.value = 'exit'
    showAssignmentModal.value = true
}

const handleIncident = (bailMobilite) => {
    selectedBailMobilite.value = bailMobilite
    showIncidentModal.value = true
}

const viewDetails = (bailMobilite) => {
    router.visit(route('ops.bail-mobilites.show', bailMobilite.id))
}

const closeAssignmentModal = () => {
    showAssignmentModal.value = false
    selectedBailMobilite.value = null
}

const closeIncidentModal = () => {
    showIncidentModal.value = false
    selectedBailMobilite.value = null
}

const handleAssignment = (data) => {
    const endpoint = assignmentType.value === 'entry' 
        ? route('ops.bail-mobilites.assign-entry', selectedBailMobilite.value.id)
        : route('ops.bail-mobilites.assign-exit', selectedBailMobilite.value.id)
    
    router.post(endpoint, data, {
        onSuccess: () => {
            closeAssignmentModal()
        }
    })
}

const handleIncidentResolution = (data) => {
    router.post(route('ops.bail-mobilites.handle-incident', selectedBailMobilite.value.id), data, {
        onSuccess: () => {
            closeIncidentModal()
        }
    })
}
</script>