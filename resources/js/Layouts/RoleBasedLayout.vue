<template>
  <div class="flex min-h-screen bg-gray-50">
    <!-- Mobile Menu Overlay -->
    <div 
      v-if="showMobileMenu" 
      class="fixed inset-0 z-50 lg:hidden"
      @click="showMobileMenu = false"
    >
      <div class="fixed inset-0 bg-black opacity-50"></div>
    </div>

    <!-- Sidebar -->
    <aside 
      :class="[
        'w-64 bg-white shadow-lg flex-shrink-0 transition-transform duration-300 ease-in-out',
        'lg:translate-x-0',
        showMobileMenu ? 'translate-x-0' : '-translate-x-full',
        'lg:static fixed inset-y-0 left-0 z-50'
      ]"
    >
      <!-- Logo Section -->
      <div class="p-6 border-b border-gray-200">
        <div class="flex items-center gap-3">
          <svg class="text-indigo-600" fill="none" height="32" viewBox="0 0 48 48" width="32">
            <path d="M13.8261 30.5736C16.7203 29.8826 20.2244 29.4783 24 29.4783C27.7756 29.4783 31.2797 29.8826 34.1739 30.5736C36.9144 31.2278 39.9967 32.7669 41.3563 33.8352L24.8486 7.36089C24.4571 6.73303 23.5429 6.73303 23.1514 7.36089L6.64374 33.8352C8.00331 32.7669 11.0856 31.2278 13.8261 30.5736Z" fill="currentColor"></path>
            <path clip-rule="evenodd" d="M39.998 35.764C39.9944 35.7463 39.9875 35.7155 39.9748 35.6706C39.9436 35.5601 39.8949 35.4259 39.8346 35.2825C39.8168 35.2403 39.7989 35.1993 39.7813 35.1602C38.5103 34.2887 35.9788 33.0607 33.7095 32.5189C30.9875 31.8691 27.6413 31.4783 24 31.4783C20.3587 31.4783 17.0125 31.8691 14.2905 32.5189C12.0012 33.0654 9.44505 34.3104 8.18538 35.1832C8.17384 35.2075 8.16216 35.233 8.15052 35.2592C8.09919 35.3751 8.05721 35.4886 8.02977 35.589C8.00356 35.6848 8.00039 35.7333 8.00004 35.7388C8.00004 35.739 8 35.7393 8.00004 35.7388C8.00004 35.7641 8.0104 36.0767 8.68485 36.6314C9.34546 37.1746 10.4222 37.7531 11.9291 38.2772C14.9242 39.319 19.1919 40 24 40C28.8081 40 33.0758 39.319 36.0709 38.2772C37.5778 37.7531 38.6545 37.1746 39.3151 36.6314C39.9006 36.1499 39.9857 35.8511 39.998 35.764ZM4.95178 32.7688L21.4543 6.30267C22.6288 4.4191 25.3712 4.41909 26.5457 6.30267L43.0534 32.777C43.0709 32.8052 43.0878 32.8338 43.104 32.8629L41.3563 33.8352C43.104 32.8629 43.1038 32.8626 43.104 32.8629L43.1051 32.865L43.1065 32.8675L43.1101 32.8739L43.1199 32.8918C43.1276 32.906 43.1377 32.9246 43.1497 32.9473C43.1738 32.9925 43.2062 33.0545 43.244 33.1299C43.319 33.2792 43.4196 33.489 43.5217 33.7317C43.6901 34.1321 44 34.9311 44 35.7391C44 37.4427 43.003 38.7775 41.8558 39.7209C40.6947 40.6757 39.1354 41.4464 37.385 42.0552C33.8654 43.2794 29.133 44 24 44C18.867 44 14.1346 43.2794 10.615 42.0552C8.86463 41.4464 7.30529 40.6757 6.14419 39.7209C4.99695 38.7775 3.99999 37.4427 3.99999 35.7391C3.99999 34.8725 4.29264 34.0922 4.49321 33.6393C4.60375 33.3898 4.71348 33.1804 4.79687 33.0311C4.83898 32.9556 4.87547 32.8935 4.9035 32.8471C4.91754 32.8238 4.92954 32.8043 4.93916 32.7889L4.94662 32.777L4.95178 32.7688ZM35.9868 29.004L24 9.77997L12.0131 29.004C12.4661 28.8609 12.9179 28.7342 13.3617 28.6282C16.4281 27.8961 20.0901 27.4783 24 27.4783C27.9099 27.4783 31.5719 27.8961 34.6383 28.6282C35.082 28.7342 35.5339 28.8609 35.9868 29.004Z" fill="currentColor" fill-rule="evenodd"></path>
          </svg>
          <h1 class="text-xl font-bold text-gray-900">Bail Mobilité</h1>
        </div>
        <div class="mt-2">
          <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
            {{ userRoleDisplay }}
          </span>
        </div>
      </div>
      
      <!-- Navigation -->
      <nav class="mt-6 px-4 space-y-1">
        <!-- Dashboard Link -->
        <Link 
          :href="primaryDashboardRoute" 
          :class="[
            'group flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors duration-200',
            route().current(primaryDashboardRoute) 
              ? 'bg-indigo-100 text-indigo-700'
              : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900'
          ]"
          @click="showMobileMenu = false"
        >
          <HomeIcon class="mr-3 h-5 w-5" />
          Dashboard
        </Link>

        <!-- Role-specific Navigation -->
        <template v-if="isSuperAdmin || isAdmin">
          <!-- Admin Navigation -->
          <div class="pt-4">
            <h3 class="px-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Administration</h3>
            <div class="mt-2 space-y-1">
              <Link 
                :href="route('admin.checkers')" 
                :class="[
                  'group flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors duration-200',
                  route().current('admin.checkers') 
                    ? 'bg-indigo-100 text-indigo-700'
                    : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900'
                ]"
                @click="showMobileMenu = false"
              >
                <UsersIcon class="mr-3 h-5 w-5" />
                Checkers
              </Link>
              
              <Link 
                :href="route('admin.analytics.data')" 
                :class="[
                  'group flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors duration-200',
                  route().current('admin.analytics.*') 
                    ? 'bg-indigo-100 text-indigo-700'
                    : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900'
                ]"
                @click="showMobileMenu = false"
              >
                <ChartBarIcon class="mr-3 h-5 w-5" />
                Analytics
              </Link>
              
              <Link 
                :href="route('admin.contract-templates.index')" 
                :class="[
                  'group flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors duration-200',
                  route().current('admin.contract-templates.*') 
                    ? 'bg-indigo-100 text-indigo-700'
                    : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900'
                ]"
                @click="showMobileMenu = false"
              >
                <DocumentTextIcon class="mr-3 h-5 w-5" />
                Templates
              </Link>
              
              <Link 
                :href="route('admin.role-management')" 
                :class="[
                  'group flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors duration-200',
                  route().current('admin.role-management') 
                    ? 'bg-indigo-100 text-indigo-700'
                    : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900'
                ]"
                @click="showMobileMenu = false"
              >
                <ShieldCheckIcon class="mr-3 h-5 w-5" />
                Role Management
              </Link>
            </div>
          </div>
        </template>

        <template v-if="isOps || isAdmin">
          <!-- Ops Navigation -->
          <div class="pt-4">
            <h3 class="px-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Operations</h3>
            <div class="mt-2 space-y-1">
              <Link 
                :href="route('ops.bail-mobilites.index')" 
                :class="[
                  'group flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors duration-200',
                  route().current('ops.bail-mobilites.*') 
                    ? 'bg-indigo-100 text-indigo-700'
                    : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900'
                ]"
                @click="showMobileMenu = false"
              >
                <BuildingOfficeIcon class="mr-3 h-5 w-5" />
                Bail Mobilités
              </Link>
              
              <Link 
                :href="route('ops.calendar.index')" 
                :class="[
                  'group flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors duration-200',
                  route().current('ops.calendar.*') 
                    ? 'bg-indigo-100 text-indigo-700'
                    : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900'
                ]"
                @click="showMobileMenu = false"
              >
                <CalendarIcon class="mr-3 h-5 w-5" />
                Calendar
              </Link>
              
              <Link 
                :href="route('ops.incidents.index')" 
                :class="[
                  'group flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors duration-200',
                  route().current('ops.incidents.*') 
                    ? 'bg-indigo-100 text-indigo-700'
                    : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900'
                ]"
                @click="showMobileMenu = false"
              >
                <ExclamationTriangleIcon class="mr-3 h-5 w-5" />
                Incidents
              </Link>
              
              <Link 
                :href="route('ops.notifications')" 
                :class="[
                  'group flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors duration-200',
                  route().current('ops.notifications') 
                    ? 'bg-indigo-100 text-indigo-700'
                    : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900'
                ]"
                @click="showMobileMenu = false"
              >
                <BellIcon class="mr-3 h-5 w-5" />
                Notifications
              </Link>
            </div>
          </div>
        </template>

        <!-- Common Navigation -->
        <div class="pt-4">
          <h3 class="px-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">General</h3>
          <div class="mt-2 space-y-1">
            <Link 
              :href="route('missions.index')" 
              :class="[
                'group flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors duration-200',
                route().current('missions.*') 
                  ? 'bg-indigo-100 text-indigo-700'
                  : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900'
              ]"
              @click="showMobileMenu = false"
            >
              <ClipboardDocumentListIcon class="mr-3 h-5 w-5" />
              Missions
            </Link>
            
            <Link 
              :href="route('profile.edit')" 
              :class="[
                'group flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors duration-200',
                route().current('profile.*') 
                  ? 'bg-indigo-100 text-indigo-700'
                  : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900'
              ]"
              @click="showMobileMenu = false"
            >
              <UserIcon class="mr-3 h-5 w-5" />
              Profile
            </Link>
          </div>
        </div>
      </nav>
    </aside>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col lg:ml-0">
      <!-- Header -->
      <header class="bg-white shadow-sm border-b border-gray-200">
        <div class="px-4 sm:px-6 lg:px-8">
          <div class="flex justify-between h-16">
            <!-- Mobile menu button -->
            <button
              @click="showMobileMenu = !showMobileMenu"
              class="lg:hidden p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"
            >
              <Bars3Icon class="h-6 w-6" />
            </button>
            
            <!-- Page title -->
            <div class="flex-1 flex items-center">
              <slot name="header" />
            </div>
            
            <!-- Right side -->
            <div class="flex items-center space-x-4">
              <!-- Notifications -->
              <button class="relative p-2 text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 rounded-md">
                <BellIcon class="h-6 w-6" />
                <span v-if="notificationCount > 0" class="absolute -top-1 -right-1 h-4 w-4 bg-red-500 text-white text-xs rounded-full flex items-center justify-center">
                  {{ notificationCount }}
                </span>
              </button>
              
              <!-- User menu -->
              <div class="relative">
                <Dropdown align="right" width="48">
                  <template #trigger>
                    <button class="flex items-center text-sm rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                      <div class="h-8 w-8 rounded-full bg-gray-300 flex items-center justify-center">
                        <span class="text-sm font-medium text-gray-700">
                          {{ $page.props.auth.user.name.charAt(0).toUpperCase() }}
                        </span>
                      </div>
                      <div class="ml-3 hidden md:block">
                        <p class="text-sm font-medium text-gray-700">{{ $page.props.auth.user.name }}</p>
                        <p class="text-xs text-gray-500">{{ userRoleDisplay }}</p>
                      </div>
                      <ChevronDownIcon class="ml-2 h-4 w-4 text-gray-400" />
                    </button>
                  </template>
                  
                  <template #content>
                    <DropdownLink :href="route('profile.edit')">
                      Profile
                    </DropdownLink>
                    <DropdownLink :href="route('logout')" method="post" as="button">
                      Log Out
                    </DropdownLink>
                  </template>
                </Dropdown>
              </div>
            </div>
          </div>
        </div>
      </header>

      <!-- Page Content -->
      <main class="flex-1 py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <slot />
        </div>
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import Dropdown from '@/Components/Dropdown.vue'
import DropdownLink from '@/Components/DropdownLink.vue'
import {
  HomeIcon,
  UsersIcon,
  ChartBarIcon,
  DocumentTextIcon,
  ShieldCheckIcon,
  BuildingOfficeIcon,
  CalendarIcon,
  ExclamationTriangleIcon,
  BellIcon,
  ClipboardDocumentListIcon,
  UserIcon,
  Bars3Icon,
  ChevronDownIcon
} from '@heroicons/vue/24/outline'

// Props
const props = defineProps({
  notificationCount: {
    type: Number,
    default: 0
  }
})

// Reactive data
const showMobileMenu = ref(false)

// Computed properties
const userRoles = computed(() => {
  return $page.props.auth.user.roles || []
})

const isSuperAdmin = computed(() => {
  return userRoles.value.includes('super-admin')
})

const isAdmin = computed(() => {
  return userRoles.value.includes('admin') || isSuperAdmin.value
})

const isOps = computed(() => {
  return userRoles.value.includes('ops') || isAdmin.value
})

const isChecker = computed(() => {
  return userRoles.value.includes('checker')
})

const userRoleDisplay = computed(() => {
  if (isSuperAdmin.value) return 'Super Admin'
  if (isAdmin.value) return 'Admin'
  if (isOps.value) return 'Operations'
  if (isChecker.value) return 'Checker'
  return 'User'
})

const primaryDashboardRoute = computed(() => {
  if (isSuperAdmin.value) return 'super-admin.dashboard'
  if (isAdmin.value) return 'admin.dashboard'
  if (isOps.value) return 'ops.dashboard'
  if (isChecker.value) return 'checker.dashboard'
  return 'dashboard'
})
</script>
