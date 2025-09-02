import { vi } from 'vitest'
import { config } from '@vue/test-utils'

// Mock Inertia
vi.mock('@inertiajs/vue3', () => ({
  router: {
    get: vi.fn(),
    post: vi.fn(),
    patch: vi.fn(),
    delete: vi.fn(),
    visit: vi.fn(),
  },
  usePage: () => ({
    props: {
      auth: {
        user: {
          id: 1,
          name: 'Test User',
          email: 'test@example.com',
          roles: ['ops']
        }
      }
    }
  }),
  Head: {
    template: '<head><slot /></head>'
  },
  Link: {
    template: '<a><slot /></a>'
  }
}))

// Mock Ziggy
vi.mock('ziggy-js', () => ({
  route: vi.fn((name, params) => `/mock-route/${name}${params ? `/${params}` : ''}`)
}))

// Global test configuration
config.global.mocks = {
  route: vi.fn((name, params) => `/mock-route/${name}${params ? `/${params}` : ''}`),
}

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