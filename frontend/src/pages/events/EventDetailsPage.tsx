import { useEffect, useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { ArrowLeft, Ticket } from 'lucide-react'
import { eventService } from '@/services/event.service'
import { extractApiError } from '@/lib/api'
import { useToast } from '@/components/ui/toast'
import { Button } from '@/components/ui/button'
import { Spinner } from '@/components/ui/spinner'
import { EventDetailsView } from '@/components/common/EventDetailsView'
import { useAuth } from '@/hooks/useAuth'
import type { EventItem } from '@/types'

export function EventDetailsPage() {
  const { slug } = useParams()
  const navigate = useNavigate()
  const { toast } = useToast()
  const { hasRole } = useAuth()
  const isAdmin = hasRole(['super_admin', 'event_manager'])

  const [event, setEvent] = useState<EventItem | null>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    if (!slug) return
    setLoading(true)
    eventService
      .getBySlug(slug)
      .then(setEvent)
      .catch((e) => setError(extractApiError(e).message))
      .finally(() => setLoading(false))
  }, [slug])

  if (loading) {
    return (
      <div className="flex h-64 items-center justify-center">
        <Spinner className="h-8 w-8" />
      </div>
    )
  }

  if (error || !event) {
    return (
      <div className="space-y-4">
        <p className="text-destructive">{error ?? 'Event not found.'}</p>
        <Button variant="outline" onClick={() => navigate('/events')}>
          <ArrowLeft className="h-4 w-4" />
          Back to events
        </Button>
      </div>
    )
  }

  const action = event.is_registration_open ? (
    <Link to={`/events/${event.slug}/register`}>
      <Button className="w-full">
        <Ticket className="h-4 w-4" />
        Register now
      </Button>
    </Link>
  ) : (
    <Button className="w-full" disabled>
      {event.is_full ? 'Event is full' : 'Registration closed'}
    </Button>
  )

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <Button variant="ghost" size="sm" onClick={() => navigate('/events')}>
          <ArrowLeft className="h-4 w-4" />
          Back to events
        </Button>
        {isAdmin && (
          <Link to={`/admin/events/${event.id}/edit`}>
            <Button variant="outline" size="sm">
              Edit event
            </Button>
          </Link>
        )}
      </div>
      <EventDetailsView event={event} action={action} showStatus={isAdmin} />
    </div>
  )
}
