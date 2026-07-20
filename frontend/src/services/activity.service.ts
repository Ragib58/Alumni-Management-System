import api from '@/lib/api'
import type { ActivityLog, ApiResponse, EnumOptionSimple, Paginated } from '@/types'

export const activityService = {
  async list(
    params: { action?: string; search?: string; page?: number } = {},
  ): Promise<Paginated<ActivityLog> & { actions: EnumOptionSimple[] }> {
    const { data } = await api.get<ApiResponse<ActivityLog[]>>('/admin/activity-logs', { params })
    const meta = data.meta as unknown as { actions: EnumOptionSimple[] }
    return { data: data.data, meta: data.meta!, actions: meta.actions ?? [] }
  },
}
