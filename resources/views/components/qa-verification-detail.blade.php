@props([
    'review' => null,
])

<!-- QA Verification Detail Component -->
<div 
    x-data="qaVerificationDetail()"
    x-show="open"
    x-transition
    @open-qa-detail.window="openDetail($event.detail)"
    @keydown.escape.window="open = false"
    class="fixed inset-0 z-50 overflow-y-auto"
>
    <!-- Backdrop -->
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="open = false"></div>

    <!-- Modal -->
    <div class="relative min-h-screen flex items-center justify-center p-4">
        <div class="relative w-full max-w-4xl bg-white dark:bg-secondary-900 rounded-2xl shadow-2xl overflow-hidden" @click.stop>
            
            <!-- Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-secondary-200 dark:border-secondary-700">
                <div class="flex items-center gap-3">
                    <div 
                        class="w-12 h-12 rounded-xl flex flex-col items-center justify-center"
                        :class="scoreClass"
                    >
                        <span class="text-xl font-bold" x-text="`${review?.score || 0}%`"></span>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-secondary-900 dark:text-white">Photo Verification Details</h2>
                        <p class="text-sm text-secondary-500" x-text="reviewTitle"></p>
                    </div>
                </div>
                <button @click="open = false" class="p-2 hover:bg-secondary-100 dark:hover:bg-secondary-800 rounded-lg">
                    <svg class="w-5 h-5 text-secondary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Content -->
            <div class="p-6 max-h-[70vh] overflow-y-auto">
                <!-- Verification Checks -->
                <div class="space-y-4 mb-6">
                    <h3 class="font-medium text-secondary-900 dark:text-white">Verification Checks</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <template x-for="(check, name) in (review?.verification_data?.checks || {})" :key="name">
                            <div 
                                class="p-4 rounded-xl border-2"
                                :class="check.passed ? 'border-success-200 dark:border-success-800 bg-success-50 dark:bg-success-900/20' : 'border-danger-200 dark:border-danger-800 bg-danger-50 dark:bg-danger-900/20'"
                            >
                                <div class="flex items-center gap-2 mb-2">
                                    <svg x-show="check.passed" class="w-5 h-5 text-success-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <svg x-show="!check.passed" class="w-5 h-5 text-danger-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <span class="font-medium capitalize text-secondary-900 dark:text-white" x-text="name.replace('_', ' ')"></span>
                                </div>
                                <p 
                                    class="text-sm"
                                    :class="check.passed ? 'text-success-700 dark:text-success-400' : 'text-danger-700 dark:text-danger-400'"
                                    x-text="check.message"
                                ></p>
                                
                                <!-- Details -->
                                <div class="mt-2 text-xs text-secondary-500" x-show="check.details">
                                    <template x-if="check.details?.distance_km !== undefined">
                                        <span>Distance: <span x-text="check.details.distance_km"></span> km</span>
                                    </template>
                                    <template x-if="check.details?.hours_difference !== undefined">
                                        <span>Time diff: <span x-text="check.details.hours_difference"></span> hours</span>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- EXIF Metadata -->
                <div class="mb-6" x-show="review?.verification_data?.metadata?.exif?.available">
                    <h3 class="font-medium text-secondary-900 dark:text-white mb-4">Photo Metadata</h3>
                    
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="p-3 bg-secondary-50 dark:bg-secondary-800 rounded-lg">
                            <div class="text-xs text-secondary-500 mb-1">Date Taken</div>
                            <div class="text-sm font-medium text-secondary-900 dark:text-white" x-text="review?.verification_data?.metadata?.exif?.datetime || 'N/A'"></div>
                        </div>
                        <div class="p-3 bg-secondary-50 dark:bg-secondary-800 rounded-lg">
                            <div class="text-xs text-secondary-500 mb-1">Device</div>
                            <div class="text-sm font-medium text-secondary-900 dark:text-white" x-text="(review?.verification_data?.metadata?.exif?.device_make || '') + ' ' + (review?.verification_data?.metadata?.exif?.device_model || 'N/A')"></div>
                        </div>
                        <div class="p-3 bg-secondary-50 dark:bg-secondary-800 rounded-lg">
                            <div class="text-xs text-secondary-500 mb-1">Latitude</div>
                            <div class="text-sm font-medium text-secondary-900 dark:text-white" x-text="review?.verification_data?.metadata?.exif?.latitude?.toFixed(6) || 'N/A'"></div>
                        </div>
                        <div class="p-3 bg-secondary-50 dark:bg-secondary-800 rounded-lg">
                            <div class="text-xs text-secondary-500 mb-1">Longitude</div>
                            <div class="text-sm font-medium text-secondary-900 dark:text-white" x-text="review?.verification_data?.metadata?.exif?.longitude?.toFixed(6) || 'N/A'"></div>
                        </div>
                    </div>
                </div>

                <!-- Warnings -->
                <div class="mb-6" x-show="(review?.verification_data?.warnings || []).length > 0">
                    <h3 class="font-medium text-secondary-900 dark:text-white mb-4">Warnings</h3>
                    <div class="space-y-2">
                        <template x-for="warning in (review?.verification_data?.warnings || [])" :key="warning">
                            <div class="flex items-center gap-2 p-3 bg-warning-50 dark:bg-warning-900/20 rounded-lg">
                                <svg class="w-5 h-5 text-warning-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <span class="text-sm text-warning-700 dark:text-warning-400" x-text="warning"></span>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Notes -->
                <div x-show="review?.notes">
                    <h3 class="font-medium text-secondary-900 dark:text-white mb-2">Reviewer Notes</h3>
                    <p class="text-secondary-600 dark:text-secondary-400 text-sm p-3 bg-secondary-50 dark:bg-secondary-800 rounded-lg" x-text="review?.notes"></p>
                </div>
            </div>

            <!-- Footer Actions -->
            <div class="flex items-center justify-end gap-2 px-6 py-4 border-t border-secondary-200 dark:border-secondary-700">
                <button 
                    @click="flag()"
                    class="px-4 py-2 text-sm font-medium text-warning-600 hover:bg-warning-50 rounded-lg"
                >
                    Flag for Review
                </button>
                <button 
                    @click="reject()"
                    class="px-4 py-2 text-sm font-medium text-danger-600 hover:bg-danger-50 rounded-lg"
                >
                    Reject
                </button>
                <button 
                    @click="approve()"
                    class="px-4 py-2 text-sm font-medium text-white bg-success-500 hover:bg-success-600 rounded-lg"
                >
                    Approve
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('qaVerificationDetail', () => ({
            open: false,
            review: null,

            get reviewTitle() {
                if (!this.review) return '';
                if (this.review.reviewable?.title) return this.review.reviewable.title;
                return `Review #${this.review.id}`;
            },

            get scoreClass() {
                const score = this.review?.score || 0;
                if (score >= 90) return 'bg-success-100 text-success-600 dark:bg-success-900/30 dark:text-success-400';
                if (score >= 70) return 'bg-warning-100 text-warning-600 dark:bg-warning-900/30 dark:text-warning-400';
                if (score >= 50) return 'bg-orange-100 text-orange-600 dark:bg-orange-900/30 dark:text-orange-400';
                return 'bg-danger-100 text-danger-600 dark:bg-danger-900/30 dark:text-danger-400';
            },

            openDetail(review) {
                this.review = review;
                this.open = true;
            },

            async approve() {
                if (!this.review) return;
                try {
                    await fetch(`/api/v1/qa/reviews/${this.review.id}/approve`, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        },
                    });
                    this.open = false;
                    window.dispatchEvent(new CustomEvent('qa-review-updated'));
                    if (window.toast) window.toast.show({ message: 'Approved!', type: 'success' });
                } catch (e) {
                    console.error('Error approving:', e);
                }
            },

            async reject() {
                const reason = prompt('Enter rejection reason:');
                if (!reason || !this.review) return;
                try {
                    await fetch(`/api/v1/qa/reviews/${this.review.id}/reject`, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        },
                        body: JSON.stringify({ reason }),
                    });
                    this.open = false;
                    window.dispatchEvent(new CustomEvent('qa-review-updated'));
                    if (window.toast) window.toast.show({ message: 'Rejected', type: 'info' });
                } catch (e) {
                    console.error('Error rejecting:', e);
                }
            },

            async flag() {
                const reason = prompt('Enter flag reason:');
                if (!reason || !this.review) return;
                try {
                    await fetch(`/api/v1/qa/reviews/${this.review.id}/flag`, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        },
                        body: JSON.stringify({ reason }),
                    });
                    this.open = false;
                    window.dispatchEvent(new CustomEvent('qa-review-updated'));
                    if (window.toast) window.toast.show({ message: 'Flagged for review', type: 'warning' });
                } catch (e) {
                    console.error('Error flagging:', e);
                }
            }
        }));
    });
</script>
