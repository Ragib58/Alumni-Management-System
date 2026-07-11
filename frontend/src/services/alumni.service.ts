import api from '@/lib/api'
import type {
  AlumniFilterOptions,
  AlumniFilters,
  AlumniProfile,
  ApiResponse,
  Paginated,
  ProfilePayload,
} from '@/types'

function toParams(filters: AlumniFilters): Record<string, string | number> {
  const params: Record<string, string | number> = {}
  Object.entries(filters).forEach(([key, value]) => {
    if (value !== undefined && value !== null && value !== '') {
      params[key] = value as string | number
    }
  })
  return params
}

/**
 * Build multipart form data for profile updates (supports file upload).
 * Uses the POST + _method=PUT convention so PHP parses multipart correctly.
 */
function toFormData(payload: ProfilePayload): FormData {
  const fd = new FormData()
  Object.entries(payload).forEach(([key, value]) => {
    if (value === undefined || value === null) return
    if (value instanceof File) {
      fd.append(key, value)
    } else {
      fd.append(key, String(value))
    }
  })
  return fd
}

export const alumniService = {
  async directory(filters: AlumniFilters = {}): Promise<Paginated<AlumniProfile>> {
    const { data } = await api.get<ApiResponse<AlumniProfile[]>>('/alumni', {
      params: toParams(filters),
    })
    return { data: data.data, meta: data.meta! }
  },

  async filterOptions(): Promise<AlumniFilterOptions> {
    const { data } = await api.get<ApiResponse<AlumniFilterOptions>>('/alumni/filters')
    return data.data
  },

  async get(id: number): Promise<AlumniProfile> {
    const { data } = await api.get<ApiResponse<AlumniProfile>>(`/alumni/${id}`)
    return data.data
  },

  // ---- Current user's own profile ----
  async myProfile(): Promise<AlumniProfile | null> {
    const { data } = await api.get<ApiResponse<AlumniProfile | null>>('/profile')
    return data.data
  },

  async updateMyProfile(payload: ProfilePayload): Promise<AlumniProfile> {
    const { data } = await api.post<ApiResponse<AlumniProfile>>('/profile', toFormData(payload), {
      headers: { 'Content-Type': 'multipart/form-data', 'X-HTTP-Method-Override': 'PUT' },
      params: { _method: 'PUT' },
    })
    return data.data
  },

  // ---- Admin updating a specific profile ----
  async updateProfile(id: number, payload: ProfilePayload): Promise<AlumniProfile> {
    const { data } = await api.post<ApiResponse<AlumniProfile>>(
      `/alumni/${id}`,
      toFormData(payload),
      {
        headers: { 'Content-Type': 'multipart/form-data', 'X-HTTP-Method-Override': 'PUT' },
        params: { _method: 'PUT' },
      },
    )
    return data.data
  },
}
