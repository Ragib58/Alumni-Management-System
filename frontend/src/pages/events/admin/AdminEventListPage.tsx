import { useCallback, useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { Plus, Search, Pencil, Trash2, Users, MoreHorizontal, Eye, ScanLine } from 'lucide-react'
import { eventService } from '@/services/event.service'
import { extractApiError } from '@/lib/api'
import { useToast } from '@/components/ui/toast'
import { useDebounce } from '@/hooks/useDebounce'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Card, CardContent } from '@/components/ui/card'
import { Spinner } from '@/components/ui/spinner'
import { Pagination } from '@/components/common/Pagination'
import { EventStatusBadge } from '@/components/common/EventStatusBadge'
import { formatCurrency, formatDateTime } from '@/lib/utils'
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
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import type { EventFilters, EventItem, EventStatus, PaginationMeta } from '@/types'

export function AdminEventListPage() {
  const { toast } = useToast()

  const [rows, setRows] = useState<EventItem[]>([])
  const [meta, setMeta] = useState<PaginationMeta | null>(null)
  const [loading, setLoading] = useState(true)

  const [search, setSearch] = useState('')
  const debouncedSearch = useDebounce(search)
  const [status, setStatus] = useState<EventStatus | 'all'>('all')
  const [page, setPage] = useState(1)

  const [deleting, setDeleting] = useState<EventItem | null>(null)
  const [deletePending, setDeletePending] = useState(false)

  const fetchEvents = useCallback(async () => {
    setLoading(true)
    try {
      const filters: EventFilters = {
        search: debouncedSearch || undefined,
        status: status === 'all' ? undefined : status,
        published_only: false,
        sort_by: 'created_at',
        sort_dir: 'desc',
        page,
        per_page: 10,
      }
      const res = await eventService.list(filters)
      setRows(res.data)
      setMeta(res.meta)
    } catch (e) {
      toast({ title: 'Failed to load events', description: extractApiError(e).message, variant: 'error' })
    } finally {
      setLoading(false)
    }
  }, [debouncedSearch, status, page, toast])

  useEffect(() => {
    void fetchEvents()
  }, [fetchEvents])

  useEffect(() => {
    setPage(1)
  }, [debouncedSearch, status])

  const handleDelete = async () => {
    if (!deleting) return
    setDeletePending(true)
    try {
      await eventService.remove(deleting.id)
      toast({ title: 'Event deleted', variant: 'success' })
      setDeleting(null)
      void fetchEvents()
    } catch (e) {
      toast({ title: 'Delete failed', description: extractApiError(e).message, variant: 'error' })
    } finally {
      setDeletePending(false)
    }
  }

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold">Event Management</h1>
          <p className="text-muted-foreground">Create and manage alumni events.</p>
        </div>
        <Link to="/admin/events/create">
          <Button>
            <Plus className="h-4 w-4" />
            New event
          </Button>
        </Link>
      </div>

      <div className="flex flex-col gap-3 sm:flex-row">
        <div className="relative flex-1">
          <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
          <Input
            placeholder="Search events…"
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            className="pl-9"
          />
        </div>
        <Select value={status} onValueChange={(v) => setStatus(v as EventStatus | 'all')}>
          <SelectTrigger className="w-full sm:w-44">
            <SelectValue placeholder="Status" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">All statuses</SelectItem>
            <SelectItem value="draft">Draft</SelectItem>
            <SelectItem value="published">Published</SelectItem>
            <SelectItem value="closed">Closed</SelectItem>
            <SelectItem value="completed">Completed</SelectItem>
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
            <p className="py-16 text-center text-sm text-muted-foreground">No events found.</p>
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Event</TableHead>
                  <TableHead>Date</TableHead>
                  <TableHead>Fee</TableHead>
                  <TableHead>Capacity</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead className="text-right">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {rows.map((ev) => (
                  <TableRow key={ev.id}>
                    <TableCell>
                      <p className="font-medium">{ev.title}</p>
                      <p className="text-xs text-muted-foreground">{ev.type_label}</p>
                    </TableCell>
                    <TableCell className="text-muted-foreground">
                      {formatDateTime(ev.event_date)}
                    </TableCell>
                    <TableCell>{formatCurrency(ev.fee)}</TableCell>
                    <TableCell className="text-muted-foreground">
                      {ev.max_capacity === null
                        ? `${ev.confirmed_count} / ∞`
                        : `${ev.confirmed_count} / ${ev.max_capacity}`}
                    </TableCell>
                    <TableCell>
                      <EventStatusBadge status={ev.status} />
                    </TableCell>
                    <TableCell className="text-right">
                      <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                          <Button variant="ghost" size="icon">
                            <MoreHorizontal className="h-4 w-4" />
                          </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                          <DropdownMenuItem asChild>
                            <Link to={`/events/${ev.slug}`}>
                              <Eye className="h-4 w-4" />
                              View
                            </Link>
                          </DropdownMenuItem>
                          <DropdownMenuItem asChild>
                            <Link to={`/admin/events/${ev.id}/edit`}>
                              <Pencil className="h-4 w-4" />
                              Edit
                            </Link>
                          </DropdownMenuItem>
                          <DropdownMenuItem asChild>
                            <Link to={`/admin/events/${ev.id}/registrations`}>
                              <Users className="h-4 w-4" />
                              Registrations
                            </Link>
                          </DropdownMenuItem>
                          <DropdownMenuItem asChild>
                            <Link to={`/admin/events/${ev.id}/attendance`}>
                              <ScanLine className="h-4 w-4" />
                              Attendance
                            </Link>
                          </DropdownMenuItem>
                          <DropdownMenuSeparator />
                          <DropdownMenuItem
                            className="text-destructive focus:text-destructive"
                            onClick={() => setDeleting(ev)}
                          >
                            <Trash2 className="h-4 w-4" />
                            Delete
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

      <Dialog open={!!deleting} onOpenChange={(o) => !o && setDeleting(null)}>
        <DialogContent className="max-w-sm">
          <DialogHeader>
            <DialogTitle>Delete event</DialogTitle>
            <DialogDescription>
              Delete <span className="font-medium text-foreground">{deleting?.title}</span>? This
              also removes its form fields and registrations.
            </DialogDescription>
          </DialogHeader>
          <DialogFooter>
            <Button variant="outline" onClick={() => setDeleting(null)}>
              Cancel
            </Button>
            <Button variant="destructive" onClick={handleDelete} disabled={deletePending}>
              {deletePending && <Spinner className="h-4 w-4 text-destructive-foreground" />}
              Delete
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  )
}
