import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import CalendarFilters from '@/Components/Calendar/CalendarFilters.vue'

describe('CalendarFilters', () => {
  let wrapper
  const mockCheckers = [
    { id: 1, name: 'Agent Smith', email: 'smith@example.com' },
    { id: 2, name: 'Agent Jones', email: 'jones@example.com' }
  ]

  const defaultProps = {
    checkers: mockCheckers,
    filters: {
      status: '',
      checker_id: null,
      mission_type: '',
      search: '',
      date_range: ''
    }
  }

  beforeEach(() => {
    wrapper = mount(CalendarFilters, {
      props: defaultProps
    })
  })

  it('renders all filter controls', () => {
    expect(wrapper.find('[data-testid="status-filter"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="checker-filter"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="mission-type-filter"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="search-filter"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="date-range-filter"]').exists()).toBe(true)
  })

  it('emits filter-change event when status filter changes', async () => {
    const statusFilter = wrapper.find('[data-testid="status-filter"]')
    await statusFilter.setValue('assigned')
    
    expect(wrapper.emitted('filter-change')).toBeTruthy()
    const emittedFilters = wrapper.emitted('filter-change')[0][0]
    expect(emittedFilters.status).toBe('assigned')
  })

  it('emits filter-change event when checker filter changes', async () => {
    const checkerFilter = wrapper.find('[data-testid="checker-filter"]')
    await checkerFilter.setValue('1')
    
    expect(wrapper.emitted('filter-change')).toBeTruthy()
    const emittedFilters = wrapper.emitted('filter-change')[0][0]
    expect(emittedFilters.checker_id).toBe('1')
  })

  it('emits filter-change event when search input changes', async () => {
    const searchInput = wrapper.find('[data-testid="search-filter"]')
    await searchInput.setValue('John Doe')
    
    expect(wrapper.emitted('filter-change')).toBeTruthy()
    const emittedFilters = wrapper.emitted('filter-change')[0][0]
    expect(emittedFilters.search).toBe('John Doe')
  })

  it('debounces search input changes', async () => {
    const searchInput = wrapper.find('[data-testid="search-filter"]')
    
    // Type multiple characters quickly
    await searchInput.setValue('J')
    await searchInput.setValue('Jo')
    await searchInput.setValue('John')
    
    // Should only emit once after debounce delay
    expect(wrapper.emitted('filter-change')).toBeTruthy()
  })

  it('populates checker options correctly', () => {
    const checkerFilter = wrapper.find('[data-testid="checker-filter"]')
    const options = checkerFilter.findAll('option')
    
    expect(options.length).toBeGreaterThan(mockCheckers.length) // Including "All Checkers" option
    expect(options[1].text()).toContain('Agent Smith')
    expect(options[2].text()).toContain('Agent Jones')
  })

  it('shows clear filters button when filters are applied', async () => {
    await wrapper.setProps({
      filters: { ...defaultProps.filters, status: 'assigned' }
    })
    
    expect(wrapper.find('[data-testid="clear-filters"]').exists()).toBe(true)
  })

  it('emits clear event when clear filters button is clicked', async () => {
    await wrapper.setProps({
      filters: { ...defaultProps.filters, status: 'assigned' }
    })
    
    await wrapper.find('[data-testid="clear-filters"]').trigger('click')
    expect(wrapper.emitted('clear')).toBeTruthy()
  })

  it('reflects current filter values in form controls', async () => {
    const activeFilters = {
      status: 'assigned',
      checker_id: '1',
      mission_type: 'entry',
      search: 'test search',
      date_range: 'today'
    }
    
    await wrapper.setProps({ filters: activeFilters })
    
    expect(wrapper.find('[data-testid="status-filter"]').element.value).toBe('assigned')
    expect(wrapper.find('[data-testid="checker-filter"]').element.value).toBe('1')
    expect(wrapper.find('[data-testid="mission-type-filter"]').element.value).toBe('entry')
    expect(wrapper.find('[data-testid="search-filter"]').element.value).toBe('test search')
    expect(wrapper.find('[data-testid="date-range-filter"]').element.value).toBe('today')
  })

  it('shows filter count badge when filters are active', async () => {
    await wrapper.setProps({
      filters: { 
        ...defaultProps.filters, 
        status: 'assigned', 
        mission_type: 'entry' 
      }
    })
    
    const badge = wrapper.find('[data-testid="filter-count-badge"]')
    expect(badge.exists()).toBe(true)
    expect(badge.text()).toBe('2')
  })

  it('handles mobile responsive layout', async () => {
    // Simulate mobile viewport
    Object.defineProperty(window, 'innerWidth', {
      writable: true,
      configurable: true,
      value: 375,
    })
    
    await wrapper.vm.$nextTick()
    expect(wrapper.find('.mobile-filters').exists()).toBe(true)
  })
})