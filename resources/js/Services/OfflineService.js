class OfflineService {
  constructor() {
    this.dbName = 'CheckerOfflineDB';
    this.dbVersion = 1;
    this.db = null;
    this.isOnline = navigator.onLine;
    
    this.init();
    this.setupEventListeners();
  }

  async init() {
    try {
      this.db = await this.openDB();
      console.log('Offline database initialized');
    } catch (error) {
      console.error('Failed to initialize offline database:', error);
    }
  }

  openDB() {
    return new Promise((resolve, reject) => {
      const request = indexedDB.open(this.dbName, this.dbVersion);
      
      request.onerror = () => reject(request.error);
      request.onsuccess = () => resolve(request.result);
      
      request.onupgradeneeded = (event) => {
        const db = event.target.result;
        
        // Create missions store
        if (!db.objectStoreNames.contains('missions')) {
          const missionsStore = db.createObjectStore('missions', { keyPath: 'id' });
          missionsStore.createIndex('status', 'status', { unique: false });
          missionsStore.createIndex('scheduled_at', 'scheduled_at', { unique: false });
        }
        
        // Create checklists store
        if (!db.objectStoreNames.contains('checklists')) {
          const checklistsStore = db.createObjectStore('checklists', { keyPath: 'id' });
          checklistsStore.createIndex('mission_id', 'mission_id', { unique: false });
        }
        
        // Create photos store
        if (!db.objectStoreNames.contains('photos')) {
          const photosStore = db.createObjectStore('photos', { keyPath: 'id' });
          photosStore.createIndex('checklist_id', 'checklist_id', { unique: false });
        }
        
        // Create sync queue store
        if (!db.objectStoreNames.contains('syncQueue')) {
          const syncStore = db.createObjectStore('syncQueue', { keyPath: 'id', autoIncrement: true });
          syncStore.createIndex('timestamp', 'timestamp', { unique: false });
          syncStore.createIndex('type', 'type', { unique: false });
        }
      };
    });
  }

  setupEventListeners() {
    window.addEventListener('online', () => {
      this.isOnline = true;
      this.syncPendingData();
    });
    
    window.addEventListener('offline', () => {
      this.isOnline = false;
    });
  }

  // Cache missions for offline access
  async cacheMissions(missions) {
    if (!this.db) return;
    
    const transaction = this.db.transaction(['missions'], 'readwrite');
    const store = transaction.objectStore('missions');
    
    for (const mission of missions) {
      await store.put({
        ...mission,
        cached_at: new Date().toISOString(),
        offline_available: true
      });
    }
    
    console.log(`Cached ${missions.length} missions for offline access`);
  }

  // Cache mission details with full data
  async cacheMissionWithDetails(mission, checklists = [], photos = []) {
    if (!this.db) return;
    
    const transaction = this.db.transaction(['missions', 'checklists', 'photos'], 'readwrite');
    const missionStore = transaction.objectStore('missions');
    const checklistStore = transaction.objectStore('checklists');
    const photoStore = transaction.objectStore('photos');
    
    // Cache mission with full details
    await missionStore.put({
      ...mission,
      cached_at: new Date().toISOString(),
      offline_available: true,
      details_cached: true
    });
    
    // Cache related checklists
    for (const checklist of checklists) {
      await checklistStore.put({
        ...checklist,
        cached_at: new Date().toISOString(),
        offline_available: true
      });
    }
    
    // Cache related photos
    for (const photo of photos) {
      await photoStore.put({
        ...photo,
        cached_at: new Date().toISOString(),
        offline_available: true
      });
    }
    
    console.log(`Cached mission ${mission.id} with full details for offline access`);
  }

  // Get cached missions
  async getCachedMissions() {
    if (!this.db) return [];
    
    const transaction = this.db.transaction(['missions'], 'readonly');
    const store = transaction.objectStore('missions');
    
    return new Promise((resolve, reject) => {
      const request = store.getAll();
      request.onsuccess = () => {
        const missions = request.result.filter(mission => mission.offline_available);
        resolve(missions);
      };
      request.onerror = () => reject(request.error);
    });
  }

  // Get cached missions by status
  async getCachedMissionsByStatus(status) {
    if (!this.db) return [];
    
    const missions = await this.getCachedMissions();
    return missions.filter(mission => mission.status === status);
  }

  // Get today's cached missions
  async getTodaysCachedMissions() {
    if (!this.db) return [];
    
    const missions = await this.getCachedMissions();
    const today = new Date().toDateString();
    
    return missions.filter(mission => {
      const missionDate = new Date(mission.scheduled_at).toDateString();
      return missionDate === today;
    });
  }

  // Get urgent cached missions
  async getUrgentCachedMissions() {
    if (!this.db) return [];
    
    const missions = await this.getCachedMissions();
    const now = new Date();
    const twoHoursFromNow = new Date(now.getTime() + 2 * 60 * 60 * 1000);
    
    return missions.filter(mission => {
      const scheduledDate = new Date(mission.scheduled_at);
      return scheduledDate <= twoHoursFromNow && mission.status !== 'completed';
    });
  }

  // Cache mission details
  async cacheMissionDetails(missionId, details) {
    if (!this.db) return;
    
    const transaction = this.db.transaction(['missions'], 'readwrite');
    const store = transaction.objectStore('missions');
    
    const mission = await this.getMissionById(missionId);
    if (mission) {
      await store.put({
        ...mission,
        ...details,
        details_cached_at: new Date().toISOString()
      });
    }
  }

  // Get mission by ID
  async getMissionById(id) {
    if (!this.db) return null;
    
    const transaction = this.db.transaction(['missions'], 'readonly');
    const store = transaction.objectStore('missions');
    
    return new Promise((resolve, reject) => {
      const request = store.get(id);
      request.onsuccess = () => resolve(request.result);
      request.onerror = () => reject(request.error);
    });
  }

  // Save checklist data offline
  async saveChecklistOffline(checklistData) {
    if (!this.db) return;
    
    const transaction = this.db.transaction(['checklists', 'syncQueue'], 'readwrite');
    const checklistStore = transaction.objectStore('checklists');
    const syncStore = transaction.objectStore('syncQueue');
    
    // Save checklist
    const checklist = {
      ...checklistData,
      id: checklistData.id || Date.now(),
      offline_created: true,
      created_at: new Date().toISOString()
    };
    
    await checklistStore.put(checklist);
    
    // Add to sync queue
    await syncStore.add({
      type: 'checklist',
      action: 'create',
      data: checklist,
      timestamp: new Date().toISOString()
    });
    
    return checklist;
  }

  // Save photo offline
  async savePhotoOffline(photoData) {
    if (!this.db) return;
    
    const transaction = this.db.transaction(['photos', 'syncQueue'], 'readwrite');
    const photoStore = transaction.objectStore('photos');
    const syncStore = transaction.objectStore('syncQueue');
    
    const photo = {
      ...photoData,
      id: photoData.id || Date.now(),
      offline_created: true,
      created_at: new Date().toISOString()
    };
    
    await photoStore.put(photo);
    
    // Add to sync queue
    await syncStore.add({
      type: 'photo',
      action: 'create',
      data: photo,
      timestamp: new Date().toISOString()
    });
    
    return photo;
  }

  // Get pending sync items
  async getPendingSyncItems() {
    if (!this.db) return [];
    
    const transaction = this.db.transaction(['syncQueue'], 'readonly');
    const store = transaction.objectStore('syncQueue');
    
    return new Promise((resolve, reject) => {
      const request = store.getAll();
      request.onsuccess = () => resolve(request.result);
      request.onerror = () => reject(request.error);
    });
  }

  // Sync pending data when online
  async syncPendingData() {
    if (!this.isOnline || !this.db) return;
    
    const pendingItems = await this.getPendingSyncItems();
    
    for (const item of pendingItems) {
      try {
        await this.syncItem(item);
        await this.removeSyncItem(item.id);
      } catch (error) {
        console.error('Failed to sync item:', item, error);
      }
    }
  }

  // Sync individual item
  async syncItem(item) {
    const { type, action, data } = item;
    
    switch (type) {
      case 'checklist':
        if (action === 'create') {
          await this.syncChecklist(data);
        }
        break;
      case 'photo':
        if (action === 'create') {
          await this.syncPhoto(data);
        }
        break;
    }
  }

  // Sync checklist to server
  async syncChecklist(checklistData) {
    const response = await fetch('/api/checklists', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
      },
      body: JSON.stringify(checklistData)
    });
    
    if (!response.ok) {
      throw new Error('Failed to sync checklist');
    }
    
    return response.json();
  }

  // Sync photo to server
  async syncPhoto(photoData) {
    const formData = new FormData();
    
    // Convert base64 to blob if needed
    if (photoData.image_data && photoData.image_data.startsWith('data:')) {
      const response = await fetch(photoData.image_data);
      const blob = await response.blob();
      formData.append('photo', blob, 'photo.jpg');
    }
    
    formData.append('checklist_id', photoData.checklist_id);
    formData.append('description', photoData.description || '');
    
    const response = await fetch('/api/checklist-photos', {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
      },
      body: formData
    });
    
    if (!response.ok) {
      throw new Error('Failed to sync photo');
    }
    
    return response.json();
  }

  // Remove synced item from queue
  async removeSyncItem(id) {
    if (!this.db) return;
    
    const transaction = this.db.transaction(['syncQueue'], 'readwrite');
    const store = transaction.objectStore('syncQueue');
    
    await store.delete(id);
  }

  // Check if data is available offline
  async isDataAvailableOffline(type, id = null) {
    if (!this.db) return false;
    
    const transaction = this.db.transaction([type], 'readonly');
    const store = transaction.objectStore(type);
    
    if (id) {
      const request = store.get(id);
      return new Promise((resolve) => {
        request.onsuccess = () => resolve(!!request.result);
        request.onerror = () => resolve(false);
      });
    } else {
      const request = store.count();
      return new Promise((resolve) => {
        request.onsuccess = () => resolve(request.result > 0);
        request.onerror = () => resolve(false);
      });
    }
  }

  // Clear old cached data
  async clearOldCache(daysOld = 7) {
    if (!this.db) return;
    
    const cutoffDate = new Date();
    cutoffDate.setDate(cutoffDate.getDate() - daysOld);
    
    const transaction = this.db.transaction(['missions'], 'readwrite');
    const store = transaction.objectStore('missions');
    
    const request = store.openCursor();
    request.onsuccess = (event) => {
      const cursor = event.target.result;
      if (cursor) {
        const mission = cursor.value;
        const cachedAt = new Date(mission.cached_at);
        
        if (cachedAt < cutoffDate) {
          cursor.delete();
        }
        
        cursor.continue();
      }
    };
  }

  // Get storage usage
  async getStorageUsage() {
    if ('storage' in navigator && 'estimate' in navigator.storage) {
      return await navigator.storage.estimate();
    }
    return null;
  }

  // Get offline statistics
  async getOfflineStats() {
    if (!this.db) return null;
    
    const missions = await this.getCachedMissions();
    const checklists = await this.getCachedChecklists();
    const photos = await this.getCachedPhotos();
    const pendingSync = await this.getPendingSyncItems();
    
    return {
      cachedMissions: missions.length,
      cachedChecklists: checklists.length,
      cachedPhotos: photos.length,
      pendingSyncItems: pendingSync.length,
      lastSync: this.getLastSyncTime(),
      storageUsage: await this.getStorageUsage()
    };
  }

  // Get cached checklists
  async getCachedChecklists() {
    if (!this.db) return [];
    
    const transaction = this.db.transaction(['checklists'], 'readonly');
    const store = transaction.objectStore('checklists');
    
    return new Promise((resolve, reject) => {
      const request = store.getAll();
      request.onsuccess = () => resolve(request.result);
      request.onerror = () => reject(request.error);
    });
  }

  // Get cached photos
  async getCachedPhotos() {
    if (!this.db) return [];
    
    const transaction = this.db.transaction(['photos'], 'readonly');
    const store = transaction.objectStore('photos');
    
    return new Promise((resolve, reject) => {
      const request = store.getAll();
      request.onsuccess = () => resolve(request.result);
      request.onerror = () => reject(request.error);
    });
  }

  // Get last sync time
  getLastSyncTime() {
    return localStorage.getItem('lastSyncTime') || null;
  }

  // Set last sync time
  setLastSyncTime() {
    localStorage.setItem('lastSyncTime', new Date().toISOString());
  }

  // Check if mission is available offline
  async isMissionAvailableOffline(missionId) {
    const mission = await this.getMissionById(missionId);
    return mission && mission.offline_available;
  }

  // Preload critical missions for offline access
  async preloadCriticalMissions() {
    if (this.isOnline) {
      try {
        // Get today's missions and urgent missions
        const response = await fetch('/missions/api/critical', {
          headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
          }
        });
        
        if (response.ok) {
          const data = await response.json();
          await this.cacheMissions(data.missions);
          
          // Cache detailed data for each mission
          for (const mission of data.missions) {
            if (mission.checklists) {
              await this.cacheMissionWithDetails(mission, mission.checklists, mission.photos || []);
            }
          }
          
          this.setLastSyncTime();
          console.log('Critical missions preloaded for offline access');
        }
      } catch (error) {
        console.error('Failed to preload critical missions:', error);
      }
    }
  }
}

// Export singleton instance
export default new OfflineService();