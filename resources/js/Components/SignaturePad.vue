<template>
    <div class="signature-container" :class="{ 'fullscreen': isFullscreen }">
        <!-- Header -->
        <div class="signature-header" :class="{ 'fullscreen-header': isFullscreen }">
            <h3 class="text-lg font-semibold text-gray-900">{{ title || label }}</h3>
            <p v-if="instructions" class="text-sm text-gray-600 mt-1">{{ instructions }}</p>
            <button
                v-if="isFullscreen"
                @click="exitFullscreen"
                class="absolute top-4 right-4 p-2 text-gray-500 hover:text-gray-700 bg-white rounded-full shadow-md"
            >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        
        <!-- Signature Pad Wrapper -->
        <div class="signature-pad-wrapper" :class="{ 'fullscreen-pad': isFullscreen }">
            <canvas
                ref="canvas"
                class="signature-canvas"
                :class="{ 'fullscreen-canvas': isFullscreen }"
                @touchstart="handleTouchStart"
                @touchmove="handleTouchMove"
                @touchend="handleTouchEnd"
                @mousedown="handleMouseDown"
                @mousemove="handleMouseMove"
                @mouseup="handleMouseUp"
                @mouseleave="handleMouseUp"
            />
            
            <!-- Signature Guidelines -->
            <div v-if="showGuidelines && !hasSignature" class="signature-guidelines">
                <p class="text-gray-400 text-center">{{ guidelineText }}</p>
            </div>
        </div>
        
        <!-- Preview Section -->
        <div v-if="hasSignature && showPreview" class="signature-preview">
            <h4 class="text-sm font-medium text-gray-700 mb-2">Preview</h4>
            <div class="preview-container">
                <img :src="signatureDataUrl" alt="Signature Preview" class="signature-preview-image" />
            </div>
        </div>
        
        <!-- Actions -->
        <div class="signature-actions" :class="{ 'fullscreen-actions': isFullscreen }">
            <SecondaryButton
                @click="clear"
                :disabled="!hasSignature"
                class="flex-1 sm:flex-none"
            >
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                Clear
            </SecondaryButton>
            
            <SecondaryButton
                v-if="!isFullscreen"
                @click="enterFullscreen"
                class="flex-1 sm:flex-none"
            >
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                </svg>
                Fullscreen
            </SecondaryButton>
            
            <PrimaryButton
                @click="save"
                :disabled="!hasSignature || isEmpty"
                class="flex-1 sm:flex-none"
            >
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                Save Signature
            </PrimaryButton>
        </div>
        
        <!-- Validation Messages -->
        <div v-if="validationMessage" class="signature-validation" :class="validationClass">
            <div class="flex items-center">
                <svg v-if="isValid" class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                <svg v-else class="w-5 h-5 text-red-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>
                <span class="text-sm">{{ validationMessage }}</span>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed, watch, nextTick } from 'vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'

const props = defineProps({
    label: {
        type: String,
        default: 'Signature'
    },
    title: {
        type: String,
        default: ''
    },
    instructions: {
        type: String,
        default: 'Please sign in the area below'
    },
    modelValue: {
        type: String,
        default: ''
    },
    showPreview: {
        type: Boolean,
        default: true
    },
    showGuidelines: {
        type: Boolean,
        default: true
    },
    guidelineText: {
        type: String,
        default: 'Sign here with your finger or stylus'
    },
    penColor: {
        type: String,
        default: '#000000'
    },
    penWidth: {
        type: Number,
        default: 2
    },
    backgroundColor: {
        type: String,
        default: '#ffffff'
    },
    minStrokeLength: {
        type: Number,
        default: 5
    },
    touchSensitivity: {
        type: Number,
        default: 1
    }
})

const emit = defineEmits(['update:modelValue', 'signature-start', 'signature-end', 'validation-change'])

// Refs
const canvas = ref(null)
const isFullscreen = ref(false)
const isDrawing = ref(false)
const hasSignature = ref(false)
const signatureDataUrl = ref('')
const validationMessage = ref('')
const isValid = ref(false)

// Drawing state
let ctx = null
let lastX = 0
let lastY = 0
let strokeLength = 0
let strokes = []
let currentStroke = []

