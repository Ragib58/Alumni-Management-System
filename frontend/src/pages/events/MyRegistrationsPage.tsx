import { useCallback, useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { CalendarDays, CreditCard, Ticket, X } from 'lucide-react'
import { registrationService } from '@/services/registration.service'
import { extractApiError } from '@/lib/api'
import { useToast } from '@/components/ui/toast'
import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'
import { Spinner } from '@/components/ui/spinner'
import { Pagination } from '@/components/common/Pagination'
import {
  PaymentStatusBadge,
  RegistrationStatusBadge,
} from '@/components/common/EventStatusBadge'
import { formatCurrency, formatDateTime } from '@/lib/utils'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import type { EventRegistration, PaginationMeta } from '@/types'

export function MyRegistrationsPage() {
  const { toast } = useToast()
  const [rows, setRows] = useState<EventRegistration[]>([])
  const [meta, setMeta] = useState<PaginationMeta | null>(null)
  const [loading, setLoading] = useState(true)
  const [page, setPage] = useState(1)

  const [cancelling, setCancelling] = useState<EventRegistration | null>(null)
  const [cancelPending, setCancelPending] = useState(false)

  const fetchRows = useCallback(async () => {
    setLoading(true)
    try {
      const res = await registrationService.myRegistrations({ page, per_page: 10 })
      setRows(res.data)
      setMeta(res.meta)
    } catch (e) {
      toast({ title: 'Failed to load', description: extractApiError(e).message, variant: 'error' })
    } finally {
      setLoading(false)
    }
  }, [page, toast])

  useEffect(() => {
    void fetchRows()
  }, [fetchRows])

  const handleCancel = async () => {
    if (!cancelling) return
    setCancelPending(true)
    try {
      await registrationService.cancel(cancelling.id)
      toast({ title: 'Registration cancelled', variant: 'success' })
      setCancelling(null)
      void fetchRows()
    } catch (e) {
      toast({ title: 'Cancel failed', description: extractApiError(e).message, variant: 'error' })
    } finally {
      setCancelPending(false)
    }
  }

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold">My Registrations</h1>
        <p className="text-muted-foreground">Track the events you&apos;ve registered for.</p>
      </div>

      {loading ? (
        <div className="flex h-64 items-center justify-center">
          <Spinner className="h-8 w-8" />
        </div>
      ) : rows.length === 0 ? (
        <Card>
          <CardContent className="flex flex-col items-center gap-3 py-16 text-center">
            <Ticket className="h-10 w-10 text-muted-foreground" />
            <p className="text-sm text-muted-foreground">You haven&apos;t registered for any events yet.</p>
            <Link to="/events">
              <Button>Browse events</Button>
            </Link>
          </CardContent>
        </Card>
      ) : (
        <div className="space-y-3">
          {rows.map((reg) => (
            <Card key={reg.id}>
              <CardContent className="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between">
                <div className="space-y-1">
                  <div className="flex items-center gap-2">
                    {reg.event ? (
                      <Link
                        to={`/events/${reg.event.slug}`}
                        className="font-semibold hover:text-primary"
                      >
                        {reg.event.title}
                      </Link>
                    ) : (
                      <span className="font-semibold">Event</span>
                    )}
                  </div>
                  <p className="flex items-center gap-2 text-sm text-muted-foreground">
                    <CalendarDays className="h-3.5 w-3.5" />
                    {formatDateTime(reg.event?.event_date)}
                  </p>
                  <p className="font-mono text-xs text-muted-foreground">{reg.registration_no}</p>
                </div>

                <div className="flex flex-wrap items-center gap-2">
                  <RegistrationStatusBadge status={reg.status} />
                  <PaymentStatusBadge status={reg.payment_status} />
                  <span className="text-sm font-medium">{formatCurrency(reg.amount)}</span>
                  {reg.status !== 'cancelled' && reg.payment_status === 'pending' && reg.amount > 0 && (
                    <Link to={`/registrations/${reg.id}/pay`}>
                      <Button size="sm">
                        <CreditCard className="h-4 w-4" />
                        Pay now
                      </Button>
                    </Link>
                  )}
                  {reg.status !== 'cancelled' && (
                    <Button
                      variant="ghost"
                      size="sm"
                      className="text-destructive hover:text-destructive"
                      onClick={() => setCancelling(reg)}
                    >
                      <X className="h-4 w-4" />
                      Cancel
                    </Button>
                  )}
                </div>
              </CardContent>
            </Card>
          ))}
        </div>
      )}

      {meta && <Pagination meta={meta} onPageChange={setPage} />}

      <Dialog open={!!cancelling} onOpenChange={(o) => !o && setCancelling(null)}>
        <DialogContent className="max-w-sm">
          <DialogHeader>
            <DialogTitle>Cancel registration</DialogTitle>
            <DialogDescription>
              Cancel your registration for{' '}
              <span className="font-medium text-foreground">{cancelling?.event?.title}</span>?
            </DialogDescription>
          </DialogHeader>
          <DialogFooter>
            <Button variant="outline" onClick={() => setCancelling(null)}>
              Keep it
            </Button>
            <Button variant="destructive" onClick={handleCancel} disabled={cancelPending}>
              {cancelPending && <Spinner className="h-4 w-4 text-destructive-foreground" />}
              Cancel registration
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  )
}
