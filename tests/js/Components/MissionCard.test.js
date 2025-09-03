import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import MissionCard from '@/Components/MissionCard.vue'

describe('MissionCard', () => {
  let wrapper

  const mockMission = {
    id: 1,
    type: 'entry',
    status: 'assigned',
    scheduled_at: '2024-01-15',
    scheduled_time: '10:00',
    tenant_name: 'John Doe',
    tenant_phone: '+1234567890',
    tenant_email: 'john@example.com',
    address: '123 Main St, City, State',
    notes: 'First floor apartment',
    agent: {
      id: 1,
      name: 'Agent Smith',
      phone: '+0987654321'
    },
    bail_mobilite: {
      id: 1,
      start_date: '2024-01-15',
      end_date: '2024-02-15'
    },
    priority: 'high',
    estimated_duration: 60,
    can_edit: true,
    can_complete: true
  }

  const defaultProps = {
    mission: mockMission,
    showActions: true,
    compact: false
  }

  beforeEach(() => {
    wrapper = mount(MissionCard, {
      props: defaultProps
    })
  })

  it('renders mission card correctly', () => {
    expect(wrapper.find('.mission-card').exists()).toBe(true)
    expect(wrapper.text()).toContain('John Doe')
    expect(wrapper.text()).toContain('123 Main St')
    expect(wrapper.text()).toContain('Agent Smith')
  })

  it('displays mission type badge', () => {
    const typeBadge = wrapper.find('[data-testid="mission-type"]')
    expect(typeBadge.exists()).toBe(true)
    expect(typeBadge.text()).toBe('Entry')
    expect(typeBadge.classes()).toContain('type-entry')
  })

  it('displays mission status badge', () => {
    const statusBadge = wrapper.find('[data-testid="mission-status"]')
    expect(statusBadge.exists()).toBe(true)
    expect(statusBadge.text()).toBe('Assigned')
    expect(statusBadge.classes()).toContain('status-assigned')
  })

  it('displays priority indicator for high priority missions', () => {
    const priorityIndicator = wrapper.find('[data-testid="priority-indicator"]')
    expect(priorityIndicator.exists()).toBe(true)
    expect(priorityIndicator.classes()).toContain('priority-high')
  })

  it('shows scheduled date and time', () => {
    const scheduleInfo = wrapper.find('[data-testid="schedule-info"]')
    expect(scheduleInfo.exists()).toBe(true)
    expect(scheduleInfo.text()).toContain('Jan 15, 2024')
    expect(scheduleInfo.text()).toContain('10:00')
  })

  it('displays tenant contact information', () => {
    const tenantInfo = wrapper.find('[data-testid="tenant-info"]')
    expect(tenantInfo.exists()).toBe(true)
    expect(tenantInfo.text()).toContain('John Doe')
    expect(tenantInfo.text()).toContain('+1234567890')
  })

  it('shows agent information', () => {
    const agentInfo = wrapper.find('[data-testid="agent-info"]')
    expect(agentInfo.exists()).toBe(true)
    expect(agentInfo.text()).toContain('Agent Smith')
  })

  it('displays action buttons when showActions is true', () => {
    const actionsSection = wrapper.find('[data-testid="mission-actions"]')
    expect(actionsSection.exists()).toBe(true)
    
    const editButton = wrapper.find('[data-testid="edit-button"]')
    const completeButton = wrapper.find('[data-testid="complete-button"]')
    
    expect(editButton.exists()).toBe(true)
    expect(completeButton.exists()).toBe(true)
  })

  it('hides action buttons when showActions is false', async () => {
    await wrapper.setProps({ showActions: false })
    
    const actionsSection = wrapper.find('[data-testid="mission-actions"]')
    expect(actionsSection.exists()).toBe(false)
  })

  it('emits edit event when edit button is clicked', async () => {
    const editButton = wrapper.find('[data-testid="edit-button"]')
    await editButton.trigger('click')
    
    expect(wrapper.emitted('edit')).toBeTruthy()
    expect(wrapper.emitted('edit')[0][0]).toEqual(mockMission)
  })

  it('emits complete event when complete button is clicked', async () => {
    const completeButton = wrapper.find('[data-testid="complete-button"]')
    await completeButton.trigger('click')
    
    expect(wrapper.emitted('complete')).toBeTruthy()
    expect(wrapper.emitted('complete')[0][0]).toEqual(mockMission)
  })

  it('emits click event when card is clicked', async () => {
    const card = wrapper.find('.mission-card')
    await card.trigger('click')
    
    expect(wrapper.emitted('click')).toBeTruthy()
    expect(wrapper.emitted('click')[0][0]).toEqual(mockMission)
  })

  it('renders in compact mode', async () => {
    await wrapper.setProps({ compact: true })
    
    expect(wrapper.find('.mission-card').classes()).toContain('compact')
    
    // Some details should be hidden in compact mode
    const notes = wrapper.find('[data-testid="mission-notes"]')
    expect(notes.exists()).toBe(false)
  })

  it('handles mission without agent', async () => {
    const missionWithoutAgent = { ...mockMission, agent: null }
    await wrapper.setProps({ mission: missionWithoutAgent })
    
    const agentInfo = wrapper.find('[data-testid="agent-info"]')
    expect(agentInfo.text()).toContain('Unassigned')
  })

  it('displays overdue indicator for past missions', async () => {
    const overdueMission = {
      ...mockMission,
      scheduled_at: '2024-01-01',
      status: 'assigned'
    }
    await wrapper.setProps({ mission: overdueMission })
    
    const overdueIndicator = wrapper.find('[data-testid="overdue-indicator"]')
    expect(overdueIndicator.exists()).toBe(true)
  })

  it('shows estimated duration', () => {
    const durationInfo = wrapper.find('[data-testid="duration-info"]')
    expect(durationInfo.exists()).toBe(true)
    expect(durationInfo.text()).toContain('60 min')
  })

  it('handles different mission types correctly', async () => {
    const exitMission = { ...mockMission, type: 'exit' }
    await wrapper.setProps({ mission: exitMission })
    
    const typeBadge = wrapper.find('[data-testid="mission-type"]')
    expect(typeBadge.text()).toBe('Exit')
    expect(typeBadge.classes()).toContain('type-exit')
  })

  it('disables actions based on permissions', async () => {
    const restrictedMission = {
      ...mockMission,
      can_edit: false,
      can_complete: false
    }
    await wrapper.setProps({ mission: restrictedMission })
    
    const editButton = wrapper.find('[data-testid="edit-button"]')
    const completeButton = wrapper.find('[data-testid="complete-button"]')
    
    expect(editButton.attributes('disabled')).toBeDefined()
    expect(completeButton.attributes('disabled')).toBeDefined()
  })
})