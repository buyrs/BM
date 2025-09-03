<template>
    <div class="flex space-x-2">
        <button
            v-for="condition in conditions"
            :key="condition.value"
            type="button"
            @click="$emit('update:modelValue', condition.value)"
            class="w-12 h-12 rounded-full flex items-center justify-center border-2 transition-colors duration-200"
            :class="[
                modelValue === condition.value ? condition.activeClass : 'border-gray-300',
                'hover:' + condition.activeClass
            ]"
            :title="condition.label"
        >
            <span
                class="w-8 h-8 rounded-full"
                :class="modelValue === condition.value ? condition.bgClass : 'bg-gray-100'"
            ></span>
        </button>
    </div>
</template>

<script setup>
import { defineProps, defineEmits } from 'vue'

const props = defineProps({
    modelValue: String
})

defineEmits(['update:modelValue'])

const conditions = [
    {
        value: 'perfect',
        label: 'Perfect',
        activeClass: 'border-success-border',
        bgClass: 'bg-success-border'
    },
    {
        value: 'good',
        label: 'Good',
        activeClass: 'border-info-border',
        bgClass: 'bg-info-border'
    },
    {
        value: 'damaged',
        label: 'Damaged',
        activeClass: 'border-warning-border',
        bgClass: 'bg-warning-border'
    },
    {
        value: 'broken',
        label: 'Broken',
        activeClass: 'border-error-border',
        bgClass: 'bg-error-border'
    }
]
</script>