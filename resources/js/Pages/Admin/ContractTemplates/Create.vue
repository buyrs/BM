<template>
    <Head title="Create Contract Template" />

    <DashboardAdmin>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Create Contract Template</h2>
                <Link :href="route('admin.contract-templates.index')">
                    <button class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                        Back to Templates
                    </button>
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
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
                                <Link :href="route('admin.contract-templates.index')">
                                    <button type="button" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                        Cancel
                                    </button>
                                </Link>
                                <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                    Create Template
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </DashboardAdmin>
</template>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import DashboardAdmin from '@/Layouts/DashboardAdmin.vue'
import InputError from '@/Components/InputError.vue'
import InputLabel from '@/Components/InputLabel.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import TextInput from '@/Components/TextInput.vue'

const form = useForm({
    name: '',
    type: '',
    content: ''
})

const submit = () => {
    form.post(route('admin.contract-templates.store'))
}
</script>