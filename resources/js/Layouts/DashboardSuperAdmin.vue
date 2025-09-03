<template>
  <div class="min-h-screen bg-background">
    <!-- Navbar -->
    <nav class="bg-white shadow-md">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex">
            <!-- Logo -->
            <div class="shrink-0 flex items-center">
              <Link :href="route('dashboard')">
                <ApplicationLogo class="block h-9 w-auto" />
              </Link>
            </div>

            <!-- Navigation Links -->
            <div class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex">
              <NavLink :href="route('super-admin.dashboard')" :active="route().current('super-admin.dashboard')">
                Dashboard
              </NavLink>
              <NavLink :href="route('super-admin.missions')" :active="route().current('super-admin.missions')">
                Missions
              </NavLink>
              <NavLink :href="route('super-admin.checkers')" :active="route().current('super-admin.checkers')">
                Checkers
              </NavLink>
              <NavLink :href="route('super-admin.reports')" :active="route().current('super-admin.reports')">
                Reports
              </NavLink>
            </div>
          </div>

          <!-- User Dropdown -->
          <div class="hidden sm:flex sm:items-center sm:ml-6">
            <div class="ml-3 relative">
              <Dropdown align="right" width="48">
                <template #trigger>
                  <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-text-secondary bg-white hover:text-text-primary focus:outline-none transition ease-in-out duration-150">
                    <div>{{ $page.props.auth.user.name }}</div>

                    <div class="ml-1">
                      <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                      </svg>
                    </div>
                  </button>
                </template>

                <template #content>
                  <DropdownLink :href="route('super-admin.logout')" method="post" as="button">
                    Log Out
                  </DropdownLink>
                </template>
              </Dropdown>
            </div>
          </div>

          <!-- Hamburger -->
          <div class="-mr-2 flex items-center sm:hidden">
            <button @click="showingNavigationDropdown = !showingNavigationDropdown" class="inline-flex items-center justify-center p-2 rounded-md text-text-secondary hover:text-text-primary hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-text-primary transition duration-150 ease-in-out">
              <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                <path :class="{'hidden': showingNavigationDropdown, 'inline-flex': !showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                <path :class="{'hidden': !showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>
      </div>

      <!-- Responsive Navigation Menu -->
      <div :class="{'block': showingNavigationDropdown, 'hidden': !showingNavigationDropdown}" class="sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
          <ResponsiveNavLink :href="route('super-admin.dashboard')" :active="route().current('super-admin.dashboard')">
            Dashboard
          </ResponsiveNavLink>
          <ResponsiveNavLink :href="route('super-admin.missions')" :active="route().current('super-admin.missions')">
            Missions
          </ResponsiveNavLink>
          <ResponsiveNavLink :href="route('super-admin.checkers')" :active="route().current('super-admin.checkers')">
            Checkers
          </ResponsiveNavLink>
          <ResponsiveNavLink :href="route('super-admin.reports')" :active="route().current('super-admin.reports')">
            Reports
          </ResponsiveNavLink>
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
          <div class="px-4">
            <div class="font-medium text-base text-text-primary">{{ $page.props.auth.user.name }}</div>
            <div class="font-medium text-sm text-text-secondary">{{ $page.props.auth.user.email }}</div>
          </div>

          <div class="mt-3 space-y-1">
            <ResponsiveNavLink :href="route('super-admin.logout')" method="post" as="button">
              Log Out
            </ResponsiveNavLink>
          </div>
        </div>
      </div>
    </nav>

    <!-- Sidebar -->
    <div class="flex">
      <!-- Mobile Menu Overlay -->
      <div 
        v-if="showingNavigationDropdown" 
        class="fixed inset-0 z-50 lg:hidden"
        @click="showingNavigationDropdown = false"
      >
        <div class="fixed inset-0 bg-black opacity-50"></div>
      </div>

      <div 
        :class="[
          'w-64 bg-white h-screen shadow-lg transition-transform duration-300 ease-in-out',
          'lg:translate-x-0',
          showingNavigationDropdown ? 'translate-x-0' : '-translate-x-full',
          'lg:static fixed inset-y-0 left-0 z-50'
        ]"
      >
        <div class="flex flex-col h-full">
          <div class="flex-1 overflow-y-auto">
            <nav class="mt-5 px-2">
              <Link
                :href="route('super-admin.dashboard')"
                class="group flex items-center px-2 py-2 text-base leading-6 font-medium rounded-md hover:bg-secondary focus:outline-none focus:bg-secondary transition ease-in-out duration-150"
                :class="[route().current('super-admin.dashboard') ? 'bg-secondary text-primary' : 'text-text-secondary hover:text-primary']"
                @click="showingNavigationDropdown = false"
              >
                <svg class="mr-4 h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                Dashboard
              </Link>

              <Link
                :href="route('super-admin.missions')"
                class="mt-1 group flex items-center px-2 py-2 text-base leading-6 font-medium rounded-md hover:bg-secondary focus:outline-none focus:bg-secondary transition ease-in-out duration-150"
                :class="[route().current('super-admin.missions') ? 'bg-secondary text-primary' : 'text-text-secondary hover:text-primary']"
                @click="showingNavigationDropdown = false"
              >
                <svg class="mr-4 h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                Missions
              </Link>

              <Link
                :href="route('super-admin.checkers')"
                class="mt-1 group flex items-center px-2 py-2 text-base leading-6 font-medium rounded-md hover:bg-secondary focus:outline-none focus:bg-secondary transition ease-in-out duration-150"
                :class="[route().current('super-admin.checkers') ? 'bg-secondary text-primary' : 'text-text-secondary hover:text-primary']"
                @click="showingNavigationDropdown = false"
              >
                <svg class="mr-4 h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
                Checkers
              </Link>

              <Link
                :href="route('super-admin.reports')"
                class="mt-1 group flex items-center px-2 py-2 text-base leading-6 font-medium rounded-md hover:bg-secondary focus:outline-none focus:bg-secondary transition ease-in-out duration-150"
                :class="[route().current('super-admin.reports') ? 'bg-secondary text-primary' : 'text-text-secondary hover:text-primary']"
                @click="showingNavigationDropdown = false"
              >
                <svg class="mr-4 h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Reports
              </Link>
            </nav>
          </div>
        </div>
      </div>

      <!-- Main Content -->
      <div class="flex-1 lg:ml-0">
        <!-- Mobile Header -->
        <div class="lg:hidden bg-white shadow-md p-4 flex items-center justify-between">
          <button
            @click="showingNavigationDropdown = !showingNavigationDropdown"
            class="p-2 rounded-md text-text-secondary hover:text-primary hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-primary transition-colors duration-200"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
          </button>
          <h1 class="text-lg font-semibold text-primary">Super Admin</h1>
          <div class="w-10"></div> <!-- Spacer for centering -->
        </div>

        <main>
          <div class="py-6 lg:py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
              <slot />
            </div>
          </div>
        </main>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';

const showingNavigationDropdown = ref(false);
</script>