import api from '@/lib/api'
import type {
  ApiResponse,
  EventFilters,
  EventItem,
  EventMeta,
  EventPayload,
  Paginated,
} from '@/types'

function toParams(filters: EventFilters): Record<string, string | number | boolean> {
  const params: Record<string, string | number | boolean> = {}
  Object.entries(filters).forEach(([key, value]) => {
    if (value !== undefined && value !== null && value !== '') {
      params[key] = value as string | number | boolean
    }
  })
  return params
}

/**
 * Build multipart form data for event create/update. `form_fields` is JSON-encoded
 * so the array survives multipart transport; the banner file is appended raw.
 */
function toFormData(payload: EventPayload): FormData {
  const fd = new FormData()
  Object.entries(payload).forEach(([key, value]) => {
    if (value === undefined || value === null) return
    if (key === 'banner' && value instanceof File) {
      fd.append('banner', value)
    } else if (key === 'form_fields') {
      fd.append('form_fields', JSON.stringify(value))
    } else if (key !== 'banner') {
      fd.append(key, String(value))
    }
  })
  return fd
}

export const eventService = {
  // ---- Authenticated catalogue ----
  async list(filters: EventFilters = {}): Promise<Paginated<EventItem>> {
    const { data } = await api.get<ApiResponse<EventItem[]>>('/events', {
      params: toParams(filters),
    })
    return { data: data.data, meta: data.meta! }
  },

  async meta(): Promise<EventMeta> {
    const { data } = await api.get<ApiResponse<EventMeta>>('/events/meta')
    return data.data
  },

  async getBySlug(slug: string): Promise<EventItem> {
    const { data } = await api.get<ApiResponse<EventItem>>(`/events/slug/${slug}`)
    return data.data
  },

  // ---- Admin ----
  async getById(id: number): Promise<EventItem> {
    const { data } = await api.get<ApiResponse<EventItem>>(`/events/${id}`)
    return data.data
  },

  async create(payload: EventPayload): Promise<EventItem> {
    const { data } = await api.post<ApiResponse<EventItem>>('/events', toFormData(payload), {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    return data.data
  },

  async update(id: number, payload: EventPayload): Promise<EventItem> {
    const { data } = await api.post<ApiResponse<EventItem>>(`/events/${id}`, toFormData(payload), {
      headers: { 'Content-Type': 'multipart/form-data', 'X-HTTP-Method-Override': 'PUT' },
      params: { _method: 'PUT' },
    })
    return data.data
  },

  async remove(id: number): Promise<void> {
    await api.delete(`/events/${id}`)
  },

  // ---- Public (no auth) ----
  async publicList(filters: EventFilters = {}): Promise<Paginated<EventItem>> {
    const { data } = await api.get<ApiResponse<EventItem[]>>('/public/events', {
      params: toParams(filters),
    })
    return { data: data.data, meta: data.meta! }
  },

  async publicGetBySlug(slug: string): Promise<EventItem> {
    const { data } = await api.get<ApiResponse<EventItem>>(`/public/events/${slug}`)
    return data.data
  },
}
