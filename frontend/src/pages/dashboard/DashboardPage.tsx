import { useEffect, useState } from 'react'
import {
  Bar,
  BarChart,
  CartesianGrid,
  ResponsiveContainer,
  Tooltip,
  XAxis,
  YAxis,
} from 'recharts'
import { Users, UserCheck, GraduationCap, UserX } from 'lucide-react'
import { dashboardService } from '@/services/dashboard.service'
import { extractApiError } from '@/lib/api'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Spinner } from '@/components/ui/spinner'
import type { DashboardStats } from '@/types'

interface StatCardProps {
  title: string
  value: number
  icon: React.ComponentType<{ className?: string }>
  accent: string
}

function StatCard({ title, value, icon: Icon, accent }: StatCardProps) {
  return (
    <Card>
      <CardContent className="flex items-center gap-4 p-6">
        <div className={`flex h-12 w-12 items-center justify-center rounded-lg ${accent}`}>
          <Icon className="h-6 w-6" />
        </div>
        <div>
          <p className="text-sm text-muted-foreground">{title}</p>
          <p className="text-2xl font-bold">{value.toLocaleString()}</p>
        </div>
      </CardContent>
    </Card>
  )
}

export function DashboardPage() {
  const [stats, setStats] = useState<DashboardStats | null>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    dashboardService
      .statistics()
      .then(setStats)
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

  if (error || !stats) {
    return <p className="text-destructive">{error ?? 'Failed to load statistics.'}</p>
  }

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold">Dashboard</h1>
        <p className="text-muted-foreground">Overview of your alumni network.</p>
      </div>

      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <StatCard
          title="Total Alumni"
          value={stats.total_alumni}
          icon={GraduationCap}
          accent="bg-indigo-100 text-indigo-600"
        />
        <StatCard
          title="Active Users"
          value={stats.total_active_users}
          icon={UserCheck}
          accent="bg-emerald-100 text-emerald-600"
        />
        <StatCard
          title="Total Users"
          value={stats.total_users}
          icon={Users}
          accent="bg-blue-100 text-blue-600"
        />
        <StatCard
          title="Inactive / Suspended"
          value={stats.total_inactive_users + stats.total_suspended_users}
          icon={UserX}
          accent="bg-amber-100 text-amber-600"
        />
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Batch Distribution</CardTitle>
        </CardHeader>
        <CardContent>
          {stats.batch_distribution.length === 0 ? (
            <p className="py-12 text-center text-sm text-muted-foreground">
              No alumni batch data yet.
            </p>
          ) : (
            <div className="h-80 w-full">
              <ResponsiveContainer width="100%" height="100%">
                <BarChart data={stats.batch_distribution} margin={{ top: 8, right: 8, bottom: 8, left: 0 }}>
                  <CartesianGrid strokeDasharray="3 3" className="stroke-muted" vertical={false} />
                  <XAxis dataKey="batch" tickLine={false} axisLine={false} fontSize={12} />
                  <YAxis allowDecimals={false} tickLine={false} axisLine={false} fontSize={12} />
                  <Tooltip
                    cursor={{ fill: 'hsl(var(--muted))' }}
                    contentStyle={{
                      borderRadius: 8,
                      border: '1px solid hsl(var(--border))',
                      background: 'hsl(var(--background))',
                    }}
                  />
                  <Bar dataKey="total" name="Alumni" fill="hsl(var(--primary))" radius={[4, 4, 0, 0]} />
                </BarChart>
              </ResponsiveContainer>
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  )
}
