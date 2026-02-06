@props([
    'maxFiles' => 10,
    'maxSizeMb' => 5,
    'acceptedTypes' => 'image/*',
    'endpoint' => '/api/v1/files',
    'compressionQuality' => 0.8,
    'missionId' => null,
    'checklistItemId' => null,
])

<!-- Bulk Photo Upload Component -->
<div 
    x-data="bulkPhotoUpload({ 
        maxFiles: {{ $maxFiles }},
        maxSizeMb: {{ $maxSizeMb }},
        endpoint: '{{ $endpoint }}',
        compressionQuality: {{ $compressionQuality }},
        missionId: {{ $missionId ?? 'null' }},
        checklistItemId: {{ $checklistItemId ?? 'null' }}
    })"
    {{ $attributes->merge(['class' => 'relative']) }}
>
    <!-- Drop Zone -->
    <div 
        @dragenter.prevent="dragOver = true"
        @dragover.prevent="dragOver = true"
        @dragleave.prevent="dragOver = false"
        @drop.prevent="handleDrop($event)"
        @click="$refs.fileInput.click()"
        class="relative border-2 border-dashed rounded-xl transition-all duration-300 cursor-pointer group"
        :class="{
            'border-primary-500 bg-primary-50 dark:bg-primary-900/20': dragOver,
            'border-secondary-300 dark:border-secondary-600 hover:border-primary-400 dark:hover:border-primary-500': !dragOver
        }"
    >
        <input 
            type="file"
            x-ref="fileInput"
            @change="handleFileSelect($event)"
            :accept="'{{ $acceptedTypes }}'"
            multiple
            class="hidden"
        >

        <!-- Empty State -->
        <div x-show="files.length === 0" class="p-8 text-center">
            <div class="mx-auto w-12 h-12 rounded-full bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                <svg class="w-6 h-6 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
            <p class="text-secondary-600 dark:text-secondary-400 font-medium">
                Drop photos here or tap to select
            </p>
            <p class="text-sm text-secondary-500 dark:text-secondary-500 mt-1">
                Up to {{ $maxFiles }} photos, {{ $maxSizeMb }}MB each
            </p>
        </div>

        <!-- Drag Over Overlay -->
        <div 
            x-show="dragOver"
            x-transition
            class="absolute inset-0 flex items-center justify-center bg-primary-500/10 rounded-xl"
        >
            <div class="text-primary-600 dark:text-primary-400 font-medium">
                Drop photos here
            </div>
        </div>
    </div>

    <!-- File Preview Grid -->
    <div 
        x-show="files.length > 0"
        x-transition
        class="mt-4 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3"
    >
        <template x-for="(file, index) in files" :key="file.id">
            <div class="relative group aspect-square rounded-lg overflow-hidden bg-secondary-100 dark:bg-secondary-800">
                <!-- Image Preview -->
                <img 
                    :src="file.preview" 
                    :alt="file.name"
                    class="w-full h-full object-cover"
                >

                <!-- Upload Progress Overlay -->
                <div 
                    x-show="file.status === 'uploading'"
                    class="absolute inset-0 flex items-center justify-center bg-black/50"
                >
                    <div class="text-center">
                        <svg class="animate-spin h-8 w-8 text-white mx-auto" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="text-white text-sm mt-2" x-text="`${file.progress}%`"></span>
                    </div>
                </div>

                <!-- Success Overlay -->
                <div 
                    x-show="file.status === 'success'"
                    class="absolute inset-0 flex items-center justify-center bg-success-500/20"
                >
                    <div class="w-10 h-10 rounded-full bg-success-500 flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                </div>

                <!-- Error Overlay -->
                <div 
                    x-show="file.status === 'error'"
                    class="absolute inset-0 flex items-center justify-center bg-danger-500/20"
                >
                    <div class="w-10 h-10 rounded-full bg-danger-500 flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </div>
                </div>

                <!-- Remove Button -->
                <button 
                    @click.stop="removeFile(index)"
                    x-show="file.status !== 'uploading'"
                    class="absolute top-2 right-2 w-6 h-6 rounded-full bg-black/50 hover:bg-danger-500 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity"
                >
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>

                <!-- File Size Badge -->
                <div class="absolute bottom-2 left-2 px-2 py-0.5 rounded bg-black/50 text-white text-xs">
                    <span x-text="formatFileSize(file.size)"></span>
                </div>
            </div>
        </template>

        <!-- Add More Button -->
        <div 
            x-show="files.length < maxFiles"
            @click="$refs.fileInput.click()"
            class="aspect-square rounded-lg border-2 border-dashed border-secondary-300 dark:border-secondary-600 hover:border-primary-400 flex items-center justify-center cursor-pointer transition-colors"
        >
            <svg class="w-8 h-8 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
        </div>
    </div>

    <!-- Upload Controls -->
    <div 
        x-show="files.length > 0"
        x-transition
        class="mt-4 flex items-center justify-between gap-4"
    >
        <div class="text-sm text-secondary-600 dark:text-secondary-400">
            <span x-text="files.length"></span> photo(s) selected
            <span x-show="uploadedCount > 0" class="text-success-600 dark:text-success-400">
                (<span x-text="uploadedCount"></span> uploaded)
            </span>
        </div>

        <div class="flex gap-2">
            <button 
                @click="clearAll()"
                type="button"
                class="px-4 py-2 text-sm font-medium text-secondary-600 dark:text-secondary-400 hover:text-danger-600 dark:hover:text-danger-400 transition-colors"
            >
                Clear All
            </button>

            <button 
                @click="uploadAll()"
                :disabled="isUploading || pendingCount === 0"
                type="button"
                class="px-4 py-2 text-sm font-medium text-white bg-primary-500 hover:bg-primary-600 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
            >
                <svg x-show="isUploading" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span x-text="isUploading ? 'Uploading...' : `Upload ${pendingCount} Photo${pendingCount !== 1 ? 's' : ''}`"></span>
            </button>
        </div>
    </div>

    <!-- Overall Progress -->
    <div 
        x-show="isUploading"
        x-transition
        class="mt-4"
    >
        <div class="flex items-center justify-between text-sm text-secondary-600 dark:text-secondary-400 mb-1">
            <span>Overall Progress</span>
            <span x-text="`${uploadedCount}/${files.length}`"></span>
        </div>
        <div class="h-2 bg-secondary-200 dark:bg-secondary-700 rounded-full overflow-hidden">
            <div 
                class="h-full bg-primary-500 rounded-full transition-all duration-300"
                :style="`width: ${(uploadedCount / files.length) * 100}%`"
            ></div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('bulkPhotoUpload', (config) => ({
            files: [],
            dragOver: false,
            isUploading: false,
            maxFiles: config.maxFiles || 10,
            maxSizeMb: config.maxSizeMb || 5,
            endpoint: config.endpoint,
            compressionQuality: config.compressionQuality || 0.8,
            missionId: config.missionId,
            checklistItemId: config.checklistItemId,

            get uploadedCount() {
                return this.files.filter(f => f.status === 'success').length;
            },

            get pendingCount() {
                return this.files.filter(f => f.status === 'pending').length;
            },

            handleFileSelect(event) {
                const files = Array.from(event.target.files);
                this.addFiles(files);
                event.target.value = ''; // Reset input
            },

            handleDrop(event) {
                this.dragOver = false;
                const files = Array.from(event.dataTransfer.files).filter(f => f.type.startsWith('image/'));
                this.addFiles(files);
            },

            async addFiles(newFiles) {
                const availableSlots = this.maxFiles - this.files.length;
                const filesToAdd = newFiles.slice(0, availableSlots);

                for (const file of filesToAdd) {
                    if (file.size > this.maxSizeMb * 1024 * 1024) {
                        if (window.toast) {
                            window.toast.show({
                                message: `${file.name} exceeds ${this.maxSizeMb}MB limit`,
                                type: 'error',
                            });
                        }
                        continue;
                    }

                    const preview = await this.createPreview(file);
                    
                    this.files.push({
                        id: Date.now() + Math.random(),
                        file: file,
                        name: file.name,
                        size: file.size,
                        preview: preview,
                        progress: 0,
                        status: 'pending', // pending, uploading, success, error
                    });
                }

                // Haptic feedback
                if (window.haptics && filesToAdd.length > 0) {
                    window.haptics.selection();
                }
            },

            createPreview(file) {
                return new Promise((resolve) => {
                    const reader = new FileReader();
                    reader.onload = (e) => resolve(e.target.result);
                    reader.readAsDataURL(file);
                });
            },

            removeFile(index) {
                const file = this.files[index];
                if (file.preview) {
                    URL.revokeObjectURL(file.preview);
                }
                this.files.splice(index, 1);
            },

            clearAll() {
                this.files.forEach(f => {
                    if (f.preview) URL.revokeObjectURL(f.preview);
                });
                this.files = [];
            },

            async uploadAll() {
                if (this.isUploading) return;
                this.isUploading = true;

                const pendingFiles = this.files.filter(f => f.status === 'pending');

                for (const fileObj of pendingFiles) {
                    await this.uploadFile(fileObj);
                }

                this.isUploading = false;

                // Success notification
                if (this.uploadedCount === this.files.length) {
                    if (window.toast) {
                        window.toast.show({
                            message: 'All photos uploaded successfully!',
                            type: 'success',
                        });
                    }
                    if (window.haptics) {
                        window.haptics.success();
                    }

                    // Dispatch event
                    window.dispatchEvent(new CustomEvent('photos-uploaded', {
                        detail: {
                            count: this.uploadedCount,
                            missionId: this.missionId,
                            checklistItemId: this.checklistItemId,
                        }
                    }));
                }
            },

            async uploadFile(fileObj) {
                fileObj.status = 'uploading';
                fileObj.progress = 0;

                try {
                    // Compress image if needed
                    const compressedFile = await this.compressImage(fileObj.file);

                    const formData = new FormData();
                    formData.append('file', compressedFile);
                    if (this.missionId) formData.append('mission_id', this.missionId);
                    if (this.checklistItemId) formData.append('checklist_item_id', this.checklistItemId);

                    const response = await this.uploadWithProgress(formData, (progress) => {
                        fileObj.progress = progress;
                    });

                    if (response.success) {
                        fileObj.status = 'success';
                        fileObj.uploadedUrl = response.url;
                    } else {
                        throw new Error(response.message || 'Upload failed');
                    }
                } catch (error) {
                    console.error('Upload error:', error);
                    fileObj.status = 'error';
                    fileObj.error = error.message;

                    // Store for offline retry
                    if (!navigator.onLine) {
                        this.storeForOffline(fileObj);
                    }
                }
            },

            async compressImage(file) {
                return new Promise((resolve) => {
                    const img = new Image();
                    img.onload = () => {
                        const canvas = document.createElement('canvas');
                        const MAX_WIDTH = 1920;
                        const MAX_HEIGHT = 1920;
                        
                        let width = img.width;
                        let height = img.height;

                        if (width > height) {
                            if (width > MAX_WIDTH) {
                                height *= MAX_WIDTH / width;
                                width = MAX_WIDTH;
                            }
                        } else {
                            if (height > MAX_HEIGHT) {
                                width *= MAX_HEIGHT / height;
                                height = MAX_HEIGHT;
                            }
                        }

                        canvas.width = width;
                        canvas.height = height;
                        
                        const ctx = canvas.getContext('2d');
                        ctx.drawImage(img, 0, 0, width, height);
                        
                        canvas.toBlob((blob) => {
                            resolve(new File([blob], file.name, { type: 'image/jpeg' }));
                        }, 'image/jpeg', this.compressionQuality);
                    };
                    img.src = URL.createObjectURL(file);
                });
            },

            uploadWithProgress(formData, onProgress) {
                return new Promise((resolve, reject) => {
                    const xhr = new XMLHttpRequest();

                    xhr.upload.addEventListener('progress', (e) => {
                        if (e.lengthComputable) {
                            const progress = Math.round((e.loaded / e.total) * 100);
                            onProgress(progress);
                        }
                    });

                    xhr.addEventListener('load', () => {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            resolve(response);
                        } catch (e) {
                            reject(new Error('Invalid response'));
                        }
                    });

                    xhr.addEventListener('error', () => reject(new Error('Network error')));
                    xhr.addEventListener('abort', () => reject(new Error('Upload cancelled')));

                    xhr.open('POST', this.endpoint);
                    xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name="csrf-token"]')?.content || '');
                    xhr.setRequestHeader('Accept', 'application/json');
                    xhr.send(formData);
                });
            },

            storeForOffline(fileObj) {
                // Store file data for later sync
                const pending = JSON.parse(localStorage.getItem('pendingSyncItems') || '[]');
                pending.push({
                    type: 'photo-upload',
                    data: {
                        name: fileObj.name,
                        missionId: this.missionId,
                        checklistItemId: this.checklistItemId,
                    },
                    timestamp: Date.now(),
                });
                localStorage.setItem('pendingSyncItems', JSON.stringify(pending));

                // Notify sync status component
                window.dispatchEvent(new CustomEvent('pending-changes', {
                    detail: { count: pending.length }
                }));
            },

            formatFileSize(bytes) {
                if (bytes < 1024) return bytes + ' B';
                if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
                return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
            }
        }));
    });
</script>
