import { useEffect, useState } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import { ArrowLeft, CreditCard, ShieldCheck } from 'lucide-react'
import { registrationService } from '@/services/registration.service'
import { paymentService } from '@/services/payment.service'
import { extractApiError } from '@/lib/api'
import { useToast } from '@/components/ui/toast'
import { Button } from '@/components/ui/button'
import { Spinner } from '@/components/ui/spinner'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { formatCurrency, formatDateTime } from '@/lib/utils'
import type { EventRegistration, PaymentGateway } from '@/types'

const GATEWAYS: { key: PaymentGateway; label: string; hint: string; color: string }[] = [
  { key: 'sslcommerz', label: 'SSLCommerz', hint: 'Cards / Net Banking', color: 'bg-blue-600' },
  { key: 'bkash', label: 'bKash', hint: 'Mobile wallet', color: 'bg-pink-600' },
  { key: 'nagad', label: 'Nagad', hint: 'Mobile wallet', color: 'bg-orange-600' },
]

export function PaymentPage() {
  const { id } = useParams()
  const navigate = useNavigate()
  const { toast } = useToast()

  const [registration, setRegistration] = useState<EventRegistration | null>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [selected, setSelected] = useState<PaymentGateway>('sslcommerz')
  const [processing, setProcessing] = useState(false)

  useEffect(() => {
    if (!id) return
    registrationService
      .getOwn(Number(id))
      .then(setRegistration)
      .catch((e) => setError(extractApiError(e).message))
      .finally(() => setLoading(false))
  }, [id])

  const pay = async () => {
    if (!registration) return
    setProcessing(true)
    try {
      const result = await paymentService.initiate(registration.id, selected)
      // In both sandbox and live modes the gateway hands back a redirect URL.
      window.location.href = result.redirect_url
    } catch (e) {
      toast({ title: 'Payment failed to start', description: extractApiError(e).message, variant: 'error' })
      setProcessing(false)
    }
  }

  if (loading) {
    return (
      <div className="flex h-64 items-center justify-center">
        <Spinner className="h-8 w-8" />
      </div>
    )
  }

  if (error || !registration) {
    return (
      <div className="space-y-4">
        <p className="text-destructive">{error ?? 'Registration not found.'}</p>
        <Button variant="outline" onClick={() => navigate('/my-registrations')}>
          <ArrowLeft className="h-4 w-4" />
          My registrations
        </Button>
      </div>
    )
  }

  const alreadyPaid = registration.payment_status === 'paid'

  return (
    <div className="mx-auto max-w-lg space-y-6">
      <Button variant="ghost" size="sm" onClick={() => navigate('/my-registrations')}>
        <ArrowLeft className="h-4 w-4" />
        My registrations
      </Button>

      <Card>
        <CardHeader>
          <CardTitle>Complete your payment</CardTitle>
          <CardDescription>{registration.event?.title}</CardDescription>
        </CardHeader>
        <CardContent className="space-y-5">
          {/* Summary */}
          <div className="rounded-lg border p-4 text-sm">
            <div className="flex justify-between py-1">
              <span className="text-muted-foreground">Registration No.</span>
              <span className="font-mono font-medium">{registration.registration_no}</span>
            </div>
            <div className="flex justify-between py-1">
              <span className="text-muted-foreground">Event date</span>
              <span className="font-medium">{formatDateTime(registration.event?.event_date)}</span>
            </div>
            <div className="mt-2 flex justify-between border-t pt-3 text-base">
              <span className="font-semibold">Amount payable</span>
              <span className="font-bold">{formatCurrency(registration.amount)}</span>
            </div>
          </div>

          {alreadyPaid ? (
            <div className="rounded-md bg-emerald-50 px-3 py-2 text-sm text-emerald-700">
              This registration is already paid.
            </div>
          ) : (
            <>
              {/* Gateway selection */}
              <div className="space-y-2">
                <p className="text-sm font-medium">Choose a payment method</p>
                <div className="grid gap-2">
                  {GATEWAYS.map((g) => (
                    <button
                      key={g.key}
                      type="button"
                      onClick={() => setSelected(g.key)}
                      className={`flex items-center gap-3 rounded-lg border p-3 text-left transition-colors ${
                        selected === g.key ? 'border-primary ring-2 ring-primary/30' : 'hover:bg-accent'
                      }`}
                    >
                      <span className={`flex h-9 w-9 items-center justify-center rounded-md text-white ${g.color}`}>
                        <CreditCard className="h-4 w-4" />
                      </span>
                      <span className="flex-1">
                        <span className="block text-sm font-medium">{g.label}</span>
                        <span className="block text-xs text-muted-foreground">{g.hint}</span>
                      </span>
                      <span
                        className={`h-4 w-4 rounded-full border ${
                          selected === g.key ? 'border-primary bg-primary' : 'border-muted-foreground/40'
                        }`}
                      />
                    </button>
                  ))}
                </div>
              </div>

              <Button className="w-full" onClick={pay} disabled={processing}>
                {processing && <Spinner className="h-4 w-4 text-primary-foreground" />}
                Pay {formatCurrency(registration.amount)}
              </Button>

              <p className="flex items-center justify-center gap-1.5 text-xs text-muted-foreground">
                <ShieldCheck className="h-3.5 w-3.5" />
                Payments are processed securely by the selected gateway.
              </p>
            </>
          )}
        </CardContent>
      </Card>
    </div>
  )
}
