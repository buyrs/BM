<template>
    <a
        :href="href"
        :class="[
            'inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium focus:outline-none transition-colors duration-200',
            active
                ? 'border-primary text-primary'
                : 'border-transparent text-text-secondary hover:text-primary hover:border-secondary'
        ]"
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
    active: {
        type: Boolean,
        default: false,
    },
});
</script> 