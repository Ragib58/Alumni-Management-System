import { useCallback, useEffect, useState } from 'react'
import { useParams, Link } from 'react-router-dom'
import { ArrowLeft, Search, MoreHorizontal } from 'lucide-react'
import { registrationService } from '@/services/registration.service'
import { eventService } from '@/services/event.service'
import { extractApiError } from '@/lib/api'
import { useToast } from '@/components/ui/toast'
import { useDebounce } from '@/hooks/useDebounce'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Card, CardContent } from '@/components/ui/card'
import { Spinner } from '@/components/ui/spinner'
import { Pagination } from '@/components/common/Pagination'
import {
  RegistrationStatusBadge,
  PaymentStatusBadge,
} from '@/components/common/EventStatusBadge'
import { formatDateTime } from '@/lib/utils'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import type {
  EventItem,
  EventRegistration,
  PaginationMeta,
  PaymentStatus,
  RegistrationFilters,
  RegistrationStatus,
} from '@/types'

export function RegistrationListPage() {
  const { id } = useParams()
  const eventId = id ? Number(id) : undefined
  const { toast } = useToast()

  const [event, setEvent] = useState<EventItem | null>(null)
  const [rows, setRows] = useState<EventRegistration[]>([])
  const [meta, setMeta] = useState<PaginationMeta | null>(null)
  const [loading, setLoading] = useState(true)

  const [search, setSearch] = useState('')
  const debouncedSearch = useDebounce(search)
  const [status, setStatus] = useState<RegistrationStatus | 'all'>('all')
  const [page, setPage] = useState(1)

  useEffect(() => {
    if (eventId) {
      eventService.getById(eventId).then(setEvent).catch(() => void 0)
    }
  }, [eventId])

  const fetchRegistrations = useCallback(async () => {
    setLoading(true)
    try {
      const filters: RegistrationFilters = {
        search: debouncedSearch || undefined,
        status: status === 'all' ? undefined : status,
        event_id: eventId,
        page,
        per_page: 15,
      }
      const res = await registrationService.adminList(filters)
      setRows(res.data)
      setMeta(res.meta)
    } catch (e) {
      toast({ title: 'Failed to load registrations', description: extractApiError(e).message, variant: 'error' })
    } finally {
      setLoading(false)
    }
  }, [debouncedSearch, status, eventId, page, toast])

  useEffect(() => {
    void fetchRegistrations()
  }, [fetchRegistrations])

  useEffect(() => {
    setPage(1)
  }, [debouncedSearch, status])

  const changeStatus = async (
    reg: EventRegistration,
    next: RegistrationStatus,
    payment?: PaymentStatus,
  ) => {
    try {
      await registrationService.updateStatus(reg.id, next, payment)
      toast({ title: 'Registration updated', variant: 'success' })
      void fetchRegistrations()
    } catch (e) {
      toast({ title: 'Update failed', description: extractApiError(e).message, variant: 'error' })
    }
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center gap-3">
        {eventId && (
          <Link to="/admin/events">
            <Button variant="ghost" size="icon">
              <ArrowLeft className="h-4 w-4" />
            </Button>
          </Link>
        )}
        <div>
          <h1 className="text-2xl font-bold">Registrations</h1>
          <p className="text-muted-foreground">
            {event ? event.title : 'All events'}
          </p>
        </div>
      </div>

      <div className="flex flex-col gap-3 sm:flex-row">
        <div className="relative flex-1">
          <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
          <Input
            placeholder="Search by name, email or registration no…"
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            className="pl-9"
          />
        </div>
        <Select value={status} onValueChange={(v) => setStatus(v as RegistrationStatus | 'all')}>
          <SelectTrigger className="w-full sm:w-44">
            <SelectValue placeholder="Status" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">All statuses</SelectItem>
            <SelectItem value="pending">Pending</SelectItem>
            <SelectItem value="confirmed">Confirmed</SelectItem>
            <SelectItem value="cancelled">Cancelled</SelectItem>
          </SelectContent>
        </Select>
      </div>

      <Card>
        <CardContent className="p-0">
          {loading ? (
            <div className="flex h-64 items-center justify-center">
              <Spinner className="h-8 w-8" />
            </div>
          ) : rows.length === 0 ? (
            <p className="py-16 text-center text-sm text-muted-foreground">No registrations found.</p>
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Reg. No</TableHead>
                  <TableHead>Attendee</TableHead>
                  {!eventId && <TableHead>Event</TableHead>}
                  <TableHead>Status</TableHead>
                  <TableHead>Payment</TableHead>
                  <TableHead>Registered</TableHead>
                  <TableHead className="text-right">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {rows.map((reg) => (
                  <TableRow key={reg.id}>
                    <TableCell className="font-mono text-xs">{reg.registration_no}</TableCell>
                    <TableCell>
                      <p className="font-medium">{reg.user?.name}</p>
                      <p className="text-xs text-muted-foreground">{reg.user?.email}</p>
                    </TableCell>
                    {!eventId && (
                      <TableCell className="text-muted-foreground">{reg.event?.title}</TableCell>
                    )}
                    <TableCell>
                      <RegistrationStatusBadge status={reg.status} />
                    </TableCell>
                    <TableCell>
                      <PaymentStatusBadge status={reg.payment_status} />
                    </TableCell>
                    <TableCell className="text-muted-foreground">
                      {formatDateTime(reg.registered_at)}
                    </TableCell>
                    <TableCell className="text-right">
                      <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                          <Button variant="ghost" size="icon">
                            <MoreHorizontal className="h-4 w-4" />
                          </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                          <DropdownMenuLabel>Set status</DropdownMenuLabel>
                          <DropdownMenuItem onClick={() => changeStatus(reg, 'confirmed', 'paid')}>
                            Confirm (mark paid)
                          </DropdownMenuItem>
                          <DropdownMenuItem onClick={() => changeStatus(reg, 'pending')}>
                            Set Pending
                          </DropdownMenuItem>
                          <DropdownMenuItem
                            className="text-destructive focus:text-destructive"
                            onClick={() => changeStatus(reg, 'cancelled')}
                          >
                            Cancel registration
                          </DropdownMenuItem>
                          <DropdownMenuSeparator />
                          <DropdownMenuLabel>Payment</DropdownMenuLabel>
                          <DropdownMenuItem onClick={() => changeStatus(reg, reg.status, 'paid')}>
                            Mark paid
                          </DropdownMenuItem>
                          <DropdownMenuItem onClick={() => changeStatus(reg, reg.status, 'pending')}>
                            Mark payment pending
                          </DropdownMenuItem>
                        </DropdownMenuContent>
                      </DropdownMenu>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          )}
        </CardContent>
      </Card>

      {meta && <Pagination meta={meta} onPageChange={setPage} />}
    </div>
  )
}