// Touch handling
let lastTouchTime = 0
const touchDelay = 16 // ~60fps

const isEmpty = computed(() => !hasSignature.value || strokes.length === 0)

const validationClass = computed(() => ({
    'signature-validation--success': isValid.value,
    'signature-validation--error': !isValid.value && validationMessage.value
}))

// Initialize canvas
onMounted(() => {
    initializeCanvas()
    window.addEventListener('resize', handleResize)
    
    // Load existing signature if provided
    if (props.modelValue) {
        loadSignature(props.modelValue)
    }
})

onUnmounted(() => {
    window.removeEventListener('resize', handleResize)
    if (isFullscreen.value) {
        exitFullscreen()
    }
})

const initializeCanvas = async () => {
    await nextTick()
    if (!canvas.value) return
    
    ctx = canvas.value.getContext('2d')
    resizeCanvas()
    setupCanvas()
}

const resizeCanvas = () => {
    if (!canvas.value || !ctx) return
    
    const rect = canvas.value.getBoundingClientRect()
    const dpr = window.devicePixelRatio || 1
    
    // Set actual size in memory (scaled to account for extra pixel density)
    canvas.value.width = rect.width * dpr
    canvas.value.height = rect.height * dpr
    
    // Scale the drawing context so everything will work at the higher DPI
    ctx.scale(dpr, dpr)
    
    // Set display size (css pixels)
    canvas.value.style.width = rect.width + 'px'
    canvas.value.style.height = rect.height + 'px'
    
    setupCanvas()
}

const setupCanvas = () => {
    if (!ctx) return
    
    ctx.strokeStyle = props.penColor
    ctx.lineWidth = props.penWidth
    ctx.lineCap = 'round'
    ctx.lineJoin = 'round'
    ctx.fillStyle = props.backgroundColor
    ctx.fillRect(0, 0, canvas.value.width, canvas.value.height)
    
    // Redraw existing strokes
    redrawStrokes()
}

const redrawStrokes = () => {
    if (!ctx || strokes.length === 0) return
    
    strokes.forEach(stroke => {
        if (stroke.length < 2) return
        
        ctx.beginPath()
        ctx.moveTo(stroke[0].x, stroke[0].y)
        
        for (let i = 1; i < stroke.length; i++) {
            ctx.lineTo(stroke[i].x, stroke[i].y)
        }
        
        ctx.stroke()
    })
}

const handleResize = () => {
    if (canvas.value) {
        resizeCanvas()
    }
}

// Touch event handlers with enhanced pressure sensitivity
const handleTouchStart = (event) => {
    event.preventDefault()
    const now = Date.now()
    if (now - lastTouchTime < touchDelay) return
    lastTouchTime = now
    
    const touch = event.touches[0]
    const rect = canvas.value.getBoundingClientRect()
    const dpr = window.devicePixelRatio || 1
    const x = (touch.clientX - rect.left) * dpr * props.touchSensitivity
    const y = (touch.clientY - rect.top) * dpr * props.touchSensitivity
    
    // Enhanced pressure sensitivity for supported devices
    const pressure = touch.force || touch.webkitForce || 1
    const adjustedPenWidth = props.penWidth * (0.5 + pressure * 0.5)
    
    startDrawing(x, y, adjustedPenWidth)
}

const handleTouchMove = (event) => {
    event.preventDefault()
    if (!isDrawing.value) return
    
    const now = Date.now()
    if (now - lastTouchTime < touchDelay) return
    lastTouchTime = now
    
    const touch = event.touches[0]
    const rect = canvas.value.getBoundingClientRect()
    const dpr = window.devicePixelRatio || 1
    const x = (touch.clientX - rect.left) * dpr * props.touchSensitivity
    const y = (touch.clientY - rect.top) * dpr * props.touchSensitivity
    
    // Enhanced pressure sensitivity and smoothing
    const pressure = touch.force || touch.webkitForce || 1
    const adjustedPenWidth = props.penWidth * (0.5 + pressure * 0.5)
    
    // Smooth line interpolation for better touch experience
    const distance = Math.sqrt(Math.pow(x - lastX, 2) + Math.pow(y - lastY, 2))
    if (distance > 2) {
        // Interpolate points for smoother lines
        const steps = Math.ceil(distance / 2)
        for (let i = 1; i <= steps; i++) {
            const t = i / steps
            const interpX = lastX + (x - lastX) * t
            const interpY = lastY + (y - lastY) * t
            draw(interpX, interpY, adjustedPenWidth)
        }
    } else {
        draw(x, y, adjustedPenWidth)
    }
}

