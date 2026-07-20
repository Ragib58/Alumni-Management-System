import { useEffect, useState } from 'react'
import { Banknote, Users, UserCheck } from 'lucide-react'
import { analyticsService } from '@/services/analytics.service'
import { extractApiError } from '@/lib/api'
import { useToast } from '@/components/ui/toast'
import { Spinner } from '@/components/ui/spinner'
import { Card, CardContent } from '@/components/ui/card'
import { Label } from '@/components/ui/label'
import { BarChartCard, LineChartCard } from '@/components/charts'
import { cn, formatCurrency } from '@/lib/utils'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import type { GrowthMetric, YearComparison } from '@/types'

function GrowthCard({
  title,
  metric,
  icon: Icon,
  format,
  yearA,
  yearB,
}: {
  title: string
  metric: GrowthMetric
  icon: React.ComponentType<{ className?: string }>
  format: (v: number) => string
  yearA: number
  yearB: number
}) {
  const positive = metric.growth >= 0
  return (
    <Card>
      <CardContent className="p-6">
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-2 text-sm text-muted-foreground">
            <Icon className="h-4 w-4" />
            {title}
          </div>
          <span
            className={cn(
              'rounded-full px-2 py-0.5 text-xs font-semibold',
              positive ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700',
            )}
          >
            {positive ? '▲' : '▼'} {Math.abs(metric.growth)}%
          </span>
        </div>
        <div className="mt-4 flex items-end justify-between">
          <div>
            <p className="text-xs text-muted-foreground">{yearA}</p>
            <p className="text-lg font-semibold">{format(metric.year_a)}</p>
          </div>
          <div className="text-right">
            <p className="text-xs text-muted-foreground">{yearB}</p>
            <p className="text-2xl font-bold">{format(metric.year_b)}</p>
          </div>
        </div>
      </CardContent>
    </Card>
  )
}

export function YearComparisonPage() {
  const { toast } = useToast()
  const [years, setYears] = useState<number[]>([])
  const current = new Date().getFullYear()
  const [yearA, setYearA] = useState<number>(current - 1)
  const [yearB, setYearB] = useState<number>(current)
  const [data, setData] = useState<YearComparison | null>(null)
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    setLoading(true)
    analyticsService
      .yearComparison(yearA, yearB)
      .then((res) => {
        setData(res.comparison)
        if (years.length === 0) setYears(res.available_years)
      })
      .catch((e) => toast({ title: 'Failed to load', description: extractApiError(e).message, variant: 'error' }))
      .finally(() => setLoading(false))
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [yearA, yearB])

  const yearOptions = years.length > 0 ? years : [current, current - 1]
  const asNumber = (v: number) => String(v)

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold">Year Comparison</h1>
          <p className="text-muted-foreground">Compare revenue, participation and attendance across years.</p>
        </div>
        <div className="flex items-end gap-3">
          <div className="space-y-1">
            <Label className="text-xs">Base year</Label>
            <Select value={asNumber(yearA)} onValueChange={(v) => setYearA(Number(v))}>
              <SelectTrigger className="w-28">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                {yearOptions.map((y) => (
                  <SelectItem key={y} value={asNumber(y)}>
                    {y}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>
          <span className="pb-2 text-muted-foreground">vs</span>
          <div className="space-y-1">
            <Label className="text-xs">Compare year</Label>
            <Select value={asNumber(yearB)} onValueChange={(v) => setYearB(Number(v))}>
              <SelectTrigger className="w-28">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                {yearOptions.map((y) => (
                  <SelectItem key={y} value={asNumber(y)}>
                    {y}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>
        </div>
      </div>

      {loading || !data ? (
        <div className="flex h-64 items-center justify-center">
          <Spinner className="h-8 w-8" />
        </div>
      ) : (
        <>
          <div className="grid gap-4 sm:grid-cols-3">
            <GrowthCard title="Revenue Growth" metric={data.revenue} icon={Banknote} format={formatCurrency} yearA={data.year_a} yearB={data.year_b} />
            <GrowthCard title="Participation Growth" metric={data.participation} icon={Users} format={(v) => v.toLocaleString()} yearA={data.year_a} yearB={data.year_b} />
            <GrowthCard title="Attendance Growth" metric={data.attendance} icon={UserCheck} format={(v) => v.toLocaleString()} yearA={data.year_a} yearB={data.year_b} />
          </div>

          <div className="grid gap-6 lg:grid-cols-2">
            <BarChartCard
              title={`Metrics: ${data.year_a} vs ${data.year_b}`}
              data={data.series}
              xKey="metric"
              series={[
                { key: 'year_a', name: String(data.year_a) },
                { key: 'year_b', name: String(data.year_b), color: '#10b981' },
              ]}
            />
            <LineChartCard
              title="Monthly Revenue Comparison"
              data={data.monthly}
              xKey="month"
              series={[
                { key: 'year_a', name: String(data.year_a) },
                { key: 'year_b', name: String(data.year_b), color: '#10b981' },
              ]}
              valueFormatter={formatCurrency}
            />
          </div>
        </>
      )}
    </div>
  )
}
