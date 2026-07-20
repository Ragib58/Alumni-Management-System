import {
  Area,
  AreaChart,
  CartesianGrid,
  Legend,
  ResponsiveContainer,
  Tooltip,
  XAxis,
  YAxis,
} from 'recharts'
import { ChartCard } from './ChartCard'
import type { ChartSeries } from './BarChartCard'
import { AXIS_PROPS, CHART_COLORS, GRID_CLASS, TOOLTIP_STYLE } from './chart-theme'

interface AreaChartCardProps {
  title: string
  data: Record<string, unknown>[]
  xKey: string
  series: ChartSeries[]
  height?: number
  valueFormatter?: (v: number) => string
  action?: React.ReactNode
}

export function AreaChartCard({
  title,
  data,
  xKey,
  series,
  height = 288,
  valueFormatter,
  action,
}: AreaChartCardProps) {
  return (
    <ChartCard title={title} height={height} empty={data.length === 0} action={action}>
      <ResponsiveContainer width="100%" height="100%">
        <AreaChart data={data} margin={{ top: 8, right: 8, bottom: 8, left: 0 }}>
          <defs>
            {series.map((s, i) => {
              const color = s.color ?? CHART_COLORS[i % CHART_COLORS.length]
              return (
                <linearGradient key={s.key} id={`grad-${s.key}`} x1="0" y1="0" x2="0" y2="1">
                  <stop offset="5%" stopColor={color} stopOpacity={0.3} />
                  <stop offset="95%" stopColor={color} stopOpacity={0} />
                </linearGradient>
              )
            })}
          </defs>
          <CartesianGrid strokeDasharray="3 3" className={GRID_CLASS} vertical={false} />
          <XAxis dataKey={xKey} {...AXIS_PROPS} />
          <YAxis {...AXIS_PROPS} allowDecimals={false} />
          <Tooltip
            contentStyle={TOOLTIP_STYLE}
            formatter={valueFormatter ? (v: number) => valueFormatter(v) : undefined}
          />
          {series.length > 1 && <Legend wrapperStyle={{ fontSize: 12 }} />}
          {series.map((s, i) => {
            const color = s.color ?? CHART_COLORS[i % CHART_COLORS.length]
            return (
              <Area
                key={s.key}
                type="monotone"
                dataKey={s.key}
                name={s.name}
                stroke={color}
                strokeWidth={2}
                fill={`url(#grad-${s.key})`}
              />
            )
          })}
        </AreaChart>
      </ResponsiveContainer>
    </ChartCard>
  )
}
