import { useEffect, useState } from 'react'
import { CheckCircle2, XCircle, ScanLine, UserCheck, AlertTriangle } from 'lucide-react'
import { attendanceService } from '@/services/attendance.service'
import { eventService } from '@/services/event.service'
import { extractApiError } from '@/lib/api'
import { useToast } from '@/components/ui/toast'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { QrScanner } from '@/components/common/QrScanner'
import { formatDateTime } from '@/lib/utils'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import type { Attendance, CheckInResult, EventItem } from '@/types'

interface ScanFeedItem {
  id: number
  name: string
  regNo: string
  time: string | null
  ok: boolean
  duplicate: boolean
  message: string
}

export function CheckInScannerPage() {
  const { toast } = useToast()
  const [events, setEvents] = useState<EventItem[]>([])
  const [eventId, setEventId] = useState<string>('all')
  const [scannerOn, setScannerOn] = useState(false)
  const [manual, setManual] = useState('')
  const [processing, setProcessing] = useState(false)
  const [last, setLast] = useState<{ result: CheckInResult; ok: boolean } | null>(null)
  const [feed, setFeed] = useState<ScanFeedItem[]>([])

  useEffect(() => {
    eventService
      .list({ published_only: true, per_page: 100, sort_by: 'event_date', sort_dir: 'desc' })
      .then((res) => setEvents(res.data))
      .catch(() => void 0)
  }, [])

  const boundEventId = eventId === 'all' ? undefined : Number(eventId)

  const record = (attendance: Attendance, ok: boolean, duplicate: boolean, message: string) => {
    setFeed((prev) => [
      {
        id: attendance.id + Date.now(),
        name: attendance.registration?.user?.name ?? 'Unknown',
        regNo: attendance.registration?.registration_no ?? '—',
        time: attendance.checkin_time,
        ok,
        duplicate,
        message,
      },
      ...prev.slice(0, 19),
    ])
  }

  const handleScan = async (value: string) => {
    if (processing) return
    setProcessing(true)
    try {
      const result = await attendanceService.checkInByQr(value, boundEventId)
      setLast({ result, ok: true })
      record(result.attendance, true, result.duplicate, result.message)
      if (!result.duplicate) {
        toast({ title: 'Checked in', description: result.attendance.registration?.user?.name, variant: 'success' })
      }
    } catch (e) {
      const msg = extractApiError(e).message
      setLast(null)
      toast({ title: 'Check-in failed', description: msg, variant: 'error' })
      setFeed((prev) => [
        { id: Date.now(), name: 'Rejected', regNo: '—', time: null, ok: false, duplicate: false, message: msg },
        ...prev.slice(0, 19),
      ])
    } finally {
      setProcessing(false)
    }
  }

  const handleManual = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!manual.trim()) return
    await handleScan(manual.trim())
    setManual('')
  }

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold">QR Check-In</h1>
        <p className="text-muted-foreground">Scan participant tickets to mark attendance.</p>
      </div>

      <div className="grid gap-6 lg:grid-cols-2">
        {/* Scanner + controls */}
        <div className="space-y-4">
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-base">
                <ScanLine className="h-5 w-5" />
                Scanner
              </CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="space-y-2">
                <Label>Event</Label>
                <Select value={eventId} onValueChange={setEventId}>
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="all">Any event</SelectItem>
                    {events.map((ev) => (
                      <SelectItem key={ev.id} value={String(ev.id)}>
                        {ev.title}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
                <p className="text-xs text-muted-foreground">
                  Binding to an event rejects tickets issued for other events.
                </p>
              </div>

              {scannerOn ? (
                <QrScanner onScan={handleScan} active={scannerOn} />
              ) : (
                <Button className="w-full" onClick={() => setScannerOn(true)}>
                  <ScanLine className="h-4 w-4" />
                  Open scanner
                </Button>
              )}
              {scannerOn && (
                <Button variant="outline" className="w-full" onClick={() => setScannerOn(false)}>
                  Close scanner
                </Button>
              )}

              <form onSubmit={handleManual} className="space-y-2 border-t pt-4">
                <Label>Manual entry (QR token or paste)</Label>
                <div className="flex gap-2">
                  <Input
                    value={manual}
                    onChange={(e) => setManual(e.target.value)}
                    placeholder="Paste QR value or token…"
                  />
                  <Button type="submit" disabled={processing}>
                    <UserCheck className="h-4 w-4" />
                    Check in
                  </Button>
                </div>
              </form>
            </CardContent>
          </Card>

          {/* Last scan result */}
          {last && (
            <Card
              className={
                last.result.duplicate
                  ? 'border-amber-300 bg-amber-50/50'
                  : 'border-emerald-300 bg-emerald-50/50'
              }
            >
              <CardContent className="flex items-center gap-4 p-5">
                {last.result.duplicate ? (
                  <AlertTriangle className="h-10 w-10 text-amber-500" />
                ) : (
                  <CheckCircle2 className="h-10 w-10 text-emerald-600" />
                )}
                <div>
                  <p className="text-lg font-semibold">
                    {last.result.attendance.registration?.user?.name}
                  </p>
                  <p className="text-sm text-muted-foreground">
                    {last.result.attendance.registration?.registration_no} ·{' '}
                    {last.result.attendance.registration?.event?.title}
                  </p>
                  <p className="mt-1 text-sm font-medium">
                    {last.result.duplicate ? 'Already checked in' : 'Checked in ✓'}
                  </p>
                </div>
              </CardContent>
            </Card>
          )}
        </div>

        {/* Recent scans feed */}
        <Card>
          <CardHeader>
            <CardTitle className="text-base">Recent scans</CardTitle>
          </CardHeader>
          <CardContent>
            {feed.length === 0 ? (
              <p className="py-12 text-center text-sm text-muted-foreground">
                Scanned check-ins will appear here.
              </p>
            ) : (
              <div className="space-y-2">
                {feed.map((f) => (
                  <div
                    key={f.id}
                    className="flex items-center justify-between rounded-md border p-3 text-sm"
                  >
                    <div className="flex items-center gap-3">
                      {f.ok ? (
                        f.duplicate ? (
                          <AlertTriangle className="h-4 w-4 text-amber-500" />
                        ) : (
                          <CheckCircle2 className="h-4 w-4 text-emerald-600" />
                        )
                      ) : (
                        <XCircle className="h-4 w-4 text-destructive" />
                      )}
                      <div>
                        <p className="font-medium">{f.name}</p>
                        <p className="text-xs text-muted-foreground">
                          {f.ok ? f.regNo : f.message}
                        </p>
                      </div>
                    </div>
                    <span className="text-xs text-muted-foreground">
                      {f.time ? formatDateTime(f.time) : ''}
                    </span>
                  </div>
                ))}
              </div>
            )}
          </CardContent>
        </Card>
      </div>
    </div>
  )
}
