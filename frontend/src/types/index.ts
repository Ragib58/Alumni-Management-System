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

// ===========================================================================
// Phase 2 — Events & Registrations
// ===========================================================================

export type EventStatus = 'draft' | 'published' | 'closed' | 'completed'

export type EventType =
  | 'reunion'
  | 'seminar'
  | 'workshop'
  | 'sports'
  | 'cultural_program'
  | 'iftar'

export type RegistrationStatus = 'pending' | 'confirmed' | 'cancelled'

export type PaymentStatus = 'pending' | 'paid' | 'failed' | 'refunded' | 'free'

export type FormFieldType =
  | 'text'
  | 'number'
  | 'email'
  | 'select'
  | 'checkbox'
  | 'radio'
  | 'textarea'
  | 'file'

export interface EventFormField {
  id?: number
  event_id?: number
  label: string
  name?: string
  type: FormFieldType
  options: string[]
  is_required: boolean
  placeholder?: string | null
  help_text?: string | null
  sort_order?: number
}

export interface EventItem {
  id: number
  title: string
  slug: string
  banner: string | null
  banner_url: string | null
  description: string | null
  venue: string | null
  type: EventType
  type_label: string
  event_date: string | null
  registration_start: string | null
  registration_end: string | null
  fee: number
  is_paid: boolean
  max_capacity: number | null
  status: EventStatus
  status_label: string
  confirmed_count: number
  seats_left: number | null
  is_full: boolean
  is_registration_open: boolean
  created_by?: { id: number | null; name: string | null }
  form_fields?: EventFormField[]
  sponsors?: Sponsor[]
  created_at?: string
  updated_at?: string
}

export interface EventRegistration {
  id: number
  registration_no: string
  event_id: number
  user_id: number
  status: RegistrationStatus
  status_label: string
  payment_status: PaymentStatus
  payment_status_label: string
  amount: number
  form_response: Record<string, string | string[]>
  registered_at: string | null
  cancelled_at: string | null
  event?: EventItem
  user?: { id: number; name: string; email: string; phone: string | null }
  created_at?: string
}

export interface EventFilters {
  search?: string
  type?: EventType | ''
  status?: EventStatus | ''
  upcoming?: boolean
  published_only?: boolean
  sort_by?: string
  sort_dir?: 'asc' | 'desc'
  per_page?: number
  page?: number
}

export interface RegistrationFilters {
  status?: RegistrationStatus | ''
  event_id?: number
  search?: string
  per_page?: number
  page?: number
}

export interface EventPayload {
  title: string
  description?: string
  venue?: string
  type: EventType
  event_date: string
  registration_start?: string
  registration_end?: string
  fee?: number
  max_capacity?: number | null
  status: EventStatus
  form_fields?: EventFormField[]
  banner?: File | null
}

export interface EnumOption {
  value: string
  label: string
  requires_options?: boolean
}

export interface EventMeta {
  types: EnumOption[]
  statuses: EnumOption[]
  field_types: EnumOption[]
}

// ===========================================================================
// Phase 3 — Payments & Tickets
// ===========================================================================

export type PaymentGateway = 'sslcommerz' | 'bkash' | 'nagad'

export interface Payment {
  id: number
  registration_id: number
  transaction_id: string
  gateway_transaction_id: string | null
  amount: number
  currency: string
  gateway: PaymentGateway
  gateway_label: string
  status: PaymentStatus
  status_label: string
  payment_date: string | null
  registration?: EventRegistration
  created_at?: string
}

export interface InitiatePaymentResult {
  payment: Payment
  redirect_url: string
  sandbox: boolean
}

export interface Ticket {
  id: number
  registration_id: number
  ticket_no: string
  qr_token: string
  pdf_url: string | null
  issued_at: string | null
  emailed_at: string | null
  checked_in_at: string | null
  is_checked_in: boolean
  registration?: EventRegistration
  created_at?: string
}

export interface PaymentFilters {
  search?: string
  status?: PaymentStatus | ''
  gateway?: PaymentGateway | ''
  event_id?: number
  per_page?: number
  page?: number
}

export interface RevenueByGateway {
  gateway: string
  transactions: number
  revenue: number
}

export interface RevenueByEvent {
  event: string
  revenue: number
  transactions: number
}

export interface MonthlyRevenue {
  month: string
  revenue: number
}

export interface RevenueStats {
  total_revenue: number
  total_refunded: number
  total_paid: number
  total_pending: number
  total_failed: number
  by_gateway: RevenueByGateway[]
  by_event: RevenueByEvent[]
  monthly_revenue: MonthlyRevenue[]
}

