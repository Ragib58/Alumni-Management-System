import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom'
import { AuthProvider } from '@/context/AuthContext'
import { ToastProvider } from '@/components/ui/toast'

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

export default function App() {
  return (
    <ToastProvider>
      <AuthProvider>
        <BrowserRouter>
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

            {/* Authenticated app */}
            <Route element={<ProtectedRoute />}>
              <Route element={<DashboardLayout />}>
                <Route index element={<IndexRedirect />} />
                <Route path="/directory" element={<AlumniDirectoryPage />} />
                <Route path="/profile" element={<ProfilePage />} />
                <Route path="/profile/edit" element={<EditProfilePage />} />
              </Route>
            </Route>

            {/* Admin-only (Super Admin / Event Manager) */}
            <Route element={<ProtectedRoute roles={['super_admin', 'event_manager']} />}>
              <Route element={<DashboardLayout />}>
                <Route path="/users" element={<UsersPage />} />
                <Route path="/alumni" element={<AlumniManagementPage />} />
              </Route>
            </Route>

            {/* Standalone */}
            <Route path="/403" element={<ForbiddenPage />} />
            <Route path="/404" element={<NotFoundPage />} />
            <Route path="*" element={<Navigate to="/404" replace />} />
          </Routes>
        </BrowserRouter>
      </AuthProvider>
    </ToastProvider>
  )
}
