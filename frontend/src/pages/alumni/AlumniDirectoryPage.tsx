import { useCallback, useEffect, useState } from 'react'
import { Search, SlidersHorizontal, X } from 'lucide-react'
import { alumniService } from '@/services/alumni.service'
import { extractApiError } from '@/lib/api'
import { useToast } from '@/components/ui/toast'
import { useDebounce } from '@/hooks/useDebounce'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'
import { Spinner } from '@/components/ui/spinner'
import { AlumniCard } from '@/components/common/AlumniCard'
import { Pagination } from '@/components/common/Pagination'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import type {
  AlumniFilterOptions,
  AlumniFilters,
  AlumniProfile,
  PaginationMeta,
} from '@/types'

const ALL = 'all'

export function AlumniDirectoryPage() {
  const { toast } = useToast()

  const [rows, setRows] = useState<AlumniProfile[]>([])
  const [meta, setMeta] = useState<PaginationMeta | null>(null)
  const [loading, setLoading] = useState(true)
  const [options, setOptions] = useState<AlumniFilterOptions>({
    batches: [],
    departments: [],
    sessions: [],
    professions: [],
  })

  const [search, setSearch] = useState('')
  const debouncedSearch = useDebounce(search)
  const [batch, setBatch] = useState<string>(ALL)
  const [department, setDepartment] = useState<string>(ALL)
  const [session, setSession] = useState<string>(ALL)
  const [profession, setProfession] = useState<string>(ALL)
  const [page, setPage] = useState(1)

  // Load filter option lists once.
  useEffect(() => {
    alumniService
      .filterOptions()
      .then(setOptions)
      .catch(() => void 0)
  }, [])

  const fetchDirectory = useCallback(async () => {
    setLoading(true)
    try {
      const filters: AlumniFilters = {
        search: debouncedSearch || undefined,
        batch: batch === ALL ? undefined : batch,
        department: department === ALL ? undefined : department,
        session: session === ALL ? undefined : session,
        profession: profession === ALL ? undefined : profession,
        page,
        per_page: 12,
      }
      const res = await alumniService.directory(filters)
      setRows(res.data)
      setMeta(res.meta)
    } catch (e) {
      toast({ title: 'Failed to load directory', description: extractApiError(e).message, variant: 'error' })
    } finally {
      setLoading(false)
    }
  }, [debouncedSearch, batch, department, session, profession, page, toast])

  useEffect(() => {
    void fetchDirectory()
  }, [fetchDirectory])

  useEffect(() => {
    setPage(1)
  }, [debouncedSearch, batch, department, session, profession])

  const hasActiveFilters =
    batch !== ALL || department !== ALL || session !== ALL || profession !== ALL || !!search

  const resetFilters = () => {
    setSearch('')
    setBatch(ALL)
    setDepartment(ALL)
    setSession(ALL)
    setProfession(ALL)
  }

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold">Alumni Directory</h1>
        <p className="text-muted-foreground">
          Search and connect with fellow alumni across batches and departments.
        </p>
      </div>

      <Card>
        <CardContent className="space-y-4 p-4">
          <div className="relative">
            <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
            <Input
              placeholder="Search by name, batch or department…"
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              className="pl-9"
            />
          </div>

          <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <FilterSelect
              label="Batch"
              value={batch}
              onChange={setBatch}
              options={options.batches}
            />
            <FilterSelect
              label="Department"
              value={department}
              onChange={setDepartment}
              options={options.departments}
            />
            <FilterSelect
              label="Session"
              value={session}
              onChange={setSession}
              options={options.sessions}
            />
            <FilterSelect
              label="Profession"
              value={profession}
              onChange={setProfession}
              options={options.professions}
            />
          </div>

          {hasActiveFilters && (
            <div className="flex items-center gap-2 text-sm text-muted-foreground">
              <SlidersHorizontal className="h-4 w-4" />
              <span>Filters applied</span>
              <Button variant="ghost" size="sm" onClick={resetFilters} className="h-7">
                <X className="h-3.5 w-3.5" />
                Clear
              </Button>
            </div>
          )}
        </CardContent>
      </Card>

      {loading ? (
        <div className="flex h-64 items-center justify-center">
          <Spinner className="h-8 w-8" />
        </div>
      ) : rows.length === 0 ? (
        <Card>
          <CardContent className="py-16 text-center text-sm text-muted-foreground">
            No alumni match your search.
          </CardContent>
        </Card>
      ) : (
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
          {rows.map((p) => (
            <AlumniCard key={p.id} profile={p} />
          ))}
        </div>
      )}

      {meta && <Pagination meta={meta} onPageChange={setPage} />}
    </div>
  )
}

function FilterSelect({
  label,
  value,
  onChange,
  options,
}: {
  label: string
  value: string
  onChange: (v: string) => void
  options: string[]
}) {
  return (
    <Select value={value} onValueChange={onChange}>
      <SelectTrigger>
        <SelectValue placeholder={label} />
      </SelectTrigger>
      <SelectContent>
        <SelectItem value={ALL}>All {label.toLowerCase()}s</SelectItem>
        {options.map((opt) => (
          <SelectItem key={opt} value={opt}>
            {opt}
          </SelectItem>
        ))}
      </SelectContent>
    </Select>
  )
}
