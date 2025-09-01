<template>
    <Head title="Edit Contract Template" />

    <DashboardAdmin>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Contract Template</h2>
                <div class="flex space-x-2">
                    <Link :href="route('admin.contract-templates.show', template.id)">
                        <button class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                            View Template
                        </button>
                    </Link>
                    <Link :href="route('admin.contract-templates.index')">
                        <button class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                            Back to Templates
                        </button>
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <!-- Warning for signed templates -->
                <div v-if="template.admin_signed_at" class="mb-6 bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded">
                    <div class="flex">
                        <svg class="w-5 h-5 text-yellow-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                        <div>
                            <p class="font-medium">This template has been signed and cannot be edited.</p>
                            <p class="text-sm">Create a new version if you need to make changes.</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <form @submit.prevent="submit">
                            <!-- Template Name -->
                            <div class="mb-6">
                                <InputLabel for="name" value="Template Name" />
                                <TextInput
                                    id="name"
                                    v-model="form.name"
                                    type="text"
                                    class="mt-1 block w-full"
                                    placeholder="e.g., Standard Entry Contract 2024"
                                    required
                                    autofocus
                                    :disabled="template.admin_signed_at"
                                />
                                <InputError class="mt-2" :message="form.errors.name" />
                            </div>

                            <!-- Template Type -->
                            <div class="mb-6">
                                <InputLabel for="type" value="Contract Type" />
                                <select
                                    id="type"
                                    v-model="form.type"
                                    class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                    required
                                    :disabled="template.admin_signed_at"
                                >
                                    <option value="">Select contract type</option>
                                    <option value="entry">Entry Contract</option>
                                    <option value="exit">Exit Contract</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.type" />
                            </div>

                            <!-- Contract Content -->
                            <div class="mb-6">
                                <InputLabel for="content" value="Contract Content" />
                                <div class="mt-1">
                                    <textarea
                                        id="content"
                                        v-model="form.content"
                                        rows="15"
                                        class="block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                        placeholder="Enter the legal content of the contract..."
                                        required
                                        :disabled="template.admin_signed_at"
                                    ></textarea>
                                </div>
                                <InputError class="mt-2" :message="form.errors.content" />
                                
                                <!-- Placeholder Help -->
                                <div class="mt-2 text-sm text-gray-600">
                                    <p class="font-medium">Available placeholders:</p>
                                    <div class="grid grid-cols-2 gap-2 mt-1">
                                        <span class="font-mono text-xs bg-gray-100 px-2 py-1 rounded">{{tenant_name}}</span>
                                        <span class="font-mono text-xs bg-gray-100 px-2 py-1 rounded">{{tenant_email}}</span>
                                        <span class="font-mono text-xs bg-gray-100 px-2 py-1 rounded">{{tenant_phone}}</span>
                                        <span class="font-mono text-xs bg-gray-100 px-2 py-1 rounded">{{address}}</span>
                                        <span class="font-mono text-xs bg-gray-100 px-2 py-1 rounded">{{start_date}}</span>
                                        <span class="font-mono text-xs bg-gray-100 px-2 py-1 rounded">{{end_date}}</span>
                                        <span class="font-mono text-xs bg-gray-100 px-2 py-1 rounded">{{admin_name}}</span>
                                        <span class="font-mono text-xs bg-gray-100 px-2 py-1 rounded">{{admin_signature_date}}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center justify-end space-x-4">
                                <Link :href="route('admin.contract-templates.show', template.id)">
                                    <button type="button" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                        Cancel
                                    </button>
                                </Link>
                                <PrimaryButton 
                                    v-if="!template.admin_signed_at"
                                    :class="{ 'opacity-25': form.processing }" 
                                    :disabled="form.processing"
                                >
                                    Update Template
                                </PrimaryButton>
                                <button 
                                    v-else
                                    @click="createVersion"
                                    type="button"
                                    class="px-4 py-2 text-sm font-medium text-white bg-green-600 border border-transparent rounded-md hover:bg-green-700"
                                >
                                    Create New Version
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </DashboardAdmin>
</template>

<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import DashboardAdmin from '@/Layouts/DashboardAdmin.vue'
import InputError from '@/Components/InputError.vue'
import InputLabel from '@/Components/InputLabel.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import TextInput from '@/Components/TextInput.vue'

const props = defineProps({
    template: {
        type: Object,
        required: true
    }
})

const form = useForm({
    name: props.template.name,
    type: props.template.type,
    content: props.template.content
})

const submit = () => {
    if (props.template.admin_signed_at) {
        return // Prevent submission for signed templates
    }
    
    form.patch(route('admin.contract-templates.update', props.template.id))
}

const createVersion = () => {
    if (confirm('Create a new version of this contract template? The current version will be deactivated.')) {
        router.post(route('admin.contract-templates.create-version', props.template.id))
    }
}
</script>