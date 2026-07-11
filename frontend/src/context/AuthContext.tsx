import {
  createContext,
  useCallback,
  useEffect,
  useMemo,
  useState,
  type ReactNode,
} from 'react'
import { authService } from '@/services/auth.service'
import { clearToken, getToken, setToken } from '@/lib/api'
import type { LoginPayload, RegisterPayload, RoleName, User } from '@/types'

interface AuthContextValue {
  user: User | null
  isLoading: boolean
  isAuthenticated: boolean
  login: (payload: LoginPayload) => Promise<User>
  register: (payload: RegisterPayload) => Promise<User>
  logout: () => Promise<void>
  refresh: () => Promise<void>
  hasRole: (roles: RoleName | RoleName[]) => boolean
  hasPermission: (permission: string) => boolean
  isAdmin: boolean
}

// eslint-disable-next-line react-refresh/only-export-components
export const AuthContext = createContext<AuthContextValue | undefined>(undefined)

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<User | null>(null)
  const [isLoading, setIsLoading] = useState<boolean>(true)

  const bootstrap = useCallback(async () => {
    if (!getToken()) {
      setIsLoading(false)
      return
    }
    try {
      const me = await authService.me()
      setUser(me)
    } catch {
      clearToken()
      setUser(null)
    } finally {
      setIsLoading(false)
    }
  }, [])

  useEffect(() => {
    void bootstrap()
  }, [bootstrap])

  const login = useCallback(async (payload: LoginPayload) => {
    const result = await authService.login(payload)
    setToken(result.token)
    setUser(result.user)
    return result.user
  }, [])

  const register = useCallback(async (payload: RegisterPayload) => {
    const result = await authService.register(payload)
    setToken(result.token)
    setUser(result.user)
    return result.user
  }, [])

  const logout = useCallback(async () => {
    try {
      await authService.logout()
    } catch {
      // ignore network errors on logout
    } finally {
      clearToken()
      setUser(null)
    }
  }, [])

  const refresh = useCallback(async () => {
    const me = await authService.me()
    setUser(me)
  }, [])

  const hasRole = useCallback(
    (roles: RoleName | RoleName[]) => {
      if (!user) return false
      const wanted = Array.isArray(roles) ? roles : [roles]
      return user.roles.some((r) => wanted.includes(r))
    },
    [user],
  )

  const hasPermission = useCallback(
    (permission: string) => !!user?.permissions?.includes(permission),
    [user],
  )

  const value = useMemo<AuthContextValue>(
    () => ({
      user,
      isLoading,
      isAuthenticated: !!user,
      login,
      register,
      logout,
      refresh,
      hasRole,
      hasPermission,
      isAdmin: hasRole(['super_admin', 'event_manager']),
    }),
    [user, isLoading, login, register, logout, refresh, hasRole, hasPermission],
  )

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}
