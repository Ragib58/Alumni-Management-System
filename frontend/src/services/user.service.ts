import api from '@/lib/api'
import type {
  ApiResponse,
  CreateUserPayload,
  Paginated,
  UpdateUserPayload,
  User,
  UserFilters,
  UserStatus,
} from '@/types'

function toParams(filters: UserFilters): Record<string, string | number> {
  const params: Record<string, string | number> = {}
  Object.entries(filters).forEach(([key, value]) => {
    if (value !== undefined && value !== null && value !== '') {
      params[key] = value as string | number
    }
  })
  return params
}

export const userService = {
  async list(filters: UserFilters = {}): Promise<Paginated<User>> {
    const { data } = await api.get<ApiResponse<User[]>>('/users', {
      params: toParams(filters),
    })
    return { data: data.data, meta: data.meta! }
  },

  async get(id: number): Promise<User> {
    const { data } = await api.get<ApiResponse<User>>(`/users/${id}`)
    return data.data
  },

  async create(payload: CreateUserPayload): Promise<User> {
    const { data } = await api.post<ApiResponse<User>>('/users', payload)
    return data.data
  },

  async update(id: number, payload: UpdateUserPayload): Promise<User> {
    const { data } = await api.put<ApiResponse<User>>(`/users/${id}`, payload)
    return data.data
  },

  async updateStatus(id: number, status: UserStatus): Promise<User> {
    const { data } = await api.patch<ApiResponse<User>>(`/users/${id}/status`, { status })
    return data.data
  },

  async remove(id: number): Promise<void> {
    await api.delete(`/users/${id}`)
  },
}
