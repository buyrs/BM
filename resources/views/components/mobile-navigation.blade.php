@props(['userRole'])

<div class="sm:hidden fixed inset-x-0 bottom-0 z-50 bg-white border-t border-gray-200">
    <div class="flex justify-around">
        @if($userRole === 'admin')
            <a href="{{ route('admin.dashboard') }}" 
               class="flex flex-col items-center py-3 px-4 text-sm font-medium {{ request()->routeIs('admin.dashboard') ? 'text-blue-600' : 'text-gray-500' }}">
                <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                </svg>
                <span>Home</span>
            </a>
            <a href="{{ route('admin.users.index') }}" 
               class="flex flex-col items-center py-3 px-4 text-sm font-medium {{ request()->routeIs('admin.users.*') ? 'text-blue-600' : 'text-gray-500' }}">
                <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                </svg>
                <span>Users</span>
            </a>
            <a href="{{ route('admin.missions.index') }}" 
               class="flex flex-col items-center py-3 px-4 text-sm font-medium {{ request()->routeIs('admin.missions.*') ? 'text-blue-600' : 'text-gray-500' }}">
                <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
                <span>Missions</span>
            </a>
        @elseif($userRole === 'ops')
            <a href="{{ route('ops.dashboard') }}" 
               class="flex flex-col items-center py-3 px-4 text-sm font-medium {{ request()->routeIs('ops.dashboard') ? 'text-blue-600' : 'text-gray-500' }}">
                <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                </svg>
                <span>Home</span>
            </a>
            <a href="{{ route('ops.missions.index') }}" 
               class="flex flex-col items-center py-3 px-4 text-sm font-medium {{ request()->routeIs('ops.missions.*') ? 'text-blue-600' : 'text-gray-500' }}">
                <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
                <span>Missions</span>
            </a>
            <a href="{{ route('ops.users.index') }}" 
               class="flex flex-col items-center py-3 px-4 text-sm font-medium {{ request()->routeIs('ops.users.*') ? 'text-blue-600' : 'text-gray-500' }}">
                <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                </svg>
                <span>Checkers</span>
            </a>
        @elseif($userRole === 'checker')
            <a href="{{ route('checker.dashboard') }}" 
               class="flex flex-col items-center py-3 px-4 text-sm font-medium {{ request()->routeIs('checker.dashboard') ? 'text-blue-600' : 'text-gray-500' }}">
                <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                </svg>
                <span>Home</span>
            </a>
            <a href="{{ route('checker.dashboard') }}" 
               class="flex flex-col items-center py-3 px-4 text-sm font-medium {{ request()->routeIs('checklists.*') || request()->routeIs('checker.dashboard') ? 'text-blue-600' : 'text-gray-500' }}">
                <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <span>Checklists</span>
            </a>
        @endif
    </div>
</div>