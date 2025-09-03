import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import NotificationPanel from '@/Components/NotificationPanel.vue'

describe('NotificationPanel', () => {
  let wrapper

  const mockNotifications = [
    {
      id: 1,
      type: 'mission_completed',
      title: 'Mission Completed',
      message: 'Entry mission for John Doe has been completed',
      priority: 'high',
      read_at: null,
      created_at: '2024-01-15T10:00:00Z',
      data: { mission_id: 1, bail_mobilite_id: 1 }
    },
    {
      id: 2,
      type: 'reminder',
      title: 'Exit Reminder',
      message: 'Bail mobilité for Jane Smith expires in 3 days',
      priority: 'medium',
      read_at: '2024-01-15T11:00:00Z',
      created_at: '2024-01-15T09:00:00Z',
      data: { bail_mobilite_id: 2 }
    },
    {
      id: 3,
      type: 'incident',
      title: 'Incident Reported',
      message: 'Damage reported at 789 Pine St',
      priority: 'urgent',
      read_at: null,
      created_at: '2024-01-15T08:00:00Z',
      data: { incident_id: 1 }
    }
  ]

  const defaultProps = {
    notifications: mockNotifications,
    loading: false
  }

  beforeEach(() => {
    wrapper = mount(NotificationPanel, {
      props: defaultProps
    })
  })

  it('renders notification panel correctly', () => {
    expect(wrapper.find('.notification-panel').exists()).toBe(true)
    expect(wrapper.findAll('.notification-item').length).toBe(3)
  })

  it('displays notifications with correct priority styling', () => {
    const urgentNotification = wrapper.find('[data-testid="notification-3"]')
    const highNotification = wrapper.find('[data-testid="notification-1"]')
    const mediumNotification = wrapper.find('[data-testid="notification-2"]')

    expect(urgentNotification.classes()).toContain('priority-urgent')
    expect(highNotification.classes()).toContain('priority-high')
    expect(mediumNotification.classes()).toContain('priority-medium')
  })

  it('shows unread notifications differently', () => {
    const unreadNotification = wrapper.find('[data-testid="notification-1"]')
    const readNotification = wrapper.find('[data-testid="notification-2"]')

    expect(unreadNotification.classes()).toContain('unread')
    expect(readNotification.classes()).not.toContain('unread')
  })

  it('marks notification as read when clicked', async () => {
    const notification = wrapper.find('[data-testid="notification-1"]')
    await notification.trigger('click')

    expect(wrapper.emitted('mark-read')).toBeTruthy()
    expect(wrapper.emitted('mark-read')[0][0]).toBe(1)
  })

  it('dismisses notification when dismiss button is clicked', async () => {
    const dismissButton = wrapper.find('[data-testid="dismiss-1"]')
    await dismissButton.trigger('click')

    expect(wrapper.emitted('dismiss')).toBeTruthy()
    expect(wrapper.emitted('dismiss')[0][0]).toBe(1)
  })

  it('handles notification actions', async () => {
    const actionButton = wrapper.find('[data-testid="action-1"]')
    if (actionButton.exists()) {
      await actionButton.trigger('click')
      expect(wrapper.emitted('action')).toBeTruthy()
    }
  })

  it('filters notifications by type', async () => {
    const typeFilter = wrapper.find('[data-testid="type-filter"]')
    await typeFilter.setValue('incident')

    expect(wrapper.vm.filteredNotifications.length).toBe(1)
    expect(wrapper.vm.filteredNotifications[0].type).toBe('incident')
  })

  it('sorts notifications by priority and date', () => {
    const sorted = wrapper.vm.sortedNotifications
    
    // Urgent should come first
    expect(sorted[0].priority).toBe('urgent')
    
    // Among same priority, newer should come first
    expect(new Date(sorted[0].created_at) >= new Date(sorted[1].created_at)).toBe(true)
  })

  it('shows empty state when no notifications', async () => {
    await wrapper.setProps({ notifications: [] })
    expect(wrapper.find('[data-testid="empty-state"]').exists()).toBe(true)
  })

  it('shows loading state', async () => {
    await wrapper.setProps({ loading: true })
    expect(wrapper.find('[data-testid="loading-spinner"]').exists()).toBe(true)
  })

  it('handles bulk mark as read', async () => {
    const markAllButton = wrapper.find('[data-testid="mark-all-read"]')
    await markAllButton.trigger('click')

    expect(wrapper.emitted('mark-all-read')).toBeTruthy()
  })

  it('displays notification count badge', () => {
    const unreadCount = mockNotifications.filter(n => !n.read_at).length
    const badge = wrapper.find('[data-testid="notification-count"]')
    
    expect(badge.text()).toBe(unreadCount.toString())
  })

  it('auto-refreshes notifications', async () => {
    vi.useFakeTimers()
    
    // Mount component with auto-refresh enabled
    wrapper = mount(NotificationPanel, {
      props: { ...defaultProps, autoRefresh: true, refreshInterval: 5000 }
    })

    vi.advanceTimersByTime(5000)
    
    expect(wrapper.emitted('refresh')).toBeTruthy()
    
    vi.useRealTimers()
  })
})