import { useEffect, useMemo, useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { ArrowLeft, CheckCircle2, Ticket } from 'lucide-react'
import { eventService } from '@/services/event.service'
import { registrationService } from '@/services/registration.service'
import { extractApiError } from '@/lib/api'
import { useToast } from '@/components/ui/toast'
import { Button } from '@/components/ui/button'
import { Spinner } from '@/components/ui/spinner'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { DynamicFormRenderer, type FieldValue } from '@/components/common/DynamicFormRenderer'
import { PaymentStatusBadge, RegistrationStatusBadge } from '@/components/common/EventStatusBadge'
import { formatCurrency } from '@/lib/utils'
import type { EventItem, EventRegistration } from '@/types'

export function RegistrationFormPage() {
  const { slug } = useParams()
  const navigate = useNavigate()
  const { toast } = useToast()

  const [event, setEvent] = useState<EventItem | null>(null)
  const [loading, setLoading] = useState(true)
  const [loadError, setLoadError] = useState<string | null>(null)

  const [values, setValues] = useState<Record<string, FieldValue>>({})
  const [files, setFiles] = useState<Record<string, File>>({})
  const [errors, setErrors] = useState<Record<string, string[]>>({})
  const [serverError, setServerError] = useState<string | null>(null)
  const [submitting, setSubmitting] = useState(false)

  const [result, setResult] = useState<EventRegistration | null>(null)

  const fields = useMemo(() => event?.form_fields ?? [], [event])

  useEffect(() => {
    if (!slug) return
    setLoading(true)
    eventService
      .getBySlug(slug)
      .then(setEvent)
      .catch((e) => setLoadError(extractApiError(e).message))
      .finally(() => setLoading(false))
  }, [slug])

  const handleChange = (name: string, value: FieldValue) =>
    setValues((prev) => ({ ...prev, [name]: value }))

  const handleFile = (name: string, file: File | null) =>
    setFiles((prev) => {
      const next = { ...prev }
      if (file) next[name] = file
      else delete next[name]
      return next
    })

  const submit = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!event) return
    setServerError(null)
    setErrors({})
    setSubmitting(true)
    try {
      // Only send scalar/array answers; file fields go through `files`.
      const formResponse: Record<string, string | string[]> = {}
      Object.entries(values).forEach(([k, v]) => {
        if (v !== undefined && v !== null && v !== '' && !(Array.isArray(v) && v.length === 0)) {
          formResponse[k] = v
        }
      })

      const registration = await registrationService.register(event.id, formResponse, files)
      setResult(registration)
      toast({ title: 'Registration successful', variant: 'success' })
    } catch (err) {
      const parsed = extractApiError(err)
      setErrors(parsed.errors ?? {})
      setServerError(parsed.message)
    } finally {
      setSubmitting(false)
    }
  }

  if (loading) {
    return (
      <div className="flex h-64 items-center justify-center">
        <Spinner className="h-8 w-8" />
      </div>
    )
  }

  if (loadError || !event) {
    return (
      <div className="space-y-4">
        <p className="text-destructive">{loadError ?? 'Event not found.'}</p>
        <Button variant="outline" onClick={() => navigate('/events')}>
          <ArrowLeft className="h-4 w-4" />
          Back to events
        </Button>
      </div>
    )
  }

  // ---- Success screen (Step 3 + 4: created, payment pending) ----
  if (result) {
    return (
      <div className="mx-auto max-w-lg">
        <Card>
          <CardHeader className="items-center text-center">
            <div className="mb-2 flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100">
              <CheckCircle2 className="h-7 w-7 text-emerald-600" />
            </div>
            <CardTitle className="text-2xl">You&apos;re registered!</CardTitle>
            <CardDescription>Your spot for {event.title} is reserved.</CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="rounded-lg border p-4 text-sm">
              <div className="flex justify-between py-1">
                <span className="text-muted-foreground">Registration No.</span>
                <span className="font-mono font-medium">{result.registration_no}</span>
              </div>
              <div className="flex items-center justify-between py-1">
                <span className="text-muted-foreground">Status</span>
                <RegistrationStatusBadge status={result.status} />
              </div>
              <div className="flex items-center justify-between py-1">
                <span className="text-muted-foreground">Payment</span>
                <PaymentStatusBadge status={result.payment_status} />
              </div>
              <div className="flex justify-between py-1">
                <span className="text-muted-foreground">Amount</span>
                <span className="font-medium">{formatCurrency(result.amount)}</span>
              </div>
            </div>

            {result.payment_status === 'pending' && (
              <p className="rounded-md bg-amber-50 px-3 py-2 text-sm text-amber-700">
                Your payment is pending. Complete the payment now to confirm your seat and
                receive your ticket.
              </p>
            )}

            {result.payment_status === 'pending' && result.amount > 0 ? (
              <>
                <Link to={`/registrations/${result.id}/pay`} className="block">
                  <Button className="w-full">Proceed to payment ({formatCurrency(result.amount)})</Button>
                </Link>
                <Link to="/my-registrations" className="block">
                  <Button variant="outline" className="w-full">
                    Pay later — my registrations
                  </Button>
                </Link>
              </>
            ) : (
              <div className="flex gap-3">
                <Link to="/my-tickets" className="flex-1">
                  <Button className="w-full">My tickets</Button>
                </Link>
                <Link to="/events" className="flex-1">
                  <Button variant="outline" className="w-full">
                    Browse events
                  </Button>
                </Link>
              </div>
            )}
          </CardContent>
        </Card>
      </div>
    )
  }

  // ---- Registration form (Step 2) ----
  return (
    <div className="mx-auto max-w-2xl space-y-6">
      <Button variant="ghost" size="sm" onClick={() => navigate(`/events/${event.slug}`)}>
        <ArrowLeft className="h-4 w-4" />
        Back to event
      </Button>

      <Card>
        <CardHeader>
          <CardTitle>Register — {event.title}</CardTitle>
          <CardDescription>
            {event.is_paid
              ? `Registration fee: ${formatCurrency(event.fee)} (payable after registration).`
              : 'This is a free event.'}
          </CardDescription>
        </CardHeader>
        <CardContent>
          {!event.is_registration_open ? (
            <p className="rounded-md bg-destructive/10 px-3 py-2 text-sm text-destructive">
              {event.is_full
                ? 'This event has reached its capacity.'
                : 'Registration for this event is closed.'}
            </p>
          ) : (
            <form onSubmit={submit} className="space-y-6" noValidate>
              {serverError && (
                <div className="rounded-md bg-destructive/10 px-3 py-2 text-sm text-destructive">
                  {serverError}
                </div>
              )}

              <DynamicFormRenderer
                fields={fields}
                values={values}
                onChange={handleChange}
                files={files}
                onFileChange={handleFile}
                errors={errors}
              />

              <Button type="submit" className="w-full" disabled={submitting}>
                {submitting && <Spinner className="h-4 w-4 text-primary-foreground" />}
                <Ticket className="h-4 w-4" />
                {event.is_paid
                  ? `Confirm registration (${formatCurrency(event.fee)})`
                  : 'Confirm registration'}
              </Button>
            </form>
          )}
        </CardContent>
      </Card>
    </div>
  )
}
