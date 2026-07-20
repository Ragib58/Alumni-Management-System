import { Badge } from '@/components/ui/badge'
import type { EventStatus, PaymentStatus, RegistrationStatus } from '@/types'

const eventMap: Record<EventStatus, { label: string; variant: 'default' | 'success' | 'secondary' | 'warning' }> = {
  draft: { label: 'Draft', variant: 'secondary' },
  published: { label: 'Published', variant: 'success' },
  closed: { label: 'Closed', variant: 'warning' },
  completed: { label: 'Completed', variant: 'default' },
}

const regMap: Record<RegistrationStatus, { label: string; variant: 'success' | 'warning' | 'destructive' }> = {
  pending: { label: 'Pending', variant: 'warning' },
  confirmed: { label: 'Confirmed', variant: 'success' },
  cancelled: { label: 'Cancelled', variant: 'destructive' },
}

const payMap: Record<PaymentStatus, { label: string; variant: 'success' | 'warning' | 'destructive' | 'secondary' }> = {
  pending: { label: 'Payment Pending', variant: 'warning' },
  paid: { label: 'Paid', variant: 'success' },
  failed: { label: 'Failed', variant: 'destructive' },
  refunded: { label: 'Refunded', variant: 'secondary' },
  free: { label: 'Free', variant: 'secondary' },
}

export function EventStatusBadge({ status }: { status: EventStatus }) {
  const cfg = eventMap[status] ?? eventMap.draft
  return <Badge variant={cfg.variant}>{cfg.label}</Badge>
}

export function RegistrationStatusBadge({ status }: { status: RegistrationStatus }) {
  const cfg = regMap[status] ?? regMap.pending
  return <Badge variant={cfg.variant}>{cfg.label}</Badge>
}

export function PaymentStatusBadge({ status }: { status: PaymentStatus }) {
  const cfg = payMap[status] ?? payMap.pending
  return <Badge variant={cfg.variant}>{cfg.label}</Badge>
}
