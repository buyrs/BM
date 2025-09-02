<template>
    <TransitionRoot appear :show="show" as="template">
        <Dialog as="div" @close="close" class="relative z-10">
            <TransitionChild
                as="template"
                enter="duration-300 ease-out"
                enter-from="opacity-0"
                enter-to="opacity-100"
                leave="duration-200 ease-in"
                leave-from="opacity-100"
                leave-to="opacity-0"
            >
                <div class="fixed inset-0 bg-black bg-opacity-25" />
            </TransitionChild>

            <div class="fixed inset-0 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4 text-center">
                    <TransitionChild
                        as="template"
                        enter="duration-300 ease-out"
                        enter-from="opacity-0 scale-95"
                        enter-to="opacity-100 scale-100"
                        leave="duration-200 ease-in"
                        leave-from="opacity-100 scale-100"
                        leave-to="opacity-0 scale-95"
                    >
                        <DialogPanel :class="[
                            'w-full transform overflow-hidden rounded-2xl bg-white text-left align-middle shadow-xl transition-all',
                            maxWidth === 'sm' ? 'max-w-sm' : '',
                            maxWidth === 'md' ? 'max-w-md' : '',
                            maxWidth === 'lg' ? 'max-w-lg' : '',
                            maxWidth === 'xl' ? 'max-w-xl' : '',
                            maxWidth === '2xl' ? 'max-w-2xl' : '',
                            maxWidth === '3xl' ? 'max-w-3xl' : '',
                            maxWidth === '4xl' ? 'max-w-4xl' : '',
                            maxWidth === '5xl' ? 'max-w-5xl' : '',
                            maxWidth === '6xl' ? 'max-w-6xl' : '',
                            maxWidth === '7xl' ? 'max-w-7xl' : ''
                        ]">
                            <slot></slot>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>
</template>

<script setup>
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'

const props = defineProps({
    show: {
        type: Boolean,
        required: true
    },
    maxWidth: {
        type: String,
        default: 'md'
    }
})

const emit = defineEmits(['close'])

const close = () => {
    emit('close')
}
</script> 