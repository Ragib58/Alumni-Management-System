import { useEffect, useRef, useState } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import { useForm } from 'react-hook-form'
import { ArrowLeft, Upload } from 'lucide-react'
import { eventService } from '@/services/event.service'
import { extractApiError } from '@/lib/api'
import { useToast } from '@/components/ui/toast'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { Spinner } from '@/components/ui/spinner'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { FormBuilder } from '@/components/common/FormBuilder'
import { toDateTimeLocal } from '@/lib/utils'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import type {
  EnumOption,
  EventFormField,
  EventPayload,
  EventStatus,
  EventType,
} from '@/types'

interface FormValues {
  title: string
  description: string
  venue: string
  event_date: string
  registration_start: string
  registration_end: string
  fee: string
  max_capacity: string
}

export function EventFormPage() {
  const { id } = useParams()
  const editing = !!id
  const navigate = useNavigate()
  const { toast } = useToast()

  const bannerRef = useRef<HTMLInputElement>(null)
  const [banner, setBanner] = useState<File | null>(null)
  const [bannerPreview, setBannerPreview] = useState<string | null>(null)

  const [type, setType] = useState<EventType>('reunion')
  const [status, setStatus] = useState<EventStatus>('draft')
  const [formFields, setFormFields] = useState<EventFormField[]>([])

  const [typeOptions, setTypeOptions] = useState<EnumOption[]>([])
  const [statusOptions, setStatusOptions] = useState<EnumOption[]>([])
  const [fieldTypeOptions, setFieldTypeOptions] = useState<EnumOption[]>([])

  const [loading, setLoading] = useState(editing)
  const [serverError, setServerError] = useState<string | null>(null)

  const {
    register,
    handleSubmit,
    reset,
    setError,
    formState: { errors, isSubmitting },
  } = useForm<FormValues>({
    defaultValues: {
      title: '',
      description: '',
      venue: '',
      event_date: '',
      registration_start: '',
      registration_end: '',
      fee: '0',
      max_capacity: '',
    },
  })

  // Load enum meta + (when editing) the event itself.
  useEffect(() => {
    let active = true
    ;(async () => {
      try {
        const meta = await eventService.meta()
        if (!active) return
        setTypeOptions(meta.types)
        setStatusOptions(meta.statuses)
        setFieldTypeOptions(meta.field_types)

        if (editing && id) {
          const ev = await eventService.getById(Number(id))
          if (!active) return
          reset({
            title: ev.title,
            description: ev.description ?? '',
            venue: ev.venue ?? '',
            event_date: toDateTimeLocal(ev.event_date),
            registration_start: toDateTimeLocal(ev.registration_start),
            registration_end: toDateTimeLocal(ev.registration_end),
            fee: String(ev.fee ?? 0),
            max_capacity: ev.max_capacity ? String(ev.max_capacity) : '',
          })
          setType(ev.type)
          setStatus(ev.status)
          setFormFields(ev.form_fields ?? [])
          setBannerPreview(ev.banner_url)
        }
      } catch (e) {
        toast({ title: 'Failed to load', description: extractApiError(e).message, variant: 'error' })
      } finally {
        if (active) setLoading(false)
      }
    })()
    return () => {
      active = false
    }
  }, [editing, id, reset, toast])

  const handleBanner = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0]
    if (file) {
      setBanner(file)
      setBannerPreview(URL.createObjectURL(file))
    }
  }

  const onSubmit = async (values: FormValues) => {
    setServerError(null)

    // Basic client-side guard: option-typed fields need options + a label.
    for (const f of formFields) {
      if (!f.label.trim()) {
        setServerError('Every form field needs a label.')
        return
      }
      if (['select', 'checkbox', 'radio'].includes(f.type) && f.options.length === 0) {
        setServerError(`Field "${f.label}" needs at least one option.`)
        return
      }
    }

    const payload: EventPayload = {
      title: values.title,
      description: values.description || undefined,
      venue: values.venue || undefined,
      type,
      status,
      event_date: values.event_date,
      registration_start: values.registration_start || undefined,
      registration_end: values.registration_end || undefined,
      fee: values.fee ? Number(values.fee) : 0,
      max_capacity: values.max_capacity ? Number(values.max_capacity) : null,
      form_fields: formFields,
      banner,
    }

    try {
      if (editing && id) {
        await eventService.update(Number(id), payload)
        toast({ title: 'Event updated', variant: 'success' })
      } else {
        await eventService.create(payload)
        toast({ title: 'Event created', variant: 'success' })
      }
      navigate('/admin/events')
    } catch (e) {
      const err = extractApiError(e)
      if (err.errors) {
        Object.entries(err.errors).forEach(([field, messages]) => {
          if (['title', 'venue', 'event_date', 'fee', 'max_capacity', 'description'].includes(field)) {
            setError(field as keyof FormValues, { message: messages[0] })
          }
        })
      }
      setServerError(err.message)
    }
  }

  if (loading) {
    return (
      <div className="flex h-64 items-center justify-center">
        <Spinner className="h-8 w-8" />
      </div>
    )
  }

  return (
    <div className="mx-auto max-w-3xl space-y-6">
      <div className="flex items-center gap-3">
        <Button variant="ghost" size="icon" onClick={() => navigate('/admin/events')}>
          <ArrowLeft className="h-4 w-4" />
        </Button>
        <div>
          <h1 className="text-2xl font-bold">{editing ? 'Edit Event' : 'Create Event'}</h1>
          <p className="text-muted-foreground">
            {editing ? 'Update the event details and form.' : 'Set up a new alumni event.'}
          </p>
        </div>
      </div>

      <form onSubmit={handleSubmit(onSubmit)} className="space-y-6" noValidate>
        {serverError && (
          <div className="rounded-md bg-destructive/10 px-3 py-2 text-sm text-destructive">
            {serverError}
          </div>
        )}

        <Card>
          <CardHeader>
            <CardTitle>Event Details</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            {/* Banner */}
            <div className="space-y-2">
              <Label>Banner</Label>
              <div className="flex items-center gap-4">
                <div className="h-20 w-32 overflow-hidden rounded-md border bg-muted">
                  {bannerPreview && (
                    <img src={bannerPreview} alt="banner" className="h-full w-full object-cover" />
                  )}
                </div>
                <div>
                  <input
                    ref={bannerRef}
                    type="file"
                    accept="image/png,image/jpeg,image/webp"
                    className="hidden"
                    onChange={handleBanner}
                  />
                  <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    onClick={() => bannerRef.current?.click()}
                  >
                    <Upload className="h-4 w-4" />
                    Upload banner
                  </Button>
                  <p className="mt-1 text-xs text-muted-foreground">JPG/PNG/WebP, max 4MB.</p>
                </div>
              </div>
            </div>

            <div className="space-y-2">
              <Label htmlFor="title">Title</Label>
              <Input id="title" {...register('title', { required: 'Title is required' })} />
              {errors.title && <p className="text-xs text-destructive">{errors.title.message}</p>}
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
              <div className="space-y-2">
                <Label>Event type</Label>
                <Select value={type} onValueChange={(v) => setType(v as EventType)}>
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    {typeOptions.map((t) => (
                      <SelectItem key={t.value} value={t.value}>
                        {t.label}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
              <div className="space-y-2">
                <Label>Status</Label>
                <Select value={status} onValueChange={(v) => setStatus(v as EventStatus)}>
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    {statusOptions.map((s) => (
                      <SelectItem key={s.value} value={s.value}>
                        {s.label}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
            </div>

            <div className="space-y-2">
              <Label htmlFor="venue">Venue</Label>
              <Input id="venue" {...register('venue')} />
            </div>

            <div className="space-y-2">
              <Label htmlFor="description">Description</Label>
              <Textarea id="description" rows={4} {...register('description')} />
            </div>

            <div className="grid gap-4 sm:grid-cols-3">
              <div className="space-y-2">
                <Label htmlFor="event_date">Event date &amp; time</Label>
                <Input
                  id="event_date"
                  type="datetime-local"
                  {...register('event_date', { required: 'Event date is required' })}
                />
                {errors.event_date && (
                  <p className="text-xs text-destructive">{errors.event_date.message}</p>
                )}
              </div>
              <div className="space-y-2">
                <Label htmlFor="registration_start">Registration opens</Label>
                <Input id="registration_start" type="datetime-local" {...register('registration_start')} />
              </div>
              <div className="space-y-2">
                <Label htmlFor="registration_end">Registration closes</Label>
                <Input id="registration_end" type="datetime-local" {...register('registration_end')} />
              </div>
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
              <div className="space-y-2">
                <Label htmlFor="fee">Fee (BDT) — 0 for free</Label>
                <Input id="fee" type="number" min="0" step="0.01" {...register('fee')} />
                {errors.fee && <p className="text-xs text-destructive">{errors.fee.message}</p>}
              </div>
              <div className="space-y-2">
                <Label htmlFor="max_capacity">Max capacity (blank = unlimited)</Label>
                <Input id="max_capacity" type="number" min="1" {...register('max_capacity')} />
                {errors.max_capacity && (
                  <p className="text-xs text-destructive">{errors.max_capacity.message}</p>
                )}
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent className="pt-6">
            <FormBuilder fields={formFields} onChange={setFormFields} fieldTypes={fieldTypeOptions} />
          </CardContent>
        </Card>

        <div className="flex justify-end gap-3">
          <Button type="button" variant="outline" onClick={() => navigate('/admin/events')}>
            Cancel
          </Button>
          <Button type="submit" disabled={isSubmitting}>
            {isSubmitting && <Spinner className="h-4 w-4 text-primary-foreground" />}
            {editing ? 'Save changes' : 'Create event'}
          </Button>
        </div>
      </form>
    </div>
  )
}
