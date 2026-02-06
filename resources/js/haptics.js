/**
 * Haptic Feedback Manager
 * Provides vibration patterns for mobile devices
 * 
 * Features:
 * - Predefined vibration patterns (success, error, warning, selection)
 * - Button press feedback
 * - Navigation transitions
 * - Configurable intensity levels
 */

// Check for vibration API support
const supportsVibration = 'vibrate' in navigator;

/**
 * Vibration patterns (in milliseconds)
 * Format: [vibrate, pause, vibrate, pause, ...]
 */
const PATTERNS = {
    // Short, single tap for selections and button presses
    light: [10],
    medium: [25],
    heavy: [50],

    // Feedback patterns
    success: [10, 50, 10, 50, 30],           // Double tap then longer
    error: [50, 100, 50, 100, 50],           // Three medium vibrations
    warning: [30, 80, 30],                    // Double short

    // UI interaction patterns
    selection: [10],                          // Very light tap
    impact: [15],                             // Light impact
    notification: [50, 100, 50],              // Double medium

    // Navigation patterns
    navigate: [5],                            // Ultra light
    swipe: [8],                               // Very light for swipe feedback
    longPress: [15, 50, 15],                  // Recognize long press

    // Custom patterns
    confirm: [10, 30, 10, 30, 50],            // Building to confirm
    cancel: [50, 50, 20],                     // Decreasing
    refresh: [15, 40, 15, 40, 15, 40, 30],   // Spinning feel
};

/**
 * Intensity multipliers
 */
const INTENSITY = {
    off: 0,
    light: 0.5,
    medium: 1,
    heavy: 1.5,
};

/**
 * HapticFeedback class
 */
class HapticFeedback {
    constructor() {
        this.enabled = true;
        this.intensity = 'medium';
        this.supportsVibration = supportsVibration;

        // Load saved preferences
        this.loadPreferences();
    }

    /**
     * Load user preferences from localStorage
     */
    loadPreferences() {
        if (typeof localStorage !== 'undefined') {
            const saved = localStorage.getItem('haptic-preferences');
            if (saved) {
                try {
                    const prefs = JSON.parse(saved);
                    this.enabled = prefs.enabled ?? true;
                    this.intensity = prefs.intensity ?? 'medium';
                } catch (e) {
                    console.warn('Failed to load haptic preferences:', e);
                }
            }
        }
    }

    /**
     * Save user preferences to localStorage
     */
    savePreferences() {
        if (typeof localStorage !== 'undefined') {
            localStorage.setItem('haptic-preferences', JSON.stringify({
                enabled: this.enabled,
                intensity: this.intensity
            }));
        }
    }

    /**
     * Enable/disable haptic feedback
     */
    setEnabled(enabled) {
        this.enabled = enabled;
        this.savePreferences();
        return this;
    }

    /**
     * Set intensity level
     * @param {string} level - 'off', 'light', 'medium', 'heavy'
     */
    setIntensity(level) {
        if (INTENSITY.hasOwnProperty(level)) {
            this.intensity = level;
            this.savePreferences();
        }
        return this;
    }

    /**
     * Apply intensity multiplier to pattern
     */
    applyIntensity(pattern) {
        const multiplier = INTENSITY[this.intensity] || 1;
        if (multiplier === 0) return [];

        return pattern.map((duration, index) => {
            // Only modify vibration durations (even indices)
            if (index % 2 === 0) {
                return Math.round(duration * multiplier);
            }
            return duration;
        });
    }

    /**
     * Trigger vibration with a pattern
     * @param {string|number[]} pattern - Pattern name or custom pattern array
     */
    vibrate(pattern = 'medium') {
        if (!this.enabled || !this.supportsVibration) return false;
        if (this.intensity === 'off') return false;

        let vibrationPattern;

        if (typeof pattern === 'string') {
            vibrationPattern = PATTERNS[pattern];
            if (!vibrationPattern) {
                console.warn(`Unknown haptic pattern: ${pattern}`);
                vibrationPattern = PATTERNS.medium;
            }
        } else if (Array.isArray(pattern)) {
            vibrationPattern = pattern;
        } else {
            vibrationPattern = PATTERNS.medium;
        }

        const adjustedPattern = this.applyIntensity(vibrationPattern);

        try {
            return navigator.vibrate(adjustedPattern);
        } catch (e) {
            console.warn('Vibration failed:', e);
            return false;
        }
    }

    /**
     * Stop any ongoing vibration
     */
    stop() {
        if (this.supportsVibration) {
            navigator.vibrate(0);
        }
        return this;
    }

    // Convenience methods for common patterns

    /**
     * Light tap for selections
     */
    selection() {
        return this.vibrate('selection');
    }

    /**
     * Light impact for button presses
     */
    impact() {
        return this.vibrate('impact');
    }

    /**
     * Success feedback
     */
    success() {
        return this.vibrate('success');
    }

    /**
     * Error feedback
     */
    error() {
        return this.vibrate('error');
    }

    /**
     * Warning feedback
     */
    warning() {
        return this.vibrate('warning');
    }

    /**
     * Notification feedback
     */
    notification() {
        return this.vibrate('notification');
    }

    /**
     * Navigation feedback
     */
    navigate() {
        return this.vibrate('navigate');
    }

    /**
     * Swipe feedback
     */
    swipe() {
        return this.vibrate('swipe');
    }

    /**
     * Long press feedback
     */
    longPress() {
        return this.vibrate('longPress');
    }

    /**
     * Confirm action feedback
     */
    confirm() {
        return this.vibrate('confirm');
    }

    /**
     * Cancel action feedback
     */
    cancel() {
        return this.vibrate('cancel');
    }

    /**
     * Refresh feedback
     */
    refresh() {
        return this.vibrate('refresh');
    }
}

// Create singleton instance
const haptics = new HapticFeedback();

/**
 * Auto-attach haptic feedback to elements
 */
function autoAttachHaptics() {
    if (typeof document === 'undefined') return;

    // Attach to buttons
    document.addEventListener('click', (e) => {
        const target = e.target.closest('button, .btn, [role="button"], a.nav-link');
        if (target) {
            // Use selection for nav links, impact for buttons
            if (target.classList.contains('nav-link') || target.closest('nav')) {
                haptics.selection();
            } else {
                haptics.impact();
            }
        }
    }, { passive: true });

    // Attach to form submissions
    document.addEventListener('submit', () => {
        haptics.confirm();
    }, { passive: true });
}

/**
 * Register Alpine.js magic property and directive
 */
function registerHapticsAlpine() {
    if (typeof Alpine !== 'undefined') {
        // Magic property $haptics
        Alpine.magic('haptics', () => haptics);

        // Directive x-haptic
        Alpine.directive('haptic', (el, { expression, modifiers }) => {
            const pattern = expression || modifiers[0] || 'impact';
            const eventType = modifiers.includes('hover') ? 'mouseenter' : 'click';

            el.addEventListener(eventType, () => {
                haptics.vibrate(pattern);
            }, { passive: true });
        });
    }
}

// Initialize on DOM ready
if (typeof document !== 'undefined') {
    document.addEventListener('DOMContentLoaded', () => {
        autoAttachHaptics();
        registerHapticsAlpine();
    });
}

// Export for module usage
export { haptics, HapticFeedback, PATTERNS, INTENSITY };
export default haptics;
