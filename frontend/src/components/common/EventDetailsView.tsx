import {
  CalendarDays,
  Clock,
  MapPin,
  Users,
  Ticket,
  Tag,
} from 'lucide-react'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { EventStatusBadge } from '@/components/common/EventStatusBadge'
import { SponsorList } from '@/components/common/SponsorList'
import { formatCurrency, formatDateTime } from '@/lib/utils'
import type { EventItem } from '@/types'
import type { ReactNode } from 'react'

interface EventDetailsViewProps {
  event: EventItem
  /** Optional action area (e.g. Register button) rendered in the sidebar. */
  action?: ReactNode
  showStatus?: boolean
}

export function EventDetailsView({ event, action, showStatus = false }: EventDetailsViewProps) {
  return (
    <div className="space-y-6">
      {/* Banner / header */}
      <Card className="overflow-hidden">
        <div className="relative h-48 bg-gradient-to-br from-primary/80 to-indigo-500 sm:h-60">
          {event.banner_url && (
            <img src={event.banner_url} alt={event.title} className="h-full w-full object-cover" />
          )}
          <div className="absolute left-4 top-4 flex gap-2">
            <Badge variant="secondary">{event.type_label}</Badge>
            {showStatus && <EventStatusBadge status={event.status} />}
          </div>
        </div>
        <CardContent className="p-6">
          <h1 className="text-2xl font-bold">{event.title}</h1>
          <div className="mt-3 flex flex-wrap gap-4 text-sm text-muted-foreground">
            <span className="flex items-center gap-2">
              <CalendarDays className="h-4 w-4" />
              {formatDateTime(event.event_date)}
            </span>
            {event.venue && (
              <span className="flex items-center gap-2">
                <MapPin className="h-4 w-4" />
                {event.venue}
              </span>
            )}
            <span className="flex items-center gap-2">
              <Tag className="h-4 w-4" />
              {formatCurrency(event.fee)}
            </span>
          </div>
        </CardContent>
      </Card>

      <div className="grid gap-6 lg:grid-cols-3">
        {/* Description */}
        <div className="space-y-6 lg:col-span-2">
          <Card>
            <CardHeader>
              <CardTitle>About this event</CardTitle>
            </CardHeader>
            <CardContent>
              <p className="whitespace-pre-line text-sm leading-relaxed text-muted-foreground">
                {event.description || 'No description provided.'}
              </p>
            </CardContent>
          </Card>

          {event.sponsors && event.sponsors.length > 0 && (
            <SponsorList sponsors={event.sponsors} />
          )}
        </div>

        {/* Sidebar */}
        <div className="space-y-4">
          <Card>
            <CardHeader>
              <CardTitle className="text-base">Registration</CardTitle>
            </CardHeader>
            <CardContent className="space-y-3 text-sm">
              <InfoRow icon={Ticket} label="Fee" value={formatCurrency(event.fee)} />
              <InfoRow
                icon={Users}
                label="Capacity"
                value={
                  event.max_capacity === null
                    ? `${event.confirmed_count} registered`
                    : `${event.confirmed_count} / ${event.max_capacity}`
                }
              />
              {event.registration_start && (
                <InfoRow
                  icon={Clock}
                  label="Opens"
                  value={formatDateTime(event.registration_start)}
                />
              )}
              {event.registration_end && (
                <InfoRow
                  icon={Clock}
                  label="Closes"
                  value={formatDateTime(event.registration_end)}
                />
              )}

              <div className="pt-2">
                {event.is_registration_open ? (
                  <Badge variant="success">Registration open</Badge>
                ) : (
                  <Badge variant="secondary">
                    {event.is_full ? 'Event full' : 'Registration closed'}
                  </Badge>
                )}
              </div>

              {action && <div className="pt-2">{action}</div>}
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  )
}

function InfoRow({
  icon: Icon,
  label,
  value,
}: {
  icon: React.ComponentType<{ className?: string }>
  label: string
  value: string
}) {
  return (
    <div className="flex items-center justify-between">
      <span className="flex items-center gap-2 text-muted-foreground">
        <Icon className="h-4 w-4" />
        {label}
      </span>
      <span className="font-medium">{value}</span>
    </div>
  )
}
