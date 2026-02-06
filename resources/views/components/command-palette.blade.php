@props([
    'placeholder' => 'Search or type a command...',
])

<!-- Command Palette (Spotlight-style) -->
<div 
    x-data="commandPalette()"
    x-show="isOpen"
    x-cloak
    @open-command-palette.window="open()"
    @keydown.escape.window="close()"
    @keydown.window="handleGlobalKeydown($event)"
    class="fixed inset-0 z-[100] overflow-y-auto"
    {{ $attributes }}
>
    <!-- Backdrop -->
    <div 
        x-show="isOpen"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="close()"
        class="fixed inset-0 bg-secondary-900/60 backdrop-blur-sm"
    ></div>
    
    <!-- Dialog -->
    <div class="fixed inset-0 flex items-start justify-center pt-[15vh] px-4">
        <div 
            x-show="isOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95 translate-y-4"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            x-trap.noscroll="isOpen"
            @click.away="close()"
            class="w-full max-w-xl bg-white dark:bg-secondary-800 rounded-2xl shadow-strong overflow-hidden"
        >
            <!-- Search Input -->
            <div class="relative">
                <div class="absolute left-4 top-1/2 -translate-y-1/2 text-secondary-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                <input 
                    x-ref="searchInput"
                    x-model="query"
                    @input.debounce.150ms="search()"
                    type="text"
                    placeholder="{{ $placeholder }}"
                    class="w-full pl-12 pr-20 py-4 text-lg bg-transparent border-0 border-b border-secondary-200 dark:border-secondary-700 focus:ring-0 focus:border-primary-500 text-secondary-900 dark:text-white placeholder-secondary-400"
                >
                <!-- Keyboard hint -->
                <div class="absolute right-4 top-1/2 -translate-y-1/2 flex items-center gap-2">
                    <kbd class="hidden sm:inline-flex items-center px-2 py-1 text-xs font-medium text-secondary-400 bg-secondary-100 dark:bg-secondary-700 rounded">
                        ESC
                    </kbd>
                </div>
            </div>
            
            <!-- Results -->
            <div 
                x-ref="resultsContainer"
                class="max-h-[50vh] overflow-y-auto overscroll-contain"
            >
                <!-- Loading State -->
                <div x-show="isLoading" class="p-4">
                    <div class="flex items-center justify-center gap-2 text-secondary-400">
                        <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>Searching...</span>
                    </div>
                </div>
                
                <!-- Empty State -->
                <div x-show="!isLoading && query && filteredResults.length === 0" class="p-8 text-center">
                    <div class="text-secondary-400">
                        <svg class="w-12 h-12 mx-auto mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M12 14a2 2 0 100-4 2 2 0 000 4z"></path>
                        </svg>
                        <p class="text-sm">No results found for "<span x-text="query"></span>"</p>
                    </div>
                </div>
                
                <!-- Results List -->
                <div x-show="!isLoading && filteredResults.length > 0" class="py-2">
                    <!-- Recent Section -->
                    <template x-if="!query && recentItems.length > 0">
                        <div>
                            <div class="px-4 py-2 text-xs font-semibold text-secondary-400 uppercase tracking-wider">
                                Recent
                            </div>
                            <template x-for="(item, index) in recentItems" :key="'recent-' + index">
                                <button 
                                    @click="selectItem(item)"
                                    @mouseenter="selectedIndex = index"
                                    :class="{ 'bg-primary-50 dark:bg-primary-900/30': selectedIndex === index }"
                                    class="w-full flex items-center gap-3 px-4 py-3 text-left hover:bg-secondary-50 dark:hover:bg-secondary-700/50 transition-colors"
                                >
                                    <div 
                                        class="flex-shrink-0 w-10 h-10 flex items-center justify-center rounded-lg"
                                        :class="item.iconBg || 'bg-secondary-100 dark:bg-secondary-700'"
                                    >
                                        <span x-html="item.icon" class="text-secondary-500"></span>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-secondary-900 dark:text-white truncate" x-text="item.title"></p>
                                        <p class="text-xs text-secondary-500 truncate" x-text="item.subtitle"></p>
                                    </div>
                                    <span x-show="item.shortcut" class="text-xs text-secondary-400" x-text="item.shortcut"></span>
                                </button>
                            </template>
                        </div>
                    </template>
                    
                    <!-- Grouped Results -->
                    <template x-for="(group, groupName) in groupedResults" :key="groupName">
                        <div>
                            <div class="px-4 py-2 text-xs font-semibold text-secondary-400 uppercase tracking-wider" x-text="groupName"></div>
                            <template x-for="(item, index) in group" :key="item.id || index">
                                <button 
                                    @click="selectItem(item)"
                                    @mouseenter="setSelectedByItem(item)"
                                    :class="{ 'bg-primary-50 dark:bg-primary-900/30': isSelected(item) }"
                                    class="w-full flex items-center gap-3 px-4 py-3 text-left hover:bg-secondary-50 dark:hover:bg-secondary-700/50 transition-colors"
                                >
                                    <div 
                                        class="flex-shrink-0 w-10 h-10 flex items-center justify-center rounded-lg"
                                        :class="item.iconBg || 'bg-secondary-100 dark:bg-secondary-700'"
                                    >
                                        <span x-html="item.icon" class="text-secondary-500"></span>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-secondary-900 dark:text-white truncate" x-text="item.title"></p>
                                        <p x-show="item.subtitle" class="text-xs text-secondary-500 truncate" x-text="item.subtitle"></p>
                                    </div>
                                    <span x-show="item.shortcut" class="text-xs text-secondary-400 font-mono" x-text="item.shortcut"></span>
                                </button>
                            </template>
                        </div>
                    </template>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="flex items-center justify-between px-4 py-3 text-xs text-secondary-400 border-t border-secondary-200 dark:border-secondary-700 bg-secondary-50 dark:bg-secondary-900/50">
                <div class="flex items-center gap-4">
                    <span class="flex items-center gap-1">
                        <kbd class="px-1.5 py-0.5 bg-white dark:bg-secondary-700 rounded shadow-sm">↑↓</kbd>
                        to navigate
                    </span>
                    <span class="flex items-center gap-1">
                        <kbd class="px-1.5 py-0.5 bg-white dark:bg-secondary-700 rounded shadow-sm">↵</kbd>
                        to select
                    </span>
                </div>
                <div class="flex items-center gap-1">
                    <span class="text-secondary-300">⌘K to open</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('commandPalette', () => ({
            isOpen: false,
            query: '',
            isLoading: false,
            selectedIndex: 0,
            results: [],
            recentItems: [],
            
            // Default commands
            commands: [
                {
                    id: 'nav-dashboard',
                    title: 'Go to Dashboard',
                    subtitle: 'View your dashboard',
                    icon: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"></path></svg>',
                    iconBg: 'bg-primary-100 dark:bg-primary-900/50',
                    group: 'Navigation',
                    shortcut: 'G D',
                    action: () => this.navigateTo('dashboard')
                },
                {
                    id: 'nav-missions',
                    title: 'Go to Missions',
                    subtitle: 'View all missions',
                    icon: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>',
                    iconBg: 'bg-success-100 dark:bg-success-900/50',
                    group: 'Navigation',
                    shortcut: 'G M',
                    action: () => this.navigateTo('missions')
                },
                {
                    id: 'nav-properties',
                    title: 'Go to Properties',
                    subtitle: 'Manage properties',
                    icon: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>',
                    iconBg: 'bg-warning-100 dark:bg-warning-900/50',
                    group: 'Navigation',
                    shortcut: 'G P',
                    action: () => this.navigateTo('properties')
                },
                {
                    id: 'nav-users',
                    title: 'Go to Users',
                    subtitle: 'Manage users and checkers',
                    icon: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>',
                    iconBg: 'bg-accent-100 dark:bg-accent-900/50',
                    group: 'Navigation',
                    shortcut: 'G U',
                    action: () => this.navigateTo('users')
                },
                {
                    id: 'action-new-mission',
                    title: 'Create New Mission',
                    subtitle: 'Schedule a new mission',
                    icon: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>',
                    iconBg: 'bg-primary-100 dark:bg-primary-900/50',
                    group: 'Actions',
                    shortcut: 'N',
                    action: () => window.dispatchEvent(new CustomEvent('shortcut-new-mission'))
                },
                {
                    id: 'action-search',
                    title: 'Search Everything',
                    subtitle: 'Search missions, properties, users...',
                    icon: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>',
                    group: 'Actions',
                    shortcut: '⌘/',
                    action: () => {
                        const input = document.querySelector('[data-search-input]');
                        if (input) {
                            this.close();
                            input.focus();
                        }
                    }
                },
                {
                    id: 'help-shortcuts',
                    title: 'Keyboard Shortcuts',
                    subtitle: 'View all keyboard shortcuts',
                    icon: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
                    group: 'Help',
                    shortcut: '?',
                    action: () => window.dispatchEvent(new CustomEvent('show-shortcuts-help'))
                }
            ],
            
            get filteredResults() {
                if (!this.query) {
                    return this.commands;
                }
                
                const q = this.query.toLowerCase();
                return this.commands.filter(cmd => 
                    cmd.title.toLowerCase().includes(q) ||
                    (cmd.subtitle && cmd.subtitle.toLowerCase().includes(q)) ||
                    (cmd.group && cmd.group.toLowerCase().includes(q))
                );
            },
            
            get groupedResults() {
                const groups = {};
                for (const item of this.filteredResults) {
                    const group = item.group || 'Other';
                    if (!groups[group]) {
                        groups[group] = [];
                    }
                    groups[group].push(item);
                }
                return groups;
            },
            
            init() {
                // Load recent items from localStorage
                this.loadRecentItems();
            },
            
            open() {
                this.isOpen = true;
                this.query = '';
                this.selectedIndex = 0;
                
                this.$nextTick(() => {
                    this.$refs.searchInput?.focus();
                });
                
                // Haptic feedback
                if (window.haptics) {
                    window.haptics.selection();
                }
            },
            
            close() {
                this.isOpen = false;
                this.query = '';
            },
            
            search() {
                // For API search, add loading state here
                // this.isLoading = true;
                // await fetch(...)
                // this.isLoading = false;
            },
            
            selectItem(item) {
                // Add to recent
                this.addToRecent(item);
                
                // Execute action
                if (typeof item.action === 'function') {
                    item.action();
                } else if (item.url) {
                    window.location.href = item.url;
                }
                
                this.close();
                
                // Haptic feedback
                if (window.haptics) {
                    window.haptics.selection();
                }
            },
            
            isSelected(item) {
                const flatResults = this.filteredResults;
                return flatResults[this.selectedIndex]?.id === item.id;
            },
            
            setSelectedByItem(item) {
                const index = this.filteredResults.findIndex(r => r.id === item.id);
                if (index >= 0) {
                    this.selectedIndex = index;
                }
            },
            
            handleGlobalKeydown(event) {
                if (!this.isOpen) return;
                
                const results = this.filteredResults;
                
                switch (event.key) {
                    case 'ArrowDown':
                        event.preventDefault();
                        this.selectedIndex = (this.selectedIndex + 1) % results.length;
                        this.scrollToSelected();
                        break;
                    case 'ArrowUp':
                        event.preventDefault();
                        this.selectedIndex = (this.selectedIndex - 1 + results.length) % results.length;
                        this.scrollToSelected();
                        break;
                    case 'Enter':
                        event.preventDefault();
                        if (results[this.selectedIndex]) {
                            this.selectItem(results[this.selectedIndex]);
                        }
                        break;
                }
            },
            
            scrollToSelected() {
                this.$nextTick(() => {
                    const container = this.$refs.resultsContainer;
                    const selected = container?.querySelector('[class*="bg-primary-50"]');
                    if (selected) {
                        selected.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                    }
                });
            },
            
            navigateTo(page) {
                const links = {
                    'dashboard': document.querySelector('a[href*="dashboard"]'),
                    'missions': document.querySelector('a[href*="missions"]'),
                    'properties': document.querySelector('a[href*="properties"]'),
                    'users': document.querySelector('a[href*="users"]'),
                };
                
                const link = links[page];
                if (link) {
                    window.location.href = link.href;
                }
            },
            
            loadRecentItems() {
                try {
                    const stored = localStorage.getItem('command-palette-recent');
                    if (stored) {
                        const ids = JSON.parse(stored);
                        this.recentItems = ids
                            .map(id => this.commands.find(c => c.id === id))
                            .filter(Boolean)
                            .slice(0, 5);
                    }
                } catch (e) {
                    console.warn('Failed to load recent items:', e);
                }
            },
            
            addToRecent(item) {
                if (!item.id) return;
                
                try {
                    let recent = JSON.parse(localStorage.getItem('command-palette-recent') || '[]');
                    recent = recent.filter(id => id !== item.id);
                    recent.unshift(item.id);
                    recent = recent.slice(0, 5);
                    localStorage.setItem('command-palette-recent', JSON.stringify(recent));
                } catch (e) {
                    console.warn('Failed to save recent item:', e);
                }
            }
        }));
    });
</script>
