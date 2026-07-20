import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { CalendarDays, Users, UserCheck, Banknote, GitCompare, FileText } from 'lucide-react'
import { analyticsService } from '@/services/analytics.service'
import { extractApiError } from '@/lib/api'
import { Button } from '@/components/ui/button'
import { Spinner } from '@/components/ui/spinner'
import {
  StatCard,
  BarChartCard,
  LineChartCard,
  AreaChartCard,
} from '@/components/charts'
import { formatCurrency } from '@/lib/utils'
import type { AnalyticsDashboard } from '@/types'

export function AnalyticsDashboardPage() {
  const [data, setData] = useState<AnalyticsDashboard | null>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    analyticsService
      .dashboard()
      .then(setData)
      .catch((e) => setError(extractApiError(e).message))
      .finally(() => setLoading(false))
  }, [])

  if (loading) {
    return (
      <div className="flex h-64 items-center justify-center">
        <Spinner className="h-8 w-8" />
      </div>
    )
  }

  if (error || !data) {
    return <p className="text-destructive">{error ?? 'Failed to load analytics.'}</p>
  }

  const { cards, charts } = data

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold">Analytics Dashboard</h1>
          <p className="text-muted-foreground">Performance across events, attendance and revenue.</p>
        </div>
        <div className="flex gap-2">
          <Link to="/admin/reports">
            <Button variant="outline">
              <FileText className="h-4 w-4" />
              Reports
            </Button>
          </Link>
          <Link to="/admin/year-comparison">
            <Button variant="outline">
              <GitCompare className="h-4 w-4" />
              Year comparison
            </Button>
          </Link>
        </div>
      </div>

      {/* Cards */}
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <StatCard title="Total Events" value={cards.total_events} icon={CalendarDays} accent="bg-indigo-100 text-indigo-600" />
        <StatCard title="Total Registrations" value={cards.total_registrations} icon={Users} accent="bg-blue-100 text-blue-600" />
        <StatCard title="Total Attendance" value={cards.total_attendance} icon={UserCheck} accent="bg-emerald-100 text-emerald-600" />
        <StatCard title="Total Revenue" value={formatCurrency(cards.total_revenue)} icon={Banknote} accent="bg-amber-100 text-amber-600" />
      </div>

      {/* Charts */}
      <div className="grid gap-6 lg:grid-cols-2">
        <BarChartCard
          title="Monthly Revenue"
          data={charts.monthly_revenue}
          xKey="month"
          series={[{ key: 'revenue', name: 'Revenue' }]}
          valueFormatter={formatCurrency}
        />
        <BarChartCard
          title="Event Participation"
          data={charts.event_participation}
          xKey="event"
          series={[
            { key: 'registrations', name: 'Registrations' },
            { key: 'attendance', name: 'Attendance', color: '#10b981' },
          ]}
        />
        <AreaChartCard
          title="Attendance Trend"
          data={charts.attendance_trend}
          xKey="month"
          series={[{ key: 'attendance', name: 'Attendance', color: '#10b981' }]}
        />
        <LineChartCard
          title="Registration Trend"
          data={charts.registration_trend}
          xKey="month"
          series={[{ key: 'registrations', name: 'Registrations' }]}
        />
      </div>
    </div>
  )
}
