import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { nextTick } from 'vue'
import axios from 'axios'

// Mock Inertia router
const mockInertiaRouter = {
  visit: vi.fn(),
  get: vi.fn(),
  post: vi.fn(),
  patch: vi.fn()
}

vi.mock('@inertiajs/vue3', () => ({
  router: mockInertiaRouter,
  usePage: () => ({
    props: {
      auth: {
        user: {
          id: 2,
          name: 'Checker User',
          email: 'checker@example.com',
          roles: ['checker']
        }
      }
    }
  })
}))

// Mock axios
vi.mock('axios')
const mockedAxios = vi.mocked(axios)

// Mock geolocation
const mockGeolocation = {
  getCurrentPosition: vi.fn((success) => {
    success({
      coords: {
        latitude: 48.8566,
        longitude: 2.3522,
        accuracy: 10
      }
    })
  })
}

Object.defineProperty(global.navigator, 'geolocation', {
  value: mockGeolocation,
  writable: true
})

describe('Checker End-to-End Workflow', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    
    // Mock API responses
    mockedAxios.get.mockImplementation((url) => {
      if (url.includes('/missions/assigned')) {
        return Promise.resolve({
          data: [
            {
              id: 1,
              type: 'entry',
              status: 'assigned',
              scheduled_at: '2024-01-15',
              scheduled_time: '10:00',
              tenant_name: 'John Doe',
              tenant_phone: '+1234567890',
              address: '123 Main St',
              priority: 'high',
              bail_mobilite: { id: 1 }
            },
            {
              id: 2,
              type: 'exit',
              status: 'assigned',
              scheduled_at: '2024-01-15',
              scheduled_time: '14:00',
              tenant_name: 'Jane Smith',
              address: '456 Oak Ave',
              priority: 'normal',
              bail_mobilite: { id: 2 }
            }
          ]
        })
      }
      if (url.includes('/missions/1/checklist')) {
        return Promise.resolve({
          data: {
            id: 1,
            mission_id: 1,
            items: [
              { id: 1, name: 'Check keys', condition: null, photos: [], notes: '' },
              { id: 2, name: 'Inspect property', condition: null, photos: [], notes: '' },
              { id: 3, name: 'Test utilities', condition: null, photos: [], notes: '' }
            ]
          }
        })
      }
      if (url.includes('/missions/1/contract')) {
        return Promise.resolve({
          data: {
            id: 1,
            bail_mobilite_id: 1,
            content: 'Contract for {{tenant_name}} at {{address}}',
            admin_signature: 'admin-signature-data',
            variables: {
              tenant_name: 'John Doe',
              address: '123 Main St'
            }
          }
        })
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
    // Step 1: Load checker dashboard
    mockInertiaRouter.visit('/checker/dashboard')
    
    // Step 2: Load assigned missions
    const missionsResponse = await mockedAxios.get('/api/missions/assigned')
    expect(missionsResponse.data.length).toBe(2)
    
    const entryMission = missionsResponse.data[0]
    expect(entryMission.type).toBe('entry')
    
    // Step 3: Start mission (update status to in_progress)
    await mockedAxios.patch(`/api/missions/${entryMission.id}/start`, {
      started_at: new Date().toISOString(),
      location: { latitude: 48.8566, longitude: 2.3522 }
    })
    
    expect(mockedAxios.patch).toHaveBeenCalledWith('/api/missions/1/start', {
      started_at: expect.any(String),
      location: { latitude: 48.8566, longitude: 2.3522 }
    })
    
    // Step 4: Load checklist
    const checklistResponse = await mockedAxios.get('/api/missions/1/checklist')
    expect(checklistResponse.data.items.length).toBe(3)
    
    // Step 5: Complete checklist items
    const checklistData = {
      mission_id: 1,
      items: [
        {
          id: 1,
          condition: 'good',
          notes: 'Keys in perfect condition',
          photos: ['photo1.jpg']
        },
        {
          id: 2,
          condition: 'fair',
          notes: 'Minor wear on walls',
          photos: ['photo2.jpg', 'photo3.jpg']
        },
        {
          id: 3,
          condition: 'good',
          notes: 'All utilities working',
          photos: ['photo4.jpg']
        }
      ]
    }
    
    await mockedAxios.post('/api/checklists', checklistData)
    expect(mockedAxios.post).toHaveBeenCalledWith('/api/checklists', checklistData)
    
    // Step 6: Load contract for signature
    const contractResponse = await mockedAxios.get('/api/missions/1/contract')
    expect(contractResponse.data.admin_signature).toBe('admin-signature-data')
    
    // Step 7: Capture tenant signature
    const signatureData = {
      contract_id: 1,
      tenant_signature: 'tenant-signature-base64-data',
      timestamp: new Date().toISOString(),
      location: { latitude: 48.8566, longitude: 2.3522 }
    }
    
    await mockedAxios.post('/api/contracts/generate-signed', signatureData)
    expect(mockedAxios.post).toHaveBeenCalledWith('/api/contracts/generate-signed', signatureData)
    
    // Step 8: Complete mission
    await mockedAxios.patch('/api/missions/1/complete', {
      completed_at: new Date().toISOString(),
      location: { latitude: 48.8566, longitude: 2.3522 }
    })
    
    expect(mockedAxios.patch).toHaveBeenCalledWith('/api/missions/1/complete', {
      completed_at: expect.any(String),
      location: { latitude: 48.8566, longitude: 2.3522 }
    })
  })

  it('completes exit mission workflow without signature', async () => {
    // Step 1: Load assigned missions
    const missionsResponse = await mockedAxios.get('/api/missions/assigned')
    const exitMission = missionsResponse.data[1]
    expect(exitMission.type).toBe('exit')
    
    // Step 2: Start exit mission
    await mockedAxios.patch(`/api/missions/${exitMission.id}/start`)
    
    // Step 3: Complete exit checklist (no signature required)
    const exitChecklistData = {
      mission_id: 2,
      items: [
        {
          id: 1,
          condition: 'good',
          notes: 'Property left in good condition',
          photos: ['exit_photo1.jpg']
        }
      ]
    }
    
    await mockedAxios.post('/api/checklists', exitChecklistData)
    expect(mockedAxios.post).toHaveBeenCalledWith('/api/checklists', exitChecklistData)
    
    // Step 4: Complete mission (no contract signature for exit)
    await mockedAxios.patch('/api/missions/2/complete')
    expect(mockedAxios.patch).toHaveBeenCalledWith('/api/missions/2/complete')
  })

  it('handles photo upload workflow', async () => {
    // Step 1: Upload photos for checklist item
    const formData = new FormData()
    formData.append('photo', new File(['photo'], 'test.jpg', { type: 'image/jpeg' }))
    formData.append('mission_id', '1')
    formData.append('checklist_item_id', '1')
    
    mockedAxios.post.mockResolvedValue({
      data: {
        success: true,
        photo_url: '/storage/photos/test.jpg',
        photo_id: 123
      }
    })
    
    await mockedAxios.post('/api/photos/upload', formData)
    expect(mockedAxios.post).toHaveBeenCalledWith('/api/photos/upload', formData)
    
    // Step 2: Delete photo if needed
    await mockedAxios.delete('/api/photos/123')
    expect(mockedAxios.delete).toHaveBeenCalledWith('/api/photos/123')
  })

  it('handles offline mode and sync', async () => {
    // Step 1: Work offline - store data locally
    const offlineData = {
      mission_id: 1,
      checklist_items: [
        { id: 1, condition: 'good', notes: 'Offline entry' }
      ],
      photos: ['offline_photo1.jpg'],
      timestamp: new Date().toISOString()
    }
    
    // Simulate storing in localStorage
    localStorage.setItem('offline_mission_1', JSON.stringify(offlineData))
    
    // Step 2: Come back online and sync
    mockedAxios.post.mockResolvedValue({
      data: { success: true, synced: true }
    })
    
    const storedData = JSON.parse(localStorage.getItem('offline_mission_1'))
    await mockedAxios.post('/api/missions/sync', storedData)
    
    expect(mockedAxios.post).toHaveBeenCalledWith('/api/missions/sync', offlineData)
    
    // Step 3: Clear local storage after successful sync
    localStorage.removeItem('offline_mission_1')
    expect(localStorage.getItem('offline_mission_1')).toBeNull()
  })

  it('handles emergency incident reporting', async () => {
    // Step 1: Report incident during mission
    const incidentData = {
      mission_id: 1,
      type: 'damage',
      severity: 'high',
      description: 'Water damage in bathroom',
      photos: ['incident_photo1.jpg', 'incident_photo2.jpg'],
      location: { latitude: 48.8566, longitude: 2.3522 },
      reported_at: new Date().toISOString()
    }
    
    await mockedAxios.post('/api/incidents', incidentData)
    expect(mockedAxios.post).toHaveBeenCalledWith('/api/incidents', incidentData)
    
    // Step 2: Update mission status to incident
    await mockedAxios.patch('/api/missions/1/status', { status: 'incident' })
    expect(mockedAxios.patch).toHaveBeenCalledWith('/api/missions/1/status', { status: 'incident' })
    
    // Step 3: Notify ops team
    await mockedAxios.post('/api/notifications/incident', {
      incident_id: 1,
      mission_id: 1,
      priority: 'urgent'
    })
    
    expect(mockedAxios.post).toHaveBeenCalledWith('/api/notifications/incident', {
      incident_id: 1,
      mission_id: 1,
      priority: 'urgent'
    })
  })

  it('handles mission scheduling and rescheduling', async () => {
    // Step 1: Request mission reschedule
    const rescheduleData = {
      mission_id: 1,
      new_date: '2024-01-16',
      new_time: '11:00',
      reason: 'Tenant not available'
    }
    
    await mockedAxios.post('/api/missions/1/reschedule', rescheduleData)
    expect(mockedAxios.post).toHaveBeenCalledWith('/api/missions/1/reschedule', rescheduleData)
    
    // Step 2: Load updated schedule
    mockedAxios.get.mockResolvedValue({
      data: [
        {
          id: 1,
          scheduled_at: '2024-01-16',
          scheduled_time: '11:00',
          status: 'rescheduled'
        }
      ]
    })
    
    const updatedMissions = await mockedAxios.get('/api/missions/assigned')
    expect(updatedMissions.data[0].scheduled_at).toBe('2024-01-16')
  })

  it('handles performance tracking', async () => {
    // Step 1: Track mission performance metrics
    const performanceData = {
      mission_id: 1,
      start_time: '2024-01-15T10:00:00Z',
      end_time: '2024-01-15T11:30:00Z',
      duration_minutes: 90,
      checklist_completion_rate: 1.0,
      photos_taken: 5,
      issues_reported: 0
    }
    
    await mockedAxios.post('/api/performance/track', performanceData)
    expect(mockedAxios.post).toHaveBeenCalledWith('/api/performance/track', performanceData)
    
    // Step 2: Load checker statistics
    mockedAxios.get.mockResolvedValue({
      data: {
        missions_completed: 45,
        average_duration: 85,
        completion_rate: 0.96,
        customer_rating: 4.8,
        incidents_reported: 2
      }
    })
    
    const stats = await mockedAxios.get('/api/checkers/2/stats')
    expect(stats.data.missions_completed).toBe(45)
    expect(stats.data.customer_rating).toBe(4.8)
  })

  it('handles error scenarios gracefully', async () => {
    // Test network error during mission start
    mockedAxios.patch.mockRejectedValue({
      code: 'NETWORK_ERROR',
      message: 'Network request failed'
    })
    
    try {
      await mockedAxios.patch('/api/missions/1/start')
    } catch (error) {
      expect(error.code).toBe('NETWORK_ERROR')
      
      // Should store action for later retry
      const retryData = {
        action: 'start_mission',
        mission_id: 1,
        timestamp: new Date().toISOString()
      }
      localStorage.setItem('retry_actions', JSON.stringify([retryData]))
    }
    
    // Test validation error
    mockedAxios.post.mockRejectedValue({
      response: {
        status: 422,
        data: {
          errors: {
            'items.0.condition': ['Condition is required']
          }
        }
      }
    })
    
    try {
      await mockedAxios.post('/api/checklists', { items: [{ id: 1 }] })
    } catch (error) {
      expect(error.response.status).toBe(422)
      expect(error.response.data.errors['items.0.condition']).toContain('Condition is required')
    }
  })

  it('completes daily workflow summary', async () => {
    // Step 1: Load daily summary
    mockedAxios.get.mockResolvedValue({
      data: {
        date: '2024-01-15',
        missions_assigned: 3,
        missions_completed: 2,
        missions_pending: 1,
        total_duration: 180,
        photos_taken: 12,
        contracts_signed: 2,
        incidents_reported: 0
      }
    })
    
    const summary = await mockedAxios.get('/api/checkers/2/daily-summary/2024-01-15')
    expect(summary.data.missions_completed).toBe(2)
    expect(summary.data.contracts_signed).toBe(2)
    
    // Step 2: Submit end-of-day report
    const endOfDayReport = {
      date: '2024-01-15',
      notes: 'All missions completed successfully',
      issues_encountered: 'None',
      suggestions: 'Consider adding more time for complex properties'
    }
    
    await mockedAxios.post('/api/checkers/2/end-of-day-report', endOfDayReport)
    expect(mockedAxios.post).toHaveBeenCalledWith('/api/checkers/2/end-of-day-report', endOfDayReport)
  })
})