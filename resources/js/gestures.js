/**
 * Gesture Utility Library
 * Provides touch gesture detection for mobile-first UX
 * 
 * Features:
 * - Swipe detection (left/right/up/down)
 * - Pull-to-refresh functionality
 * - Long-press detection
 * - Pinch-to-zoom for images
 */

// Gesture configuration defaults
const GESTURE_CONFIG = {
    swipeThreshold: 50,        // Minimum distance in px to register swipe
    swipeVelocity: 0.3,        // Minimum velocity for swipe
    longPressDelay: 500,       // Milliseconds for long press
    pullRefreshThreshold: 80,  // Distance to trigger refresh
    pinchThreshold: 0.1,       // Minimum scale change for pinch
};

/**
 * SwipeDetector - Detects swipe gestures on an element
 */
export class SwipeDetector {
    constructor(element, options = {}) {
        this.element = element;
        this.options = { ...GESTURE_CONFIG, ...options };
        this.callbacks = {
            onSwipeLeft: () => {},
            onSwipeRight: () => {},
            onSwipeUp: () => {},
            onSwipeDown: () => {},
            onSwipeStart: () => {},
            onSwipeMove: () => {},
            onSwipeEnd: () => {},
        };
        
        this.startX = 0;
        this.startY = 0;
        this.startTime = 0;
        this.isTracking = false;
        
        this.bindEvents();
    }
    
    bindEvents() {
        this.element.addEventListener('touchstart', this.handleTouchStart.bind(this), { passive: true });
        this.element.addEventListener('touchmove', this.handleTouchMove.bind(this), { passive: false });
        this.element.addEventListener('touchend', this.handleTouchEnd.bind(this), { passive: true });
    }
    
    handleTouchStart(e) {
        const touch = e.touches[0];
        this.startX = touch.clientX;
        this.startY = touch.clientY;
        this.startTime = Date.now();
        this.isTracking = true;
        
        this.callbacks.onSwipeStart({ x: this.startX, y: this.startY });
    }
    
    handleTouchMove(e) {
        if (!this.isTracking) return;
        
        const touch = e.touches[0];
        const deltaX = touch.clientX - this.startX;
        const deltaY = touch.clientY - this.startY;
        
        this.callbacks.onSwipeMove({ deltaX, deltaY, x: touch.clientX, y: touch.clientY });
        
        // Prevent default if horizontal swipe to avoid page navigation
        if (Math.abs(deltaX) > Math.abs(deltaY) && Math.abs(deltaX) > 10) {
            e.preventDefault();
        }
    }
    
    handleTouchEnd(e) {
        if (!this.isTracking) return;
        this.isTracking = false;
        
        const touch = e.changedTouches[0];
        const deltaX = touch.clientX - this.startX;
        const deltaY = touch.clientY - this.startY;
        const deltaTime = Date.now() - this.startTime;
        const velocity = Math.sqrt(deltaX * deltaX + deltaY * deltaY) / deltaTime;
        
        const swipeData = { deltaX, deltaY, velocity, duration: deltaTime };
        this.callbacks.onSwipeEnd(swipeData);
        
        // Check if swipe threshold met
        if (velocity < this.options.swipeVelocity) return;
        
        const absX = Math.abs(deltaX);
        const absY = Math.abs(deltaY);
        
        if (absX > absY && absX > this.options.swipeThreshold) {
            // Horizontal swipe
            if (deltaX > 0) {
                this.callbacks.onSwipeRight(swipeData);
            } else {
                this.callbacks.onSwipeLeft(swipeData);
            }
        } else if (absY > absX && absY > this.options.swipeThreshold) {
            // Vertical swipe
            if (deltaY > 0) {
                this.callbacks.onSwipeDown(swipeData);
            } else {
                this.callbacks.onSwipeUp(swipeData);
            }
        }
    }
    
    on(event, callback) {
        if (this.callbacks.hasOwnProperty(event)) {
            this.callbacks[event] = callback;
        }
        return this;
    }
    
    destroy() {
        this.element.removeEventListener('touchstart', this.handleTouchStart);
        this.element.removeEventListener('touchmove', this.handleTouchMove);
        this.element.removeEventListener('touchend', this.handleTouchEnd);
    }
}

