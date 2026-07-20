import { Link } from 'react-router-dom'
import { CalendarDays, MapPin, Users, Ticket } from 'lucide-react'
import { Card, CardContent } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { EventStatusBadge } from '@/components/common/EventStatusBadge'
import { formatCurrency, formatDateTime } from '@/lib/utils'
import type { EventItem } from '@/types'

interface EventCardProps {
  event: EventItem
  /** Base path for the details link (e.g. "/events" or "/public/events"). */
  basePath?: string
  showStatus?: boolean
}

export function EventCard({ event, basePath = '/events', showStatus = false }: EventCardProps) {
  return (
    <Link to={`${basePath}/${event.slug}`} className="group block">
      <Card className="h-full overflow-hidden transition-shadow group-hover:shadow-md">
        <div className="relative h-36 bg-gradient-to-br from-primary/80 to-indigo-500">
          {event.banner_url && (
            <img
              src={event.banner_url}
              alt={event.title}
              className="h-full w-full object-cover"
            />
          )}
          <div className="absolute left-3 top-3 flex gap-2">
            <Badge variant="secondary">{event.type_label}</Badge>
            {showStatus && <EventStatusBadge status={event.status} />}
          </div>
          {event.is_paid ? (
            <Badge className="absolute right-3 top-3" variant="default">
              {formatCurrency(event.fee)}
            </Badge>
          ) : (
            <Badge className="absolute right-3 top-3" variant="success">
              Free
            </Badge>
          )}
        </div>

        <CardContent className="space-y-3 p-4">
          <h3 className="line-clamp-1 font-semibold group-hover:text-primary">{event.title}</h3>

          <div className="space-y-1.5 text-sm text-muted-foreground">
            <p className="flex items-center gap-2">
              <CalendarDays className="h-3.5 w-3.5" />
              {formatDateTime(event.event_date)}
            </p>
            {event.venue && (
              <p className="flex items-center gap-2">
                <MapPin className="h-3.5 w-3.5" />
                <span className="line-clamp-1">{event.venue}</span>
              </p>
            )}
            <p className="flex items-center gap-2">
              <Users className="h-3.5 w-3.5" />
              {event.max_capacity === null
                ? `${event.confirmed_count} registered`
                : `${event.confirmed_count}/${event.max_capacity} registered`}
            </p>
          </div>

          <div className="flex items-center justify-between pt-1">
            {event.is_registration_open ? (
              <Badge variant="success" className="gap-1">
                <Ticket className="h-3 w-3" />
                Registration open
              </Badge>
            ) : (
              <Badge variant="secondary">
                {event.is_full ? 'Full' : 'Registration closed'}
              </Badge>
            )}
            {event.seats_left !== null && event.seats_left > 0 && (
              <span className="text-xs text-muted-foreground">{event.seats_left} seats left</span>
            )}
          </div>
        </CardContent>
      </Card>
    </Link>
  )
}
