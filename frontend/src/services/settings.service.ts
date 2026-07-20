import api from '@/lib/api'
import type { ApiResponse, GroupedSettings, PublicSettings, SettingItem } from '@/types'

export const settingsService = {
  async publicSettings(): Promise<PublicSettings> {
    const { data } = await api.get<ApiResponse<PublicSettings>>('/public/settings')
    return data.data
  },

  async all(): Promise<GroupedSettings> {
    const { data } = await api.get<ApiResponse<GroupedSettings>>('/admin/settings')
    return data.data
  },

  async update(settings: { key: string; value: unknown }[]): Promise<GroupedSettings> {
    const { data } = await api.put<ApiResponse<GroupedSettings>>('/admin/settings', { settings })
    return data.data
  },
}

export type { SettingItem }
