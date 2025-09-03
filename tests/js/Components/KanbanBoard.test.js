import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import KanbanBoard from '@/Components/KanbanBoard.vue'

describe('KanbanBoard', () => {
  let wrapper

  const mockBailMobilites = [
    {
      id: 1,
      tenant_name: 'John Doe',
      status: 'assigned',
      start_date: '2024-01-15',
      end_date: '2024-02-15',
      address: '123 Main St',
      entry_mission: { id: 1, status: 'assigned' },
      exit_mission: { id: 2, status: 'pending' }
    },
    {
      id: 2,
      tenant_name: 'Jane Smith',
      status: 'in_progress',
      start_date: '2024-01-10',
      end_date: '2024-02-10',
      address: '456 Oak Ave',
      entry_mission: { id: 3, status: 'completed' },
      exit_mission: { id: 4, status: 'pending' }
    },
    {
      id: 3,
      tenant_name: 'Bob Johnson',
      status: 'completed',
      start_date: '2024-01-01',
      end_date: '2024-01-31',
      address: '789 Pine St',
      entry_mission: { id: 5, status: 'completed' },
      exit_mission: { id: 6, status: 'completed' }
    }
  ]

  const defaultProps = {
    bailMobilites: mockBailMobilites,
    loading: false
  }

  beforeEach(() => {
    wrapper = mount(KanbanBoard, {
      props: defaultProps
    })
  })

  it('renders kanban board with columns', () => {
    expect(wrapper.find('.kanban-board').exists()).toBe(true)
    expect(wrapper.findAll('.kanban-column').length).toBeGreaterThan(0)
  })

  it('displays bail mobilités in correct columns', () => {
    const assignedColumn = wrapper.find('[data-testid="column-assigned"]')
    const inProgressColumn = wrapper.find('[data-testid="column-in_progress"]')
    const completedColumn = wrapper.find('[data-testid="column-completed"]')

    expect(assignedColumn.exists()).toBe(true)
    expect(inProgressColumn.exists()).toBe(true)
    expect(completedColumn.exists()).toBe(true)
  })

  it('handles drag and drop operations', async () => {
    const dragItem = wrapper.find('[data-testid="bail-mobilite-1"]')
    const dropZone = wrapper.find('[data-testid="column-in_progress"]')

    // Simulate drag start
    await dragItem.trigger('dragstart', {
      dataTransfer: {
        setData: vi.fn(),
        effectAllowed: 'move'
      }
    })

    // Simulate drop
    await dropZone.trigger('drop', {
      dataTransfer: {
        getData: vi.fn(() => '1')
      },
      preventDefault: vi.fn()
    })

    expect(wrapper.emitted('status-change')).toBeTruthy()
  })

  it('emits item-click event when bail mobilité is clicked', async () => {
    const bailMobiliteCard = wrapper.find('[data-testid="bail-mobilite-1"]')
    await bailMobiliteCard.trigger('click')

    expect(wrapper.emitted('item-click')).toBeTruthy()
    expect(wrapper.emitted('item-click')[0][0]).toEqual(mockBailMobilites[0])
  })

  it('shows loading state', async () => {
    await wrapper.setProps({ loading: true })
    expect(wrapper.find('[data-testid="loading-spinner"]').exists()).toBe(true)
  })

  it('handles empty bail mobilités array', async () => {
    await wrapper.setProps({ bailMobilites: [] })
    expect(wrapper.findAll('[data-testid^="bail-mobilite-"]').length).toBe(0)
  })

  it('filters bail mobilités by status', () => {
    const assignedItems = wrapper.vm.getItemsByStatus('assigned')
    const inProgressItems = wrapper.vm.getItemsByStatus('in_progress')
    const completedItems = wrapper.vm.getItemsByStatus('completed')

    expect(assignedItems.length).toBe(1)
    expect(inProgressItems.length).toBe(1)
    expect(completedItems.length).toBe(1)
  })

  it('validates status transitions', () => {
    expect(wrapper.vm.canTransition('assigned', 'in_progress')).toBe(true)
    expect(wrapper.vm.canTransition('in_progress', 'completed')).toBe(true)
    expect(wrapper.vm.canTransition('completed', 'assigned')).toBe(false)
  })

  it('handles bulk operations', async () => {
    const selectAllCheckbox = wrapper.find('[data-testid="select-all"]')
    await selectAllCheckbox.setChecked(true)

    expect(wrapper.vm.selectedItems.length).toBe(mockBailMobilites.length)

    const bulkActionButton = wrapper.find('[data-testid="bulk-action"]')
    await bulkActionButton.trigger('click')

    expect(wrapper.emitted('bulk-action')).toBeTruthy()
  })
})