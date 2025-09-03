/**
 * Test Configuration and Utilities
 * 
 * This file contains shared configuration and utilities for all test files
 */

import { vi } from 'vitest'

// Global test configuration
export const TEST_CONFIG = {
  // Timeout settings
  timeouts: {
    unit: 5000,
    integration: 10000,
    e2e: 30000,
    performance: 15000
  },
  
  // Performance thresholds
  performance: {
    componentRender: 100, // ms
    dataUpdate: 50, // ms
    userInteraction: 200, // ms
    largeDataset: 1000 // ms
  },
  
  // Mobile viewport sizes
  viewports: {
    mobile: { width: 375, height: 667 },
    tablet: { width: 768, height: 1024 },
    desktop: { width: 1920, height: 1080 }
  }
}

// Common test utilities
export const TestUtils = {
  // Create mock user data
  createMockUser: (role = 'checker') => ({
    id: 1,
    name: 'Test User',
    email: 'test@example.com',
    roles: [role]
  }),

  // Create mock mission data
  createMockMission: (overrides = {}) => ({
    id: 1,
    type: 'entry',
    status: 'assigned',
    scheduled_at: '2024-01-15',
    scheduled_time: '10:00',
    tenant_name: 'John Doe',
    address: '123 Main St',
    agent: { id: 1, name: 'Agent Smith' },
    bail_mobilite: { id: 1 },
    can_edit: true,
    can_complete: true,
    ...overrides
  }),

  // Create mock bail mobilité data
  createMockBailMobilite: (overrides = {}) => ({
    id: 1,
    tenant_name: 'John Doe',
    status: 'assigned',
    start_date: '2024-01-15',
    end_date: '2024-02-15',
    address: '123 Main St',
    entry_mission: { id: 1, status: 'assigned' },
    exit_mission: { id: 2, status: 'pending' },
    ...overrides
  }),

  // Create mock contract data
  createMockContract: (overrides = {}) => ({
    id: 1,
    template_id: 1,
    bail_mobilite_id: 1,
    content: 'Contract content with {{tenant_name}}',
    admin_signature: 'admin-signature-data',
    admin_signed_at: '2024-01-15T10:00:00Z',
    variables: {
      tenant_name: 'John Doe',
      address: '123 Main St'
    },
    ...overrides
  }),

  // Wait for next tick with timeout
  waitForNextTick: (timeout = 100) => {
    return new Promise((resolve, reject) => {
      const timer = setTimeout(() => {
        reject(new Error(`waitForNextTick timeout after ${timeout}ms`))
      }, timeout)
      
      setTimeout(() => {
        clearTimeout(timer)
        resolve()
      }, 0)
    })
  },

  // Mock touch event
  createTouchEvent: (type, touches = [], changedTouches = []) => {
    const event = new Event(type, { bubbles: true, cancelable: true })
    Object.defineProperty(event, 'touches', { value: touches, writable: false })
    Object.defineProperty(event, 'changedTouches', { value: changedTouches, writable: false })
    Object.defineProperty(event, 'targetTouches', { value: touches, writable: false })
    event.preventDefault = vi.fn()
    event.stopPropagation = vi.fn()
    return event
  },

  // Mock touch point
  createTouch: (identifier, clientX, clientY, target = null) => ({
    identifier,
    clientX,
    clientY,
    pageX: clientX,
    pageY: clientY,
    screenX: clientX,
    screenY: clientY,
    target: target || document.createElement('div')
  }),

  // Set viewport size
  setViewport: (width, height) => {
    Object.defineProperty(window, 'innerWidth', { value: width, writable: true })
    Object.defineProperty(window, 'innerHeight', { value: height, writable: true })
    Object.defineProperty(window.screen, 'width', { value: width, writable: true })
    Object.defineProperty(window.screen, 'height', { value: height, writable: true })
    window.dispatchEvent(new Event('resize'))
  },

  // Mock performance measurement
  measurePerformance: async (fn) => {
    const start = performance.now()
    await fn()
    const end = performance.now()
    return end - start
  },

  // Generate test data
  generateTestData: (count, type) => {
    const data = []
    for (let i = 1; i <= count; i++) {
      switch (type) {
        case 'missions':
          data.push(TestUtils.createMockMission({ id: i, tenant_name: `Tenant ${i}` }))
          break
        case 'bailMobilites':
          data.push(TestUtils.createMockBailMobilite({ id: i, tenant_name: `Tenant ${i}` }))
          break
        case 'notifications':
          data.push({
            id: i,
            type: 'mission_completed',
            title: `Notification ${i}`,
            message: `Message ${i}`,
            priority: 'normal',
            read_at: null,
            created_at: new Date().toISOString()
          })
          break
        default:
          data.push({ id: i, name: `Item ${i}` })
      }
    }
    return data
  }
}

