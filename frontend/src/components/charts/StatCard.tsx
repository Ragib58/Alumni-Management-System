import { TrendingDown, TrendingUp } from 'lucide-react'
import { Card, CardContent } from '@/components/ui/card'
import { cn } from '@/lib/utils'

interface StatCardProps {
  title: string
  value: string | number
  icon: React.ComponentType<{ className?: string }>
  accent?: string
  /** Optional growth % badge (e.g. year-over-year). */
  delta?: number | null
}

export function StatCard({ title, value, icon: Icon, accent = 'bg-primary/10 text-primary', delta }: StatCardProps) {
  return (
    <Card>
      <CardContent className="flex items-center gap-4 p-6">
        <div className={cn('flex h-12 w-12 items-center justify-center rounded-lg', accent)}>
          <Icon className="h-6 w-6" />
        </div>
        <div className="min-w-0">
          <p className="truncate text-sm text-muted-foreground">{title}</p>
          <p className="text-2xl font-bold">{typeof value === 'number' ? value.toLocaleString() : value}</p>
          {delta !== undefined && delta !== null && (
            <p
              className={cn(
                'mt-0.5 flex items-center gap-1 text-xs font-medium',
                delta >= 0 ? 'text-emerald-600' : 'text-destructive',
              )}
            >
              {delta >= 0 ? <TrendingUp className="h-3 w-3" /> : <TrendingDown className="h-3 w-3" />}
              {delta >= 0 ? '+' : ''}
              {delta}%
            </p>
          )}
        </div>
      </CardContent>
    </Card>
  )
}
