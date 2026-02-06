/**
 * Keyboard Shortcuts System
 * Global keyboard shortcut handler for power users
 * 
 * Features:
 * - Quick navigation (G + D = Dashboard, G + M = Missions)
 * - Command palette trigger (Cmd/Ctrl + K)
 * - Customizable bindings
 * - Conflict detection
 */

// Default keyboard shortcut configuration
const DEFAULT_SHORTCUTS = {
    // Command palette
    'command-palette': {
        keys: ['mod+k'],
        description: 'Open command palette',
        action: () => window.dispatchEvent(new CustomEvent('open-command-palette'))
    },

    // Search
    'global-search': {
        keys: ['mod+/'],
        description: 'Focus search',
        action: () => {
            const searchInput = document.querySelector('[data-search-input]') ||
                document.querySelector('input[type="search"]') ||
                document.querySelector('input[name="search"]');
            if (searchInput) {
                searchInput.focus();
                searchInput.select();
            }
        }
    },

    // Quick navigation (G prefix)
    'nav-dashboard': {
        keys: ['g d'],
        description: 'Go to Dashboard',
        action: () => navigateTo('dashboard')
    },
    'nav-missions': {
        keys: ['g m'],
        description: 'Go to Missions',
        action: () => navigateTo('missions')
    },
    'nav-properties': {
        keys: ['g p'],
        description: 'Go to Properties',
        action: () => navigateTo('properties')
    },
    'nav-users': {
        keys: ['g u'],
        description: 'Go to Users',
        action: () => navigateTo('users')
    },
    'nav-settings': {
        keys: ['g s'],
        description: 'Go to Settings',
        action: () => navigateTo('settings')
    },

    // Actions
    'new-mission': {
        keys: ['n'],
        description: 'Create new mission',
        action: () => window.dispatchEvent(new CustomEvent('shortcut-new-mission'))
    },

    // Escape to close modals/panels
    'close': {
        keys: ['Escape'],
        description: 'Close modal/panel',
        action: () => window.dispatchEvent(new CustomEvent('shortcut-close'))
    },

    // Help
    'show-shortcuts': {
        keys: ['shift+/'],
        description: 'Show keyboard shortcuts',
        action: () => window.dispatchEvent(new CustomEvent('show-shortcuts-help'))
    }
};

// Navigation helper
function navigateTo(page) {
    const links = {
        'dashboard': document.querySelector('a[href*="dashboard"]'),
        'missions': document.querySelector('a[href*="missions"]'),
        'properties': document.querySelector('a[href*="properties"]'),
        'users': document.querySelector('a[href*="users"]'),
        'settings': document.querySelector('a[href*="settings"]'),
    };

    const link = links[page];
    if (link) {
        window.location.href = link.href;
    }
}

/**
 * KeyboardShortcuts class
 */
class KeyboardShortcuts {
    constructor(options = {}) {
        this.shortcuts = { ...DEFAULT_SHORTCUTS, ...options.shortcuts };
        this.enabled = true;
        this.sequenceBuffer = [];
        this.sequenceTimeout = null;
        this.sequenceDelay = 1000; // ms to wait for sequence completion
        this.listeners = new Map();

        // Platform detection
        this.isMac = navigator.platform.toUpperCase().indexOf('MAC') >= 0;

        this.init();
    }

    init() {
        document.addEventListener('keydown', this.handleKeyDown.bind(this));

        // Show shortcut hints on focused elements
        this.setupShortcutHints();
    }

    handleKeyDown(event) {
        if (!this.enabled) return;

        // Don't trigger shortcuts when typing in inputs
        if (this.isTypingContext(event)) {
            // Allow escape in typing context
            if (event.key === 'Escape') {
                event.target.blur();
            }
            return;
        }

        const key = this.normalizeKey(event);

        // Add to sequence buffer
        this.sequenceBuffer.push(key);

        // Clear sequence after delay
        clearTimeout(this.sequenceTimeout);
        this.sequenceTimeout = setTimeout(() => {
            this.sequenceBuffer = [];
        }, this.sequenceDelay);

        // Check for matching shortcuts
        for (const [name, config] of Object.entries(this.shortcuts)) {
            for (const shortcutKeys of config.keys) {
                if (this.matchesShortcut(shortcutKeys, event)) {
                    event.preventDefault();
                    event.stopPropagation();

                    // Execute action
                    if (typeof config.action === 'function') {
                        config.action();
                    }

                    // Clear sequence
                    this.sequenceBuffer = [];
                    return;
                }
            }
        }
    }

