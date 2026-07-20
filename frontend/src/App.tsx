import { lazy, Suspense } from 'react'
import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom'
import { AuthProvider } from '@/context/AuthContext'
import { SettingsProvider } from '@/context/SettingsContext'
import { ToastProvider } from '@/components/ui/toast'
import { FullPageSpinner } from '@/components/ui/spinner'

// Layouts
import { AuthLayout } from '@/layouts/AuthLayout'
import { DashboardLayout } from '@/layouts/DashboardLayout'

// Route guards
import { ProtectedRoute } from '@/components/common/ProtectedRoute'
import { GuestRoute } from '@/components/common/GuestRoute'

// Auth pages
import { LoginPage } from '@/pages/auth/LoginPage'
import { RegisterPage } from '@/pages/auth/RegisterPage'
import { ForgotPasswordPage } from '@/pages/auth/ForgotPasswordPage'
import { ResetPasswordPage } from '@/pages/auth/ResetPasswordPage'

// App pages
import { IndexRedirect } from '@/pages/misc/IndexRedirect'
import { UsersPage } from '@/pages/users/UsersPage'
import { AlumniManagementPage } from '@/pages/alumni/AlumniManagementPage'
import { AlumniDirectoryPage } from '@/pages/alumni/AlumniDirectoryPage'
import { ProfilePage } from '@/pages/profile/ProfilePage'
import { EditProfilePage } from '@/pages/profile/EditProfilePage'
import { NotFoundPage } from '@/pages/misc/NotFoundPage'
import { ForbiddenPage } from '@/pages/misc/ForbiddenPage'

// Phase 2 — Events & Registrations
import { EventListPage } from '@/pages/events/EventListPage'
import { EventDetailsPage } from '@/pages/events/EventDetailsPage'
import { RegistrationFormPage } from '@/pages/events/RegistrationFormPage'
import { MyRegistrationsPage } from '@/pages/events/MyRegistrationsPage'
import { AdminEventListPage } from '@/pages/events/admin/AdminEventListPage'
import { EventFormPage } from '@/pages/events/admin/EventFormPage'
import { RegistrationListPage } from '@/pages/events/admin/RegistrationListPage'
import { PublicEventsPage } from '@/pages/events/public/PublicEventsPage'
import { PublicEventDetailsPage } from '@/pages/events/public/PublicEventDetailsPage'

// Phase 3 — Payments & Tickets
import { PaymentPage } from '@/pages/payments/PaymentPage'
import { SandboxGatewayPage } from '@/pages/payments/SandboxGatewayPage'
import { PaymentSuccessPage } from '@/pages/payments/PaymentSuccessPage'
import { PaymentFailedPage } from '@/pages/payments/PaymentFailedPage'
import { MyTicketsPage } from '@/pages/tickets/MyTicketsPage'
import { PaymentListPage } from '@/pages/payments/admin/PaymentListPage'
import { TransactionDetailsPage } from '@/pages/payments/admin/TransactionDetailsPage'
import { RevenueDashboardPage } from '@/pages/payments/admin/RevenueDashboardPage'

// Phase 4 — Attendance & Analytics
import { CheckInScannerPage } from '@/pages/attendance/CheckInScannerPage'
import { EventAttendancePage } from '@/pages/attendance/EventAttendancePage'
import { AnalyticsDashboardPage } from '@/pages/analytics/AnalyticsDashboardPage'
import { YearComparisonPage } from '@/pages/analytics/YearComparisonPage'
import { ReportsPage } from '@/pages/analytics/ReportsPage'

// Phase 5 — Notifications, Sponsors, Settings, Activity (code-split / lazy-loaded)
const NotificationsPage = lazy(() =>
  import('@/pages/notifications/NotificationsPage').then((m) => ({ default: m.NotificationsPage })),
)
const SponsorManagementPage = lazy(() =>
  import('@/pages/sponsors/SponsorManagementPage').then((m) => ({ default: m.SponsorManagementPage })),
)
const SettingsPage = lazy(() =>
  import('@/pages/settings/SettingsPage').then((m) => ({ default: m.SettingsPage })),
)
const ActivityLogPage = lazy(() =>
  import('@/pages/activity/ActivityLogPage').then((m) => ({ default: m.ActivityLogPage })),
)