/**
 * PullToRefresh - Implements pull-to-refresh functionality
 */
export class PullToRefresh {
    constructor(element, options = {}) {
        this.element = element;
        this.options = { 
            ...GESTURE_CONFIG, 
            indicatorElement: null,
            onRefresh: async () => {},
            ...options 
        };
        
        this.startY = 0;
        this.currentY = 0;
        this.isPulling = false;
        this.isRefreshing = false;
        
        this.createIndicator();
        this.bindEvents();
    }
    
    createIndicator() {
        if (!this.options.indicatorElement) {
            this.indicator = document.createElement('div');
            this.indicator.className = 'ptr-indicator fixed top-0 left-0 right-0 flex items-center justify-center py-4 bg-primary-50 transform -translate-y-full transition-transform duration-200 z-50';
            this.indicator.innerHTML = `
                <div class="ptr-spinner w-6 h-6 border-2 border-primary-500 border-t-transparent rounded-full animate-spin opacity-0 transition-opacity"></div>
                <span class="ptr-text ml-2 text-sm text-primary-600">Pull to refresh</span>
            `;
            document.body.insertBefore(this.indicator, document.body.firstChild);
        } else {
            this.indicator = this.options.indicatorElement;
        }
    }
    
    bindEvents() {
        this.element.addEventListener('touchstart', this.handleTouchStart.bind(this), { passive: true });
        this.element.addEventListener('touchmove', this.handleTouchMove.bind(this), { passive: false });
        this.element.addEventListener('touchend', this.handleTouchEnd.bind(this), { passive: true });
    }
    
    handleTouchStart(e) {
        if (this.isRefreshing) return;
        if (window.scrollY > 0) return;
        
        this.startY = e.touches[0].clientY;
        this.isPulling = true;
    }
    
    handleTouchMove(e) {
        if (!this.isPulling || this.isRefreshing) return;
        if (window.scrollY > 0) {
            this.isPulling = false;
            return;
        }
        
        this.currentY = e.touches[0].clientY;
        const pullDistance = Math.max(0, this.currentY - this.startY);
        
        if (pullDistance > 0) {
            e.preventDefault();
            
            // Apply resistance to pull
            const resistance = 0.4;
            const adjustedDistance = pullDistance * resistance;
            
            // Update indicator position
            const progress = Math.min(adjustedDistance / this.options.pullRefreshThreshold, 1);
            this.indicator.style.transform = `translateY(${adjustedDistance - 60}px)`;
            
            // Update indicator state
            const spinner = this.indicator.querySelector('.ptr-spinner');
            const text = this.indicator.querySelector('.ptr-text');
            
            if (progress >= 1) {
                spinner.style.opacity = '1';
                text.textContent = 'Release to refresh';
            } else {
                spinner.style.opacity = progress.toString();
                text.textContent = 'Pull to refresh';
            }
        }
    }
    
    async handleTouchEnd() {
        if (!this.isPulling || this.isRefreshing) return;
        this.isPulling = false;
        
        const pullDistance = (this.currentY - this.startY) * 0.4;
        
        if (pullDistance >= this.options.pullRefreshThreshold) {
            await this.triggerRefresh();
        } else {
            this.resetIndicator();
        }
    }
    
    async triggerRefresh() {
        this.isRefreshing = true;
        
        const text = this.indicator.querySelector('.ptr-text');
        text.textContent = 'Refreshing...';
        this.indicator.style.transform = 'translateY(0)';
        
        try {
            await this.options.onRefresh();
            text.textContent = 'Done!';
            await new Promise(resolve => setTimeout(resolve, 500));
        } catch (error) {
            text.textContent = 'Error refreshing';
            console.error('Pull to refresh error:', error);
            await new Promise(resolve => setTimeout(resolve, 1000));
        }
        
        this.resetIndicator();
        this.isRefreshing = false;
    }
    
    resetIndicator() {
        this.indicator.style.transform = 'translateY(-100%)';
        setTimeout(() => {
            const text = this.indicator.querySelector('.ptr-text');
            const spinner = this.indicator.querySelector('.ptr-spinner');
            text.textContent = 'Pull to refresh';
            spinner.style.opacity = '0';
        }, 200);
    }
    
