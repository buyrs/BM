@props([
    'signatureType' => 'tenant', // 'tenant' or 'agent'
    'existingSignature' => null,
    'required' => false,
    'title' => null,
    'instructions' => null,
    'showGuidelines' => true,
    'showPreview' => true
])

<div 
    x-data="signaturePad({
        signatureType: '{{ $signatureType }}',
        existingSignature: @js($existingSignature),
        required: {{ $required ? 'true' : 'false' }},
        title: '{{ $title }}',
        instructions: '{{ $instructions }}',
        showGuidelines: {{ $showGuidelines ? 'true' : 'false' }},
        showPreview: {{ $showPreview ? 'true' : 'false' }}
    })"
    class="signature-container"
    :class="{ 'fullscreen': isFullscreen }"
>
    <!-- Header -->
    <div class="signature-header" :class="{ 'fullscreen-header': isFullscreen }">
        <h3 class="text-lg font-semibold text-gray-900" x-text="title || getDefaultTitle()"></h3>
        <p x-show="instructions" class="text-sm text-gray-600 mt-1" x-text="instructions"></p>
        <button
            x-show="isFullscreen"
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
            x-ref="canvas"
            class="signature-canvas"
            :class="{ 'fullscreen-canvas': isFullscreen }"
            @touchstart="handleTouchStart"
            @touchmove="handleTouchMove"
            @touchend="handleTouchEnd"
            @mousedown="handleMouseDown"
            @mousemove="handleMouseMove"
            @mouseup="handleMouseUp"
            @mouseleave="handleMouseUp"
        ></canvas>
        
        <!-- Signature Guidelines -->
        <div x-show="showGuidelines && !hasSignature" class="signature-guidelines">
            <p class="text-gray-400 text-center" x-text="getGuidelineText()"></p>
        </div>
    </div>
    
    <!-- Preview Section -->
    <div x-show="hasSignature && showPreview" class="signature-preview">
        <h4 class="text-sm font-medium text-gray-700 mb-2">Aperçu</h4>
        <div class="preview-container">
            <img :src="signatureDataUrl" alt="Aperçu de la signature" class="signature-preview-image" />
        </div>
    </div>
    
    <!-- Actions -->
    <div class="signature-actions" :class="{ 'fullscreen-actions': isFullscreen }">
        <button
            type="button"
            @click="clear"
            :disabled="!hasSignature"
            class="flex-1 sm:flex-none px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
        >
            <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
            Effacer
        </button>
        
        <button
            x-show="!isFullscreen"
            type="button"
            @click="enterFullscreen"
            class="flex-1 sm:flex-none px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
        >
            <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
            </svg>
            Plein écran
        </button>
    </div>
    
    <!-- Error Message -->
    <div x-show="hasError" class="text-sm text-red-600 mt-2" x-text="errorMessage"></div>
    
    <!-- Required Indicator -->
    <div x-show="required && !hasSignature" class="text-sm text-red-500 mt-2">
        * Signature requise
    </div>
</div>

<style>
.signature-container {
    @apply border border-gray-200 rounded-lg p-4 bg-white;
}

.signature-container.fullscreen {
    @apply fixed inset-0 z-50 bg-white;
}

.signature-header {
    @apply relative mb-4;
}

.signature-header.fullscreen-header {
    @apply p-6 border-b border-gray-200;
}

.signature-pad-wrapper {
    @apply relative border border-gray-300 rounded-lg bg-white;
    height: 200px;
}

.signature-pad-wrapper.fullscreen-pad {
    @apply mx-6;
    height: calc(100vh - 200px);
}

.signature-canvas {
    @apply w-full h-full cursor-crosshair;
    touch-action: none;
}

.signature-canvas.fullscreen-canvas {
    @apply rounded-lg;
}

.signature-guidelines {
    @apply absolute inset-0 flex items-center justify-center pointer-events-none;
}

.signature-preview {
    @apply mt-4 p-4 border border-gray-200 rounded-lg bg-gray-50;
}

.preview-container {
    @apply flex justify-center;
}

.signature-preview-image {
    @apply max-w-full max-h-32 border border-gray-300 rounded;
}

.signature-actions {
    @apply flex flex-col sm:flex-row gap-2 mt-4;
}

