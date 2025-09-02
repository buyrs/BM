import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import CreateMissionModal from '@/Components/Calendar/CreateMissionModal.vue'

describe('CreateMissionModal', () => {
  let wrapper
  const mockCheckers = [
    { id: 1, name: 'Agent Smith', email: 'smith@example.com' },
    { id: 2, name: 'Agent Jones', email: 'jones@example.com' }
  ]

  const defaultProps = {
    show: true,
    selectedDate: new Date('2024-01-15'),
    checkers: mockCheckers
  }

  beforeEach(() => {
    wrapper = mount(CreateMissionModal, {
      props: defaultProps
    })
  })

  it('renders modal when show is true', () => {
    expect(wrapper.find('[data-testid="create-mission-modal"]').exists()).toBe(true)
  })

  it('does not render modal when show is false', async () => {
    await wrapper.setProps({ show: false })
    expect(wrapper.find('[data-testid="create-mission-modal"]').exists()).toBe(false)
  })

  it('pre-populates start date with selected date', () => {
    const startDateInput = wrapper.find('[data-testid="start-date-input"]')
    expect(startDateInput.element.value).toBe('2024-01-15')
  })

  it('validates required fields', async () => {
    const submitButton = wrapper.find('[data-testid="submit-button"]')
    await submitButton.trigger('click')
    
    expect(wrapper.find('.error-message').exists()).toBe(true)
  })

  it('populates checker options', () => {
    const entryCheckerSelect = wrapper.find('[data-testid="entry-checker-select"]')
    const options = entryCheckerSelect.findAll('option')
    
    expect(options.length).toBeGreaterThan(mockCheckers.length) // Including empty option
    expect(options[1].text()).toContain('Agent Smith')
    expect(options[2].text()).toContain('Agent Jones')
  })

  it('emits create event with form data when submitted', async () => {
    // Fill out the form
    await wrapper.find('[data-testid="address-input"]').setValue('123 Test St')
    await wrapper.find('[data-testid="tenant-name-input"]').setValue('John Doe')
    await wrapper.find('[data-testid="tenant-phone-input"]').setValue('555-1234')
    await wrapper.find('[data-testid="tenant-email-input"]').setValue('john@example.com')
    await wrapper.find('[data-testid="end-date-input"]').setValue('2024-01-20')
    await wrapper.find('[data-testid="entry-time-input"]').setValue('10:00')
    await wrapper.find('[data-testid="exit-time-input"]').setValue('14:00')
    await wrapper.find('[data-testid="entry-checker-select"]').setValue('1')
    await wrapper.find('[data-testid="exit-checker-select"]').setValue('2')
    await wrapper.find('[data-testid="notes-input"]').setValue('Test notes')

    await wrapper.find('[data-testid="submit-button"]').trigger('click')

    expect(wrapper.emitted('create')).toBeTruthy()
    const emittedData = wrapper.emitted('create')[0][0]
    expect(emittedData.address).toBe('123 Test St')
    expect(emittedData.tenant_name).toBe('John Doe')
    expect(emittedData.entry_checker_id).toBe('1')
  })

  it('emits close event when cancel button is clicked', async () => {
    await wrapper.find('[data-testid="cancel-button"]').trigger('click')
    expect(wrapper.emitted('close')).toBeTruthy()
  })

  it('emits close event when modal overlay is clicked', async () => {
    await wrapper.find('[data-testid="modal-overlay"]').trigger('click')
    expect(wrapper.emitted('close')).toBeTruthy()
  })

  it('validates email format', async () => {
    await wrapper.find('[data-testid="tenant-email-input"]').setValue('invalid-email')
    await wrapper.find('[data-testid="submit-button"]').trigger('click')
    
    expect(wrapper.find('.email-error').exists()).toBe(true)
  })

  it('validates date logic (end date after start date)', async () => {
    await wrapper.find('[data-testid="end-date-input"]').setValue('2024-01-10') // Before start date
    await wrapper.find('[data-testid="submit-button"]').trigger('click')
    
    expect(wrapper.find('.date-error').exists()).toBe(true)
  })

  it('shows loading state during submission', async () => {
    await wrapper.setProps({ loading: true })
    expect(wrapper.find('[data-testid="submit-button"]').attributes('disabled')).toBeDefined()
    expect(wrapper.find('.loading-spinner').exists()).toBe(true)
  })

  it('resets form when modal is closed and reopened', async () => {
    // Fill out form
    await wrapper.find('[data-testid="address-input"]').setValue('123 Test St')
    
    // Close modal
    await wrapper.setProps({ show: false })
    
    // Reopen modal
    await wrapper.setProps({ show: true })
    
    // Form should be reset
    expect(wrapper.find('[data-testid="address-input"]').element.value).toBe('')
  })
})