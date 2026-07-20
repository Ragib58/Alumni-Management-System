import { useState } from 'react'
import { useNavigate, useSearchParams } from 'react-router-dom'
import { Lock, CheckCircle2, XCircle } from 'lucide-react'
import { paymentService } from '@/services/payment.service'
import { extractApiError } from '@/lib/api'
import { Button } from '@/components/ui/button'
import { Spinner } from '@/components/ui/spinner'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { formatCurrency } from '@/lib/utils'

const GATEWAY_LABELS: Record<string, string> = {
  sslcommerz: 'SSLCommerz',
  bkash: 'bKash',
  nagad: 'Nagad',
}

/**
 * Simulated gateway checkout page (PAYMENT_MODE=sandbox). Mimics a hosted
 * gateway: the user confirms or cancels, and we settle the payment server-side.
 */
export function SandboxGatewayPage() {
  const [params] = useSearchParams()
  const navigate = useNavigate()

  const paymentId = Number(params.get('payment'))
  const gateway = params.get('gateway') ?? 'sslcommerz'
  const token = params.get('token') ?? ''
  const amount = Number(params.get('amount') ?? 0)
  const transaction = params.get('transaction') ?? ''

  const [processing, setProcessing] = useState<'success' | 'failed' | null>(null)
  const [error, setError] = useState<string | null>(null)

  const settle = async (outcome: 'success' | 'failed') => {
    setProcessing(outcome)
    setError(null)
    try {
      const payment = await paymentService.sandboxComplete(paymentId, token, outcome)
      if (payment.status === 'paid') {
        navigate(`/payment/success?transaction=${payment.transaction_id}`, { replace: true })
      } else {
        navigate(`/payment/failed?transaction=${payment.transaction_id}`, { replace: true })
      }
    } catch (e) {
      setError(extractApiError(e).message)
      setProcessing(null)
    }
  }

  return (
    <div className="flex min-h-screen items-center justify-center bg-muted/40 p-4">
      <Card className="w-full max-w-md">
        <CardHeader className="items-center text-center">
          <div className="mb-1 flex items-center gap-2 text-sm font-medium text-muted-foreground">
            <Lock className="h-4 w-4" />
            Secure Sandbox Checkout
          </div>
          <CardTitle className="text-xl">{GATEWAY_LABELS[gateway] ?? gateway}</CardTitle>
        </CardHeader>
        <CardContent className="space-y-5">
          <div className="rounded-lg border bg-background p-4 text-sm">
            <div className="flex justify-between py-1">
              <span className="text-muted-foreground">Merchant</span>
              <span className="font-medium">Alumni Event Management</span>
            </div>
            <div className="flex justify-between py-1">
              <span className="text-muted-foreground">Transaction</span>
              <span className="font-mono text-xs">{transaction}</span>
            </div>
            <div className="mt-2 flex justify-between border-t pt-3 text-base">
              <span className="font-semibold">Total</span>
              <span className="font-bold">{formatCurrency(amount)}</span>
            </div>
          </div>

          {error && (
            <div className="rounded-md bg-destructive/10 px-3 py-2 text-sm text-destructive">
              {error}
            </div>
          )}

          <p className="text-center text-xs text-muted-foreground">
            This is a simulated gateway for local testing. Choose an outcome:
          </p>

          <div className="grid gap-2">
            <Button onClick={() => settle('success')} disabled={processing !== null}>
              {processing === 'success' ? (
                <Spinner className="h-4 w-4 text-primary-foreground" />
              ) : (
                <CheckCircle2 className="h-4 w-4" />
              )}
              Pay {formatCurrency(amount)} (Success)
            </Button>
            <Button
              variant="outline"
              onClick={() => settle('failed')}
              disabled={processing !== null}
              className="text-destructive"
            >
              {processing === 'failed' ? <Spinner className="h-4 w-4" /> : <XCircle className="h-4 w-4" />}
              Cancel / Fail payment
            </Button>
          </div>
        </CardContent>
      </Card>
    </div>
  )
}
