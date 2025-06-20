<template>
    <a
        :href="href"
        class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600"
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