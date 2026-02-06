/**
 * Page Transitions Module
 * Smooth page transitions using View Transitions API with fallback
 * 
 * Features:
 * - View Transitions API for modern browsers
 * - CSS fallback for older browsers
 * - Configurable transition types
 * - Progress indicator
 */

class PageTransitions {
    constructor(options = {}) {
        this.options = {
            duration: options.duration || 300,
            type: options.type || 'fade', // fade, slide-left, slide-right, slide-up
            showProgress: options.showProgress !== false,
            ...options
        };

        this.supportsViewTransitions = 'startViewTransition' in document;
        this.isTransitioning = false;

        this.init();
    }

    init() {
        // Intercept link clicks for SPA-like transitions
        this.interceptLinks();

        // Create progress indicator
        if (this.options.showProgress) {
            this.createProgressIndicator();
        }

        // Add page load animation
        this.animatePageLoad();
    }

    interceptLinks() {
        document.addEventListener('click', (e) => {
            const link = e.target.closest('a');

            if (!link) return;
            if (link.target === '_blank') return;
            if (link.hasAttribute('data-no-transition')) return;
            if (link.href.startsWith('#')) return;
            if (link.href.startsWith('javascript:')) return;
            if (link.href.includes('mailto:')) return;
            if (link.href.includes('tel:')) return;
            if (e.ctrlKey || e.metaKey || e.shiftKey) return;

            // Check if same origin
            try {
                const url = new URL(link.href);
                if (url.origin !== window.location.origin) return;
            } catch {
                return;
            }

            e.preventDefault();
            this.navigate(link.href);
        });

        // Handle browser back/forward
        window.addEventListener('popstate', () => {
            this.navigate(window.location.href, { replace: true });
        });
    }

    async navigate(url, options = {}) {
        if (this.isTransitioning) return;
        this.isTransitioning = true;

        // Show progress
        this.showProgress();

        // Haptic feedback
        if (window.haptics) {
            window.haptics.selection();
        }

        try {
            if (this.supportsViewTransitions) {
                await this.navigateWithViewTransition(url, options);
            } else {
                await this.navigateWithFallback(url, options);
            }
        } catch (error) {
            console.error('Navigation error:', error);
            // Fallback to regular navigation
            window.location.href = url;
        } finally {
            this.isTransitioning = false;
            this.hideProgress();
        }
    }

    async navigateWithViewTransition(url, options) {
        const transition = document.startViewTransition(async () => {
            // Fetch the new page
            const response = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const html = await response.text();

            // Parse and update the DOM
            const parser = new DOMParser();
            const newDoc = parser.parseFromString(html, 'text/html');

            // Update the main content
            const newContent = newDoc.querySelector('main') || newDoc.body;
            const currentContent = document.querySelector('main') || document.body;

            // Update title
            document.title = newDoc.title;

            // Update content
            currentContent.innerHTML = newContent.innerHTML;

            // Update URL
            if (!options.replace) {
                history.pushState({}, '', url);
            }

            // Reinitialize Alpine components
            if (window.Alpine) {
                window.Alpine.initTree(currentContent);
            }

            // Scroll to top
            window.scrollTo({ top: 0, behavior: 'instant' });
        });

        await transition.finished;
    }

    async navigateWithFallback(url, options) {
        const main = document.querySelector('main') || document.body;

        // Fade out
        main.style.transition = `opacity ${this.options.duration}ms ease-out`;
        main.style.opacity = '0';

        await new Promise(resolve => setTimeout(resolve, this.options.duration));

        // Fetch new page
        const response = await fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html'
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        const html = await response.text();
        const parser = new DOMParser();
        const newDoc = parser.parseFromString(html, 'text/html');

        const newContent = newDoc.querySelector('main') || newDoc.body;

        // Update content
        document.title = newDoc.title;
        main.innerHTML = newContent.innerHTML;

        // Update URL
        if (!options.replace) {
            history.pushState({}, '', url);
        }

        // Reinitialize Alpine
        if (window.Alpine) {
            window.Alpine.initTree(main);
        }

        // Fade in
        main.style.opacity = '1';

        // Scroll to top
        window.scrollTo({ top: 0, behavior: 'instant' });

        await new Promise(resolve => setTimeout(resolve, this.options.duration));
        main.style.transition = '';
    }

