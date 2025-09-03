<template>
    <Head title="Create Contract Template" />

    <DashboardAdmin>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-text-primary leading-tight">Create Contract Template</h2>
                <Link :href="route('admin.contract-templates.index')">
                    <SecondaryButton>Back to Templates</SecondaryButton>
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-md rounded-lg">
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
                                    class="mt-1 block w-full bg-white border-gray-200 rounded-md shadow-sm p-md focus:ring-2 focus:ring-primary focus:border-transparent"
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
                                        class="block w-full bg-white border-gray-200 rounded-md shadow-sm p-md focus:ring-2 focus:ring-primary focus:border-transparent"
                                        placeholder="Enter the legal content of the contract..."
                                        required
                                    ></textarea>
                                </div>
                                <InputError class="mt-2" :message="form.errors.content" />
                                
                                <!-- Placeholder Help -->
                                <div class="mt-2 text-sm text-text-secondary">
                                    <p class="font-medium">Available placeholders:</p>
                                    <div class="grid grid-cols-2 gap-2 mt-1">
                                        <span class="font-mono text-xs bg-gray-50 px-2 py-1 rounded">{{tenant_name}}</span>
                                        <span class="font-mono text-xs bg-gray-50 px-2 py-1 rounded">{{tenant_email}}</span>
                                        <span class="font-mono text-xs bg-gray-50 px-2 py-1 rounded">{{tenant_phone}}</span>
                                        <span class="font-mono text-xs bg-gray-50 px-2 py-1 rounded">{{address}}</span>
                                        <span class="font-mono text-xs bg-gray-50 px-2 py-1 rounded">{{start_date}}</span>
                                        <span class="font-mono text-xs bg-gray-50 px-2 py-1 rounded">{{end_date}}</span>
                                        <span class="font-mono text-xs bg-gray-50 px-2 py-1 rounded">{{admin_name}}</span>
                                        <span class="font-mono text-xs bg-gray-50 px-2 py-1 rounded">{{admin_signature_date}}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center justify-end space-x-4">
                                <Link :href="route('admin.contract-templates.index')">
                                    <SecondaryButton type="button">Cancel</SecondaryButton>
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
import SecondaryButton from '@/Components/SecondaryButton.vue'
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