    destroy() {
        this.element.removeEventListener('touchstart', this.handleTouchStart);
        this.element.removeEventListener('touchmove', this.handleTouchMove);
        this.element.removeEventListener('touchend', this.handleTouchEnd);
        if (!this.options.indicatorElement) {
            this.indicator.remove();
        }
    }
}

/**
 * LongPressDetector - Detects long press gestures
 */
export class LongPressDetector {
    constructor(element, options = {}) {
        this.element = element;
        this.options = { ...GESTURE_CONFIG, ...options };
        this.callbacks = {
            onLongPress: () => {},
            onLongPressStart: () => {},
            onLongPressEnd: () => {},
        };
        
        this.pressTimer = null;
        this.isPressed = false;
        this.startX = 0;
        this.startY = 0;
        
        this.bindEvents();
    }
    
    bindEvents() {
        this.element.addEventListener('touchstart', this.handleTouchStart.bind(this), { passive: true });
        this.element.addEventListener('touchmove', this.handleTouchMove.bind(this), { passive: true });
        this.element.addEventListener('touchend', this.handleTouchEnd.bind(this), { passive: true });
        this.element.addEventListener('touchcancel', this.handleTouchEnd.bind(this), { passive: true });
        
        // Also support mouse for desktop
        this.element.addEventListener('mousedown', this.handleMouseDown.bind(this));
        this.element.addEventListener('mousemove', this.handleMouseMove.bind(this));
        this.element.addEventListener('mouseup', this.handleMouseUp.bind(this));
        this.element.addEventListener('mouseleave', this.handleMouseUp.bind(this));
    }
    
    handleTouchStart(e) {
        const touch = e.touches[0];
        this.startPress(touch.clientX, touch.clientY);
    }
    
    handleTouchMove(e) {
        const touch = e.touches[0];
        this.checkMovement(touch.clientX, touch.clientY);
    }
    
    handleTouchEnd() {
        this.endPress();
    }
    
    handleMouseDown(e) {
        this.startPress(e.clientX, e.clientY);
    }
    
    handleMouseMove(e) {
        this.checkMovement(e.clientX, e.clientY);
    }
    
    handleMouseUp() {
        this.endPress();
    }
    
    startPress(x, y) {
        this.startX = x;
        this.startY = y;
        this.isPressed = true;
        
        this.callbacks.onLongPressStart({ x, y });
        
        this.pressTimer = setTimeout(() => {
            if (this.isPressed) {
                this.callbacks.onLongPress({ x: this.startX, y: this.startY });
            }
        }, this.options.longPressDelay);
    }
    
    checkMovement(x, y) {
        if (!this.isPressed) return;
        
        const moveThreshold = 10;
        const deltaX = Math.abs(x - this.startX);
        const deltaY = Math.abs(y - this.startY);
        
        if (deltaX > moveThreshold || deltaY > moveThreshold) {
            this.endPress();
        }
    }
    
    endPress() {
        this.isPressed = false;
        if (this.pressTimer) {
            clearTimeout(this.pressTimer);
            this.pressTimer = null;
        }
        this.callbacks.onLongPressEnd();
    }
    
    on(event, callback) {
        if (this.callbacks.hasOwnProperty(event)) {
            this.callbacks[event] = callback;
        }
        return this;
    }
    
    destroy() {
        this.element.removeEventListener('touchstart', this.handleTouchStart);
        this.element.removeEventListener('touchmove', this.handleTouchMove);
        this.element.removeEventListener('touchend', this.handleTouchEnd);
        this.element.removeEventListener('touchcancel', this.handleTouchEnd);
        this.element.removeEventListener('mousedown', this.handleMouseDown);
        this.element.removeEventListener('mousemove', this.handleMouseMove);
        this.element.removeEventListener('mouseup', this.handleMouseUp);
        this.element.removeEventListener('mouseleave', this.handleMouseUp);
    }
}

/**
 * PinchZoom - Handles pinch-to-zoom gestures
 */
export class PinchZoom {
    constructor(element, options = {}) {
        this.element = element;
        this.options = {
            minScale: 0.5,
            maxScale: 4,
            ...GESTURE_CONFIG,
            ...options
        };
        
        this.callbacks = {
            onPinchStart: () => {},
            onPinchMove: () => {},
            onPinchEnd: () => {},
            onZoomIn: () => {},
            onZoomOut: () => {},
        };
        
        this.currentScale = 1;
        this.initialDistance = 0;
        this.isPinching = false;
        
        this.bindEvents();
    }
    
