<template>
    <Link :href="href" :class="classes">
        <slot />
    </Link>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    href: {
        type: String,
        required: true,
    },
    active: {
        type: Boolean,
    },
});

const classes = computed(() =>
    props.active
        ? 'block w-full pl-3 pr-4 py-2 border-l-4 border-primary text-left text-base font-medium text-primary bg-secondary focus:outline-none focus:text-accent focus:bg-secondary focus:border-accent transition duration-200 ease-in-out'
        : 'block w-full pl-3 pr-4 py-2 border-l-4 border-transparent text-left text-base font-medium text-text-secondary hover:text-primary hover:bg-secondary hover:border-secondary focus:outline-none focus:text-primary focus:bg-secondary focus:border-secondary transition duration-200 ease-in-out'
);
</script> 