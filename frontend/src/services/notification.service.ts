import api from '@/lib/api'
import type { ApiResponse, AppNotification, Paginated } from '@/types'

interface NotificationsMeta {
  unread_count: number
}

export const notificationService = {
  async list(
    page = 1,
    unread = false,
  ): Promise<Paginated<AppNotification> & { unreadCount: number }> {
    const { data } = await api.get<ApiResponse<AppNotification[]>>('/notifications', {
      params: { page, per_page: 15, unread: unread ? 1 : undefined },
    })
    const meta = data.meta as unknown as NotificationsMeta
    return { data: data.data, meta: data.meta!, unreadCount: meta.unread_count ?? 0 }
  },

  async unreadCount(): Promise<number> {
    const { data } = await api.get<ApiResponse<{ unread_count: number }>>(
      '/notifications/unread-count',
    )
    return data.data.unread_count
  },

  async markRead(id: string): Promise<void> {
    await api.patch(`/notifications/${id}/read`)
  },

  async markAllRead(): Promise<void> {
    await api.patch('/notifications/read-all')
  },

  async remove(id: string): Promise<void> {
    await api.delete(`/notifications/${id}`)
  },
}
