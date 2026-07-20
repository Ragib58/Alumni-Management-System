import { useEffect, useState } from 'react'
import {
  Bar,
  BarChart,
  CartesianGrid,
  Cell,
  Pie,
  PieChart,
  ResponsiveContainer,
  Tooltip,
  XAxis,
  YAxis,
} from 'recharts'
import { Banknote, CheckCircle2, Clock, RotateCcw } from 'lucide-react'
import { paymentService } from '@/services/payment.service'
import { extractApiError } from '@/lib/api'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Spinner } from '@/components/ui/spinner'
import { formatCurrency } from '@/lib/utils'
import type { RevenueStats } from '@/types'

const GATEWAY_COLORS: Record<string, string> = {
  sslcommerz: '#2563eb',
  bkash: '#db2777',
  nagad: '#ea580c',
}

function StatCard({
  title,
  value,
  icon: Icon,
  accent,
}: {
  title: string
  value: string
  icon: React.ComponentType<{ className?: string }>
  accent: string
}) {
  return (
    <Card>
      <CardContent className="flex items-center gap-4 p-6">
        <div className={`flex h-12 w-12 items-center justify-center rounded-lg ${accent}`}>
          <Icon className="h-6 w-6" />
        </div>
        <div>
          <p className="text-sm text-muted-foreground">{title}</p>
          <p className="text-2xl font-bold">{value}</p>
        </div>
      </CardContent>
    </Card>
  )
}

export function RevenueDashboardPage() {
  const [stats, setStats] = useState<RevenueStats | null>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    paymentService
      .revenue()
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
    return <p className="text-destructive">{error ?? 'Failed to load revenue.'}</p>
  }

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold">Revenue Dashboard</h1>
        <p className="text-muted-foreground">Payment performance across all events.</p>
      </div>

      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <StatCard
          title="Total Revenue"
          value={formatCurrency(stats.total_revenue)}
          icon={Banknote}
          accent="bg-emerald-100 text-emerald-600"
        />
        <StatCard
          title="Paid Transactions"
          value={String(stats.total_paid)}
          icon={CheckCircle2}
          accent="bg-indigo-100 text-indigo-600"
        />
        <StatCard
          title="Pending"
          value={String(stats.total_pending)}
          icon={Clock}
          accent="bg-amber-100 text-amber-600"
        />
        <StatCard
          title="Refunded"
          value={formatCurrency(stats.total_refunded)}
          icon={RotateCcw}
          accent="bg-rose-100 text-rose-600"
        />
      </div>

      <div className="grid gap-6 lg:grid-cols-2">
        {/* Monthly revenue */}
        <Card>
          <CardHeader>
            <CardTitle>Monthly Revenue</CardTitle>
          </CardHeader>
          <CardContent>
            {stats.monthly_revenue.length === 0 ? (
              <p className="py-12 text-center text-sm text-muted-foreground">No revenue yet.</p>
            ) : (
              <div className="h-72 w-full">
                <ResponsiveContainer width="100%" height="100%">
                  <BarChart data={stats.monthly_revenue} margin={{ top: 8, right: 8, bottom: 8, left: 0 }}>
                    <CartesianGrid strokeDasharray="3 3" className="stroke-muted" vertical={false} />
                    <XAxis dataKey="month" tickLine={false} axisLine={false} fontSize={12} />
                    <YAxis tickLine={false} axisLine={false} fontSize={12} />
                    <Tooltip
                      formatter={(v: number) => formatCurrency(v)}
                      contentStyle={{
                        borderRadius: 8,
                        border: '1px solid hsl(var(--border))',
                        background: 'hsl(var(--background))',
                      }}
                    />
                    <Bar dataKey="revenue" name="Revenue" fill="hsl(var(--primary))" radius={[4, 4, 0, 0]} />
                  </BarChart>
                </ResponsiveContainer>
              </div>
            )}
          </CardContent>
        </Card>

        {/* Revenue by gateway */}
        <Card>
          <CardHeader>
            <CardTitle>Revenue by Gateway</CardTitle>
          </CardHeader>
          <CardContent>
            {stats.by_gateway.length === 0 ? (
              <p className="py-12 text-center text-sm text-muted-foreground">No data.</p>
            ) : (
              <div className="flex flex-col items-center gap-4 sm:flex-row">
                <div className="h-56 w-full sm:w-1/2">
                  <ResponsiveContainer width="100%" height="100%">
                    <PieChart>
                      <Pie
                        data={stats.by_gateway}
                        dataKey="revenue"
                        nameKey="gateway"
                        innerRadius={45}
                        outerRadius={80}
                        paddingAngle={2}
                      >
                        {stats.by_gateway.map((g) => (
                          <Cell key={g.gateway} fill={GATEWAY_COLORS[g.gateway] ?? '#64748b'} />
                        ))}
                      </Pie>
                      <Tooltip formatter={(v: number) => formatCurrency(v)} />
                    </PieChart>
                  </ResponsiveContainer>
                </div>
                <div className="w-full space-y-2 sm:w-1/2">
                  {stats.by_gateway.map((g) => (
                    <div key={g.gateway} className="flex items-center justify-between text-sm">
                      <span className="flex items-center gap-2">
                        <span
                          className="h-3 w-3 rounded-full"
                          style={{ background: GATEWAY_COLORS[g.gateway] ?? '#64748b' }}
                        />
                        <span className="capitalize">{g.gateway}</span>
                      </span>
                      <span className="font-medium">{formatCurrency(g.revenue)}</span>
                    </div>
                  ))}
                </div>
              </div>
            )}
          </CardContent>
        </Card>
      </div>

      {/* Top events */}
      <Card>
        <CardHeader>
          <CardTitle>Top Events by Revenue</CardTitle>
        </CardHeader>
        <CardContent>
          {stats.by_event.length === 0 ? (
            <p className="py-8 text-center text-sm text-muted-foreground">No event revenue yet.</p>
          ) : (
            <div className="space-y-3">
              {stats.by_event.map((e) => (
                <div key={e.event} className="flex items-center justify-between">
                  <div>
                    <p className="text-sm font-medium">{e.event}</p>
                    <p className="text-xs text-muted-foreground">{e.transactions} transactions</p>
                  </div>
                  <span className="font-semibold">{formatCurrency(e.revenue)}</span>
                </div>
              ))}
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  )
}