// Common mock implementations
export const CommonMocks = {
  // Mock Inertia
  inertia: () => ({
    router: {
      get: vi.fn(),
      post: vi.fn(),
      patch: vi.fn(),
      delete: vi.fn(),
      visit: vi.fn()
    },
    usePage: () => ({
      props: {
        auth: {
          user: TestUtils.createMockUser()
        }
      }
    }),
    Head: { template: '<head><slot /></head>' },
    Link: { template: '<a><slot /></a>' }
  }),

  // Mock Ziggy
  ziggy: () => ({
    route: vi.fn((name, params) => `/mock-route/${name}${params ? `/${params}` : ''}`)
  }),

  // Mock Axios
  axios: () => {
    const mockAxios = {
      get: vi.fn(),
      post: vi.fn(),
      patch: vi.fn(),
      delete: vi.fn()
    }
    
    // Default successful responses
    mockAxios.get.mockResolvedValue({ data: {} })
    mockAxios.post.mockResolvedValue({ data: { success: true, id: 1 } })
    mockAxios.patch.mockResolvedValue({ data: { success: true } })
    mockAxios.delete.mockResolvedValue({ data: { success: true } })
    
    return mockAxios
  },

  // Mock Canvas
  canvas: () => {
    const mockContext = {
      clearRect: vi.fn(),
      beginPath: vi.fn(),
      moveTo: vi.fn(),
      lineTo: vi.fn(),
      stroke: vi.fn(),
      strokeStyle: '',
      lineWidth: 2,
      lineCap: 'round',
      lineJoin: 'round'
    }

    return {
      getContext: vi.fn(() => mockContext),
      toDataURL: vi.fn(() => 'data:image/png;base64,mock-data'),
      getBoundingClientRect: vi.fn(() => ({
        left: 0,
        top: 0,
        width: 300,
        height: 150
      })),
      width: 300,
      height: 150
    }
  },

  // Mock Geolocation
  geolocation: () => ({
    getCurrentPosition: vi.fn((success) => {
      success({
        coords: {
          latitude: 48.8566,
          longitude: 2.3522,
          accuracy: 10
        }
      })
    }),
    watchPosition: vi.fn(),
    clearWatch: vi.fn()
  }),

  // Mock File API
  file: (name = 'test.jpg', type = 'image/jpeg', size = 1024) => {
    return new File([new ArrayBuffer(size)], name, { type })
  }
}

// Test environment setup
export const setupTestEnvironment = () => {
  // Mock window.matchMedia
  Object.defineProperty(window, 'matchMedia', {
    writable: true,
    value: vi.fn().mockImplementation(query => ({
      matches: false,
      media: query,
      onchange: null,
      addListener: vi.fn(),
      removeListener: vi.fn(),
      addEventListener: vi.fn(),
      removeEventListener: vi.fn(),
      dispatchEvent: vi.fn(),
    })),
  })

  // Mock ResizeObserver
  global.ResizeObserver = vi.fn().mockImplementation(() => ({
    observe: vi.fn(),
    unobserve: vi.fn(),
    disconnect: vi.fn(),
  }))

  // Mock IntersectionObserver
  global.IntersectionObserver = vi.fn().mockImplementation(() => ({
    observe: vi.fn(),
    unobserve: vi.fn(),
    disconnect: vi.fn(),
  }))

  // Mock navigator.geolocation
  Object.defineProperty(global.navigator, 'geolocation', {
    value: CommonMocks.geolocation(),
    writable: true
  })

  // Mock performance API
  if (!global.performance) {
    global.performance = {
      now: vi.fn(() => Date.now()),
      mark: vi.fn(),
      measure: vi.fn()
    }
  }
}

// Export everything
export default {
  TEST_CONFIG,
  TestUtils,
  CommonMocks,
  setupTestEnvironment
}