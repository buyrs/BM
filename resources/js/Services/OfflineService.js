/**
 * Offline functionality service
 */

class OfflineService {
    constructor() {
        this.isOnline = navigator.onLine;
        this.listeners = new Set();
        this.pendingRequests = new Map();
        this.dbName = 'BMAppOfflineDB';
        this.dbVersion = 1;
        this.db = null;
        
        this.init();
    }
    
    async init() {
        // Listen for online/offline events
        window.addEventListener('online', this.handleOnline.bind(this));
        window.addEventListener('offline', this.handleOffline.bind(this));
        
        // Initialize IndexedDB
        await this.initDB();
        
        // Register service worker
        await this.registerServiceWorker();
    }
    
    async initDB() {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open(this.dbName, this.dbVersion);
            
            request.onerror = () => reject(request.error);
            request.onsuccess = () => {
                this.db = request.result;
                resolve(this.db);
            };
            
            request.onupgradeneeded = (event) => {
                const db = event.target.result;
                
                // Create object stores
                if (!db.objectStoreNames.contains('missions')) {
                    const missionStore = db.createObjectStore('missions', { keyPath: 'id', autoIncrement: true });
                    missionStore.createIndex('status', 'status', { unique: false });
                    missionStore.createIndex('timestamp', 'timestamp', { unique: false });
                }
                
                if (!db.objectStoreNames.contains('checklists')) {
                    const checklistStore = db.createObjectStore('checklists', { keyPath: 'id', autoIncrement: true });
                    checklistStore.createIndex('missionId', 'missionId', { unique: false });
                    checklistStore.createIndex('timestamp', 'timestamp', { unique: false });
                }
                
                if (!db.objectStoreNames.contains('pendingActions')) {
                    const actionStore = db.createObjectStore('pendingActions', { keyPath: 'id', autoIncrement: true });
                    actionStore.createIndex('type', 'type', { unique: false });
                    actionStore.createIndex('timestamp', 'timestamp', { unique: false });
                }
            };
        });
    }
    
    async registerServiceWorker() {
        if ('serviceWorker' in navigator) {
            try {
                const registration = await navigator.serviceWorker.register('/service-worker.js');
                console.log('Service Worker registered successfully:', registration);
                
                // Listen for service worker updates
                registration.addEventListener('updatefound', () => {
                    const newWorker = registration.installing;
                    newWorker.addEventListener('statechange', () => {
                        if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                            // New service worker available
                            this.notifyListeners('serviceWorkerUpdate', { registration });
                        }
                    });
                });
                
                return registration;
            } catch (error) {
                console.error('Service Worker registration failed:', error);
            }
        }
    }
    
    handleOnline() {
        console.log('App is back online');
        this.isOnline = true;
        this.notifyListeners('online');
        this.syncPendingActions();
    }
    
    handleOffline() {
        console.log('App is offline');
        this.isOnline = false;
        this.notifyListeners('offline');
    }
    
    // Add listener for online/offline events
    addListener(callback) {
        this.listeners.add(callback);
        return () => this.listeners.delete(callback);
    }
    
    notifyListeners(event, data = null) {
        this.listeners.forEach(callback => {
            try {
                callback(event, data);
            } catch (error) {
                console.error('Error in offline service listener:', error);
            }
        });
    }
    
    // Store data for offline access
    async storeOfflineData(storeName, data) {
        if (!this.db) await this.initDB();
        
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction([storeName], 'readwrite');
            const store = transaction.objectStore(storeName);
            
            const request = store.put({
                ...data,
                timestamp: Date.now(),
                synced: false
            });
            
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }
    
    // Get offline data
    async getOfflineData(storeName, query = null) {
        if (!this.db) await this.initDB();
        
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction([storeName], 'readonly');
            const store = transaction.objectStore(storeName);
            
            let request;
            if (query) {
                const index = store.index(query.index);
                request = index.getAll(query.value);
            } else {
                request = store.getAll();
            }
            
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }
    
    // Store pending action for later sync
    async storePendingAction(type, data, endpoint) {
        const action = {
            type,
            data,
            endpoint,
            timestamp: Date.now(),
            retries: 0,
            maxRetries: 3
        };
        
        await this.storeOfflineData('pendingActions', action);
        
        // Try to sync immediately if online
        if (this.isOnline) {
            setTimeout(() => this.syncPendingActions(), 1000);
        }
    }
    
    // Sync pending actions when back online
    async syncPendingActions() {
        if (!this.isOnline || !this.db) return;
        
        try {
            const pendingActions = await this.getOfflineData('pendingActions');
            
            for (const action of pendingActions) {
                try {
                    const response = await fetch(action.endpoint, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify(action.data)
                    });
                    
                    if (response.ok) {
                        await this.removePendingAction(action.id);
                        console.log('Synced pending action:', action.type);
                    } else {
                        throw new Error(`HTTP ${response.status}`);
                    }
                } catch (error) {
                    console.error('Failed to sync action:', error);
                    
                    // Increment retry count
                    action.retries++;
                    if (action.retries >= action.maxRetries) {
                        await this.removePendingAction(action.id);
                        console.log('Max retries reached, removing action:', action.type);
                    } else {
                        await this.storeOfflineData('pendingActions', action);
                    }
                }
            }
        } catch (error) {
            console.error('Error syncing pending actions:', error);
        }
    }
    
    async removePendingAction(id) {
        if (!this.db) return;
        
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction(['pendingActions'], 'readwrite');
            const store = transaction.objectStore('pendingActions');
            
            const request = store.delete(id);
            request.onsuccess = () => resolve();
            request.onerror = () => reject(request.error);
        });
    }
    
    // Cache important data for offline access
    async cacheForOffline(type, data) {
        switch (type) {
            case 'missions':
                await this.storeOfflineData('missions', data);
                break;
            case 'checklists':
                await this.storeOfflineData('checklists', data);
                break;
            default:
                console.warn('Unknown cache type:', type);
        }
    }
    
    // Get cached data when offline
    async getCachedData(type, query = null) {
        switch (type) {
            case 'missions':
                return await this.getOfflineData('missions', query);
            case 'checklists':
                return await this.getOfflineData('checklists', query);
            default:
                console.warn('Unknown cache type:', type);
                return [];
        }
    }
    
    // Check if a request can be made offline
    canMakeRequest(url) {
        if (this.isOnline) return true;
        
        // Check if it's a GET request for cached data
        const cachedEndpoints = ['/api/missions', '/api/checklists'];
        return cachedEndpoints.some(endpoint => url.includes(endpoint));
    }
    
    // Make a request with offline fallback
    async makeRequest(url, options = {}) {
        if (this.isOnline) {
            try {
                const response = await fetch(url, options);
                
                // Cache successful GET requests
                if (response.ok && options.method === 'GET') {
                    const data = await response.clone().json();
                    if (url.includes('/api/missions')) {
                        await this.cacheForOffline('missions', data);
                    } else if (url.includes('/api/checklists')) {
                        await this.cacheForOffline('checklists', data);
                    }
                }
                
                return response;
            } catch (error) {
                // Network error, try offline fallback
                return this.getOfflineResponse(url, options);
            }
        } else {
            return this.getOfflineResponse(url, options);
        }
    }
    
    async getOfflineResponse(url, options) {
        // For POST requests, store as pending action
        if (options.method === 'POST') {
            await this.storePendingAction('api_request', options.body, url);
            
            return new Response(JSON.stringify({
                success: true,
                message: 'Request queued for sync when online',
                offline: true
            }), {
                status: 202,
                headers: { 'Content-Type': 'application/json' }
            });
        }
        
        // For GET requests, try to serve from cache
        let cachedData = [];
        if (url.includes('/api/missions')) {
            cachedData = await this.getCachedData('missions');
        } else if (url.includes('/api/checklists')) {
            cachedData = await this.getCachedData('checklists');
        }
        
        return new Response(JSON.stringify({
            data: cachedData,
            message: 'Served from offline cache',
            offline: true
        }), {
            status: 200,
            headers: { 
                'Content-Type': 'application/json',
                'X-Served-From': 'offline-cache'
            }
        });
    }
    
    // Get offline status
    getStatus() {
        return {
            isOnline: this.isOnline,
            hasServiceWorker: 'serviceWorker' in navigator,
            hasIndexedDB: 'indexedDB' in window,
            pendingActionsCount: this.getPendingActionsCount()
        };
    }
    
    async getPendingActionsCount() {
        try {
            const actions = await this.getOfflineData('pendingActions');
            return actions.length;
        } catch (error) {
            return 0;
        }
    }
}

// Create singleton instance
export const offlineService = new OfflineService();
export default offlineService;