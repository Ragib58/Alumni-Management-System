import { Link, useSearchParams } from 'react-router-dom'
import { CheckCircle2, Ticket } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'

export function PaymentSuccessPage() {
  const [params] = useSearchParams()
  const transaction = params.get('transaction')

  return (
    <div className="flex min-h-screen items-center justify-center bg-muted/40 p-4">
      <Card className="w-full max-w-md">
        <CardHeader className="items-center text-center">
          <div className="mb-2 flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100">
            <CheckCircle2 className="h-8 w-8 text-emerald-600" />
          </div>
          <CardTitle className="text-2xl">Payment successful</CardTitle>
        </CardHeader>
        <CardContent className="space-y-5 text-center">
          <p className="text-sm text-muted-foreground">
            Your registration is confirmed. We&apos;re generating your ticket with a QR code and
            emailing it to you — it will also appear under <strong>My Tickets</strong> shortly.
          </p>

          {transaction && (
            <div className="rounded-md border bg-background px-3 py-2 text-sm">
              <span className="text-muted-foreground">Transaction: </span>
              <span className="font-mono">{transaction}</span>
            </div>
          )}

          <div className="flex flex-col gap-2">
            <Link to="/my-tickets">
              <Button className="w-full">
                <Ticket className="h-4 w-4" />
                View my tickets
              </Button>
            </Link>
            <Link to="/my-registrations">
              <Button variant="outline" className="w-full">
                My registrations
              </Button>
            </Link>
          </div>
        </CardContent>
      </Card>
    </div>
  )
}