    bindEvents() {
        this.element.addEventListener('touchstart', this.handleTouchStart.bind(this), { passive: true });
        this.element.addEventListener('touchmove', this.handleTouchMove.bind(this), { passive: false });
        this.element.addEventListener('touchend', this.handleTouchEnd.bind(this), { passive: true });
    }
    
    getDistance(touches) {
        const dx = touches[0].clientX - touches[1].clientX;
        const dy = touches[0].clientY - touches[1].clientY;
        return Math.sqrt(dx * dx + dy * dy);
    }
    
    getCenter(touches) {
        return {
            x: (touches[0].clientX + touches[1].clientX) / 2,
            y: (touches[0].clientY + touches[1].clientY) / 2
        };
    }
    
    handleTouchStart(e) {
        if (e.touches.length === 2) {
            this.isPinching = true;
            this.initialDistance = this.getDistance(e.touches);
            this.initialScale = this.currentScale;
            
            this.callbacks.onPinchStart({
                center: this.getCenter(e.touches),
                scale: this.currentScale
            });
        }
    }
    
    handleTouchMove(e) {
        if (!this.isPinching || e.touches.length !== 2) return;
        
        e.preventDefault();
        
        const currentDistance = this.getDistance(e.touches);
        const scaleChange = currentDistance / this.initialDistance;
        let newScale = this.initialScale * scaleChange;
        
        // Clamp scale
        newScale = Math.max(this.options.minScale, Math.min(this.options.maxScale, newScale));
        
        this.currentScale = newScale;
        
        this.callbacks.onPinchMove({
            center: this.getCenter(e.touches),
            scale: this.currentScale,
            delta: newScale - this.initialScale
        });
    }
    
    handleTouchEnd(e) {
        if (this.isPinching && e.touches.length < 2) {
            this.isPinching = false;
            
            const zoomedIn = this.currentScale > this.initialScale;
            
            this.callbacks.onPinchEnd({ scale: this.currentScale });
            
            if (zoomedIn) {
                this.callbacks.onZoomIn({ scale: this.currentScale });
            } else {
                this.callbacks.onZoomOut({ scale: this.currentScale });
            }
        }
    }
    
    on(event, callback) {
        if (this.callbacks.hasOwnProperty(event)) {
            this.callbacks[event] = callback;
        }
        return this;
    }
    
    reset() {
        this.currentScale = 1;
    }
    
    destroy() {
        this.element.removeEventListener('touchstart', this.handleTouchStart);
        this.element.removeEventListener('touchmove', this.handleTouchMove);
        this.element.removeEventListener('touchend', this.handleTouchEnd);
    }
}

// Alpine.js directive registration
export function registerGestureDirectives() {
    if (typeof Alpine !== 'undefined') {
        // x-swipe directive
        Alpine.directive('swipe', (el, { expression, modifiers }, { evaluate, cleanup }) => {
            const swipe = new SwipeDetector(el);
            
            if (modifiers.includes('left')) {
                swipe.on('onSwipeLeft', () => evaluate(expression));
            }
            if (modifiers.includes('right')) {
                swipe.on('onSwipeRight', () => evaluate(expression));
            }
            if (modifiers.includes('up')) {
                swipe.on('onSwipeUp', () => evaluate(expression));
            }
            if (modifiers.includes('down')) {
                swipe.on('onSwipeDown', () => evaluate(expression));
            }
            
            cleanup(() => swipe.destroy());
        });
        
        // x-long-press directive
        Alpine.directive('long-press', (el, { expression }, { evaluate, cleanup }) => {
            const longPress = new LongPressDetector(el);
            longPress.on('onLongPress', () => evaluate(expression));
            
            cleanup(() => longPress.destroy());
        });
    }
}

// Auto-initialize on DOM ready
if (typeof document !== 'undefined') {
    document.addEventListener('DOMContentLoaded', registerGestureDirectives);
}

export default {
    SwipeDetector,
    PullToRefresh,
    LongPressDetector,
    PinchZoom,
    registerGestureDirectives,
    GESTURE_CONFIG
};
