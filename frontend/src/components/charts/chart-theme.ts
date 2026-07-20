/**
 * Shared chart palette + tooltip styling so every chart in the app reads as one
 * consistent system (light/dark aware via CSS variables where possible).
 */
export const CHART_COLORS = [
  'hsl(243 75% 59%)', // primary indigo
  '#10b981', // emerald
  '#f59e0b', // amber
  '#ec4899', // pink
  '#3b82f6', // blue
  '#8b5cf6', // violet
  '#ef4444', // red
  '#14b8a6', // teal
]

export const AXIS_PROPS = {
  tickLine: false,
  axisLine: false,
  fontSize: 12,
} as const

export const TOOLTIP_STYLE = {
  borderRadius: 8,
  border: '1px solid hsl(var(--border))',
  background: 'hsl(var(--background))',
  fontSize: 12,
} as const

export const GRID_CLASS = 'stroke-muted'
