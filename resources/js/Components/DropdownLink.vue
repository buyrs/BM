<template>
    <a
        :href="href"
        class="block px-4 py-2 text-sm text-text-primary hover:bg-secondary hover:text-primary transition-colors duration-200"
        role="menuitem"
        v-bind="$attrs"
    >
        <slot />
    </a>
</template>

<script setup>
defineProps({
    href: {
        type: String,
        required: true,
    },
});
</script> 