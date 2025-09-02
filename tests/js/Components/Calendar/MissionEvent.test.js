import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import MissionEvent from '@/Components/Calendar/MissionEvent.vue'

describe('MissionEvent', () => {
  let wrapper
  const mockMission = {
    id: 1,
    type: 'entry',
    scheduled_at: '2024-01-15',
    scheduled_time: '10:00',
    status: 'assigned',
    tenant_name: 'John Doe',
    address: '123 Main St',
    agent: { id: 1, name: 'Agent Smith' },
    conflicts: [],
    can_edit: true,
    can_assign: true
  }

  beforeEach(() => {
    wrapper = mount(MissionEvent, {
      props: { mission: mockMission }
    })
  })

  it('renders mission information correctly', () => {
    expect(wrapper.text()).toContain('John Doe')
    expect(wrapper.text()).toContain('10:00')
  })

  it('applies correct CSS classes based on mission type', () => {
    expect(wrapper.find('.mission-event').classes()).toContain('entry-mission')
    
    const exitMission = { ...mockMission, type: 'exit' }
    wrapper = mount(MissionEvent, { props: { mission: exitMission } })
    expect(wrapper.find('.mission-event').classes()).toContain('exit-mission')
  })

  it('applies correct CSS classes based on mission status', () => {
    expect(wrapper.find('.mission-event').classes()).toContain('status-assigned')
    
    const completedMission = { ...mockMission, status: 'completed' }
    wrapper = mount(MissionEvent, { props: { mission: completedMission } })
    expect(wrapper.find('.mission-event').classes()).toContain('status-completed')
  })

  it('shows conflict indicator when mission has conflicts', async () => {
    const conflictMission = { 
      ...mockMission, 
      conflicts: ['Time conflict with another mission'] 
    }
    await wrapper.setProps({ mission: conflictMission })
    
    expect(wrapper.find('.conflict-indicator').exists()).toBe(true)
  })

  it('emits click event when clicked', async () => {
    await wrapper.trigger('click')
    expect(wrapper.emitted('click')).toBeTruthy()
    expect(wrapper.emitted('click')[0][0]).toEqual(mockMission)
  })

  it('shows tooltip on hover', async () => {
    await wrapper.trigger('mouseenter')
    expect(wrapper.find('.mission-tooltip').exists()).toBe(true)
    
    await wrapper.trigger('mouseleave')
    // Tooltip should be hidden after a delay
  })

  it('handles mission without agent', () => {
    const unassignedMission = { ...mockMission, agent: null }
    wrapper = mount(MissionEvent, { props: { mission: unassignedMission } })
    
    expect(wrapper.text()).toContain('Unassigned')
  })

  it('displays correct mission type label', () => {
    expect(wrapper.text()).toContain('Entry')
    
    const exitMission = { ...mockMission, type: 'exit' }
    wrapper = mount(MissionEvent, { props: { mission: exitMission } })
    expect(wrapper.text()).toContain('Exit')
  })
})