<template>
    <Modal :show="true" @close="$emit('close')">
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-text-primary">
                    Opérations en lot
                </h3>
                <button
                    @click="$emit('close')"
                    class="text-text-secondary hover:text-text-primary"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="mb-4">
                <p class="text-sm text-text-secondary">
                    {{ selectedItems.length }} élément(s) sélectionné(s)
                </p>
            </div>

            <!-- Selected Items Preview -->
            <div class="max-h-32 overflow-y-auto mb-6 border border-gray-200 rounded-lg">
                <div class="divide-y divide-gray-200">
                    <div
                        v-for="item in selectedItems"
                        :key="item.id"
                        class="p-2 text-sm"
                    >
                        <span class="font-medium">{{ item.tenant_name }}</span>
                        <span class="text-text-secondary ml-2">{{ item.address }}</span>
                    </div>
                </div>
            </div>

            <!-- Bulk Actions -->
            <div class="space-y-3">
                <h4 class="font-medium text-text-primary">Actions disponibles:</h4>
                
                <!-- Status Change -->
                <div class="flex items-center space-x-3">
                    <select
                        v-model="selectedStatus"
                        class="flex-1 border border-gray-300 rounded-md px-3 py-2 text-sm"
                    >
                        <option value="">Changer le statut vers...</option>
                        <option value="assigned">Assigné</option>
                        <option value="in_progress">En Cours</option>
                        <option value="completed">Terminé</option>
                        <option value="incident">Incident</option>
                    </select>
                    <SecondaryButton
                        @click="handleBulkAction('change_status')"
                        :disabled="!selectedStatus"
                    >
                        Appliquer
                    </SecondaryButton>
                </div>

                <!-- Assign Checker -->
                <div class="flex items-center space-x-3">
                    <select
                        v-model="selectedChecker"
                        class="flex-1 border border-gray-300 rounded-md px-3 py-2 text-sm"
                    >
                        <option value="">Assigner à un checker...</option>
                        <option
                            v-for="checker in checkers"
                            :key="checker.id"
                            :value="checker.id"
                        >
                            {{ checker.name }}
                        </option>
                    </select>
                    <SecondaryButton
                        @click="handleBulkAction('assign_checker')"
                        :disabled="!selectedChecker"
                    >
                        Assigner
                    </SecondaryButton>
                </div>

                <!-- Export -->
                <div class="flex items-center space-x-3">
                    <span class="flex-1 text-sm text-text-secondary">
                        Exporter les éléments sélectionnés
                    </span>
                    <SecondaryButton @click="handleBulkAction('export_csv')">
                        CSV
                    </SecondaryButton>
                    <SecondaryButton @click="handleBulkAction('export_json')">
                        JSON
                    </SecondaryButton>
                </div>

                <!-- Delete (if applicable) -->
                <div class="flex items-center space-x-3 pt-3 border-t border-gray-200">
                    <span class="flex-1 text-sm text-error-text">
                        Supprimer les éléments sélectionnés (irréversible)
                    </span>
                    <button
                        @click="handleBulkAction('delete')"
                        class="px-4 py-2 bg-error-border text-white text-sm rounded-md hover:bg-error-text focus:outline-none focus:ring-2 focus:ring-error-border"
                    >
                        Supprimer
                    </button>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex justify-end space-x-3 mt-6 pt-4 border-t border-gray-200">
                <SecondaryButton @click="$emit('close')">
                    Annuler
                </SecondaryButton>
            </div>
        </div>
    </Modal>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import Modal from './Modal.vue'
import SecondaryButton from './SecondaryButton.vue'

const props = defineProps({
    selectedItems: {
        type: Array,
        default: () => []
    }
})

const emit = defineEmits(['close', 'bulkAction'])

const selectedStatus = ref('')
const selectedChecker = ref('')
const checkers = ref([])

const handleBulkAction = (action) => {
    const payload = {
        action,
        items: props.selectedItems.map(item => item.id)
    }

    // Add specific data based on action
    switch (action) {
        case 'change_status':
            payload.status = selectedStatus.value
            break
        case 'assign_checker':
            payload.checker_id = selectedChecker.value
            break
    }

    emit('bulkAction', payload)
}

const loadCheckers = async () => {
    try {
        const response = await fetch(route('ops.api.checkers'))
        checkers.value = await response.json()
    } catch (error) {
        console.error('Error loading checkers:', error)
    }
}

onMounted(() => {
    loadCheckers()
})
</script>