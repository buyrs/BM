const CACHE_NAME = 'bail-mobilite-v1';
const STATIC_CACHE_NAME = 'static-v1';
const RUNTIME_CACHE_NAME = 'runtime-v1';

const urlsToCache = [
  '/',
  '/offline',
  '/css/app.css',
  '/js/app.js',
  '/images/icons/icon-192x192.png'
];

// Install event - cache static assets
self.addEventListener('install', event => {
  event.waitUntil(
    Promise.all([
      caches.open(STATIC_CACHE_NAME)
        .then(cache => {
          console.log('Static cache opened');
          return cache.addAll(urlsToCache);
        }),
      self.skipWaiting() // Force the waiting service worker to become the active one
    ])
  );
});

// Activate event - clean up old caches
self.addEventListener('activate', event => {
  event.waitUntil(
    Promise.all([
      caches.keys().then(cacheNames => {
        return Promise.all(
          cacheNames.map(cacheName => {
            if (cacheName !== STATIC_CACHE_NAME && cacheName !== RUNTIME_CACHE_NAME) {
              console.log('Deleting old cache:', cacheName);
              return caches.delete(cacheName);
            }
          })
        );
      }),
      self.clients.claim() // Take control of all clients immediately
    ])
  );
});

// Fetch event - handle network requests
self.addEventListener('fetch', event => {
  // Don't cache requests to external domains
  if (event.request.url.startsWith(self.location.origin)) {
    event.respondWith(
      caches.match(event.request).then(cachedResponse => {
        // Return cached version if available, otherwise fetch from network
        if (cachedResponse) {
          // Update cached version in the background
          event.waitUntil(updateCache(event.request));
          return cachedResponse;
        }
        
        return fetch(event.request).then(networkResponse => {
          // Add response to cache if it's a valid response
          if (networkResponse.status === 200) {
            updateCache(event.request, networkResponse.clone());
          }
          return networkResponse;
        }).catch(() => {
          // Return offline page if both cache and network fail
          return caches.match('/offline');
        });
      })
    );
  } else {
    // For external requests, just fetch from network
    event.respondWith(fetch(event.request));
  }
});

// Function to update cache
function updateCache(request, response) {
  return caches.open(RUNTIME_CACHE_NAME)
    .then(cache => {
      if (response) {
        return cache.put(request, response);
      } else {
        return fetch(request).then(networkResponse => {
          if (networkResponse.status === 200) {
            cache.put(request, networkResponse.clone());
          }
          return networkResponse;
        });
      }
    })
    .catch(error => {
      console.error('Failed to update cache:', error);
    });
}

// Handle background sync for offline data submission
self.addEventListener('sync', event => {
  if (event.tag === 'submit-checklist') {
    event.waitUntil(performChecklistSubmission());
  }
});

// Example background sync function
async function performChecklistSubmission() {
  try {
    const syncData = await getDeferredData('checklist-submissions');
    if (syncData && syncData.length > 0) {
      // Attempt to send each checklist submission
      for (const submission of syncData) {
        try {
          const response = await fetch(submission.url, {
            method: submission.method,
            headers: {
              'Content-Type': 'application/json',
              'X-Requested-With': 'XMLHttpRequest',
              'X-CSRF-TOKEN': submission.csrfToken
            },
            body: JSON.stringify(submission.data)
          });
          
          if (response.ok) {
            // Remove successfully submitted data from storage
            await removeDeferredData('checklist-submissions', submission.id);
          }
        } catch (error) {
          console.error('Failed to submit checklist:', error);
        }
      }
    }
  } catch (error) {
    console.error('Background sync failed:', error);
  }
}

// Helper functions for storing and retrieving offline data
async function getDeferredData(key) {
  return new Promise((resolve, reject) => {
    const data = self.localStorage.getItem(key);
    resolve(data ? JSON.parse(data) : []);
  });
}

async function removeDeferredData(key, id) {
  const data = await getDeferredData(key);
  const updatedData = data.filter(item => item.id !== id);
  self.localStorage.setItem(key, JSON.stringify(updatedData));
}

// Message handling for communication with the app
self.addEventListener('message', event => {
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  } else if (event.data && event.data.type === 'SYNC_DATA') {
    // Attempt to sync offline data to the server
    if ('sync' in self.registration) {
      self.registration.sync.register('submit-checklist');
    } else {
      // Fallback for browsers without background sync
      performChecklistSubmission();
    }
  }
});