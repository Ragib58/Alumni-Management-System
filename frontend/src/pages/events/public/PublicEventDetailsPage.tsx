import { useEffect, useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { ArrowLeft, GraduationCap, LogIn } from 'lucide-react'
import { eventService } from '@/services/event.service'
import { extractApiError } from '@/lib/api'
import { Button } from '@/components/ui/button'
import { Spinner } from '@/components/ui/spinner'
import { EventDetailsView } from '@/components/common/EventDetailsView'
import type { EventItem } from '@/types'

const APP_NAME = import.meta.env.VITE_APP_NAME ?? 'Alumni Event Management'

export function PublicEventDetailsPage() {
  const { slug } = useParams()
  const navigate = useNavigate()
  const [event, setEvent] = useState<EventItem | null>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    if (!slug) return
    setLoading(true)
    eventService
      .publicGetBySlug(slug)
      .then(setEvent)
      .catch((e) => setError(extractApiError(e).message))
      .finally(() => setLoading(false))
  }, [slug])

  const action = (
    <Link to="/login">
      <Button className="w-full">
        <LogIn className="h-4 w-4" />
        Sign in to register
      </Button>
    </Link>
  )

  return (
    <div className="min-h-screen bg-muted/30">
      <header className="border-b bg-background">
        <div className="container flex h-16 items-center justify-between">
          <Link to="/public/events" className="flex items-center gap-2 font-semibold">
            <GraduationCap className="h-6 w-6 text-primary" />
            {APP_NAME}
          </Link>
          <Link to="/login">
            <Button size="sm">Sign in</Button>
          </Link>
        </div>
      </header>

      <main className="container space-y-4 py-8">
        <Button variant="ghost" size="sm" onClick={() => navigate('/public/events')}>
          <ArrowLeft className="h-4 w-4" />
          All events
        </Button>

        {loading ? (
          <div className="flex h-64 items-center justify-center">
            <Spinner className="h-8 w-8" />
          </div>
        ) : error || !event ? (
          <p className="text-destructive">{error ?? 'Event not found.'}</p>
        ) : (
          <EventDetailsView event={event} action={action} />
        )}
      </main>
    </div>
  )
}
