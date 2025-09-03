<template>
    <div class="kanban-board">
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 min-h-screen">
            <!-- Assigned Column -->
            <KanbanColumn
                title="Assigné"
                status="assigned"
                :items="items.assigned || []"
                :loading="loading"
                @drop="handleDrop"
                @item-click="$emit('itemClick', $event)"
            >
                <template #badge>
                    <span class="bg-warning-bg text-warning-text text-xs font-medium px-2 py-1 rounded-full">
                        {{ items.assigned?.length || 0 }}
                    </span>
                </template>
            </KanbanColumn>

            <!-- In Progress Column -->
            <KanbanColumn
                title="En Cours"
                status="in_progress"
                :items="items.in_progress || []"
                :loading="loading"
                @drop="handleDrop"
                @item-click="$emit('itemClick', $event)"
            >
                <template #badge>
                    <span class="bg-info-bg text-info-text text-xs font-medium px-2 py-1 rounded-full">
                        {{ items.in_progress?.length || 0 }}
                    </span>
                </template>
            </KanbanColumn>

            <!-- Completed Column -->
            <KanbanColumn
                title="Terminé"
                status="completed"
                :items="items.completed || []"
                :loading="loading"
                @drop="handleDrop"
                @item-click="$emit('itemClick', $event)"
            >
                <template #badge>
                    <span class="bg-success-bg text-success-text text-xs font-medium px-2 py-1 rounded-full">
                        {{ items.completed?.length || 0 }}
                    </span>
                </template>
            </KanbanColumn>

            <!-- Incident Column -->
            <KanbanColumn
                title="Incident"
                status="incident"
                :items="items.incident || []"
                :loading="loading"
                @drop="handleDrop"
                @item-click="$emit('itemClick', $event)"
            >
                <template #badge>
                    <span class="bg-error-bg text-error-text text-xs font-medium px-2 py-1 rounded-full">
                        {{ items.incident?.length || 0 }}
                    </span>
                </template>
            </KanbanColumn>
        </div>

        <!-- Bulk Operations Modal -->
        <BulkOperationsModal
            v-if="showBulkModal"
            :selected-items="selectedItems"
            @close="closeBulkModal"
            @bulk-action="handleBulkAction"
        />
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import KanbanColumn from './KanbanColumn.vue'
import BulkOperationsModal from './BulkOperationsModal.vue'

const props = defineProps({
    items: {
        type: Object,
        default: () => ({
            assigned: [],
            in_progress: [],
            completed: [],
            incident: []
        })
    },
    loading: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['drop', 'itemClick', 'bulkAction'])

const selectedItems = ref([])
const showBulkModal = ref(false)

const handleDrop = async (event) => {
    const { item, fromStatus, toStatus } = event
    
    // Validate status transition
    if (!isValidTransition(fromStatus, toStatus)) {
        console.warn(`Invalid transition from ${fromStatus} to ${toStatus}`)
        return
    }
    
    emit('drop', event)
}

const isValidTransition = (from, to) => {
    const validTransitions = {
        assigned: ['in_progress', 'incident'],
        in_progress: ['completed', 'incident'],
        completed: ['incident'], // Can reopen if incident found
        incident: ['assigned', 'in_progress'] // Can be reassigned after resolution
    }
    
    return validTransitions[from]?.includes(to) || false
}

const handleBulkAction = (action) => {
    emit('bulkAction', {
        action,
        items: selectedItems.value
    })
    closeBulkModal()
}

const closeBulkModal = () => {
    showBulkModal.value = false
    selectedItems.value = []
}

// Expose methods for parent component
defineExpose({
    selectItems: (items) => {
        selectedItems.value = items
        showBulkModal.value = true
    }
})
</script>

<style scoped>
.kanban-board {
    @apply w-full;
}

/* Smooth transitions for drag and drop */
.kanban-item-move {
    transition: transform 0.3s ease;
}

.kanban-item-enter-active,
.kanban-item-leave-active {
    transition: all 0.3s ease;
}

.kanban-item-enter-from,
.kanban-item-leave-to {
    opacity: 0;
    transform: translateY(-10px);
}
</style>