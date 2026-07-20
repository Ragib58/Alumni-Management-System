import {
  CartesianGrid,
  Legend,
  Line,
  LineChart,
  ResponsiveContainer,
  Tooltip,
  XAxis,
  YAxis,
} from 'recharts'
import { ChartCard } from './ChartCard'
import type { ChartSeries } from './BarChartCard'
import { AXIS_PROPS, CHART_COLORS, GRID_CLASS, TOOLTIP_STYLE } from './chart-theme'

interface LineChartCardProps {
  title: string
  data: Record<string, unknown>[]
  xKey: string
  series: ChartSeries[]
  height?: number
  valueFormatter?: (v: number) => string
  action?: React.ReactNode
}

export function LineChartCard({
  title,
  data,
  xKey,
  series,
  height = 288,
  valueFormatter,
  action,
}: LineChartCardProps) {
  return (
    <ChartCard title={title} height={height} empty={data.length === 0} action={action}>
      <ResponsiveContainer width="100%" height="100%">
        <LineChart data={data} margin={{ top: 8, right: 8, bottom: 8, left: 0 }}>
          <CartesianGrid strokeDasharray="3 3" className={GRID_CLASS} vertical={false} />
          <XAxis dataKey={xKey} {...AXIS_PROPS} />
          <YAxis {...AXIS_PROPS} allowDecimals={false} />
          <Tooltip
            contentStyle={TOOLTIP_STYLE}
            formatter={valueFormatter ? (v: number) => valueFormatter(v) : undefined}
          />
          {series.length > 1 && <Legend wrapperStyle={{ fontSize: 12 }} />}
          {series.map((s, i) => (
            <Line
              key={s.key}
              type="monotone"
              dataKey={s.key}
              name={s.name}
              stroke={s.color ?? CHART_COLORS[i % CHART_COLORS.length]}
              strokeWidth={2}
              dot={{ r: 3 }}
              activeDot={{ r: 5 }}
            />
          ))}
        </LineChart>
      </ResponsiveContainer>
    </ChartCard>
  )
}
