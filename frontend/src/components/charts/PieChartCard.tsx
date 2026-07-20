import { Cell, Pie, PieChart, ResponsiveContainer, Tooltip } from 'recharts'
import { ChartCard } from './ChartCard'
import { CHART_COLORS, TOOLTIP_STYLE } from './chart-theme'

interface PieChartCardProps {
  title: string
  data: Record<string, unknown>[]
  nameKey: string
  valueKey: string
  height?: number
  valueFormatter?: (v: number) => string
  action?: React.ReactNode
}

export function PieChartCard({
  title,
  data,
  nameKey,
  valueKey,
  height = 288,
  valueFormatter,
  action,
}: PieChartCardProps) {
  return (
    <ChartCard title={title} height={height} empty={data.length === 0} action={action}>
      <div className="flex h-full flex-col items-center gap-4 sm:flex-row">
        <div className="h-full min-h-[180px] w-full sm:w-1/2">
          <ResponsiveContainer width="100%" height="100%">
            <PieChart>
              <Pie
                data={data}
                dataKey={valueKey}
                nameKey={nameKey}
                innerRadius="55%"
                outerRadius="85%"
                paddingAngle={2}
              >
                {data.map((_, i) => (
                  <Cell key={i} fill={CHART_COLORS[i % CHART_COLORS.length]} />
                ))}
              </Pie>
              <Tooltip
                contentStyle={TOOLTIP_STYLE}
                formatter={valueFormatter ? (v: number) => valueFormatter(v) : undefined}
              />
            </PieChart>
          </ResponsiveContainer>
        </div>
        <div className="w-full space-y-2 sm:w-1/2">
          {data.map((d, i) => (
            <div key={i} className="flex items-center justify-between text-sm">
              <span className="flex items-center gap-2">
                <span
                  className="h-3 w-3 rounded-full"
                  style={{ background: CHART_COLORS[i % CHART_COLORS.length] }}
                />
                <span className="capitalize">{String(d[nameKey])}</span>
              </span>
              <span className="font-medium">
                {valueFormatter ? valueFormatter(Number(d[valueKey])) : String(d[valueKey])}
              </span>
            </div>
          ))}
        </div>
      </div>
    </ChartCard>
  )
}
