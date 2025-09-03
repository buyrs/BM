import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { nextTick } from 'vue'
import ContractSignatureFlow from '@/Components/ContractSignatureFlow.vue'
import SignaturePad from '@/Components/SignaturePad.vue'
import axios from 'axios'

// Mock axios
vi.mock('axios')
const mockedAxios = vi.mocked(axios)

// Mock PDF generation
vi.mock('jspdf', () => ({
  default: vi.fn().mockImplementation(() => ({
    addImage: vi.fn(),
    text: vi.fn(),
    save: vi.fn(),
    output: vi.fn(() => 'mock-pdf-data')
  }))
}))

describe('Signature Workflow Integration', () => {
  let wrapper
  let mockContract

  beforeEach(() => {
    mockContract = {
      id: 1,
      template_id: 1,
      bail_mobilite_id: 1,
      content: 'Contract for {{tenant_name}} at {{address}}',
      admin_signature: 'admin-signature-data',
      admin_signed_at: '2024-01-15T10:00:00Z',
      variables: {
        tenant_name: 'John Doe',
        address: '123 Main St',
        start_date: '2024-01-15',
        end_date: '2024-02-15'
      }
    }

    // Mock successful API responses
    mockedAxios.post.mockResolvedValue({
      data: {
        success: true,
        pdf_url: '/storage/contracts/signed-contract-1.pdf',
        signature_id: 123
      }
    })

    wrapper = mount(ContractSignatureFlow, {
      props: {
        contract: mockContract,
        loading: false
      }
    })
  })

  afterEach(() => {
    vi.clearAllMocks()
    wrapper.unmount()
  })

  it('completes full signature workflow', async () => {
    // Step 1: Verify contract preview is displayed
    expect(wrapper.find('[data-testid="contract-preview"]').exists()).toBe(true)
    
    // Step 2: Verify admin signature is shown
    expect(wrapper.find('[data-testid="admin-signature"]').exists()).toBe(true)
    
    // Step 3: Capture tenant signature
    const signaturePad = wrapper.findComponent(SignaturePad)
    expect(signaturePad.exists()).toBe(true)
    
    // Simulate signature capture
    await signaturePad.vm.$emit('update:modelValue', 'tenant-signature-data')
    await nextTick()
    
    expect(wrapper.vm.tenantSignature).toBe('tenant-signature-data')
    
    // Step 4: Verify confirmation section appears
    expect(wrapper.find('[data-testid="signature-confirmation"]').exists()).toBe(true)
    
    // Step 5: Confirm and generate contract
    const confirmButton = wrapper.find('[data-testid="confirm-signature"]')
    expect(confirmButton.attributes('disabled')).toBeUndefined()
    
    await confirmButton.trigger('click')
    
    // Step 6: Verify API call is made
    expect(mockedAxios.post).toHaveBeenCalledWith('/api/contracts/generate-signed', {
      contract_id: 1,
      tenant_signature: 'tenant-signature-data',
      timestamp: expect.any(String)
    })
    
    // Step 7: Verify success event is emitted
    expect(wrapper.emitted('contract-generated')).toBeTruthy()
  })

  it('handles signature validation errors', async () => {
    // Try to confirm without tenant signature
    const confirmButton = wrapper.find('[data-testid="confirm-signature"]')
    expect(confirmButton.attributes('disabled')).toBeDefined()
    
    // Add invalid signature (empty)
    const signaturePad = wrapper.findComponent(SignaturePad)
    await signaturePad.vm.$emit('update:modelValue', '')
    await nextTick()
    
    expect(confirmButton.attributes('disabled')).toBeDefined()
    
    // Add valid signature
    await signaturePad.vm.$emit('update:modelValue', 'valid-signature-data')
    await nextTick()
    
    expect(confirmButton.attributes('disabled')).toBeUndefined()
  })

  it('handles API errors during contract generation', async () => {
    // Mock API error
    mockedAxios.post.mockRejectedValue({
      response: {
        status: 500,
        data: { message: 'Contract generation failed' }
      }
    })
    
    // Complete signature
    const signaturePad = wrapper.findComponent(SignaturePad)
    await signaturePad.vm.$emit('update:modelValue', 'tenant-signature-data')
    await nextTick()
    
    // Try to confirm
    const confirmButton = wrapper.find('[data-testid="confirm-signature"]')
    await confirmButton.trigger('click')
    
    // Wait for error handling
    await nextTick()
    
    // Verify error is displayed
    expect(wrapper.find('[data-testid="error-message"]').exists()).toBe(true)
    expect(wrapper.emitted('error')).toBeTruthy()
  })

  it('supports signature retry after error', async () => {
    // Mock initial error then success
    mockedAxios.post
      .mockRejectedValueOnce({
        response: { status: 500, data: { message: 'Network error' } }
      })
      .mockResolvedValueOnce({
        data: { success: true, pdf_url: '/storage/contracts/signed-contract-1.pdf' }
      })
    
    // Complete signature
    const signaturePad = wrapper.findComponent(SignaturePad)
    await signaturePad.vm.$emit('update:modelValue', 'tenant-signature-data')
    await nextTick()
    
    // First attempt fails
    const confirmButton = wrapper.find('[data-testid="confirm-signature"]')
    await confirmButton.trigger('click')
    await nextTick()
    
    expect(wrapper.find('[data-testid="error-message"]').exists()).toBe(true)
    
    // Retry
    const retryButton = wrapper.find('[data-testid="retry-button"]')
    await retryButton.trigger('click')
    
    // Second attempt succeeds
    expect(wrapper.emitted('contract-generated')).toBeTruthy()
  })

  it('preserves signature data during workflow', async () => {
    const signatureData = 'persistent-signature-data'
    
    // Capture signature
    const signaturePad = wrapper.findComponent(SignaturePad)
    await signaturePad.vm.$emit('update:modelValue', signatureData)
    await nextTick()
    
    // Verify signature is preserved
    expect(wrapper.vm.tenantSignature).toBe(signatureData)
    
    // Navigate away and back (simulate)
    await wrapper.setProps({ contract: null })
    await wrapper.setProps({ contract: mockContract })
    
    // Signature should be cleared on contract change
    expect(wrapper.vm.tenantSignature).toBe(null)
  })

  it('handles multiple signature attempts', async () => {
    const signaturePad = wrapper.findComponent(SignaturePad)
    
    // First signature attempt
    await signaturePad.vm.$emit('update:modelValue', 'first-signature')
    await nextTick()
    expect(wrapper.vm.tenantSignature).toBe('first-signature')
    
    // Clear signature
    const clearButton = wrapper.find('[data-testid="clear-signature"]')
    await clearButton.trigger('click')
    expect(wrapper.vm.tenantSignature).toBe(null)
    
    // Second signature attempt
    await signaturePad.vm.$emit('update:modelValue', 'second-signature')
    await nextTick()
    expect(wrapper.vm.tenantSignature).toBe('second-signature')
  })

  it('validates contract data before allowing signature', async () => {
    // Test with invalid contract (missing admin signature)
    const invalidContract = {
      ...mockContract,
      admin_signature: null
    }
    
    await wrapper.setProps({ contract: invalidContract })
    
    // Signature pad should be disabled
    const signaturePad = wrapper.findComponent(SignaturePad)
    expect(signaturePad.props('disabled')).toBe(true)
    
    // Error message should be shown
    expect(wrapper.find('[data-testid="contract-error"]').exists()).toBe(true)
  })

  it('generates PDF with both signatures', async () => {
    // Complete signature workflow
    const signaturePad = wrapper.findComponent(SignaturePad)
    await signaturePad.vm.$emit('update:modelValue', 'tenant-signature-data')
    await nextTick()
    
    const confirmButton = wrapper.find('[data-testid="confirm-signature"]')
    await confirmButton.trigger('click')
    
    // Verify API call includes both signatures
    expect(mockedAxios.post).toHaveBeenCalledWith('/api/contracts/generate-signed', {
      contract_id: 1,
      tenant_signature: 'tenant-signature-data',
      timestamp: expect.any(String)
    })
  })

  it('handles loading states during workflow', async () => {
    // Set loading state
    await wrapper.setProps({ loading: true })
    
    // Verify loading spinner is shown
    expect(wrapper.find('[data-testid="loading-spinner"]').exists()).toBe(true)
    
    // Verify signature pad is disabled during loading
    const signaturePad = wrapper.findComponent(SignaturePad)
    expect(signaturePad.props('disabled')).toBe(true)
    
    // Clear loading state
    await wrapper.setProps({ loading: false })
    
    // Verify components are re-enabled
    expect(signaturePad.props('disabled')).toBe(false)
  })

  it('tracks signature timestamps', async () => {
    const signaturePad = wrapper.findComponent(SignaturePad)
    
    // Capture signature
    const beforeSignature = Date.now()
    await signaturePad.vm.$emit('update:modelValue', 'tenant-signature-data')
    await nextTick()
    const afterSignature = Date.now()
    
    // Verify timestamp is within expected range
    const timestamp = new Date(wrapper.vm.tenantSignedAt).getTime()
    expect(timestamp).toBeGreaterThanOrEqual(beforeSignature)
    expect(timestamp).toBeLessThanOrEqual(afterSignature)
  })
})