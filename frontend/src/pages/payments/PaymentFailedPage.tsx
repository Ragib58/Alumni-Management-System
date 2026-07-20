import { Link, useSearchParams } from 'react-router-dom'
import { XCircle, RotateCcw } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'

export function PaymentFailedPage() {
  const [params] = useSearchParams()
  const transaction = params.get('transaction')

  return (
    <div className="flex min-h-screen items-center justify-center bg-muted/40 p-4">
      <Card className="w-full max-w-md">
        <CardHeader className="items-center text-center">
          <div className="mb-2 flex h-16 w-16 items-center justify-center rounded-full bg-red-100">
            <XCircle className="h-8 w-8 text-destructive" />
          </div>
          <CardTitle className="text-2xl">Payment failed</CardTitle>
        </CardHeader>
        <CardContent className="space-y-5 text-center">
          <p className="text-sm text-muted-foreground">
            Your payment was cancelled or could not be completed. Your registration is still
            pending — you can try paying again from My Registrations.
          </p>

          {transaction && (
            <div className="rounded-md border bg-background px-3 py-2 text-sm">
              <span className="text-muted-foreground">Transaction: </span>
              <span className="font-mono">{transaction}</span>
            </div>
          )}

          <div className="flex flex-col gap-2">
            <Link to="/my-registrations">
              <Button className="w-full">
                <RotateCcw className="h-4 w-4" />
                Try again
              </Button>
            </Link>
            <Link to="/events">
              <Button variant="outline" className="w-full">
                Browse events
              </Button>
            </Link>
          </div>
        </CardContent>
      </Card>
    </div>
  )
}
