import { useEffect, useState } from 'react'
import { FileSpreadsheet, FileText, FileDown, Loader2 } from 'lucide-react'
import { reportService } from '@/services/report.service'
import { extractApiError } from '@/lib/api'
import { useToast } from '@/components/ui/toast'
import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'
import { Spinner } from '@/components/ui/spinner'
import { cn, formatCurrency } from '@/lib/utils'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import type {
  AlumniReport,
  EventReportRow,
  ExportFormat,
  FinancialReport,
  ReportType,
} from '@/types'

const TABS: { key: ReportType; label: string }[] = [
  { key: 'event', label: 'Event Report' },
  { key: 'financial', label: 'Financial Report' },
  { key: 'alumni', label: 'Alumni Report' },
]

export function ReportsPage() {
  const { toast } = useToast()
  const [tab, setTab] = useState<ReportType>('event')
  const [loading, setLoading] = useState(true)
  const [exporting, setExporting] = useState<ExportFormat | null>(null)

  const [eventRows, setEventRows] = useState<EventReportRow[]>([])
  const [financial, setFinancial] = useState<FinancialReport | null>(null)
  const [alumni, setAlumni] = useState<AlumniReport | null>(null)

  useEffect(() => {
    setLoading(true)
    const load = async () => {
      try {
        if (tab === 'event') setEventRows(await reportService.event())
        else if (tab === 'financial') setFinancial(await reportService.financial())
        else setAlumni(await reportService.alumni())
      } catch (e) {
        toast({ title: 'Failed to load report', description: extractApiError(e).message, variant: 'error' })
      } finally {
        setLoading(false)
      }
    }
    void load()
  }, [tab, toast])

  const doExport = async (format: ExportFormat) => {
    setExporting(format)
    try {
      await reportService.export(tab, format)
      toast({ title: 'Export ready', description: `${tab} report (${format})`, variant: 'success' })
    } catch (e) {
      toast({ title: 'Export failed', description: extractApiError(e).message, variant: 'error' })
    } finally {
      setExporting(null)
    }
  }

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold">Reports</h1>
          <p className="text-muted-foreground">Event, financial and alumni participation reports.</p>
        </div>
        <div className="flex gap-2">
          <Button variant="outline" onClick={() => doExport('excel')} disabled={exporting !== null}>
            {exporting === 'excel' ? <Loader2 className="h-4 w-4 animate-spin" /> : <FileSpreadsheet className="h-4 w-4" />}
            Excel
          </Button>
          <Button variant="outline" onClick={() => doExport('csv')} disabled={exporting !== null}>
            {exporting === 'csv' ? <Loader2 className="h-4 w-4 animate-spin" /> : <FileDown className="h-4 w-4" />}
            CSV
          </Button>
          <Button variant="outline" onClick={() => doExport('pdf')} disabled={exporting !== null}>
            {exporting === 'pdf' ? <Loader2 className="h-4 w-4 animate-spin" /> : <FileText className="h-4 w-4" />}
            PDF
          </Button>
        </div>
      </div>

      {/* Tabs */}
      <div className="flex gap-1 border-b">
        {TABS.map((t) => (
          <button
            key={t.key}
            onClick={() => setTab(t.key)}
            className={cn(
              'border-b-2 px-4 py-2 text-sm font-medium transition-colors',
              tab === t.key
                ? 'border-primary text-primary'
                : 'border-transparent text-muted-foreground hover:text-foreground',
            )}
          >
            {t.label}
          </button>
        ))}
      </div>

      {loading ? (
        <div className="flex h-64 items-center justify-center">
          <Spinner className="h-8 w-8" />
        </div>
      ) : (
        <Card>
          <CardContent className="p-0">
            {tab === 'event' && <EventTable rows={eventRows} />}
            {tab === 'financial' && financial && <FinancialTable report={financial} />}
            {tab === 'alumni' && alumni && <AlumniTables report={alumni} />}
          </CardContent>
        </Card>
      )}
    </div>
  )
}

