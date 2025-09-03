<template>
    <div 
        class="kanban-column bg-white rounded-xl p-4 shadow-md"
        :class="{ 'drag-over': isDragOver }"
        @dragover.prevent="handleDragOver"
        @dragleave="handleDragLeave"
        @drop="handleDrop"
    >
        <!-- Column Header -->
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-text-primary">{{ title }}</h3>
            <slot name="badge">
                <span class="bg-gray-100 text-gray-600 text-xs font-medium px-2 py-1 rounded-full">
                    {{ items.length }}
                </span>
            </slot>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="space-y-3">
            <div v-for="i in 3" :key="i" class="animate-pulse">
                <div class="bg-gray-200 rounded-lg h-24"></div>
            </div>
        </div>

        <!-- Items -->
        <div v-else class="space-y-3 min-h-32">
            <TransitionGroup name="kanban-item" tag="div" class="space-y-3">
                <div
                    v-for="item in items"
                    :key="item.id"
                    :draggable="true"
                    class="kanban-item cursor-move"
                    @dragstart="handleDragStart($event, item)"
                    @dragend="handleDragEnd"
                    @click="$emit('itemClick', item)"
                >
                    <BailMobiliteCard
                        :bail-mobilite="item"
                        @view-details="$emit('itemClick', $event)"
                        @assign-entry="$emit('itemClick', $event)"
                        @assign-exit="$emit('itemClick', $event)"
                        @handle-incident="$emit('itemClick', $event)"
                    />
                </div>
            </TransitionGroup>

            <!-- Empty State -->
            <div v-if="items.length === 0" class="text-center py-8 text-text-secondary">
                <svg class="w-12 h-12 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <p class="text-sm">Aucun élément</p>
            </div>
        </div>

        <!-- Drop Zone Indicator -->
        <div 
            v-if="isDragOver" 
            class="absolute inset-0 bg-primary bg-opacity-10 border-2 border-dashed border-primary rounded-xl flex items-center justify-center"
        >
            <div class="text-primary font-medium">
                Déposer ici
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import BailMobiliteCard from './BailMobiliteCard.vue'

const props = defineProps({
    title: {
        type: String,
        required: true
    },
    status: {
        type: String,
        required: true
    },
    items: {
        type: Array,
        default: () => []
    },
    loading: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['drop', 'itemClick'])

const isDragOver = ref(false)
const draggedItem = ref(null)

const handleDragStart = (event, item) => {
    draggedItem.value = item
    event.dataTransfer.setData('text/plain', JSON.stringify({
        item,
        fromStatus: props.status
    }))
    event.dataTransfer.effectAllowed = 'move'
    
    // Add visual feedback
    event.target.style.opacity = '0.5'
}

const handleDragEnd = (event) => {
    event.target.style.opacity = '1'
    draggedItem.value = null
}

const handleDragOver = (event) => {
    event.preventDefault()
    event.dataTransfer.dropEffect = 'move'
    isDragOver.value = true
}

const handleDragLeave = (event) => {
    // Only hide drop indicator if leaving the column entirely
    if (!event.currentTarget.contains(event.relatedTarget)) {
        isDragOver.value = false
    }
}

const handleDrop = (event) => {
    event.preventDefault()
    isDragOver.value = false
    
    try {
        const data = JSON.parse(event.dataTransfer.getData('text/plain'))
        const { item, fromStatus } = data
        
        // Don't emit if dropping in the same column
        if (fromStatus === props.status) {
            return
        }
        
        emit('drop', {
            item,
            fromStatus,
            toStatus: props.status
        })
    } catch (error) {
        console.error('Error handling drop:', error)
    }
}
</script>

<style scoped>
.kanban-column {
    position: relative;
    min-height: 400px;
}

.kanban-column.drag-over {
    @apply ring-2 ring-primary ring-opacity-50;
}

.kanban-item {
    transition: all 0.2s ease;
}

.kanban-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.kanban-item:active {
    transform: scale(0.98);
}

/* Transition animations */
.kanban-item-move,
.kanban-item-enter-active,
.kanban-item-leave-active {
    transition: all 0.3s ease;
}

.kanban-item-enter-from,
.kanban-item-leave-to {
    opacity: 0;
    transform: translateY(-10px);
}

.kanban-item-leave-active {
    position: absolute;
    width: 100%;
}
</style>