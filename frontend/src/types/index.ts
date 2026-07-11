// ---------------------------------------------------------------------------
// Domain & API types shared across the app
// ---------------------------------------------------------------------------

export type UserStatus = 'active' | 'inactive' | 'suspended'

export type RoleName =
  | 'super_admin'
  | 'event_manager'
  | 'alumni_member'
  | 'guest'

export interface AlumniProfile {
  id: number
  user_id: number
  student_id: string | null
  batch: string | null
  department: string | null
  session: string | null
  profession: string | null
  company: string | null
  designation: string | null
  address: string | null
  bio: string | null
  profile_photo: string | null
  profile_photo_url: string | null
  user?: UserSummary
  created_at?: string
  updated_at?: string
}

export interface UserSummary {
  id: number
  name: string
  email: string
  phone: string | null
  status: UserStatus
}

export interface User {
  id: number
  name: string
  email: string
  phone: string | null
  status: UserStatus
  roles: RoleName[]
  permissions?: string[]
  profile: AlumniProfile | null
  created_at?: string
  updated_at?: string
}

// ------------------------------ API envelope --------------------------------

export interface ApiResponse<T> {
  success: boolean
  message: string
  data: T
  meta?: PaginationMeta & Record<string, unknown>
  errors?: Record<string, string[]>
}

export interface PaginationMeta {
  current_page: number
  per_page: number
  total: number
  last_page: number
  from: number | null
  to: number | null
}

export interface Paginated<T> {
  data: T[]
  meta: PaginationMeta
}

// ------------------------------ Auth payloads -------------------------------

export interface AuthResult {
  user: User
  token: string
  token_type: string
}

export interface LoginPayload {
  email: string
  password: string
  device_name?: string
}

export interface RegisterPayload {
  name: string
  email: string
  phone?: string
  password: string
  password_confirmation: string
  student_id?: string
  batch?: string
  department?: string
  session?: string
}

export interface ForgotPasswordPayload {
  email: string
}

export interface ResetPasswordPayload {
  token: string
  email: string
  password: string
  password_confirmation: string
}

// --------------------------- User management --------------------------------

export interface UserFilters {
  search?: string
  status?: UserStatus | ''
  role?: RoleName | ''
  sort_by?: string
  sort_dir?: 'asc' | 'desc'
  per_page?: number
  page?: number
}

export interface CreateUserPayload {
  name: string
  email: string
  phone?: string
  password: string
  password_confirmation: string
  status: UserStatus
  roles: RoleName[]
}

export interface UpdateUserPayload {
  name?: string
  email?: string
  phone?: string
  password?: string
  password_confirmation?: string
  status?: UserStatus
  roles?: RoleName[]
}

// ------------------------------ Alumni directory ----------------------------

export interface AlumniFilters {
  search?: string
  batch?: string
  department?: string
  session?: string
  profession?: string
  sort_by?: string
  sort_dir?: 'asc' | 'desc'
  per_page?: number
  page?: number
}

export interface AlumniFilterOptions {
  batches: string[]
  departments: string[]
  sessions: string[]
  professions: string[]
}

export interface ProfilePayload {
  student_id?: string
  batch?: string
  department?: string
  session?: string
  profession?: string
  company?: string
  designation?: string
  address?: string
  bio?: string
  profile_photo?: File | null
}

// ------------------------------ Dashboard -----------------------------------

export interface BatchDistribution {
  batch: string
  total: number
}

export interface DashboardStats {
  total_alumni: number
  total_users: number
  total_active_users: number
  total_inactive_users: number
  total_suspended_users: number
  batch_distribution: BatchDistribution[]
}
