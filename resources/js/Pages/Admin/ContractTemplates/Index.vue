<template>
    <Head title="Contract Templates" />

    <DashboardAdmin>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-text-primary leading-tight">Contract Templates</h2>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Success/Error Messages -->
                <div v-if="$page.props.flash.success" class="mb-6 bg-success-bg border border-success-border text-success-text px-4 py-3 rounded">
                    {{ $page.props.flash.success }}
                </div>
                <div v-if="$page.props.flash.error" class="mb-6 bg-error-bg border border-error-border text-error-text px-4 py-3 rounded">
                    {{ $page.props.flash.error }}
                </div>

                <!-- Enhanced Contract Template Manager -->
                <ContractTemplateManager :templates="templates.data" />
            </div>
        </div>
    </DashboardAdmin>
</template>

<script setup>
import { Head } from '@inertiajs/vue3'
import DashboardAdmin from '@/Layouts/DashboardAdmin.vue'
import ContractTemplateManager from '@/Components/ContractTemplateManager.vue'

const props = defineProps({
    templates: {
        type: Object,
        required: true
    }
})
</script>