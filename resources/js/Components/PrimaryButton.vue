<template>
    <button
        class="inline-flex items-center px-6 py-3 bg-primary border border-transparent rounded-md font-medium text-white uppercase tracking-widest hover:bg-accent focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 transition ease-in-out duration-200"
        :disabled="disabled"
    >
        <slot />
    </button>
</template>

<script setup>
defineProps({
    disabled: {
        type: Boolean,
        default: false,
    },
});
</script>