import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import CalendarNavigation from '@/Components/Calendar/CalendarNavigation.vue'

describe('CalendarNavigation', () => {
  let wrapper
  const defaultProps = {
    currentDate: new Date('2024-01-15'),
    viewMode: 'month'
  }

  beforeEach(() => {
    wrapper = mount(CalendarNavigation, {
      props: defaultProps
    })
  })

  it('renders navigation controls', () => {
    expect(wrapper.find('[data-testid="prev-button"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="next-button"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="today-button"]').exists()).toBe(true)
  })

  it('displays current date correctly', () => {
    expect(wrapper.text()).toContain('January 2024')
  })

  it('emits date-change event when previous button is clicked', async () => {
    await wrapper.find('[data-testid="prev-button"]').trigger('click')
    expect(wrapper.emitted('date-change')).toBeTruthy()
  })

  it('emits date-change event when next button is clicked', async () => {
    await wrapper.find('[data-testid="next-button"]').trigger('click')
    expect(wrapper.emitted('date-change')).toBeTruthy()
  })

  it('emits date-change event when today button is clicked', async () => {
    await wrapper.find('[data-testid="today-button"]').trigger('click')
    expect(wrapper.emitted('date-change')).toBeTruthy()
  })

  it('shows view mode selector', () => {
    expect(wrapper.find('[data-testid="view-selector"]').exists()).toBe(true)
  })

  it('emits view-change event when view mode is changed', async () => {
    const viewSelector = wrapper.find('[data-testid="view-selector"]')
    await viewSelector.setValue('week')
    expect(wrapper.emitted('view-change')).toBeTruthy()
    expect(wrapper.emitted('view-change')[0][0]).toBe('week')
  })

  it('handles different view modes correctly', async () => {
    await wrapper.setProps({ viewMode: 'week' })
    expect(wrapper.find('[data-testid="view-selector"]').element.value).toBe('week')

    await wrapper.setProps({ viewMode: 'day' })
    expect(wrapper.find('[data-testid="view-selector"]').element.value).toBe('day')
  })

  it('disables navigation when loading', async () => {
    await wrapper.setProps({ loading: true })
    expect(wrapper.find('[data-testid="prev-button"]').attributes('disabled')).toBeDefined()
    expect(wrapper.find('[data-testid="next-button"]').attributes('disabled')).toBeDefined()
  })

  it('supports keyboard navigation', async () => {
    await wrapper.trigger('keydown', { key: 'ArrowLeft' })
    expect(wrapper.emitted('date-change')).toBeTruthy()

    await wrapper.trigger('keydown', { key: 'ArrowRight' })
    expect(wrapper.emitted('date-change')).toBeTruthy()
  })
})