    createProgressIndicator() {
        const indicator = document.createElement('div');
        indicator.id = 'page-transition-progress';
        indicator.innerHTML = `
            <div class="fixed top-0 left-0 right-0 z-[9999] h-1 bg-transparent pointer-events-none">
                <div class="h-full bg-primary-500 transition-all duration-300 ease-out" style="width: 0%"></div>
            </div>
        `;
        document.body.appendChild(indicator);

        this.progressBar = indicator.querySelector('div > div');
    }

    showProgress() {
        if (!this.progressBar) return;

        this.progressBar.style.width = '0%';
        this.progressBar.style.opacity = '1';

        // Animate progress
        let progress = 0;
        this.progressInterval = setInterval(() => {
            progress += Math.random() * 15;
            if (progress > 90) progress = 90;
            this.progressBar.style.width = `${progress}%`;
        }, 100);
    }

    hideProgress() {
        if (!this.progressBar) return;

        clearInterval(this.progressInterval);
        this.progressBar.style.width = '100%';

        setTimeout(() => {
            this.progressBar.style.opacity = '0';
            setTimeout(() => {
                this.progressBar.style.width = '0%';
            }, 300);
        }, 200);
    }

    animatePageLoad() {
        // Add initial animation class
        document.body.classList.add('page-loaded');

        // Animate main content
        const main = document.querySelector('main');
        if (main) {
            main.style.opacity = '0';
            main.style.transform = 'translateY(10px)';

            requestAnimationFrame(() => {
                main.style.transition = 'opacity 0.3s ease-out, transform 0.3s ease-out';
                main.style.opacity = '1';
                main.style.transform = 'translateY(0)';

                setTimeout(() => {
                    main.style.transition = '';
                    main.style.transform = '';
                }, 300);
            });
        }
    }
}

// CSS for View Transitions API
const viewTransitionStyles = `
    @keyframes fade-in {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    @keyframes fade-out {
        from { opacity: 1; }
        to { opacity: 0; }
    }
    
    @keyframes slide-from-right {
        from { transform: translateX(30px); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    
    @keyframes slide-to-left {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(-30px); opacity: 0; }
    }
    
    ::view-transition-old(root) {
        animation: 150ms cubic-bezier(0.4, 0, 1, 1) both fade-out,
                   300ms cubic-bezier(0.4, 0, 0.2, 1) both slide-to-left;
    }
    
    ::view-transition-new(root) {
        animation: 210ms cubic-bezier(0, 0, 0.2, 1) 90ms both fade-in,
                   300ms cubic-bezier(0.4, 0, 0.2, 1) both slide-from-right;
    }
    
    /* Reduce motion for users who prefer it */
    @media (prefers-reduced-motion: reduce) {
        ::view-transition-old(root),
        ::view-transition-new(root) {
            animation: none;
        }
    }
`;

// Inject styles
const styleSheet = document.createElement('style');
styleSheet.textContent = viewTransitionStyles;
document.head.appendChild(styleSheet);

// Create singleton
let pageTransitions = null;

function initPageTransitions(options = {}) {
    if (!pageTransitions) {
        pageTransitions = new PageTransitions(options);
    }
    return pageTransitions;
}

// Auto-initialize on DOM ready
if (typeof document !== 'undefined') {
    document.addEventListener('DOMContentLoaded', () => {
        // Only init if not explicitly disabled
        if (!document.body.hasAttribute('data-no-page-transitions')) {
            initPageTransitions();
        }
    });
}

export { PageTransitions, initPageTransitions };
export default initPageTransitions;
