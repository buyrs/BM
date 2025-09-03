import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { nextTick } from 'vue'

// Mock different viewport sizes
const setViewport = (width, height) => {
  Object.defineProperty(window, 'innerWidth', { value: width, writable: true })
  Object.defineProperty(window, 'innerHeight', { value: height, writable: true })
  Object.defineProperty(window.screen, 'width', { value: width, writable: true })
  Object.defineProperty(window.screen, 'height', { value: height, writable: true })
  
  // Trigger resize event
  window.dispatchEvent(new Event('resize'))
}

// Common viewport sizes
const VIEWPORTS = {
  mobile: { width: 375, height: 667 },
  tablet: { width: 768, height: 1024 },
  desktop: { width: 1920, height: 1080 }
}

describe('Responsive Components', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  describe('Dashboard Responsiveness', () => {
    let wrapper

    afterEach(() => {
      if (wrapper) {
        wrapper.unmount()
      }
    })

    it('adapts admin dashboard for mobile', async () => {
      setViewport(VIEWPORTS.mobile.width, VIEWPORTS.mobile.height)
      
      const AdminDashboard = (await import('@/Pages/Admin/Dashboard.vue')).default
      wrapper = mount(AdminDashboard, {
        props: {
          stats: { totalBailMobilites: 45 },
          checkers: [],
          recentActivities: []
        }
      })

      await nextTick()

      // Should stack components vertically on mobile
      const dashboard = wrapper.find('.admin-dashboard')
      expect(dashboard.classes()).toContain('mobile-layout')
      
      // Stats should be in a scrollable horizontal container
      const statsGrid = wrapper.find('.stats-grid')
      expect(statsGrid.classes()).toContain('horizontal-scroll')
    })

    it('adapts ops dashboard for tablet', async () => {
      setViewport(VIEWPORTS.tablet.width, VIEWPORTS.tablet.height)
      
      const OpsDashboard = (await import('@/Pages/Ops/Dashboard.vue')).default
      wrapper = mount(OpsDashboard, {
        props: {
          bailMobilites: [],
          metrics: {},
          notifications: []
        }
      })

      await nextTick()

      // Should use tablet-optimized layout
      const dashboard = wrapper.find('.ops-dashboard')
      expect(dashboard.classes()).toContain('tablet-layout')
      
      // Kanban board should have fewer columns on tablet
      const kanbanBoard = wrapper.find('.kanban-board')
      expect(kanbanBoard.classes()).toContain('tablet-columns')
    })

    it('adapts checker dashboard for mobile', async () => {
      setViewport(VIEWPORTS.mobile.width, VIEWPORTS.mobile.height)
      
      const CheckerDashboard = (await import('@/Pages/Checker/Dashboard.vue')).default
      wrapper = mount(CheckerDashboard, {
        props: {
          missions: [],
          stats: {},
          urgentMissions: []
        }
      })

      await nextTick()

      // Should prioritize urgent missions on mobile
      const urgentSection = wrapper.find('.urgent-missions')
      expect(urgentSection.classes()).toContain('mobile-priority')
      
      // Stats should be condensed
      const statsCards = wrapper.find('.stats-cards')
      expect(statsCards.classes()).toContain('mobile-condensed')
    })
  })

  describe('Component Breakpoint Behavior', () => {
    let wrapper

    afterEach(() => {
      if (wrapper) {
        wrapper.unmount()
      }
    })

    it('handles mission card responsiveness', async () => {
      const MissionCard = (await import('@/Components/MissionCard.vue')).default
      const mockMission = {
        id: 1,
        type: 'entry',
        tenant_name: 'John Doe',
        address: '123 Main St',
        status: 'assigned'
      }

      // Test mobile layout
      setViewport(VIEWPORTS.mobile.width, VIEWPORTS.mobile.height)
      wrapper = mount(MissionCard, {
        props: { mission: mockMission }
      })

      await nextTick()
      expect(wrapper.find('.mission-card').classes()).toContain('mobile')
      
      // Test tablet layout
      setViewport(VIEWPORTS.tablet.width, VIEWPORTS.tablet.height)
      await nextTick()
      expect(wrapper.find('.mission-card').classes()).toContain('tablet')
      
      // Test desktop layout
      setViewport(VIEWPORTS.desktop.width, VIEWPORTS.desktop.height)
      await nextTick()
      expect(wrapper.find('.mission-card').classes()).toContain('desktop')
    })

    it('handles kanban board column adaptation', async () => {
      const KanbanBoard = (await import('@/Components/KanbanBoard.vue')).default
      const mockBailMobilites = [
        { id: 1, status: 'assigned', tenant_name: 'John' },
        { id: 2, status: 'in_progress', tenant_name: 'Jane' }
      ]

      wrapper = mount(KanbanBoard, {
        props: { bailMobilites: mockBailMobilites }
      })

      // Mobile: Single column view
      setViewport(VIEWPORTS.mobile.width, VIEWPORTS.mobile.height)
      await nextTick()
      
      const mobileColumns = wrapper.findAll('.kanban-column:not(.hidden)')
      expect(mobileColumns.length).toBe(1) // Only active column shown
      
      // Tablet: Two columns
      setViewport(VIEWPORTS.tablet.width, VIEWPORTS.tablet.height)
      await nextTick()
      
      const tabletColumns = wrapper.findAll('.kanban-column:not(.hidden)')
      expect(tabletColumns.length).toBe(2)
      
      // Desktop: All columns
      setViewport(VIEWPORTS.desktop.width, VIEWPORTS.desktop.height)
      await nextTick()
      
      const desktopColumns = wrapper.findAll('.kanban-column')
      expect(desktopColumns.length).toBeGreaterThanOrEqual(3)
    })

    it('handles signature pad fullscreen on mobile', async () => {
      const SignaturePad = (await import('@/Components/SignaturePad.vue')).default
      
      setViewport(VIEWPORTS.mobile.width, VIEWPORTS.mobile.height)
      wrapper = mount(SignaturePad, {
        props: { title: 'Test', modelValue: null }
      })

      await nextTick()

      // Should automatically enter fullscreen on mobile
      expect(wrapper.find('.signature-pad-wrapper').classes()).toContain('mobile-fullscreen')
      
      // Canvas should fill viewport
      const canvas = wrapper.find('canvas')
      expect(canvas.attributes('width')).toBe(VIEWPORTS.mobile.width.toString())
    })
  })

  describe('Navigation Responsiveness', () => {
    let wrapper

    afterEach(() => {
      if (wrapper) {
        wrapper.unmount()
      }
    })

    it('switches to mobile navigation on small screens', async () => {
      const AppLayout = (await import('@/Layouts/AppLayout.vue')).default
      
      setViewport(VIEWPORTS.mobile.width, VIEWPORTS.mobile.height)
      wrapper = mount(AppLayout, {
        props: {
          user: { name: 'Test User', roles: ['checker'] }
        }
      })

      await nextTick()

      // Desktop navigation should be hidden
      const desktopNav = wrapper.find('.desktop-navigation')
      expect(desktopNav.exists()).toBe(false)
      
      // Mobile navigation should be visible
      const mobileNav = wrapper.find('.mobile-navigation')
      expect(mobileNav.exists()).toBe(true)
    })

    it('handles hamburger menu on mobile', async () => {
      const MobileNavigation = (await import('@/Components/MobileNavigation.vue')).default
      
      setViewport(VIEWPORTS.mobile.width, VIEWPORTS.mobile.height)
      wrapper = mount(MobileNavigation, {
        props: {
          currentRoute: 'dashboard',
          user: { name: 'Test User' }
        }
      })

      // Menu should be closed initially
      expect(wrapper.find('.nav-menu').classes()).toContain('closed')
      
      // Click hamburger button
      const hamburger = wrapper.find('[data-testid="hamburger-menu"]')
      await hamburger.trigger('click')
      
      // Menu should open
      expect(wrapper.find('.nav-menu').classes()).toContain('open')
    })
  })

  describe('Form Responsiveness', () => {
    let wrapper

    afterEach(() => {
      if (wrapper) {
        wrapper.unmount()
      }
    })

    it('adapts checklist form for mobile', async () => {
      const ChecklistForm = (await import('@/Components/ChecklistForm.vue')).default
      const mockMission = { id: 1, type: 'entry' }
      const mockChecklist = {
        items: [
          { id: 1, name: 'Check keys', condition: null }
        ]
      }

      setViewport(VIEWPORTS.mobile.width, VIEWPORTS.mobile.height)
      wrapper = mount(ChecklistForm, {
        props: { mission: mockMission, checklist: mockChecklist }
      })

      await nextTick()

      // Form should stack vertically on mobile
      const form = wrapper.find('.checklist-form')
      expect(form.classes()).toContain('mobile-stack')
      
      // Input fields should be full width
      const inputs = wrapper.findAll('input, select, textarea')
      inputs.forEach(input => {
        expect(input.classes()).toContain('full-width')
      })
    })

    it('optimizes photo upload for mobile', async () => {
      const PhotoUploader = (await import('@/Components/PhotoUploader.vue')).default
      
      setViewport(VIEWPORTS.mobile.width, VIEWPORTS.mobile.height)
      wrapper = mount(PhotoUploader, {
        props: { maxFiles: 5 }
      })

      await nextTick()

      // Should show camera option on mobile
      const cameraButton = wrapper.find('[data-testid="camera-capture"]')
      expect(cameraButton.exists()).toBe(true)
      
      // Upload area should be touch-friendly
      const uploadArea = wrapper.find('.upload-area')
      expect(uploadArea.classes()).toContain('touch-optimized')
    })
  })

  describe('Modal and Overlay Responsiveness', () => {
    let wrapper

    afterEach(() => {
      if (wrapper) {
        wrapper.unmount()
      }
    })

    it('adapts modals for mobile screens', async () => {
      const Modal = (await import('@/Components/Modal.vue')).default
      
      setViewport(VIEWPORTS.mobile.width, VIEWPORTS.mobile.height)
      wrapper = mount(Modal, {
        props: { show: true },
        slots: {
          default: '<div>Modal content</div>'
        }
      })

      await nextTick()

      // Modal should be full screen on mobile
      const modal = wrapper.find('.modal')
      expect(modal.classes()).toContain('mobile-fullscreen')
      
      // Should have mobile-specific close button
      const closeButton = wrapper.find('[data-testid="mobile-close"]')
      expect(closeButton.exists()).toBe(true)
    })

    it('handles contract preview on mobile', async () => {
      const ContractPreview = (await import('@/Components/ContractPreview.vue')).default
      const mockContract = {
        content: 'Contract content',
        variables: { tenant_name: 'John Doe' }
      }

      setViewport(VIEWPORTS.mobile.width, VIEWPORTS.mobile.height)
      wrapper = mount(ContractPreview, {
        props: { contract: mockContract }
      })

      await nextTick()

      // Should be scrollable on mobile
      const preview = wrapper.find('.contract-preview')
      expect(preview.classes()).toContain('mobile-scroll')
      
      // Text should be readable size
      expect(preview.classes()).toContain('mobile-text-size')
    })
  })

  describe('Performance on Different Screen Sizes', () => {
    it('lazy loads components based on viewport', async () => {
      const mockIntersectionObserver = vi.fn()
      mockIntersectionObserver.mockReturnValue({
        observe: vi.fn(),
        unobserve: vi.fn(),
        disconnect: vi.fn()
      })
      
      window.IntersectionObserver = mockIntersectionObserver

      setViewport(VIEWPORTS.mobile.width, VIEWPORTS.mobile.height)
      
      const LazyComponent = (await import('@/Components/LazyLoadedComponent.vue')).default
      const wrapper = mount(LazyComponent)

      expect(mockIntersectionObserver).toHaveBeenCalled()
      
      wrapper.unmount()
    })

    it('optimizes images for different screen densities', async () => {
      const PhotoUploader = (await import('@/Components/PhotoUploader.vue')).default
      
      // Mock high DPI screen
      Object.defineProperty(window, 'devicePixelRatio', { value: 2, writable: true })
      
      setViewport(VIEWPORTS.mobile.width, VIEWPORTS.mobile.height)
      const wrapper = mount(PhotoUploader, {
        props: { maxFiles: 5 }
      })

      await nextTick()

      // Should request higher resolution images
      const images = wrapper.findAll('img')
      images.forEach(img => {
        expect(img.attributes('srcset')).toContain('2x')
      })
      
      wrapper.unmount()
    })
  })

  describe('Accessibility on Mobile', () => {
    it('maintains accessibility on touch devices', async () => {
      const MissionCard = (await import('@/Components/MissionCard.vue')).default
      const mockMission = {
        id: 1,
        type: 'entry',
        tenant_name: 'John Doe',
        status: 'assigned'
      }

      setViewport(VIEWPORTS.mobile.width, VIEWPORTS.mobile.height)
      const wrapper = mount(MissionCard, {
        props: { mission: mockMission }
      })

      await nextTick()

      const card = wrapper.find('.mission-card')
      
      // Should have proper touch target size
      const rect = card.element.getBoundingClientRect()
      expect(rect.height).toBeGreaterThanOrEqual(44) // iOS minimum
      
      // Should have proper ARIA labels
      expect(card.attributes('aria-label')).toBeDefined()
      expect(card.attributes('role')).toBe('button')
      
      wrapper.unmount()
    })

    it('supports screen reader navigation on mobile', async () => {
      const ChecklistForm = (await import('@/Components/ChecklistForm.vue')).default
      const mockMission = { id: 1, type: 'entry' }
      const mockChecklist = {
        items: [{ id: 1, name: 'Check keys', condition: null }]
      }

      setViewport(VIEWPORTS.mobile.width, VIEWPORTS.mobile.height)
      const wrapper = mount(ChecklistForm, {
        props: { mission: mockMission, checklist: mockChecklist }
      })

      await nextTick()

      // Form should have proper heading structure
      const headings = wrapper.findAll('h1, h2, h3, h4, h5, h6')
      expect(headings.length).toBeGreaterThan(0)
      
      // Form fields should have labels
      const inputs = wrapper.findAll('input, select, textarea')
      inputs.forEach(input => {
        const id = input.attributes('id')
        if (id) {
          const label = wrapper.find(`label[for="${id}"]`)
          expect(label.exists()).toBe(true)
        }
      })
      
      wrapper.unmount()
    })
  })
})