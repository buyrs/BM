import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import ContractSignatureFlow from '@/Components/ContractSignatureFlow.vue'

// Mock child components
vi.mock('@/Components/ContractPreview.vue', () => ({
  default: {
    name: 'ContractPreview',
    template: '<div data-testid="contract-preview"><slot /></div>',
    props: ['contract']
  }
}))

vi.mock('@/Components/SignaturePad.vue', () => ({
  default: {
    name: 'SignaturePad',
    template: '<div data-testid="signature-pad" @click="$emit(\'update:modelValue\', \'mock-signature\')"><slot /></div>',
    props: ['modelValue', 'title', 'instructions'],
    emits: ['update:modelValue']
  }
}))

describe('ContractSignatureFlow', () => {
  let wrapper

  const mockContract = {
    id: 1,
    template_id: 1,
    bail_mobilite_id: 1,
    content: 'Contract content with {{tenant_name}} and {{address}}',
    admin_signature: 'admin-signature-data',
    admin_signed_at: '2024-01-15T10:00:00Z',
    variables: {
      tenant_name: 'John Doe',
      address: '123 Main St',
      start_date: '2024-01-15',
      end_date: '2024-02-15'
    }
  }

  const defaultProps = {
    contract: mockContract,
    loading: false
  }

  beforeEach(() => {
    wrapper = mount(ContractSignatureFlow, {
      props: defaultProps
    })
  })

  it('renders contract signature flow correctly', () => {
    expect(wrapper.find('.contract-flow').exists()).toBe(true)
    expect(wrapper.find('[data-testid="contract-preview"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="signature-pad"]').exists()).toBe(true)
  })

  it('displays contract preview with merged content', () => {
    const preview = wrapper.find('[data-testid="contract-preview"]')
    expect(preview.exists()).toBe(true)
  })

  it('shows admin signature section', () => {
    const adminSignature = wrapper.find('[data-testid="admin-signature"]')
    expect(adminSignature.exists()).toBe(true)
    expect(wrapper.text()).toContain('Administrator Signature')
  })

  it('enables tenant signature pad', () => {
    const signaturePad = wrapper.find('[data-testid="signature-pad"]')
    expect(signaturePad.exists()).toBe(true)
  })

  it('captures tenant signature', async () => {
    const signaturePad = wrapper.find('[data-testid="signature-pad"]')
    await signaturePad.trigger('click')

    expect(wrapper.vm.tenantSignature).toBe('mock-signature')
  })

  it('shows signature confirmation when both signatures present', async () => {
    // Set tenant signature
    wrapper.vm.tenantSignature = 'tenant-signature-data'
    await wrapper.vm.$nextTick()

    const confirmationSection = wrapper.find('[data-testid="signature-confirmation"]')
    expect(confirmationSection.exists()).toBe(true)
  })

  it('generates signed contract when confirmed', async () => {
    wrapper.vm.tenantSignature = 'tenant-signature-data'
    await wrapper.vm.$nextTick()

    const confirmButton = wrapper.find('[data-testid="confirm-signature"]')
    await confirmButton.trigger('click')

    expect(wrapper.emitted('generate-contract')).toBeTruthy()
    expect(wrapper.emitted('generate-contract')[0][0]).toEqual({
      contract: mockContract,
      tenantSignature: 'tenant-signature-data',
      timestamp: expect.any(String)
    })
  })

  it('validates required signatures before confirmation', () => {
    const confirmButton = wrapper.find('[data-testid="confirm-signature"]')
    expect(confirmButton.attributes('disabled')).toBeDefined()
  })

  it('enables confirmation when both signatures present', async () => {
    wrapper.vm.tenantSignature = 'tenant-signature-data'
    await wrapper.vm.$nextTick()

    const confirmButton = wrapper.find('[data-testid="confirm-signature"]')
    expect(confirmButton.attributes('disabled')).toBeUndefined()
  })

  it('shows loading state during contract generation', async () => {
    await wrapper.setProps({ loading: true })
    expect(wrapper.find('[data-testid="loading-spinner"]').exists()).toBe(true)
  })

  it('handles signature clearing', async () => {
    wrapper.vm.tenantSignature = 'tenant-signature-data'
    await wrapper.vm.$nextTick()

    const clearButton = wrapper.find('[data-testid="clear-signature"]')
    await clearButton.trigger('click')

    expect(wrapper.vm.tenantSignature).toBe(null)
  })

  it('displays signature timestamps', async () => {
    wrapper.vm.tenantSignature = 'tenant-signature-data'
    await wrapper.vm.$nextTick()

    const adminTimestamp = wrapper.find('[data-testid="admin-timestamp"]')
    const tenantTimestamp = wrapper.find('[data-testid="tenant-timestamp"]')

    expect(adminTimestamp.exists()).toBe(true)
    expect(tenantTimestamp.exists()).toBe(true)
  })

  it('validates contract data before signing', () => {
    expect(wrapper.vm.isContractValid).toBe(true)
    
    // Test with invalid contract
    wrapper.setProps({ 
      contract: { ...mockContract, admin_signature: null } 
    })
    
    expect(wrapper.vm.isContractValid).toBe(false)
  })

  it('handles signature pad errors', async () => {
    const signaturePad = wrapper.find('[data-testid="signature-pad"]')
    
    // Simulate signature pad error
    signaturePad.vm.$emit('error', 'Signature capture failed')
    
    expect(wrapper.find('[data-testid="error-message"]').exists()).toBe(true)
  })

  it('provides signature instructions', () => {
    const instructions = wrapper.find('[data-testid="signature-instructions"]')
    expect(instructions.exists()).toBe(true)
    expect(instructions.text()).toContain('Please sign')
  })
})