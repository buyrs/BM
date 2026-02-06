/**
 * IndexedDB Wrapper for Bail Mobilite PWA
 * Provides a simple async API for offline data storage
 */
class OfflineStorage {
    constructor(dbName = 'bail-mobilite-db', version = 1) {
        this.dbName = dbName;
        this.version = version;
        this.db = null;
        this.stores = {
            missions: { keyPath: 'id', indexes: ['status', 'propertyAddress', 'syncStatus'] },
            checklists: { keyPath: 'id', indexes: ['missionId', 'syncStatus'] },
            photos: { keyPath: 'id', indexes: ['checklistItemId', 'syncStatus'] },
            pendingSync: { keyPath: 'id', indexes: ['type', 'createdAt'] },
            cache: { keyPath: 'key' }
        };
    }

    /**
     * Initialize the database
     */
    async init() {
        if (this.db) return this.db;

        return new Promise((resolve, reject) => {
            const request = indexedDB.open(this.dbName, this.version);

            request.onerror = () => reject(request.error);
            request.onsuccess = () => {
                this.db = request.result;
                resolve(this.db);
            };

            request.onupgradeneeded = (event) => {
                const db = event.target.result;

                for (const [storeName, config] of Object.entries(this.stores)) {
                    if (!db.objectStoreNames.contains(storeName)) {
                        const store = db.createObjectStore(storeName, {
                            keyPath: config.keyPath,
                            autoIncrement: config.keyPath === 'id'
                        });

                        if (config.indexes) {
                            for (const indexName of config.indexes) {
                                store.createIndex(indexName, indexName, { unique: false });
                            }
                        }
                    }
                }
            };
        });
    }

    /**
     * Get a store transaction
     */
    getStore(storeName, mode = 'readonly') {
        if (!this.db) throw new Error('Database not initialized');
        const transaction = this.db.transaction(storeName, mode);
        return transaction.objectStore(storeName);
    }

