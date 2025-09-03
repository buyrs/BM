<template>
    <Modal :show="show" @close="$emit('close')" max-width="2xl">
        <div class="p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-semibold text-gray-900">
                    Assigner une Mission
                </h3>
                <button
                    @click="$emit('close')"
                    class="text-gray-400 hover:text-gray-600"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Mission Information -->
            <div v-if="mission" class="bg-gray-50 rounded-lg p-4 mb-6">
                <h4 class="font-medium text-gray-900 mb-2">Détails de la Mission</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="font-medium text-gray-700">Type:</span>
                        <span class="ml-2">{{ mission.mission_type === 'entry' ? 'Entrée' : 'Sortie' }}</span>
                    </div>
                    <div>
                        <span class="font-medium text-gray-700">Adresse:</span>
                        <span class="ml-2">{{ mission.address }}</span>
                    </div>
                    <div>
                        <span class="font-medium text-gray-700">Date:</span>
                        <span class="ml-2">{{ formatDate(mission.scheduled_at) }}</span>
                    </div>
                    <div>
                        <span class="font-medium text-gray-700">Locataire:</span>
                        <span class="ml-2">{{ mission.tenant_name || mission.bail_mobilite?.tenant_name }}</span>
                    </div>
                </div>
            </div>

            <!-- Assignment Form -->
            <form @submit.prevent="submitAssignment" class="space-y-6">
                <!-- Checker Selection -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Sélectionner un Checker
                        <span class="text-red-500">*</span>
                    </label>
                    <div class="space-y-2 max-h-64 overflow-y-auto">
                        <div
                            v-for="checker in availableCheckers"
                            :key="checker.id"
                            class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition-colors"
                            :class="{ 'border-blue-500 bg-blue-50': form.agent_id === checker.id }"
                            @click="selectChecker(checker)"
                        >
                            <input
                                type="radio"
                                :value="checker.id"
                                v-model="form.agent_id"
                                class="mr-3 text-blue-600 focus:ring-blue-500"
                            />
                            <div class="flex-1">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <div class="font-medium text-gray-900">{{ checker.name }}</div>
                                        <div class="text-sm text-gray-600">{{ checker.email }}</div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-sm font-medium" :class="getAvailabilityClass(checker)">
                                            {{ getAvailabilityText(checker) }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            {{ checker.active_missions_count || 0 }} missions actives
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Checker Skills/Specializations -->
                                <div v-if="checker.specializations && checker.specializations.length > 0" class="mt-2">
                                    <div class="flex flex-wrap gap-1">
                                        <span
                                            v-for="spec in checker.specializations"
                                            :key="spec"
                                            class="px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded-full"
                                        >
                                            {{ spec }}
                                        </span>
                                    </div>
                                </div>
                                
                                <!-- Conflicts Warning -->
                                <div v-if="hasConflict(checker)" class="mt-2 p-2 bg-yellow-50 border border-yellow-200 rounded text-xs text-yellow-800">
                                    <svg class="w-4 h-4 inline mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                    Conflit d'horaire détecté
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-if="errors.agent_id" class="text-red-600 text-sm mt-1">
                        {{ errors.agent_id }}
                    </div>
                </div>

                <!-- Scheduling Options -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Date programmée
                        </label>
                        <input
                            type="date"
                            v-model="form.scheduled_date"
                            class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            :min="minDate"
                        />
                        <div v-if="errors.scheduled_date" class="text-red-600 text-sm mt-1">
                            {{ errors.scheduled_date }}
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Heure programmée
                        </label>
                        <select
                            v-model="form.scheduled_time"
                            class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        >
                            <option value="">Sélectionner une heure</option>
                            <option v-for="time in timeSlots" :key="time.value" :value="time.value">
                                {{ time.label }}
                            </option>
                        </select>
                        <div v-if="errors.scheduled_time" class="text-red-600 text-sm mt-1">
                            {{ errors.scheduled_time }}
                        </div>
                    </div>
                </div>

                <!-- Priority -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Priorité
                    </label>
                    <select
                        v-model="form.priority"
                        class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    >
                        <option value="3">Normale</option>
                        <option value="1">Urgent</option>
                        <option value="2">Élevée</option>
                        <option value="4">Faible</option>
                        <option value="5">Très faible</option>
                    </select>
                </div>

                <!-- Estimated Duration -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Durée estimée (minutes)
                    </label>
                    <input
                        type="number"
                        v-model.number="form.estimated_duration"
                        min="15"
                        max="480"
                        step="15"
                        class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="60"
                    />
                </div>

                <!-- Notes -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Notes pour le Checker
                    </label>
                    <textarea
                        v-model="form.notes"
                        rows="3"
                        class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Instructions spéciales, codes d'accès, etc..."
                    ></textarea>
                </div>

                <!-- Notification Options -->
                <div>
                    <label class="flex items-center">
                        <input
                            type="checkbox"
                            v-model="form.send_notification"
                            class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"
                        />
                        <span class="ml-2 text-sm text-gray-700">
                            Envoyer une notification au Checker
                        </span>
                    </label>
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-between pt-4 border-t">
                    <button
                        type="button"
                        @click="$emit('close')"
                        class="px-6 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600 transition-colors"
                    >
                        Annuler
                    </button>
                    
                    <button
                        type="submit"
                        :disabled="processing || !form.agent_id"
                        class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                    >
                        {{ processing ? 'Attribution...' : 'Assigner la Mission' }}
                    </button>
                </div>
            </form>
        </div>
    </Modal>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import Modal from './Modal.vue'

const props = defineProps({
    show: {
        type: Boolean,
        default: false
    },
    mission: {
        type: Object,
        default: null
    },
    checkers: {
        type: Array,
        default: () => []
    },
    errors: {
        type: Object,
        default: () => ({})
    }
})

const emit = defineEmits(['close', 'assigned'])

const processing = ref(false)

const form = reactive({
    agent_id: null,
    scheduled_date: '',
    scheduled_time: '',
    priority: 3,
    estimated_duration: 60,
    notes: '',
    send_notification: true
})

const minDate = computed(() => {
    return new Date().toISOString().split('T')[0]
})

const timeSlots = computed(() => {
    const slots = []
    for (let hour = 8; hour <= 20; hour++) {
        for (let minute = 0; minute < 60; minute += 30) {
            const time = `${hour.toString().padStart(2, '0')}:${minute.toString().padStart(2, '0')}`
            const label = `${hour.toString().padStart(2, '0')}h${minute.toString().padStart(2, '0')}`
            slots.push({ value: time, label })
        }
    }
    return slots
})

const availableCheckers = computed(() => {
    return props.checkers.filter(checker => checker.is_active !== false)
})

onMounted(() => {
    if (props.mission) {
        // Pre-fill form with mission data
        form.scheduled_date = props.mission.scheduled_at ? 
            new Date(props.mission.scheduled_at).toISOString().split('T')[0] : 
            minDate.value
        form.scheduled_time = props.mission.scheduled_time || ''
        form.priority = props.mission.priority || 3
        form.estimated_duration = props.mission.estimated_duration || 60
    }
})

const formatDate = (dateString) => {
    if (!dateString) return 'Non programmée'
    return new Date(dateString).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    })
}

