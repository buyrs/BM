<template>
    <input
        class="bg-white border-gray-200 rounded-md shadow-sm p-md focus:ring-2 focus:ring-primary focus:border-transparent"
        :value="modelValue"
        @input="$emit('update:modelValue', $event.target.value)"
        ref="input"
    >
</template>

<script setup>
import { onMounted, ref } from 'vue';

defineProps({
    modelValue: {
        type: String,
        default: '',
    },
});

defineEmits(['update:modelValue']);

const input = ref(null);

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>