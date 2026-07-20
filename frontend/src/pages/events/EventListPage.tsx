import { useCallback, useEffect, useState } from 'react'
import { Search } from 'lucide-react'
import { eventService } from '@/services/event.service'
import { extractApiError } from '@/lib/api'
import { useToast } from '@/components/ui/toast'
import { useDebounce } from '@/hooks/useDebounce'
import { Input } from '@/components/ui/input'
import { Card, CardContent } from '@/components/ui/card'
import { Spinner } from '@/components/ui/spinner'
import { EventCard } from '@/components/common/EventCard'
import { Pagination } from '@/components/common/Pagination'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import type { EnumOption, EventFilters, EventItem, EventType, PaginationMeta } from '@/types'

export function EventListPage() {
  const { toast } = useToast()

  const [rows, setRows] = useState<EventItem[]>([])
  const [meta, setMeta] = useState<PaginationMeta | null>(null)
  const [loading, setLoading] = useState(true)
  const [types, setTypes] = useState<EnumOption[]>([])

  const [search, setSearch] = useState('')
  const debouncedSearch = useDebounce(search)
  const [type, setType] = useState<EventType | 'all'>('all')
  const [page, setPage] = useState(1)

  useEffect(() => {
    eventService.meta().then((m) => setTypes(m.types)).catch(() => void 0)
  }, [])

  const fetchEvents = useCallback(async () => {
    setLoading(true)
    try {
      const filters: EventFilters = {
        search: debouncedSearch || undefined,
        type: type === 'all' ? undefined : type,
        page,
        per_page: 12,
      }
      const res = await eventService.list(filters)
      setRows(res.data)
      setMeta(res.meta)
    } catch (e) {
      toast({ title: 'Failed to load events', description: extractApiError(e).message, variant: 'error' })
    } finally {
      setLoading(false)
    }
  }, [debouncedSearch, type, page, toast])

  useEffect(() => {
    void fetchEvents()
  }, [fetchEvents])

  useEffect(() => {
    setPage(1)
  }, [debouncedSearch, type])

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold">Events</h1>
        <p className="text-muted-foreground">Discover and register for upcoming alumni events.</p>
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
        <Select value={type} onValueChange={(v) => setType(v as EventType | 'all')}>
          <SelectTrigger className="w-full sm:w-52">
            <SelectValue placeholder="Type" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">All types</SelectItem>
            {types.map((t) => (
              <SelectItem key={t.value} value={t.value}>
                {t.label}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>
      </div>

      {loading ? (
        <div className="flex h-64 items-center justify-center">
          <Spinner className="h-8 w-8" />
        </div>
      ) : rows.length === 0 ? (
        <Card>
          <CardContent className="py-16 text-center text-sm text-muted-foreground">
            No events found.
          </CardContent>
        </Card>
      ) : (
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
          {rows.map((ev) => (
            <EventCard key={ev.id} event={ev} basePath="/events" />
          ))}
        </div>
      )}

      {meta && <Pagination meta={meta} onPageChange={setPage} />}
    </div>
  )
}
