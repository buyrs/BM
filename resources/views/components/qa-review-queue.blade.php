@props([
    'refreshInterval' => 30000,
])

<!-- QA Review Queue Component -->
<div 
    x-data="qaReviewQueue({ refreshInterval: {{ $refreshInterval }} })"
    x-init="init()"
    {{ $attributes->merge(['class' => 'space-y-6']) }}
>
    <!-- Dashboard Summary -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <div class="bg-white dark:bg-secondary-800 rounded-xl p-4 shadow-soft">
            <div class="flex items-center justify-between mb-2">
                <span class="text-secondary-500 dark:text-secondary-400 text-sm">Pending</span>
                <div class="w-8 h-8 rounded-full bg-warning-100 dark:bg-warning-900/30 flex items-center justify-center">
                    <svg class="w-4 h-4 text-warning-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <div class="text-2xl font-bold text-secondary-900 dark:text-white" x-text="dashboard.pending"></div>
        </div>

        <div class="bg-white dark:bg-secondary-800 rounded-xl p-4 shadow-soft">
            <div class="flex items-center justify-between mb-2">
                <span class="text-secondary-500 dark:text-secondary-400 text-sm">Flagged</span>
                <div class="w-8 h-8 rounded-full bg-danger-100 dark:bg-danger-900/30 flex items-center justify-center">
                    <svg class="w-4 h-4 text-danger-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"/>
                    </svg>
                </div>
            </div>
            <div class="text-2xl font-bold text-danger-600 dark:text-danger-400" x-text="dashboard.flagged"></div>
        </div>

        <div class="bg-white dark:bg-secondary-800 rounded-xl p-4 shadow-soft">
            <div class="flex items-center justify-between mb-2">
                <span class="text-secondary-500 dark:text-secondary-400 text-sm">Approved Today</span>
                <div class="w-8 h-8 rounded-full bg-success-100 dark:bg-success-900/30 flex items-center justify-center">
                    <svg class="w-4 h-4 text-success-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <div class="text-2xl font-bold text-success-600 dark:text-success-400" x-text="dashboard.approved_today"></div>
        </div>

        <div class="bg-white dark:bg-secondary-800 rounded-xl p-4 shadow-soft">
            <div class="flex items-center justify-between mb-2">
                <span class="text-secondary-500 dark:text-secondary-400 text-sm">Rejected Today</span>
                <div class="w-8 h-8 rounded-full bg-secondary-100 dark:bg-secondary-700 flex items-center justify-center">
                    <svg class="w-4 h-4 text-secondary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <div class="text-2xl font-bold text-secondary-900 dark:text-white" x-text="dashboard.rejected_today"></div>
        </div>

        <div class="bg-white dark:bg-secondary-800 rounded-xl p-4 shadow-soft">
            <div class="flex items-center justify-between mb-2">
                <span class="text-secondary-500 dark:text-secondary-400 text-sm">Avg Score</span>
                <div class="w-8 h-8 rounded-full bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center">
                    <svg class="w-4 h-4 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                    </svg>
                </div>
            </div>
            <div class="text-2xl font-bold text-secondary-900 dark:text-white"><span x-text="dashboard.avg_score_today"></span>%</div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white dark:bg-secondary-800 rounded-xl p-4 shadow-soft flex items-center gap-4 flex-wrap">
        <div class="flex items-center gap-2">
            <label class="text-sm text-secondary-500">Status:</label>
            <select 
                x-model="filters.status"
                @change="loadQueue()"
                class="px-3 py-1.5 rounded-lg border border-secondary-200 dark:border-secondary-700 bg-white dark:bg-secondary-900 text-sm"
            >
                <option value="">Needs Attention</option>
                <option value="pending">Pending</option>
                <option value="flagged">Flagged</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
            </select>
        </div>

        <div class="flex items-center gap-2">
            <label class="text-sm text-secondary-500">Score:</label>
            <select 
                x-model="filters.maxScore"
                @change="loadQueue()"
                class="px-3 py-1.5 rounded-lg border border-secondary-200 dark:border-secondary-700 bg-white dark:bg-secondary-900 text-sm"
            >
                <option value="">All</option>
                <option value="50">Below 50%</option>
                <option value="70">Below 70%</option>
                <option value="90">Below 90%</option>
            </select>
        </div>

        <div class="flex-1"></div>

        <button 
            @click="bulkApproveSelected()"
            :disabled="selectedReviews.length === 0"
            class="px-4 py-2 text-sm font-medium text-white bg-success-500 hover:bg-success-600 rounded-lg transition-colors disabled:opacity-50"
        >
            Approve Selected (<span x-text="selectedReviews.length"></span>)
        </button>

        <button 
            @click="loadQueue()"
            class="p-2 rounded-lg hover:bg-secondary-100 dark:hover:bg-secondary-700"
        >
            <svg class="w-5 h-5 text-secondary-500" :class="{ 'animate-spin': loading }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
        </button>
    </div>

    <!-- Review Queue -->
    <div class="bg-white dark:bg-secondary-800 rounded-xl shadow-soft overflow-hidden">
        <div class="divide-y divide-secondary-100 dark:divide-secondary-700">
            <template x-for="review in reviews" :key="review.id">
                <div class="p-4 hover:bg-secondary-50 dark:hover:bg-secondary-700/50 transition-colors">
                    <div class="flex items-start gap-4">
                        <!-- Checkbox -->
                        <input 
                            type="checkbox"
                            :value="review.id"
                            x-model="selectedReviews"
                            class="mt-1 rounded border-secondary-300 dark:border-secondary-600"
                        >

                        <!-- Score Badge -->
                        <div 
                            class="w-14 h-14 rounded-xl flex flex-col items-center justify-center"
                            :class="{
                                'bg-success-100 dark:bg-success-900/30': review.score >= 90,
                                'bg-warning-100 dark:bg-warning-900/30': review.score >= 70 && review.score < 90,
                                'bg-orange-100 dark:bg-orange-900/30': review.score >= 50 && review.score < 70,
                                'bg-danger-100 dark:bg-danger-900/30': review.score < 50
                            }"
                        >
                            <span 
                                class="text-lg font-bold"
                                :class="{
                                    'text-success-600 dark:text-success-400': review.score >= 90,
                                    'text-warning-600 dark:text-warning-400': review.score >= 70 && review.score < 90,
                                    'text-orange-600 dark:text-orange-400': review.score >= 50 && review.score < 70,
                                    'text-danger-600 dark:text-danger-400': review.score < 50
                                }"
                                x-text="`${review.score}%`"
                            ></span>
                            <span class="text-[10px] text-secondary-500 uppercase">Score</span>
                        </div>

                        <!-- Content -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="font-medium text-secondary-900 dark:text-white" x-text="getReviewableTitle(review)"></span>
                                <span 
                                    class="px-2 py-0.5 rounded-full text-xs font-medium"
                                    :class="{
                                        'bg-warning-100 text-warning-700': review.status === 'pending',
                                        'bg-danger-100 text-danger-700': review.status === 'flagged',
                                        'bg-success-100 text-success-700': review.status === 'approved',
                                        'bg-secondary-100 text-secondary-700': review.status === 'rejected'
                                    }"
                                    x-text="review.status"
                                ></span>
                            </div>

                            <!-- Verification Issues -->
                            <div class="flex flex-wrap gap-2 mt-2" x-show="review.verification_data?.warnings?.length > 0">
                                <template x-for="warning in (review.verification_data?.warnings || []).slice(0, 3)" :key="warning">
                                    <span class="px-2 py-1 bg-warning-50 dark:bg-warning-900/20 text-warning-700 dark:text-warning-400 text-xs rounded-lg" x-text="warning"></span>
                                </template>
                            </div>

                            <p class="text-xs text-secondary-500 mt-2" x-text="formatDate(review.created_at)"></p>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center gap-2">
                            <button 
                                @click="openDetail(review)"
                                class="p-2 rounded-lg hover:bg-secondary-100 dark:hover:bg-secondary-700 text-secondary-500"
                                title="View Details"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </button>
                            <button 
                                @click="approveReview(review.id)"
                                class="p-2 rounded-lg hover:bg-success-100 dark:hover:bg-success-900/30 text-success-600"
                                title="Approve"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </button>
                            <button 
                                @click="openRejectModal(review)"
                                class="p-2 rounded-lg hover:bg-danger-100 dark:hover:bg-danger-900/30 text-danger-600"
                                title="Reject"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Empty State -->
        <div x-show="!loading && reviews.length === 0" class="p-12 text-center">
            <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-success-100 dark:bg-success-900/30 flex items-center justify-center">
                <svg class="w-8 h-8 text-success-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <p class="text-secondary-900 dark:text-white font-medium">All caught up!</p>
            <p class="text-secondary-500 text-sm">No reviews need attention</p>
        </div>
    </div>

    <!-- Reject Modal -->
    <div 
        x-show="rejectModal.open"
        x-transition
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
        @keydown.escape.window="rejectModal.open = false"
    >
        <div class="bg-white dark:bg-secondary-800 rounded-xl shadow-xl max-w-md w-full p-6" @click.away="rejectModal.open = false">
            <h3 class="text-lg font-semibold text-secondary-900 dark:text-white mb-4">Reject Review</h3>
            <textarea 
                x-model="rejectModal.reason"
                placeholder="Enter rejection reason..."
                rows="3"
                class="w-full px-3 py-2 rounded-lg border border-secondary-200 dark:border-secondary-700 bg-white dark:bg-secondary-900 mb-4"
            ></textarea>
            <div class="flex justify-end gap-2">
                <button 
                    @click="rejectModal.open = false"
                    class="px-4 py-2 text-sm text-secondary-700 dark:text-secondary-300"
                >Cancel</button>
                <button 
                    @click="confirmReject()"
                    :disabled="!rejectModal.reason"
                    class="px-4 py-2 text-sm font-medium text-white bg-danger-500 hover:bg-danger-600 rounded-lg disabled:opacity-50"
                >Reject</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('qaReviewQueue', (config) => ({
            refreshInterval: config.refreshInterval,
            loading: false,
            reviews: [],
            dashboard: { pending: 0, flagged: 0, approved_today: 0, rejected_today: 0, avg_score_today: 0 },
            selectedReviews: [],
            filters: { status: '', maxScore: '' },
            rejectModal: { open: false, review: null, reason: '' },

            init() {
                this.loadDashboard();
                this.loadQueue();
            },

            async loadDashboard() {
                try {
                    const response = await fetch('/api/v1/qa/dashboard', {
                        headers: { 'Accept': 'application/json' },
                    });
                    const data = await response.json();
                    if (data.success) {
                        this.dashboard = data.data;
                    }
                } catch (e) {
                    console.error('Error loading dashboard:', e);
                }
            },

            async loadQueue() {
                this.loading = true;
                try {
                    const params = new URLSearchParams();
                    if (this.filters.status) params.append('status', this.filters.status);
                    if (this.filters.maxScore) params.append('max_score', this.filters.maxScore);

                    const response = await fetch(`/api/v1/qa/queue?${params}`, {
                        headers: { 'Accept': 'application/json' },
                    });
                    const data = await response.json();
                    if (data.success) {
                        this.reviews = data.data || [];
                    }
                } catch (e) {
                    console.error('Error loading queue:', e);
                } finally {
                    this.loading = false;
                }
            },

            async approveReview(reviewId) {
                try {
                    const response = await fetch(`/api/v1/qa/reviews/${reviewId}/approve`, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        },
                    });
                    if (response.ok) {
                        this.reviews = this.reviews.filter(r => r.id !== reviewId);
                        this.loadDashboard();
                        if (window.toast) window.toast.show({ message: 'Approved!', type: 'success' });
                    }
                } catch (e) {
                    console.error('Error approving:', e);
                }
            },

            openRejectModal(review) {
                this.rejectModal = { open: true, review, reason: '' };
            },

            async confirmReject() {
                if (!this.rejectModal.review || !this.rejectModal.reason) return;
                try {
                    const response = await fetch(`/api/v1/qa/reviews/${this.rejectModal.review.id}/reject`, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        },
                        body: JSON.stringify({ reason: this.rejectModal.reason }),
                    });
                    if (response.ok) {
                        this.reviews = this.reviews.filter(r => r.id !== this.rejectModal.review.id);
                        this.rejectModal.open = false;
                        this.loadDashboard();
                        if (window.toast) window.toast.show({ message: 'Rejected', type: 'info' });
                    }
                } catch (e) {
                    console.error('Error rejecting:', e);
                }
            },

            async bulkApproveSelected() {
                if (this.selectedReviews.length === 0) return;
                try {
                    const response = await fetch('/api/v1/qa/bulk-approve', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        },
                        body: JSON.stringify({ review_ids: this.selectedReviews }),
                    });
                    if (response.ok) {
                        this.selectedReviews = [];
                        this.loadQueue();
                        this.loadDashboard();
                        if (window.toast) window.toast.show({ message: 'Bulk approved!', type: 'success' });
                    }
                } catch (e) {
                    console.error('Error bulk approving:', e);
                }
            },

            openDetail(review) {
                window.dispatchEvent(new CustomEvent('open-qa-detail', { detail: review }));
            },

            getReviewableTitle(review) {
                if (review.reviewable?.title) return review.reviewable.title;
                if (review.reviewable_type?.includes('Mission')) return `Mission #${review.reviewable_id}`;
                return `Item #${review.reviewable_id}`;
            },

            formatDate(date) {
                return new Date(date).toLocaleString();
            }
        }));
    });
</script>
