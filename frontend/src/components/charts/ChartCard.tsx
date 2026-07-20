import type { ReactNode } from 'react'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'

interface ChartCardProps {
  title: string
  action?: ReactNode
  /** Chart body height in px (defaults to 288 / h-72). */
  height?: number
  empty?: boolean
  emptyText?: string
  children: ReactNode
}

/**
 * Consistent card shell for every chart: title row, fixed-height body and a
 * shared empty state.
 */
export function ChartCard({
  title,
  action,
  height = 288,
  empty = false,
  emptyText = 'No data yet.',
  children,
}: ChartCardProps) {
  return (
    <Card>
      <CardHeader className="flex-row items-center justify-between space-y-0">
        <CardTitle className="text-base">{title}</CardTitle>
        {action}
      </CardHeader>
      <CardContent>
        {empty ? (
          <div className="flex items-center justify-center text-sm text-muted-foreground" style={{ height }}>
            {emptyText}
          </div>
        ) : (
          <div style={{ height }} className="w-full">
            {children}
          </div>
        )}
      </CardContent>
    </Card>
  )
}
