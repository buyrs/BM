import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import UrgentMissions from '@/Components/Checker/UrgentMissions.vue'

describe('Checker UrgentMissions', () => {
  let wrapper

  const mockUrgentMissions = [
    {
      id: 1,
      type: 'entry',
      status: 'assigned',
      priority: 'urgent',
      scheduled_at: '2024-01-15',
      scheduled_time: '09:00',
      tenant_name: 'Emergency Tenant',
      address: '123 Urgent St',
      notes: 'Urgent entry required',
      overdue: true,
      time_until_due: -2 // 2 hours overdue
    },
    {
      id: 2,
      type: 'exit',
      status: 'assigned',
      priority: 'high',
      scheduled_at: '2024-01-15',
      scheduled_time: '14:00',
      tenant_name: 'High Priority Tenant',
      address: '456 Priority Ave',
      notes: 'High priority exit',
      overdue: false,
      time_until_due: 2 // 2 hours until due
    },
    {
      id: 3,
      type: 'entry',
      status: 'assigned',
      priority: 'urgent',
      scheduled_at: '2024-01-15',
      scheduled_time: '16:00',
      tenant_name: 'Critical Tenant',
      address: '789 Critical Blvd',
      notes: 'Critical situation',
      overdue: false,
      time_until_due: 0.5 // 30 minutes until due
    }
  ]

  const defaultProps = {
    missions: mockUrgentMissions,
    loading: false
  }

  beforeEach(() => {
    wrapper = mount(UrgentMissions, {
      props: defaultProps
    })
  })

  it('renders urgent missions component correctly', () => {
    expect(wrapper.find('.urgent-missions').exists()).toBe(true)
    expect(wrapper.find('.urgent-missions-header').exists()).toBe(true)
  })

  it('displays urgent missions count', () => {
    const header = wrapper.find('[data-testid="urgent-count"]')
    expect(header.text()).toContain('3')
  })

  it('shows overdue missions with special styling', () => {
    const overdueMission = wrapper.find('[data-testid="mission-1"]')
    expect(overdueMission.classes()).toContain('overdue')
    expect(overdueMission.find('[data-testid="overdue-badge"]').exists()).toBe(true)
  })

  it('displays priority badges correctly', () => {
    const urgentBadge = wrapper.find('[data-testid="priority-urgent"]')
    const highBadge = wrapper.find('[data-testid="priority-high"]')

    expect(urgentBadge.exists()).toBe(true)
    expect(highBadge.exists()).toBe(true)
    expect(urgentBadge.classes()).toContain('priority-urgent')
    expect(highBadge.classes()).toContain('priority-high')
  })

  it('shows time until due for each mission', () => {
    const mission1Time = wrapper.find('[data-testid="time-until-due-1"]')
    const mission2Time = wrapper.find('[data-testid="time-until-due-2"]')
    const mission3Time = wrapper.find('[data-testid="time-until-due-3"]')

    expect(mission1Time.text()).toContain('2h overdue')
    expect(mission2Time.text()).toContain('2h remaining')
    expect(mission3Time.text()).toContain('30m remaining')
  })

  it('sorts missions by urgency and time', () => {
    const missionElements = wrapper.findAll('[data-testid^="mission-"]')
    
    // First should be overdue urgent mission
    expect(missionElements[0].attributes('data-testid')).toBe('mission-1')
    
    // Then urgent mission due soon
    expect(missionElements[1].attributes('data-testid')).toBe('mission-3')
    
    // Then high priority mission
    expect(missionElements[2].attributes('data-testid')).toBe('mission-2')
  })

  it('emits mission-click when mission is clicked', async () => {
    const mission = wrapper.find('[data-testid="mission-1"]')
    await mission.trigger('click')

    expect(wrapper.emitted('mission-click')).toBeTruthy()
    expect(wrapper.emitted('mission-click')[0][0]).toEqual(mockUrgentMissions[0])
  })

  it('shows quick action buttons', () => {
    const quickActions = wrapper.find('[data-testid="quick-actions-1"]')
    expect(quickActions.exists()).toBe(true)
    
    const startButton = wrapper.find('[data-testid="start-mission-1"]')
    const callButton = wrapper.find('[data-testid="call-tenant-1"]')
    
    expect(startButton.exists()).toBe(true)
    expect(callButton.exists()).toBe(true)
  })

  it('handles start mission action', async () => {
    const startButton = wrapper.find('[data-testid="start-mission-1"]')
    await startButton.trigger('click')

    expect(wrapper.emitted('start-mission')).toBeTruthy()
    expect(wrapper.emitted('start-mission')[0][0]).toBe(1)
  })

  it('handles call tenant action', async () => {
    const callButton = wrapper.find('[data-testid="call-tenant-1"]')
    await callButton.trigger('click')

    expect(wrapper.emitted('call-tenant')).toBeTruthy()
    expect(wrapper.emitted('call-tenant')[0][0]).toEqual(mockUrgentMissions[0])
  })

  it('shows loading state', async () => {
    await wrapper.setProps({ loading: true })
    expect(wrapper.find('[data-testid="loading-spinner"]').exists()).toBe(true)
  })

  it('displays empty state when no urgent missions', async () => {
    await wrapper.setProps({ missions: [] })
    expect(wrapper.find('[data-testid="empty-state"]').exists()).toBe(true)
    expect(wrapper.text()).toContain('No urgent missions')
  })

  it('shows mission type icons', () => {
    const entryIcon = wrapper.find('[data-testid="entry-icon"]')
    const exitIcon = wrapper.find('[data-testid="exit-icon"]')

    expect(entryIcon.exists()).toBe(true)
    expect(exitIcon.exists()).toBe(true)
  })

  it('displays tenant contact information', () => {
    const tenantInfo = wrapper.find('[data-testid="tenant-info-1"]')
    expect(tenantInfo.text()).toContain('Emergency Tenant')
    expect(tenantInfo.text()).toContain('123 Urgent St')
  })

  it('shows mission notes for urgent missions', () => {
    const notes = wrapper.find('[data-testid="mission-notes-1"]')
    expect(notes.text()).toContain('Urgent entry required')
  })

  it('handles refresh action', async () => {
    const refreshButton = wrapper.find('[data-testid="refresh-urgent"]')
    await refreshButton.trigger('click')

    expect(wrapper.emitted('refresh')).toBeTruthy()
  })

  it('filters missions by priority level', async () => {
    const priorityFilter = wrapper.find('[data-testid="priority-filter"]')
    await priorityFilter.setValue('urgent')

    const filteredMissions = wrapper.vm.filteredMissions
    expect(filteredMissions.every(m => m.priority === 'urgent')).toBe(true)
  })

  it('shows notification badge for overdue missions', () => {
    const notificationBadge = wrapper.find('[data-testid="overdue-notification"]')
    expect(notificationBadge.exists()).toBe(true)
    expect(notificationBadge.text()).toContain('1') // One overdue mission
  })

  it('auto-refreshes urgent missions', async () => {
    vi.useFakeTimers()
    
    wrapper = mount(UrgentMissions, {
      props: { ...defaultProps, autoRefresh: true, refreshInterval: 30000 }
    })

    vi.advanceTimersByTime(30000)
    
    expect(wrapper.emitted('refresh')).toBeTruthy()
    
    vi.useRealTimers()
  })
})