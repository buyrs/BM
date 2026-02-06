@props([
    'position' => 'top-right', // top-right, top-left, bottom-right, bottom-left
    'showDetails' => false,
])

@php
    $positions = [
        'top-right' => 'top-4 right-4',
        'top-left' => 'top-4 left-4',
        'bottom-right' => 'bottom-4 right-4',
        'bottom-left' => 'bottom-4 left-4',
    ];
    $positionClass = $positions[$position] ?? $positions['top-right'];
@endphp

<!-- Sync Status Indicator Component -->
<div 
    x-data="syncStatus()"
    x-init="init()"
    {{ $attributes->merge(['class' => "fixed {$positionClass} z-50"]) }}
>
    <!-- Status Badge -->
    <div 
        x-show="visible"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-90 translate-y-2"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-90"
        class="flex items-center gap-2 px-3 py-2 rounded-full shadow-lg backdrop-blur-sm transition-all duration-300"
        :class="{
            'bg-success-500/90 text-white': status === 'online',
            'bg-warning-500/90 text-white': status === 'syncing',
            'bg-danger-500/90 text-white': status === 'offline',
            'bg-secondary-500/90 text-white': status === 'pending'
        }"
    >
        <!-- Status Icon -->
        <div class="relative">
            <!-- Online Icon -->
            <svg 
                x-show="status === 'online'" 
                class="w-4 h-4"
                fill="none" 
                stroke="currentColor" 
                viewBox="0 0 24 24"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.14 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"/>
            </svg>

            <!-- Syncing Icon -->
            <svg 
                x-show="status === 'syncing'" 
                class="w-4 h-4 animate-spin"
                fill="none" 
                stroke="currentColor" 
                viewBox="0 0 24 24"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>

            <!-- Offline Icon -->
            <svg 
                x-show="status === 'offline'" 
                class="w-4 h-4"
                fill="none" 
                stroke="currentColor" 
                viewBox="0 0 24 24"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636a9 9 0 010 12.728m0 0l-2.829-2.829m2.829 2.829L21 21M15.536 8.464a5 5 0 010 7.072m0 0l-2.829-2.829m-4.243 2.829a4.978 4.978 0 01-1.414-2.83m-1.414 5.658a9 9 0 01-2.167-9.238m7.824 2.167a1 1 0 111.414 1.414m-1.414-1.414L3 3"/>
            </svg>

            <!-- Pending Icon -->
            <svg 
                x-show="status === 'pending'" 
                class="w-4 h-4"
                fill="none" 
                stroke="currentColor" 
                viewBox="0 0 24 24"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>

        <!-- Status Text -->
        <span class="text-xs font-medium" x-text="statusText"></span>

        <!-- Pending Count Badge -->
        <span 
            x-show="pendingCount > 0 && status !== 'syncing'"
            class="inline-flex items-center justify-center px-1.5 py-0.5 text-xs font-bold rounded-full bg-white/20"
            x-text="pendingCount"
        ></span>

        <!-- Progress Bar (during sync) -->
        <div 
            x-show="status === 'syncing' && syncProgress > 0"
            class="w-16 h-1.5 bg-white/30 rounded-full overflow-hidden"
        >
            <div 
                class="h-full bg-white rounded-full transition-all duration-300"
                :style="`width: ${syncProgress}%`"
            ></div>
        </div>
    </div>

    @if($showDetails)
    <!-- Expanded Details Panel -->
    <div 
        x-show="showDetails && (status === 'offline' || pendingCount > 0)"
        x-transition
        class="mt-2 p-3 rounded-lg shadow-lg backdrop-blur-sm bg-white/95 dark:bg-secondary-800/95 border border-secondary-200 dark:border-secondary-700"
    >
        <div class="text-xs text-secondary-600 dark:text-secondary-300 space-y-1">
            <p x-show="status === 'offline'">
                <span class="font-medium">Offline Mode:</span> Changes will sync when back online.
            </p>
            <p x-show="pendingCount > 0">
                <span class="font-medium">Pending:</span> <span x-text="pendingCount"></span> item(s) to sync
            </p>
            <p x-show="lastSyncTime">
                <span class="font-medium">Last sync:</span> <span x-text="lastSyncTimeFormatted"></span>
            </p>
        </div>
    </div>
    @endif
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('syncStatus', () => ({
            status: 'online', // online, offline, syncing, pending
            visible: false,
            pendingCount: 0,
            syncProgress: 0,
            lastSyncTime: null,
            showDetails: false,
            hideTimeout: null,

            get statusText() {
                switch (this.status) {
                    case 'online': return 'Online';
                    case 'offline': return 'Offline';
                    case 'syncing': return 'Syncing...';
                    case 'pending': return `${this.pendingCount} pending`;
                    default: return '';
                }
            },

            get lastSyncTimeFormatted() {
                if (!this.lastSyncTime) return 'Never';
                const diff = Date.now() - this.lastSyncTime;
                if (diff < 60000) return 'Just now';
                if (diff < 3600000) return `${Math.floor(diff / 60000)}m ago`;
                return `${Math.floor(diff / 3600000)}h ago`;
            },

            init() {
                // Initial status check
                this.updateStatus(navigator.onLine);

                // Listen for online/offline events
                window.addEventListener('online', () => this.handleOnline());
                window.addEventListener('offline', () => this.handleOffline());

                // Listen for custom sync events
                window.addEventListener('sync-started', (e) => this.startSync(e.detail));
                window.addEventListener('sync-progress', (e) => this.updateProgress(e.detail));
                window.addEventListener('sync-completed', () => this.completeSync());
                window.addEventListener('sync-failed', (e) => this.syncFailed(e.detail));
                window.addEventListener('pending-changes', (e) => this.updatePending(e.detail));

                // Check for pending items in IndexedDB/localStorage
                this.checkPendingItems();
            },

            updateStatus(isOnline) {
                if (isOnline) {
                    this.status = this.pendingCount > 0 ? 'pending' : 'online';
                } else {
                    this.status = 'offline';
                }
            },

            handleOnline() {
                this.visible = true;
                this.status = 'online';
                
                // Haptic feedback
                if (window.haptics) {
                    window.haptics.success();
                }

                // Auto-sync pending items
                if (this.pendingCount > 0) {
                    this.triggerSync();
                }

                // Hide after delay if online
                this.scheduleHide();
            },

            handleOffline() {
                this.visible = true;
                this.status = 'offline';
                
                // Haptic feedback
                if (window.haptics) {
                    window.haptics.warning();
                }

                // Keep visible while offline
                clearTimeout(this.hideTimeout);
            },

            startSync(detail = {}) {
                this.visible = true;
                this.status = 'syncing';
                this.syncProgress = 0;
                clearTimeout(this.hideTimeout);
            },

            updateProgress(detail) {
                if (detail.progress !== undefined) {
                    this.syncProgress = detail.progress;
                }
                if (detail.current !== undefined && detail.total !== undefined) {
                    this.syncProgress = Math.round((detail.current / detail.total) * 100);
                }
            },

            completeSync() {
                this.status = 'online';
                this.syncProgress = 100;
                this.pendingCount = 0;
                this.lastSyncTime = Date.now();

                // Haptic feedback
                if (window.haptics) {
                    window.haptics.success();
                }

                // Show success toast
                if (window.toast) {
                    window.toast.show({
                        message: 'All changes synced!',
                        type: 'success',
                        duration: 2000,
                    });
                }

                this.scheduleHide();
            },

            syncFailed(detail = {}) {
                this.status = 'pending';
                
                // Haptic feedback
                if (window.haptics) {
                    window.haptics.error();
                }

                // Show error toast
                if (window.toast) {
                    window.toast.show({
                        message: detail.message || 'Sync failed. Will retry...',
                        type: 'error',
                    });
                }
            },

            updatePending(detail) {
                this.pendingCount = detail.count || 0;
                if (this.pendingCount > 0 && navigator.onLine) {
                    this.status = 'pending';
                    this.visible = true;
                }
            },

            async checkPendingItems() {
                // Check localStorage for pending items
                try {
                    const pending = localStorage.getItem('pendingSyncItems');
                    if (pending) {
                        const items = JSON.parse(pending);
                        this.pendingCount = Array.isArray(items) ? items.length : 0;
                        if (this.pendingCount > 0) {
                            this.visible = true;
                            this.status = navigator.onLine ? 'pending' : 'offline';
                        }
                    }
                } catch (e) {
                    console.error('Error checking pending items:', e);
                }
            },

            triggerSync() {
                // Dispatch event to trigger sync
                window.dispatchEvent(new CustomEvent('trigger-sync'));
            },

            scheduleHide() {
                clearTimeout(this.hideTimeout);
                this.hideTimeout = setTimeout(() => {
                    if (this.status === 'online') {
                        this.visible = false;
                    }
                }, 3000);
            },

            toggleDetails() {
                this.showDetails = !this.showDetails;
            }
        }));
    });

    // Register service worker sync event handler
    if ('serviceWorker' in navigator && 'SyncManager' in window) {
        navigator.serviceWorker.ready.then(registration => {
            // Listen for sync messages from service worker
            navigator.serviceWorker.addEventListener('message', event => {
                if (event.data.type === 'SYNC_STATUS') {
                    window.dispatchEvent(new CustomEvent(event.data.event, {
                        detail: event.data.detail
                    }));
                }
            });
        });
    }
</script>