export default function App() {
  return (
    <ToastProvider>
      <SettingsProvider>
        <AuthProvider>
          <BrowserRouter>
            <Suspense fallback={<FullPageSpinner />}>
          <Routes>
            {/* Guest-only auth routes */}
            <Route element={<GuestRoute />}>
              <Route element={<AuthLayout />}>
                <Route path="/login" element={<LoginPage />} />
                <Route path="/register" element={<RegisterPage />} />
                <Route path="/forgot-password" element={<ForgotPasswordPage />} />
                <Route path="/reset-password" element={<ResetPasswordPage />} />
              </Route>
            </Route>

            {/* Public event pages (no auth required) */}
            <Route path="/public/events" element={<PublicEventsPage />} />
            <Route path="/public/events/:slug" element={<PublicEventDetailsPage />} />

            {/* Authenticated full-screen payment flow (no dashboard chrome) */}
            <Route element={<ProtectedRoute />}>
              <Route path="/payment/simulate" element={<SandboxGatewayPage />} />
              <Route path="/payment/success" element={<PaymentSuccessPage />} />
              <Route path="/payment/failed" element={<PaymentFailedPage />} />
            </Route>

            {/* Authenticated app */}
            <Route element={<ProtectedRoute />}>
              <Route element={<DashboardLayout />}>
                <Route index element={<IndexRedirect />} />
                <Route path="/directory" element={<AlumniDirectoryPage />} />
                <Route path="/profile" element={<ProfilePage />} />
                <Route path="/profile/edit" element={<EditProfilePage />} />

                {/* Events (any authenticated user) */}
                <Route path="/events" element={<EventListPage />} />
                <Route path="/events/:slug" element={<EventDetailsPage />} />
                <Route path="/events/:slug/register" element={<RegistrationFormPage />} />
                <Route path="/my-registrations" element={<MyRegistrationsPage />} />

                {/* Payments & tickets */}
                <Route path="/registrations/:id/pay" element={<PaymentPage />} />
                <Route path="/my-tickets" element={<MyTicketsPage />} />

                {/* Notifications (all users) */}
                <Route path="/notifications" element={<NotificationsPage />} />
              </Route>
            </Route>

            {/* Admin-only (Super Admin / Event Manager) */}
            <Route element={<ProtectedRoute roles={['super_admin', 'event_manager']} />}>
              <Route element={<DashboardLayout />}>
                <Route path="/users" element={<UsersPage />} />
                <Route path="/alumni" element={<AlumniManagementPage />} />

                {/* Event management */}
                <Route path="/admin/events" element={<AdminEventListPage />} />
                <Route path="/admin/events/create" element={<EventFormPage />} />
                <Route path="/admin/events/:id/edit" element={<EventFormPage />} />
                <Route path="/admin/events/:id/registrations" element={<RegistrationListPage />} />
                <Route path="/admin/registrations" element={<RegistrationListPage />} />

                {/* Payment management */}
                <Route path="/admin/payments" element={<PaymentListPage />} />
                <Route path="/admin/payments/:id" element={<TransactionDetailsPage />} />
                <Route path="/admin/revenue" element={<RevenueDashboardPage />} />

                {/* Attendance & analytics */}
                <Route path="/admin/attendance/scan" element={<CheckInScannerPage />} />
                <Route path="/admin/events/:id/attendance" element={<EventAttendancePage />} />
                <Route path="/admin/analytics" element={<AnalyticsDashboardPage />} />
                <Route path="/admin/reports" element={<ReportsPage />} />
                <Route path="/admin/year-comparison" element={<YearComparisonPage />} />

                {/* Phase 5 — sponsors & activity log */}
                <Route path="/admin/sponsors" element={<SponsorManagementPage />} />
                <Route path="/admin/activity" element={<ActivityLogPage />} />
              </Route>
            </Route>

            {/* Super Admin only */}
            <Route element={<ProtectedRoute roles={['super_admin']} />}>
              <Route element={<DashboardLayout />}>
                <Route path="/admin/settings" element={<SettingsPage />} />
              </Route>
            </Route>

            {/* Standalone */}
            <Route path="/403" element={<ForbiddenPage />} />
            <Route path="/404" element={<NotFoundPage />} />
            <Route path="*" element={<Navigate to="/404" replace />} />
          </Routes>
            </Suspense>
          </BrowserRouter>
        </AuthProvider>
      </SettingsProvider>
    </ToastProvider>
  )
}