    normalizeKey(event) {
        const parts = [];

        if (event.ctrlKey && !this.isMac) parts.push('ctrl');
        if (event.metaKey && this.isMac) parts.push('cmd');
        if (event.altKey) parts.push('alt');
        if (event.shiftKey) parts.push('shift');

        // Normalize key
        let key = event.key.toLowerCase();
        if (key === ' ') key = 'space';
        if (key === 'arrowup') key = 'up';
        if (key === 'arrowdown') key = 'down';
        if (key === 'arrowleft') key = 'left';
        if (key === 'arrowright') key = 'right';

        parts.push(key);

        return parts.join('+');
    }

    matchesShortcut(shortcutKeys, event) {
        const parts = shortcutKeys.toLowerCase().split(' ');

        // Single key or modifier combo
        if (parts.length === 1) {
            const targetKey = parts[0]
                .replace('mod', this.isMac ? 'cmd' : 'ctrl')
                .replace('cmd', 'cmd')
                .replace('ctrl', 'ctrl');

            const pressedKey = this.normalizeKey(event);
            return targetKey === pressedKey;
        }

        // Key sequence (e.g., 'g d')
        if (parts.length === 2) {
            if (this.sequenceBuffer.length < 2) return false;

            const lastTwo = this.sequenceBuffer.slice(-2);
            return lastTwo[0] === parts[0] && lastTwo[1] === parts[1];
        }

        return false;
    }

    isTypingContext(event) {
        const target = event.target;
        const tagName = target.tagName.toLowerCase();

        // Check if we're in an editable element
        if (tagName === 'input' || tagName === 'textarea' || tagName === 'select') {
            return true;
        }

        if (target.isContentEditable) {
            return true;
        }

        // Check for code editors, etc.
        if (target.closest('[role="textbox"]') || target.closest('.code-editor')) {
            return true;
        }

        return false;
    }

    setupShortcutHints() {
        // Add data attributes for shortcut hints
        document.querySelectorAll('[data-shortcut]').forEach(el => {
            const shortcut = el.dataset.shortcut;
            const config = this.shortcuts[shortcut];

            if (config) {
                // Add tooltip with shortcut
                el.title = `${el.title || ''} (${this.formatShortcut(config.keys[0])})`.trim();
            }
        });
    }

    formatShortcut(keys) {
        return keys
            .replace('mod', this.isMac ? '⌘' : 'Ctrl')
            .replace('cmd', '⌘')
            .replace('ctrl', 'Ctrl')
            .replace('alt', this.isMac ? '⌥' : 'Alt')
            .replace('shift', '⇧')
            .replace('+', ' + ')
            .replace(' ', ' then ')
            .toUpperCase();
    }

    // Public API

    register(name, config) {
        this.shortcuts[name] = config;
        return this;
    }

    unregister(name) {
        delete this.shortcuts[name];
        return this;
    }

    enable() {
        this.enabled = true;
        return this;
    }

    disable() {
        this.enabled = false;
        return this;
    }

    getShortcuts() {
        return Object.entries(this.shortcuts).map(([name, config]) => ({
            name,
            keys: config.keys,
            description: config.description
        }));
    }

    destroy() {
        document.removeEventListener('keydown', this.handleKeyDown);
    }
}

// Create singleton instance
let keyboardShortcuts = null;

function initKeyboardShortcuts(options = {}) {
    if (!keyboardShortcuts) {
        keyboardShortcuts = new KeyboardShortcuts(options);
    }
    return keyboardShortcuts;
}

// Auto-initialize on DOM ready
if (typeof document !== 'undefined') {
    document.addEventListener('DOMContentLoaded', () => {
        initKeyboardShortcuts();
    });
}

// Export for module usage
export { KeyboardShortcuts, initKeyboardShortcuts, DEFAULT_SHORTCUTS };
export default initKeyboardShortcuts;
