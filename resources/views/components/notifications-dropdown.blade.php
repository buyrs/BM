<div class="relative" x-data="{ open: false }" @click.outside="open = false" @close.stop="open = false">
    <div @click="open = ! open">
        <button type="button" class="relative rounded-full bg-gray-100 p-1 text-gray-400 hover:text-gray-500 focus:outline-none">
            <span class="absolute -inset-1.5"></span>
            <span class="sr-only">View notifications</span>
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
            </svg>
            <!-- Notification badge -->
            <span class="absolute top-0 right-0 block h-2 w-2 rounded-full bg-red-400 ring-2 ring-white"></span>
        </button>
    </div>

    <div x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-75"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="absolute right-0 z-50 mt-2 w-80 rounded-md shadow-lg origin-top-right"
            style="display: none;"
            @click="open = false">
        <div class="rounded-md ring-1 ring-black ring-opacity-5 bg-white py-1">
            <div class="border-b border-gray-200 px-4 py-3">
                <h3 class="text-sm font-medium text-gray-900">Notifications</h3>
            </div>
            <div class="max-h-60 overflow-y-auto">
                <div class="px-4 py-3 text-sm text-gray-500 text-center">
                    No new notifications
                </div>
            </div>
            <div class="border-t border-gray-200 px-4 py-2">
                <a href="#" class="text-sm text-indigo-600 hover:text-indigo-900">View all</a>
            </div>
        </div>
    </div>
</div>