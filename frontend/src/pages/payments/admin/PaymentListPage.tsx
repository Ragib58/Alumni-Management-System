import { useCallback, useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { Search, Eye, BarChart3 } from 'lucide-react'
import { paymentService } from '@/services/payment.service'
import { extractApiError } from '@/lib/api'
import { useToast } from '@/components/ui/toast'
import { useDebounce } from '@/hooks/useDebounce'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Card, CardContent } from '@/components/ui/card'
import { Spinner } from '@/components/ui/spinner'
import { Pagination } from '@/components/common/Pagination'
import { PaymentStatusBadge } from '@/components/common/EventStatusBadge'
import { formatCurrency, formatDateTime } from '@/lib/utils'
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
import type { PaginationMeta, Payment, PaymentFilters, PaymentGateway, PaymentStatus } from '@/types'

export function PaymentListPage() {
  const { toast } = useToast()

  const [rows, setRows] = useState<Payment[]>([])
  const [meta, setMeta] = useState<PaginationMeta | null>(null)
  const [loading, setLoading] = useState(true)

  const [search, setSearch] = useState('')
  const debouncedSearch = useDebounce(search)
  const [status, setStatus] = useState<PaymentStatus | 'all'>('all')
  const [gateway, setGateway] = useState<PaymentGateway | 'all'>('all')
  const [page, setPage] = useState(1)

  const fetchPayments = useCallback(async () => {
    setLoading(true)
    try {
      const filters: PaymentFilters = {
        search: debouncedSearch || undefined,
        status: status === 'all' ? undefined : status,
        gateway: gateway === 'all' ? undefined : gateway,
        page,
        per_page: 15,
      }
      const res = await paymentService.adminList(filters)
      setRows(res.data)
      setMeta(res.meta)
    } catch (e) {
      toast({ title: 'Failed to load payments', description: extractApiError(e).message, variant: 'error' })
    } finally {
      setLoading(false)
    }
  }, [debouncedSearch, status, gateway, page, toast])

  useEffect(() => {
    void fetchPayments()
  }, [fetchPayments])

  useEffect(() => {
    setPage(1)
  }, [debouncedSearch, status, gateway])

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold">Payments</h1>
          <p className="text-muted-foreground">All transactions across events.</p>
        </div>
        <Link to="/admin/revenue">
          <Button variant="outline">
            <BarChart3 className="h-4 w-4" />
            Revenue dashboard
          </Button>
        </Link>
      </div>

      <div className="flex flex-col gap-3 sm:flex-row">
        <div className="relative flex-1">
          <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
          <Input
            placeholder="Search transaction, reg no, name or email…"
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            className="pl-9"
          />
        </div>
        <Select value={status} onValueChange={(v) => setStatus(v as PaymentStatus | 'all')}>
          <SelectTrigger className="w-full sm:w-40">
            <SelectValue placeholder="Status" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">All statuses</SelectItem>
            <SelectItem value="pending">Pending</SelectItem>
            <SelectItem value="paid">Paid</SelectItem>
            <SelectItem value="failed">Failed</SelectItem>
            <SelectItem value="refunded">Refunded</SelectItem>
          </SelectContent>
        </Select>
        <Select value={gateway} onValueChange={(v) => setGateway(v as PaymentGateway | 'all')}>
          <SelectTrigger className="w-full sm:w-40">
            <SelectValue placeholder="Gateway" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">All gateways</SelectItem>
            <SelectItem value="sslcommerz">SSLCommerz</SelectItem>
            <SelectItem value="bkash">bKash</SelectItem>
            <SelectItem value="nagad">Nagad</SelectItem>
          </SelectContent>
        </Select>
      </div>

      <Card>
        <CardContent className="p-0">
          {loading ? (
            <div className="flex h-64 items-center justify-center">
              <Spinner className="h-8 w-8" />
            </div>
          ) : rows.length === 0 ? (
            <p className="py-16 text-center text-sm text-muted-foreground">No payments found.</p>
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Transaction</TableHead>
                  <TableHead>Payer</TableHead>
                  <TableHead>Event</TableHead>
                  <TableHead>Gateway</TableHead>
                  <TableHead>Amount</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead>Date</TableHead>
                  <TableHead className="text-right">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {rows.map((p) => (
                  <TableRow key={p.id}>
                    <TableCell className="font-mono text-xs">{p.transaction_id}</TableCell>
                    <TableCell>
                      <p className="font-medium">{p.registration?.user?.name ?? '—'}</p>
                      <p className="text-xs text-muted-foreground">{p.registration?.user?.email}</p>
                    </TableCell>
                    <TableCell className="text-muted-foreground">
                      {p.registration?.event?.title ?? '—'}
                    </TableCell>
                    <TableCell>{p.gateway_label}</TableCell>
                    <TableCell className="font-medium">{formatCurrency(p.amount)}</TableCell>
                    <TableCell>
                      <PaymentStatusBadge status={p.status} />
                    </TableCell>
                    <TableCell className="text-muted-foreground">
                      {formatDateTime(p.payment_date)}
                    </TableCell>
                    <TableCell className="text-right">
                      <Link to={`/admin/payments/${p.id}`}>
                        <Button variant="ghost" size="sm">
                          <Eye className="h-4 w-4" />
                          View
                        </Button>
                      </Link>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          )}
        </CardContent>
      </Card>

      {meta && <Pagination meta={meta} onPageChange={setPage} />}
    </div>
  )
}
