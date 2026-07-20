import { useCallback, useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { CalendarDays, Download, Mail, MapPin, Ticket as TicketIcon, CheckCircle2 } from 'lucide-react'
import { ticketService } from '@/services/ticket.service'
import { extractApiError } from '@/lib/api'
import { useToast } from '@/components/ui/toast'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent } from '@/components/ui/card'
import { Spinner } from '@/components/ui/spinner'
import { Pagination } from '@/components/common/Pagination'
import { formatDateTime } from '@/lib/utils'
import type { PaginationMeta, Ticket } from '@/types'

export function MyTicketsPage() {
  const { toast } = useToast()
  const [rows, setRows] = useState<Ticket[]>([])
  const [meta, setMeta] = useState<PaginationMeta | null>(null)
  const [loading, setLoading] = useState(true)
  const [page, setPage] = useState(1)
  const [busy, setBusy] = useState<number | null>(null)

  const fetchTickets = useCallback(async () => {
    setLoading(true)
    try {
      const res = await ticketService.myTickets(page, 10)
      setRows(res.data)
      setMeta(res.meta)
    } catch (e) {
      toast({ title: 'Failed to load tickets', description: extractApiError(e).message, variant: 'error' })
    } finally {
      setLoading(false)
    }
  }, [page, toast])

  useEffect(() => {
    void fetchTickets()
  }, [fetchTickets])

  const download = async (ticket: Ticket) => {
    setBusy(ticket.id)
    try {
      await ticketService.download(ticket.id, ticket.ticket_no)
    } catch (e) {
      toast({ title: 'Download failed', description: extractApiError(e).message, variant: 'error' })
    } finally {
      setBusy(null)
    }
  }

  const email = async (ticket: Ticket) => {
    try {
      await ticketService.email(ticket.id)
      toast({ title: 'Ticket emailed', description: 'Check your inbox shortly.', variant: 'success' })
    } catch (e) {
      toast({ title: 'Email failed', description: extractApiError(e).message, variant: 'error' })
    }
  }

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold">My Tickets</h1>
        <p className="text-muted-foreground">Download or email your event tickets.</p>
      </div>

      {loading ? (
        <div className="flex h-64 items-center justify-center">
          <Spinner className="h-8 w-8" />
        </div>
      ) : rows.length === 0 ? (
        <Card>
          <CardContent className="flex flex-col items-center gap-3 py-16 text-center">
            <TicketIcon className="h-10 w-10 text-muted-foreground" />
            <p className="text-sm text-muted-foreground">
              You don&apos;t have any tickets yet. Tickets are issued once your payment is confirmed.
            </p>
            <Link to="/events">
              <Button>Browse events</Button>
            </Link>
          </CardContent>
        </Card>
      ) : (
        <div className="grid gap-4 md:grid-cols-2">
          {rows.map((ticket) => (
            <Card key={ticket.id} className="overflow-hidden">
              <CardContent className="p-0">
                <div className="flex">
                  {/* QR panel */}
                  <div className="flex w-32 shrink-0 flex-col items-center justify-center gap-2 border-r bg-muted/40 p-4">
                    {ticket.qr_token && (
                      <div className="rounded bg-white p-1">
                        {/* Lightweight visual QR placeholder; the real scannable QR is in the PDF. */}
                        <div
                          className="h-20 w-20 bg-[length:6px_6px] bg-center"
                          style={{
                            backgroundImage:
                              'repeating-linear-gradient(45deg,#0f172a 0 3px,transparent 0 6px)',
                          }}
                          aria-hidden
                        />
                      </div>
                    )}
                    <span className="text-center text-[10px] text-muted-foreground">
                      Scan from PDF at entry
                    </span>
                  </div>

                  {/* Details */}
                  <div className="flex-1 space-y-2 p-4">
                    <div className="flex items-start justify-between gap-2">
                      <h3 className="font-semibold leading-tight">
                        {ticket.registration?.event?.title ?? 'Event'}
                      </h3>
                      {ticket.is_checked_in && (
                        <Badge variant="success" className="gap-1">
                          <CheckCircle2 className="h-3 w-3" />
                          Checked in
                        </Badge>
                      )}
                    </div>
                    <p className="font-mono text-xs text-muted-foreground">{ticket.ticket_no}</p>
                    <p className="flex items-center gap-2 text-sm text-muted-foreground">
                      <CalendarDays className="h-3.5 w-3.5" />
                      {formatDateTime(ticket.registration?.event?.event_date)}
                    </p>
                    {ticket.registration?.event?.venue && (
                      <p className="flex items-center gap-2 text-sm text-muted-foreground">
                        <MapPin className="h-3.5 w-3.5" />
                        {ticket.registration.event.venue}
                      </p>
                    )}

                    <div className="flex gap-2 pt-2">
                      <Button size="sm" onClick={() => download(ticket)} disabled={busy === ticket.id}>
                        {busy === ticket.id ? (
                          <Spinner className="h-4 w-4 text-primary-foreground" />
                        ) : (
                          <Download className="h-4 w-4" />
                        )}
                        Download
                      </Button>
                      <Button size="sm" variant="outline" onClick={() => email(ticket)}>
                        <Mail className="h-4 w-4" />
                        Email
                      </Button>
                    </div>
                  </div>
                </div>
              </CardContent>
            </Card>
          ))}
        </div>
      )}

      {meta && <Pagination meta={meta} onPageChange={setPage} />}
    </div>
  )
}