const handleTouchEnd = (event) => {
    event.preventDefault()
    stopDrawing()
}

// Mouse event handlers
const handleMouseDown = (event) => {
    const rect = canvas.value.getBoundingClientRect()
    const x = event.clientX - rect.left
    const y = event.clientY - rect.top
    
    startDrawing(x, y)
}

const handleMouseMove = (event) => {
    if (!isDrawing.value) return
    
    const rect = canvas.value.getBoundingClientRect()
    const x = event.clientX - rect.left
    const y = event.clientY - rect.top
    
    draw(x, y)
}

const handleMouseUp = () => {
    stopDrawing()
}

// Drawing functions with pressure support
const startDrawing = (x, y, penWidth = props.penWidth) => {
    isDrawing.value = true
    lastX = x
    lastY = y
    strokeLength = 0
    currentStroke = [{ x, y, pressure: penWidth }]
    
    ctx.beginPath()
    ctx.lineWidth = penWidth
    ctx.moveTo(x, y)
    
    emit('signature-start')
}

const draw = (x, y, penWidth = props.penWidth) => {
    if (!isDrawing.value) return
    
    const distance = Math.sqrt(Math.pow(x - lastX, 2) + Math.pow(y - lastY, 2))
    strokeLength += distance
    
    currentStroke.push({ x, y, pressure: penWidth })
    
    // Apply variable line width for pressure sensitivity
    ctx.lineWidth = penWidth
    ctx.lineTo(x, y)
    ctx.stroke()
    
    lastX = x
    lastY = y
    
    if (!hasSignature.value) {
        hasSignature.value = true
    }
}

const stopDrawing = () => {
    if (!isDrawing.value) return
    
    isDrawing.value = false
    
    // Only add stroke if it meets minimum length requirement
    if (strokeLength >= props.minStrokeLength && currentStroke.length > 1) {
        strokes.push([...currentStroke])
        updateSignature()
        validateSignature()
    }
    
    currentStroke = []
    emit('signature-end')
}

const updateSignature = () => {
    if (!canvas.value) return
    
    signatureDataUrl.value = canvas.value.toDataURL('image/png')
    emit('update:modelValue', signatureDataUrl.value)
}

const validateSignature = () => {
    if (strokes.length === 0) {
        validationMessage.value = ''
        isValid.value = false
        emit('validation-change', { isValid: false, message: '' })
        return
    }
    
    // Basic validation rules
    const totalStrokes = strokes.length
    const totalPoints = strokes.reduce((sum, stroke) => sum + stroke.length, 0)
    
    if (totalStrokes < 1) {
        validationMessage.value = 'Signature is too simple'
        isValid.value = false
    } else if (totalPoints < 10) {
        validationMessage.value = 'Signature is too short'
        isValid.value = false
    } else {
        validationMessage.value = 'Signature looks good'
        isValid.value = true
    }
    
    emit('validation-change', { isValid: isValid.value, message: validationMessage.value })
}

const clear = () => {
    if (!ctx || !canvas.value) return
    
    ctx.clearRect(0, 0, canvas.value.width, canvas.value.height)
    ctx.fillStyle = props.backgroundColor
    ctx.fillRect(0, 0, canvas.value.width, canvas.value.height)
    
    strokes = []
    currentStroke = []
    hasSignature.value = false
    signatureDataUrl.value = ''
    validationMessage.value = ''
    isValid.value = false
    
    emit('update:modelValue', '')
    emit('validation-change', { isValid: false, message: '' })
}

const save = () => {
    if (isEmpty.value || !isValid.value) return
    
    updateSignature()
}

