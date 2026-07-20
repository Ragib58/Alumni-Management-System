import { useCallback, useEffect, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { Bell, Check, CheckCheck, Trash2 } from 'lucide-react'
import { notificationService } from '@/services/notification.service'
import { extractApiError } from '@/lib/api'
import { useToast } from '@/components/ui/toast'
import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'
import { Spinner } from '@/components/ui/spinner'
import { Pagination } from '@/components/common/Pagination'
import { cn, formatDateTime } from '@/lib/utils'
import type { AppNotification, PaginationMeta } from '@/types'

export function NotificationsPage() {
  const { toast } = useToast()
  const navigate = useNavigate()
  const [rows, setRows] = useState<AppNotification[]>([])
  const [meta, setMeta] = useState<PaginationMeta | null>(null)
  const [loading, setLoading] = useState(true)
  const [page, setPage] = useState(1)

  const fetchList = useCallback(async () => {
    setLoading(true)
    try {
      const res = await notificationService.list(page)
      setRows(res.data)
      setMeta(res.meta)
    } catch (e) {
      toast({ title: 'Failed to load', description: extractApiError(e).message, variant: 'error' })
    } finally {
      setLoading(false)
    }
  }, [page, toast])

  useEffect(() => {
    void fetchList()
  }, [fetchList])

  const open = async (n: AppNotification) => {
    if (!n.read) await notificationService.markRead(n.id)
    if (n.url) navigate(n.url)
    else void fetchList()
  }

  const markAll = async () => {
    await notificationService.markAllRead()
    toast({ title: 'All marked as read', variant: 'success' })
    void fetchList()
  }

  const remove = async (id: string) => {
    await notificationService.remove(id)
    void fetchList()
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold">Notifications</h1>
          <p className="text-muted-foreground">Your registration, payment and event updates.</p>
        </div>
        <Button variant="outline" onClick={markAll}>
          <CheckCheck className="h-4 w-4" />
          Mark all read
        </Button>
      </div>

      {loading ? (
        <div className="flex h-64 items-center justify-center">
          <Spinner className="h-8 w-8" />
        </div>
      ) : rows.length === 0 ? (
        <Card>
          <CardContent className="flex flex-col items-center gap-3 py-16 text-center">
            <Bell className="h-10 w-10 text-muted-foreground" />
            <p className="text-sm text-muted-foreground">You have no notifications.</p>
          </CardContent>
        </Card>
      ) : (
        <div className="space-y-2">
          {rows.map((n) => (
            <Card key={n.id} className={cn(!n.read && 'border-primary/40 bg-primary/5')}>
              <CardContent className="flex items-start gap-3 p-4">
                <button onClick={() => open(n)} className="flex flex-1 items-start gap-3 text-left">
                  <div className={cn('mt-1.5 h-2 w-2 shrink-0 rounded-full', n.read ? 'bg-muted-foreground/30' : 'bg-primary')} />
                  <div>
                    <p className="font-medium">{n.title}</p>
                    <p className="text-sm text-muted-foreground">{n.message}</p>
                    <p className="mt-1 text-xs text-muted-foreground">{formatDateTime(n.created_at)}</p>
                  </div>
                </button>
                <div className="flex items-center gap-1">
                  {n.read && <Check className="h-4 w-4 text-muted-foreground" />}
                  <Button
                    variant="ghost"
                    size="icon"
                    className="text-muted-foreground hover:text-destructive"
                    onClick={() => remove(n.id)}
                  >
                    <Trash2 className="h-4 w-4" />
                  </Button>
                </div>
              </CardContent>
            </Card>
          ))}
        </div>
      )}

      {meta && <Pagination meta={meta} onPageChange={setPage} />}
    </div>
  )
}
