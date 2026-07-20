import {
  Bar,
  BarChart,
  CartesianGrid,
  Legend,
  ResponsiveContainer,
  Tooltip,
  XAxis,
  YAxis,
} from 'recharts'
import { ChartCard } from './ChartCard'
import { AXIS_PROPS, CHART_COLORS, GRID_CLASS, TOOLTIP_STYLE } from './chart-theme'

export interface ChartSeries {
  key: string
  name: string
  color?: string
}

interface BarChartCardProps {
  title: string
  data: Record<string, unknown>[]
  xKey: string
  series: ChartSeries[]
  height?: number
  valueFormatter?: (v: number) => string
  action?: React.ReactNode
  stacked?: boolean
}

export function BarChartCard({
  title,
  data,
  xKey,
  series,
  height = 288,
  valueFormatter,
  action,
  stacked = false,
}: BarChartCardProps) {
  return (
    <ChartCard title={title} height={height} empty={data.length === 0} action={action}>
      <ResponsiveContainer width="100%" height="100%">
        <BarChart data={data} margin={{ top: 8, right: 8, bottom: 8, left: 0 }}>
          <CartesianGrid strokeDasharray="3 3" className={GRID_CLASS} vertical={false} />
          <XAxis dataKey={xKey} {...AXIS_PROPS} />
          <YAxis {...AXIS_PROPS} allowDecimals={false} />
          <Tooltip
            cursor={{ fill: 'hsl(var(--muted))' }}
            contentStyle={TOOLTIP_STYLE}
            formatter={valueFormatter ? (v: number) => valueFormatter(v) : undefined}
          />
          {series.length > 1 && <Legend wrapperStyle={{ fontSize: 12 }} />}
          {series.map((s, i) => (
            <Bar
              key={s.key}
              dataKey={s.key}
              name={s.name}
              fill={s.color ?? CHART_COLORS[i % CHART_COLORS.length]}
              radius={stacked ? 0 : [4, 4, 0, 0]}
              stackId={stacked ? 'stack' : undefined}
            />
          ))}
        </BarChart>
      </ResponsiveContainer>
    </ChartCard>
  )
}
