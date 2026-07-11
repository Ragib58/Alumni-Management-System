import { Link } from 'react-router-dom'
import { ShieldAlert } from 'lucide-react'
import { Button } from '@/components/ui/button'

export function ForbiddenPage() {
  return (
    <div className="flex min-h-screen flex-col items-center justify-center gap-4 p-6 text-center">
      <ShieldAlert className="h-16 w-16 text-destructive" />
      <p className="text-4xl font-bold">403</p>
      <h1 className="text-2xl font-semibold">Access denied</h1>
      <p className="max-w-sm text-muted-foreground">
        You don&apos;t have permission to view this page.
      </p>
      <Link to="/directory">
        <Button variant="outline">Go to directory</Button>
      </Link>
    </div>
  )
}
