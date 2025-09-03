<template>
  <nav class="mobile-nav" :class="{ 'nav-open': isOpen }">
    <!-- Mobile Header -->
    <div class="mobile-header safe-area-top">
      <div class="header-content">
        <button
          @click="toggleNav"
          class="nav-toggle touch-target touch-manipulation"
          :aria-expanded="isOpen"
          aria-label="Toggle navigation"
        >
          <div class="hamburger" :class="{ 'hamburger-open': isOpen }">
            <span></span>
            <span></span>
            <span></span>
          </div>
        </button>
        
        <div class="header-title">
          <h1 class="app-title">{{ appTitle }}</h1>
          <p v-if="subtitle" class="app-subtitle">{{ subtitle }}</p>
        </div>
        
        <div class="header-actions">
          <!-- Offline indicator -->
          <div v-if="!isOnline" class="offline-badge">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-12.728 12.728m0 0L5.636 18.364m12.728-12.728L5.636 5.636m12.728 12.728L18.364 18.364" />
            </svg>
            <span class="sr-only">Offline</span>
          </div>
          
          <!-- Notifications -->
          <button
            v-if="notificationCount > 0"
            @click="$emit('showNotifications')"
            class="notification-btn touch-target touch-manipulation"
            :aria-label="`${notificationCount} notifications`"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM10.97 4.97a.235.235 0 0 0-.02.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.061L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-1.071-1.05z" />
            </svg>
            <span class="notification-badge">{{ notificationCount }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Navigation Overlay -->
    <div
      v-if="isOpen"
      class="nav-overlay"
      @click="closeNav"
      @touchstart="handleOverlayTouch"
    />

    <!-- Navigation Drawer -->
    <div class="nav-drawer" :class="{ 'drawer-open': isOpen }">
      <div class="drawer-content safe-area-all">
        <!-- User Profile Section -->
        <div class="user-profile">
          <div class="user-avatar">
            <img
              v-if="user?.avatar"
              :src="user.avatar"
              :alt="user.name"
              class="avatar-image"
            />
            <div v-else class="avatar-placeholder">
              {{ user?.name?.charAt(0) || 'U' }}
            </div>
          </div>
          <div class="user-info">
            <h3 class="user-name">{{ user?.name || 'User' }}</h3>
            <p class="user-role">{{ user?.role || 'Checker' }}</p>
          </div>
        </div>

        <!-- Navigation Menu -->
        <div class="nav-menu">
          <div
            v-for="section in navigationSections"
            :key="section.title"
            class="nav-section"
          >
            <h4 v-if="section.title" class="section-title">{{ section.title }}</h4>
            <ul class="nav-list">
              <li
                v-for="item in section.items"
                :key="item.name"
                class="nav-item"
              >
                <component
                  :is="item.href ? 'a' : 'button'"
                  :href="item.href"
                  @click="handleNavClick(item)"
                  class="nav-link touch-target touch-manipulation"
                  :class="{ 'nav-link-active': isActiveRoute(item.route) }"
                >
                  <div class="nav-icon">
                    <component :is="item.icon" class="w-5 h-5" />
                  </div>
                  <span class="nav-text">{{ item.name }}</span>
                  <div v-if="item.badge" class="nav-badge">{{ item.badge }}</div>
                  <svg
                    v-if="item.children"
                    class="nav-arrow w-4 h-4"
                    :class="{ 'arrow-open': item.isOpen }"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                  </svg>
                </component>
                
                <!-- Submenu -->
                <ul v-if="item.children && item.isOpen" class="nav-submenu">
                  <li
                    v-for="child in item.children"
                    :key="child.name"
                    class="nav-subitem"
                  >
                    <component
                      :is="child.href ? 'a' : 'button'"
                      :href="child.href"
                      @click="handleNavClick(child)"
                      class="nav-sublink touch-target touch-manipulation"
                      :class="{ 'nav-link-active': isActiveRoute(child.route) }"
                    >
                      <span class="nav-text">{{ child.name }}</span>
                      <div v-if="child.badge" class="nav-badge">{{ child.badge }}</div>
                    </component>
                  </li>
                </ul>
              </li>
            </ul>
          </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
          <h4 class="section-title">Quick Actions</h4>
          <div class="action-grid">
            <button
              v-for="action in quickActions"
              :key="action.name"
              @click="handleActionClick(action)"
              class="action-btn touch-target touch-manipulation touch-feedback"
            >
              <component :is="action.icon" class="w-6 h-6" />
              <span class="action-text">{{ action.name }}</span>
            </button>
          </div>
        </div>

        <!-- App Info -->
        <div class="app-info">
          <div class="sync-status">
            <div class="sync-indicator" :class="syncStatusClass">
              <div class="sync-dot"></div>
              <span class="sync-text">{{ syncStatusText }}</span>
            </div>
            <button
              v-if="pendingSyncCount > 0"
              @click="$emit('syncNow')"
              class="sync-btn touch-target"
            >
              Sync {{ pendingSyncCount }} items
            </button>
          </div>
          
          <div class="app-version">
            <span class="version-text">Version {{ appVersion }}</span>
            <span v-if="!isOnline" class="offline-text">Offline Mode</span>
          </div>
        </div>
      </div>
    </div>
  </nav>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { useTouchInteractions } from '@/Composables/useTouchInteractions.js'

const props = defineProps({
  user: {
    type: Object,
    default: () => ({})
  },
  appTitle: {
    type: String,
    default: 'BM Manager'
  },
  subtitle: {
    type: String,
    default: ''
  },
  notificationCount: {
    type: Number,
    default: 0
  },
  pendingSyncCount: {
    type: Number,
    default: 0
  },
  appVersion: {
    type: String,
    default: '1.0.0'
  }
})

const emit = defineEmits(['showNotifications', 'syncNow', 'navigate'])

// Navigation state
const isOpen = ref(false)
const isOnline = ref(navigator.onLine)

// Navigation configuration
const navigationSections = ref([
  {
    title: 'Main',
    items: [
      {
        name: 'Dashboard',
        route: 'dashboard',
        href: '/dashboard',
        icon: 'HomeIcon'
      },
      {
        name: 'Missions',
        route: 'missions',
        href: '/missions',
        icon: 'ClipboardListIcon',
        badge: props.pendingSyncCount > 0 ? props.pendingSyncCount : null
      },
      {
        name: 'Checklists',
        route: 'checklists',
        href: '/checklists',
        icon: 'CheckCircleIcon'
      }
    ]
  },
  {
    title: 'Tools',
    items: [
      {
        name: 'Contracts',
        route: 'contracts',
        href: '/contracts',
        icon: 'DocumentTextIcon'
      },
      {
        name: 'Photos',
        route: 'photos',
        href: '/photos',
        icon: 'CameraIcon'
      }
    ]
  }
])

const quickActions = ref([
  {
    name: 'New Checklist',
    action: 'create-checklist',
    icon: 'PlusCircleIcon'
  },
  {
    name: 'Take Photo',
    action: 'take-photo',
    icon: 'CameraIcon'
  },
  {
    name: 'Sign Contract',
    action: 'sign-contract',
    icon: 'PencilIcon'
  },
  {
    name: 'Emergency',
    action: 'emergency',
    icon: 'ExclamationTriangleIcon'
  }
])

// Computed properties
const syncStatusClass = computed(() => ({
  'sync-online': isOnline.value && props.pendingSyncCount === 0,
  'sync-pending': isOnline.value && props.pendingSyncCount > 0,
  'sync-offline': !isOnline.value
}))

const syncStatusText = computed(() => {
  if (!isOnline.value) return 'Offline'
  if (props.pendingSyncCount > 0) return 'Syncing...'
  return 'Synced'
})

// Navigation methods
const toggleNav = () => {
  isOpen.value = !isOpen.value
  
  // Prevent body scroll when nav is open
  if (isOpen.value) {
    document.body.style.overflow = 'hidden'
  } else {
    document.body.style.overflow = ''
  }
}

const closeNav = () => {
  isOpen.value = false
  document.body.style.overflow = ''
}

const handleNavClick = (item) => {
  if (item.children) {
    item.isOpen = !item.isOpen
  } else {
    emit('navigate', item)
    closeNav()
  }
}

const handleActionClick = (action) => {
  emit('navigate', action)
  closeNav()
}

const isActiveRoute = (route) => {
  const currentRoute = usePage().component
  return currentRoute.includes(route)
}

// Touch handling for overlay
const handleOverlayTouch = (event) => {
  // Allow swipe to close
  const startX = event.touches[0].clientX
  
  const handleTouchMove = (moveEvent) => {
    const currentX = moveEvent.touches[0].clientX
    const deltaX = currentX - startX
    
    // If swiping left (closing gesture)
    if (deltaX < -50) {
      closeNav()
      document.removeEventListener('touchmove', handleTouchMove)
      document.removeEventListener('touchend', handleTouchEnd)
    }
  }
  
  const handleTouchEnd = () => {
    document.removeEventListener('touchmove', handleTouchMove)
    document.removeEventListener('touchend', handleTouchEnd)
  }
  
  document.addEventListener('touchmove', handleTouchMove, { passive: true })
  document.addEventListener('touchend', handleTouchEnd, { passive: true })
}

// Online/offline detection
const handleOnline = () => {
  isOnline.value = true
}

const handleOffline = () => {
  isOnline.value = false
}

// Lifecycle
onMounted(() => {
  window.addEventListener('online', handleOnline)
  window.addEventListener('offline', handleOffline)
  
  // Close nav on escape key
  const handleEscape = (event) => {
    if (event.key === 'Escape' && isOpen.value) {
      closeNav()
    }
  }
  document.addEventListener('keydown', handleEscape)
})

onUnmounted(() => {
  window.removeEventListener('online', handleOnline)
  window.removeEventListener('offline', handleOffline)
  document.body.style.overflow = ''
})
</script>

<style scoped>
.mobile-nav {
  @apply relative z-50;
}

.mobile-header {
  @apply fixed top-0 left-0 right-0 bg-white border-b border-gray-200 z-40;
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
}

.header-content {
  @apply flex items-center justify-between px-4 py-3;
}

.nav-toggle {
  @apply p-2 rounded-lg hover:bg-gray-100 transition-colors;
}

.hamburger {
  @apply w-6 h-6 flex flex-col justify-center items-center;
}

.hamburger span {
  @apply block w-5 h-0.5 bg-gray-600 transition-all duration-300 ease-in-out;
}

.hamburger span:not(:last-child) {
  @apply mb-1;
}

.hamburger-open span:nth-child(1) {
  @apply transform rotate-45 translate-y-1.5;
}

.hamburger-open span:nth-child(2) {
  @apply opacity-0;
}

.hamburger-open span:nth-child(3) {
  @apply transform -rotate-45 -translate-y-1.5;
}

.header-title {
  @apply flex-1 text-center;
}

.app-title {
  @apply text-lg font-semibold text-gray-900;
}

.app-subtitle {
  @apply text-sm text-gray-500;
}

.header-actions {
  @apply flex items-center space-x-2;
}

.offline-badge {
  @apply flex items-center justify-center w-8 h-8 bg-warning-bg text-warning-text rounded-full;
}

.notification-btn {
  @apply relative p-2 rounded-lg hover:bg-gray-100 transition-colors;
}

.notification-badge {
  @apply absolute -top-1 -right-1 w-5 h-5 bg-error-text text-white text-xs font-bold rounded-full flex items-center justify-center;
}

.nav-overlay {
  @apply fixed inset-0 bg-black bg-opacity-50 z-30;
  backdrop-filter: blur(2px);
  -webkit-backdrop-filter: blur(2px);
}

.nav-drawer {
  @apply fixed top-0 left-0 h-full w-80 max-w-sm bg-white transform -translate-x-full transition-transform duration-300 ease-in-out z-40;
  box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
}

.drawer-open {
  @apply translate-x-0;
}

.drawer-content {
  @apply h-full overflow-y-auto flex flex-col;
  -webkit-overflow-scrolling: touch;
}

.user-profile {
  @apply flex items-center p-6 bg-gradient-to-r from-primary to-accent text-white;
}

.user-avatar {
  @apply w-12 h-12 rounded-full overflow-hidden mr-4 flex-shrink-0;
}

.avatar-image {
  @apply w-full h-full object-cover;
}

.avatar-placeholder {
  @apply w-full h-full bg-white bg-opacity-20 flex items-center justify-center text-lg font-bold;
}

.user-info {
  @apply flex-1 min-w-0;
}

.user-name {
  @apply text-lg font-semibold truncate;
}

.user-role {
  @apply text-sm opacity-90 truncate;
}

.nav-menu {
  @apply flex-1 py-4;
}

.nav-section {
  @apply mb-6;
}

.section-title {
  @apply px-6 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider;
}

.nav-list {
  @apply space-y-1;
}

.nav-item {
  @apply px-3;
}

.nav-link {
  @apply flex items-center w-full px-3 py-3 text-gray-700 rounded-lg hover:bg-gray-100 transition-colors;
}

.nav-link-active {
  @apply bg-primary bg-opacity-10 text-primary font-medium;
}

.nav-icon {
  @apply mr-3 flex-shrink-0;
}

.nav-text {
  @apply flex-1 truncate;
}

.nav-badge {
  @apply ml-2 px-2 py-1 bg-error-text text-white text-xs font-bold rounded-full;
}

.nav-arrow {
  @apply ml-2 transform transition-transform;
}

.arrow-open {
  @apply rotate-90;
}

.nav-submenu {
  @apply mt-2 ml-8 space-y-1;
}

.nav-sublink {
  @apply flex items-center w-full px-3 py-2 text-sm text-gray-600 rounded-lg hover:bg-gray-50 transition-colors;
}

.quick-actions {
  @apply px-6 py-4 border-t border-gray-200;
}

.action-grid {
  @apply grid grid-cols-2 gap-3 mt-3;
}

.action-btn {
  @apply flex flex-col items-center justify-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors;
}

.action-text {
  @apply mt-2 text-xs font-medium text-gray-700 text-center;
}

.app-info {
  @apply px-6 py-4 border-t border-gray-200 bg-gray-50;
}

.sync-status {
  @apply flex items-center justify-between mb-2;
}

.sync-indicator {
  @apply flex items-center;
}

.sync-dot {
  @apply w-2 h-2 rounded-full mr-2;
}

.sync-online .sync-dot {
  @apply bg-success-text;
}

.sync-pending .sync-dot {
  @apply bg-warning-text animate-pulse;
}

.sync-offline .sync-dot {
  @apply bg-error-text;
}

.sync-text {
  @apply text-sm text-gray-600;
}

.sync-btn {
  @apply text-xs text-primary font-medium;
}

.app-version {
  @apply flex items-center justify-between;
}

.version-text {
  @apply text-xs text-gray-500;
}

.offline-text {
  @apply text-xs text-warning-text font-medium;
}

/* Mobile optimizations */
@media (max-width: 640px) {
  .nav-drawer {
    @apply w-full max-w-none;
  }
  
  .action-grid {
    @apply grid-cols-4;
  }
  
  .action-btn {
    @apply p-3;
  }
  
  .action-text {
    @apply text-xs;
  }
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
  .mobile-header {
    @apply bg-gray-900 border-gray-700;
  }
  
  .nav-drawer {
    @apply bg-gray-900;
  }
  
  .nav-link {
    @apply text-gray-300 hover:bg-gray-800;
  }
  
  .action-btn {
    @apply bg-gray-800 hover:bg-gray-700;
  }
  
  .app-info {
    @apply bg-gray-800 border-gray-700;
  }
}
</style>