function EventTable({ rows }: { rows: EventReportRow[] }) {
  if (rows.length === 0) return <Empty />
  return (
    <Table>
      <TableHeader>
        <TableRow>
          <TableHead>Event</TableHead>
          <TableHead>Type</TableHead>
          <TableHead>Date</TableHead>
          <TableHead>Registrations</TableHead>
          <TableHead>Attendance</TableHead>
          <TableHead>Rate</TableHead>
          <TableHead>Revenue</TableHead>
        </TableRow>
      </TableHeader>
      <TableBody>
        {rows.map((r, i) => (
          <TableRow key={i}>
            <TableCell className="font-medium">{r.event}</TableCell>
            <TableCell className="text-muted-foreground">{r.type}</TableCell>
            <TableCell className="text-muted-foreground">{r.date ?? '—'}</TableCell>
            <TableCell>{r.registrations}</TableCell>
            <TableCell>{r.attendance}</TableCell>
            <TableCell>{r.attendance_rate}%</TableCell>
            <TableCell className="font-medium">{formatCurrency(r.revenue)}</TableCell>
          </TableRow>
        ))}
      </TableBody>
    </Table>
  )
}

function FinancialTable({ report }: { report: FinancialReport }) {
  return (
    <div>
      <div className="grid gap-4 border-b p-4 sm:grid-cols-3">
        <Summary label="Total Revenue" value={formatCurrency(report.summary.total_revenue)} />
        <Summary label="Total Refunds" value={formatCurrency(report.summary.total_refunds)} />
        <Summary label="Transactions" value={String(report.summary.total_transactions)} />
      </div>
      {report.transactions.length === 0 ? (
        <Empty />
      ) : (
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Transaction</TableHead>
              <TableHead>Gateway</TableHead>
              <TableHead>Event</TableHead>
              <TableHead>Payer</TableHead>
              <TableHead>Amount</TableHead>
              <TableHead>Status</TableHead>
              <TableHead>Date</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {report.transactions.map((t, i) => (
              <TableRow key={i}>
                <TableCell className="font-mono text-xs">{t.transaction_id}</TableCell>
                <TableCell>{t.gateway}</TableCell>
                <TableCell className="text-muted-foreground">{t.event ?? '—'}</TableCell>
                <TableCell>{t.payer ?? '—'}</TableCell>
                <TableCell className="font-medium">{formatCurrency(t.amount)}</TableCell>
                <TableCell>{t.status}</TableCell>
                <TableCell className="text-muted-foreground">{t.date ?? '—'}</TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      )}
    </div>
  )
}

function AlumniTables({ report }: { report: AlumniReport }) {
  return (
    <div className="grid gap-6 p-4 lg:grid-cols-2">
      <ParticipationTable title="Batch-wise Participation" heading="Batch" rows={report.by_batch} />
      <ParticipationTable title="Department-wise Participation" heading="Department" rows={report.by_department} />
    </div>
  )
}

function ParticipationTable({
  title,
  heading,
  rows,
}: {
  title: string
  heading: string
  rows: AlumniReport['by_batch']
}) {
  return (
    <div>
      <h3 className="mb-2 font-semibold">{title}</h3>
      {rows.length === 0 ? (
        <p className="py-8 text-center text-sm text-muted-foreground">No data.</p>
      ) : (
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>{heading}</TableHead>
              <TableHead>Participants</TableHead>
              <TableHead>Registrations</TableHead>
              <TableHead>Attendance</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {rows.map((r, i) => (
              <TableRow key={i}>
                <TableCell className="font-medium">{r.group}</TableCell>
                <TableCell>{r.participants}</TableCell>
                <TableCell>{r.registrations}</TableCell>
                <TableCell>{r.attendance}</TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      )}
    </div>
  )
}

function Summary({ label, value }: { label: string; value: string }) {
  return (
    <div className="rounded-lg border p-3">
      <p className="text-xs text-muted-foreground">{label}</p>
      <p className="text-lg font-bold">{value}</p>
    </div>
  )
}

function Empty() {
  return <p className="py-16 text-center text-sm text-muted-foreground">No data available.</p>
}
