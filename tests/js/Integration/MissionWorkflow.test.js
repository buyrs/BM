import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { nextTick } from 'vue'
import MissionCard from '@/Components/MissionCard.vue'
import ChecklistForm from '@/Components/ChecklistForm.vue'
import ContractSignatureFlow from '@/Components/ContractSignatureFlow.vue'
import axios from 'axios'

// Mock axios
vi.mock('axios')
const mockedAxios = vi.mocked(axios)

describe('Mission Workflow Integration', () => {
  let mockMission
  let mockChecklist
  let mockContract

  beforeEach(() => {
    mockMission = {
      id: 1,
      type: 'entry',
      status: 'assigned',
      scheduled_at: '2024-01-15',
      scheduled_time: '10:00',
      tenant_name: 'John Doe',
      address: '123 Main St',
      agent: { id: 1, name: 'Agent Smith' },
      bail_mobilite: {
        id: 1,
        start_date: '2024-01-15',
        end_date: '2024-02-15'
      },
      can_complete: true
    }

    mockChecklist = {
      id: 1,
      mission_id: 1,
      items: [
        { id: 1, name: 'Check keys', condition: null, photos: [], notes: '' },
        { id: 2, name: 'Inspect property', condition: null, photos: [], notes: '' }
      ]
    }

    mockContract = {
      id: 1,
      bail_mobilite_id: 1,
      template_id: 1,
      content: 'Contract content',
      admin_signature: 'admin-sig',
      variables: { tenant_name: 'John Doe' }
    }

    // Mock API responses
    mockedAxios.get.mockImplementation((url) => {
      if (url.includes('/checklist')) {
        return Promise.resolve({ data: mockChecklist })
      }
      if (url.includes('/contract')) {
        return Promise.resolve({ data: mockContract })
      }
      return Promise.resolve({ data: {} })
    })

    mockedAxios.post.mockResolvedValue({
      data: { success: true, id: 1 }
    })

    mockedAxios.patch.mockResolvedValue({
      data: { success: true }
    })
  })

  afterEach(() => {
    vi.clearAllMocks()
  })

  it('completes full entry mission workflow', async () => {
    // Step 1: Start with mission card
    const missionWrapper = mount(MissionCard, {
      props: { mission: mockMission, showActions: true }
    })

    // Step 2: Click complete button to start workflow
    const completeButton = missionWrapper.find('[data-testid="complete-button"]')
    await completeButton.trigger('click')

    expect(missionWrapper.emitted('complete')).toBeTruthy()

    // Step 3: Load checklist form
    const checklistWrapper = mount(ChecklistForm, {
      props: { 
        mission: mockMission,
        checklist: mockChecklist
      }
    })

    // Step 4: Fill out checklist items
    const firstItem = checklistWrapper.find('[data-testid="checklist-item-1"]')
    const conditionSelect = firstItem.find('[data-testid="condition-select"]')
    await conditionSelect.setValue('good')

    const notesInput = firstItem.find('[data-testid="notes-input"]')
    await notesInput.setValue('Keys in good condition')

    // Step 5: Add photos (simulate file upload)
    const photoUpload = firstItem.find('[data-testid="photo-upload"]')
    const mockFile = new File(['photo'], 'test.jpg', { type: 'image/jpeg' })
    
    Object.defineProperty(photoUpload.element, 'files', {
      value: [mockFile],
      writable: false
    })
    
    await photoUpload.trigger('change')

    // Step 6: Complete all checklist items
    const secondItem = checklistWrapper.find('[data-testid="checklist-item-2"]')
    const secondConditionSelect = secondItem.find('[data-testid="condition-select"]')
    await secondConditionSelect.setValue('fair')

    // Step 7: Submit checklist
    const submitButton = checklistWrapper.find('[data-testid="submit-checklist"]')
    await submitButton.trigger('click')

    expect(mockedAxios.post).toHaveBeenCalledWith('/api/checklists', expect.objectContaining({
      mission_id: 1,
      items: expect.arrayContaining([
        expect.objectContaining({
          id: 1,
          condition: 'good',
          notes: 'Keys in good condition'
        })
      ])
    }))

    // Step 8: Load contract signature flow
    const signatureWrapper = mount(ContractSignatureFlow, {
      props: { contract: mockContract }
    })

    // Step 9: Complete signature
    const signaturePad = signatureWrapper.find('[data-testid="signature-pad"]')
    await signaturePad.vm.$emit('update:modelValue', 'tenant-signature')
    await nextTick()

    const confirmButton = signatureWrapper.find('[data-testid="confirm-signature"]')
    await confirmButton.trigger('click')

    // Step 10: Verify mission completion
    expect(mockedAxios.post).toHaveBeenCalledWith('/api/contracts/generate-signed', expect.any(Object))
    expect(signatureWrapper.emitted('contract-generated')).toBeTruthy()

    // Cleanup
    missionWrapper.unmount()
    checklistWrapper.unmount()
    signatureWrapper.unmount()
  })

  it('handles exit mission workflow without signature', async () => {
    const exitMission = { ...mockMission, type: 'exit' }
    
    // Step 1: Start exit mission
    const missionWrapper = mount(MissionCard, {
      props: { mission: exitMission, showActions: true }
    })

    const completeButton = missionWrapper.find('[data-testid="complete-button"]')
    await completeButton.trigger('click')

    // Step 2: Complete checklist only (no signature for exit)
    const checklistWrapper = mount(ChecklistForm, {
      props: { 
        mission: exitMission,
        checklist: mockChecklist,
        requireSignature: false
      }
    })

    // Fill out checklist
    const firstItem = checklistWrapper.find('[data-testid="checklist-item-1"]')
    const conditionSelect = firstItem.find('[data-testid="condition-select"]')
    await conditionSelect.setValue('good')

    // Submit checklist
    const submitButton = checklistWrapper.find('[data-testid="submit-checklist"]')
    await submitButton.trigger('click')

    expect(mockedAxios.post).toHaveBeenCalledWith('/api/checklists', expect.any(Object))
    expect(checklistWrapper.emitted('checklist-submitted')).toBeTruthy()

    // No signature flow for exit missions
    expect(checklistWrapper.find('[data-testid="signature-section"]').exists()).toBe(false)

    missionWrapper.unmount()
    checklistWrapper.unmount()
  })

  it('handles workflow errors gracefully', async () => {
    // Mock API error
    mockedAxios.post.mockRejectedValue({
      response: { status: 500, data: { message: 'Server error' } }
    })

    const checklistWrapper = mount(ChecklistForm, {
      props: { 
        mission: mockMission,
        checklist: mockChecklist
      }
    })

    // Fill out checklist
    const firstItem = checklistWrapper.find('[data-testid="checklist-item-1"]')
    const conditionSelect = firstItem.find('[data-testid="condition-select"]')
    await conditionSelect.setValue('good')

    // Try to submit
    const submitButton = checklistWrapper.find('[data-testid="submit-checklist"]')
    await submitButton.trigger('click')

    // Wait for error handling
    await nextTick()

    // Verify error is displayed
    expect(checklistWrapper.find('[data-testid="error-message"]').exists()).toBe(true)
    expect(checklistWrapper.emitted('error')).toBeTruthy()

    checklistWrapper.unmount()
  })

  it('validates required checklist items', async () => {
    const checklistWrapper = mount(ChecklistForm, {
      props: { 
        mission: mockMission,
        checklist: mockChecklist
      }
    })

    // Try to submit without filling required items
    const submitButton = checklistWrapper.find('[data-testid="submit-checklist"]')
    expect(submitButton.attributes('disabled')).toBeDefined()

    // Fill first item
    const firstItem = checklistWrapper.find('[data-testid="checklist-item-1"]')
    const conditionSelect = firstItem.find('[data-testid="condition-select"]')
    await conditionSelect.setValue('good')

    // Still disabled (second item not filled)
    expect(submitButton.attributes('disabled')).toBeDefined()

    // Fill second item
    const secondItem = checklistWrapper.find('[data-testid="checklist-item-2"]')
    const secondConditionSelect = secondItem.find('[data-testid="condition-select"]')
    await secondConditionSelect.setValue('fair')

    // Now enabled
    expect(submitButton.attributes('disabled')).toBeUndefined()

    checklistWrapper.unmount()
  })

  it('handles photo upload workflow', async () => {
    const checklistWrapper = mount(ChecklistForm, {
      props: { 
        mission: mockMission,
        checklist: mockChecklist
      }
    })

    const firstItem = checklistWrapper.find('[data-testid="checklist-item-1"]')
    const photoUpload = firstItem.find('[data-testid="photo-upload"]')

    // Mock file upload
    const mockFile = new File(['photo'], 'test.jpg', { type: 'image/jpeg' })
    Object.defineProperty(photoUpload.element, 'files', {
      value: [mockFile],
      writable: false
    })

    // Mock successful upload response
    mockedAxios.post.mockResolvedValue({
      data: { 
        success: true, 
        photo_url: '/storage/photos/test.jpg',
        photo_id: 123
      }
    })

    await photoUpload.trigger('change')

    // Verify upload API call
    expect(mockedAxios.post).toHaveBeenCalledWith('/api/photos/upload', expect.any(FormData))

    // Verify photo is added to item
    await nextTick()
    const photoPreview = firstItem.find('[data-testid="photo-preview"]')
    expect(photoPreview.exists()).toBe(true)

    checklistWrapper.unmount()
  })

  it('supports workflow state persistence', async () => {
    const checklistWrapper = mount(ChecklistForm, {
      props: { 
        mission: mockMission,
        checklist: mockChecklist,
        autosave: true
      }
    })

    // Fill out first item
    const firstItem = checklistWrapper.find('[data-testid="checklist-item-1"]')
    const conditionSelect = firstItem.find('[data-testid="condition-select"]')
    await conditionSelect.setValue('good')

    const notesInput = firstItem.find('[data-testid="notes-input"]')
    await notesInput.setValue('Test notes')

    // Wait for autosave
    await new Promise(resolve => setTimeout(resolve, 1000))

    // Verify autosave API call
    expect(mockedAxios.patch).toHaveBeenCalledWith('/api/checklists/1/autosave', expect.any(Object))

    checklistWrapper.unmount()
  })
})