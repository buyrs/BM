<template>
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background overlay -->
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="$emit('close')"></div>

            <!-- Modal panel -->
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-secondary sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                Assigner {{ assignmentType === 'entry' ? 'l\'Entrée' : 'la Sortie' }}
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">
                                    {{ bailMobilite.tenant_name }} - {{ bailMobilite.address }}
                                </p>
                                <p class="text-xs text-gray-400 mt-1">
                                    {{ assignmentType === 'entry' ? 'Date d\'entrée' : 'Date de sortie' }}: 
                                    {{ formatDate(assignmentType === 'entry' ? bailMobilite.start_date : bailMobilite.end_date) }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <form @submit.prevent="handleSubmit" class="mt-6">
                        <div class="space-y-4">
                            <!-- Checker Selection -->
                            <div>
                                <label for="checker_id" class="block text-sm font-medium text-gray-700 mb-2">
                                    Sélectionner un Checker *
                                </label>
                                <select
                                    id="checker_id"
                                    v-model="form.checker_id"
                                    class="w-full rounded-lg border-gray-200 focus:border-primary focus:ring-primary transition-colors duration-200"
                                    :class="{ 'border-error-border': errors.checker_id }"
                                    required
                                >
                                    <option value="">Choisir un checker...</option>
                                    <option v-for="checker in availableCheckers" :key="checker.id" :value="checker.id">
                                        {{ checker.name }} 
                                        <span v-if="checker.current_missions_count > 0" class="text-gray-500">
                                            ({{ checker.current_missions_count }} mission{{ checker.current_missions_count > 1 ? 's' : '' }})
                                        </span>
                                    </option>
                                </select>
                                <div v-if="errors.checker_id" class="mt-1 text-sm text-red-600">
                                    {{ errors.checker_id }}
                                </div>
                            </div>

                            <!-- Time Selection (required for exit) -->
                            <div v-if="assignmentType === 'exit'">
                                <label for="scheduled_time" class="block text-sm font-medium text-gray-700 mb-2">
                                    Heure de Rendez-vous *
                                </label>
                                <input
                                    id="scheduled_time"
                                    v-model="form.scheduled_time"
                                    type="time"
                                    class="w-full rounded-lg border-gray-200 focus:border-primary focus:ring-primary transition-colors duration-200"
                                    :class="{ 'border-error-border': errors.scheduled_time }"
                                    required
                                />
                                <div v-if="errors.scheduled_time" class="mt-1 text-sm text-red-600">
                                    {{ errors.scheduled_time }}
                                </div>
                            </div>

                            <!-- Optional Time for Entry -->
                            <div v-if="assignmentType === 'entry'">
                                <label for="scheduled_time" class="block text-sm font-medium text-gray-700 mb-2">
                                    Heure de Rendez-vous (optionnel)
                                </label>
                                <input
                                    id="scheduled_time"
                                    v-model="form.scheduled_time"
                                    type="time"
                                    class="w-full rounded-lg border-gray-200 focus:border-primary focus:ring-primary transition-colors duration-200"
                                />
                                <p class="mt-1 text-xs text-gray-500">
                                    Si non spécifiée, le checker pourra choisir l'heure
                                </p>
                            </div>

                            <!-- Checker Availability Info -->
                            <div v-if="form.checker_id && selectedChecker" class="bg-gray-50 rounded-lg p-3">
                                <h4 class="text-sm font-medium text-gray-900 mb-2">Informations du Checker</h4>
                                <div class="text-xs text-gray-600 space-y-1">
                                    <p><strong>Nom:</strong> {{ selectedChecker.name }}</p>
                                    <p><strong>Email:</strong> {{ selectedChecker.email }}</p>
                                    <p><strong>Missions en cours:</strong> {{ selectedChecker.current_missions_count }}</p>
                                    <div v-if="selectedChecker.is_available" class="flex items-center text-green-600">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                        Disponible
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="mt-6 flex items-center justify-end space-x-3">
                            <button
                                type="button"
                                @click="$emit('close')"
                                class="px-4 py-2 text-sm font-medium text-text-primary bg-white border border-gray-200 rounded-lg hover:bg-secondary focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors duration-200"
                            >
                                Annuler
                            </button>
                            <button
                                type="submit"
                                :disabled="!form.checker_id || (assignmentType === 'exit' && !form.scheduled_time) || processing"
                                class="px-4 py-2 text-sm font-medium text-white bg-primary border border-transparent rounded-lg hover:bg-accent focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-200"
                            >
                                <span v-if="processing">Assignation...</span>
                                <span v-else>Assigner</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import axios from 'axios'

const props = defineProps({
    bailMobilite: {
        type: Object,
        required: true
    },
    checkers: {
        type: Array,
        required: true
    },
    assignmentType: {
        type: String,
        required: true,
        validator: (value) => ['entry', 'exit'].includes(value)
    }
})

const emit = defineEmits(['close', 'assign'])

const form = reactive({
    checker_id: '',
    scheduled_time: ''
})

const errors = reactive({})
const processing = ref(false)
const availableCheckers = ref([])

const selectedChecker = computed(() => {
    return availableCheckers.value.find(checker => checker.id == form.checker_id)
})

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('fr-FR', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    })
}

const loadAvailableCheckers = async () => {
    try {
        const targetDate = props.assignmentType === 'entry' 
            ? props.bailMobilite.start_date 
            : props.bailMobilite.end_date

        const response = await axios.get(route('ops.checkers.available'), {
            params: {
                date: targetDate,
                time: form.scheduled_time
            }
        })
        
        availableCheckers.value = response.data
    } catch (error) {
        console.error('Error loading available checkers:', error)
        // Fallback to all checkers if API fails
        availableCheckers.value = props.checkers.map(checker => ({
            ...checker,
            is_available: true,
            current_missions_count: 0
        }))
    }
}

const handleSubmit = () => {
    // Clear previous errors
    Object.keys(errors).forEach(key => delete errors[key])
    
    // Basic validation
    if (!form.checker_id) {
        errors.checker_id = 'Veuillez sélectionner un checker'
        return
    }
    
    if (props.assignmentType === 'exit' && !form.scheduled_time) {
        errors.scheduled_time = 'L\'heure est requise pour les sorties'
        return
    }
    
    processing.value = true
    emit('assign', { ...form })
}

onMounted(() => {
    loadAvailableCheckers()
})
</script>