.signature-actions.fullscreen-actions {
    @apply mx-6;
}
</style>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('signaturePad', (config) => ({
        signatureType: config.signatureType,
        existingSignature: config.existingSignature,
        required: config.required,
        title: config.title,
        instructions: config.instructions,
        showGuidelines: config.showGuidelines,
        showPreview: config.showPreview,
        
        // State
        canvas: null,
        ctx: null,
        isDrawing: false,
        hasSignature: false,
        signatureDataUrl: null,
        isFullscreen: false,
        hasError: false,
        errorMessage: '',
        
        // Drawing state
        lastX: 0,
        lastY: 0,
        
        init() {
            this.$nextTick(() => {
                this.initializeCanvas();
                this.loadExistingSignature();
                this.setupResizeListener();
            });
        },
        
        initializeCanvas() {
            this.canvas = this.$refs.canvas;
            this.ctx = this.canvas.getContext('2d');
            
            // Set canvas size
            this.resizeCanvas();
            
            // Set drawing properties
            this.ctx.strokeStyle = '#000000';
            this.ctx.lineWidth = 2;
            this.ctx.lineCap = 'round';
            this.ctx.lineJoin = 'round';
            
            // Clear canvas
            this.clearCanvas();
        },
        
        resizeCanvas() {
            const rect = this.canvas.getBoundingClientRect();
            const dpr = window.devicePixelRatio || 1;
            
            this.canvas.width = rect.width * dpr;
            this.canvas.height = rect.height * dpr;
            
            this.ctx.scale(dpr, dpr);
            this.canvas.style.width = rect.width + 'px';
            this.canvas.style.height = rect.height + 'px';
        },
        
        setupResizeListener() {
            window.addEventListener('resize', () => {
                this.resizeCanvas();
            });
        },
        
        loadExistingSignature() {
            if (this.existingSignature) {
                const img = new Image();
                img.onload = () => {
                    this.ctx.drawImage(img, 0, 0, this.canvas.width, this.canvas.height);
                    this.hasSignature = true;
                    this.signatureDataUrl = this.canvas.toDataURL();
                    this.emitSignatureChange();
                };
                img.src = this.existingSignature;
            }
        },
        
        handleMouseDown(e) {
            this.startDrawing(e.clientX, e.clientY);
        },
        
        handleMouseMove(e) {
            if (this.isDrawing) {
                this.draw(e.clientX, e.clientY);
            }
        },
        
        handleMouseUp() {
            this.stopDrawing();
        },
        
        handleTouchStart(e) {
            e.preventDefault();
            const touch = e.touches[0];
            const rect = this.canvas.getBoundingClientRect();
            this.startDrawing(touch.clientX - rect.left, touch.clientY - rect.top);
        },
        
        handleTouchMove(e) {
            e.preventDefault();
            if (this.isDrawing) {
                const touch = e.touches[0];
                const rect = this.canvas.getBoundingClientRect();
                this.draw(touch.clientX - rect.left, touch.clientY - rect.top);
            }
        },
        
        handleTouchEnd(e) {
            e.preventDefault();
            this.stopDrawing();
        },
        
        startDrawing(x, y) {
            this.isDrawing = true;
            this.lastX = x;
            this.lastY = y;
            this.hasError = false;
            this.errorMessage = '';
        },
        
        draw(x, y) {
            if (!this.isDrawing) return;
            
            this.ctx.beginPath();
            this.ctx.moveTo(this.lastX, this.lastY);
            this.ctx.lineTo(x, y);
            this.ctx.stroke();
            
            this.lastX = x;
            this.lastY = y;
            
            // Mark as having signature
            if (!this.hasSignature) {
                this.hasSignature = true;
                this.signatureDataUrl = this.canvas.toDataURL();
                this.emitSignatureChange();
            }
        },
        
        stopDrawing() {
            if (this.isDrawing) {
                this.isDrawing = false;
                this.signatureDataUrl = this.canvas.toDataURL();
                this.emitSignatureChange();
            }
        },
        
        clear() {
            this.clearCanvas();
            this.hasSignature = false;
            this.signatureDataUrl = null;
            this.emitSignatureChange();
        },
        
        clearCanvas() {
            this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
        },
        
        enterFullscreen() {
            this.isFullscreen = true;
            document.body.style.overflow = 'hidden';
            this.$nextTick(() => {
                this.resizeCanvas();
            });
        },
        
        exitFullscreen() {
            this.isFullscreen = false;
            document.body.style.overflow = '';
            this.$nextTick(() => {
                this.resizeCanvas();
            });
        },
        
        getDefaultTitle() {
            return this.signatureType === 'tenant' ? 'Signature du Locataire' : 'Signature de l\'Agent';
        },
        
        getGuidelineText() {
            return 'Signez dans la zone ci-dessus';
        },
        
        emitSignatureChange() {
            this.$dispatch('signature-changed', {
                signatureType: this.signatureType,
                signatureData: this.signatureDataUrl,
                hasSignature: this.hasSignature
            });
        },
        
        validate() {
            if (this.required && !this.hasSignature) {
                this.hasError = true;
                this.errorMessage = 'Signature requise';
                return false;
            }
            
            this.hasError = false;
            this.errorMessage = '';
            return true;
        }
    }));
});
</script>
