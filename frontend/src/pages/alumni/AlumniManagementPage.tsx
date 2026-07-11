import { useCallback, useEffect, useState } from 'react'
import { Search, Pencil } from 'lucide-react'
import { alumniService } from '@/services/alumni.service'
import { extractApiError } from '@/lib/api'
import { useToast } from '@/components/ui/toast'
import { useDebounce } from '@/hooks/useDebounce'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Card, CardContent } from '@/components/ui/card'
import { Spinner } from '@/components/ui/spinner'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Badge } from '@/components/ui/badge'
import { Pagination } from '@/components/common/Pagination'
import { ProfileForm } from '@/components/common/ProfileForm'
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
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import type { AlumniFilters, AlumniProfile, PaginationMeta, ProfilePayload } from '@/types'

export function AlumniManagementPage() {
  const { toast } = useToast()

  const [rows, setRows] = useState<AlumniProfile[]>([])
  const [meta, setMeta] = useState<PaginationMeta | null>(null)
  const [loading, setLoading] = useState(true)

  const [search, setSearch] = useState('')
  const debouncedSearch = useDebounce(search)
  const [page, setPage] = useState(1)

  const [editing, setEditing] = useState<AlumniProfile | null>(null)

  const fetchAlumni = useCallback(async () => {
    setLoading(true)
    try {
      const filters: AlumniFilters = {
        search: debouncedSearch || undefined,
        page,
        per_page: 10,
      }
      const res = await alumniService.directory(filters)
      setRows(res.data)
      setMeta(res.meta)
    } catch (e) {
      toast({ title: 'Failed to load alumni', description: extractApiError(e).message, variant: 'error' })
    } finally {
      setLoading(false)
    }
  }, [debouncedSearch, page, toast])

  useEffect(() => {
    void fetchAlumni()
  }, [fetchAlumni])

  useEffect(() => {
    setPage(1)
  }, [debouncedSearch])

  const handleUpdate = async (payload: ProfilePayload) => {
    if (!editing) return
    await alumniService.updateProfile(editing.id, payload)
    toast({ title: 'Alumni profile updated', variant: 'success' })
    setEditing(null)
    void fetchAlumni()
  }

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold">Alumni Management</h1>
        <p className="text-muted-foreground">Review and edit alumni profiles.</p>
      </div>

      <div className="relative max-w-md">
        <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
        <Input
          placeholder="Search alumni…"
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          className="pl-9"
        />
      </div>

      <Card>
        <CardContent className="p-0">
          {loading ? (
            <div className="flex h-64 items-center justify-center">
              <Spinner className="h-8 w-8" />
            </div>
          ) : rows.length === 0 ? (
            <p className="py-16 text-center text-sm text-muted-foreground">No alumni found.</p>
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Alumnus</TableHead>
                  <TableHead>Batch</TableHead>
                  <TableHead>Department</TableHead>
                  <TableHead>Profession</TableHead>
                  <TableHead className="text-right">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {rows.map((p) => (
                  <TableRow key={p.id}>
                    <TableCell>
                      <div className="flex items-center gap-3">
                        <Avatar className="h-9 w-9">
                          <AvatarImage src={p.profile_photo_url ?? undefined} />
                          <AvatarFallback>{getInitials(p.user?.name ?? 'A')}</AvatarFallback>
                        </Avatar>
                        <div>
                          <p className="font-medium">{p.user?.name}</p>
                          <p className="text-xs text-muted-foreground">{p.user?.email}</p>
                        </div>
                      </div>
                    </TableCell>
                    <TableCell>
                      {p.batch ? <Badge variant="secondary">{p.batch}</Badge> : '—'}
                    </TableCell>
                    <TableCell>{p.department ?? '—'}</TableCell>
                    <TableCell className="text-muted-foreground">{p.profession ?? '—'}</TableCell>
                    <TableCell className="text-right">
                      <Button variant="ghost" size="sm" onClick={() => setEditing(p)}>
                        <Pencil className="h-4 w-4" />
                        Edit
                      </Button>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          )}
        </CardContent>
      </Card>

      {meta && <Pagination meta={meta} onPageChange={setPage} />}

      <Dialog open={!!editing} onOpenChange={(o) => !o && setEditing(null)}>
        <DialogContent className="max-w-2xl">
          <DialogHeader>
            <DialogTitle>Edit alumni profile</DialogTitle>
            <DialogDescription>{editing?.user?.name}</DialogDescription>
          </DialogHeader>
          {editing && (
            <ProfileForm
              initial={editing}
              displayName={editing.user?.name ?? 'A'}
              onSubmit={handleUpdate}
              submitLabel="Update profile"
            />
          )}
        </DialogContent>
      </Dialog>
    </div>
  )
}
