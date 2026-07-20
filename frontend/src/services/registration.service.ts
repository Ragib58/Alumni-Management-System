import api from '@/lib/api'
import type {
  ApiResponse,
  EventRegistration,
  Paginated,
  PaymentStatus,
  RegistrationFilters,
  RegistrationStatus,
} from '@/types'

function toParams(filters: RegistrationFilters): Record<string, string | number> {
  const params: Record<string, string | number> = {}
  Object.entries(filters).forEach(([key, value]) => {
    if (value !== undefined && value !== null && value !== '') {
      params[key] = value as string | number
    }
  })
  return params
}

export const registrationService = {
  /**
   * Register the current user for an event.
   * `formResponse` maps field name → value; `files` maps field name → File.
   */
  async register(
    eventId: number,
    formResponse: Record<string, string | string[]>,
    files: Record<string, File> = {},
  ): Promise<EventRegistration> {
    const fd = new FormData()
    fd.append('form_response', JSON.stringify(formResponse))
    Object.entries(files).forEach(([name, file]) => {
      fd.append(`files[${name}]`, file)
    })

    const { data } = await api.post<ApiResponse<EventRegistration>>(
      `/events/${eventId}/register`,
      fd,
      { headers: { 'Content-Type': 'multipart/form-data' } },
    )
    return data.data
  },

  // ---- Current user ----
  async myRegistrations(filters: RegistrationFilters = {}): Promise<Paginated<EventRegistration>> {
    const { data } = await api.get<ApiResponse<EventRegistration[]>>('/my-registrations', {
      params: toParams(filters),
    })
    return { data: data.data, meta: data.meta! }
  },

  async getOwn(registrationId: number): Promise<EventRegistration> {
    const { data } = await api.get<ApiResponse<EventRegistration>>(
      `/my-registrations/${registrationId}`,
    )
    return data.data
  },

  async cancel(registrationId: number): Promise<EventRegistration> {
    const { data } = await api.delete<ApiResponse<EventRegistration>>(
      `/registrations/${registrationId}/cancel`,
    )
    return data.data
  },

  // ---- Admin ----
  async adminList(filters: RegistrationFilters = {}): Promise<Paginated<EventRegistration>> {
    const { data } = await api.get<ApiResponse<EventRegistration[]>>('/registrations', {
      params: toParams(filters),
    })
    return { data: data.data, meta: data.meta! }
  },

  async get(id: number): Promise<EventRegistration> {
    const { data } = await api.get<ApiResponse<EventRegistration>>(`/registrations/${id}`)
    return data.data
  },

  async updateStatus(
    id: number,
    status: RegistrationStatus,
    paymentStatus?: PaymentStatus,
  ): Promise<EventRegistration> {
    const { data } = await api.patch<ApiResponse<EventRegistration>>(
      `/registrations/${id}/status`,
      { status, payment_status: paymentStatus },
    )
    return data.data
  },
}