const selectChecker = (checker) => {
    form.agent_id = checker.id
}

const getAvailabilityClass = (checker) => {
    const activeMissions = checker.active_missions_count || 0
    if (activeMissions === 0) return 'text-green-600'
    if (activeMissions <= 2) return 'text-yellow-600'
    return 'text-red-600'
}

const getAvailabilityText = (checker) => {
    const activeMissions = checker.active_missions_count || 0
    if (activeMissions === 0) return 'Disponible'
    if (activeMissions <= 2) return 'Partiellement disponible'
    return 'Très occupé'
}

const hasConflict = (checker) => {
    // Check if checker has conflicting missions at the same time
    if (!form.scheduled_date || !form.scheduled_time) return false
    
    // This would typically check against the checker's existing missions
    // For now, we'll simulate conflict detection
    return checker.active_missions_count > 3
}

const submitAssignment = async () => {
    processing.value = true
    
    try {
        await router.post(route('missions.assign', props.mission.id), form, {
            onSuccess: () => {
                emit('assigned', {
                    mission: props.mission,
                    checker: availableCheckers.value.find(c => c.id === form.agent_id),
                    assignment_data: { ...form }
                })
                emit('close')
            }
        })
    } catch (error) {
        console.error('Assignment error:', error)
    } finally {
        processing.value = false
    }
}
</script>
</template>