import './bootstrap';

import Alpine from 'alpinejs';
import focus from '@alpinejs/focus';

// UX Enhancement Modules
import { registerGestureDirectives } from './gestures';
import haptics from './haptics';
import initKeyboardShortcuts from './keyboard-shortcuts';
import initPageTransitions from './page-transitions';

// Make haptics globally available
window.haptics = haptics;

window.Alpine = Alpine;

// Register plugins
Alpine.plugin(focus);

// Register gesture directives before Alpine starts
registerGestureDirectives();

Alpine.start();

// Initialize keyboard shortcuts for desktop power users
initKeyboardShortcuts();

// Initialize page transitions for smooth navigation
initPageTransitions();
