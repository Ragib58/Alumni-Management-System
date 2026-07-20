import api from '@/lib/api'
import type { AnalyticsDashboard, ApiResponse, YearComparisonResponse } from '@/types'

export const analyticsService = {
  async dashboard(): Promise<AnalyticsDashboard> {
    const { data } = await api.get<ApiResponse<AnalyticsDashboard>>('/admin/analytics/dashboard')
    return data.data
  },

  async yearComparison(yearA?: number, yearB?: number): Promise<YearComparisonResponse> {
    const { data } = await api.get<ApiResponse<YearComparisonResponse>>(
      '/admin/analytics/year-comparison',
      { params: { year_a: yearA, year_b: yearB } },
    )
    return data.data
  },
}
