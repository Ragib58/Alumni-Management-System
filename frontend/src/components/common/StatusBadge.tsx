import { Badge } from '@/components/ui/badge'
import type { UserStatus } from '@/types'

const map: Record<UserStatus, { label: string; variant: 'success' | 'secondary' | 'destructive' }> = {
  active: { label: 'Active', variant: 'success' },
  inactive: { label: 'Inactive', variant: 'secondary' },
  suspended: { label: 'Suspended', variant: 'destructive' },
}

export function StatusBadge({ status }: { status: UserStatus }) {
  const cfg = map[status] ?? map.inactive
  return <Badge variant={cfg.variant}>{cfg.label}</Badge>
}
