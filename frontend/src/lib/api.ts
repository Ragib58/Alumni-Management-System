import axios, {
  type AxiosInstance,
  type AxiosError,
  type InternalAxiosRequestConfig,
} from 'axios'

const API_URL = import.meta.env.VITE_API_URL ?? 'http://localhost:8000/api/v1'

export const TOKEN_KEY = 'ams_token'

export function getToken(): string | null {
  return localStorage.getItem(TOKEN_KEY)
}

export function setToken(token: string): void {
  localStorage.setItem(TOKEN_KEY, token)
}

export function clearToken(): void {
  localStorage.removeItem(TOKEN_KEY)
}

const api: AxiosInstance = axios.create({
  baseURL: API_URL,
  headers: {
    Accept: 'application/json',
  },
  withCredentials: false,
})

// Attach the bearer token on every request.
api.interceptors.request.use((config: InternalAxiosRequestConfig) => {
  const token = getToken()
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

// Global response handling: auto-logout on 401.
api.interceptors.response.use(
  (response) => response,
  (error: AxiosError) => {
    if (error.response?.status === 401) {
      clearToken()
      // Avoid redirect loops on the auth pages.
      const path = window.location.pathname
      const isAuthRoute = ['/login', '/register', '/forgot-password', '/reset-password'].some(
        (p) => path.startsWith(p),
      )
      if (!isAuthRoute) {
        window.location.href = '/login'
      }
    }
    return Promise.reject(error)
  },
)

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

export interface ApiError {
  message: string
  errors?: Record<string, string[]>
  status?: number
}

export function extractApiError(error: unknown): ApiError {
  if (axios.isAxiosError(error)) {
    const data = error.response?.data as
      | { message?: string; errors?: Record<string, string[]> }
      | undefined
    return {
      message: data?.message ?? error.message ?? 'Request failed',
      errors: data?.errors,
      status: error.response?.status,
    }
  }
  return { message: 'An unexpected error occurred' }
}

export default api
