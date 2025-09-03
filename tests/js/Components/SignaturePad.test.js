import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import SignaturePad from '@/Components/SignaturePad.vue'

// Mock HTML5 Canvas
const mockCanvas = {
  getContext: vi.fn(() => ({
    clearRect: vi.fn(),
    beginPath: vi.fn(),
    moveTo: vi.fn(),
    lineTo: vi.fn(),
    stroke: vi.fn(),
    strokeStyle: '',
    lineWidth: 2,
    lineCap: 'round',
    lineJoin: 'round'
  })),
  toDataURL: vi.fn(() => 'data:image/png;base64,mock-signature-data'),
  width: 400,
  height: 200
}

// Mock canvas element
Object.defineProperty(HTMLCanvasElement.prototype, 'getContext', {
  value: mockCanvas.getContext
})

Object.defineProperty(HTMLCanvasElement.prototype, 'toDataURL', {
  value: mockCanvas.toDataURL
})

describe('SignaturePad', () => {
  let wrapper

  const defaultProps = {
    title: 'Test Signature',
    instructions: 'Please sign here',
    modelValue: null
  }

  beforeEach(() => {
    wrapper = mount(SignaturePad, {
      props: defaultProps
    })
  })

  afterEach(() => {
    wrapper.unmount()
  })

  it('renders signature pad correctly', () => {
    expect(wrapper.find('.signature-container').exists()).toBe(true)
    expect(wrapper.find('canvas').exists()).toBe(true)
    expect(wrapper.text()).toContain('Test Signature')
    expect(wrapper.text()).toContain('Please sign here')
  })

  it('displays action buttons', () => {
    expect(wrapper.find('[data-testid="clear-button"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="fullscreen-button"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="save-button"]').exists()).toBe(true)
  })

  it('clears signature when clear button is clicked', async () => {
    const clearButton = wrapper.find('[data-testid="clear-button"]')
    await clearButton.trigger('click')
    
    expect(mockCanvas.getContext().clearRect).toHaveBeenCalled()
  })

  it('toggles fullscreen mode', async () => {
    const fullscreenButton = wrapper.find('[data-testid="fullscreen-button"]')
    
    expect(wrapper.find('.signature-pad-wrapper').classes()).not.toContain('fullscreen')
    
    await fullscreenButton.trigger('click')
    expect(wrapper.find('.signature-pad-wrapper').classes()).toContain('fullscreen')
    
    await fullscreenButton.trigger('click')
    expect(wrapper.find('.signature-pad-wrapper').classes()).not.toContain('fullscreen')
  })

  it('handles touch events for drawing', async () => {
    const canvas = wrapper.find('canvas')
    const mockTouchEvent = {
      preventDefault: vi.fn(),
      touches: [{ clientX: 100, clientY: 50 }],
      target: { getBoundingClientRect: () => ({ left: 0, top: 0 }) }
    }

    await canvas.trigger('touchstart', mockTouchEvent)
    await canvas.trigger('touchmove', mockTouchEvent)
    await canvas.trigger('touchend', mockTouchEvent)

    expect(mockTouchEvent.preventDefault).toHaveBeenCalled()
  })

  it('handles mouse events for drawing', async () => {
    const canvas = wrapper.find('canvas')
    const mockMouseEvent = {
      preventDefault: vi.fn(),
      clientX: 100,
      clientY: 50,
      target: { getBoundingClientRect: () => ({ left: 0, top: 0 }) }
    }

    await canvas.trigger('mousedown', mockMouseEvent)
    await canvas.trigger('mousemove', mockMouseEvent)
    await canvas.trigger('mouseup', mockMouseEvent)

    expect(mockMouseEvent.preventDefault).toHaveBeenCalled()
  })

  it('saves signature and emits update event', async () => {
    // Simulate drawing by setting internal state
    wrapper.vm.isEmpty = false
    
    const saveButton = wrapper.find('[data-testid="save-button"]')
    await saveButton.trigger('click')
    
    expect(wrapper.emitted('update:modelValue')).toBeTruthy()
    expect(mockCanvas.toDataURL).toHaveBeenCalled()
  })

  it('disables save button when signature is empty', () => {
    const saveButton = wrapper.find('[data-testid="save-button"]')
    expect(saveButton.attributes('disabled')).toBeDefined()
  })

  it('enables save button when signature is not empty', async () => {
    wrapper.vm.isEmpty = false
    await wrapper.vm.$nextTick()
    
    const saveButton = wrapper.find('[data-testid="save-button"]')
    expect(saveButton.attributes('disabled')).toBeUndefined()
  })
})