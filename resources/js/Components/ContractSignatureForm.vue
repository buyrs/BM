<template>
    <div class="space-y-6">
        <!-- Enhanced Contract Signature Flow -->
        <ContractSignatureFlow
            :bail-mobilite="bailMobilite"
            :contract-template="contractTemplate"
            :signature-type="signatureType"
            :mission="mission"
            @completed="handleSignatureCompleted"
            @cancel="$emit('cancelled')"
        />
    </div>
</template>

<script setup>
import ContractSignatureFlow from './ContractSignatureFlow.vue'

const props = defineProps({
    mission: {
        type: Object,
        required: true
    },
    bailMobilite: {
        type: Object,
        required: true
    },
    contractTemplate: {
        type: Object,
        required: true
    },
    signatureType: {
        type: String,
        required: true,
        validator: value => ['entry', 'exit'].includes(value)
    }
})

const emit = defineEmits(['signed', 'cancelled'])

const handleSignatureCompleted = (signatureResult) => {
    emit('signed', signatureResult)
}
</script>