<template>
    <div class="signature-pad-container">
        <div class="mb-4">
            <label class="block text-sm font-medium text-text-secondary">{{ label }}</label>
            <div class="mt-1 border-2 border-gray-200 rounded-md">
                <VueSignaturePad
                    ref="signaturePad"
                    :options="options"
                    class="w-full h-48 bg-white"
                />
            </div>
        </div>
        <div class="flex space-x-4">
            <SecondaryButton
                type="button"
                @click="clear"
            >
                Clear
            </SecondaryButton>
            <PrimaryButton
                type="button"
                @click="save"
            >
                Save Signature
            </PrimaryButton>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import VueSignaturePad from 'vue-signature-pad'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'

const props = defineProps({
    label: {
        type: String,
        required: true
    },
    modelValue: {
        type: String,
        default: ''
    }
})

const emit = defineEmits(['update:modelValue'])

const signaturePad = ref(null)
const options = {
    backgroundColor: 'rgb(255, 255, 255)',
    penColor: 'rgb(0, 0, 0)'
}

const clear = () => {
    signaturePad.value.clearSignature()
    emit('update:modelValue', '')
}

const save = () => {
    if (signaturePad.value.isEmpty()) {
        return
    }
    const { data } = signaturePad.value.saveSignature()
    emit('update:modelValue', data)
}
</script> 