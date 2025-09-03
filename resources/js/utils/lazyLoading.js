import { defineAsyncComponent } from 'vue';
import LoadingSpinner from '../Components/LoadingSpinner.vue';
import ErrorBoundary from '../Components/ErrorBoundary.vue';

/**
 * Create a lazy-loaded component with loading and error states
 * @param {Function} loader - Dynamic import function
 * @param {Object} options - Configuration options
 * @returns {Object} Vue async component
 */
export function createLazyComponent(loader, options = {}) {
    return defineAsyncComponent({
        loader,
        loadingComponent: options.loadingComponent || LoadingSpinner,
        errorComponent: options.errorComponent || ErrorBoundary,
        delay: options.delay || 200,
        timeout: options.timeout || 10000,
        suspensible: options.suspensible !== false,
        onError: (error, retry, fail, attempts) => {
            console.error(`Failed to load component (attempt ${attempts}):`, error);
            
            // Retry up to 3 times
            if (attempts <= 3) {
                retry();
            } else {
                fail();
            }
        }
    });
}

/**
 * Create lazy-loaded dashboard components
 */
export const LazyDashboardComponents = {
    // Admin Dashboard Components
    AdminDashboard: createLazyComponent(() => import('../Pages/Admin/Dashboard.vue')),
    StatsGrid: createLazyComponent(() => import('../Components/Admin/StatsGrid.vue')),
    RecentActivity: createLazyComponent(() => import('../Components/Admin/RecentActivity.vue')),
    CheckerManagement: createLazyComponent(() => import('../Components/Admin/CheckerManagement.vue')),
    SystemHealth: createLazyComponent(() => import('../Components/Admin/SystemHealth.vue')),
    
    // Ops Dashboard Components
    OpsDashboard: createLazyComponent(() => import('../Pages/Ops/Dashboard.vue')),
    KanbanBoard: createLazyComponent(() => import('../Components/KanbanBoard.vue')),
    OverviewStats: createLazyComponent(() => import('../Components/OverviewStats.vue')),
    AnalyticsView: createLazyComponent(() => import('../Components/AnalyticsView.vue')),
    NotificationPanel: createLazyComponent(() => import('../Components/NotificationPanel.vue')),
    
    // Checker Dashboard Components
    CheckerDashboard: createLazyComponent(() => import('../Pages/Checker/Dashboard.vue')),
    UrgentMissions: createLazyComponent(() => import('../Components/Checker/UrgentMissions.vue')),
    StatsCards: createLazyComponent(() => import('../Components/Checker/StatsCards.vue')),
    TodaySchedule: createLazyComponent(() => import('../Components/Checker/TodaySchedule.vue')),
    QuickActions: createLazyComponent(() => import('../Components/Checker/QuickActions.vue')),
    
    // Heavy Components
    SignaturePad: createLazyComponent(() => import('../Components/SignaturePad.vue')),
    ContractSignatureFlow: createLazyComponent(() => import('../Components/ContractSignatureFlow.vue')),
    ContractTemplateManager: createLazyComponent(() => import('../Components/ContractTemplateManager.vue')),
    RichTextEditor: createLazyComponent(() => import('../Components/RichTextEditor.vue')),
    
    // Chart Components
    Charts: {
        LineChart: createLazyComponent(() => import('../Components/Charts/LineChart.vue')),
        BarChart: createLazyComponent(() => import('../Components/Charts/BarChart.vue')),
        PieChart: createLazyComponent(() => import('../Components/Charts/PieChart.vue')),
        DoughnutChart: createLazyComponent(() => import('../Components/Charts/DoughnutChart.vue'))
    }
};

/**
 * Preload critical components for better UX
 */
export function preloadCriticalComponents() {
    const criticalComponents = [
        () => import('../Components/LoadingSpinner.vue'),
        () => import('../Components/ErrorBoundary.vue'),
        () => import('../Components/Modal.vue'),
        () => import('../Components/PrimaryButton.vue'),
        () => import('../Components/SecondaryButton.vue')
    ];
    
    // Preload in the background
    setTimeout(() => {
        criticalComponents.forEach(loader => {
            loader().catch(error => {
                console.warn('Failed to preload critical component:', error);
            });
        });
    }, 1000);
}

/**
 * Intersection Observer for lazy loading components when they come into view
 */
export function createIntersectionObserver(callback, options = {}) {
    const defaultOptions = {
        root: null,
        rootMargin: '50px',
        threshold: 0.1
    };
    
    return new IntersectionObserver(callback, { ...defaultOptions, ...options });
}

/**
 * Vue directive for lazy loading components on scroll
 */
export const vLazyLoad = {
    mounted(el, binding) {
        const observer = createIntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    binding.value();
                    observer.unobserve(el);
                }
            });
        });
        
        observer.observe(el);
        el._observer = observer;
    },
    unmounted(el) {
        if (el._observer) {
            el._observer.disconnect();
        }
    }
};