// ===========================================================================
// Phase 4 — Attendance, Analytics & Reports
// ===========================================================================

export type AttendanceStatus = 'not_arrived' | 'checked_in' | 'checked_out'

export interface Attendance {
  id: number
  registration_id: number
  event_id: number
  status: AttendanceStatus
  status_label: string
  checkin_time: string | null
  checkout_time: string | null
  checked_by?: { id: number | null; name: string | null }
  registration?: EventRegistration
  created_at?: string
}

export interface AttendanceStats {
  total: number
  checked_in: number
  checked_out: number
  not_arrived: number
  attended: number
}

export interface CheckInResult {
  attendance: Attendance
  message: string
  duplicate: boolean
}

// ------------------------------ Analytics -----------------------------------

export interface AnalyticsCards {
  total_events: number
  total_registrations: number
  total_attendance: number
  total_revenue: number
}

export interface EventParticipation {
  event: string
  registrations: number
  attendance: number
}

export interface AttendanceTrendPoint {
  month: string
  attendance: number
}

export interface RegistrationTrendPoint {
  month: string
  registrations: number
}

export interface AnalyticsCharts {
  monthly_revenue: MonthlyRevenue[]
  event_participation: EventParticipation[]
  attendance_trend: AttendanceTrendPoint[]
  registration_trend: RegistrationTrendPoint[]
}

export interface AnalyticsDashboard {
  cards: AnalyticsCards
  charts: AnalyticsCharts
}

// --------------------------- Year comparison --------------------------------

export interface GrowthMetric {
  year_a: number
  year_b: number
  growth: number
}

export interface YearComparison {
  year_a: number
  year_b: number
  revenue: GrowthMetric
  participation: GrowthMetric
  attendance: GrowthMetric
  series: { metric: string; year_a: number; year_b: number }[]
  monthly: { month: string; year_a: number; year_b: number }[]
}

export interface YearComparisonResponse {
  available_years: number[]
  comparison: YearComparison
}

// ------------------------------- Reports ------------------------------------

export interface EventReportRow {
  event: string
  type: string
  date: string | null
  registrations: number
  confirmed: number
  attendance: number
  attendance_rate: number
  revenue: number
}

export interface FinancialTransaction {
  transaction_id: string
  gateway: string
  event: string | null
  payer: string | null
  amount: number
  status: string
  date: string | null
}

export interface FinancialSummary {
  total_revenue: number
  total_refunds: number
  total_transactions: number
  paid_transactions: number
  failed_transactions: number
}

export interface FinancialReport {
  transactions: FinancialTransaction[]
  summary: FinancialSummary
}

export interface ParticipationRow {
  group: string
  participants: number
  registrations: number
  attendance: number
}

export interface AlumniReport {
  by_batch: ParticipationRow[]
  by_department: ParticipationRow[]
}

export type ReportType = 'event' | 'financial' | 'alumni'
export type ExportFormat = 'excel' | 'csv' | 'pdf'

// ===========================================================================
// Phase 5 — Notifications, Sponsors, Settings, Activity Log
// ===========================================================================

export interface AppNotification {
  id: string
  type: string
  title: string
  message: string
  url: string | null
  data: Record<string, unknown>
  read: boolean
  read_at: string | null
  created_at: string
}

export type SponsorType = 'platinum' | 'gold' | 'silver' | 'bronze'

export interface Sponsor {
  id: number
  event_id: number | null
  name: string
  logo: string | null
  logo_url: string | null
  website: string | null
  amount: number
  sponsor_type: SponsorType
  sponsor_type_label: string
  sort_order: number
  is_active: boolean
  event?: { id: number | null; title: string | null }
  created_at?: string
}

export interface SponsorPayload {
  event_id?: number | null
  name: string
  website?: string
  amount?: number
  sponsor_type: SponsorType
  sort_order?: number
  is_active?: boolean
  logo?: File | null
}

export interface SettingItem {
  key: string
  value: unknown
  group: string
  is_encrypted: boolean
  is_public: boolean
}

export type GroupedSettings = Record<string, SettingItem[]>

export type PublicSettings = Record<string, string | null>

export interface ActivityLog {
  id: number
  action: string
  action_label: string
  description: string | null
  subject_type: string | null
  subject_id: number | null
  properties: Record<string, unknown> | null
  ip_address: string | null
  user?: { id: number; name: string; email: string }
  created_at: string
}

export interface EnumOptionSimple {
  value: string
  label: string
}
