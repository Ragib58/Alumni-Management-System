import { useCallback, useEffect, useRef, useState } from 'react'
import { Plus, Pencil, Trash2, Upload, ExternalLink } from 'lucide-react'
import { sponsorService } from '@/services/sponsor.service'
import { eventService } from '@/services/event.service'
import { extractApiError } from '@/lib/api'
import { useToast } from '@/components/ui/toast'
import { useForm } from 'react-hook-form'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent } from '@/components/ui/card'
import { Spinner } from '@/components/ui/spinner'
import { Pagination } from '@/components/common/Pagination'
import { formatCurrency } from '@/lib/utils'
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
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import type { EnumOptionSimple, EventItem, PaginationMeta, Sponsor, SponsorPayload, SponsorType } from '@/types'

interface FormValues {
  name: string
  website: string
  amount: string
}

export function SponsorManagementPage() {
  const { toast } = useToast()
  const [rows, setRows] = useState<Sponsor[]>([])
  const [meta, setMeta] = useState<PaginationMeta | null>(null)
  const [loading, setLoading] = useState(true)
  const [page, setPage] = useState(1)

  const [types, setTypes] = useState<EnumOptionSimple[]>([])
  const [events, setEvents] = useState<EventItem[]>([])

  const [open, setOpen] = useState(false)
  const [editing, setEditing] = useState<Sponsor | null>(null)
  const [deleting, setDeleting] = useState<Sponsor | null>(null)

  const [sponsorType, setSponsorType] = useState<SponsorType>('gold')
  const [eventId, setEventId] = useState<string>('none')
  const [logo, setLogo] = useState<File | null>(null)
  const [preview, setPreview] = useState<string | null>(null)
  const fileRef = useRef<HTMLInputElement>(null)

  const { register, handleSubmit, reset, formState: { isSubmitting } } = useForm<FormValues>()

  useEffect(() => {
    sponsorService.meta().then(setTypes).catch(() => void 0)
    eventService.list({ published_only: false, per_page: 100 }).then((r) => setEvents(r.data)).catch(() => void 0)
  }, [])

  const fetchSponsors = useCallback(async () => {
    setLoading(true)
    try {
      const res = await sponsorService.list({ page })
      setRows(res.data)
      setMeta(res.meta)
    } catch (e) {
      toast({ title: 'Failed to load sponsors', description: extractApiError(e).message, variant: 'error' })
    } finally {
      setLoading(false)
    }
  }, [page, toast])

  useEffect(() => {
    void fetchSponsors()
  }, [fetchSponsors])

  const openForm = (sponsor: Sponsor | null) => {
    setEditing(sponsor)
    setSponsorType(sponsor?.sponsor_type ?? 'gold')
    setEventId(sponsor?.event_id ? String(sponsor.event_id) : 'none')
    setLogo(null)
    setPreview(sponsor?.logo_url ?? null)
    reset({
      name: sponsor?.name ?? '',
      website: sponsor?.website ?? '',
      amount: sponsor ? String(sponsor.amount) : '0',
    })
    setOpen(true)
  }

  const handleLogo = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0]
    if (file) {
      setLogo(file)
      setPreview(URL.createObjectURL(file))
    }
  }

  const submit = async (values: FormValues) => {
    const payload: SponsorPayload = {
      name: values.name,
      website: values.website || undefined,
      amount: values.amount ? Number(values.amount) : 0,
      sponsor_type: sponsorType,
      event_id: eventId === 'none' ? null : Number(eventId),
      logo,
    }
    try {
      if (editing) {
        await sponsorService.update(editing.id, payload)
        toast({ title: 'Sponsor updated', variant: 'success' })
      } else {
        await sponsorService.create(payload)
        toast({ title: 'Sponsor added', variant: 'success' })
      }
      setOpen(false)
      void fetchSponsors()
    } catch (e) {
      toast({ title: 'Save failed', description: extractApiError(e).message, variant: 'error' })
    }
  }

  const handleDelete = async () => {
    if (!deleting) return
    try {
      await sponsorService.remove(deleting.id)
      toast({ title: 'Sponsor deleted', variant: 'success' })
      setDeleting(null)
      void fetchSponsors()
    } catch (e) {
      toast({ title: 'Delete failed', description: extractApiError(e).message, variant: 'error' })
    }
  }

  const tierVariant = (t: SponsorType) =>
    t === 'platinum' ? 'default' : t === 'gold' ? 'warning' : t === 'silver' ? 'secondary' : 'outline'

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold">Sponsors</h1>
          <p className="text-muted-foreground">Manage event sponsors and tiers.</p>
        </div>
        <Button onClick={() => openForm(null)}>
          <Plus className="h-4 w-4" />
          Add sponsor
        </Button>
      </div>

      <Card>
        <CardContent className="p-0">
          {loading ? (
            <div className="flex h-64 items-center justify-center">
              <Spinner className="h-8 w-8" />
            </div>
          ) : rows.length === 0 ? (
            <p className="py-16 text-center text-sm text-muted-foreground">No sponsors yet.</p>
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Sponsor</TableHead>
                  <TableHead>Tier</TableHead>
                  <TableHead>Event</TableHead>
                  <TableHead>Amount</TableHead>
                  <TableHead className="text-right">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {rows.map((s) => (
                  <TableRow key={s.id}>
                    <TableCell>
                      <div className="flex items-center gap-3">
                        <div className="flex h-10 w-16 items-center justify-center overflow-hidden rounded border bg-muted/40">
                          {s.logo_url ? (
                            <img src={s.logo_url} alt={s.name} className="max-h-full max-w-full object-contain" />
                          ) : (
                            <span className="text-xs text-muted-foreground">No logo</span>
                          )}
                        </div>
                        <div>
                          <p className="font-medium">{s.name}</p>
                          {s.website && (
                            <a
                              href={s.website}
                              target="_blank"
                              rel="noreferrer"
                              className="flex items-center gap-1 text-xs text-primary hover:underline"
                            >
                              <ExternalLink className="h-3 w-3" />
                              Website
                            </a>
                          )}
                        </div>
                      </div>
                    </TableCell>
                    <TableCell>
                      <Badge variant={tierVariant(s.sponsor_type)}>{s.sponsor_type_label}</Badge>
                    </TableCell>
                    <TableCell className="text-muted-foreground">{s.event?.title ?? '—'}</TableCell>
                    <TableCell className="font-medium">{formatCurrency(s.amount)}</TableCell>
                    <TableCell className="text-right">
                      <Button variant="ghost" size="icon" onClick={() => openForm(s)}>
                        <Pencil className="h-4 w-4" />
                      </Button>
                      <Button
                        variant="ghost"
                        size="icon"
                        className="text-destructive hover:text-destructive"
                        onClick={() => setDeleting(s)}
                      >
                        <Trash2 className="h-4 w-4" />
                      </Button>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          )}
        </CardContent>
      </Card>

      {meta && <Pagination meta={meta} onPageChange={setPage} />}

      {/* Form dialog */}
      <Dialog open={open} onOpenChange={setOpen}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>{editing ? 'Edit sponsor' : 'Add sponsor'}</DialogTitle>
            <DialogDescription>Sponsors appear on their event&apos;s public page.</DialogDescription>
          </DialogHeader>
          <form onSubmit={handleSubmit(submit)} className="space-y-4">
            <div className="flex items-center gap-4">
              <div className="flex h-16 w-24 items-center justify-center overflow-hidden rounded border bg-muted/40">
                {preview ? (
                  <img src={preview} alt="logo" className="max-h-full max-w-full object-contain" />
                ) : (
                  <span className="text-xs text-muted-foreground">Logo</span>
                )}
              </div>
              <div>
                <input ref={fileRef} type="file" accept="image/*" className="hidden" onChange={handleLogo} />
                <Button type="button" variant="outline" size="sm" onClick={() => fileRef.current?.click()}>
                  <Upload className="h-4 w-4" />
                  Upload logo
                </Button>
              </div>
            </div>

            <div className="space-y-2">
              <Label>Name</Label>
              <Input {...register('name', { required: true })} />
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
              <div className="space-y-2">
                <Label>Tier</Label>
                <Select value={sponsorType} onValueChange={(v) => setSponsorType(v as SponsorType)}>
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    {types.map((t) => (
                      <SelectItem key={t.value} value={t.value}>
                        {t.label}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
              <div className="space-y-2">
                <Label>Amount (BDT)</Label>
                <Input type="number" min="0" {...register('amount')} />
              </div>
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
              <div className="space-y-2">
                <Label>Event</Label>
                <Select value={eventId} onValueChange={setEventId}>
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="none">General (no event)</SelectItem>
                    {events.map((ev) => (
                      <SelectItem key={ev.id} value={String(ev.id)}>
                        {ev.title}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
              <div className="space-y-2">
                <Label>Website</Label>
                <Input placeholder="https://…" {...register('website')} />
              </div>
            </div>

            <DialogFooter>
              <Button type="button" variant="outline" onClick={() => setOpen(false)}>
                Cancel
              </Button>
              <Button type="submit" disabled={isSubmitting}>
                {isSubmitting && <Spinner className="h-4 w-4 text-primary-foreground" />}
                {editing ? 'Save' : 'Add sponsor'}
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>

      {/* Delete confirm */}
      <Dialog open={!!deleting} onOpenChange={(o) => !o && setDeleting(null)}>
        <DialogContent className="max-w-sm">
          <DialogHeader>
            <DialogTitle>Delete sponsor</DialogTitle>
            <DialogDescription>Remove {deleting?.name}?</DialogDescription>
          </DialogHeader>
          <DialogFooter>
            <Button variant="outline" onClick={() => setDeleting(null)}>
              Cancel
            </Button>
            <Button variant="destructive" onClick={handleDelete}>
              Delete
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  )
}
