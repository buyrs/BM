import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import StatsGrid from '@/Components/Admin/StatsGrid.vue'

describe('Admin StatsGrid', () => {
  let wrapper

  const mockStats = {
    totalBailMobilites: 45,
    activeBailMobilites: 23,
    completedMissions: 156,
    pendingMissions: 12,
    totalCheckers: 8,
    activeCheckers: 6,
    totalRevenue: 125000,
    monthlyRevenue: 15000,
    averageCompletionTime: 2.5,
    customerSatisfaction: 4.7,
    incidentRate: 0.03,
    occupancyRate: 0.87
  }

  const defaultProps = {
    stats: mockStats,
    loading: false,
    period: 'month'
  }

  beforeEach(() => {
    wrapper = mount(StatsGrid, {
      props: defaultProps
    })
  })

  it('renders stats grid correctly', () => {
    expect(wrapper.find('.stats-grid').exists()).toBe(true)
    expect(wrapper.findAll('.stat-card').length).toBeGreaterThan(0)
  })

  it('displays bail mobilité statistics', () => {
    const totalBailMobilites = wrapper.find('[data-testid="stat-total-bail-mobilites"]')
    const activeBailMobilites = wrapper.find('[data-testid="stat-active-bail-mobilites"]')

    expect(totalBailMobilites.text()).toContain('45')
    expect(activeBailMobilites.text()).toContain('23')
  })

  it('displays mission statistics', () => {
    const completedMissions = wrapper.find('[data-testid="stat-completed-missions"]')
    const pendingMissions = wrapper.find('[data-testid="stat-pending-missions"]')

    expect(completedMissions.text()).toContain('156')
    expect(pendingMissions.text()).toContain('12')
  })

  it('displays checker statistics', () => {
    const totalCheckers = wrapper.find('[data-testid="stat-total-checkers"]')
    const activeCheckers = wrapper.find('[data-testid="stat-active-checkers"]')

    expect(totalCheckers.text()).toContain('8')
    expect(activeCheckers.text()).toContain('6')
  })

  it('displays revenue statistics with proper formatting', () => {
    const totalRevenue = wrapper.find('[data-testid="stat-total-revenue"]')
    const monthlyRevenue = wrapper.find('[data-testid="stat-monthly-revenue"]')

    expect(totalRevenue.text()).toContain('€125,000')
    expect(monthlyRevenue.text()).toContain('€15,000')
  })

  it('displays performance metrics', () => {
    const completionTime = wrapper.find('[data-testid="stat-completion-time"]')
    const satisfaction = wrapper.find('[data-testid="stat-satisfaction"]')
    const incidentRate = wrapper.find('[data-testid="stat-incident-rate"]')
    const occupancyRate = wrapper.find('[data-testid="stat-occupancy-rate"]')

    expect(completionTime.text()).toContain('2.5')
    expect(satisfaction.text()).toContain('4.7')
    expect(incidentRate.text()).toContain('3%')
    expect(occupancyRate.text()).toContain('87%')
  })

  it('shows loading state', async () => {
    await wrapper.setProps({ loading: true })
    
    const loadingElements = wrapper.findAll('[data-testid="stat-loading"]')
    expect(loadingElements.length).toBeGreaterThan(0)
  })

  it('handles empty stats gracefully', async () => {
    await wrapper.setProps({ stats: {} })
    
    const statCards = wrapper.findAll('.stat-card')
    statCards.forEach(card => {
      expect(card.text()).toContain('--')
    })
  })

  it('updates period and emits period-change event', async () => {
    const periodSelector = wrapper.find('[data-testid="period-selector"]')
    await periodSelector.setValue('week')

    expect(wrapper.emitted('period-change')).toBeTruthy()
    expect(wrapper.emitted('period-change')[0][0]).toBe('week')
  })

  it('displays trend indicators', () => {
    const trendUp = wrapper.find('[data-testid="trend-up"]')
    const trendDown = wrapper.find('[data-testid="trend-down"]')

    expect(trendUp.exists() || trendDown.exists()).toBe(true)
  })

  it('handles stat card clicks for drill-down', async () => {
    const statCard = wrapper.find('[data-testid="stat-total-bail-mobilites"]')
    await statCard.trigger('click')

    expect(wrapper.emitted('stat-click')).toBeTruthy()
    expect(wrapper.emitted('stat-click')[0][0]).toBe('totalBailMobilites')
  })

  it('formats large numbers correctly', () => {
    const largeStats = {
      ...mockStats,
      totalRevenue: 1250000,
      completedMissions: 1560
    }

    wrapper.setProps({ stats: largeStats })

    const totalRevenue = wrapper.find('[data-testid="stat-total-revenue"]')
    expect(totalRevenue.text()).toContain('€1.25M')
  })

  it('shows comparison with previous period', () => {
    const comparison = wrapper.find('[data-testid="period-comparison"]')
    expect(comparison.exists()).toBe(true)
  })

  it('displays appropriate icons for each stat', () => {
    const statCards = wrapper.findAll('.stat-card')
    
    statCards.forEach(card => {
      const icon = card.find('.stat-icon')
      expect(icon.exists()).toBe(true)
    })
  })

  it('handles refresh action', async () => {
    const refreshButton = wrapper.find('[data-testid="refresh-stats"]')
    await refreshButton.trigger('click')

    expect(wrapper.emitted('refresh')).toBeTruthy()
  })

  it('exports stats data', async () => {
    const exportButton = wrapper.find('[data-testid="export-stats"]')
    await exportButton.trigger('click')

    expect(wrapper.emitted('export')).toBeTruthy()
    expect(wrapper.emitted('export')[0][0]).toEqual(mockStats)
  })
})