const loadSignature = (dataUrl) => {
    if (!dataUrl || !canvas.value || !ctx) return
    
    const img = new Image()
    img.onload = () => {
        ctx.clearRect(0, 0, canvas.value.width, canvas.value.height)
        ctx.drawImage(img, 0, 0)
        hasSignature.value = true
        signatureDataUrl.value = dataUrl
        validateSignature()
    }
    img.src = dataUrl
}

// Fullscreen functionality
const enterFullscreen = () => {
    isFullscreen.value = true
    document.body.style.overflow = 'hidden'
    
    nextTick(() => {
        resizeCanvas()
    })
}

const exitFullscreen = () => {
    isFullscreen.value = false
    document.body.style.overflow = ''
    
    nextTick(() => {
        resizeCanvas()
    })
}

// Watch for prop changes
watch(() => props.modelValue, (newValue) => {
    if (newValue && newValue !== signatureDataUrl.value) {
        loadSignature(newValue)
    } else if (!newValue && hasSignature.value) {
        clear()
    }
})

watch(() => props.penColor, () => {
    if (ctx) {
        ctx.strokeStyle = props.penColor
    }
})

watch(() => props.penWidth, () => {
    if (ctx) {
        ctx.lineWidth = props.penWidth
    }
})
</script>

<style scoped>
.signature-container {
    @apply relative bg-white border border-gray-200 rounded-lg shadow-sm;
}

.signature-container.fullscreen {
    @apply fixed inset-0 z-50 bg-white flex flex-col;
    height: 100vh;
    height: 100dvh; /* Dynamic viewport height for mobile */
}

.signature-header {
    @apply px-6 py-4 border-b border-gray-200;
}

.signature-header.fullscreen-header {
    @apply relative flex-shrink-0 px-6 py-6 bg-gray-50;
}

.signature-pad-wrapper {
    @apply relative flex-1 min-h-48;
}

.signature-pad-wrapper.fullscreen-pad {
    @apply flex-1 p-4;
}

.signature-canvas {
    @apply w-full h-full cursor-crosshair border-2 border-gray-300 rounded-md bg-white;
    touch-action: none;
    -webkit-touch-callout: none;
    -webkit-user-select: none;
    -khtml-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    user-select: none;
}

.signature-canvas.fullscreen-canvas {
    @apply border-4 border-gray-400 rounded-lg;
    min-height: 60vh;
}

.signature-guidelines {
    @apply absolute inset-0 flex items-center justify-center pointer-events-none;
}

.signature-preview {
    @apply px-6 py-4 border-t border-gray-200 bg-gray-50;
}

.preview-container {
    @apply border border-gray-300 rounded-md p-2 bg-white;
}

.signature-preview-image {
    @apply max-h-16 mx-auto;
}

.signature-actions {
    @apply flex flex-col sm:flex-row gap-3 px-6 py-4 border-t border-gray-200 bg-gray-50;
}

.signature-actions.fullscreen-actions {
    @apply flex-shrink-0 px-6 py-6 bg-white border-t-2 border-gray-300;
}

.signature-validation {
    @apply px-6 py-3 border-t border-gray-200;
}

.signature-validation--success {
    @apply bg-green-50 border-green-200;
}

.signature-validation--error {
    @apply bg-red-50 border-red-200;
}

/* Mobile optimizations */
@media (max-width: 640px) {
    .signature-container.fullscreen .signature-canvas {
        min-height: 50vh;
    }
    
    .signature-actions {
        @apply flex-col gap-2;
    }
    
    .signature-actions button {
        @apply w-full justify-center;
    }
}

/* High DPI displays */
@media (-webkit-min-device-pixel-ratio: 2), (min-resolution: 192dpi) {
    .signature-canvas {
        image-rendering: -webkit-optimize-contrast;
        image-rendering: crisp-edges;
    }
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
    .signature-container {
        @apply bg-gray-800 border-gray-600;
    }
    
    .signature-header {
        @apply border-gray-600 text-white;
    }
    
    .signature-canvas {
        @apply border-gray-500;
    }
    
    .signature-actions {
        @apply bg-gray-700 border-gray-600;
    }
}
</style>