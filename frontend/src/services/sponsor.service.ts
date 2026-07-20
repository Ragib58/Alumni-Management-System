import api from '@/lib/api'
import type {
  ApiResponse,
  EnumOptionSimple,
  Paginated,
  Sponsor,
  SponsorPayload,
} from '@/types'

function toFormData(payload: SponsorPayload): FormData {
  const fd = new FormData()
  Object.entries(payload).forEach(([key, value]) => {
    if (value === undefined || value === null) return
    if (key === 'logo' && value instanceof File) fd.append('logo', value)
    else if (key !== 'logo') fd.append(key, String(value))
  })
  return fd
}

export const sponsorService = {
  async list(
    params: { search?: string; event_id?: number; sponsor_type?: string; page?: number } = {},
  ): Promise<Paginated<Sponsor>> {
    const { data } = await api.get<ApiResponse<Sponsor[]>>('/admin/sponsors', { params })
    return { data: data.data, meta: data.meta! }
  },

  async meta(): Promise<EnumOptionSimple[]> {
    const { data } = await api.get<ApiResponse<{ types: EnumOptionSimple[] }>>('/admin/sponsors/meta')
    return data.data.types
  },

  async create(payload: SponsorPayload): Promise<Sponsor> {
    const { data } = await api.post<ApiResponse<Sponsor>>('/admin/sponsors', toFormData(payload), {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    return data.data
  },

  async update(id: number, payload: SponsorPayload): Promise<Sponsor> {
    const { data } = await api.post<ApiResponse<Sponsor>>(
      `/admin/sponsors/${id}`,
      toFormData(payload),
      {
        headers: { 'Content-Type': 'multipart/form-data', 'X-HTTP-Method-Override': 'PUT' },
        params: { _method: 'PUT' },
      },
    )
    return data.data
  },

  async remove(id: number): Promise<void> {
    await api.delete(`/admin/sponsors/${id}`)
  },
}
