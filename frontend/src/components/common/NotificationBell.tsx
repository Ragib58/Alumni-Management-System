import { useCallback, useEffect, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { Bell, Check, CheckCheck } from 'lucide-react'
import { notificationService } from '@/services/notification.service'
import { Button } from '@/components/ui/button'
import { cn, formatDateTime } from '@/lib/utils'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import type { AppNotification } from '@/types'

export function NotificationBell() {
  const navigate = useNavigate()
  const [items, setItems] = useState<AppNotification[]>([])
  const [unread, setUnread] = useState(0)
  const [open, setOpen] = useState(false)

  const loadCount = useCallback(async () => {
    try {
      setUnread(await notificationService.unreadCount())
    } catch {
      // ignore
    }
  }, [])

  const loadList = useCallback(async () => {
    try {
      const res = await notificationService.list(1)
      setItems(res.data)
      setUnread(res.unreadCount)
    } catch {
      // ignore
    }
  }, [])

  // Poll unread count every 60s.
  useEffect(() => {
    void loadCount()
    const timer = setInterval(loadCount, 60000)
    return () => clearInterval(timer)
  }, [loadCount])

  useEffect(() => {
    if (open) void loadList()
  }, [open, loadList])

  const onItemClick = async (n: AppNotification) => {
    if (!n.read) {
      await notificationService.markRead(n.id)
      setUnread((c) => Math.max(0, c - 1))
      setItems((prev) => prev.map((i) => (i.id === n.id ? { ...i, read: true } : i)))
    }
    setOpen(false)
    if (n.url) navigate(n.url)
  }

  const markAll = async () => {
    await notificationService.markAllRead()
    setUnread(0)
    setItems((prev) => prev.map((i) => ({ ...i, read: true })))
  }

  return (
    <DropdownMenu open={open} onOpenChange={setOpen}>
      <DropdownMenuTrigger asChild>
        <button className="relative rounded-full p-2 text-muted-foreground hover:bg-accent hover:text-foreground">
          <Bell className="h-5 w-5" />
          {unread > 0 && (
            <span className="absolute right-1 top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-destructive px-1 text-[10px] font-bold text-destructive-foreground">
              {unread > 9 ? '9+' : unread}
            </span>
          )}
        </button>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="end" className="w-80 p-0">
        <div className="flex items-center justify-between border-b px-4 py-2.5">
          <span className="text-sm font-semibold">Notifications</span>
          {unread > 0 && (
            <Button variant="ghost" size="sm" className="h-7 text-xs" onClick={markAll}>
              <CheckCheck className="h-3.5 w-3.5" />
              Mark all read
            </Button>
          )}
        </div>
        <div className="max-h-96 overflow-y-auto">
          {items.length === 0 ? (
            <p className="px-4 py-8 text-center text-sm text-muted-foreground">
              No notifications yet.
            </p>
          ) : (
            items.map((n) => (
              <button
                key={n.id}
                onClick={() => onItemClick(n)}
                className={cn(
                  'flex w-full items-start gap-2 border-b px-4 py-3 text-left last:border-0 hover:bg-accent',
                  !n.read && 'bg-primary/5',
                )}
              >
                <div className={cn('mt-1.5 h-2 w-2 shrink-0 rounded-full', n.read ? 'bg-transparent' : 'bg-primary')} />
                <div className="min-w-0 flex-1">
                  <p className="text-sm font-medium">{n.title}</p>
                  <p className="line-clamp-2 text-xs text-muted-foreground">{n.message}</p>
                  <p className="mt-0.5 text-[11px] text-muted-foreground">{formatDateTime(n.created_at)}</p>
                </div>
                {n.read && <Check className="mt-1 h-3.5 w-3.5 text-muted-foreground" />}
              </button>
            ))
          )}
        </div>
      </DropdownMenuContent>
    </DropdownMenu>
  )
}
