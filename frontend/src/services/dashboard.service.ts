import api from '@/lib/api'
import type { ApiResponse, DashboardStats } from '@/types'

export const dashboardService = {
  async statistics(): Promise<DashboardStats> {
    const { data } = await api.get<ApiResponse<DashboardStats>>('/dashboard/statistics')
    return data.data
  },
}