    /**
     * Add or update an item
     */
    async put(storeName, item) {
        await this.init();

        return new Promise((resolve, reject) => {
            const store = this.getStore(storeName, 'readwrite');
            const request = store.put(item);
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    /**
     * Get an item by key
     */
    async get(storeName, key) {
        await this.init();

        return new Promise((resolve, reject) => {
            const store = this.getStore(storeName);
            const request = store.get(key);
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    /**
     * Get all items from a store
     */
    async getAll(storeName, query = null) {
        await this.init();

        return new Promise((resolve, reject) => {
            const store = this.getStore(storeName);
            const request = query ? store.getAll(query) : store.getAll();
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    /**
     * Get items by index
     */
    async getByIndex(storeName, indexName, value) {
        await this.init();

        return new Promise((resolve, reject) => {
            const store = this.getStore(storeName);
            const index = store.index(indexName);
            const request = index.getAll(value);
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    /**
     * Delete an item
     */
    async delete(storeName, key) {
        await this.init();

        return new Promise((resolve, reject) => {
            const store = this.getStore(storeName, 'readwrite');
            const request = store.delete(key);
            request.onsuccess = () => resolve();
            request.onerror = () => reject(request.error);
        });
    }

    /**
     * Clear all items in a store
     */
    async clear(storeName) {
        await this.init();

        return new Promise((resolve, reject) => {
            const store = this.getStore(storeName, 'readwrite');
            const request = store.clear();
            request.onsuccess = () => resolve();
            request.onerror = () => reject(request.error);
        });
    }

    /**
     * Count items in a store
     */
    async count(storeName) {
        await this.init();

        return new Promise((resolve, reject) => {
            const store = this.getStore(storeName);
            const request = store.count();
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    /**
     * Bulk insert items
     */
    async bulkPut(storeName, items) {
        await this.init();

        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction(storeName, 'readwrite');
            const store = transaction.objectStore(storeName);

            let completed = 0;
            const results = [];

            transaction.oncomplete = () => resolve(results);
            transaction.onerror = () => reject(transaction.error);

            for (const item of items) {
                const request = store.put(item);
                request.onsuccess = () => {
                    results.push(request.result);
                    completed++;
                };
            }
        });
    }
}

/**
 * Sync Manager for handling offline/online data synchronization
 */
class SyncManager {
    constructor(storage) {
        this.storage = storage;
        this.isSyncing = false;
        this.retryAttempts = {};
        this.maxRetries = 5;
        this.retryDelays = [1000, 2000, 5000, 10000, 30000]; // Exponential backoff
    }

    /**
     * Initialize sync manager
     */
    init() {
        // Listen for online events
        window.addEventListener('online', () => this.onOnline());
        window.addEventListener('offline', () => this.onOffline());

        // Listen for sync triggers
        window.addEventListener('trigger-sync', () => this.sync());

        // Register for background sync if available
        if ('serviceWorker' in navigator && 'sync' in window.registration) {
            navigator.serviceWorker.ready.then(registration => {
                registration.sync.register('sync-pending-changes');
            });
        }

        // Check for pending items on init
        this.checkPendingItems();
    }

    /**
     * Handle coming online
     */
    async onOnline() {
        console.log('[SyncManager] Online - starting sync');
        window.dispatchEvent(new CustomEvent('sync-started'));
        await this.sync();
    }

    /**
     * Handle going offline
     */
    onOffline() {
        console.log('[SyncManager] Offline');
        this.isSyncing = false;
    }

    /**
     * Check for pending items
     */
    async checkPendingItems() {
        try {
            const pending = await this.storage.getAll('pendingSync');
            window.dispatchEvent(new CustomEvent('pending-changes', {
                detail: { count: pending.length }
            }));
            return pending.length;
        } catch (e) {
            console.error('[SyncManager] Error checking pending items:', e);
            return 0;
        }
    }

    /**
     * Add item to sync queue
     */
    async queueForSync(type, data, endpoint, method = 'POST') {
        const item = {
            id: `${Date.now()}-${Math.random().toString(36).substr(2, 9)}`,
            type,
            data,
            endpoint,
            method,
            createdAt: Date.now(),
            attempts: 0
        };

        await this.storage.put('pendingSync', item);
        await this.checkPendingItems();

        // Trigger sync if online
        if (navigator.onLine) {
            this.sync();
        }

        return item.id;
    }

    /**
     * Sync all pending items
     */
    async sync() {
        if (this.isSyncing || !navigator.onLine) return;

        this.isSyncing = true;
        window.dispatchEvent(new CustomEvent('sync-started'));

        try {
            const pending = await this.storage.getAll('pendingSync');

            if (pending.length === 0) {
                this.isSyncing = false;
                window.dispatchEvent(new CustomEvent('sync-completed'));
                return;
            }

            // Sort by creation time
            pending.sort((a, b) => a.createdAt - b.createdAt);

            let processed = 0;
            const total = pending.length;

            for (const item of pending) {
                try {
                    await this.syncItem(item);
                    await this.storage.delete('pendingSync', item.id);
                    processed++;

                    window.dispatchEvent(new CustomEvent('sync-progress', {
                        detail: { current: processed, total }
                    }));
                } catch (error) {
                    console.error(`[SyncManager] Failed to sync item ${item.id}:`, error);

                    // Update retry count
                    item.attempts++;
                    item.lastError = error.message;
                    item.lastAttempt = Date.now();

                    if (item.attempts >= this.maxRetries) {
                        // Move to failed queue or notify user
                        console.error(`[SyncManager] Max retries reached for ${item.id}`);
                        window.dispatchEvent(new CustomEvent('sync-item-failed', {
                            detail: { item, error: error.message }
                        }));
                    } else {
                        await this.storage.put('pendingSync', item);
                    }
                }
            }

            window.dispatchEvent(new CustomEvent('sync-completed'));
        } catch (error) {
            console.error('[SyncManager] Sync failed:', error);
            window.dispatchEvent(new CustomEvent('sync-failed', {
                detail: { message: error.message }
            }));
        } finally {
            this.isSyncing = false;
            await this.checkPendingItems();
        }
    }

    /**
     * Sync a single item
     */
    async syncItem(item) {
        const response = await fetch(item.endpoint, {
            method: item.method,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
            body: item.method !== 'GET' ? JSON.stringify(item.data) : undefined,
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        return response.json();
    }

    /**
     * Handle sync conflicts
     */
    async resolveConflict(localItem, serverItem, resolution) {
        switch (resolution) {
            case 'keep-local':
                // Re-queue the local version
                await this.queueForSync(
                    localItem.type,
                    localItem.data,
                    localItem.endpoint,
                    'PUT'
                );
                break;
            case 'keep-server':
                // Update local storage with server version
                await this.storage.put(localItem.type + 's', serverItem);
                break;
            case 'merge':
                // Merge logic depends on data type
                const merged = this.mergeData(localItem.data, serverItem);
                await this.storage.put(localItem.type + 's', merged);
                await this.queueForSync(localItem.type, merged, localItem.endpoint, 'PUT');
                break;
        }
    }

    /**
     * Simple merge strategy
     */
    mergeData(local, server) {
        // Use server data as base, overlay local changes
        return {
            ...server,
            ...local,
            _mergedAt: Date.now(),
            _serverVersion: server.updated_at,
            _localVersion: local.updated_at,
        };
    }
}

/**
 * Mission Data Manager - High-level API for mission data
 */
class MissionDataManager {
    constructor() {
        this.storage = new OfflineStorage();
        this.syncManager = new SyncManager(this.storage);
    }

    async init() {
        await this.storage.init();
        this.syncManager.init();
    }

    /**
     * Get mission with offline fallback
     */
    async getMission(id) {
        // Try online first if available
        if (navigator.onLine) {
            try {
                const response = await fetch(`/api/v1/missions/${id}`);
                const data = await response.json();

                if (data.success) {
                    // Cache for offline use
                    await this.storage.put('missions', {
                        ...data.data,
                        syncStatus: 'synced',
                        cachedAt: Date.now()
                    });
                    return data.data;
                }
            } catch (e) {
                console.warn('[MissionDataManager] Online fetch failed, using cache');
            }
        }

        // Fallback to offline cache
        return this.storage.get('missions', id);
    }

    /**
     * Get all missions for offline use
     */
    async getMissions(filters = {}) {
        if (navigator.onLine) {
            try {
                const params = new URLSearchParams(filters);
                const response = await fetch(`/api/v1/missions?${params}`);
                const data = await response.json();

                if (data.success && data.data) {
                    // Cache all missions
                    await this.storage.bulkPut('missions', data.data.map(m => ({
                        ...m,
                        syncStatus: 'synced',
                        cachedAt: Date.now()
                    })));
                    return data.data;
                }
            } catch (e) {
                console.warn('[MissionDataManager] Online fetch failed, using cache');
            }
        }

        // Return cached missions
        let missions = await this.storage.getAll('missions');

        // Apply filters
        if (filters.status) {
            missions = missions.filter(m => m.status === filters.status);
        }

        return missions;
    }

    /**
     * Update mission (with offline support)
     */
    async updateMission(id, data) {
        const mission = await this.storage.get('missions', id) || { id };
        const updated = {
            ...mission,
            ...data,
            syncStatus: 'pending',
            updatedAt: Date.now()
        };

        await this.storage.put('missions', updated);

        if (navigator.onLine) {
            try {
                const response = await fetch(`/api/v1/missions/${id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify(data)
                });

                if (response.ok) {
                    updated.syncStatus = 'synced';
                    await this.storage.put('missions', updated);
                }
            } catch (e) {
                // Queue for sync
                await this.syncManager.queueForSync('mission', data, `/api/v1/missions/${id}`, 'PUT');
            }
        } else {
            // Queue for sync
            await this.syncManager.queueForSync('mission', data, `/api/v1/missions/${id}`, 'PUT');
        }

        return updated;
    }

    /**
     * Save checklist item (with offline support)
     */
    async saveChecklistItem(checklistId, itemId, data) {
        const cacheKey = `${checklistId}-${itemId}`;

        await this.storage.put('checklists', {
            id: cacheKey,
            checklistId,
            itemId,
            data,
            syncStatus: 'pending',
            updatedAt: Date.now()
        });

        if (navigator.onLine) {
            try {
                const response = await fetch(`/api/v1/checklists/${checklistId}/items/${itemId}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify(data)
                });

                if (response.ok) {
                    await this.storage.put('checklists', {
                        id: cacheKey,
                        checklistId,
                        itemId,
                        data,
                        syncStatus: 'synced',
                        updatedAt: Date.now()
                    });
                }
            } catch (e) {
                await this.syncManager.queueForSync(
                    'checklistItem',
                    { checklistId, itemId, data },
                    `/api/v1/checklists/${checklistId}/items/${itemId}`,
                    'PUT'
                );
            }
        } else {
            await this.syncManager.queueForSync(
                'checklistItem',
                { checklistId, itemId, data },
                `/api/v1/checklists/${checklistId}/items/${itemId}`,
                'PUT'
            );
        }
    }

    /**
     * Get pending sync count
     */
    async getPendingCount() {
        return this.storage.count('pendingSync');
    }

    /**
     * Clear all cached data
     */
    async clearCache() {
        await this.storage.clear('missions');
        await this.storage.clear('checklists');
        await this.storage.clear('photos');
        await this.storage.clear('cache');
    }
}

// Initialize global instance
window.offlineStorage = new OfflineStorage();
window.missionDataManager = new MissionDataManager();

// Auto-initialize on page load
document.addEventListener('DOMContentLoaded', async () => {
    try {
        await window.missionDataManager.init();
        console.log('[OfflineStorage] Initialized successfully');
    } catch (e) {
        console.error('[OfflineStorage] Initialization failed:', e);
    }
});
