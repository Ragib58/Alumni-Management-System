import { useCallback, useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { GraduationCap, Search } from 'lucide-react'
import { eventService } from '@/services/event.service'
import { extractApiError } from '@/lib/api'
import { useDebounce } from '@/hooks/useDebounce'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Card, CardContent } from '@/components/ui/card'
import { Spinner } from '@/components/ui/spinner'
import { EventCard } from '@/components/common/EventCard'
import { Pagination } from '@/components/common/Pagination'
import type { EventFilters, EventItem, PaginationMeta } from '@/types'

const APP_NAME = import.meta.env.VITE_APP_NAME ?? 'Alumni Event Management'

export function PublicEventsPage() {
  const [rows, setRows] = useState<EventItem[]>([])
  const [meta, setMeta] = useState<PaginationMeta | null>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  const [search, setSearch] = useState('')
  const debouncedSearch = useDebounce(search)
  const [page, setPage] = useState(1)

  const fetchEvents = useCallback(async () => {
    setLoading(true)
    try {
      const filters: EventFilters = { search: debouncedSearch || undefined, page, per_page: 12 }
      const res = await eventService.publicList(filters)
      setRows(res.data)
      setMeta(res.meta)
    } catch (e) {
      setError(extractApiError(e).message)
    } finally {
      setLoading(false)
    }
  }, [debouncedSearch, page])

  useEffect(() => {
    void fetchEvents()
  }, [fetchEvents])

  useEffect(() => {
    setPage(1)
  }, [debouncedSearch])

  return (
    <div className="min-h-screen bg-muted/30">
      <header className="border-b bg-background">
        <div className="container flex h-16 items-center justify-between">
          <Link to="/public/events" className="flex items-center gap-2 font-semibold">
            <GraduationCap className="h-6 w-6 text-primary" />
            {APP_NAME}
          </Link>
          <div className="flex items-center gap-2">
            <Link to="/login">
              <Button variant="ghost" size="sm">
                Sign in
              </Button>
            </Link>
            <Link to="/register">
              <Button size="sm">Join now</Button>
            </Link>
          </div>
        </div>
      </header>

      <main className="container space-y-6 py-8">
        <div>
          <h1 className="text-3xl font-bold">Upcoming Events</h1>
          <p className="text-muted-foreground">
            Explore public alumni events. Sign in to register.
          </p>
        </div>

        <div className="relative max-w-md">
          <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
          <Input
            placeholder="Search events…"
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            className="pl-9"
          />
        </div>

        {loading ? (
          <div className="flex h-64 items-center justify-center">
            <Spinner className="h-8 w-8" />
          </div>
        ) : error ? (
          <p className="text-destructive">{error}</p>
        ) : rows.length === 0 ? (
          <Card>
            <CardContent className="py-16 text-center text-sm text-muted-foreground">
              No public events at the moment.
            </CardContent>
          </Card>
        ) : (
          <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            {rows.map((ev) => (
              <EventCard key={ev.id} event={ev} basePath="/public/events" />
            ))}
          </div>
        )}

        {meta && <Pagination meta={meta} onPageChange={setPage} />}
      </main>
    </div>
  )
}
