import { Navigate } from 'react-router-dom'
import { useAuth } from '@/hooks/useAuth'
import { DashboardPage } from '@/pages/dashboard/DashboardPage'

/**
 * Landing route: admins/managers see the dashboard, everyone else is sent
 * to the alumni directory (they have no dashboard access).
 */
export function IndexRedirect() {
  const { isAdmin } = useAuth()
  return isAdmin ? <DashboardPage /> : <Navigate to="/directory" replace />
}
