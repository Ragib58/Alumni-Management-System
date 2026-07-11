import api from '@/lib/api'
import type {
  ApiResponse,
  AuthResult,
  ForgotPasswordPayload,
  LoginPayload,
  RegisterPayload,
  ResetPasswordPayload,
  User,
} from '@/types'

export const authService = {
  async login(payload: LoginPayload): Promise<AuthResult> {
    const { data } = await api.post<ApiResponse<AuthResult>>('/auth/login', payload)
    return data.data
  },

  async register(payload: RegisterPayload): Promise<AuthResult> {
    const { data } = await api.post<ApiResponse<AuthResult>>('/auth/register', payload)
    return data.data
  },

  async logout(): Promise<void> {
    await api.post('/auth/logout')
  },

  async me(): Promise<User> {
    const { data } = await api.get<ApiResponse<User>>('/auth/me')
    return data.data
  },

  async forgotPassword(payload: ForgotPasswordPayload): Promise<string> {
    const { data } = await api.post<ApiResponse<null>>('/auth/forgot-password', payload)
    return data.message
  },

  async resetPassword(payload: ResetPasswordPayload): Promise<string> {
    const { data } = await api.post<ApiResponse<null>>('/auth/reset-password', payload)
    return data.message
  },
}
