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
        activeClass: 'border-green-500',
        bgClass: 'bg-green-500'
    },
    {
        value: 'good',
        label: 'Good',
        activeClass: 'border-blue-500',
        bgClass: 'bg-blue-500'
    },
    {
        value: 'damaged',
        label: 'Damaged',
        activeClass: 'border-yellow-500',
        bgClass: 'bg-yellow-500'
    },
    {
        value: 'broken',
        label: 'Broken',
        activeClass: 'border-red-500',
        bgClass: 'bg-red-500'
    }
]
</script>