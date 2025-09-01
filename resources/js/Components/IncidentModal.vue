<template>
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background overlay -->
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="$emit('close')"></div>

            <!-- Modal panel -->
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                Gestion d'Incident
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">
                                    {{ bailMobilite.tenant_name }} - {{ bailMobilite.address }}
                                </p>
                                <p class="text-xs text-gray-400 mt-1">
                                    Statut: Incident détecté
                                </p>
                            </div>
                        </div>
                    </div>

                    <form @submit.prevent="handleSubmit" class="mt-6">
                        <div class="space-y-6">
                            <!-- Action Type Selection -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-3">
                                    Action à Effectuer *
                                </label>
                                <div class="space-y-3">
                                    <label class="flex items-start">
                                        <input
                                            v-model="form.action"
                                            type="radio"
                                            value="resolve"
                                            class="mt-1 h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300"
                                        />
                                        <div class="ml-3">
                                            <div class="text-sm font-medium text-gray-900">Résoudre l'Incident</div>
                                            <div class="text-xs text-gray-500">Marquer le Bail Mobilité comme terminé</div>
                                        </div>
                                    </label>

                                    <label class="flex items-start">
                                        <input
                                            v-model="form.action"
                                            type="radio"
                                            value="create_task"
                                            class="mt-1 h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300"
                                        />
                                        <div class="ml-3">
                                            <div class="text-sm font-medium text-gray-900">Créer une Tâche Corrective</div>
                                            <div class="text-xs text-gray-500">Créer une tâche pour résoudre le problème</div>
                                        </div>
                                    </label>

                                    <label class="flex items-start">
                                        <input
                                            v-model="form.action"
                                            type="radio"
                                            value="reassign"
                                            class="mt-1 h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300"
                                        />
                                        <div class="ml-3">
                                            <div class="text-sm font-medium text-gray-900">Réassigner la Mission</div>
                                            <div class="text-xs text-gray-500">Assigner la mission à un autre checker</div>
                                        </div>
                                    </label>
                                </div>
                                <div v-if="errors.action" class="mt-1 text-sm text-red-600">
                                    {{ errors.action }}
                                </div>
                            </div>

                            <!-- Description -->
                            <div>
                                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                                    Description / Commentaires *
                                </label>
                                <textarea
                                    id="description"
                                    v-model="form.description"
                                    rows="4"
                                    class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500"
                                    :class="{ 'border-red-300': errors.description }"
                                    :placeholder="getDescriptionPlaceholder()"
                                    required
                                ></textarea>
                                <div v-if="errors.description" class="mt-1 text-sm text-red-600">
                                    {{ errors.description }}
                                </div>
                            </div>

                            <!-- New Checker Selection (only for reassign) -->
                            <div v-if="form.action === 'reassign'">
                                <label for="new_checker_id" class="block text-sm font-medium text-gray-700 mb-2">
                                    Nouveau Checker *
                                </label>
                                <select
                                    id="new_checker_id"
                                    v-model="form.new_checker_id"
                                    class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500"
                                    :class="{ 'border-red-300': errors.new_checker_id }"
                                    required
                                >
                                    <option value="">Choisir un nouveau checker...</option>
                                    <option 
                                        v-for="checker in availableCheckers" 
                                        :key="checker.id" 
                                        :value="checker.id"
                                        :disabled="isCurrentChecker(checker.id)"
                                    >
                                        {{ checker.name }}
                                        <span v-if="isCurrentChecker(checker.id)" class="text-gray-400">(Actuellement assigné)</span>
                                        <span v-else-if="checker.current_missions_count > 0" class="text-gray-500">
                                            ({{ checker.current_missions_count }} mission{{ checker.current_missions_count > 1 ? 's' : '' }})
                                        </span>
                                    </option>
                                </select>
                                <div v-if="errors.new_checker_id" class="mt-1 text-sm text-red-600">
                                    {{ errors.new_checker_id }}
                                </div>
                            </div>

                            <!-- Current Issue Summary -->
                            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                                <h4 class="text-sm font-medium text-red-800 mb-2">Problèmes Détectés</h4>
                                <div class="text-sm text-red-700 space-y-1">
                                    <div v-if="!bailMobilite.exit_signature?.tenant_signature" class="flex items-center">
                                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                        </svg>
                                        Signature du locataire manquante
                                    </div>
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                        </svg>
                                        Problème lors de la validation
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="mt-6 flex items-center justify-end space-x-3">
                            <button
                                type="button"
                                @click="$emit('close')"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                            >
                                Annuler
                            </button>
                            <button
                                type="submit"
                                :disabled="!form.action || !form.description || (form.action === 'reassign' && !form.new_checker_id) || processing"
                                class="px-4 py-2 text-sm font-medium text-white bg-red-600 border border-transparent rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <span v-if="processing">Traitement...</span>
                                <span v-else>{{ getActionButtonText() }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'

const props = defineProps({
    bailMobilite: {
        type: Object,
        required: true
    },
    checkers: {
        type: Array,
        required: true
    }
})

const emit = defineEmits(['close', 'resolve'])

const form = reactive({
    action: '',
    description: '',
    new_checker_id: ''
})

const errors = reactive({})
const processing = ref(false)

const availableCheckers = computed(() => {
    return props.checkers.map(checker => ({
        ...checker,
        current_missions_count: 0 // This would come from the API in real implementation
    }))
})

const isCurrentChecker = (checkerId) => {
    return props.bailMobilite.exit_mission?.agent_id === checkerId
}

const getDescriptionPlaceholder = () => {
    switch (form.action) {
        case 'resolve':
            return 'Décrivez comment l\'incident a été résolu...'
        case 'create_task':
            return 'Décrivez la tâche corrective à effectuer...'
        case 'reassign':
            return 'Expliquez pourquoi la mission doit être réassignée...'
        default:
            return 'Décrivez l\'action à effectuer...'
    }
}

const getActionButtonText = () => {
    switch (form.action) {
        case 'resolve':
            return 'Résoudre l\'Incident'
        case 'create_task':
            return 'Créer la Tâche'
        case 'reassign':
            return 'Réassigner'
        default:
            return 'Confirmer'
    }
}

const handleSubmit = () => {
    // Clear previous errors
    Object.keys(errors).forEach(key => delete errors[key])
    
    // Basic validation
    if (!form.action) {
        errors.action = 'Veuillez sélectionner une action'
        return
    }
    
    if (!form.description.trim()) {
        errors.description = 'La description est requise'
        return
    }
    
    if (form.action === 'reassign' && !form.new_checker_id) {
        errors.new_checker_id = 'Veuillez sélectionner un nouveau checker'
        return
    }
    
    processing.value = true
    emit('resolve', { ...form })
}
</script>