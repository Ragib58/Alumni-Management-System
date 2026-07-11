import { useCallback, useEffect, useState } from 'react'
import { Plus, Search, Pencil, Trash2, MoreHorizontal } from 'lucide-react'
import { userService } from '@/services/user.service'
import { extractApiError } from '@/lib/api'
import { useAuth } from '@/hooks/useAuth'
import { useToast } from '@/components/ui/toast'
import { useDebounce } from '@/hooks/useDebounce'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Card, CardContent } from '@/components/ui/card'
import { Spinner } from '@/components/ui/spinner'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { StatusBadge } from '@/components/common/StatusBadge'
import { Pagination } from '@/components/common/Pagination'
import { getInitials } from '@/lib/utils'
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
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { UserFormDialog } from './UserFormDialog'
import type { PaginationMeta, RoleName, User, UserFilters, UserStatus } from '@/types'

const ROLE_LABELS: Record<RoleName, string> = {
  super_admin: 'Super Admin',
  event_manager: 'Event Manager',
  alumni_member: 'Alumni Member',
  guest: 'Guest',
}

export function UsersPage() {
  const { hasRole, user: currentUser } = useAuth()
  const { toast } = useToast()
  const canManage = hasRole('super_admin')

  const [rows, setRows] = useState<User[]>([])
  const [meta, setMeta] = useState<PaginationMeta | null>(null)
  const [loading, setLoading] = useState(true)

  const [search, setSearch] = useState('')
  const debouncedSearch = useDebounce(search)
  const [status, setStatus] = useState<UserStatus | 'all'>('all')
  const [role, setRole] = useState<RoleName | 'all'>('all')
  const [page, setPage] = useState(1)

  const [formOpen, setFormOpen] = useState(false)
  const [editing, setEditing] = useState<User | null>(null)
  const [deleting, setDeleting] = useState<User | null>(null)
  const [deletePending, setDeletePending] = useState(false)

  const fetchUsers = useCallback(async () => {
    setLoading(true)
    try {
      const filters: UserFilters = {
        search: debouncedSearch || undefined,
        status: status === 'all' ? undefined : status,
        role: role === 'all' ? undefined : role,
        page,
        per_page: 10,
      }
      const res = await userService.list(filters)
      setRows(res.data)
      setMeta(res.meta)
    } catch (e) {
      toast({ title: 'Failed to load users', description: extractApiError(e).message, variant: 'error' })
    } finally {
      setLoading(false)
    }
  }, [debouncedSearch, status, role, page, toast])

  useEffect(() => {
    void fetchUsers()
  }, [fetchUsers])

  useEffect(() => {
    setPage(1)
  }, [debouncedSearch, status, role])

  const handleStatusChange = async (u: User, next: UserStatus) => {
    try {
      await userService.updateStatus(u.id, next)
      toast({ title: 'Status updated', variant: 'success' })
      void fetchUsers()
    } catch (e) {
      toast({ title: 'Update failed', description: extractApiError(e).message, variant: 'error' })
    }
  }

  const handleDelete = async () => {
    if (!deleting) return
    setDeletePending(true)
    try {
      await userService.remove(deleting.id)
      toast({ title: 'User deleted', variant: 'success' })
      setDeleting(null)
      void fetchUsers()
    } catch (e) {
      toast({ title: 'Delete failed', description: extractApiError(e).message, variant: 'error' })
    } finally {
      setDeletePending(false)
    }
  }

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold">User Management</h1>
          <p className="text-muted-foreground">Manage accounts, roles and access.</p>
        </div>
        {canManage && (
          <Button
            onClick={() => {
              setEditing(null)
              setFormOpen(true)
            }}
          >
            <Plus className="h-4 w-4" />
            New user
          </Button>
        )}
      </div>

      {/* Filters */}
      <div className="flex flex-col gap-3 sm:flex-row">
        <div className="relative flex-1">
          <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
          <Input
            placeholder="Search by name, email or phone…"
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            className="pl-9"
          />
        </div>
        <Select value={status} onValueChange={(v) => setStatus(v as UserStatus | 'all')}>
          <SelectTrigger className="w-full sm:w-40">
            <SelectValue placeholder="Status" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">All statuses</SelectItem>
            <SelectItem value="active">Active</SelectItem>
            <SelectItem value="inactive">Inactive</SelectItem>
            <SelectItem value="suspended">Suspended</SelectItem>
          </SelectContent>
        </Select>
        <Select value={role} onValueChange={(v) => setRole(v as RoleName | 'all')}>
          <SelectTrigger className="w-full sm:w-44">
            <SelectValue placeholder="Role" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">All roles</SelectItem>
            <SelectItem value="super_admin">Super Admin</SelectItem>
            <SelectItem value="event_manager">Event Manager</SelectItem>
            <SelectItem value="alumni_member">Alumni Member</SelectItem>
            <SelectItem value="guest">Guest</SelectItem>
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
            <p className="py-16 text-center text-sm text-muted-foreground">No users found.</p>
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>User</TableHead>
                  <TableHead>Role</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead>Phone</TableHead>
                  <TableHead className="text-right">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {rows.map((u) => (
                  <TableRow key={u.id}>
                    <TableCell>
                      <div className="flex items-center gap-3">
                        <Avatar className="h-9 w-9">
                          <AvatarImage src={u.profile?.profile_photo_url ?? undefined} />
                          <AvatarFallback>{getInitials(u.name)}</AvatarFallback>
                        </Avatar>
                        <div>
                          <p className="font-medium">{u.name}</p>
                          <p className="text-xs text-muted-foreground">{u.email}</p>
                        </div>
                      </div>
                    </TableCell>
                    <TableCell>
                      {u.roles.map((r) => ROLE_LABELS[r] ?? r).join(', ') || '—'}
                    </TableCell>
                    <TableCell>
                      <StatusBadge status={u.status} />
                    </TableCell>
                    <TableCell className="text-muted-foreground">{u.phone ?? '—'}</TableCell>
                    <TableCell className="text-right">
                      <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                          <Button variant="ghost" size="icon">
                            <MoreHorizontal className="h-4 w-4" />
                          </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                          <DropdownMenuItem
                            onClick={() => {
                              setEditing(u)
                              setFormOpen(true)
                            }}
                          >
                            <Pencil className="h-4 w-4" />
                            Edit
                          </DropdownMenuItem>
                          {u.id !== currentUser?.id && (
                            <>
                              {u.status !== 'active' && (
                                <DropdownMenuItem onClick={() => handleStatusChange(u, 'active')}>
                                  Set Active
                                </DropdownMenuItem>
                              )}
                              {u.status !== 'suspended' && (
                                <DropdownMenuItem onClick={() => handleStatusChange(u, 'suspended')}>
                                  Suspend
                                </DropdownMenuItem>
                              )}
                            </>
                          )}
                          {canManage && u.id !== currentUser?.id && (
                            <>
                              <DropdownMenuSeparator />
                              <DropdownMenuItem
                                className="text-destructive focus:text-destructive"
                                onClick={() => setDeleting(u)}
                              >
                                <Trash2 className="h-4 w-4" />
                                Delete
                              </DropdownMenuItem>
                            </>
                          )}
                        </DropdownMenuContent>
                      </DropdownMenu>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          )}
        </CardContent>
      </Card>

      {meta && <Pagination meta={meta} onPageChange={setPage} />}

      <UserFormDialog
        open={formOpen}
        onOpenChange={setFormOpen}
        user={editing}
        onSaved={fetchUsers}
        canCreate={canManage}
      />

      {/* Delete confirm */}
      <Dialog open={!!deleting} onOpenChange={(o) => !o && setDeleting(null)}>
        <DialogContent className="max-w-sm">
          <DialogHeader>
            <DialogTitle>Delete user</DialogTitle>
            <DialogDescription>
              Are you sure you want to delete{' '}
              <span className="font-medium text-foreground">{deleting?.name}</span>? This action can
              be reversed by an administrator (soft delete).
            </DialogDescription>
          </DialogHeader>
          <DialogFooter>
            <Button variant="outline" onClick={() => setDeleting(null)}>
              Cancel
            </Button>
            <Button variant="destructive" onClick={handleDelete} disabled={deletePending}>
              {deletePending && <Spinner className="h-4 w-4 text-destructive-foreground" />}
              Delete
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  )
}
