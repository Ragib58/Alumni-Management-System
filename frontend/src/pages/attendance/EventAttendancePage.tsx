import { useCallback, useEffect, useState } from 'react'
import { Link, useParams } from 'react-router-dom'
import { ArrowLeft, Search, UserCheck, UserMinus, Users, LogOut, CheckCircle2 } from 'lucide-react'
import { attendanceService } from '@/services/attendance.service'
import { eventService } from '@/services/event.service'
import { extractApiError } from '@/lib/api'
import { useToast } from '@/components/ui/toast'
import { useDebounce } from '@/hooks/useDebounce'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent } from '@/components/ui/card'
import { Spinner } from '@/components/ui/spinner'
import { StatCard } from '@/components/charts'
import { Pagination } from '@/components/common/Pagination'
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
import type {
  Attendance,
  AttendanceStats,
  AttendanceStatus,
  EventItem,
  PaginationMeta,
} from '@/types'

const STATUS_BADGE: Record<AttendanceStatus, { label: string; variant: 'success' | 'secondary' | 'default' }> = {
  not_arrived: { label: 'Not Arrived', variant: 'secondary' },
  checked_in: { label: 'Checked In', variant: 'success' },
  checked_out: { label: 'Checked Out', variant: 'default' },
}

export function EventAttendancePage() {
  const { id } = useParams()
  const eventId = Number(id)
  const { toast } = useToast()

  const [event, setEvent] = useState<EventItem | null>(null)
  const [rows, setRows] = useState<Attendance[]>([])
  const [meta, setMeta] = useState<PaginationMeta | null>(null)
  const [stats, setStats] = useState<AttendanceStats | null>(null)
  const [loading, setLoading] = useState(true)

  const [search, setSearch] = useState('')
  const debouncedSearch = useDebounce(search)
  const [status, setStatus] = useState<AttendanceStatus | 'all'>('all')
  const [page, setPage] = useState(1)

  useEffect(() => {
    eventService.getById(eventId).then(setEvent).catch(() => void 0)
  }, [eventId])

  const fetchAttendance = useCallback(async () => {
    setLoading(true)
    try {
      const res = await attendanceService.listForEvent(eventId, {
        search: debouncedSearch || undefined,
        status: status === 'all' ? undefined : status,
        page,
        per_page: 20,
      })
      setRows(res.data)
      setMeta(res.meta)
      setStats(res.stats)
    } catch (e) {
      toast({ title: 'Failed to load attendance', description: extractApiError(e).message, variant: 'error' })
    } finally {
      setLoading(false)
    }
  }, [eventId, debouncedSearch, status, page, toast])

  useEffect(() => {
    void fetchAttendance()
  }, [fetchAttendance])

  useEffect(() => {
    setPage(1)
  }, [debouncedSearch, status])

  const checkIn = async (registrationId: number) => {
    try {
      await attendanceService.checkInByRegistration(registrationId, eventId)
      toast({ title: 'Checked in', variant: 'success' })
      void fetchAttendance()
    } catch (e) {
      toast({ title: 'Check-in failed', description: extractApiError(e).message, variant: 'error' })
    }
  }

  const checkOut = async (registrationId: number) => {
    try {
      await attendanceService.checkOut(registrationId)
      toast({ title: 'Checked out', variant: 'success' })
      void fetchAttendance()
    } catch (e) {
      toast({ title: 'Check-out failed', description: extractApiError(e).message, variant: 'error' })
    }
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center gap-3">
        <Link to="/admin/events">
          <Button variant="ghost" size="icon">
            <ArrowLeft className="h-4 w-4" />
          </Button>
        </Link>
        <div>
          <h1 className="text-2xl font-bold">Attendance</h1>
          <p className="text-muted-foreground">{event?.title ?? 'Event'}</p>
        </div>
        <Link to="/admin/attendance/scan" className="ml-auto">
          <Button>
            <UserCheck className="h-4 w-4" />
            Open scanner
          </Button>
        </Link>
      </div>

      {/* Stats */}
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <StatCard title="Total" value={stats?.total ?? 0} icon={Users} accent="bg-indigo-100 text-indigo-600" />
        <StatCard title="Checked In" value={stats?.checked_in ?? 0} icon={CheckCircle2} accent="bg-emerald-100 text-emerald-600" />
        <StatCard title="Checked Out" value={stats?.checked_out ?? 0} icon={LogOut} accent="bg-blue-100 text-blue-600" />
        <StatCard title="Not Arrived" value={stats?.not_arrived ?? 0} icon={UserMinus} accent="bg-amber-100 text-amber-600" />
      </div>

      {/* Filters */}
      <div className="flex flex-col gap-3 sm:flex-row">
        <div className="relative flex-1">
          <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
          <Input
            placeholder="Search attendee…"
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            className="pl-9"
          />
        </div>
        <Select value={status} onValueChange={(v) => setStatus(v as AttendanceStatus | 'all')}>
          <SelectTrigger className="w-full sm:w-44">
            <SelectValue placeholder="Status" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">All statuses</SelectItem>
            <SelectItem value="not_arrived">Not Arrived</SelectItem>
            <SelectItem value="checked_in">Checked In</SelectItem>
            <SelectItem value="checked_out">Checked Out</SelectItem>
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
            <p className="py-16 text-center text-sm text-muted-foreground">
              No attendance records yet.
            </p>
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Attendee</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead>Check-in</TableHead>
                  <TableHead>Check-out</TableHead>
                  <TableHead>By</TableHead>
                  <TableHead className="text-right">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {rows.map((a) => {
                  const badge = STATUS_BADGE[a.status]
                  return (
                    <TableRow key={a.id}>
                      <TableCell>
                        <p className="font-medium">{a.registration?.user?.name}</p>
                        <p className="font-mono text-xs text-muted-foreground">
                          {a.registration?.registration_no}
                        </p>
                      </TableCell>
                      <TableCell>
                        <Badge variant={badge.variant}>{badge.label}</Badge>
                      </TableCell>
                      <TableCell className="text-muted-foreground">
                        {formatDateTime(a.checkin_time)}
                      </TableCell>
                      <TableCell className="text-muted-foreground">
                        {formatDateTime(a.checkout_time)}
                      </TableCell>
                      <TableCell className="text-muted-foreground">
                        {a.checked_by?.name ?? '—'}
                      </TableCell>
                      <TableCell className="text-right">
                        {a.status === 'not_arrived' && (
                          <Button size="sm" onClick={() => checkIn(a.registration_id)}>
                            <UserCheck className="h-4 w-4" />
                            Check in
                          </Button>
                        )}
                        {a.status === 'checked_in' && (
                          <Button size="sm" variant="outline" onClick={() => checkOut(a.registration_id)}>
                            <LogOut className="h-4 w-4" />
                            Check out
                          </Button>
                        )}
                      </TableCell>
                    </TableRow>
                  )
                })}
              </TableBody>
            </Table>
          )}
        </CardContent>
      </Card>

      {meta && <Pagination meta={meta} onPageChange={setPage} />}
    </div>
  )
}
