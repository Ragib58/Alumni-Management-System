import { useEffect, useState } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import { ArrowLeft, RotateCcw } from 'lucide-react'
import { paymentService } from '@/services/payment.service'
import { extractApiError } from '@/lib/api'
import { useToast } from '@/components/ui/toast'
import { Button } from '@/components/ui/button'
import { Spinner } from '@/components/ui/spinner'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import {
  PaymentStatusBadge,
  RegistrationStatusBadge,
} from '@/components/common/EventStatusBadge'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { formatCurrency, formatDateTime } from '@/lib/utils'
import type { Payment } from '@/types'

export function TransactionDetailsPage() {
  const { id } = useParams()
  const navigate = useNavigate()
  const { toast } = useToast()

  const [payment, setPayment] = useState<Payment | null>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [refunding, setRefunding] = useState(false)
  const [confirmRefund, setConfirmRefund] = useState(false)

  const load = () => {
    if (!id) return
    setLoading(true)
    paymentService
      .adminGet(Number(id))
      .then(setPayment)
      .catch((e) => setError(extractApiError(e).message))
      .finally(() => setLoading(false))
  }

  useEffect(load, [id])

  const refund = async () => {
    if (!payment) return
    setRefunding(true)
    try {
      const updated = await paymentService.refund(payment.id)
      setPayment(updated)
      setConfirmRefund(false)
      toast({ title: 'Payment refunded', variant: 'success' })
    } catch (e) {
      toast({ title: 'Refund failed', description: extractApiError(e).message, variant: 'error' })
    } finally {
      setRefunding(false)
    }
  }

  if (loading) {
    return (
      <div className="flex h-64 items-center justify-center">
        <Spinner className="h-8 w-8" />
      </div>
    )
  }

  if (error || !payment) {
    return (
      <div className="space-y-4">
        <p className="text-destructive">{error ?? 'Payment not found.'}</p>
        <Button variant="outline" onClick={() => navigate('/admin/payments')}>
          <ArrowLeft className="h-4 w-4" />
          Back to payments
        </Button>
      </div>
    )
  }

  const reg = payment.registration
  const rows: [string, React.ReactNode][] = [
    ['Transaction ID', <span className="font-mono">{payment.transaction_id}</span>],
    ['Gateway reference', payment.gateway_transaction_id ?? '—'],
    ['Gateway', payment.gateway_label],
    ['Amount', formatCurrency(payment.amount)],
    ['Currency', payment.currency],
    ['Paid at', formatDateTime(payment.payment_date)],
    ['Registration No.', reg?.registration_no ?? '—'],
    ['Event', reg?.event?.title ?? '—'],
    ['Payer', reg?.user ? `${reg.user.name} (${reg.user.email})` : '—'],
  ]

  return (
    <div className="mx-auto max-w-2xl space-y-6">
      <div className="flex items-center justify-between">
        <Button variant="ghost" size="sm" onClick={() => navigate('/admin/payments')}>
          <ArrowLeft className="h-4 w-4" />
          Back to payments
        </Button>
        {payment.status === 'paid' && (
          <Button variant="outline" onClick={() => setConfirmRefund(true)}>
            <RotateCcw className="h-4 w-4" />
            Refund
          </Button>
        )}
      </div>

      <Card>
        <CardHeader>
          <div className="flex items-center justify-between">
            <CardTitle>Transaction details</CardTitle>
            <div className="flex items-center gap-2">
              <PaymentStatusBadge status={payment.status} />
              {reg && <RegistrationStatusBadge status={reg.status} />}
            </div>
          </div>
        </CardHeader>
        <CardContent>
          <dl className="divide-y">
            {rows.map(([label, value]) => (
              <div key={label} className="flex items-center justify-between py-3 text-sm">
                <dt className="text-muted-foreground">{label}</dt>
                <dd className="font-medium text-right">{value}</dd>
              </div>
            ))}
          </dl>
        </CardContent>
      </Card>

      <Dialog open={confirmRefund} onOpenChange={setConfirmRefund}>
        <DialogContent className="max-w-sm">
          <DialogHeader>
            <DialogTitle>Refund payment</DialogTitle>
            <DialogDescription>
              Refund {formatCurrency(payment.amount)} for {reg?.registration_no}? This will cancel
              the registration.
            </DialogDescription>
          </DialogHeader>
          <DialogFooter>
            <Button variant="outline" onClick={() => setConfirmRefund(false)}>
              Cancel
            </Button>
            <Button variant="destructive" onClick={refund} disabled={refunding}>
              {refunding && <Spinner className="h-4 w-4 text-destructive-foreground" />}
              Confirm refund
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  )
}
