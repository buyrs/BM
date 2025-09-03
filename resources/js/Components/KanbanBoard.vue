<template>
    <div class="kanban-board mobile-scroll">
        <div class="kanban-container" :class="{ 'mobile-view': isMobile }">
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
import { ref, computed, onMounted, onUnmounted } from 'vue'
import KanbanColumn from './KanbanColumn.vue'
import BulkOperationsModal from './BulkOperationsModal.vue'
import { useTouchInteractions } from '@/Composables/useTouchInteractions.js'

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
const isMobile = ref(false)
const kanbanContainer = ref(null)

// Mobile responsiveness
const checkMobile = () => {
  isMobile.value = window.innerWidth < 1024
}

onMounted(() => {
  checkMobile()
  window.addEventListener('resize', checkMobile)
})

onUnmounted(() => {
  window.removeEventListener('resize', checkMobile)
})

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

.kanban-container {
    @apply grid grid-cols-1 lg:grid-cols-4 gap-6 min-h-screen;
}

.kanban-container.mobile-view {
    @apply grid-cols-1 gap-4;
    /* Enable horizontal scrolling on mobile for columns */
    display: flex;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    scroll-snap-type: x mandatory;
    padding-bottom: 1rem;
}

.kanban-container.mobile-view > * {
    flex: 0 0 280px;
    scroll-snap-align: start;
    margin-right: 1rem;
}

.kanban-container.mobile-view > *:last-child {
    margin-right: 0;
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

/* Mobile-specific optimizations */
@media (max-width: 1023px) {
    .kanban-board {
        @apply px-4;
    }
    
    .kanban-container {
        @apply gap-4;
    }
}

/* Touch-friendly scrollbar for mobile */
@media (max-width: 640px) {
    .kanban-container.mobile-view::-webkit-scrollbar {
        height: 4px;
    }
    
    .kanban-container.mobile-view::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 2px;
    }
    
    .kanban-container.mobile-view::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 2px;
    }
    
    .kanban-container.mobile-view::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }
}

/* Reduced motion for performance */
@media (prefers-reduced-motion: reduce) {
    .kanban-item-move,
    .kanban-item-enter-active,
    .kanban-item-leave-active {
        transition: none;
    }
}
</style>