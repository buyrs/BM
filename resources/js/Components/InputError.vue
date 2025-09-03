<template>
    <div v-show="message">
        <p class="text-sm text-error-text">{{ message }}</p>
    </div>
</template>

<script setup>
defineProps({
    message: {
        type: String,
    },
});
</script>