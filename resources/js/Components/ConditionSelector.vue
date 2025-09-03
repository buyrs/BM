<template>
    <div class="space-y-2">
        <select
            :value="modelValue"
            @input="$emit('update:modelValue', $event.target.value)"
            @change="$emit('change', $event.target.value)"
            :class="[
                'block w-full border rounded-md shadow-sm focus:ring-primary focus:border-primary',
                getValidationClass()
            ]"
            :required="required"
        >
            <option value="">Sélectionner l'état</option>
            <option
                v-for="condition in conditions"
                :key="condition.value"
                :value="condition.value"
                :class="condition.class"
            >
                {{ condition.label }}
            </option>
        </select>
        
        <!-- Visual indicator -->
        <div v-if="modelValue" class="flex items-center space-x-2">
            <div :class="getIndicatorClass()" class="w-3 h-3 rounded-full"></div>
            <span class="text-xs text-gray-600">{{ getConditionDescription() }}</span>
        </div>
        
        <!-- Validation error -->
        <div v-if="required && !modelValue && showValidation" class="text-red-600 text-xs">
            Ce champ est obligatoire
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    modelValue: {
        type: String,
        default: ''
    },
    itemType: {
        type: String,
        default: 'general'
    },
    required: {
        type: Boolean,
        default: false
    },
    showValidation: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['update:modelValue', 'change'])

const conditions = computed(() => {
    const baseConditions = [
        {
            value: 'excellent',
            label: 'Excellent',
            class: 'text-green-700',
            description: 'État parfait, aucun défaut visible'
        },
        {
            value: 'good',
            label: 'Bon',
            class: 'text-green-600',
            description: 'État satisfaisant, usure normale'
        },
        {
            value: 'fair',
            label: 'Correct',
            class: 'text-yellow-600',
            description: 'État acceptable, quelques défauts mineurs'
        },
        {
            value: 'poor',
            label: 'Mauvais',
            class: 'text-orange-600',
            description: 'État dégradé, nécessite attention'
        },
        {
            value: 'damaged',
            label: 'Endommagé',
            class: 'text-red-600',
            description: 'Dommages visibles, réparation nécessaire'
        }
    ]

    // Add specific conditions based on item type
    if (props.itemType === 'electrical') {
        baseConditions.push({
            value: 'not_working',
            label: 'Hors service',
            class: 'text-red-700',
            description: 'Ne fonctionne pas'
        })
    }

    if (props.itemType === 'plumbing') {
        baseConditions.push({
            value: 'leaking',
            label: 'Fuite',
            class: 'text-red-700',
            description: 'Présence de fuite'
        })
    }

    return baseConditions
})

const getValidationClass = () => {
    if (!props.required) return 'border-gray-300'
    
    if (props.showValidation) {
        return props.modelValue ? 'border-green-300' : 'border-red-300'
    }
    
    return 'border-gray-300'
}

const getIndicatorClass = () => {
    const classes = {
        excellent: 'bg-green-500',
        good: 'bg-green-400',
        fair: 'bg-yellow-400',
        poor: 'bg-orange-400',
        damaged: 'bg-red-500',
        not_working: 'bg-red-600',
        leaking: 'bg-red-600'
    }
    return classes[props.modelValue] || 'bg-gray-400'
}

const getConditionDescription = () => {
    const condition = conditions.value.find(c => c.value === props.modelValue)
    return condition?.description || ''
}
</script>
</template>