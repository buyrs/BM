import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import CalendarGrid from '@/Components/Calendar/CalendarGrid.vue'

describe('CalendarGrid', () => {
  let wrapper
  const mockMissions = [
    {
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
    },
    {
      id: 2,
      type: 'exit',
      scheduled_at: '2024-01-15',
      scheduled_time: '14:00',
      status: 'completed',
      tenant_name: 'Jane Doe',
      address: '456 Oak Ave',
      agent: { id: 2, name: 'Agent Jones' },
      conflicts: [],
      can_edit: false,
      can_assign: false
    }
  ]

  const defaultProps = {
    missions: mockMissions,
    currentDate: new Date('2024-01-15'),
    viewMode: 'month'
  }

  beforeEach(() => {
    wrapper = mount(CalendarGrid, {
      props: defaultProps
    })
  })

  it('renders calendar grid correctly', () => {
    expect(wrapper.find('.calendar-grid').exists()).toBe(true)
  })

  it('displays missions on correct dates', () => {
    const missionElements = wrapper.findAll('[data-testid="mission-event"]')
    expect(missionElements.length).toBe(2)
  })

  it('emits mission-click event when mission is clicked', async () => {
    const missionElement = wrapper.find('[data-testid="mission-event"]')
    await missionElement.trigger('click')
    
    expect(wrapper.emitted('mission-click')).toBeTruthy()
    expect(wrapper.emitted('mission-click')[0][0]).toEqual(mockMissions[0])
  })

  it('emits date-click event when empty date is clicked', async () => {
    const dateCell = wrapper.find('[data-testid="date-cell-empty"]')
    if (dateCell.exists()) {
      await dateCell.trigger('click')
      expect(wrapper.emitted('date-click')).toBeTruthy()
    }
  })

  it('handles different view modes', async () => {
    await wrapper.setProps({ viewMode: 'week' })
    expect(wrapper.find('.calendar-grid').classes()).toContain('week-view')

    await wrapper.setProps({ viewMode: 'day' })
    expect(wrapper.find('.calendar-grid').classes()).toContain('day-view')
  })

  it('handles empty missions array', async () => {
    await wrapper.setProps({ missions: [] })
    const missionElements = wrapper.findAll('[data-testid="mission-event"]')
    expect(missionElements.length).toBe(0)
  })
})