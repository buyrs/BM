@props(['user'])

@php
    $userRoles = $user->roles->pluck('name')->toArray();
    $isSuperAdmin = in_array('super-admin', $userRoles);
    $isAdmin = in_array('admin', $userRoles) || $isSuperAdmin;
    $isOps = in_array('ops', $userRoles) || $isAdmin;
    $isChecker = in_array('checker', $userRoles);
@endphp

<nav class="bg-white shadow-sm border-b border-gray-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="flex-shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}" class="text-xl font-bold text-[var(--primary-color)]">
                        Bail Mobilité
                    </a>
                </div>

                <!-- Primary Navigation Menu -->
                <div class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex">
                    <!-- Dashboard Links -->
                    @if($isSuperAdmin)
                        <x-nav-link :href="route('super-admin.dashboard')" :active="request()->routeIs('super-admin.*')">
                            {{ __('Super Admin') }}
                        </x-nav-link>
                    @endif

                    @if($isAdmin)
                        <x-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.*')">
                            {{ __('Admin') }}
                        </x-nav-link>
                    @endif

                    @if($isOps)
                        <x-nav-link :href="route('ops.dashboard')" :active="request()->routeIs('ops.*')">
                            {{ __('Operations') }}
                        </x-nav-link>
                    @endif

                    @if($isChecker)
                        <x-nav-link :href="route('checker.dashboard')" :active="request()->routeIs('checker.*')">
                            {{ __('Checker') }}
                        </x-nav-link>
                    @endif

                    <!-- Common Navigation Items -->
                    <x-nav-link :href="route('missions.index')" :active="request()->routeIs('missions.*')">
                        {{ __('Missions') }}
                    </x-nav-link>

                    @if($isOps || $isAdmin)
                        <x-nav-link :href="route('ops.bail-mobilites.index')" :active="request()->routeIs('ops.bail-mobilites.*')">
                            {{ __('Bail Mobilités') }}
                        </x-nav-link>
                    @endif

                    @if($isAdmin)
                        <x-nav-link :href="route('admin.contract-templates.index')" :active="request()->routeIs('admin.contract-templates.*')">
                            {{ __('Contract Templates') }}
                        </x-nav-link>
                    @endif

                    @if($isOps || $isAdmin)
                        <x-nav-link :href="route('ops.incidents.index')" :active="request()->routeIs('ops.incidents.*')">
                            {{ __('Incidents') }}
                        </x-nav-link>
                    @endif
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:ml-6 sm:flex sm:items-center">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="flex items-center text-sm font-medium text-gray-500 hover:text-gray-700 focus:outline-none focus:text-gray-700 focus:border-gray-300 transition duration-150 ease-in-out">
                            <div>{{ $user->name }}</div>
                            <div class="ml-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <!-- User Info -->
                        <div class="px-4 py-2 text-sm text-gray-500 border-b border-gray-200">
                            <div class="font-medium">{{ $user->name }}</div>
                            <div class="text-xs">{{ $user->email }}</div>
                            <div class="text-xs text-gray-400">
                                @foreach($user->roles as $role)
                                    <span class="inline-block bg-gray-100 text-gray-600 px-2 py-1 rounded text-xs mr-1">
                                        {{ ucfirst($role->name) }}
                                    </span>
                                @endforeach
                            </div>
                        </div>

                        <!-- Profile -->
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <!-- Role-specific Settings -->
                        @if($isAdmin)
                            <x-dropdown-link :href="route('admin.role-management')">
                                {{ __('Role Management') }}
                            </x-dropdown-link>
                        @endif

                        @if($isOps)
                            <x-dropdown-link :href="route('ops.notifications')">
                                {{ __('Notifications') }}
                            </x-dropdown-link>
                        @endif

                        <!-- Logout -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-mr-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <!-- Mobile Navigation Links -->
            @if($isSuperAdmin)
                <x-responsive-nav-link :href="route('super-admin.dashboard')" :active="request()->routeIs('super-admin.*')">
                    {{ __('Super Admin') }}
                </x-responsive-nav-link>
            @endif

            @if($isAdmin)
                <x-responsive-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.*')">
                    {{ __('Admin') }}
                </x-responsive-nav-link>
            @endif

            @if($isOps)
                <x-responsive-nav-link :href="route('ops.dashboard')" :active="request()->routeIs('ops.*')">
                    {{ __('Operations') }}
                </x-responsive-nav-link>
            @endif

            @if($isChecker)
                <x-responsive-nav-link :href="route('checker.dashboard')" :active="request()->routeIs('checker.*')">
                    {{ __('Checker') }}
                </x-responsive-nav-link>
            @endif

            <x-responsive-nav-link :href="route('missions.index')" :active="request()->routeIs('missions.*')">
                {{ __('Missions') }}
            </x-responsive-nav-link>

            @if($isOps || $isAdmin)
                <x-responsive-nav-link :href="route('ops.bail-mobilites.index')" :active="request()->routeIs('ops.bail-mobilites.*')">
                    {{ __('Bail Mobilités') }}
                </x-responsive-nav-link>
            @endif

            @if($isAdmin)
                <x-responsive-nav-link :href="route('admin.contract-templates.index')" :active="request()->routeIs('admin.contract-templates.*')">
                    {{ __('Contract Templates') }}
                </x-responsive-nav-link>
            @endif

            @if($isOps || $isAdmin)
                <x-responsive-nav-link :href="route('ops.incidents.index')" :active="request()->routeIs('ops.incidents.*')">
                    {{ __('Incidents') }}
                </x-responsive-nav-link>
            @endif
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">{{ $user->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ $user->email }}</div>
                <div class="text-xs text-gray-400 mt-1">
                    @foreach($user->roles as $role)
                        <span class="inline-block bg-gray-100 text-gray-600 px-2 py-1 rounded text-xs mr-1">
                            {{ ucfirst($role->name) }}
                        </span>
                    @endforeach
                </div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                @if($isAdmin)
                    <x-responsive-nav-link :href="route('admin.role-management')">
                        {{ __('Role Management') }}
                    </x-responsive-nav-link>
                @endif

                @if($isOps)
                    <x-responsive-nav-link :href="route('ops.notifications')">
                        {{ __('Notifications') }}
                    </x-responsive-nav-link>
                @endif

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
